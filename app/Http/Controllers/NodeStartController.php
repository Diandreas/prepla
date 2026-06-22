<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\LearningPathNode;
use App\Models\UserLearningProgress;
use App\Services\AI\ExerciseGeneratorService;
use Inertia\Inertia;
use Inertia\Response;

class NodeStartController extends Controller
{
    /**
     * Lance une session d'apprentissage pour un nœud spécifique (Le "Set de 3").
     * Cette version implémente la vision "Duolingo" : session rapide de 3 exercices.
     */
    public function __invoke(LearningPathNode $node, ExerciseGeneratorService $generator): Response
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
            $shortComponents = ['mcq', 'true-false-ng', 'gap-fill', 'matching', 'sentence-completion', 'short-answer', 'ordering', 'word-formation', 'multiple-matching', 'open-cloze', 'note-completion', 'dictation', 'speaking-recorder'];

            $pickBySkill = function (array $skills, array $excludeIds = []) use ($node, $shortComponents) {
                return ExerciseType::where('exam_id', $node->exam_id)
                    ->whereIn('component_key', $shortComponents)
                    ->whereNotIn('id', $excludeIds)
                    ->whereHas('section', fn ($q) => $q->whereIn('skill_type', $skills))
                    ->inRandomOrder()
                    ->first();
            };

            $picked = collect();
            // 1) one listening exercise
            if ($listening = $pickBySkill(['listening'])) {
                $picked->push($listening);
            }
            // 2) one reading/grammar exercise
            if ($reading = $pickBySkill(['reading', 'grammar', 'use-of-english'], $picked->pluck('id')->all())) {
                $picked->push($reading);
            }
            // 3) round it out: ~1 in 3 sessions ends with a speaking turn, else any skill
            $thirdSkills = random_int(1, 3) === 1
                ? ['speaking']
                : ['reading', 'grammar', 'listening', 'use-of-english'];
            if ($third = $pickBySkill($thirdSkills, $picked->pluck('id')->all())) {
                $picked->push($third);
            }

            $variedTypes = $picked->shuffle()->values();

            // Fallback: if the exam has no sections wired to skill_type, just grab any
            // short types so the session is never empty.
            if ($variedTypes->isEmpty()) {
                $variedTypes = ExerciseType::whereIn('component_key', $shortComponents)->inRandomOrder()->limit(3)->get();
            }

            // Lesson context: when the node has an associated Lesson, pass its concept
            // to the generator so exercises actually test what was just taught.
            $lessonContext = null;
            $lesson = \App\Models\Lesson::where('node_id', $node->id)->first();
            if ($lesson) {
                $lessonContext = [
                    'title' => $lesson->title,
                    'concept' => $lesson->concept ?? '',
                    'native_language' => $user->profile?->native_language ?? 'Français',
                ];
            }

            if ($variedTypes->isNotEmpty()) {
                $needed = 3 - $exercises->count();
                $generated = collect();
                for ($i = 0; $i < $needed; $i++) {
                    $exerciseType = $variedTypes[$i % $variedTypes->count()];
                    try {
                        $ex = $generator->generate($exerciseType, $node->exam, $node->level, $lessonContext);
                        $ex->update(['node_id' => $node->id, 'order_in_node' => $i + 1]);
                        $ex->load(['exerciseType', 'exam.language']);
                        $generated->push($ex);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('NodeStart: exercise generation failed', ['error' => $e->getMessage()]);
                        break;
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
}
