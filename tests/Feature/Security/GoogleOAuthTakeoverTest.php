<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

function fakeGoogleUser(string $email, string $googleId = 'google-123'): \Mockery\MockInterface
{
    $googleUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
    $googleUser->shouldReceive('getId')->andReturn($googleId);
    $googleUser->shouldReceive('getEmail')->andReturn($email);
    $googleUser->shouldReceive('getName')->andReturn('Test User');
    $googleUser->shouldReceive('getAvatar')->andReturn(null);

    return $googleUser;
}

test('Google sign-in does not silently take over an existing unverified-email account', function () {
    $victim = User::factory()->unverified()->create(['email' => 'victime@example.com', 'password' => bcrypt('attacker-password')]);

    Socialite::shouldReceive('driver->user')->andReturn(fakeGoogleUser('victime@example.com'));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    Auth::logout();
    $this->assertGuest();
});

test('Google sign-in links to an existing account when its email is already verified', function () {
    $user = User::factory()->create(['email' => 'verified@example.com']);

    Socialite::shouldReceive('driver->user')->andReturn(fakeGoogleUser('verified@example.com', 'google-456'));

    $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($user->fresh());
    expect($user->fresh()->google_id)->toBe('google-456');
});

test('a brand new Google sign-in creates a verified account', function () {
    Socialite::shouldReceive('driver->user')->andReturn(fakeGoogleUser('nouveau@example.com', 'google-789'));

    $this->get(route('auth.google.callback'));

    $user = User::where('email', 'nouveau@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});
