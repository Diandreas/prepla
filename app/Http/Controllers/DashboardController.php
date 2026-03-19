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

        // Get learning roadmap nodes with user progress
        $roadmap = [];
        if ($examId) {
            $nodes = LearningPathNode::where('exam_id', $examId)
                ->orderBy('sort_order')
                ->get();

            $progressMap = UserLearningProgress::where('user_id', $user->id)
                ->whereIn('node_id', $nodes->pluck('id'))
                ->pluck('status', 'node_id');

            $roadmap = $nodes->map(function ($node) use ($progressMap) {
                return [
                    'id' => $node->id,
                    'sort_order' => $node->sort_order,
                    'title' => $node->title,
                    'description' => $node->description,
                    'icon' => $node->icon,
                    'skill_type' => $node->skill_type,
                    'level' => $node->level,
                    'xp_reward' => $node->xp_reward,
                    'node_type' => $node->node_type,
                    'status' => $progressMap[$node->id] ?? 'locked',
                ];
            })->values()->toArray();
        }

        return Inertia::render('dashboard', [
            'profile' => $profile,
            'roadmap' => $roadmap,
        ]);
    }
}
