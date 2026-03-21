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

        // 3. Fallback : Piocher des exercices génériques par difficulté si pas assez de statiques
        if ($exercises->count() < 3) {
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
        if ($exercises->count() < 3) {
            $node->loadMissing('exam.language');
            $exerciseType = ExerciseType::where('exam_id', $node->exam_id)->inRandomOrder()->first()
                ?? ExerciseType::inRandomOrder()->first();

            if ($exerciseType) {
                $needed = 3 - $exercises->count();
                $generated = collect();
                for ($i = 0; $i < $needed; $i++) {
                    try {
                        $ex = $generator->generate($exerciseType, $node->exam, $node->level);
                        $ex->load(['exerciseType', 'exam.language']);
                        $generated->push($ex);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('NodeStart: exercise generation failed', ['error' => $e->getMessage()]);
                        break;
                    }
                }
                $exercises = $exercises->concat($generated);
            }
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
