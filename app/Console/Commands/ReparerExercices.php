<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use App\Models\UserExerciseAttempt;
use App\Services\AI\MistralService;
use Illuminate\Console\Command;

/**
 * Repare les exercices qu'aucun apprenant ne peut faire.
 *
 * L'audit en trouve de trois sortes, toutes heritees de generations anciennes,
 * d'avant les garde-fous du generateur :
 *
 *   1. des entrees parasites dans la liste des questions — des chaines la ou
 *      le format attend des objets. Un exercice annoncait vingt-sept questions
 *      et n'en avait qu'une de vraie ;
 *   2. des « construire la phrase » sans phrase correcte : les mots sont la,
 *      la solution manque, et rien ne peut etre corrige ;
 *   3. le reste, irreparable.
 *
 * On ne SUPPRIME que ce dont personne ne s'est servi : une tentative
 * d'apprenant disparait avec son exercice, et le travail de quelqu'un ne se
 * jette pas pour faire du ménage.
 */
class ReparerExercices extends Command
{
    protected $signature = 'prepla:reparer-exercices
        {--sec : annoncer ce qui serait fait, sans rien ecrire}
        {--garder : ne rien supprimer, meme sans tentative}';

    protected $description = 'Repare ou retire les exercices dont aucune question n est jouable';

