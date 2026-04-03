<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\LeaderboardEntry;
use App\Models\LearningPathNode;
use App\Models\UserExerciseAttempt;
use App\Models\UserLearningProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExerciseController extends Controller
{
    protected \App\Services\ExerciseScoringService $scoringService;
    protected \App\Services\StreakService $streakService;

    public function __construct(
        \App\Services\ExerciseScoringService $scoringService,
        \App\Services\StreakService $streakService
    ) {
        $this->scoringService = $scoringService;
        $this->streakService = $streakService;
    }

    public function submitSession(Request $request, LearningPathNode $node)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'answers' => 'required|array',
        ]);

        // 1. Marquer le nœud comme complété
        $progress = UserLearningProgress::where('user_id', $user->id)
            ->where('node_id', $node->id)
            ->first();

        if ($progress) {
            $progress->update([
                'status' => 'completed',
                'exercises_done' => $progress->exercises_required,
                'exercises_required' => 3
            ]);

            // 2. Débloquer le nœud suivant dans le syllabus
            $nextNode = LearningPathNode::where('exam_id', $node->exam_id)
                ->where(function($query) use ($node) {
                    $query->where('chapter_order', '>', $node->chapter_order)
                          ->orWhere(function($q) use ($node) {
                              $q->where('chapter_order', $node->chapter_order)
                                ->where('sort_order', '>', $node->sort_order);
                          });
                })
                ->orderBy('chapter_order')
                ->orderBy('sort_order')
                ->first();

            if ($nextNode) {
                UserLearningProgress::updateOrCreate(
                    ['user_id' => $user->id, 'node_id' => $nextNode->id],
                    ['status' => 'available']
                );
            }
        }

        // 3. Ajouter de l'XP à l'utilisateur
        $xpEarned = $node->xp_reward ?? 50;
        $user->profile?->increment('xp_total', $xpEarned);

        // 4. Mettre à jour le classement hebdomadaire
        $this->incrementLeaderboard($user->id, $xpEarned);

        // 5. Mettre à jour la série (streak)
        if ($user instanceof \App\Models\User) {
            $this->streakService->recordActivity($user);
        }

        return redirect()->route('dashboard')->with('success', "Session terminée ! +{$xpEarned} XP");
    }

    public function show(Exercise $exercise): Response
    {
        $exercise->load(['exerciseType.section', 'exam.language']);

        return Inertia::render('exercise/show', [
            'exercise' => $exercise,
        ]);
    }

    public function submit(Request $request, Exercise $exercise)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'answers' => 'required|array',
            'time_spent' => 'required|integer|min:0',
        ]);

        // Score the exercise
        $result = $this->scoringService->score($exercise, $validated['answers']);

        $attempt = UserExerciseAttempt::create([
            'user_id' => $user->id,
            'exercise_id' => $exercise->id,
            'answers' => $validated['answers'],
            'score' => $result['score'],
            'accuracy_percent' => $result['accuracy'],
            'time_spent' => $validated['time_spent'],
            'xp_earned' => $result['xp'],
            'feedback' => $result['feedback'],
        ]);

        // Update user XP
        if ($user instanceof \App\Models\User && $user->profile) {
            $user->profile->increment('xp_total', $result['xp']);
            $this->streakService->recordActivity($user);
        }

        // Mettre à jour le classement hebdomadaire
        $this->incrementLeaderboard($user->id, $result['xp']);

        // Update node progress if this exercise came from a node
        $nodeCompleted = false;
        $nodeId = session('current_node_id');
        if ($nodeId) {
            $userId = $user->id;
            $progress = UserLearningProgress::where('user_id', $userId)
                ->where('node_id', $nodeId)
                ->whereIn('status', ['in_progress', 'available'])
                ->first();

            if ($progress) {
                $progress->increment('exercises_done');
                $progress->refresh();

                if ($progress->exercises_done >= $progress->exercises_required) {
                    $progress->update(['status' => 'completed']);
                    $nodeCompleted = true;

                    // Unlock the next node for this user
                    $node = LearningPathNode::find($nodeId);
                    if ($node) {
                        $nextNode = LearningPathNode::where('exam_id', $node->exam_id)
                            ->where('sort_order', '>', $node->sort_order)
                            ->orderBy('sort_order')
                            ->first();

                        if ($nextNode) {
                            UserLearningProgress::where('user_id', $userId)
                                ->where('node_id', $nextNode->id)
                                ->where('status', 'locked')
                                ->update(['status' => 'available']);
                        }
                    }

                    session()->forget('current_node_id');
                    session(['last_node_id' => $nodeId]);
                }
            }
        }

        return redirect()->route('exercise.result', [
            'attempt' => $attempt,
            'node_completed' => $nodeCompleted ? 1 : 0,
            'node_id' => $nodeId,
        ]);
    }

    public function result(UserExerciseAttempt $attempt, \Illuminate\Http\Request $request): Response
    {
        $attempt->load(['exercise.exerciseType.section', 'exercise.exam.language']);

        // Load node progress if applicable
        $nodeProgress = null;
        $nodeId = $request->query('node_id') ?: session('last_node_id');
        $nodeCompleted = (bool) $request->query('node_completed', 0);

        if ($nodeId) {
            $progress = UserLearningProgress::where('user_id', auth()->id())
                ->where('node_id', $nodeId)
                ->first();
            if ($progress) {
                $nodeProgress = [
                    'node_id' => $nodeId,
                    'exercises_done' => $progress->exercises_done,
                    'exercises_required' => $progress->exercises_required,
                    'completed' => $nodeCompleted,
                ];
            }
        }

        return Inertia::render('exercise/result', [
            'attempt' => $attempt,
            'nodeProgress' => $nodeProgress,
        ]);
    }


    public function explainMistake(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string',
            'user_answer' => 'required|string',
            'correct_answer' => 'required|string',
            'language' => 'required|string',
        ]);

        $explanation = $this->scoringService->explainMistake(
            $validated['prompt'] ?? '',
            $validated['user_answer'] ?? '',
            $validated['correct_answer'] ?? '',
            $validated['language'] ?? 'English'
        );

        return response()->json(['explanation' => $explanation]);
    }

    private function incrementLeaderboard(int $userId, int $xp): void
    {
        if ($xp <= 0) return;

        $periodKey = now()->format('Y-\WW'); // ex: 2026-W12

        LeaderboardEntry::updateOrCreate(
            ['user_id' => $userId, 'period_type' => 'weekly', 'period_key' => $periodKey],
            ['xp' => 0] // valeur initiale si création
        );

        LeaderboardEntry::where('user_id', $userId)
            ->where('period_type', 'weekly')
            ->where('period_key', $periodKey)
            ->increment('xp', $xp);
    }
}
