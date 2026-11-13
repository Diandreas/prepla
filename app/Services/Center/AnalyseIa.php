<?php

namespace App\Services\Center;

use App\Models\Assignment;
use App\Models\CenterAnalysis;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;
use App\Services\AI\MistralService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * L'analyse IA d'un eleve, d'une classe ou du centre : le probleme principal,
 * le levier de progression et ce que l'enseignant peut faire cette semaine.
 *
 * La fiche montrait des chiffres et des fautes, mais personne ne disait au
 * responsable « voila ce qui bloque, voila quoi faire ». On rassemble les FAITS
 * (scores par competence et leur evolution sur 4 semaines, regularite, devoirs,
 * faiblesses et profil de SuiviApprenant), on les donne a Mistral avec
 * interdiction d'inventer, et on relit sa reponse avant de la montrer.
 *
 * TROIS REGLES :
 *
 * 1. Jamais d'erreur a l'ecran. Si l'IA ne repond pas, repond mal ou si la cle
 *    manque, la meme structure est produite par des regles simples a partir
 *    des memes faits (source « regles »). Moins de 3 exercices : on le dit, on
 *    n'invente pas d'analyse.
 * 2. Le cout reste maitrise. L'analyse est gardee en base (center_analyses) et
 *    resservie tant que les DONNEES n'ont pas change et qu'elle a moins de 24 h.
 *    L'empreinte porte sur les donnees elles-memes (tentatives, fautes, profil,
 *    classes, devoirs), pas sur les faits calcules depuis « maintenant » : sinon
 *    elle changerait chaque jour, ou des qu'une vieille tentative franchit une
 *    fenetre de 7 ou 14 jours, sans que l'eleve ait rien fait.
 *    La lecture (mode « lire », le GET) n'appelle jamais l'IA. La generation
 *    (« auto », un POST) est bornee : une relance par sujet toutes les 15 min,
 *    un plafond d'appels par centre et par heure, un verrou pour que deux
 *    ouvertures simultanees ne paient pas deux appels. « Actualiser » (« forcer »)
 *    est limite a une fois par heure et par sujet par le controleur.
 *    Si l'IA echoue, une analyse IA recente est gardee plutot que remplacee par
 *    les regles.
 * 3. Rien de personnel n'est envoye a l'IA : le prenom seulement, jamais l'e-mail
 *    ni le nom complet. Dans une classe ou un centre, les eleves et les classes
 *    sont des references (E1, C2...) que l'on remet en noms au retour.
 */
class AnalyseIa
{
    /** En dessous, une analyse serait inventee. */
    public const MIN_TENTATIVES = 3;

    /** Une analyse IA est resservie pendant 24 h si les faits n'ont pas change. */
    public const VIE_IA_HEURES = 24;

    /** Une analyse par regles (IA en panne) est retentee au bout d'une heure. */
    public const VIE_REGLES_HEURES = 1;

    /** « Actualiser » : une fois par heure et par sujet. */
    public const ACTUALISATION_SECONDES = 3600;

    /** Regeneration automatique d'un meme sujet (donnees changees) : au plus une toutes les 15 min. */
    public const RELANCE_AUTO_SECONDES = 900;

    /** Appels IA declenches depuis l'interface : au plus 60 par centre et par heure. */
    public const PLAFOND_CENTRE_HEURE = 60;

    /** Si l'IA echoue, une analyse IA de moins de 72 h est gardee plutot que remplacee par les regles. */
    public const GARDER_IA_HEURES = 72;

    public const SUJETS = ['eleve', 'classe', 'centre'];

    /**
     * lire : jamais d'appel ni d'ecriture (le GET) ;
     * auto : genere seulement si l'analyse n'est plus a jour (le POST de la carte, la commande) ;
     * forcer : regenere meme si elle est a jour (« Actualiser »).
     */
    public const MODES = ['lire', 'auto', 'forcer'];

    private const COMPETENCES = [
        'reading' => 'Compréhension écrite',
        'listening' => 'Compréhension orale',
        'writing' => 'Production écrite',
        'speaking' => 'Production orale',
        'grammar' => 'Grammaire',
        'vocabulary' => 'Vocabulaire',
    ];

    private const GRAVITES = ['strength' => 'point fort', 'minor' => 'à travailler', 'major' => 'priorité'];

    /** Ce qu'un enseignant peut donner cette semaine, competence par competence. */
    private const ACTIONS = [
        'listening' => [
            'Faire écouter un document court au format de l’épreuve, une première fois sans les questions, puis avec.',
            'Faire noter les mots-clés (chiffres, lieux, négations) pendant l’écoute avant de répondre.',
            'Revoir en classe les questions ratées avec la transcription sous les yeux.',
        ],
        'reading' => [
            'Donner un texte court au format de l’épreuve et faire souligner la phrase qui justifie chaque réponse.',
            'Travailler le repérage rapide : 5 minutes pour trouver une information précise dans un texte.',
            'Revoir les questions ratées en cherchant ensemble le piège (synonyme, négation).',
        ],
        'writing' => [
            'Donner une production écrite courte au format de l’épreuve, avec un plan imposé en 3 parties.',
            'Faire réécrire le même texte après correction en corrigeant les fautes relevées.',
            'Fournir une liste de connecteurs à réemployer obligatoirement dans la prochaine copie.',
        ],
        'speaking' => [
            'Organiser une prise de parole de 2 minutes enregistrée, sur un sujet du format de l’épreuve.',
            'Faire préparer 3 idées et 3 connecteurs avant de parler, puis réécouter l’enregistrement ensemble.',
            'Travailler l’interaction en binôme : questions-réponses chronométrées.',
        ],
        'grammar' => [
            'Donner une série d’exercices ciblés sur le point de grammaire fautif, puis le réemployer à l’écrit.',
            'Commencer le cours par 5 phrases à corriger tirées de ses propres fautes.',
        ],
        'vocabulary' => [
            'Donner une liste de 15 mots du thème de l’examen à réemployer dans une production courte.',
            'Faire reformuler un texte en remplaçant les mots répétés par des synonymes.',
        ],
    ];

    private const FORMATS = [
        'listening' => 'Compréhension orale au format de l’examen',
        'reading' => 'Compréhension écrite au format de l’examen',
        'writing' => 'Production écrite courte avec plan imposé',
        'speaking' => 'Prise de parole enregistrée de 2 minutes',
        'grammar' => 'Exercices ciblés de grammaire',
        'vocabulary' => 'Exercices de vocabulaire thématique',
    ];

    public function __construct(
        private readonly MistralService $mistral,
        private readonly SuiviApprenant $suivi,
    ) {}

    // ─────────────────────────── Points d'entree ───────────────────────────

    /**
     * @param  string  $mode  lire | auto | forcer (voir MODES)
     * @param  bool  $plafond  false pour la commande : son cout est annonce et lance a la main
     * @return array<string, mixed>
     */
    public function eleve(LanguageCenter $centre, User $eleve, string $mode = 'auto', bool $plafond = true): array
    {
        $faits = $this->faitsEleve($centre, $eleve);
        $total = $faits['activite']['exercices_total'];

        if ($total < self::MIN_TENTATIVES) {
            return $this->insuffisant('eleve', $total === 0
                ? 'Pas encore assez de données : cet élève n’a réalisé aucun exercice. L’analyse apparaîtra après ses 3 premiers exercices.'
                : "Pas encore assez de données : {$total} exercice".($total > 1 ? 's' : '').' réalisé'.($total > 1 ? 's' : '').'. Il en faut au moins '.self::MIN_TENTATIVES.' pour une analyse fiable.');
        }

        return $this->obtenir($centre, 'eleve', $eleve->id, $faits, [], $this->empreinteDe($centre, 'eleve', $eleve), $mode, $plafond);
    }

    /** @return array<string, mixed> */
    public function classe(LanguageCenter $centre, Classroom $classe, string $mode = 'auto', bool $plafond = true): array
    {
        ['faits' => $faits, 'annuaire' => $annuaire] = $this->faitsClasse($centre, $classe);

        if ($faits['activite']['exercices_total'] < self::MIN_TENTATIVES) {
            return $this->insuffisant('classe', 'Pas encore assez de données : les élèves de cette classe ont réalisé moins de '.self::MIN_TENTATIVES.' exercices.');
        }

        return $this->obtenir($centre, 'classe', $classe->id, $faits, $annuaire, $this->empreinteDe($centre, 'classe', $classe), $mode, $plafond);
    }

