<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\UserLearningProgress;
use App\Models\UserProfile;
use App\Services\AI\PlacementTestService;
use App\Services\AI\RoadmapGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(
        protected PlacementTestService $placementTest,
        protected RoadmapGeneratorService $roadmapGenerator
    ) {
    }

    public function examSelect(): Response
    {
        $languages = Language::where('is_active', true)
            ->with('exams')
            ->get();

        return Inertia::render('onboarding/exam-select', [
            'languages' => $languages,
        ]);
    }

    public function storeExam(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
        ]);

        UserProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['target_exam_id' => $validated['exam_id']]
        );

        return redirect()->route('onboarding.goal');
    }

    public function goalSetting(): Response
    {
        $profile = auth()->user()->profile;
        $exam = $profile?->targetExam;

        return Inertia::render('onboarding/goal-setting', [
            'exam' => $exam?->load('language'),
            'profile' => $profile,
        ]);
    }

    public function storeGoal(Request $request)
    {
        $validated = $request->validate([
            'target_score' => 'nullable|integer|min:0',
            'exam_date' => 'nullable|date|after:today',
        ]);

        $request->user()->profile->update($validated);

        return redirect()->route('onboarding.placement');
    }

    public function placementTest(): Response
    {
        $profile = auth()->user()->profile;
        $exam = $profile?->targetExam?->load('language');

        if (!$exam) {
            return redirect()->route('onboarding.exam');
        }

        $language = $exam->language;
        $cacheKey = "placement_questions_{$exam->id}_{$profile->user_id}";

        // Generate or retrieve cached questions (cache for 30 min per user session)
        $questions = Cache::remember($cacheKey, 1800, function () use ($language, $exam) {
            return $this->placementTest->generateQuestions(
                $language->name,
                $language->native_name,
                $exam->name
            );
        });

        return Inertia::render('onboarding/placement-test', [
            'exam' => $exam,
            'questions' => $questions,
        ]);
    }

    public function submitPlacement(Request $request)
    {
        $validated = $request->validate([
            'answers' => 'required|array',
            'questions' => 'required|string',
        ]);

        $questions = json_decode($validated['questions'], true) ?? [];

        // Evaluate level based on answers
        $level = $this->placementTest->evaluateLevel(
            $questions,
            $validated['answers']
        );

        $request->user()->profile->update([
            'current_level' => $level,
        ]);

        // Cache the questions so result page can use them
        $cacheKey = "placement_answers_{$request->user()->id}";
        Cache::put($cacheKey, [
            'questions' => $validated['questions'],
            'answers' => $validated['answers'],
            'level' => $level,
        ], 3600);

        return redirect()->route('onboarding.result');
    }

    public function result(): Response
    {
        $user = auth()->user();
        $profile = $user->profile->load('targetExam.language');
        $exam = $profile->targetExam;

        // Generate personalized program
        $cacheKey = "study_program_{$user->id}_{$profile->current_level}";

        $program = Cache::remember($cacheKey, 86400, function () use ($user, $profile, $exam) {
            return $this->placementTest->generateProgram(
                $exam?->language?->name ?? 'English',
                $exam?->name ?? 'Language Exam',
                $profile->current_level ?? 'B1',
                $profile->target_score,
                $profile->exam_date?->format('Y-m-d'),
                $user->name
            );
        });

        return Inertia::render('onboarding/result', [
            'profile' => $profile,
            'program' => $program,
        ]);
    }

    public function complete(Request $request)
    {
        $profile = $request->user()->profile->load('targetExam.language');
        $exam = $profile->targetExam;

        // Generate learning roadmap via Algorithm (Duolingo-style)
        if ($exam) {
            $this->roadmapGenerator->generateForUser(
                $request->user(),
                $exam,
                $profile->current_level ?? 'A1',
                $profile->exam_date
            );
        }

        $profile->update([
            'onboarding_completed_at' => now(),
        ]);

        // Clear caches
        Cache::forget("placement_questions_{$profile->target_exam_id}_{$request->user()->id}");
        Cache::forget("placement_answers_{$request->user()->id}");

        return redirect()->route('dashboard');
    }
}
