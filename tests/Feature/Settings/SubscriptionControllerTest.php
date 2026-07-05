<?php

use App\Http\Controllers\Settings\SubscriptionController;
use App\Models\User;
use App\Models\UserProfile;

test('subscription index shows free plan for a user with no subscription and no trial', function () {
    $user = User::factory()->create();
    UserProfile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('subscription.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/subscription')
        ->where('currentPlan', 'free')
        ->where('isSubscribed', false)
        ->where('onTrial', false)
    );
});

test('subscription index reflects an active onboarding trial from user_profiles', function () {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'trial_ends_at' => now()->addDays(5),
    ]);

    $response = $this->actingAs($user)->get(route('subscription.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('settings/subscription')
        ->where('currentPlan', 'premium')
        ->where('onTrial', true)
        ->where('trialDaysLeft', fn (int $days) => $days === 4 || $days === 5)
    );
});

test('subscription index reflects an active paid subscription', function () {
    $user = User::factory()->create(['stripe_id' => 'cus_test123']);
    UserProfile::factory()->create(['user_id' => $user->id]);
    $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test123',
        'stripe_status' => 'active',
        'stripe_price' => SubscriptionController::PRICE_MONTHLY,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($user)->get(route('subscription.index'));

    $response->assertInertia(fn ($page) => $page
        ->where('currentPlan', 'premium')
        ->where('isSubscribed', true)
    );
});

test('checkout rejects a price id that is not one of the known plans', function () {
    $user = User::factory()->create();
    UserProfile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('subscription.checkout'), [
        'price_id' => 'price_not_a_real_plan',
    ]);

    $response->assertSessionHasErrors('price_id');
});

test('guests cannot access the subscription page', function () {
    $this->get(route('subscription.index'))->assertRedirect(route('login'));
});
