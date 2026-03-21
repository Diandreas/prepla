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
        // On ne génère QUE si le nœud n'a jamais eu d'exercices générés (évite de reconsommer des tokens)
        $alreadyGenerated = Exercise::where('node_id', $node->id)->where('is_ai_generated', true)->exists();

        if ($exercises->count() < 3 && !$alreadyGenerated) {
            $node->loadMissing('exam.language');
            $exerciseType = ExerciseType::where('exam_id', $node->exam_id)->inRandomOrder()->first()
                ?? ExerciseType::inRandomOrder()->first();

            if ($exerciseType) {
                $needed = 3 - $exercises->count();
                $generated = collect();
                for ($i = 0; $i < $needed; $i++) {
                    try {
                        $ex = $generator->generate($exerciseType, $node->exam, $node->level);
                        // Attacher le node_id pour ne plus régénérer la prochaine fois
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
