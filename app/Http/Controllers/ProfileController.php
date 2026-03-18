<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function achievements(): Response
    {
        $user = auth()->user();
        $allAchievements = Achievement::all();
        $earnedIds = $user->achievements()->pluck('achievements.id')->toArray();

        return Inertia::render('profile/achievements', [
            'achievements' => $allAchievements,
            'earnedIds' => $earnedIds,
        ]);
    }

    public function stats(): Response
    {
        $user = auth()->user();
        $profile = $user->profile;

        $totalAttempts = $user->exerciseAttempts()->count();
        $totalTime = $user->exerciseAttempts()->sum('time_spent');
        $avgAccuracy = $user->exerciseAttempts()->avg('accuracy_percent');

        return Inertia::render('profile/stats', [
            'profile' => $profile,
            'totalAttempts' => $totalAttempts,
            'totalTime' => $totalTime,
            'avgAccuracy' => round($avgAccuracy ?? 0, 1),
        ]);
    }
}
