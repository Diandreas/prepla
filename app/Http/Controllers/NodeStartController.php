<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\LearningPathNode;
use App\Models\UserLearningProgress;
use Inertia\Inertia;
use Inertia\Response;

class NodeStartController extends Controller
{
    /**
     * Lance une session d'apprentissage pour un nœud spécifique (Le "Set de 3").
     * Cette version implémente la vision "Duolingo" : session rapide de 3 exercices.
     */
    public function __invoke(LearningPathNode $node): Response
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

        // 4. Mettre à jour le statut du nœud
        if ($progress->status === 'available') {
            $progress->update(['status' => 'in_progress']);
        }

        // 5. Rendre la vue du "Player" (Moteur d'exercices)
        return Inertia::render('exercises/player', [
            'node' => $node->load('exam.language'),
            'exercises' => $exercises,
            'progress' => $progress,
        ]);
    }
}
