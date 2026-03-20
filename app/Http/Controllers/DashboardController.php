<?php

namespace App\Http\Controllers;

use App\Models\LearningPathNode;
use App\Models\UserLearningProgress;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();
        $profile = $user->profile?->load('targetExam.language');
        $examId = $profile?->target_exam_id;

        $chapters = [];
        $totalNodes = 0;
        $completedNodes = 0;

        if ($examId) {
            // 1. Récupérer la progression de l'utilisateur pour cet examen
            // On filtre par les nœuds qui existent dans la roadmap de l'utilisateur
            $userProgress = UserLearningProgress::where('user_id', $user->id)
                ->with(['node' => function($q) use ($examId) {
                    $q->where('exam_id', $examId);
                }])
                ->get()
                ->filter(fn($p) => $p->node !== null) // Sécurité si un nœud a été supprimé
                ->sortBy(function($p) {
                    return $p->node->chapter_order * 1000 + $p->node->sort_order;
                });

            $totalNodes = $userProgress->count();
            $completedNodes = $userProgress->where('status', 'completed')->count();

            // 2. Grouper par chapitre
            $chapters = $userProgress->groupBy(function($p) {
                return $p->node->chapter_name ?? 'Introduction';
            })->map(function($items, $name) {
                return [
                    'name' => $name,
                    'order' => $items->first()->node->chapter_order ?? 1,
                    'nodes' => $items->map(function($p) {
                        return [
                            'id' => $p->node->id,
                            'title' => $p->node->title,
                            'description' => $p->node->description,
                            'icon' => $p->node->icon,
                            'skill_type' => $p->node->skill_type,
                            'level' => $p->node->level,
                            'status' => $p->status,
                            'scheduled_for' => $p->scheduled_for,
                            'xp_reward' => $p->node->xp_reward,
                        ];
                    })->values()
                ];
            })->values()->sortBy('order')->values()->toArray();
        }

        return Inertia::render('dashboard', [
            'profile' => $profile,
            'chapters' => $chapters,
            'stats' => [
                'total_nodes' => $totalNodes,
                'completed_nodes' => $completedNodes,
                'progress_percent' => $totalNodes > 0 ? round(($completedNodes / $totalNodes) * 100) : 0,
            ]
        ]);
    }
}
