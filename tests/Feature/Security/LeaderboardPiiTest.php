<?php

use App\Models\LeaderboardEntry;
use App\Models\User;
use App\Models\UserProfile;

test('the leaderboard does not leak email or other PII of ranked users', function () {
    $viewer = User::factory()->create();
    UserProfile::factory()->create(['user_id' => $viewer->id, 'onboarding_completed_at' => now()]);

    $ranked = User::factory()->create(['email' => 'secret@example.com']);
    LeaderboardEntry::create([
        'user_id' => $ranked->id,
        'period_type' => 'weekly',
        'period_key' => now()->format('Y-\WW'),
        'xp' => 500,
    ]);

    $response = $this->actingAs($viewer)->get(route('leaderboard'));

    $response->assertInertia(fn ($page) => $page
        ->has('entries.0.user', fn ($user) => $user
            ->where('id', $ranked->id)
            ->where('name', $ranked->name)
            ->missing('email')
            ->missing('google_id')
            ->missing('role')
        )
    );
});
