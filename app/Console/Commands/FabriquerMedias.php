<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use App\Services\Media\ImagesCommons;
use App\Services\TTS\DeepgramTtsService;
use App\Services\TTS\ElevenLabsTtsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Fabrique A L'AVANCE le son et les images des exercices, et les RANGE.
 *
 * Jusqu'ici rien n'etait prepare : la voix etait synthetisee au premier clic de
 * l'apprenant, et chaque illustration etait demandee a un service tiers au
 * moment de l'affichage. Trois consequences, toutes payees par l'apprenant :
 * il attendait, un service indisponible lui donnait une epreuve d'ecoute muette
 * ou une consigne « decrivez l'image » sans image, et la meme depense etait
 * refaite a chaque nouveau cache navigateur.
 *
 * Le fichier fabrique ici est ecrit sur le disque ET inscrit dans la question
 * (audio_url, image_url) : plus aucun appel a un tiers pendant une epreuve.
 */
class FabriquerMedias extends Command
{
    protected $signature = 'prepla:fabriquer-medias
        {--son : ne fabriquer que le son}
        {--image : ne fabriquer que les images}
        {--sujets : se limiter aux exercices des epreuves blanches}
        {--limite=0 : s arreter apres ce nombre de fichiers}
        {--dialogues : donner une voix par locuteur aux passages a deux voix (ElevenLabs)}
        {--sec : annoncer ce qui serait fabrique, sans rien ecrire}';

    protected $description = 'Fabrique et range le son et les images des exercices, pour que rien ne se genere pendant une epreuve';

    private const DOSSIER_IMAGES = 'exercise-images';

