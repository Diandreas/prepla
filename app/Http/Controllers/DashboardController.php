<?php

namespace App\Http\Controllers;

use App\Models\CurriculumSkeleton;
use App\Models\LearningPathNode;
use App\Models\Lesson;
use App\Models\UserError;
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

        // Check if user has the new curriculum skeleton (Pilier 9)
        $skeleton = CurriculumSkeleton::where('user_id', $user->id)->first();
        $hasCurriculum = $skeleton !== null;

        // Curriculum data
        $curriculumData = null;
        $nextLesson = null;
        $errorDiagnostic = [];

        if ($hasCurriculum) {
            // New adaptive system
            $currentObjective = $skeleton->currentObjective();
            $objectives = $skeleton->objectives ?? [];
            $doneCount = collect($objectives)->filter(fn ($o) => ($o['status'] ?? '') === 'done')->count();
            $practiceDoneCount = collect($objectives)->filter(fn ($o) => ($o['status'] ?? '') === 'current_practice')->count();
            $totalCount = count($objectives);

            $curriculumData = [
                'current_objective' => $currentObjective,
                'current_index' => $skeleton->current_objective_index,
                'total_objectives' => $totalCount,
                'progress_percent' => $totalCount > 0 ? (int) round((($doneCount + $practiceDoneCount) / $totalCount) * 100) : 0,
                'consecutive_failures' => $skeleton->consecutive_failures,
            ];

            // Get the most recent lesson
            $nextLesson = Lesson::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->first();

            // Error diagnostics (Pilier 4)
            $errorDiagnostic = UserError::categoryStats($user->id);

            $totalNodes = $totalCount * 2; // Each objective is 2 nodes (Lesson + Practice)
            $completedNodes = $doneCount * 2 + $practiceDoneCount; // each current_practice = lesson done (1 node)

            // Fetch explicitly related lessons
            $userLessons = Lesson::where('user_id', $user->id)
                ->whereNotNull('skeleton_objective_index')
                ->get()
                ->keyBy('skeleton_objective_index');

            // --- PILOT 9: GENERATE ROADMAP FROM CURRICULUM SKELETON ---
            $chapterIndex = 1;
            // Group by 3 objectives per "chapter" visually
            $groupedObjectives = collect($objectives)->chunk(3);

            foreach ($groupedObjectives as $chunkIndex => $objChunk) {
                $nodes = [];
                foreach ($objChunk as $i => $objective) {
                    $globalIndex = ($chunkIndex * 3) + $i;
                    
                    $lessonStatus = 'locked';
                    $practiceStatus = 'locked';
                    
                    $objStatus = $objective['status'] ?? 'pending';
                    
                    if ($objStatus === 'done') {
                        $lessonStatus = 'completed';
                        $practiceStatus = 'completed';
                    } elseif ($objStatus === 'current_practice') {
                        $lessonStatus = 'completed';
                        $practiceStatus = 'available';
                    } elseif ($objStatus === 'current') {
                        $lessonStatus = 'available';
                        $practiceStatus = 'locked';
                    }

                    // Try to link the exact lesson if already generated/started
                    $relatedLesson = $userLessons->get($globalIndex);
                    $lessonUrl = $relatedLesson
                        ? route('lessons.show', $relatedLesson->id)
                        : route('lessons.next');

                    // Make sure a LearningPathNode exists for this objective for the Practice session
                    $nodeEntity = LearningPathNode::firstOrCreate(
                        ['exam_id' => $examId, 'title' => $objective['title']],
                        [
                            'chapter_name' => 'Étape ' . $chapterIndex,
                            'chapter_order' => $chapterIndex,
                            'sort_order' => $globalIndex,
                            'description' => $objective['concept'],
                            'node_type' => 'lesson',
                            'skill_type' => 'grammar',
                            'xp_reward' => 30,
                            'level' => 'A1'
                        ]
                    );

                    // Nœud Théorique (Leçon)
                    $nodes[] = [
                        'id' => 'l_' . $globalIndex,
                        'title' => 'Théorie',
                        'description' => $objective['title'],
                        'icon' => 'book',
                        'skill_type' => 'theory',
                        'level' => 'A1',
                        'status' => $lessonStatus,
                        'xp_reward' => 10,
                        'type' => 'lesson', // New property for UI mapping
                        'action_url' => $lessonUrl,
                    ];
                    
                    // Nœud Pratique (Exercice)
                    $nodes[] = [
                        'id' => 'p_' . $globalIndex,
                        'title' => 'Pratique',
                        'description' => 'Exercices: ' . $objective['title'],
                        'icon' => 'target',
                        'skill_type' => 'practice',
                        'level' => 'A1',
                        'status' => $practiceStatus,
                        'xp_reward' => 30,
                        'type' => 'practice',
                        'action_url' => route('node.start', $nodeEntity->id),
                    ];
                }
                
                $chapters[] = [
                    'name' => 'Module ' . $chapterIndex,
                    'order' => $chapterIndex,
                    'nodes' => $nodes
                ];
                $chapterIndex++;
            }
        } elseif ($examId) {
            // Legacy roadmap system (kept for backwards compatibility)
            $userProgress = UserLearningProgress::where('user_id', $user->id)
                ->with(['node' => function($q) use ($examId) {
                    $q->where('exam_id', $examId);
                }])
                ->get()
                ->filter(fn($p) => $p->node !== null) // Sécurité si un nœud a été supprimé
                ->sortBy(function($p) {
                    return $p->node->chapter_order * 1000 + $p->node->sort_order;
                });

            if (!$hasCurriculum) {
                $totalNodes = $userProgress->count();
                $completedNodes = $userProgress->where('status', 'completed')->count();
            }

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

        // Due errors count (Pilier 3: SM-2)
        $dueErrorsCount = UserError::dueForReview($user->id)->count();

        return Inertia::render('dashboard', [
            'profile' => $profile,
            'chapters' => $chapters,
            'stats' => [
                'total_nodes' => $totalNodes,
                'completed_nodes' => $completedNodes,
                'progress_percent' => $totalNodes > 0 ? round(($completedNodes / $totalNodes) * 100) : 0,
            ],
            // New curriculum data (Pilier 9)
            'curriculum' => $curriculumData,
            'nextLesson' => $nextLesson,
            'errorDiagnostic' => $errorDiagnostic,
            'dueErrorsCount' => $dueErrorsCount,
        ]);
    }
}
