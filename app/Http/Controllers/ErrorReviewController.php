<?php

namespace App\Http\Controllers;

use App\Models\UserError;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ErrorReviewController extends Controller
{
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

        return Inertia::render('errors/index', [
            'errors' => $errors,
            'errorsBySkill' => $errorsBySkill,
        ]);
    }

    // GET /errors/practice - personalized error review session
    public function practice(Request $request)
    {
        $user = $request->user();
        $skillType = $request->query('skill');

        $errors = UserError::where('user_id', $user->id)
            ->where('mastered', false)
            ->when($skillType, fn($q) => $q->where('skill_type', $skillType))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return Inertia::render('errors/practice', [
            'errors' => $errors,
        ]);
    }

    // POST /errors/{error}/review - mark as reviewed
    public function submitReview(Request $request, UserError $error)
    {
        $validated = $request->validate([
            'correct' => 'required|boolean',
        ]);

        $error->markReviewed($validated['correct']);

        return response()->json(['success' => true, 'mastered' => $error->mastered]);
    }
}
