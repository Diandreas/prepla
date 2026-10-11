<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use App\Services\TTS\DeepgramTtsService;
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
        {--sec : annoncer ce qui serait fabrique, sans rien ecrire}';

    protected $description = 'Fabrique et range le son et les images des exercices, pour que rien ne se genere pendant une epreuve';

    private const DOSSIER_IMAGES = 'exercise-images';

    public function handle(DeepgramTtsService $tts): int
    {
        $sec = (bool) $this->option('sec');
        $limite = (int) $this->option('limite');
        $toutFaire = ! $this->option('son') && ! $this->option('image');

        $faits = 0;
        $echecs = [];
        $octets = 0;

        Exercise::with('exerciseType:id,skill_type,component_key', 'exam:id,language_id', 'exam.language:id,name')
            ->when($this->option('sujets'), fn ($q) => $q->whereNotNull('mock_exam_id'))
            ->chunkById(200, function ($lot) use ($tts, $sec, $limite, $toutFaire, &$faits, &$echecs, &$octets) {
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
                            [$question, $fait, $erreur] = $this->poserLeSon($tts, $exercice, $question, $sec);
                            if ($fait) {
                                $faits++;
                                $modifie = true;
                            }
                            if ($erreur) {
                                $echecs[] = "#{$exercice->id} son : {$erreur}";
                            }
                        }

                        if ($toutFaire || $this->option('image')) {
                            [$question, $fait, $erreur, $poids] = $this->poserLesImages($question, $sec);
                            if ($fait) {
                                $faits += $fait;
                                $octets += $poids;
                                $modifie = true;
                            }
                            if ($erreur) {
                                $echecs[] = "#{$exercice->id} image : {$erreur}";
                            }
                        }

                        $questions[$i] = $question;
                    }

                    if ($modifie && ! $sec) {
                        $exercice->questions = $questions;
                        $exercice->saveQuietly();
                    }
                }

                return true;
            });

        $this->newLine();
        $this->info(($sec ? 'A fabriquer : ' : 'Fabriques : ').$faits.' fichier(s)'
            .($octets ? ', '.round($octets / 1048576, 1).' Mo d images' : ''));

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
    private function poserLeSon(DeepgramTtsService $tts, Exercise $exercice, array $question, bool $sec): array
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

        $url = $tts->speak($texte, $langue);
        if (! $url) {
            return [$question, false, 'synthese refusee'];
        }

        $question['audio_url'] = $url;
        $this->line('  <fg=green>♪</> #'.$exercice->id.' '.$langue.' '.mb_strlen($texte).' car.');

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

            $adresses = $question[$champ.'_urls'] ?? [];
            if (count($adresses) === count($question[$champ])) {
                continue; // deja fabriquees
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

            if (! $sec && array_filter($adresses)) {
                $question[$champ.'_urls'] = $adresses;
            }
        }

        return [$question, $faits, $erreur, $octets];
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
            return [null, 0, null];
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
