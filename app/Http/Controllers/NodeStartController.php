<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\LearningPathNode;
use App\Models\UserError;
use App\Models\UserLearningProgress;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\AI\TtsAudioGenerator;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NodeStartController extends Controller
{
    /**
     * Lance une session d'apprentissage pour un nœud spécifique (Le "Set de 3").
     * Cette version implémente la vision "Duolingo" : session rapide de 3 exercices.
     */
    public function __invoke(LearningPathNode $node, ExerciseGeneratorService $generator, TtsAudioGenerator $ttsAudio): Response|RedirectResponse
    {
        $user = auth()->user();
        
        // 1. Vérifier/Récupérer la progression pour ce nœud
        $progress = UserLearningProgress::firstOrCreate(
            ['user_id' => $user->id, 'node_id' => $node->id],
            [
                'status' => 'available',
                'exercises_required' => 3,
                'exercises_done' => 0
            ]
        );

        // 2. Récupérer 3 exercices (Priorité aux statiques liés au nœud)
        $exercises = Exercise::where('node_id', $node->id)
            ->with(['exerciseType', 'exam.language'])
            ->orderBy('order_in_node')
            ->limit(3)
            ->get();

        // 3. Fallback générique : SAUTÉ pour les nodes de type 'lesson' car les exercices
        // génériques pris au hasard sont rarement alignés avec le concept de la leçon
        // (ex: leçon "Simple Present" + exercice générique sur Madrid = aucun rapport).
        // Pour les nodes de pratique générale (non-lesson) le fallback reste utile.
        $isLessonNode = $node->node_type === 'lesson';
        if ($exercises->count() < 3 && !$isLessonNode) {
            $needed = 3 - $exercises->count();
            $generic = Exercise::where('exam_id', $node->exam_id)
                ->where('difficulty', $node->level)
                ->whereNotIn('id', $exercises->pluck('id'))
                ->with(['exerciseType', 'exam.language'])
                ->inRandomOrder()
                ->limit($needed)
                ->get();

            $exercises = $exercises->concat($generic);
        }

        // 4. Génération IA si toujours pas assez d'exercices
        // On ne génère QUE si le nœud n'a jamais eu d'exercices générés (évite de reconsommer des tokens)
        $alreadyGenerated = Exercise::where('node_id', $node->id)->where('is_ai_generated', true)->exists();

        if ($exercises->count() < 3 && !$alreadyGenerated) {
            $node->loadMissing('exam.language');

            // Variety: a quick session of 3 exercises should mix SKILLS, not just
            // reading/grammar. We deliberately compose: 1 listening + 1 reading/grammar
            // + 1 of either (with an occasional speaking turn) so a learner actually
            // meets listening and speaking exercises across their journey.
            // Components kept short enough for a quick session (no essays here).
            // Types "courts" piochés pour une session rapide de nœud. Inclut les
            // nouveaux types (picture-mcq, listen-repeat, complete-the-words,
            // build-a-sentence, oraux interactifs) → ils profitent AUSSI aux parcours
            // déjà commencés, car chaque session génère à la demande.
            $shortComponents = ['mcq', 'true-false-ng', 'gap-fill', 'matching', 'sentence-completion', 'short-answer', 'ordering', 'word-formation', 'multiple-matching', 'open-cloze', 'note-completion', 'dictation', 'speaking-recorder', 'picture-mcq', 'listen-repeat', 'complete-the-words', 'build-a-sentence', 'oral-debate', 'negotiation', 'speaking-elicitation'];

            // Components qui marchent vraiment en ÉCOUTE : on répond À l'oreille sur
            // un audio (texte caché). On EXCLUT matching (associer des termes écrits =
            // ce n'est pas de l'écoute) et les types texte-only.
            $listeningComponents = ['mcq', 'true-false-ng', 'note-completion', 'dictation', 'short-answer', 'picture-mcq', 'listen-choose-response', 'listen-repeat'];

            // skill_type is a direct column on exercise_types (not via section), and
            // the types aren't scoped by exam_id — so we filter on the column directly.
            $pickBySkill = function (array $skills, array $excludeIds = []) use ($shortComponents, $listeningComponents) {
                // En listening pur, restreindre aux composants adaptés à l'écoute.
                $pool = ($skills === ['listening']) ? $listeningComponents : $shortComponents;
                return ExerciseType::whereIn('component_key', $pool)
                    ->whereIn('skill_type', $skills)
                    ->whereNotIn('id', $excludeIds)
                    ->inRandomOrder()
                    ->first();
            };

            // The exercise-type pool is hugely skewed toward reading (≈65 reading vs
            // ≈4 grammar), so a naive inRandomOrder() over ['reading','grammar'] almost
            // never picks grammar → every session became reading comprehension, never
            // the concept being taught (the "trop de compréhension de texte" complaint).
            // For a LESSON node we therefore prioritise grammar/applied practice that
            // tests the concept, and use reading only as a fallback. General-practice
            // nodes keep the broader reading-led mix.
            $picked = collect();

            // Speaking turns (role-play, oral-debate...) are entirely IN THE TARGET
            // LANGUAGE — the examiner's lines, the scenario, everything. A beginner
            // (A0/A1/A2) can't parse that yet, so speaking must be rare for them.
            // From B1 up, the learner can actually follow it, so keep the usual mix.
            $isBeginner = in_array($node->level, ['A0', 'A1', 'A2'], true);
            $speakingOdds = $isBeginner ? 8 : 3; // beginner: ~1/8 · intermediate+: ~1/3

            if ($isLessonNode) {
                // 1) a concept exercise (grammar) — the heart of a lesson practice.
                $concept = $pickBySkill(['grammar'])
                    ?? $pickBySkill(['reading']); // fallback if no grammar type exists
                if ($concept) {
                    $picked->push($concept);
                }
                // 2) a second concept exercise, different component if possible.
                if ($concept2 = $pickBySkill(['grammar', 'reading'], $picked->pluck('id')->all())) {
                    $picked->push($concept2);
                }
                // 3) round it out with a different skill so the session isn't monotone:
                //    a listening turn, or occasionally a speaking turn (rarer for beginners).
                $thirdSkills = random_int(1, $speakingOdds) === 1 ? ['speaking'] : ['listening'];
                if ($third = $pickBySkill($thirdSkills, $picked->pluck('id')->all())) {
                    $picked->push($third);
                }
            } else {
                // General practice: mix skills broadly.
                // 1) one listening exercise
                if ($listening = $pickBySkill(['listening'])) {
                    $picked->push($listening);
                }
                // 2) one reading/grammar exercise
                if ($reading = $pickBySkill(['reading', 'grammar'], $picked->pluck('id')->all())) {
                    $picked->push($reading);
                }
                // 3) round it out: occasionally a speaking turn (rarer for beginners), else any skill
                $thirdSkills = random_int(1, $speakingOdds) === 1
                    ? ['speaking']
                    : ['reading', 'grammar', 'listening'];
                if ($third = $pickBySkill($thirdSkills, $picked->pluck('id')->all())) {
                    $picked->push($third);
                }
            }

            $variedTypes = $picked->shuffle()->values();

            // Fallback: never leave the session empty.
            if ($variedTypes->isEmpty()) {
                $variedTypes = ExerciseType::whereIn('component_key', $shortComponents)->inRandomOrder()->limit(3)->get();
            }

            // Lesson context: ground the generated exercises in what's being taught so
            // they actually test the concept instead of producing random reading
            // comprehension. Prefer an associated Lesson; otherwise fall back to the
            // node's own title — it IS the learning objective in this curriculum
            // (e.g. "Einfache Vorstellungsgespräche führen"). Without any context the
            // generator drifts to generic passages unrelated to the node.
            $lesson = \App\Models\Lesson::where('node_id', $node->id)->first();
            if ($lesson) {
                $lessonContext = [
                    'title' => $lesson->title,
                    'concept' => $lesson->concept ?: $node->title,
                    'native_language' => $user->profile?->native_language ?? 'Français',
                ];
            } else {
                $lessonContext = [
                    'title' => $node->title,
                    'concept' => $node->title,
                    'native_language' => $user->profile?->native_language ?? 'Français',
                ];
            }

            // Spaced repetition (SM2): before generating a fresh random set, check
            // whether the learner has a concept mistake DUE for review. Previously
            // NodeStartController never consulted UserError at all — the SM2 queue
            // only surfaced via the separate, opt-in "Review Center" page — so a
            // recurring mistake never resurfaced in the everyday "Set of 3" flow.
            // Re-using the existing lessonContext channel (not a new mechanism) to
            // steer ONE of the generated exercises onto that concept.
            $dueError = UserError::dueForReview($user->id)
                ->whereNotIn('skill_type', ['reading', 'listening'])
                ->where(function ($q) {
                    $q->whereIn('exercise_type_slug', UserError::CONCEPT_SLUGS)
                      ->orWhereIn('skill_type', ['grammar', 'vocabulary', 'use-of-english', 'writing']);
                })
                ->first();
            $reviewLessonContext = $dueError ? array_merge($lessonContext, [
                'title' => $lessonContext['title'],
                'concept' => $dueError->error_category ?: $lessonContext['concept'],
            ]) : null;

            if ($variedTypes->isNotEmpty()) {
                $needed = 3 - $exercises->count();
                $generated = collect();
                for ($i = 0; $i < $needed; $i++) {
                    $exerciseType = $variedTypes[$i % $variedTypes->count()];
                    // Steer the FIRST generated exercise of the set onto the due
                    // review concept, if any — the rest keep testing the node's
                    // own concept as before.
                    $contextForThis = ($i === 0 && $reviewLessonContext) ? $reviewLessonContext : $lessonContext;
                    try {
                        $ex = $generator->generate($exerciseType, $node->exam, $node->level, $contextForThis);
                        $ex->update(['node_id' => $node->id, 'order_in_node' => $i + 1]);
                        $ex->load(['exerciseType', 'exam.language']);
                        $generated->push($ex);
                    } catch (\Throwable $e) {
                        // One type failed → try the next instead of aborting the whole set.
                        \Illuminate\Support\Facades\Log::error('NodeStart: exercise generation failed', ['error' => $e->getMessage()]);
                        continue;
                    }
                }
                $exercises = $exercises->concat($generated);
            }
        } elseif ($exercises->count() < 3 && $alreadyGenerated) {
            // Récupérer les exercices déjà générés pour ce nœud (node_id attaché)
            $existing = Exercise::where('node_id', $node->id)
                ->with(['exerciseType', 'exam.language'])
                ->orderBy('order_in_node')
                ->get();
            $exercises = $existing;
        }

        // 4-bis. Pré-générer l'audio TTS des exercices d'ÉCOUTE avant d'afficher le
        // player, pour que le son soit prêt instantanément (plus de latence au clic
        // sur "Écouter"). Idempotent : ignore les questions qui ont déjà un audio_url.
        $this->pregenerateListeningAudio($exercises, $ttsAudio);

        // 4-ter. Garde-fou : si la génération a totalement échoué (aucun exercice
        // réel), ne PAS afficher le player avec du contenu bidon → retour propre.
        if ($exercises->isEmpty()) {
            return redirect()->route('dashboard')
                ->with('error', "La génération des exercices a échoué. Réessaie dans un instant.");
        }

        // 5. Mettre à jour le statut du nœud
        if ($progress->status === 'available') {
            $progress->update(['status' => 'in_progress']);
        }

        // 6. Rendre la vue du "Player" (Moteur d'exercices)
        return Inertia::render('exercises/player', [
            'node' => $node->load('exam.language'),
            'exercises' => $exercises,
            'progress' => $progress,
        ]);
    }

    /**
     * Pre-generate Deepgram TTS for listening exercises and store audio_url on each
     * question, so the player plays it instantly instead of calling TTS at click time.
     */
    private function pregenerateListeningAudio($exercises, TtsAudioGenerator $ttsAudio): void
    {
        foreach ($exercises as $exercise) {
            if (($exercise->exerciseType->skill_type ?? null) !== 'listening') {
                continue;
            }
            $questions = $exercise->questions ?? [];
            $language = strtolower($exercise->exam?->language?->slug ?? 'english');
            $modified = false;

            // Content-level audio (audio_text/passage on the exercise itself) — the
            // player reads content.audio_url first; without this only per-question
            // audio was pre-built and the passage still hit live TTS at click time.
            $content = $exercise->content ?? [];
            if (is_array($content) && empty($content['audio_url'])) {
                $contentText = $content['audio_text'] ?? $content['passage'] ?? null;
                if (is_string($contentText) && trim($contentText) !== '') {
                    try {
                        $url = $ttsAudio->generate(trim($contentText), $ttsAudio->defaultVoiceFor($language));
                        if ($url) {
                            $content['audio_url'] = $url;
                            $exercise->content = $content;
                            $modified = true;
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Listening pre-gen TTS failed (content)', ['error' => $e->getMessage()]);
                    }
                }
            }

            foreach ($questions as $idx => $question) {
                if (!empty($question['audio_url'])) {
                    continue; // already generated
                }
                try {
                    $url = $ttsAudio->generateForQuestion($question, $language);
                    if ($url) {
                        $questions[$idx]['audio_url'] = $url;
                        $modified = true;
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Listening pre-gen TTS failed', ['error' => $e->getMessage()]);
                }
            }
            if ($modified) {
                $exercise->questions = $questions;
                $exercise->save();
            }
        }
    }
}
