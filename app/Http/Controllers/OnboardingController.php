<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\UserLearningProgress;
use App\Models\UserProfile;
use App\Services\AI\PlacementTestService;
use App\Services\AI\RoadmapGeneratorService;
use App\Services\Curriculum\CurriculumPlannerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(
        protected PlacementTestService $placementTest,
        protected RoadmapGeneratorService $roadmapGenerator,
        protected CurriculumPlannerService $curriculumPlanner
    ) {
    }

    // ─── Étape 1 : Langue maternelle ────────────────────────────────────────

    public function nativeLanguage(): Response
    {
        return Inertia::render('onboarding/native-language', [
            'currentNativeLanguage' => auth()->user()->profile?->native_language,
        ]);
    }

    public function storeNativeLanguage(Request $request)
    {
        $validated = $request->validate([
            'native_language' => 'required|string|max:50',
        ]);

        UserProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['native_language' => $validated['native_language']]
        );

        return redirect()->route('onboarding.exam');
    }

    // ─── Étape 2 : Choix de l'examen ────────────────────────────────────────

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
            'current_level' => 'nullable|string|in:A0,A1,B1,TEST',
        ]);

        $request->user()->profile->update([
            'target_score' => $validated['target_score'],
            'exam_date' => $validated['exam_date'],
            'current_level' => $validated['current_level'] === 'TEST' ? null : $validated['current_level'],
            'level_source' => $validated['current_level'] && $validated['current_level'] !== 'TEST' ? 'declared' : null,
        ]);

        if ($validated['current_level'] && $validated['current_level'] !== 'TEST') {
            return redirect()->route('onboarding.result');
        }

        return redirect()->route('onboarding.placement');
    }

    public function placementTest(): Response|\Illuminate\Http\RedirectResponse
    {
        $profile = auth()->user()->profile;
        $exam = $profile?->targetExam?->load('language');

        if (!$exam) {
            return redirect()->route('onboarding.exam');
        }

        $language = $exam->language;
        $nativeLanguage = $profile->native_language ?? 'Français';
        $cacheKey = "placement_v2_{$exam->id}_{$profile->user_id}";

        $testData = Cache::remember($cacheKey, 1800, function () use ($language, $exam, $nativeLanguage) {
            return $this->placementTest->generateFullTest(
                $language->name,
                $language->native_name ?? $language->name,
                $exam->name,
                $nativeLanguage
            );
        });

        return Inertia::render('onboarding/placement-test', [
            'exam'     => $exam,
            'sectionA' => $testData['section_a'],
            'sectionB' => $testData['section_b'],
            'sectionC' => $testData['section_c'],
        ]);
    }

    public function submitPlacement(Request $request)
    {
        $validated = $request->validate([
            'answers'         => 'required|array',
            'questions'       => 'required|string',
            'essay_text'      => 'nullable|string|max:6000',
            'essay_time_used' => 'nullable|integer|min:0',
        ]);

        $questions = json_decode($validated['questions'], true) ?? [];

        $level = $this->placementTest->evaluateFullLevel(
            $questions,
            $validated['answers'],
            $validated['essay_text'] ?? ''
        );

        $request->user()->profile->update(['current_level' => $level, 'level_source' => 'tested']);

        Cache::put("placement_answers_{$request->user()->id}", [
            'questions'  => $validated['questions'],
            'answers'    => $validated['answers'],
            'essay_text' => $validated['essay_text'] ?? '',
            'level'      => $level,
        ], 3600);

        return redirect()->route('onboarding.result');
    }

    public function result(): Response
    {
        $user = auth()->user();
        $profile = $user->profile->load('targetExam.language');
        $exam = $profile->targetExam;

        // The personalized program is generated by an AI call that can take several
        // seconds. We render the result page INSTANTLY and let it fetch the program
        // asynchronously (see programData) behind a loader, so the onboarding never
        // appears frozen. If it's already cached we pass it straight through to skip
        // the extra round-trip.
        $cacheKey = "study_program_{$user->id}_{$profile->current_level}";
        $program = Cache::get($cacheKey);

        return Inertia::render('onboarding/result', [
            'profile' => $profile->append([]),
            'exam'    => $exam ? [
                'id'     => $exam->id,
                'name'   => $exam->name,
                'levels' => $exam->levels ?? [],
                'language' => [
                    'name'        => $exam->language?->name,
                    'native_name' => $exam->language?->native_name,
                    'flag'        => $exam->language?->flag,
                ],
            ] : null,
            'program' => $program, // null on first visit → fetched client-side
        ]);
    }

    /**
     * GET /onboarding/program — async JSON: generate (and cache) the AI study
     * program. Called by the result page behind a loader so the page itself is
     * instant. A0 means complete beginner.
     */
    public function programData(): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $profile = $user->profile->load('targetExam.language');
        $exam = $profile->targetExam;

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

        return response()->json(['program' => $program]);
    }

    public function complete(Request $request)
    {
        $profile = $request->user()->profile->load('targetExam.language');
        $exam = $profile->targetExam;

        if ($exam) {
            $startLevel = $profile->current_level === 'A0' ? 'A1' : ($profile->current_level ?? 'A1');

            // Pilier 9: Use CurriculumPlannerService to build an adaptive skeleton
            // instead of the deterministic roadmap.
            // Both the skeleton build and the legacy fallback are AI calls that can be
            // slow or fail. We must NEVER let an exception here block the redirect to
            // the dashboard — otherwise "Voir mon parcours" appears to do nothing.
            try {
                $this->curriculumPlanner->buildSkeleton(
                    $request->user(),
                    $exam,
                    $startLevel
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Curriculum skeleton generation failed, falling back to legacy roadmap', [
                    'error' => $e->getMessage(),
                ]);

                // Fallback: generate legacy roadmap if skeleton generation fails.
                // Guarded so a second failure still lets the user reach the dashboard.
                try {
                    $this->roadmapGenerator->generateForUser(
                        $request->user(),
                        $exam,
                        $startLevel,
                        $profile->exam_date
                    );
                } catch (\Throwable $e2) {
                    \Illuminate\Support\Facades\Log::error('Legacy roadmap fallback also failed; proceeding to dashboard', [
                        'error' => $e2->getMessage(),
                    ]);
                }
            }
        }

        $profile->update([
            'onboarding_completed_at' => now(),
        ]);

        // Clear caches
        Cache::forget("placement_questions_{$profile->target_exam_id}_{$request->user()->id}");
        Cache::forget("placement_v2_{$profile->target_exam_id}_{$request->user()->id}");
        Cache::forget("placement_answers_{$request->user()->id}");

        return redirect()->route('dashboard');
    }
}