    /** @return array<string, mixed> */
    public function centre(LanguageCenter $centre, string $mode = 'auto', bool $plafond = true): array
    {
        ['faits' => $faits, 'annuaire' => $annuaire] = $this->faitsCentre($centre);

        if ($faits['activite']['exercices_28_jours'] < self::MIN_TENTATIVES) {
            return $this->insuffisant('centre', 'Pas encore assez de données : moins de '.self::MIN_TENTATIVES.' exercices réalisés dans le centre ces 4 dernières semaines.');
        }

        return $this->obtenir($centre, 'centre', $centre->id, $faits, $annuaire, $this->empreinteDe($centre, 'centre', $centre), $mode, $plafond);
    }

    /** La cle du limiteur d'actualisation d'un sujet. */
    public static function cleActualisation(LanguageCenter $centre, string $type, int $id): string
    {
        return "analyse-ia:{$centre->id}:{$type}:{$id}";
    }

    /** La cle de la relance automatique d'un sujet (donnees changees). */
    private static function cleRelance(LanguageCenter $centre, string $type, int $id): string
    {
        return "analyse-ia-auto:{$centre->id}:{$type}:{$id}";
    }

    /** La cle du plafond d'appels IA du centre. */
    private static function clePlafond(LanguageCenter $centre): string
    {
        return "analyse-ia-centre:{$centre->id}";
    }

    /** Secondes avant de pouvoir actualiser ce sujet (0 : tout de suite). */
    public static function actualisableDans(LanguageCenter $centre, string $type, int $id): int
    {
        $cle = self::cleActualisation($centre, $type, $id);

        return RateLimiter::tooManyAttempts($cle, 1) ? RateLimiter::availableIn($cle) : 0;
    }

    /**
     * L'analyse gardee pour ce sujet, si elle est encore a jour (memes donnees,
     * encore dans sa duree de vie). La commande de pre-generation s'en sert pour
     * annoncer le nombre d'appels a l'IA avant de les faire.
     */
    public function analyseAJour(LanguageCenter $centre, string $type, Model $objet): ?CenterAnalysis
    {
        $existante = CenterAnalysis::where('center_id', $centre->id)
            ->where('sujet_type', $type)->where('sujet_id', $objet->getKey())->first();

        return $existante !== null && $this->valide($existante, $this->empreinteDe($centre, $type, $objet)) ? $existante : null;
    }

    // ─────────────────────────── Cache et appel ───────────────────────────

    /**
     * @param  array<string, mixed>  $faits
     * @param  array<string, array{id: int, nom: string}>  $annuaire  references E1, C1... vers les vrais noms
     * @return array<string, mixed>
     */
    private function obtenir(LanguageCenter $centre, string $type, int $id, array $faits, array $annuaire, string $empreinte, string $mode, bool $plafond): array
    {
        $existante = $this->enBase($centre, $type, $id);

        if ($mode !== 'forcer' && $existante !== null && $this->valide($existante, $empreinte)) {
            return $this->rendu($centre, $existante, true);
        }

        // Lecture seule : jamais d'appel ni d'ecriture. La carte demandera la generation en POST.
        if ($mode === 'lire') {
            return $existante !== null ? $this->rendu($centre, $existante, false) : $this->etat($type, 'a_generer', 'Analyse en cours de préparation…');
        }

        // Les donnees changent a chaque exercice : une relance automatique au plus
        // toutes les 15 min par sujet, l'analyse en place est montree entre-temps.
        $cleRelance = self::cleRelance($centre, $type, $id);
        if ($mode === 'auto' && $existante !== null && RateLimiter::tooManyAttempts($cleRelance, 1)) {
            return $this->rendu($centre, $existante, false);
        }

        // Deux ouvertures simultanees ne paient pas deux appels : la seconde montre
        // l'analyse en place, ou attend que la premiere ait fini.
        $verrou = Cache::lock("analyse-ia-verrou:{$centre->id}:{$type}:{$id}", 120);
        if (! $verrou->get()) {
            return $existante !== null ? $this->rendu($centre, $existante, false) : $this->etat($type, 'en_cours', 'Analyse en cours de préparation…');
        }

        try {
            // Relue sous verrou : l'autre requete vient peut-etre de la produire.
            $existante = $this->enBase($centre, $type, $id);
            if ($mode !== 'forcer' && $existante !== null && $this->valide($existante, $empreinte)) {
                return $this->rendu($centre, $existante, true);
            }

            $repli = match ($type) {
                'eleve' => $this->reglesEleve($faits),
                'classe' => $this->reglesClasse($faits, $annuaire),
                'centre' => $this->reglesCentre($faits, $annuaire),
            };

            $contenu = null;
            $clePlafond = self::clePlafond($centre);
            if (! $plafond || ! RateLimiter::tooManyAttempts($clePlafond, self::PLAFOND_CENTRE_HEURE)) {
                RateLimiter::hit($cleRelance, self::RELANCE_AUTO_SECONDES);
                if ($plafond) {
                    RateLimiter::hit($clePlafond, 3600);
                }

                try {
                    $donnees = $this->decoder($this->mistral->chat($this->messages($type, $faits, $centre->langueAnalyses())));
                    if ($donnees !== null) {
                        $contenu = match ($type) {
                            'eleve' => $this->nettoyerEleve($donnees, $repli),
                            'classe' => $this->nettoyerClasse($donnees, $repli, $annuaire),
                            'centre' => $this->nettoyerCentre($donnees, $repli, $annuaire),
                        };
                    }
                } catch (\Throwable $e) {
                    // L'IA ne fait jamais tomber la fiche : on passe aux regles.
                    Log::warning('analyse-ia: appel en echec', ['type' => $type, 'id' => $id, 'erreur' => $e->getMessage()]);
                }
            } else {
                Log::warning('analyse-ia: plafond du centre atteint', ['centre' => $centre->id, 'type' => $type, 'id' => $id]);
            }

            // L'IA n'a pas repondu (429, panne, plafond) : une analyse IA recente
            // vaut mieux que les regles. On la garde, sans toucher a sa date.
            if ($contenu === null && $existante !== null && $existante->source === 'ia'
                && $existante->generated_at?->gt(now()->subHours(self::GARDER_IA_HEURES))) {
                return $this->rendu($centre, $existante, false, 'L’IA n’a pas pu refaire l’analyse : la précédente est conservée.');
            }

            $valeurs = [
                'contenu' => $contenu ?? $repli,
                'source' => $contenu !== null ? 'ia' : 'regles',
                'faits_empreinte' => $empreinte,
                'generated_at' => now(),
            ];
            $cle = ['center_id' => $centre->id, 'sujet_type' => $type, 'sujet_id' => $id];

            try {
                $ligne = CenterAnalysis::updateOrCreate($cle, $valeurs);
            } catch (UniqueConstraintViolationException) {
                // Une autre requete (verrou non partage, autre serveur) a insere la
                // ligne entre-temps : on la met a jour au lieu d'echouer.
                $ligne = CenterAnalysis::where($cle)->firstOrFail();
                $ligne->update($valeurs);
            }

            return $this->rendu($centre, $ligne, true);
        } finally {
            $verrou->release();
        }
    }

    private function enBase(LanguageCenter $centre, string $type, int $id): ?CenterAnalysis
    {
        return CenterAnalysis::where('center_id', $centre->id)
            ->where('sujet_type', $type)->where('sujet_id', $id)->first();
    }

    private function valide(CenterAnalysis $analyse, string $empreinte): bool
    {
        $vie = $analyse->source === 'ia' ? self::VIE_IA_HEURES : self::VIE_REGLES_HEURES;

        return $analyse->faits_empreinte === $empreinte
            && $analyse->generated_at !== null
            && $analyse->generated_at->gt(now()->subHours($vie));
    }

    /**
     * L'empreinte des DONNEES d'un sujet, independante de l'heure : elle ne change
     * que si l'eleve (ou la classe) a fait quelque chose, qu'une faute est
     * maitrisee, que le profil, la composition des classes ou les devoirs changent.
     * Les faits envoyes a l'IA, eux, sont recalcules a chaque generation.
     */
    public function empreinteDe(LanguageCenter $centre, string $type, Model $objet): string
    {
        $donnees = match ($type) {
            'eleve' => $this->signatureEleve($centre, $objet),
            'classe' => $this->signatureClasse($objet),
            'centre' => $this->signatureCentre($centre),
        };

        // En anglais, une autre empreinte : changer de langue refait les analyses.
        // Les centres en francais gardent la leur, rien n'est refait chez eux.
        $langue = $centre->langueAnalyses() === 'en' ? ['langue' => 'en'] : [];

        return hash('sha256', (string) json_encode(['v' => 2] + $langue + $donnees));
    }