    public function handle(MistralService $mistral): int
    {
        $sec = (bool) $this->option('sec');

        $nettoyes = 0;
        $recomposes = 0;
        $supprimes = 0;
        $restants = [];
        $aSonoriser = [];

        // skill_type est indispensable : sans lui, estUneEcoute() ne reconnait
            // aucune ecoute et le garde-fou qui les epargne ne sert a rien.
            Exercise::with('exerciseType:id,slug,component_key,skill_type')
            ->chunkById(300, function ($lot) use ($mistral, $sec, &$nettoyes, &$recomposes, &$supprimes, &$restants, &$aSonoriser) {
                foreach ($lot as $exercice) {
                    // 1. Les entrees qui ne sont meme pas des objets. On les
                    //    retire TOUJOURS, meme quand l'exercice reste jouable
                    //    par ailleurs : le cas reel annoncait vingt-sept
                    //    questions et n'en avait qu'une de vraie.
                    $questions = is_array($exercice->questions) ? $exercice->questions : [];
                    $propres = array_values(array_filter($questions, 'is_array'));

                    if (count($propres) !== count($questions) && $propres !== []) {
                        if (! $sec) {
                            $exercice->questions = $propres;
                            $exercice->saveQuietly();
                        }
                        $nettoyes++;
                        $this->line('  <fg=green>✓</> #'.$exercice->id.' '
                            .(count($questions) - count($propres)).' entree(s) parasite(s) retiree(s)');
                    }

                    // A sec, on juge sur les questions NETTOYEES sans rien ecrire :
                    // se contenter de « il en reste » annoncait moins de travail
                    // qu'il n'y en a reellement.
                    if (! $sec) {
                        $exercice = $exercice->fresh();
                    } else {
                        $exercice->questions = $propres;
                    }

                    if ($exercice->answerableQuestions() !== []) {
                        continue;
                    }

                    // 2. « Construire la phrase » sans solution.
                    if ($exercice->exerciseType?->component_key === 'build-a-sentence'
                        && $this->recomposer($mistral, $exercice, $sec)) {
                        $recomposes++;

                        continue;
                    }

                    // 3. Une ECOUTE sans passage parle n'est pas cassee : il lui
                    //    manque son enregistrement, et c'est prepla:sonoriser-ecoutes
                    //    qui l'ecrit. La supprimer jetterait un exercice complet
                    //    pour un fichier son absent.
                    if ($exercice->estUneEcoute()) {
                        $aSonoriser[] = '#'.$exercice->id.' '.($exercice->exerciseType?->slug ?? '?');

                        continue;
                    }

                    // 4. Irreparable : on ne retire que ce dont personne ne s'est servi.
                    $tentatives = UserExerciseAttempt::where('exercise_id', $exercice->id)->count();
                    if ($tentatives > 0 || $this->option('garder')) {
                        $restants[] = '#'.$exercice->id.' '.($exercice->exerciseType?->slug ?? '?')
                            .' ('.$tentatives.' tentative(s))';

                        continue;
                    }

                    if (! $sec) {
                        $exercice->delete();
                    }
                    $supprimes++;
                    $this->line('  <fg=yellow>×</> #'.$exercice->id.' '
                        .($exercice->exerciseType?->slug ?? '?').' retire, aucune tentative');
                }
            });

        $this->newLine();
        $this->info(($sec ? 'A faire : ' : '')
            .$nettoyes.' nettoye(s), '.$recomposes.' recompose(s), '.$supprimes.' retire(s)');

        if ($aSonoriser !== []) {
            $this->warn(count($aSonoriser).' ecoute(s) attendent leur passage parle — prepla:sonoriser-ecoutes :');
            foreach (array_slice($aSonoriser, 0, 10) as $e) {
                $this->line('  '.$e);
            }
        }

        if ($restants !== []) {
            $this->warn(count($restants).' exercice(s) restent injouables et sont gardes :');
            foreach ($restants as $r) {
                $this->line('  '.$r);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Remet la phrase solution d'un « construire la phrase ».
     *
     * La reponse n'est pas inventee : c'est l'ORDRE des mots que l'exercice
     * fournit deja. On verifie donc que la phrase rendue emploie exactement ces
     * mots-la, chacun une fois — sinon on refuse, et l'exercice sera retire.
     */
    private function recomposer(MistralService $mistral, Exercise $exercice, bool $sec): bool
    {
        $questions = is_array($exercice->questions) ? $exercice->questions : [];
        $modifie = false;

        foreach ($questions as $i => $question) {
            if (! is_array($question) || ! empty($question['correct_answer'])) {
                continue;
            }
            $mots = $question['words'] ?? null;
            if (! is_array($mots) || count($mots) < 2) {
                return false;
            }

            if ($sec) {
                $this->line('  <fg=cyan>?</> #'.$exercice->id.' phrase a recomposer : '.implode(' ', $mots));

                return true;
            }

            $reponse = $mistral->chat([
                ['role' => 'system', 'content' => 'You reorder words into one correct sentence. '
                    .'Reply with the sentence ONLY — no quotes, no explanation. '
                    .'Use every given word exactly once, and no other word.'],
                ['role' => 'user', 'content' => 'Words: '.implode(' / ', $mots)],
            ]);

            $reponse = $this->phraseSeule($reponse);

            if (! $reponse || ! $this->emploieExactementCesMots($reponse, $mots)) {
                $this->line('  <fg=red>✗</> #'.$exercice->id.' phrase refusee : '
                    .mb_substr(trim((string) $reponse), 0, 60));

                return false;
            }

            $questions[$i]['correct_answer'] = trim($reponse);
            $modifie = true;
            $this->line('  <fg=green>✓</> #'.$exercice->id.' phrase recomposee : '.trim($reponse));
        }

        if ($modifie) {
            $exercice->questions = $questions;
            $exercice->saveQuietly();
        }

        return $modifie;
    }

    /**
     * La phrase seule, debarrassee de l'emballage que l'IA ajoute parfois.
     *
     * La consigne demande la phrase et rien d'autre ; Mistral repond malgre
     * tout {"sentence": "..."}. Refuser pour l'emballage revenait a jeter une
     * phrase juste — le controle des mots, lui, reste le seul juge.
     */
    private function phraseSeule(?string $brut): ?string
    {
        $brut = trim((string) $brut);
        if ($brut === '') {
            return null;
        }

        if (str_starts_with($brut, '{') || str_starts_with($brut, '[')) {
            $decode = json_decode($brut, true);
            if (is_array($decode)) {
                $textes = array_filter($decode, fn ($v) => is_string($v) && trim($v) !== '');

                return $textes === [] ? null : trim((string) reset($textes));
            }
        }

        return trim($brut, " 	
 \"'");
    }

    /** La phrase emploie-t-elle exactement les mots fournis, chacun une fois ? */
    private function emploieExactementCesMots(string $phrase, array $mots): bool
    {
        $normalise = fn (string $t) => preg_split('/\s+/u',
            trim(preg_replace('/[^\p{L}\p{N}\s\']+/u', ' ', mb_strtolower($t))), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $rendus = $normalise($phrase);
        $attendus = $normalise(implode(' ', array_map('strval', $mots)));
        sort($rendus);
        sort($attendus);

        return $rendus === $attendus;
    }
}
