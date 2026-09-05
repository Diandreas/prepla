<?php

use App\Models\User;
use App\Models\UserProfile;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());
    UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);

    $this->get('/dashboard')->assertOk();
});