    /** @return array<string, mixed> */
    private function signatureEleve(LanguageCenter $centre, User $eleve): array
    {
        $classes = $eleve->classrooms()
            ->where('center_id', $centre->id)
            ->wherePivot('role_in_class', 'student')
            ->orderBy('classrooms.id')
            ->get(['classrooms.id', 'classrooms.name', 'classrooms.level', 'classrooms.exam_id']);
        $profil = $eleve->profile;

        return [
            'nom' => $eleve->name,
            'profil' => [$profil?->target_exam_id, $profil?->current_level, $profil?->exam_date?->toDateString()],
            'classes' => $classes->map(fn (Classroom $c) => [$c->id, $c->name, $c->level, $c->exam_id])->all(),
            'tentatives' => $this->signatureTable(UserExerciseAttempt::query(), [$eleve->id]),
            'fautes' => $this->signatureTable(UserError::query(), [$eleve->id]),
            'devoirs' => $this->signatureDevoirs($classes->pluck('id')->all()),
        ];
    }

    /** @return array<string, mixed> */
    private function signatureClasse(Classroom $classe): array
    {
        $eleves = $classe->students()->orderBy('users.id')->get(['users.id', 'users.name']);
        $ids = $eleves->pluck('id')->all();

        return [
            'classe' => [$classe->name, $classe->level, $classe->exam_id, $classe->archived_at?->toIso8601String()],
            'eleves' => $eleves->map(fn (User $u) => [$u->id, $u->name])->all(),
            'tentatives' => $this->signatureTable(UserExerciseAttempt::query(), $ids),
            'fautes' => $this->signatureTable(UserError::query(), $ids),
            'devoirs' => $this->signatureDevoirs([$classe->id]),
        ];
    }

    /** @return array<string, mixed> */
    private function signatureCentre(LanguageCenter $centre): array
    {
        $ids = $centre->students()->orderBy('users.id')->pluck('users.id')->all();
        $classes = $centre->classrooms()->whereNull('archived_at')->orderBy('id')->get();

        return [
            'eleves' => $ids,
            'classes' => $classes->map(fn (Classroom $c) => [
                $c->id, $c->name, $c->level, $c->exam_id, $c->students()->orderBy('users.id')->pluck('users.id')->all(),
            ])->all(),
            'tentatives' => $this->signatureTable(UserExerciseAttempt::query(), $ids),
            'fautes' => $this->signatureTable(UserError::query(), $ids),
            'devoirs' => $this->signatureDevoirs($classes->pluck('id')->all()),
        ];
    }

    /**
     * Nombre de lignes, derniere ligne et derniere modification : une tentative
     * ajoutee, corrigee apres coup ou une faute maitrisee changent l'empreinte.
     *
     * @param  list<int>  $ids
     * @return list<mixed>
     */
    private function signatureTable(Builder $requete, array $ids): array
    {
        if ($ids === []) {
            return [0];
        }

        $r = $requete->whereIn('user_id', $ids)
            ->selectRaw('count(*) as n, max(id) as dernier, max(updated_at) as modifie')
            ->toBase()->first();

        return [(int) $r->n, $r->dernier !== null ? (int) $r->dernier : null, $r->modifie];
    }

    /**
     * Les devoirs publies et leur echeance : un devoir qui passe en retard est un
     * vrai changement.
     *
     * @param  list<int>  $classes
     * @return list<mixed>
     */
    private function signatureDevoirs(array $classes): array
    {
        if ($classes === []) {
            return [];
        }

        return Assignment::whereIn('classroom_id', $classes)
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->orderBy('id')->get(['id', 'due_at', 'updated_at'])
            ->map(fn (Assignment $d) => [$d->id, $d->updated_at?->toIso8601String(), $d->isOverdue()])
            ->all();
    }

    /** @return array<string, mixed> */
    private function rendu(LanguageCenter $centre, CenterAnalysis $analyse, bool $aJour, ?string $message = null): array
    {
        return [
            'sujet' => $analyse->sujet_type,
            'etat' => 'pret',
            'a_jour' => $aJour,
            'source' => $analyse->source,
            'generee_le' => $analyse->generated_at?->toIso8601String(),
            'message' => $message,
            'contenu' => $analyse->contenu,
            'actualisable_dans' => self::actualisableDans($centre, $analyse->sujet_type, (int) $analyse->sujet_id),
        ];
    }

    /** @return array<string, mixed> */
    private function insuffisant(string $type, string $message): array
    {
        return $this->etat($type, 'insuffisant', $message);
    }

    /**
     * Un etat sans analyse a montrer : insuffisant, a_generer (la carte la
     * demande en POST), en_cours (une autre requete la prepare).
     *
     * @return array<string, mixed>
     */
    private function etat(string $type, string $etat, string $message): array
    {
        return [
            'sujet' => $type,
            'etat' => $etat,
            'a_jour' => false,
            'source' => null,
            'generee_le' => null,
            'message' => $message,
            'contenu' => null,
            'actualisable_dans' => 0,
        ];
    }

    // ─────────────────────────── Les faits ───────────────────────────

    /**
     * Ce que l'on sait d'un eleve, sans rien de personnel au-dela du prenom.
     *
     * @return array<string, mixed>
     */
    public function faitsEleve(LanguageCenter $centre, User $eleve): array
    {
        $fuseau = $this->suivi->fuseau($centre);
        $maintenant = now();
        $suivi = $this->suivi->pourLeCentre($centre);

        $lignes = $this->lignes([$eleve->id], null, 400);
        $total = UserExerciseAttempt::where('user_id', $eleve->id)->count();
        $recentes = $lignes->filter(fn ($l) => $l->created_at->gte($maintenant->copy()->subDays(28)));
        $derniere = $lignes->first()?->created_at;

        $classes = $eleve->classrooms()
            ->where('center_id', $centre->id)
            ->wherePivot('role_in_class', 'student')
            ->with('exam:id,name')
            ->get(['classrooms.id', 'classrooms.name', 'classrooms.level', 'classrooms.exam_id']);

        $profil = $eleve->profile;

        return [
            'eleve' => [
                'prenom' => $this->prenom($eleve->name),
                'examen_vise' => $profil?->targetExam?->name ?? $classes->first()?->exam?->name,
                'niveau_actuel' => $profil?->current_level,
                'jours_avant_examen' => $profil?->exam_date ? (int) max(0, $maintenant->copy()->startOfDay()->diffInDays(Carbon::parse($profil->exam_date), false)) : null,
                // L'examen et le niveau des classes, pas leur nom (texte libre du centre).
                'classes' => $classes->map(fn (Classroom $c) => implode(' ', array_filter([$c->exam?->name, $c->level])) ?: 'classe')->values()->all(),
            ],
            'activite' => [
                'exercices_total' => $total,
                'exercices_28_jours' => $recentes->count(),
                'jours_actifs_28_jours' => $this->joursActifs($recentes, $fuseau),
                'jours_depuis_derniere_activite' => $derniere ? (int) $derniere->copy()->startOfDay()->diffInDays($maintenant->copy()->startOfDay()) : null,
                'reussite_moyenne' => $this->moyenne($lignes),
                'reussite_28_jours' => $this->moyenne($recentes),
                'minutes_par_exercice' => $lignes->isNotEmpty() ? round($lignes->avg('time_spent') / 60, 1) : null,
            ],
            'semaines' => $this->semaines($lignes, $maintenant),
            'competences' => $this->competences($lignes, $maintenant),
            'faiblesses' => collect($suivi->faiblesses($eleve))->take(5)->map(fn (array $f) => array_filter([
                'faiblesse' => $f['libelle'],
                'fautes' => $f['nombre'],
                'competences' => array_values(array_map(fn ($c) => self::COMPETENCES[$c] ?? $c, $f['competences'])),
                'exemple' => $this->exemple($f['exemples'][0] ?? null, $eleve->name),
            ], fn ($v) => $v !== null && $v !== []))->values()->all(),
            'profil_production' => collect($suivi->profil($eleve))->take(6)->map(fn (array $o) => [
                'dimension' => $o['dimension'],
                'gravite' => self::GRAVITES[$o['gravite']] ?? $o['gravite'],
                'observation' => $this->anonymiser((string) $o['observation'], $eleve->name, 200),
                'competence' => self::COMPETENCES[$o['competence'] ?? ''] ?? null,
            ])->values()->all(),
            'devoirs' => $this->devoirsEleve($eleve, $classes->pluck('id')->all()),
        ];
    }

