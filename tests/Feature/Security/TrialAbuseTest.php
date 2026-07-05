<?php

use App\Models\ConsumedTrial;
use App\Models\User;

test('a brand new registration grants a trial and records the email as consumed', function () {
    $response = $this->post(route('register'), [
        'name' => 'Nouvel Élève',
        'email' => 'nouvel-eleve@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'nouvel-eleve@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->profile->trial_ends_at)->not->toBeNull();
    expect(ConsumedTrial::alreadyUsedBy('nouvel-eleve@example.com'))->toBeTrue();
});

test('re-registering with an email that already consumed a trial grants no new trial', function () {
    ConsumedTrial::recordFor('recycle@example.com');

    $this->post(route('register'), [
        'name' => 'Deuxième Compte',
        'email' => 'recycle@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'recycle@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->profile->trial_ends_at)->toBeNull();
});

test('deleting an account and re-registering with the same email does not grant a second trial', function () {
    $this->post(route('register'), [
        'name' => 'Premier Passage',
        'email' => 'delete-then-retry@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'delete-then-retry@example.com')->first();
    expect($user->profile->trial_ends_at)->not->toBeNull();

    $user->profile()->delete();
    $user->delete();
    $this->app['auth']->guard()->logout();
    $this->flushSession();

    $response = $this->post(route('register'), [
        'name' => 'Deuxième Passage',
        'email' => 'delete-then-retry@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    $response->assertSessionHasNoErrors();

    $secondUser = User::where('email', 'delete-then-retry@example.com')->first();
    expect($secondUser)->not->toBeNull();
    expect($secondUser->id)->not->toBe($user->id);
    expect($secondUser->profile->trial_ends_at)->toBeNull();
});
