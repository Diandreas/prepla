<?php

namespace App\Http\Controllers;

use App\Models\CurriculumSkeleton;
use App\Models\LeaderboardEntry;
use App\Models\Lesson;
use App\Models\UserError;
use App\Services\Curriculum\CurriculumPlannerService;
use App\Services\Curriculum\NextLessonGenerator;
use App\Services\PersonalLexiconService;
use Illuminate\Http\RedirectResponse;
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
    public function next(): Response|RedirectResponse
    {
        $user = auth()->user();

        $skeleton = CurriculumSkeleton::where('user_id', $user->id)->first();
        $skeleton?->ensureLevelExams();
        if ($pending = $skeleton?->pendingLevelExam()) {
            return redirect()->route('level.exam', $pending['level']);
        }
        if ($skeleton?->currentObjective()['is_level_exam'] ?? false) {
            $pending = $skeleton->pendingLevelExam();

            return $pending
                ? redirect()->route('level.exam', $pending['level'])
                : redirect()->route('dashboard')->with('error', 'Termine la pratique avant cet examen.');
        }
        $lesson = $this->lessonGenerator->generate($user);

        if (! $lesson) {
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

        app(\App\Services\Content\IntroductionLesson::class)->strengthen($lesson);
        $quality = app(\App\Services\Content\LessonQuizQuality::class);
        $quiz = $lesson->comprehension_quiz ?? [];
        $lesson->comprehension_quiz = $quality->normalize($quiz);
        $lesson->setAttribute('quiz_needs_repair', count($quiz) > 0 && ! $quality->valid($quiz));

        $skeleton = CurriculumSkeleton::where('user_id', $user->id)->first();

        return Inertia::render('learning/lesson', [
            'lesson' => $lesson,
            'lessonWords' => app(PersonalLexiconService::class)->lessonWords($user, $lesson),
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

        $quality = app(\App\Services\Content\LessonQuizQuality::class);
        $quiz = $lesson->comprehension_quiz ?? [];
        abort_if($quiz && ! $quality->valid($quiz), 422, 'Ce quiz doit être corrigé avant de pouvoir être évalué. Ta progression reste inchangée.');
        $lesson->comprehension_quiz = $quality->normalize($quiz);
        $passed = $lesson->isComprehensionPassed($validated['answers']);

        // Calculate quiz results
        $quiz = $lesson->comprehension_quiz ?? [];
        $results = [];
        $correctCount = 0;
        foreach ($quiz as $index => $question) {
            $userAnswer = $validated['answers'][$index] ?? null;
            $isCorrect = Lesson::isQuestionCorrect($question, $userAnswer);
            if ($isCorrect) {
                $correctCount++;
            }
            $results[] = [
                'question' => $question['question'],
                'user_answer' => $userAnswer,
                // Return the full option text (not the stored "C" letter) so the
                // UI shows the actual correct sentence.
                'correct_answer' => Lesson::resolveCorrectAnswerText($question),
                'correct' => $isCorrect,
                'explanation' => $question['explanation'] ?? null,
            ];
        }

        // Une leçon sans questions ne mesure rien : elle valait 100 %, ce qui nourrissait
        // la série de réussites et pouvait faire sauter la leçon suivante, sur un savoir
        // jamais vérifié. `null` dit explicitement « non évalué ».
        $accuracy = count($quiz) > 0 ? round(($correctCount / count($quiz)) * 100) : null;

        // Record outcome for curriculum adaptation. Pass the explicit quiz verdict
        // ($passed, 2/3 threshold) so the practice phase opens for any passing score,
        // matching the "Pratiquer ce concept" CTA the UI shows on success.
        $path = CurriculumSkeleton::where('user_id', $user->id)->first();
        $isCurrent = $path && $path->current_objective_index === (int) $lesson->skeleton_objective_index
            && in_array($path->currentObjective()['status'] ?? '', ['current', 'current_lesson'], true);
        $outcome = $isCurrent
            ? $this->planner->recordLessonOutcome($user, $accuracy, $accuracy === null ? null : $passed)
            : 'review';

        // Actually perform the skip the 'skip_ahead' signal promises — previously
        // this outcome only changed the message shown to the user, with no real
        // effect on the skeleton, so a fast learner never actually advanced faster.
        if ($outcome === 'skip_ahead') {
            $skeleton = CurriculumSkeleton::where('user_id', $user->id)->first();
            $skeleton?->skipAhead(1);
        }

        // Award XP and update streak if passed
        if ($passed && $isCurrent) {
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
                'user_id' => $user->id,
                'period_type' => 'weekly',
                'period_key' => $weekKey,
            ]);
            $entry->xp = ($entry->xp ?? 0) + $xpReward;
            $entry->save();
        }

        // Trigger reassessment if needed. `null` veut dire « non évalué » : en PHP il
        // serait passé pour inférieur à 60 et aurait déclenché une réévaluation du
        // parcours à chaque leçon arrivée sans questions.
        if ($isCurrent && $accuracy !== null && $accuracy < 60) {
            $this->planner->reassess($user);
        }

        return response()->json([
            'passed' => $passed,
            'accuracy' => $accuracy,
            'results' => $results,
            'outcome' => $outcome,
            'message' => match ($outcome) {
                'advance' => 'Bravo ! La leçon est validée, place à la pratique.',
                'skip_ahead' => 'Excellent ! Tu progresses vite, on saute directement au concept suivant.',
                'consolidation' => 'Ne t\'inquiète pas — la prochaine leçon reprendra ce concept différemment.',
                'retry_concept' => 'Bon effort ! On va approfondir ce point théorique.',
                'unblocked_after_struggle' => 'Ce concept est difficile — on passe au suivant, tu y reviendras plus tard pour le retravailler.',
                'not_assessed' => 'Cette leçon n\'avait pas de questions : rien n\'a été noté. Passe à la pratique pour la mettre à l\'épreuve.',
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
        if (empty($objectives)) {
            return 0;
        }

        $done = collect($objectives)->filter(fn ($o) => ($o['status'] ?? '') === 'done')->count();

        return (int) round(($done / count($objectives)) * 100);
    }
}
