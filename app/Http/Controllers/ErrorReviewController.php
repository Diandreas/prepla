<?php

namespace App\Http\Controllers;

use App\Models\UserError;
use App\Services\ErrorSpacedRepetitionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ErrorReviewController extends Controller
{
    protected ErrorSpacedRepetitionService $sm2;

    public function __construct(ErrorSpacedRepetitionService $sm2)
    {
        $this->sm2 = $sm2;
    }

    // GET /errors - list unmastered errors
    public function index(Request $request)
    {
        $user = $request->user();

        $errors = UserError::unmastered($user->id)->paginate(20);
        // Normalise field names for the frontend (reviewed_count → review_count,
        // question_text → prompt) and expose the pedagogical family.
        $errors->getCollection()->transform(fn ($e) => [
            'id' => $e->id,
            'skill_type' => $e->skill_type,
            'prompt' => $e->question_text,
            'user_answer' => $e->user_answer,
            'correct_answer' => $e->correct_answer,
            'explanation' => $e->explanation,
            'mastered' => $e->mastered,
            'review_count' => $e->reviewed_count ?? 0,
            'next_review_at' => $e->next_review_at,
            'created_at' => $e->created_at,
            'family' => UserError::classifyFamily($e->exercise_type_slug, $e->skill_type),
        ]);

        $errorsBySkill = UserError::where('user_id', $user->id)
            ->where('mastered', false)
            ->selectRaw('skill_type, count(*) as count')
            ->groupBy('skill_type')
            ->pluck('count', 'skill_type');

        // Pilier 4: Error category stats
        $errorsByCategory = UserError::categoryStats($user->id);

        // Pilier 3: Due errors count
        $dueForReviewCount = UserError::dueForReview($user->id)->count();

        return Inertia::render('errors/index', [
            'errors' => $errors,
            'errorsBySkill' => $errorsBySkill,
            'errorsByCategory' => $errorsByCategory,
            'dueForReviewCount' => $dueForReviewCount,
        ]);
    }

    // GET /errors/practice - personalized error review session.
    // Only CONCEPT errors are re-practised here (grammar/vocab/writing…): re-testing
    // a comprehension question would just make the learner memorise that one passage.
    public function practice(Request $request)
    {
        $user = $request->user();
        $skillType = $request->query('skill');

        // Pilier 3: SM-2 due errors first, then recent unmastered — concept only.
        // Comprehension errors (reading/listening on a passage) are excluded: they
        // can't be re-posed without the original text.
        $dueErrors = UserError::dueForReview($user->id)
            ->whereNotIn('skill_type', ['reading', 'listening'])
            ->where(function ($q) {
                $q->whereIn('exercise_type_slug', UserError::CONCEPT_SLUGS)
                  ->orWhereIn('skill_type', ['grammar', 'vocabulary', 'use-of-english', 'writing']);
            })
            ->when($skillType, fn($q) => $q->where('skill_type', $skillType))
            ->limit(10)
            ->get();

        if ($dueErrors->isEmpty()) {
            $dueErrors = UserError::concept($user->id)
                ->when($skillType, fn($q) => $q->where('skill_type', $skillType))
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        return Inertia::render('errors/practice', [
            'errors' => $dueErrors->map(fn ($e) => [
                'id' => $e->id,
                'skill_type' => $e->skill_type,
                'prompt' => $e->question_text,
                'user_answer' => $e->user_answer,
                'correct_answer' => $e->correct_answer,
                'explanation' => $e->explanation,
                'mastered' => $e->mastered,
                'review_count' => $e->reviewed_count,
                'created_at' => $e->created_at,
            ])->values(),
        ]);
    }

    // POST /errors/{error}/review - mark as reviewed with SM-2
    public function submitReview(Request $request, UserError $error)
    {
        $validated = $request->validate([
            'correct' => 'required|boolean',
        ]);

        // Pilier 3: Use SM-2 scheduling instead of simple counter
        $this->sm2->schedule($error, $validated['correct']);

        return response()->json([
            'success' => true,
            'mastered' => $error->mastered,
            'next_review_at' => $error->next_review_at?->format('Y-m-d H:i'),
            'interval_days' => $error->interval_days,
        ]);
    }
}
