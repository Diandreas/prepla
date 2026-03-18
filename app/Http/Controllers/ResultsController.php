<?php

namespace App\Http\Controllers;

use App\Models\UserExerciseAttempt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResultsController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $profile = $user->profile;

        // Skill breakdown
        $skillStats = UserExerciseAttempt::where('user_id', $user->id)
            ->with('exercise.exerciseType')
            ->get()
            ->groupBy(fn ($attempt) => $attempt->exercise?->exerciseType?->skill_type ?? 'unknown')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'avg_accuracy' => round($group->avg('accuracy_percent'), 1),
                'total_xp' => $group->sum('xp_earned'),
            ]);

        // Recent attempts
        $recentAttempts = UserExerciseAttempt::where('user_id', $user->id)
            ->with(['exercise.exerciseType', 'exercise.exam'])
            ->latest()
            ->take(20)
            ->get();

        return Inertia::render('results/index', [
            'profile' => $profile,
            'skillStats' => $skillStats,
            'recentAttempts' => $recentAttempts,
        ]);
    }

    public function attempts(): Response
    {
        $attempts = UserExerciseAttempt::where('user_id', auth()->id())
            ->with(['exercise.exerciseType', 'exercise.exam.language'])
            ->latest()
            ->paginate(20);

        return Inertia::render('results/attempts', [
            'attempts' => $attempts,
        ]);
    }

    public function attemptDetail(UserExerciseAttempt $attempt): Response
    {
        $attempt->load(['exercise.exerciseType.section', 'exercise.exam.language']);

        return Inertia::render('results/attempt-detail', [
            'attempt' => $attempt,
        ]);
    }
}
