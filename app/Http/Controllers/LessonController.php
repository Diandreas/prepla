<?php

namespace App\Http\Controllers;

use App\Models\CurriculumSkeleton;
use App\Models\LeaderboardEntry;
use App\Models\Lesson;
use App\Models\UserError;
use App\Services\Curriculum\CurriculumPlannerService;
use App\Services\Curriculum\NextLessonGenerator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    public function __construct(
        protected NextLessonGenerator $lessonGenerator,
        protected CurriculumPlannerService $planner
    ) {}

    /**
     * GET /lessons — list recent lessons for the user
     */
    public function index(): Response
    {
        $user = auth()->user();

        $lessons = Lesson::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        $skeleton = CurriculumSkeleton::where('user_id', $user->id)->first();

        // Error category stats for the weakness dashboard
        $errorStats = UserError::categoryStats($user->id);

        return Inertia::render('learning/index', [
            'lessons' => $lessons,
            'skeleton' => $skeleton ? [
                'objectives' => $skeleton->objectives,
                'current_index' => $skeleton->current_objective_index,
                'current_objective' => $skeleton->currentObjective(),
                'progress_percent' => $this->calculateSkeletonProgress($skeleton),
            ] : null,
            'errorStats' => $errorStats,
        ]);
    }

    /**
     * GET /lessons/next — generate and show the next lesson (JIT)
     */
    public function next(): Response|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();

        $lesson = $this->lessonGenerator->generate($user);

        if (!$lesson) {
            return redirect()->route('lessons.index')->with('error', 'Impossible de générer la prochaine leçon.');
        }

        return redirect()->route('lessons.show', $lesson->id);
    }

    /**
     * GET /lessons/{lesson} — show a lesson
     */
    public function show(Lesson $lesson): Response
    {
        $user = auth()->user();

        // Ensure the lesson belongs to the user
        if ($lesson->user_id !== $user->id) {
            abort(403);
        }

        $skeleton = CurriculumSkeleton::where('user_id', $user->id)->first();

        return Inertia::render('learning/lesson', [
            'lesson' => $lesson,
            'skeleton' => $skeleton ? [
                'current_objective' => $skeleton->currentObjective(),
                'current_index' => $skeleton->current_objective_index,
                'total_objectives' => count($skeleton->objectives ?? []),
                'consecutive_failures' => $skeleton->consecutive_failures,
            ] : null,
        ]);
    }

    /**
     * POST /lessons/{lesson}/quiz — submit comprehension quiz answers
     */
    public function submitQuiz(Request $request, Lesson $lesson)
    {
        $user = auth()->user();

        if ($lesson->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'answers' => 'required|array',
        ]);

        $passed = $lesson->isComprehensionPassed($validated['answers']);

        // Calculate quiz results
        $quiz = $lesson->comprehension_quiz ?? [];
        $results = [];
        $correctCount = 0;
        foreach ($quiz as $index => $question) {
            $userAnswer = $validated['answers'][$index] ?? null;
            $isCorrect = \App\Models\Lesson::checkAnswerMatch($userAnswer, $question['correct_answer'] ?? '');
            if ($isCorrect) $correctCount++;
            $results[] = [
                'question' => $question['question'],
                'user_answer' => $userAnswer,
                'correct_answer' => $question['correct_answer'],
                'correct' => $isCorrect,
                'explanation' => $question['explanation'] ?? null,
            ];
        }

        $accuracy = count($quiz) > 0 ? round(($correctCount / count($quiz)) * 100) : 100;

        // Record outcome for curriculum adaptation. Pass the explicit quiz verdict
        // ($passed, 2/3 threshold) so the practice phase opens for any passing score,
        // matching the "Pratiquer ce concept" CTA the UI shows on success.
        $outcome = $this->planner->recordLessonOutcome($user, $accuracy, $passed);

        // Award XP and update streak if passed
        if ($passed) {
            $xpReward = $lesson->node?->xp_reward ?? 20;
            $profile = $user->profile;
            $profile->xp_total = ($profile->xp_total ?? 0) + $xpReward;

            // Update streak
            $today = now()->toDateString();
            $lastDate = $profile->streak_last_date?->toDateString();
            if ($lastDate === $today) {
                // Already practiced today — no change
            } elseif ($lastDate === now()->subDay()->toDateString()) {
                // Consecutive day
                $profile->streak_current = ($profile->streak_current ?? 0) + 1;
            } else {
                // Streak broken or first time
                $profile->streak_current = 1;
            }
            $profile->streak_last_date = $today;
            $profile->save();

            // Update weekly leaderboard entry
            $weekKey = now()->format('Y-\WW');
            $entry = LeaderboardEntry::firstOrNew([
                'user_id'     => $user->id,
                'period_type' => 'weekly',
                'period_key'  => $weekKey,
            ]);
            $entry->xp = ($entry->xp ?? 0) + $xpReward;
            $entry->save();
        }

        // Trigger reassessment if needed
        if ($accuracy < 60) {
            $this->planner->reassess($user);
        }

        return response()->json([
            'passed' => $passed,
            'accuracy' => $accuracy,
            'results' => $results,
            'outcome' => $outcome,
            'message' => match($outcome) {
                'advance' => 'Bravo ! La leçon est validée, place à la pratique.',
                'skip_ahead' => 'Excellent ! La leçon est validée, place à la pratique approfondie.',
                'consolidation' => 'Ne t\'inquiète pas — la prochaine leçon reprendra ce concept différemment.',
                'retry_concept' => 'Bon effort ! On va approfondir ce point théorique.',
                default => 'Continue comme ça !',
            },
        ]);
    }

    /**
     * Calculate progress percentage for the skeleton.
     */
    private function calculateSkeletonProgress(CurriculumSkeleton $skeleton): int
    {
        $objectives = $skeleton->objectives ?? [];
        if (empty($objectives)) return 0;

        $done = collect($objectives)->filter(fn ($o) => ($o['status'] ?? '') === 'done')->count();
        return (int) round(($done / count($objectives)) * 100);
    }
}
