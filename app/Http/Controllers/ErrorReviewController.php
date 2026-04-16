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

        $errors = UserError::unmastered($user->id)
            ->paginate(20);

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

    // GET /errors/practice - personalized error review session
    public function practice(Request $request)
    {
        $user = $request->user();
        $skillType = $request->query('skill');

        // Pilier 3: Prioritize SM-2 due errors
        $dueErrors = $this->sm2->getDueErrors($user->id, $skillType, 5);

        if ($dueErrors->isEmpty()) {
            // Fall back to recent unmastered errors
            $dueErrors = UserError::where('user_id', $user->id)
                ->where('mastered', false)
                ->when($skillType, fn($q) => $q->where('skill_type', $skillType))
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        return Inertia::render('errors/practice', [
            'errors' => $dueErrors,
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