    /**
     * Ce que l'on sait d'une classe. Les eleves y sont des references E1, E2...
     *
     * @return array{faits: array<string, mixed>, annuaire: array<string, array{id: int, nom: string}>}
     */
    public function faitsClasse(LanguageCenter $centre, Classroom $classe): array
    {
        $fuseau = $this->suivi->fuseau($centre);
        $maintenant = now();
        $classe->loadMissing('exam:id,name');

        $eleves = $classe->students()->orderBy('users.id')->get(['users.id', 'users.name']);
        $ids = $eleves->pluck('id')->all();
        $lignes = $this->lignes($ids, $maintenant->copy()->subDays(28));
        $parEleve = $lignes->groupBy('user_id');
        $dernieres = $ids ? UserExerciseAttempt::whereIn('user_id', $ids)->groupBy('user_id')->selectRaw('user_id, max(created_at) as derniere')->pluck('derniere', 'user_id') : collect();
        $total = $ids ? UserExerciseAttempt::whereIn('user_id', $ids)->count() : 0;

        $annuaire = [];
        $resumes = [];
        foreach ($eleves->take(40)->values() as $i => $eleve) {
            $ref = 'E'.($i + 1);
            $annuaire[$ref] = ['id' => $eleve->id, 'nom' => $eleve->name];
            $siennes = $parEleve->get($eleve->id, collect());
            $faible = collect($this->competences($siennes, $maintenant))->firstWhere('exercices', '>=', 2);
            $derniere = $dernieres->get($eleve->id);

            $resumes[] = [
                'ref' => $ref,
                'prenom' => $this->prenom($eleve->name),
                'exercices_28_jours' => $siennes->count(),
                'reussite_28_jours' => $this->moyenne($siennes),
                'jours_depuis_derniere_activite' => $derniere ? (int) Carbon::parse($derniere, 'UTC')->startOfDay()->diffInDays($maintenant->copy()->startOfDay()) : null,
                'competence_la_plus_faible' => $faible ? $faible['competence'].' ('.$faible['reussite'].' %)' : null,
            ];
        }

        $actifs7 = $lignes->filter(fn ($l) => $l->created_at->gte($maintenant->copy()->subDays(7)))->pluck('user_id')->unique()->count();

        return [
            'faits' => [
                // Pas le nom de la classe (texte libre du centre) : l'examen et le niveau suffisent.
                'classe' => [
                    'examen' => $classe->exam?->name,
                    'niveau' => $classe->level,
                    'effectif' => $eleves->count(),
                ],
                'activite' => [
                    'exercices_total' => $total,
                    'exercices_28_jours' => $lignes->count(),
                    'eleves_actifs_7_jours' => $actifs7,
                    'eleves_sans_activite_28_jours' => $eleves->count() - $parEleve->count(),
                    'jours_actifs_28_jours' => $this->joursActifs($lignes, $fuseau),
                    'reussite_28_jours' => $this->moyenne($lignes),
                ],
                'semaines' => $this->semaines($lignes, $maintenant),
                'competences' => $this->competences($lignes, $maintenant),
                'faiblesses_collectives' => $this->faiblessesCollectives($ids),
                'devoirs' => $this->devoirsClasse([$classe->id]),
                'eleves' => $resumes,
            ],
            'annuaire' => $annuaire,
        ];
    }

    /**
     * Ce que l'on sait du centre. Les classes y sont des references C1, C2...
     *
     * @return array{faits: array<string, mixed>, annuaire: array<string, array{id: int, nom: string}>}
     */
    public function faitsCentre(LanguageCenter $centre): array
    {
        $fuseau = $this->suivi->fuseau($centre);
        $maintenant = now();

        $ids = $centre->students()->pluck('users.id')->all();
        $lignes = $this->lignes($ids, $maintenant->copy()->subDays(28));
        $classes = $centre->classrooms()->whereNull('archived_at')->with('exam:id,name')->orderBy('id')->get();

        $annuaire = [];
        $resumes = [];
        foreach ($classes->take(30)->values() as $i => $classe) {
            $ref = 'C'.($i + 1);
            $annuaire[$ref] = ['id' => $classe->id, 'nom' => $classe->name];
            $membres = $classe->students()->pluck('users.id')->all();
            $siennes = $lignes->whereIn('user_id', $membres);
            $semaines = $this->semaines($siennes, $maintenant);
            $devoirs = $this->devoirsClasse([$classe->id]);

            $resumes[] = [
                'ref' => $ref,
                'examen' => $classe->exam?->name,
                'niveau' => $classe->level,
                'effectif' => count($membres),
                'eleves_actifs_7_jours' => $siennes->filter(fn ($l) => $l->created_at->gte($maintenant->copy()->subDays(7)))->pluck('user_id')->unique()->count(),
                'exercices_28_jours' => $siennes->count(),
                'reussite_28_jours' => $this->moyenne($siennes),
                'reussite_semaine_en_cours' => $semaines[3]['reussite'],
                'reussite_il_y_a_3_semaines' => $semaines[0]['reussite'],
                'devoirs_en_retard' => array_sum(array_column($devoirs, 'en_retard')),
            ];
        }

        return [
            'faits' => [
                'centre' => [
                    'eleves' => count($ids),
                    'classes' => $classes->count(),
                    'examens' => $classes->pluck('exam.name')->filter()->unique()->values()->all(),
                ],
                'activite' => [
                    'exercices_28_jours' => $lignes->count(),
                    'eleves_actifs_7_jours' => $lignes->filter(fn ($l) => $l->created_at->gte($maintenant->copy()->subDays(7)))->pluck('user_id')->unique()->count(),
                    'eleves_actifs_28_jours' => $lignes->pluck('user_id')->unique()->count(),
                    'jours_actifs_28_jours' => $this->joursActifs($lignes, $fuseau),
                    'reussite_28_jours' => $this->moyenne($lignes),
                ],
                'semaines' => $this->semaines($lignes, $maintenant),
                'competences' => $this->competences($lignes, $maintenant),
                'faiblesses_collectives' => $this->faiblessesCollectives($ids),
                'classes' => $resumes,
            ],
            'annuaire' => $annuaire,
        ];
    }

    /**
     * Les tentatives, avec leur competence et leur format. Jointure directe :
     * un exercice retire du vivier depuis compte quand meme dans le passe de l'eleve.
     *
     * @param  list<int>  $ids
     */
    private function lignes(array $ids, ?Carbon $depuis, ?int $limite = null): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $requete = UserExerciseAttempt::query()
            ->whereIn('user_exercise_attempts.user_id', $ids)
            ->leftJoin('exercises', 'exercises.id', '=', 'user_exercise_attempts.exercise_id')
            ->leftJoin('exercise_types', 'exercise_types.id', '=', 'exercises.exercise_type_id')
            ->orderByDesc('user_exercise_attempts.created_at')
            ->orderByDesc('user_exercise_attempts.id');

        if ($depuis) {
            $requete->where('user_exercise_attempts.created_at', '>=', $depuis);
        }
        if ($limite) {
            $requete->limit($limite);
        }

