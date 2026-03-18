<?php

namespace App\Http\Controllers;

use App\Models\LeaderboardEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function leaderboard(): Response
    {
        $weekly = LeaderboardEntry::where('period_type', 'weekly')
            ->where('period_key', now()->format('Y-\WW'))
            ->with('user')
            ->orderByDesc('xp')
            ->take(50)
            ->get();

        return Inertia::render('leaderboard', [
            'entries' => $weekly,
            'currentUserId' => auth()->id(),
        ]);
    }
}