    public function handle(DeepgramTtsService $tts, ElevenLabsTtsService $deuxVoix): int
    {
        $sec = (bool) $this->option('sec');
        $limite = (int) $this->option('limite');
        $toutFaire = ! $this->option('son') && ! $this->option('image');

        $faits = 0;       // fichiers reellement fabriques
        $equipees = 0;    // questions qui ont recu leur adresse
        $echecs = [];
        $octets = 0;

        Exercise::with('exerciseType:id,skill_type,component_key', 'exam:id,language_id', 'exam.language:id,name')
            ->when($this->option('sujets'), fn ($q) => $q->whereNotNull('mock_exam_id'))
            ->chunkById(200, function ($lot) use ($tts, $deuxVoix, $sec, $limite, $toutFaire, &$faits, &$equipees, &$echecs, &$octets) {
                foreach ($lot as $exercice) {
                    $questions = is_array($exercice->questions) ? $exercice->questions : [];
                    $modifie = false;

                    foreach ($questions as $i => $question) {
                        if (! is_array($question)) {
                            continue;
                        }

                        if ($limite > 0 && $faits >= $limite) {
                            return false;
                        }

                        if (($toutFaire || $this->option('son')) && $exercice->estUneEcoute()) {
                            $avant = $question['audio_url'] ?? null;
                            [$question, $fait, $erreur] = $this->poserLeSon($tts, $deuxVoix, $exercice, $question, $sec);
                            $faits += $fait ? 1 : 0;
                            $modifie = $modifie || ($question['audio_url'] ?? null) !== $avant;
                            if ($erreur) {
                                $echecs[] = "#{$exercice->id} son : {$erreur}";
                            }
                        }

                        if ($toutFaire || $this->option('image')) {
                            $avant = json_encode($question);
                            [$question, $fait, $erreur, $poids] = $this->poserLesImages($question, $sec);
                            $faits += $fait;
                            $octets += $poids;
                            $modifie = $modifie || json_encode($question) !== $avant;
                            if ($erreur) {
                                $echecs[] = "#{$exercice->id} image : {$erreur}";
                            }
                        }

                        $questions[$i] = $question;
                    }

                    if ($modifie) {
                        $equipees++;
                        if (! $sec) {
                            $exercice->questions = $questions;
                            $exercice->saveQuietly();
                        }
                    }
                }

                return true;
            });

        $this->newLine();
        $this->info(($sec ? 'A fabriquer : ' : 'Fabriques : ').$faits.' fichier(s)'
            .($octets ? ', '.round($octets / 1048576, 1).' Mo d images' : '')
            .' — '.$equipees.' exercice(s) equipe(s)');

        if ($echecs !== []) {
            $this->warn(count($echecs).' echec(s) :');
            foreach (array_slice($echecs, 0, 12) as $e) {
                $this->line('  '.$e);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Synthetise le passage parle et inscrit son adresse dans la question.
     *
     * @return array{0: array, 1: bool, 2: ?string}
     */
    private function poserLeSon(DeepgramTtsService $tts, ElevenLabsTtsService $deuxVoix, Exercise $exercice, array $question, bool $sec): array
    {
        if (! empty($question['audio_url'])) {
            return [$question, false, null];
        }

        $texte = $exercice->texteParle($question);
        if ($texte === '') {
            return [$question, false, null];
        }

        $langue = match ($exercice->exam?->language?->name) {
            'Allemand' => 'de',
            'Français' => 'fr',
            default => 'en',
        };

        // La cle de cache du service est l'empreinte du texte : un passage deja
        // fabrique pour un exercice du vivier sert aussi a sa copie dans un sujet.
        $dejaLa = Storage::disk('public')->exists('tts/'.md5($texte.$langue.'').'.mp3');

        if ($sec) {
            return [$question, ! $dejaLa, null];
        }

        // Un DIALOGUE merite une voix par locuteur : c'est la seule difference
        // qui s'entend vraiment, et c'est une competence evaluee a l'examen —
        // savoir qui dit quoi. On n'y recourt que la, le quota se comptant en
        // caracteres. Sans cle, le service se declare indisponible et Deepgram
        // lit tout, comme avant.
        $url = null;
        if ($this->option('dialogues') && $deuxVoix->disponible() && $deuxVoix->estUnDialogue($texte)) {
            $url = $deuxVoix->parler($texte, $langue);
            if ($url) {
                $this->line('  <fg=cyan>♫</> #'.$exercice->id.' '.$langue.' dialogue a '
                    .count(array_unique(array_column($deuxVoix->repliques($texte), 'locuteur'))).' voix');
            }
        }

        $url = $url ?? $tts->speak($texte, $langue);
        if (! $url) {
            return [$question, false, 'synthese refusee'];
        }

        $question['audio_url'] = $url;
        if (! str_contains($url, '/el-')) {
            $this->line('  <fg=green>♪</> #'.$exercice->id.' '.$langue.' '.mb_strlen($texte).' car.');
        }

        return [$question, ! $dejaLa, null];
    }

    /**
     * Fabrique les illustrations decrites par la question, les range sur le
     * disque, et remplace la description par l'adresse du fichier.
     *
     * @return array{0: array, 1: int, 2: ?string, 3: int}
     */
    private function poserLesImages(array $question, bool $sec): array
    {
        $faits = 0;
        $octets = 0;
        $erreur = null;

        // Des photographies reelles, prises dans Wikimedia Commons, quand la
        // question dit CE QU'ELLE CHERCHE (« apples fruit ») plutot que ce
        // qu'elle veut faire dessiner. C'est la bonne source pour une epreuve :
        // le Goethe A1 montre des photos du quotidien, et Commons n'a ni
        // filigrane, ni quota, ni vignette d'erreur a la place de l'image.
        if (! empty($question['image_queries']) && is_array($question['image_queries'])) {
            // « Deja la » veut dire : une adresse par terme, aucune manquante,
            // ET le fichier toujours sur le disque. La question gardait les
            // adresses meme apres un menage dans le dossier : les images ne
            // revenaient plus jamais, et l'exercice montrait des cartes vides.
            $dejaLa = is_array($question['image_options_urls'] ?? null)
                && count($question['image_options_urls']) === count($question['image_queries'])
                && ! in_array(null, $question['image_options_urls'], true)
                && collect($question['image_options_urls'])->every(
                    fn ($url) => is_string($url)
                        && Storage::disk('public')->exists(ltrim(str_replace('/storage/', '', $url), '/')),
                );

            if (! $dejaLa) {
                $adresses = [];
                $credits = [];
                foreach ($question['image_queries'] as $terme) {
                    [$url, $credit, $poids, $e] = $this->prendreUnePhoto((string) $terme, $sec);
                    $adresses[] = $url;
                    $credits[] = $credit;
                    $erreur = $erreur ?? $e;
                    if ($url) {
                        $faits++;
                        $octets += $poids;
                    }
                }
                if (array_filter($adresses)) {
                    $question['image_options_urls'] = $adresses;
                    $question['image_credits'] = $credits;
                }
            }
        }

        // Une seule image : « image_prompt » devient « image_url ».
        if (empty($question['image_url']) && ! empty($question['image_prompt']) && is_string($question['image_prompt'])) {
            [$url, $poids, $erreur] = $this->fabriquerUneImage($question['image_prompt'], $sec);
            if ($url) {
                $question['image_url'] = $url;
                $faits++;
                $octets += $poids;
            }
        }

        // Plusieurs images : un QCM image en demande une par option.
        foreach (['image_prompts', 'image_options'] as $champ) {
            if (empty($question[$champ]) || ! is_array($question[$champ])) {
                continue;
            }

            // « Deja fabriquees » veut dire TOUTES. Le test comparait les
            // tailles : une fabrication a moitie ratee laissait des trous dans
            // la liste, la taille collait quand meme, et plus aucune relance
            // n'allait rechercher les images manquantes.
            $adresses = $question[$champ.'_urls'] ?? [];
            if (count($adresses) === count($question[$champ]) && ! in_array(null, $adresses, true)) {
                continue;
            }

            $adresses = [];
            foreach ($question[$champ] as $description) {
                if (! is_string($description) || trim($description) === '') {
                    $adresses[] = null;

                    continue;
                }
                [$url, $poids, $e] = $this->fabriquerUneImage($description, $sec);
                $adresses[] = $url;
                $erreur = $erreur ?? $e;
                if ($url) {
                    $faits++;
                    $octets += $poids;
                }
            }

            // On inscrit les adresses dans la question meme a blanc : rien n'est
            // enregistre tant qu'on est a blanc (la sauvegarde est gardee plus
            // haut), et c'est ce qui permet d'annoncer combien d'exercices
            // seraient equipes.
            if (array_filter($adresses)) {
                $question[$champ.'_urls'] = $adresses;
            }
        }

        return [$question, $faits, $erreur, $octets];
    }

    /**
     * Prend une photographie dans Wikimedia Commons et la range chez nous.
     *
     * On telecharge : plus aucun appel a un tiers pendant une epreuve, et
     * l'image ne disparait pas le jour ou le fichier est renomme la-bas.
     *
     * @return array{0: ?string, 1: string, 2: int, 3: ?string}
     */
    private function prendreUnePhoto(string $terme, bool $sec): array
    {
        $nom = self::DOSSIER_IMAGES.'/commons-'.md5(mb_strtolower(trim($terme))).'.jpg';

        if (Storage::disk('public')->exists($nom)) {
            // Le credit est relu sur le disque, pas seulement garde en memoire :
            // une image deja rangee gardait son fichier mais perdait sa
            // mention d'auteur au passage suivant, et une photo sous CC BY-SA
            // sans credit est une photo utilisee hors de sa licence.
            return [Storage::url($nom), $this->creditsRanges()[$nom] ?? '', 0, null];
        }

        if ($sec) {
            return [Storage::url($nom), '', 0, null];
        }

        $trouvee = app(ImagesCommons::class)->chercher($terme);
        if (! $trouvee) {
            return [null, '', 0, "aucune image pour « {$terme} »"];
        }

        try {
            $reponse = Http::withHeaders(['User-Agent' => 'PrePla/1.0 (https://prepla.mirlab.cloud)'])
                ->timeout(60)->get($trouvee['url']);

            if (! $reponse->successful() || strlen($reponse->body()) < 2000) {
                return [null, '', 0, 'image refusee ('.$reponse->status().')'];
            }

            Storage::disk('public')->put($nom, $reponse->body());
            $credit = app(ImagesCommons::class)->credit($trouvee);
            $this->rangerLeCredit($nom, $credit);
            $poids = strlen($reponse->body());
            $this->line('  <fg=green>▣</> '.mb_substr($terme, 0, 34).' — '
                .($trouvee['licence'] ?: 'licence inconnue').' — '.round($poids / 1024).' Ko');

            return [Storage::url($nom), $credit, $poids, null];
        } catch (\Throwable $e) {
            Log::warning('Photo Commons non rangee', ['terme' => $terme, 'erreur' => $e->getMessage()]);

            return [null, '', 0, 'reseau'];
        }
    }

    /** Le registre des credits, range a cote des images. */
    private const REGISTRE_CREDITS = self::DOSSIER_IMAGES.'/credits.json';

    /** @var array<string, string>|null */
    private ?array $creditsConnus = null;

    /** @return array<string, string> */
    private function creditsRanges(): array
    {
        if ($this->creditsConnus === null) {
            $brut = Storage::disk('public')->exists(self::REGISTRE_CREDITS)
                ? json_decode(Storage::disk('public')->get(self::REGISTRE_CREDITS), true)
                : [];
            $this->creditsConnus = is_array($brut) ? $brut : [];
        }

        return $this->creditsConnus;
    }

    private function rangerLeCredit(string $fichier, string $credit): void
    {
        $credits = $this->creditsRanges();
        $credits[$fichier] = $credit;
        $this->creditsConnus = $credits;
        Storage::disk('public')->put(
            self::REGISTRE_CREDITS,
            json_encode($credits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }

    /**
     * @return array{0: ?string, 1: int, 2: ?string}
     */
    private function fabriquerUneImage(string $description, bool $sec): array
    {
        $propre = trim(preg_replace('/\s+/', ' ', $description));
        $nom = self::DOSSIER_IMAGES.'/'.md5($propre).'.jpg';

        if (Storage::disk('public')->exists($nom)) {
            return [Storage::url($nom), 0, null];
        }

        if ($sec) {
            // A blanc on ne telecharge rien, mais il faut ANNONCER le travail :
            // l'adresse rendue est celle que le fichier aura, et l'appelant ne
            // l'ecrit pas en base tant qu'on est a blanc. Sans cela, le mode
            // d'essai disait « 0 fichier a fabriquer » alors que des dizaines
            // d'images manquaient — or le guide de deploiement demande
            // justement de le lire avant de lancer pour de vrai.
            return [Storage::url($nom), 0, null];
        }

        // Meme recette que le navigateur jusqu'ici, mais le resultat est garde.
        $adresse = 'https://image.pollinations.ai/prompt/'
            .rawurlencode($propre.', simple clear illustration, white background, no text')
            .'?width=768&height=512&nologo=true';

        try {
            $reponse = Http::timeout(90)->get($adresse);
            if (! $reponse->successful() || strlen($reponse->body()) < 2000) {
                return [null, 0, 'image refusee ('.$reponse->status().')'];
            }

            Storage::disk('public')->put($nom, $reponse->body());
            $poids = strlen($reponse->body());
            $this->line('  <fg=green>▣</> '.mb_substr($propre, 0, 52).'… '.round($poids / 1024).' Ko');

            return [Storage::url($nom), $poids, null];
        } catch (\Throwable $e) {
            Log::warning('Illustration non fabriquee', ['description' => $propre, 'erreur' => $e->getMessage()]);

            return [null, 0, 'reseau'];
        }
    }
}