        return $requete->get([
            'user_exercise_attempts.user_id',
            'user_exercise_attempts.accuracy_percent',
            'user_exercise_attempts.time_spent',
            'user_exercise_attempts.created_at',
            'exercise_types.skill_type as competence',
            'exercise_types.name as format',
        ]);
    }

    private function moyenne(Collection $lignes): ?float
    {
        $notees = $lignes->whereNotNull('accuracy_percent');

        return $notees->isNotEmpty() ? round((float) $notees->avg('accuracy_percent'), 1) : null;
    }

    private function joursActifs(Collection $lignes, string $fuseau): int
    {
        return $lignes->map(fn ($l) => $l->created_at->copy()->setTimezone($fuseau)->toDateString())->unique()->count();
    }

    /**
     * Les 4 dernieres semaines, de la plus ancienne (index 0) a la semaine en cours (index 3).
     *
     * @return list<array{semaine: string, exercices: int, reussite: float|null}>
     */
    private function semaines(Collection $lignes, Carbon $maintenant): array
    {
        $noms = ['il y a 3 semaines', 'il y a 2 semaines', 'semaine dernière', 'cette semaine'];
        $semaines = [];

        for ($i = 3; $i >= 0; $i--) {
            $fin = $maintenant->copy()->subDays(7 * $i);
            $debut = $fin->copy()->subDays(7);
            $dedans = $lignes->filter(fn ($l) => $l->created_at->gt($debut) && $l->created_at->lte($fin));
            $semaines[] = ['semaine' => $noms[3 - $i], 'exercices' => $dedans->count(), 'reussite' => $this->moyenne($dedans)];
        }

        return $semaines;
    }

    /**
     * La reussite par competence, la plus faible d'abord, avec son evolution :
     * les 14 derniers jours contre les 14 jours d'avant.
     *
     * @return list<array<string, mixed>>
     */
    private function competences(Collection $lignes, Carbon $maintenant): array
    {
        $milieu = $maintenant->copy()->subDays(14);
        $debut = $maintenant->copy()->subDays(28);

        return $lignes->filter(fn ($l) => isset(self::COMPETENCES[$l->competence]))
            ->groupBy('competence')
            ->map(function (Collection $groupe, string $cle) use ($milieu, $debut) {
                $recent = $this->moyenne($groupe->filter(fn ($l) => $l->created_at->gte($milieu)));
                $avant = $this->moyenne($groupe->filter(fn ($l) => $l->created_at->gte($debut) && $l->created_at->lt($milieu)));
                $formats = $groupe->whereNotNull('format')->groupBy('format')
                    ->filter(fn (Collection $f) => $f->count() >= 2)
                    ->map(fn (Collection $f, string $nom) => ['format' => $nom, 'reussite' => $this->moyenne($f), 'exercices' => $f->count()])
                    ->filter(fn ($f) => $f['reussite'] !== null)
                    ->sortBy('reussite')
                    ->first();

                return array_filter([
                    'cle' => $cle,
                    'competence' => self::COMPETENCES[$cle],
                    'exercices' => $groupe->count(),
                    'reussite' => $this->moyenne($groupe),
                    'evolution_points_14_jours' => $recent !== null && $avant !== null ? round($recent - $avant, 1) : null,
                    'format_le_plus_faible' => $formats,
                ], fn ($v) => $v !== null);
            })
            ->filter(fn ($c) => isset($c['reussite']))
            ->sortBy('reussite')
            ->values()
            ->all();
    }

    /**
     * Les faiblesses partagees par plusieurs eleves.
     *
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    private function faiblessesCollectives(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return UserError::whereIn('user_id', $ids)
            ->where('mastered', false)
            ->whereNotNull('error_category')
            ->where('error_category', '!=', 'session_mistake')
            ->get(['user_id', 'error_category', 'subcategory'])
            ->groupBy(fn (UserError $e) => SuiviApprenant::libelleCategorie($e->error_category, $e->subcategory))
            ->map(fn (Collection $g, string $libelle) => ['faiblesse' => $libelle, 'fautes' => $g->count(), 'eleves' => $g->pluck('user_id')->unique()->count()])
            ->sortBy([['eleves', 'desc'], ['fautes', 'desc']])
            ->take(6)
            ->values()
            ->all();
    }

    /**
     * Un exemple de faute, ecrit par l'eleve : court, et nettoye de ce qui
     * pourrait l'identifier.
     *
     * @param  array<string, mixed>|null  $e
     */
    private function exemple(?array $e, ?string $nom): ?string
    {
        if (! $e || ! $e['reponse'] || ! $e['correction']) {
            return null;
        }

        return '« '.$this->anonymiser((string) $e['reponse'], $nom, 80).' » au lieu de « '.$this->anonymiser((string) $e['correction'], $nom, 80).' »';
    }

    /**
     * Retire d'un texte libre ce qui identifie quelqu'un, avant l'envoi a l'IA :
     * e-mails, liens, numeros (telephone, rue, code postal), le nom de famille de
     * l'eleve (tous ses mots sauf le prenom), puis le coupe.
     */
    private function anonymiser(string $texte, ?string $nom, int $max): string
    {
        $texte = (string) preg_replace('/[^\s@]+@[^\s@]+/u', '[e-mail]', $texte);
        $texte = (string) preg_replace('~\b(?:https?://|www\.)\S+~iu', '[lien]', $texte);
        $texte = (string) preg_replace('/\+?\d[\d\s.\-\/]{1,}\d/u', '[nombre]', $texte);

        $mots = Str::of((string) $nom)->trim()->explode(' ')->filter(fn ($m) => mb_strlen($m) >= 2)->values();
        foreach ($mots->slice(1) as $mot) {
            $texte = (string) preg_replace('/\b'.preg_quote($mot, '/').'\b/iu', '[nom]', $texte);
        }

        return Str::limit(trim($texte), $max);
    }

    /**
     * @param  list<int>  $classes
     * @return array<string, mixed>
     */
    private function devoirsEleve(User $eleve, array $classes): array
    {
        $statuts = ['rendus' => 0, 'en_cours' => 0, 'en_retard' => 0, 'a_faire' => 0];
        if ($classes === []) {
            return $statuts;
        }

        $progression = app(AssignmentProgressService::class);
        $devoirs = Assignment::whereIn('classroom_id', $classes)
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->latest('published_at')->take(10)->get();

        foreach ($devoirs as $devoir) {
            $ligne = collect($progression->perStudent($devoir))->firstWhere('user_id', $eleve->id);
            $cle = match ($ligne['status'] ?? null) {
                'done' => 'rendus', 'in_progress' => 'en_cours', 'late' => 'en_retard', 'not_started' => 'a_faire', default => null,
            };
            if ($cle) {
                $statuts[$cle]++;
            }
        }

        return $statuts;
    }

    /**
     * @param  list<int>  $classes
     * @return list<array<string, mixed>>
     */
    private function devoirsClasse(array $classes): array
    {
        $progression = app(AssignmentProgressService::class);

        return Assignment::whereIn('classroom_id', $classes)
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->latest('published_at')->take(6)->get()
            ->map(function (Assignment $devoir) use ($progression) {
                $lignes = collect($progression->perStudent($devoir));

                return [
                    'titre' => Str::limit((string) $devoir->title, 80),
                    'echeance_depassee' => $devoir->isOverdue(),
                    'rendus' => $lignes->where('status', 'done')->count(),
                    'en_retard' => $lignes->where('status', 'late')->count(),
                    'eleves' => $lignes->count(),
                ];
            })->values()->all();
    }

    private function prenom(?string $nom): string
    {
        return (string) (Str::of((string) $nom)->trim()->explode(' ')->filter()->first() ?? 'l’élève');
    }

    // ─────────────────────────── Le prompt ───────────────────────────

    /**
     * @param  array<string, mixed>  $faits
     * @return list<array{role: string, content: string}>
     */
    private function messages(string $type, array $faits, string $langue = 'fr'): array
    {
        $format = match ($type) {
            'eleve' => '{"probleme":{"titre":"...","explication":"...","preuve":"..."},"levier":{"titre":"...","actions":["...","..."]},"point_fort":"...","risque":{"niveau":"faible|moyen|eleve","raison":"..."},"prochain_devoir":{"competence":"...","format":"...","pourquoi":"..."}}',
            'classe' => '{"probleme":{"titre":"...","explication":"...","preuve":"..."},"levier":{"titre":"...","actions":["...","..."]},"point_fort":"...","risque":{"niveau":"faible|moyen|eleve","raison":"..."},"prochain_devoir":{"competence":"...","format":"...","pourquoi":"..."},"eleves_a_surveiller":[{"ref":"E1","raison":"..."}]}',
            'centre' => '{"constats":[{"titre":"...","explication":"..."}],"leviers":[{"titre":"...","action":"..."}],"point_fort":"...","classes_qui_decrochent":[{"ref":"C1","raison":"..."}]}',
        };

        $sujet = match ($type) {
            'eleve' => 'd’UN élève : son problème principal et le levier qui le fera le plus progresser',
            'classe' => 'd’UNE classe : le problème collectif, le levier pour toute la classe et les élèves à surveiller (désignés par leur « ref »)',
            'centre' => 'du centre entier : exactement 3 constats et 3 leviers, et les classes qui décrochent (désignées par leur « ref »)',
        };

        // La langue de redaction : celle choisie par le centre. Les cles du JSON
        // et les valeurs du risque (faible|moyen|eleve) ne changent pas.
        $redaction = $langue === 'en'
            ? 'Write every text value in ENGLISH (British English), in a professional and caring tone, with short sentences and no jargon. Keep the JSON keys and the risk values (faible|moyen|eleve) exactly as given.'
            : 'Français, ton professionnel et bienveillant, phrases courtes, sans jargon.';

        $systeme = <<<TXT
        Tu es conseiller pédagogique dans un centre de langues qui prépare aux examens officiels (Goethe-Zertifikat, telc, TCF, TEF, DELF, IELTS...). Tu fais l'analyse {$sujet}, pour un enseignant ou le responsable du centre.

        RÈGLES STRICTES :
        1. N'invente rien. Chaque affirmation s'appuie sur un chiffre ou un élément des FAITS fournis ; la « preuve » cite ces chiffres. Si un fait n'est pas fourni, n'en parle pas.
        2. Les réussites sont des pourcentages de bonnes réponses ; « evolution_points_14_jours » compare les 14 derniers jours aux 14 jours précédents.
        3. Les actions sont concrètes et faisables cette semaine par l'enseignant : type d'exercice à donner, format de l'épreuve visée, durée.
        4. Risque : « eleve » si l'élève (ou la classe) décroche ou ne travaille plus, « moyen » si les résultats ou la régularité sont fragiles, « faible » sinon.
        5. {$redaction} Pas de markdown.
        6. Réponds UNIQUEMENT par un objet JSON de cette forme exacte :
        {$format}
        TXT;

        return [
            ['role' => 'system', 'content' => $systeme],
            ['role' => 'user', 'content' => "FAITS :\n".json_encode($faits, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)],
        ];
    }

    // ─────────────────────────── Relire la reponse ───────────────────────────

    /** Le JSON de l'IA, meme entoure de texte ou de balises de code. */
    private function decoder(?string $brut): ?array
    {
        if (! is_string($brut) || trim($brut) === '') {
            return null;
        }

        $texte = trim((string) preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($brut)));
        $donnees = json_decode($texte, true);

        if (! is_array($donnees)) {
            $debut = strpos($texte, '{');
            $fin = strrpos($texte, '}');
            if ($debut !== false && $fin !== false && $fin > $debut) {
                $donnees = json_decode(substr($texte, $debut, $fin - $debut + 1), true);
            }
        }

        return is_array($donnees) ? $donnees : null;
    }

    private function texte(mixed $valeur, int $max = 400): ?string
    {
        if (is_int($valeur) || is_float($valeur)) {
            $valeur = (string) $valeur;
        }
        if (! is_string($valeur)) {
            return null;
        }

        $propre = trim((string) preg_replace('/\s+/u', ' ', strip_tags($valeur)));

        return $propre === '' ? null : Str::limit($propre, $max);
    }

    /** @return list<string> */
    private function listeDeTextes(mixed $valeur, int $nombre): array
    {
        return collect(is_array($valeur) ? $valeur : [])
            ->map(fn ($v) => is_array($v) ? $this->texte($v['action'] ?? $v['titre'] ?? $v['description'] ?? null, 300) : $this->texte($v, 300))
            ->filter()
            ->take($nombre)
            ->values()
            ->all();
    }

    private function niveauDeRisque(mixed $valeur): ?string
    {
        $n = Str::of((string) (is_string($valeur) ? $valeur : ''))->ascii()->lower()->trim()->value();

        return in_array($n, ['faible', 'moyen', 'eleve'], true) ? $n : null;
    }

    /**
     * Le tronc commun eleve / classe. null si l'essentiel manque : on passe aux regles.
     *
     * @param  array<string, mixed>  $d
     * @param  array<string, mixed>  $repli
     * @return array<string, mixed>|null
     */
    private function nettoyerCommun(array $d, array $repli): ?array
    {
        $probleme = is_array($d['probleme'] ?? null) ? $d['probleme'] : [];
        $levier = is_array($d['levier'] ?? null) ? $d['levier'] : [];
        $titreProbleme = $this->texte($probleme['titre'] ?? null, 140);
        $titreLevier = $this->texte($levier['titre'] ?? null, 140);
        $actions = $this->listeDeTextes($levier['actions'] ?? null, 3);

        if ($titreProbleme === null || $titreLevier === null || $actions === []) {
            return null;
        }

        $risque = is_array($d['risque'] ?? null) ? $d['risque'] : [];
        $niveau = $this->niveauDeRisque($risque['niveau'] ?? null);
        $devoir = is_array($d['prochain_devoir'] ?? null) ? $d['prochain_devoir'] : [];
        $competence = $this->texte($devoir['competence'] ?? null, 80);
        $format = $this->texte($devoir['format'] ?? null, 140);
        $pointFort = is_array($d['point_fort'] ?? null)
            ? $this->texte($d['point_fort']['titre'] ?? $d['point_fort']['explication'] ?? null, 300)
            : $this->texte($d['point_fort'] ?? null, 300);

        return [
            'probleme' => [
                'titre' => $titreProbleme,
                'explication' => $this->texte($probleme['explication'] ?? null) ?? '',
                'preuve' => $this->texte($probleme['preuve'] ?? null, 300) ?? '',
            ],
            'levier' => ['titre' => $titreLevier, 'actions' => $actions],
            'point_fort' => $pointFort ?? $repli['point_fort'],
            'risque' => $niveau !== null
                ? ['niveau' => $niveau, 'raison' => $this->texte($risque['raison'] ?? null, 300) ?? '']
                : $repli['risque'],
            'prochain_devoir' => $competence !== null && $format !== null
                ? ['competence' => $competence, 'format' => $format, 'pourquoi' => $this->texte($devoir['pourquoi'] ?? null, 300) ?? '']
                : $repli['prochain_devoir'],
        ];
    }

    /**
     * @param  array<string, mixed>  $d
     * @param  array<string, mixed>  $repli
     * @return array<string, mixed>|null
     */
    private function nettoyerEleve(array $d, array $repli): ?array
    {
        return $this->nettoyerCommun($d, $repli);
    }

    /**
     * @param  array<string, mixed>  $d
     * @param  array<string, mixed>  $repli
     * @param  array<string, array{id: int, nom: string}>  $annuaire
     * @return array<string, mixed>|null
     */
    private function nettoyerClasse(array $d, array $repli, array $annuaire): ?array
    {
        $commun = $this->nettoyerCommun($d, $repli);
        if ($commun === null) {
            return null;
        }

        $commun['eleves_a_surveiller'] = $this->references($d['eleves_a_surveiller'] ?? null, $annuaire, 6);

        return $commun;
    }

    /**
     * @param  array<string, mixed>  $d
     * @param  array<string, mixed>  $repli
     * @param  array<string, array{id: int, nom: string}>  $annuaire
     * @return array<string, mixed>|null
     */
    private function nettoyerCentre(array $d, array $repli, array $annuaire): ?array
    {
        $constats = collect(is_array($d['constats'] ?? null) ? $d['constats'] : [])
            ->map(fn ($c) => is_array($c) ? ['titre' => $this->texte($c['titre'] ?? null, 140), 'explication' => $this->texte($c['explication'] ?? null) ?? ''] : ['titre' => $this->texte($c, 140), 'explication' => ''])
            ->filter(fn ($c) => $c['titre'] !== null)->take(3)->values()->all();
        $leviers = collect(is_array($d['leviers'] ?? null) ? $d['leviers'] : [])
            ->map(fn ($l) => is_array($l) ? ['titre' => $this->texte($l['titre'] ?? null, 140), 'action' => $this->texte($l['action'] ?? $l['explication'] ?? null) ?? ''] : ['titre' => $this->texte($l, 140), 'action' => ''])
            ->filter(fn ($l) => $l['titre'] !== null)->take(3)->values()->all();

        if ($constats === [] || $leviers === []) {
            return null;
        }

        return [
            'constats' => $constats,
            'leviers' => $leviers,
            'point_fort' => $this->texte($d['point_fort'] ?? null, 300) ?? $repli['point_fort'],
            'classes_qui_decrochent' => $this->references($d['classes_qui_decrochent'] ?? null, $annuaire, 6),
        ];
    }

    /**
     * Les references rendues par l'IA (E1, C2...), remises en vrais noms. Une
     * reference inconnue est ecartee : l'IA ne designe personne qu'on ne lui a pas donne.
     *
     * @param  array<string, array{id: int, nom: string}>  $annuaire
     * @return list<array{id: int, nom: string, raison: string}>
     */
    private function references(mixed $valeur, array $annuaire, int $nombre): array
    {
        return collect(is_array($valeur) ? $valeur : [])
            ->filter(fn ($v) => is_array($v) && is_string($v['ref'] ?? null) && isset($annuaire[strtoupper(trim($v['ref']))]))
            ->map(fn (array $v) => $annuaire[strtoupper(trim($v['ref']))] + ['raison' => $this->texte($v['raison'] ?? null, 240) ?? ''])
            ->unique('id')
            ->take($nombre)
            ->values()
            ->all();
    }

    // ─────────────────────────── Le repli sans IA ───────────────────────────

    /**
     * La meme analyse, par des regles simples sur les memes faits.
     *
     * @param  array<string, mixed>  $f
     * @return array<string, mixed>
     */
    private function reglesEleve(array $f): array
    {
        $a = $f['activite'];
        $prenom = $f['eleve']['prenom'];
        $competences = $f['competences'];
        $faible = collect($competences)->first();
        $fort = collect($competences)->filter(fn ($c) => $c['exercices'] >= 2 && $c['reussite'] >= 60)->sortByDesc('reussite')->first();
        $faute = $f['faiblesses'][0] ?? null;
        $absence = $a['jours_depuis_derniere_activite'];
        $retard = $f['devoirs']['en_retard'];

        if ($absence !== null && $absence > 14) {
            $probleme = [
                'titre' => "Plus d’activité depuis {$absence} jours",
                'explication' => "{$prenom} a cessé de s’entraîner : sans pratique régulière, les acquis s’effacent avant l’examen.",
                'preuve' => "Dernier exercice il y a {$absence} jours ; {$a['jours_actifs_28_jours']} jour(s) actif(s) sur les 4 dernières semaines.",
            ];
            $levier = [
                'titre' => 'Relancer avec un objectif court et atteignable',
                'actions' => [
                    "Contacter {$prenom} cette semaine et fixer un objectif simple : 3 exercices courts d’ici 7 jours.",
                    'Donner un devoir court ('.($faible['competence'] ?? 'compétence au choix').') avec une échéance proche.',
                ],
            ];
        } elseif ($faute && $faute['fautes'] >= 3 && (! $faible || $faible['reussite'] >= 50 || $faute['fautes'] >= 5)) {
            $probleme = [
                'titre' => "Faute récurrente : {$faute['faiblesse']}",
                'explication' => 'La même erreur revient d’un exercice à l’autre : c’est un point de cours non acquis, pas une inattention.',
                'preuve' => "{$faute['fautes']} fautes relevées".(isset($faute['exemple']) ? ', par exemple '.$faute['exemple'] : '').'.',
            ];
            $levier = [
                'titre' => "Retravailler « {$faute['faiblesse']} » de façon ciblée",
                'actions' => [
                    "Reprendre en classe la règle « {$faute['faiblesse']} » à partir de ses propres fautes.",
                    'Donner une série d’exercices ciblés sur ce point, puis le faire réemployer dans une production courte.',
                ],
            ];
        } elseif ($faible) {
            $evolution = $faible['evolution_points_14_jours'] ?? null;
            $probleme = [
                'titre' => "Compétence la plus faible : {$faible['competence']}",
                'explication' => "C’est la compétence où {$prenom} perd le plus de points".($evolution !== null && $evolution < 0 ? ', et la tendance est à la baisse.' : '.'),
                'preuve' => "{$faible['reussite']} % de réussite sur {$faible['exercices']} exercice(s)"
                    .($a['reussite_moyenne'] !== null ? ", contre {$a['reussite_moyenne']} % en moyenne toutes compétences confondues" : '')
                    .($evolution !== null ? " ; évolution sur 14 jours : {$this->signe($evolution)} points" : '').'.',
            ];
            $levier = [
                'titre' => "Plus de pratique guidée en {$faible['competence']}",
                'actions' => array_slice(self::ACTIONS[$faible['cle']] ?? self::ACTIONS['reading'], 0, 3),
            ];
        } else {
            $probleme = [
                'titre' => 'Résultats encore trop peu détaillés',
                'explication' => 'Les exercices réalisés ne permettent pas encore d’isoler une compétence faible.',
                'preuve' => "{$a['exercices_total']} exercice(s) réalisé(s).",
            ];
            $levier = [
                'titre' => 'Varier les compétences travaillées',
                'actions' => ['Donner un devoir mêlant compréhension orale, compréhension écrite et production écrite pour situer l’élève.'],
            ];
        }

        if ($faute && $probleme['titre'] !== "Faute récurrente : {$faute['faiblesse']}" && count($levier['actions']) < 3) {
            $levier['actions'][] = "Revoir aussi sa faute la plus fréquente : {$faute['faiblesse']} ({$faute['fautes']} fautes).";
        }

        $pointFort = match (true) {
            $fort !== null => "{$fort['competence']} : {$fort['reussite']} % de réussite sur {$fort['exercices']} exercices.",
            ($obs = collect($f['profil_production'])->firstWhere('gravite', 'point fort')) !== null => "{$obs['dimension']} : {$obs['observation']}",
            $a['jours_actifs_28_jours'] >= 8 => "Régularité : {$a['jours_actifs_28_jours']} jours actifs sur les 4 dernières semaines.",
            default => 'Pas encore de point fort net sur les données disponibles.',
        };

        $risque = match (true) {
            $absence !== null && $absence > 7 => ['niveau' => 'eleve', 'raison' => "Aucun exercice depuis {$absence} jours."],
            $retard >= 2 => ['niveau' => 'eleve', 'raison' => "{$retard} devoirs en retard."],
            ($a['reussite_28_jours'] ?? 100) < 50 => ['niveau' => 'moyen', 'raison' => "Réussite de {$a['reussite_28_jours']} % sur les 4 dernières semaines."],
            $a['jours_actifs_28_jours'] < 4 => ['niveau' => 'moyen', 'raison' => "Seulement {$a['jours_actifs_28_jours']} jour(s) actif(s) sur les 4 dernières semaines."],
            $retard === 1 => ['niveau' => 'moyen', 'raison' => 'Un devoir en retard.'],
            default => ['niveau' => 'faible', 'raison' => "Travail régulier : {$a['jours_actifs_28_jours']} jours actifs sur 4 semaines."],
        };

        return [
            'probleme' => $probleme,
            'levier' => $levier,
            'point_fort' => $pointFort,
            'risque' => $risque,
            'prochain_devoir' => $this->prochainDevoir($faible),
        ];
    }

    /**
     * @param  array<string, mixed>  $f
     * @param  array<string, array{id: int, nom: string}>  $annuaire
     * @return array<string, mixed>
     */
    private function reglesClasse(array $f, array $annuaire): array
    {
        $a = $f['activite'];
        $effectif = max(1, $f['classe']['effectif']);
        $faible = collect($f['competences'])->first();
        $fort = collect($f['competences'])->filter(fn ($c) => $c['exercices'] >= 3 && $c['reussite'] >= 60)->sortByDesc('reussite')->first();
        $faute = collect($f['faiblesses_collectives'])->firstWhere('eleves', '>=', 2);
        $retards = array_sum(array_column($f['devoirs'], 'en_retard'));
        $partActifs = $a['eleves_actifs_7_jours'] / $effectif;

        if ($partActifs < 0.4) {
            $probleme = [
                'titre' => 'Une majorité de la classe ne s’entraîne plus',
                'explication' => 'Le travail entre les cours s’est arrêté pour la plupart des élèves.',
                'preuve' => "{$a['eleves_actifs_7_jours']} élève(s) actif(s) sur {$f['classe']['effectif']} cette semaine ; {$a['eleves_sans_activite_28_jours']} sans aucun exercice depuis 4 semaines.",
            ];
            $levier = [
                'titre' => 'Relancer la pratique avec un devoir court commun',
                'actions' => [
                    'Donner un devoir de 3 exercices courts à rendre sous 7 jours, et en faire la correction en classe.',
                    'Commencer le prochain cours par 10 minutes d’exercice sur la plateforme, pour lever les blocages techniques.',
                ],
            ];
        } elseif ($faible) {
            $probleme = [
                'titre' => "Point faible collectif : {$faible['competence']}",
                'explication' => 'C’est la compétence où la classe perd le plus de points.',
                'preuve' => "{$faible['reussite']} % de réussite sur {$faible['exercices']} exercice(s) ces 4 dernières semaines"
                    .($a['reussite_28_jours'] !== null ? ", contre {$a['reussite_28_jours']} % toutes compétences confondues" : '').'.',
            ];
            $levier = [
                'titre' => "Une séance par semaine sur « {$faible['competence']} »",
                'actions' => array_slice(self::ACTIONS[$faible['cle']] ?? self::ACTIONS['reading'], 0, 2),
            ];
        } else {
            $probleme = ['titre' => 'Pas de difficulté collective nette', 'explication' => 'Les résultats ne font pas ressortir de compétence faible commune.', 'preuve' => "{$a['exercices_28_jours']} exercice(s) ces 4 dernières semaines."];
            $levier = ['titre' => 'Varier les épreuves travaillées', 'actions' => ['Donner un devoir couvrant les quatre compétences de l’examen pour situer chaque élève.']];
        }

        if ($faute) {
            $levier['actions'][] = "Reprendre en classe « {$faute['faiblesse']} » : {$faute['fautes']} fautes chez {$faute['eleves']} élèves.";
        }

        $surveiller = collect($f['eleves'])
            ->map(function (array $e) use ($annuaire) {
                $raison = match (true) {
                    $e['jours_depuis_derniere_activite'] === null => 'Aucun exercice réalisé.',
                    $e['jours_depuis_derniere_activite'] > 7 => "Aucun exercice depuis {$e['jours_depuis_derniere_activite']} jours.",
                    $e['reussite_28_jours'] !== null && $e['reussite_28_jours'] < 50 => "Réussite de {$e['reussite_28_jours']} % sur 4 semaines.",
                    default => null,
                };

                return $raison ? $annuaire[$e['ref']] + ['raison' => $raison, 'poids' => $e['jours_depuis_derniere_activite'] ?? 999] : null;
            })
            ->filter()->sortByDesc('poids')->take(6)
            ->map(fn ($e) => ['id' => $e['id'], 'nom' => $e['nom'], 'raison' => $e['raison']])
            ->values()->all();

        return [
            'probleme' => $probleme,
            'levier' => $levier,
            'point_fort' => $fort ? "{$fort['competence']} : {$fort['reussite']} % de réussite pour la classe." : 'Pas encore de point fort collectif net.',
            'risque' => match (true) {
                $partActifs < 0.4 => ['niveau' => 'eleve', 'raison' => "{$a['eleves_actifs_7_jours']} élève(s) actif(s) sur {$f['classe']['effectif']} cette semaine."],
                $retards >= $effectif => ['niveau' => 'eleve', 'raison' => "{$retards} rendus de devoir en retard."],
                ($a['reussite_28_jours'] ?? 100) < 50 || $partActifs < 0.7 => ['niveau' => 'moyen', 'raison' => "Réussite de {$a['reussite_28_jours']} % et {$a['eleves_actifs_7_jours']} élève(s) actif(s) sur {$f['classe']['effectif']} cette semaine."],
                default => ['niveau' => 'faible', 'raison' => 'Classe active et résultats corrects.'],
            },
            'prochain_devoir' => $this->prochainDevoir($faible),
            'eleves_a_surveiller' => $surveiller,
        ];
    }

    /**
     * @param  array<string, mixed>  $f
     * @param  array<string, array{id: int, nom: string}>  $annuaire
     * @return array<string, mixed>
     */
    private function reglesCentre(array $f, array $annuaire): array
    {
        $a = $f['activite'];
        $eleves = max(1, $f['centre']['eleves']);
        $faible = collect($f['competences'])->first();
        $fort = collect($f['competences'])->sortByDesc('reussite')->first();
        $faute = $f['faiblesses_collectives'][0] ?? null;
        $s = $f['semaines'];

        $constats = [[
            'titre' => "{$a['eleves_actifs_7_jours']} élève(s) actif(s) sur {$f['centre']['eleves']} cette semaine",
            'explication' => "{$a['eleves_actifs_28_jours']} élève(s) ont travaillé au moins une fois ces 4 dernières semaines ({$a['exercices_28_jours']} exercices).",
        ]];
        if ($faible) {
            $constats[] = ['titre' => "Compétence la plus faible : {$faible['competence']}", 'explication' => "{$faible['reussite']} % de réussite sur {$faible['exercices']} exercices, contre {$a['reussite_28_jours']} % en moyenne."];
        }
        if ($s[0]['reussite'] !== null && $s[3]['reussite'] !== null) {
            $constats[] = ['titre' => 'Réussite : '.$s[0]['reussite'].' % il y a 3 semaines, '.$s[3]['reussite'].' % cette semaine', 'explication' => 'Évolution de '.$this->signe(round($s[3]['reussite'] - $s[0]['reussite'], 1)).' points sur la période.'];
        } elseif ($faute) {
            $constats[] = ['titre' => "Faute la plus répandue : {$faute['faiblesse']}", 'explication' => "{$faute['fautes']} fautes chez {$faute['eleves']} élèves."];
        }

        $leviers = [['titre' => 'Fixer un rythme minimum', 'action' => 'Demander à chaque classe 3 exercices par semaine, suivis par l’enseignant à chaque cours.']];
        if ($faible) {
            $leviers[] = ['titre' => "Renforcer « {$faible['competence']} »", 'action' => self::ACTIONS[$faible['cle']][0] ?? 'Donner un devoir ciblé sur cette compétence.'];
        }
        if ($faute) {
            $leviers[] = ['titre' => "Traiter « {$faute['faiblesse']} » dans toutes les classes concernées", 'action' => 'Une séance de remédiation à partir des fautes des élèves eux-mêmes.'];
        }
        if (count($leviers) < 3) {
            $leviers[] = ['titre' => 'Relancer les élèves inactifs', 'action' => 'Chaque enseignant contacte cette semaine les élèves sans activité depuis 7 jours.'];
        }

        $decrochent = collect($f['classes'])
            ->map(function (array $c) use ($annuaire) {
                $part = $c['effectif'] > 0 ? $c['eleves_actifs_7_jours'] / $c['effectif'] : 1;
                $baisse = $c['reussite_il_y_a_3_semaines'] !== null && $c['reussite_semaine_en_cours'] !== null
                    ? $c['reussite_semaine_en_cours'] - $c['reussite_il_y_a_3_semaines'] : 0;
                $raison = match (true) {
                    $c['effectif'] > 0 && $part < 0.4 => "{$c['eleves_actifs_7_jours']} élève(s) actif(s) sur {$c['effectif']} cette semaine.",
                    $baisse <= -10 => 'Réussite en baisse de '.abs(round($baisse, 1)).' points en 3 semaines.',
                    $c['reussite_28_jours'] !== null && $c['reussite_28_jours'] < 50 => "Réussite de {$c['reussite_28_jours']} % sur 4 semaines.",
                    default => null,
                };

                return $raison ? $annuaire[$c['ref']] + ['raison' => $raison] : null;
            })
            ->filter()->take(6)->values()->all();

        return [
            'constats' => array_slice($constats, 0, 3),
            'leviers' => array_slice($leviers, 0, 3),
            'point_fort' => $fort && $fort['reussite'] >= 60 ? "{$fort['competence']} : {$fort['reussite']} % de réussite dans le centre." : 'Pas encore de point fort net à l’échelle du centre.',
            'classes_qui_decrochent' => $decrochent,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $faible
     * @return array{competence: string, format: string, pourquoi: string}
     */
    private function prochainDevoir(?array $faible): array
    {
        if (! $faible) {
            return ['competence' => 'Toutes compétences', 'format' => 'Épreuve blanche courte', 'pourquoi' => 'Situer le niveau dans chaque compétence de l’examen.'];
        }

        $format = $faible['format_le_plus_faible']['format'] ?? null;

        return [
            'competence' => $faible['competence'],
            'format' => $format ?? self::FORMATS[$faible['cle']] ?? 'Exercice au format de l’examen',
            'pourquoi' => $format
                ? "C’est le format le moins réussi ({$faible['format_le_plus_faible']['reussite']} %) dans la compétence la plus faible."
                : "C’est la compétence la plus faible ({$faible['reussite']} %).",
        ];
    }

    private function signe(float $n): string
    {
        return ($n > 0 ? '+' : '').$n;
    }
}
