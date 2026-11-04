<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use App\Services\AI\ExplainerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Nettoie le contenu DEJA en base, releve par l'audit de qualite du 2026-11-04.
 *
 * Le generateur a ete corrige pour l'avenir ; ce qui a ete fabrique avant reste
 * servi tel quel. Cinq etapes, chacune nommee :
 *
 *  - gabarits-tef : les anciens exercices manuels du TEF (passage « Phrase
 *    d'entrainement », choix Bon / Mauvais / Super) et leurs copies a
 *    l'identique sont RETIRES DU SERVICE ;
 *  - lettres : des choix commencent par « D Dance group » ; apres le melange,
 *    la lettre affichee contredit la position. On enleve le prefixe et on
 *    remet les choix dans l'ordre de leurs lettres ;
 *  - ecoutes : le son ne dit que la question, ou rien du tout. On y met le
 *    passage et on efface la piste, pour que prepla:fabriquer-medias la refasse ;
 *  - explications : on compte les questions corrigees automatiquement sans
 *    explication ; on ne les complete par l'IA que sur demande EXPLICITE ;
 *  - doublons : meme premiere question, meme niveau, meme examen. On garde le
 *    plus ancien.
 *
 * TROIS REGLES :
 *
 * 1. Sans --appliquer, rien n'est ecrit : la commande annonce seulement.
 * 2. Rien n'est supprime. « Retirer » veut dire poser content.retire : les
 *    tentatives des apprenants gardent leur exercice, et le scope global
 *    d'Exercise (SCOPE_EN_SERVICE) l'ecarte de TOUTE requete qui tire dans le
 *    vivier ou compose une epreuve blanche ; on ne le retrouve plus que par
 *    son numero (historique, tentative, revision).
 * 3. Avant toute ecriture, les lignes touchees sont copiees dans
 *    storage/app/nettoyage-vivier/<date>.json. --restaurer=<fichier> les remet.
 *
 * Les exercices des centres et ceux des lecons personnelles ne sont jamais
 * touches : ils appartiennent a quelqu'un.
 */
class NettoyerVivier extends Command
{
    protected $signature = 'prepla:nettoyer-vivier
        {--appliquer : ecrit vraiment ; sans cette option, simple rapport}
        {--etape=* : limiter a certaines etapes}
        {--restaurer= : remettre les lignes sauvegardees dans ce fichier}
        {--limite=0 : en explications, s arreter apres ce nombre d appels a l IA (questions distinctes)}';

    protected $description = 'Retire, corrige ou complete le contenu existant du vivier (rapport seulement sans --appliquer)';

    /** Dans l'ordre ou elles tournent : un doublon retire n'a plus a etre corrige. */
    public const ETAPES = ['gabarits-tef', 'doublons', 'lettres', 'ecoutes', 'explications'];

    /** Formats ou chaque question porte VOLONTAIREMENT sa propre courte piste. */
    private const PISTE_PAR_QUESTION = ['dictation', 'listen-repeat', 'listen-choose-response', 'integrated-task'];

    /** Questions envoyees a l'IA entre deux enregistrements. */
    private const LOT = 20;

    /** @var array<int, Exercise> copies de travail, une par exercice touche */
    private array $copies = [];

    /** @var array<int, array{content: mixed, questions: mixed}> etat d'origine des lignes ecrites */
    private array $sauvegarde = [];

    private ?string $fichierSauvegarde = null;

    private bool $appliquer = false;

    public function handle(ExplainerService $explainer): int
    {
        // La meme instance peut servir deux fois dans un meme processus (tests,
        // Artisan::call) : on repart toujours d'un etat vierge.
        $this->copies = [];
        $this->sauvegarde = [];
        $this->fichierSauvegarde = null;
        $this->appliquer = (bool) $this->option('appliquer');

        if ($this->option('restaurer')) {
            return $this->restaurer((string) $this->option('restaurer'));
        }

        $demandees = array_values(array_filter((array) $this->option('etape')));
        $inconnues = array_diff($demandees, self::ETAPES);
        if ($inconnues !== []) {
            $this->error('Etape inconnue : '.implode(', ', $inconnues).'. Etapes possibles : '.implode(', ', self::ETAPES).'.');

            return self::FAILURE;
        }
        $etapes = $demandees === [] ? self::ETAPES : array_values(array_intersect(self::ETAPES, $demandees));

        $this->line($this->appliquer
            ? '<fg=yellow>Mode ecriture : les lignes touchees seront sauvegardees avant d etre modifiees.</>'
            : '<fg=cyan>Rapport seulement : rien ne sera ecrit. Relancer avec --appliquer pour le faire.</>');

        foreach (array_diff($etapes, ['explications']) as $etape) {
            $this->newLine();
            $this->info('== '.$etape.' ==');
            match ($etape) {
                'gabarits-tef' => $this->gabaritsTef(),
                'doublons' => $this->doublons(),
                'lettres' => $this->lettres(),
                'ecoutes' => $this->ecoutes(),
            };
        }

        if ($this->appliquer) {
            $this->ecrire();
        }

        if (in_array('explications', $etapes, true)) {
            $this->newLine();
            $this->info('== explications ==');
            // L'IA coute : on ne la sollicite que si l'etape est NOMMEE, jamais
            // parce qu'on a lance la commande entiere avec --appliquer.
            $this->explications($explainer, $this->appliquer && in_array('explications', $demandees, true));
        }

        $this->newLine();
        if ($this->fichierSauvegarde) {
            $this->info(count($this->sauvegarde).' exercice(s) modifie(s). Sauvegarde : '.$this->fichierSauvegarde);
            $this->line('Pour revenir en arriere : php artisan prepla:nettoyer-vivier --restaurer='.basename($this->fichierSauvegarde).' --appliquer');
        } elseif ($this->appliquer) {
            $this->info('Rien a modifier.');
        } else {
            $aEcrire = count(array_filter($this->copies, fn (Exercise $e) => $e->isDirty(['content', 'questions'])));
            $this->info($aEcrire.' exercice(s) seraient modifies. Relancer avec --appliquer pour le faire.');
        }

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------
    // Etape 1 : les anciens gabarits manuels du TEF
    // ------------------------------------------------------------------

    /**
     * Reconnus par leur CONTENU, pas par leurs numeros : les numeros ne sont pas
     * les memes d'une base a l'autre, et un numero faux retirerait un bon
     * exercice. Les copies a l'identique sont retirees TOUTES : l'audit les a
     * trouvees semees du A1 au C2, aucune n'est a sa place.
     */
    private function gabaritsTef(): void
    {
        $candidats = $this->vivierLibre()
            ->whereHas('exam', fn ($q) => $q->where('slug', 'tef'))
            ->where('is_ai_generated', false)
            ->whereNull('catalog_key')
            ->orderBy('id')
            ->get()
            ->map(fn (Exercise $e) => $this->copie($e))
            ->reject(fn (Exercise $e) => $this->estRetire($e));

        $retires = 0;
        $restants = collect();

        foreach ($candidats as $exercice) {
            if ($this->estUnGabarit($exercice)) {
                $this->retirer($exercice, 'gabarits-tef', 'gabarit « Phrase d entrainement » / Bon-Mauvais-Super');
                $this->line(sprintf('  #%-6d %-3s gabarit', $exercice->id, $exercice->difficulty));
                $retires++;
            } else {
                $restants->push($exercice);
            }
        }

        foreach ($restants->groupBy(fn (Exercise $e) => $this->empreinteComplete($e)) as $groupe) {
            if ($groupe->count() < 2) {
                continue;
            }
            $ids = $groupe->pluck('id')->all();
            foreach ($groupe as $exercice) {
                $this->retirer($exercice, 'gabarits-tef', 'copie a l identique de #'.implode(', #', array_diff($ids, [$exercice->id])));
                $this->line(sprintf('  #%-6d %-3s copie de #%s', $exercice->id, $exercice->difficulty, implode(', #', array_diff($ids, [$exercice->id]))));
                $retires++;
            }
        }

        $this->line($retires.' ancien(s) exercice(s) manuel(s) du TEF '.($this->appliquer ? 'retire(s) du service.' : 'a retirer du service.'));
    }

    private function estUnGabarit(Exercise $exercice): bool
    {
        $passage = $this->normaliser($this->passage($exercice));
        if (preg_match("/^phrase d'entra[iî]nement/u", $passage)) {
            return true;
        }

        $questions = $this->questionsDe($exercice);
        if ($questions === []) {
            return false;
        }

        foreach ($questions as $question) {
            $choix = array_map(fn ($o) => $this->normaliser($o), Exercise::optionList($question['options'] ?? null));
            sort($choix);
            if ($choix !== ['bon', 'mauvais', 'super']) {
                return false;
            }
        }

        return true;
    }

    // ------------------------------------------------------------------
    // Etape 5 : les doublons du vivier
    // ------------------------------------------------------------------

    /**
     * On ne retire que les copies dont la premiere question est la MEME, choix
     * compris (dans n'importe quel ordre : ils ont ete melanges). Une consigne
     * generique (« Complete les notes. ») est partagee par des exercices tout a
     * fait differents ; le texte seul ne suffit donc pas a les confondre. Les
     * groupes qui ne se ressemblent que par le texte sont listes, a revoir a la
     * main.
     */
    private function doublons(): void
    {
        $exercices = $this->vivierLibre()
            ->orderBy('id')
            ->get()
            ->map(fn (Exercise $e) => $this->copie($e))
            ->reject(fn (Exercise $e) => $this->estRetire($e));

        $retires = 0;
        $groupes = 0;

        $parQuestion = $exercices->groupBy(fn (Exercise $e) => $this->clePremiereQuestion($e, true))
            ->forget('');

        foreach ($parQuestion as $groupe) {
            if ($groupe->count() < 2) {
                continue;
            }
            $groupes++;

            // On garde le plus ancien. Un exercice de la serie preparee ou des
            // epreuves verifiees (catalog_key) n'est jamais retire : sa
            // bibliotheque le retrouverait par sa cle et le resservirait.
            $tries = $groupe->sortBy(fn (Exercise $e) => [$e->catalog_key === null ? 1 : 0, (string) $e->created_at, $e->id])->values();
            $garde = $tries->first();
            foreach ($tries->slice(1) as $exercice) {
                if ($exercice->catalog_key !== null) {
                    continue;
                }
                $this->retirer($exercice, 'doublons', 'meme premiere question que #'.$garde->id);
                $retires++;
            }
            $this->line(sprintf('  %s %-3s garde #%d, retire #%s — « %s »',
                $garde->exam?->slug, $garde->difficulty, $garde->id,
                implode(', #', $tries->slice(1)->whereNull('catalog_key')->pluck('id')->all()),
                mb_substr($this->texteDeQuestion($this->questionsDe($garde)[0] ?? []), 0, 60)));
        }

        // Meme texte, choix differents : peut-etre le meme exercice regenere,
        // peut-etre une consigne generique. On le dit, on n'y touche pas.
        $aRevoir = $exercices->reject(fn (Exercise $e) => $this->estRetire($e))
            ->groupBy(fn (Exercise $e) => $this->clePremiereQuestion($e, false))
            ->forget('')
            ->filter(fn ($g) => $g->count() > 1);
        foreach ($aRevoir->take(15) as $groupe) {
            $this->line(sprintf('  <fg=yellow>a revoir</> %s %-3s #%s — meme texte, choix differents',
                $groupe->first()->exam?->slug, $groupe->first()->difficulty, implode(', #', $groupe->pluck('id')->all())));
        }

        $this->line($groupes.' groupe(s) de doublons, '.$retires.' exercice(s) '.($this->appliquer ? 'retire(s)' : 'a retirer')
            .($aRevoir->isNotEmpty() ? ' ; '.$aRevoir->count().' groupe(s) a revoir a la main.' : '.'));
    }

    /** Examen, niveau et premiere question ; '' quand il n'y a rien a comparer. */
    private function clePremiereQuestion(Exercise $exercice, bool $avecChoix): string
    {
        $premiere = $this->questionsDe($exercice)[0] ?? null;
        if (! is_array($premiere)) {
            return '';
        }

        $texte = $this->normaliser($this->texteDeQuestion($premiere));
        if ($texte === '') {
            return '';
        }

        $cle = [$exercice->exam_id, $exercice->difficulty, $texte];
        if ($avecChoix) {
            $cle[] = $this->empreinteDesChoix($premiere);
        }

        return md5(json_encode($cle, JSON_UNESCAPED_UNICODE));
    }

    // ------------------------------------------------------------------
    // Etape 2 : les lettres en tete des choix
    // ------------------------------------------------------------------

    /**
     * « D Dance group », « C Fireworks show », « A Spring Festival »… : apres le
     * melange, la lettre affichee n'est plus celle de la position. Quand TOUS les
     * choix portent une lettre, que ces lettres sont distinctes et vont de A a la
     * derniere sans trou, la bonne reponse est le choix designe par la position
     * marquee : on remet les choix dans l'ordre de leurs lettres, on enleve le
     * prefixe, et la reponse devient la lettre de ce choix. Position et lettre
     * disent alors la meme chose.
     */
    private function lettres(): void
    {
        $corrigees = 0;
        $exercicesCorriges = 0;
        $incoherents = [];

        foreach ($this->vivier()->orderBy('id')->lazyById(200) as $brut) {
            $exercice = $this->copies[$brut->id] ?? $brut;
            // Les epreuves verifiees et la serie preparee sont ecrites a la
            // main : leurs lettres renvoient aux annonces du texte (« A -
            // Deutschkurs am Abend ») et ne sont jamais melangees.
            if ($this->estRetire($exercice) || $exercice->catalog_key !== null) {
                continue;
            }

            $questions = $this->questionsDe($exercice);
            $touche = false;

            foreach ($questions as $i => $question) {
                if (! is_array($question)) {
                    continue;
                }
                $lettrees = $this->choixLettres($question['options'] ?? null);
                // Des lettres deja a leur place ne contredisent rien : elles
                // renvoient souvent aux textes A, B, C du passage. On les garde.
                if ($lettrees === null || array_column($lettrees, 'lettre') === array_map(fn ($k) => chr(65 + $k), array_keys($lettrees))) {
                    continue;
                }

                $attendue = $question['correct_answer'] ?? null;
                $lettre = is_scalar($attendue) ? strtoupper(trim((string) $attendue)) : '';
                if (! preg_match('/^[A-Z]$/', $lettre) || ord($lettre) - 65 >= count($lettrees)) {
                    $incoherents[] = sprintf('#%d q%d : reponse « %s » hors des choix', $exercice->id, $i + 1, is_scalar($attendue) ? $attendue : json_encode($attendue));

                    continue;
                }

                $rang = ord($lettre) - 65;
                $juste = $lettrees[$rang];

                // L'explication defend-elle un AUTRE choix que celui marque ?
                // On le signale et on ne touche a rien : qui a raison, de la
                // reponse ou de l'explication, se juge en lisant.
                $brute = $question['explanation'] ?? null;
                $explication = mb_strtolower(match (true) {
                    is_string($brute) => $brute,
                    is_array($brute) => (string) json_encode($brute, JSON_UNESCAPED_UNICODE),
                    default => '',
                });
                if ($explication !== '' && ! str_contains($explication, mb_strtolower($juste['texte']))) {
                    $autres = array_filter($lettrees, fn ($l, $k) => $k !== $rang
                        && mb_strlen($l['texte']) >= 4
                        && str_contains($explication, mb_strtolower($l['texte'])), ARRAY_FILTER_USE_BOTH);
                    if ($autres !== []) {
                        $incoherents[] = sprintf('#%d q%d : reponse marquee « %s », l explication parle de « %s »',
                            $exercice->id, $i + 1, $juste['texte'], reset($autres)['texte']);

                        continue;
                    }
                }

                // Ordre des lettres : la position K porte la lettre K.
                $ordre = array_keys($lettrees);
                usort($ordre, fn ($a, $b) => strcmp($lettrees[$a]['lettre'], $lettrees[$b]['lettre']));
                $reordonner = fn ($liste) => (is_array($liste) && array_is_list($liste) && count($liste) === count($ordre))
                    ? array_map(fn ($k) => $liste[$k], $ordre) : $liste;

                $question['options'] = array_map(fn ($k) => $lettrees[$k]['texte'], $ordre);
                foreach (['image_prompts', 'image_options', 'image_prompts_urls', 'image_options_urls'] as $parallele) {
                    if (isset($question[$parallele])) {
                        $question[$parallele] = $reordonner($question[$parallele]);
                    }
                }
                $question['correct_answer'] = $juste['lettre'];

                $questions[$i] = $question;
                $touche = true;
                $corrigees++;
            }

            if ($touche) {
                $exercice = $this->copie($exercice);
                $exercice->questions = $questions;
                $exercicesCorriges++;
                $this->line(sprintf('  #%-6d %s %-3s lettres retirees', $exercice->id, $exercice->exam?->slug, $exercice->difficulty));
            }
        }

        foreach (array_slice($incoherents, 0, 30) as $ligne) {
            $this->line('  <fg=yellow>a revoir</> '.$ligne);
        }
        if (count($incoherents) > 30) {
            $this->line('  … et '.(count($incoherents) - 30).' autre(s)');
        }

        $this->line($corrigees.' question(s) dans '.$exercicesCorriges.' exercice(s) '.($this->appliquer ? 'corrigee(s)' : 'a corriger')
            .' ; '.count($incoherents).' cas incoherent(s) laisse(s) tels quels.');
    }

    /**
     * Les choix, s'ils portent TOUS « lettre + espace » avec des lettres
     * distinctes qui vont de A sans trou. Null sinon.
     *
     * @return array<int, array{lettre: string, texte: string}>|null
     */
    private function choixLettres(mixed $options): ?array
    {
        if (! is_array($options) || count($options) < 2 || ! array_is_list($options)) {
            return null;
        }

        $lettrees = [];
        foreach ($options as $option) {
            if (! is_string($option) || ! preg_match('/^\s*([A-Z])\s+(\S.*)$/su', $option, $m)) {
                return null;
            }
            $lettrees[] = ['lettre' => $m[1], 'texte' => trim($m[2])];
        }

        $lettres = array_column($lettrees, 'lettre');
        $attendues = array_map(fn ($k) => chr(65 + $k), array_keys($lettrees));
        $triees = $lettres;
        sort($triees);

        return $triees === $attendues ? $lettrees : null;
    }

    // ------------------------------------------------------------------
    // Etape 3 : les ecoutes dont le son ne dit pas le passage
    // ------------------------------------------------------------------

    /**
     * Le lecteur joue la piste de la question et cache le passage en ecoute : si
     * la piste ne dit que la question, le texte a comprendre n'est jamais
     * entendu. On ne corrige que quand la cause est sure :
     *  - piste vide, ou egale a la question ou a la consigne ;
     *  - piste trois fois plus courte que le passage ET identique sur toutes
     *    les questions (une seule piste pour tout l'exercice).
     * Des pistes courtes MAIS differentes d'une question a l'autre peuvent etre
     * voulues (un petit dialogue par item, comme au Goethe A1 Horen Teil 1) :
     * elles sont listees, pas modifiees.
     */
    private function ecoutes(): void
    {
        $aRefaire = 0;
        $questionsRefaites = 0;
        $sansTexte = [];
        $aRevoir = [];

        foreach ($this->vivier()->with('exerciseType', 'exam.language')->orderBy('id')->lazyById(200) as $brut) {
            $exercice = $this->copies[$brut->id] ?? $brut;
            if ($this->estRetire($exercice) || ! $exercice->estUneEcoute()
                || in_array($exercice->exerciseType?->component_key, self::PISTE_PAR_QUESTION, true)) {
                continue;
            }

            $questions = $this->questionsDe($exercice);
            $contenu = is_array($exercice->content) ? $exercice->content : [];
            $passage = trim($this->passage($exercice));
            $consigne = $this->normaliser((string) ($contenu['instructions'] ?? $contenu['instruction'] ?? ''));

            $pistes = [];
            foreach ($questions as $i => $question) {
                if (is_array($question)) {
                    $pistes[$i] = $this->pisteExplicite($exercice, $question);
                }
            }
            $pisteUnique = count(array_unique(array_map(fn ($p) => $this->normaliser($p), $pistes))) <= 1;

            $touche = false;
            foreach ($pistes as $i => $piste) {
                $question = $questions[$i];
                $normale = $this->normaliser($piste);

                // Sans piste ecrite, le son est fabrique a partir du passage
                // (Exercise::texteParle). Si le fichier porte l'empreinte du
                // passage, ou s'il n'existe pas encore, tout va bien : on ne
                // touche qu'a un son dont on ignore ce qu'il dit.
                if ($normale === '' && $passage !== '' && $this->sonDuPassage($exercice, $question, $passage)) {
                    continue;
                }

                $raison = match (true) {
                    $normale === '' => 'piste vide, son d origine inconnue',
                    $normale === $this->normaliser($this->texteDeQuestion($question)) => 'piste = question',
                    $consigne !== '' && $normale === $consigne => 'piste = consigne',
                    $passage !== '' && $this->mots($piste) * 3 < $this->mots($passage) => $pisteUnique ? 'piste < 1/3 du passage' : 'courte, differente par question',
                    default => null,
                };
                if ($raison === null) {
                    continue;
                }

                if ($passage === '' || $this->normaliser($passage) === $normale) {
                    $sansTexte[] = sprintf('#%d q%d (%s) : aucun passage pour la remplacer', $exercice->id, $i + 1, $raison);

                    continue;
                }
                if ($raison === 'courte, differente par question') {
                    $aRevoir[] = sprintf('#%d q%d : %d mots, passage de %d', $exercice->id, $i + 1, $this->mots($piste), $this->mots($passage));

                    continue;
                }

                // Le passage devient la piste ; l'ancienne adresse est effacee
                // pour que prepla:fabriquer-medias en refasse une (il ne touche
                // jamais une question qui en a deja une).
                $question['audio_text'] = $passage;
                unset($question['audio_url'], $question['audio_script']);
                $questions[$i] = $question;
                $questionsRefaites++;
                $touche = true;
                $this->line(sprintf('  #%-6d %s %-3s q%d %s', $exercice->id, $exercice->exam?->slug, $exercice->difficulty, $i + 1, $raison));
            }

            if ($touche) {
                $exercice = $this->copie($exercice);
                // Le lecteur des seances lit d'abord content.audio_text : s'il
                // portait la meme piste trop courte, il faut le remettre aussi.
                $piste = $this->normaliser((string) ($contenu['audio_text'] ?? ''));
                if ($piste !== '' && $piste !== $this->normaliser($passage) && $this->mots((string) $contenu['audio_text']) * 3 < $this->mots($passage)) {
                    $contenu['audio_text'] = $passage;
                    unset($contenu['audio_url']);
                    $exercice->content = $contenu;
                }
                $exercice->questions = $questions;
                $aRefaire++;
            }
        }

        foreach (array_slice($aRevoir, 0, 15) as $ligne) {
            $this->line('  <fg=yellow>a revoir</> '.$ligne);
        }
        foreach (array_slice($sansTexte, 0, 15) as $ligne) {
            $this->line('  <fg=red>sans texte</> '.$ligne);
        }

        $this->line($questionsRefaites.' question(s) dans '.$aRefaire.' ecoute(s) '.($this->appliquer ? 'remise(s) sur leur passage' : 'a remettre sur leur passage')
            .' ; '.count($aRevoir).' a revoir ; '.count($sansTexte).' sans aucun texte.');
        if ($aRefaire > 0) {
            $this->line('Ensuite, pour refaire les pistes : php artisan prepla:fabriquer-medias --son --sec (pour voir), puis sans --sec.');
        }
    }

    /**
     * Le son de cette question vient-il du passage ? Vrai aussi quand il n'y en
     * a pas encore : prepla:fabriquer-medias le fera a partir du passage. Meme
     * nom de fichier que DeepgramTtsService::speak (empreinte texte + langue),
     * meme choix de langue que FabriquerMedias ; une piste a deux voix (/el-)
     * n'est fabriquee qu'a partir de ce meme texte.
     */
    private function sonDuPassage(Exercise $exercice, array $question, string $passage): bool
    {
        $adresse = $question['audio_url'] ?? null;
        if (! is_string($adresse) || trim($adresse) === '' || str_contains($adresse, '/el-')) {
            return true;
        }

        $langue = match ($exercice->exam?->language?->name) {
            'Allemand' => 'de',
            'Français' => 'fr',
            default => 'en',
        };

        return str_contains($adresse, md5(trim($passage).$langue));
    }

    /** La piste ecrite pour cette question, sans retomber sur le passage. */
    private function pisteExplicite(Exercise $exercice, array $question): string
    {
        foreach (['audio_text', 'audio_script'] as $cle) {
            if (is_string($question[$cle] ?? null) && trim($question[$cle]) !== '') {
                return trim($question[$cle]);
            }
        }
        $contenu = is_array($exercice->content) ? $exercice->content : [];
        foreach (['audio_text', 'transcript', 'script', 'dialogue'] as $cle) {
            if (is_string($contenu[$cle] ?? null) && trim($contenu[$cle]) !== '') {
                return trim($contenu[$cle]);
            }
        }

        return '';
    }

    // ------------------------------------------------------------------
    // Etape 4 : les explications manquantes
    // ------------------------------------------------------------------

    /**
     * Le compte porte sur le VIVIER LIBRE, le perimetre de l'audit. Les epreuves
     * blanches sont composees de COPIES a l'identique d'exercices du vivier
     * (MockExamComposer) : les compter, et surtout les payer, une fois par copie
     * ferait ecrire et facturer plusieurs fois la meme explication. On regroupe
     * donc les questions par contenu : un seul appel a l'IA par question
     * distincte, et son texte est recopie dans chaque exemplaire, copies des
     * epreuves blanches et des noeuds comprises. Une question qui n'existe que
     * dans une epreuve blanche ou un noeud n'est ni comptee ni payee.
     */
    private function explications(ExplainerService $explainer, bool $completer): void
    {
        /** @var array<string, array{libres: list<array{int, int}>, copies: list<array{int, int}>}> $groupes */
        $groupes = [];
        $compte = [];       // examen|niveau|format => nombre de questions du vivier libre
        $questionsLibres = 0;

        foreach ($this->vivier()->with('exerciseType', 'exam.language')->orderBy('id')->lazyById(200) as $brut) {
            $exercice = $this->copies[$brut->id] ?? $brut;
            if ($this->estRetire($exercice)) {
                continue;
            }
            $libre = $exercice->node_id === null && $exercice->mock_exam_id === null;
            $format = (string) ($exercice->exerciseType?->component_key ?? '?');

            foreach ($this->questionsDe($exercice) as $i => $question) {
                if (! is_array($question) || ! Exercise::questionIsAnswerable($question, $format)) {
                    continue;
                }
                $type = $question['type'] ?? $format;
                if (in_array($type, Exercise::AI_EVALUATED_TYPES, true) || in_array($format, Exercise::AI_EVALUATED_TYPES, true)) {
                    continue;
                }
                if (! $this->sansExplication($question['explanation'] ?? null)) {
                    continue;
                }

                $cle = $this->empreinteQuestion($exercice, $question, $format);
                $groupes[$cle] ??= ['libres' => [], 'copies' => []];
                if ($libre) {
                    $groupes[$cle]['libres'][] = [$exercice->id, $i];
                    $cleCompte = ($exercice->exam?->slug ?? '?').'|'.$exercice->difficulty.'|'.$format;
                    $compte[$cleCompte] = ($compte[$cleCompte] ?? 0) + 1;
                    $questionsLibres++;
                } else {
                    $groupes[$cle]['copies'][] = [$exercice->id, $i];
                }
            }
        }

        // Seul le vivier libre declenche un appel ; une copie ne recoit que ce
        // qui a ete ecrit pour son original.
        $aEcrire = array_values(array_filter($groupes, fn ($g) => $g['libres'] !== []));
        $copiesSuivantes = array_sum(array_map(fn ($g) => count($g['copies']), $aEcrire));

        ksort($compte);
        $this->table(['Examen', 'Niveau', 'Format', 'Sans explication'], array_map(
            fn ($cle, $n) => [...explode('|', $cle), $n], array_keys($compte), $compte
        ));
        $this->line($questionsLibres.' question(s) corrigee(s) automatiquement sans explication dans le vivier libre, '
            .count($aEcrire).' distincte(s) : autant d appel(s) a l IA.');
        if ($copiesSuivantes > 0) {
            $this->line($copiesSuivantes.' copie(s) dans les epreuves blanches ou les noeuds recevront la meme explication, sans appel de plus.');
        }

        if (! $completer) {
            if ($aEcrire !== []) {
                $this->line('Pour les completer par l IA (payant) : php artisan prepla:nettoyer-vivier --etape=explications --appliquer [--limite=N]');
            }

            return;
        }

        $limite = (int) $this->option('limite');
        if ($limite > 0) {
            $aEcrire = array_slice($aEcrire, 0, $limite);
        }

        $faites = 0;
        $recopiees = 0;
        $echecs = 0;
        foreach (array_chunk($aEcrire, self::LOT) as $lot) {
            foreach ($lot as $groupe) {
                [$id, $rang] = $groupe['libres'][0];
                $modele = $this->exerciceDeTravail($id);
                if (! $modele) {
                    continue;
                }
                $texte = $this->demanderExplication($explainer, $modele, $this->questionsDe($modele)[$rang]);
                if ($texte === null) {
                    $echecs++;

                    continue;
                }
                $faites++;

                foreach ([...$groupe['libres'], ...$groupe['copies']] as [$idExemplaire, $rangExemplaire]) {
                    $exemplaire = $this->exerciceDeTravail($idExemplaire);
                    $questions = $exemplaire ? $this->questionsDe($exemplaire) : [];
                    if (! is_array($questions[$rangExemplaire] ?? null) || ! $this->sansExplication($questions[$rangExemplaire]['explanation'] ?? null)) {
                        continue;
                    }
                    $questions[$rangExemplaire]['explanation'] = $texte;
                    $exemplaire->questions = $questions;
                    if ($idExemplaire !== $id) {
                        $recopiees++;
                    }
                }
            }
            // Enregistre lot par lot : une panne de l'IA au milieu ne fait pas
            // perdre ce qui a deja ete paye.
            $this->ecrire();
            $this->line('  '.$faites.' explication(s) ecrite(s)…');
        }

        $this->line($faites.' explication(s) ajoutee(s) ('.$faites.' appel(s) a l IA), '.$recopiees.' recopiee(s) dans des exemplaires identiques, '
            .$echecs.' echec(s). A relire avant de les annoncer.');
    }

    /** La copie de travail de cet exercice, chargee si aucune etape ne l'a encore touche. */
    private function exerciceDeTravail(int $id): ?Exercise
    {
        if (isset($this->copies[$id])) {
            return $this->copies[$id];
        }
        $exercice = Exercise::avecLesRetires()->with('exerciseType', 'exam.language')->find($id);

        return $exercice ? $this->copie($exercice) : null;
    }

    /**
     * Ce qui fait qu'une question est LA MEME ailleurs : examen, niveau, format,
     * passage et question, sans son numero ni son explication.
     */
    private function empreinteQuestion(Exercise $exercice, array $question, string $format): string
    {
        unset($question['explanation'], $question['id']);
        ksort($question);

        return md5(json_encode([
            $exercice->exam_id, $exercice->difficulty, $format,
            $this->normaliser($this->passage($exercice)), $question,
        ], JSON_UNESCAPED_UNICODE));
    }

    private function sansExplication(mixed $explication): bool
    {
        if (is_string($explication)) {
            return trim($explication) === '';
        }

        return $explication === null || $explication === [] || $explication === false;
    }

    /**
     * Une explication courte, dans la langue de l'apprenant pour les debutants
     * (A0-A2 : il ne lit pas encore l'explication dans la langue de l'examen),
     * dans la langue de l'examen au-dela. Le vivier n'a pas d'apprenant attache :
     * le francais est la langue par defaut de l'interface.
     */
    private function demanderExplication(ExplainerService $explainer, Exercise $exercice, array $question): ?string
    {
        $langue = in_array($exercice->difficulty, ['A0', 'A1', 'A2'], true)
            ? 'French'
            : match ($exercice->exam?->language?->slug) {
                'french' => 'French',
                'german' => 'German',
                'english' => 'English',
                default => (string) ($exercice->exam?->language?->name ?: 'French'),
            };

        $choix = Exercise::optionList($question['options'] ?? null);
        $attendue = $question['correct_answers'] ?? $question['correct_order'] ?? $question['correct_answer'] ?? null;
        if (is_string($attendue) && preg_match('/^[A-Z]$/', strtoupper(trim($attendue))) && $choix !== []) {
            $rang = ord(strtoupper(trim($attendue))) - 65;
            $attendue = trim($attendue).' — '.($choix[$rang] ?? '');
        }

        $demande = "Write the explanation shown to a {$exercice->difficulty} learner after they answer this exam exercise question.\n"
            ."- Write it in {$langue}. Quote words from the exercise in their original language.\n"
            ."- 2 to 4 short sentences, no heading, no greeting, no table.\n"
            ."- Say why the expected answer is right".($choix !== [] ? ' and why the most tempting wrong choice is wrong' : '').".\n"
            .(is_array($attendue) ? "- There are several blanks or items: give one short line per item.\n" : '')
            .'- The expected answer is correct: never contradict it.';

        $contexte = json_encode(array_filter([
            'exam' => $exercice->exam?->name,
            'format' => $exercice->exerciseType?->component_key,
            'passage' => mb_substr($this->passage($exercice), 0, 1500) ?: null,
            'question' => $this->texteDeQuestion($question) ?: null,
            'options' => $choix ?: null,
            'statements' => $question['statements'] ?? null,
            'notes' => $question['notes'] ?? $question['fields'] ?? null,
            'expected_answer' => $attendue,
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $reponse = trim($explainer->explain($demande, (string) $contexte, $langue));
        } catch (\Throwable $e) {
            $this->warn('  #'.$exercice->id.' : '.$e->getMessage());

            return null;
        }

        // explain() rend un message d'excuse quand l'IA ne repond pas : ce
        // n'est pas une explication, on ne l'ecrit pas.
        if ($reponse === '' || str_starts_with($reponse, 'Désolé, je n\'ai pas pu')) {
            return null;
        }

        return $reponse;
    }

    // ------------------------------------------------------------------
    // Ecriture, sauvegarde, restauration
    // ------------------------------------------------------------------

    /** Sauvegarde d'abord l'etat d'origine, puis enregistre les copies modifiees. */
    private function ecrire(): void
    {
        $modifies = array_filter($this->copies, fn (Exercise $e) => $e->isDirty(['content', 'questions']));
        if ($modifies === []) {
            return;
        }

        foreach ($modifies as $id => $exercice) {
            $this->sauvegarde[$id] ??= [
                'content' => json_decode((string) $exercice->getRawOriginal('content'), true),
                'questions' => json_decode((string) $exercice->getRawOriginal('questions'), true),
            ];
        }

        if ($this->fichierSauvegarde === null) {
            $dossier = storage_path('app/nettoyage-vivier');
            File::ensureDirectoryExists($dossier);
            $base = now()->format('Y-m-d_His');
            $chemin = $dossier.DIRECTORY_SEPARATOR.$base.'.json';
            for ($n = 2; File::exists($chemin); $n++) {
                $chemin = $dossier.DIRECTORY_SEPARATOR.$base.'-'.$n.'.json';
            }
            $this->fichierSauvegarde = $chemin;
        }

        // Le fichier est reecrit en entier AVANT la base : si l'ecriture du
        // disque echoue, rien n'est touche.
        $ecrit = File::put($this->fichierSauvegarde, json_encode([
            'commande' => 'prepla:nettoyer-vivier',
            'cree_le' => now()->toIso8601String(),
            'exercices' => $this->sauvegarde,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($ecrit === false) {
            throw new \RuntimeException('Sauvegarde impossible : '.$this->fichierSauvegarde);
        }

        DB::transaction(function () use ($modifies) {
            foreach ($modifies as $exercice) {
                $exercice->saveQuietly();
            }
        });
    }

    private function restaurer(string $fichier): int
    {
        $chemin = File::exists($fichier) ? $fichier : storage_path('app/nettoyage-vivier/'.basename($fichier));
        if (! File::exists($chemin)) {
            $this->error('Sauvegarde introuvable : '.$fichier);

            return self::FAILURE;
        }

        $donnees = json_decode((string) File::get($chemin), true);
        if (! is_array($donnees['exercices'] ?? null)) {
            $this->error('Ce fichier n est pas une sauvegarde de prepla:nettoyer-vivier.');

            return self::FAILURE;
        }

        $remis = 0;
        $absents = [];
        foreach ($donnees['exercices'] as $id => $etat) {
            $exercice = Exercise::avecLesRetires()->find((int) $id);
            if (! $exercice) {
                $absents[] = $id;

                continue;
            }
            if ($this->appliquer) {
                $exercice->content = $etat['content'];
                $exercice->questions = $etat['questions'];
                $exercice->saveQuietly();
            }
            $remis++;
        }

        if ($absents !== []) {
            $this->warn(count($absents).' exercice(s) n existent plus : #'.implode(', #', $absents));
        }
        // Ce qui a ete fait APRES le nettoyage sur ces lignes (une piste refaite
        // par prepla:fabriquer-medias, par exemple) est perdu avec la restauration.
        $this->info($this->appliquer
            ? $remis.' exercice(s) remis dans leur etat d avant le nettoyage.'
            : $remis.' exercice(s) seraient remis dans leur etat d avant le nettoyage. Relancer avec --appliquer pour le faire.');

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------
    // Outils
    // ------------------------------------------------------------------

    /** Hors centres et lecons personnelles : du contenu qui appartient a quelqu'un. */
    private function vivier()
    {
        // Retires compris : la commande doit les voir pour ne pas les compter
        // deux fois, et pour que --restaurer les retrouve.
        return Exercise::avecLesRetires()->with('exam')->whereNull('center_id')->whereNull('lesson_id');
    }

    /** Le vivier libre : ni noeud, ni epreuve blanche. */
    private function vivierLibre()
    {
        return $this->vivier()->whereNull('node_id')->whereNull('mock_exam_id');
    }

    /** La copie de travail de cet exercice : toutes les etapes modifient la meme. */
    private function copie(Exercise $exercice): Exercise
    {
        return $this->copies[$exercice->id] ??= $exercice;
    }

    private function estRetire(Exercise $exercice): bool
    {
        return is_array($exercice->content) && ! empty($exercice->content[Exercise::CLE_RETRAIT]);
    }

    private function retirer(Exercise $exercice, string $etape, string $raison): void
    {
        $exercice = $this->copie($exercice);
        $contenu = is_array($exercice->content) ? $exercice->content : [];
        $contenu[Exercise::CLE_RETRAIT] = ['etape' => $etape, 'raison' => $raison, 'le' => now()->toDateString()];
        $exercice->content = $contenu;
    }

    /** @return array<int, mixed> */
    private function questionsDe(Exercise $exercice): array
    {
        return is_array($exercice->questions) ? array_values($exercice->questions) : [];
    }

    private function passage(Exercise $exercice): string
    {
        $contenu = is_array($exercice->content) ? $exercice->content : [];
        foreach (['passage', 'text'] as $cle) {
            if (is_string($contenu[$cle] ?? null) && trim($contenu[$cle]) !== '') {
                return $contenu[$cle];
            }
        }

        return '';
    }

    private function texteDeQuestion(array $question): string
    {
        foreach (['text', 'question', 'sentence', 'original_sentence'] as $cle) {
            if (is_string($question[$cle] ?? null) && trim($question[$cle]) !== '') {
                return trim($question[$cle]);
            }
        }

        return '';
    }

    /** Les choix d'une question, dans n'importe quel ordre (ils ont ete melanges). */
    private function empreinteDesChoix(array $question): string
    {
        $choix = array_map(fn ($o) => $this->normaliser($o), Exercise::optionList($question['options'] ?? null));
        sort($choix);

        $autres = [];
        foreach (['statements', 'notes', 'fields', 'items', 'labels'] as $cle) {
            if (is_array($question[$cle] ?? null)) {
                $autres[$cle] = $question[$cle];
            }
        }

        return md5(json_encode([$choix, $autres], JSON_UNESCAPED_UNICODE));
    }

    /** Passage et questions, pour reconnaitre deux copies a l'identique. */
    private function empreinteComplete(Exercise $exercice): string
    {
        $questions = array_map(
            fn ($q) => is_array($q) ? [$this->normaliser($this->texteDeQuestion($q)), $this->empreinteDesChoix($q)] : null,
            $this->questionsDe($exercice)
        );

        return md5(json_encode([$this->normaliser($this->passage($exercice)), $questions], JSON_UNESCAPED_UNICODE));
    }

    private function normaliser(mixed $texte): string
    {
        $texte = is_scalar($texte) ? (string) $texte : '';
        $texte = str_replace(["\u{2019}", "\u{2018}", "\u{00A0}"], ["'", "'", ' '], $texte);

        return trim(preg_replace('/\s+/u', ' ', mb_strtolower($texte)));
    }

    private function mots(string $texte): int
    {
        return count(preg_split('/\s+/u', trim($texte), -1, PREG_SPLIT_NO_EMPTY));
    }
}
