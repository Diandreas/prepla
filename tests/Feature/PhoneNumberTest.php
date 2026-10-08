<?php

use App\Models\User;
use App\Models\UserProfile;

/**
 * Sans numéro de téléphone, aucun apprenant n'est joignable pour un retour. Le
 * formulaire d'inscription le demande ; une entrée par Google ne demande qu'un clic,
 * on pose donc la question une fois ensuite — et une seule.
 */
test('l inscription enregistre le numero de telephone', function () {
    $this->post(route('register'), [
        'name' => 'Awa Diallo',
        'email' => 'awa@exemple.test',
        'phone' => '+237 6 55 44 33 22',
        'password' => 'motdepasse-solide',
        'password_confirmation' => 'motdepasse-solide',
    ])->assertRedirect(route('onboarding.native-language'));

    $user = User::where('email', 'awa@exemple.test')->sole();

    expect($user->phone)->toBe('+237 6 55 44 33 22')
        // La question est reglee : on ne la reposera pas en fenetre.
        ->and($user->phone_prompted_at)->not->toBeNull();
});

test('l inscription refuse un numero absent', function () {
    $this->post(route('register'), [
        'name' => 'Awa Diallo',
        'email' => 'awa2@exemple.test',
        'password' => 'motdepasse-solide',
        'password_confirmation' => 'motdepasse-solide',
    ])->assertSessionHasErrors('phone');

    expect(User::where('email', 'awa2@exemple.test')->exists())->toBeFalse();
});

test('la question est posee a qui est entre sans numero, puis enregistree', function () {
    // Un compte cree par Google : ni numero, ni question posee.
    $user = User::factory()->create(['phone' => null, 'phone_prompted_at' => null]);
    UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);

    $this->actingAs($user)->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('needsPhone', true));

    $this->post(route('phone.store'), ['phone' => '06 12 34 56 78'])->assertRedirect();

    expect($user->fresh()->phone)->toBe('06 12 34 56 78');

    // Numero connu : la fenetre ne revient pas.
    $this->get('/dashboard')->assertInertia(fn ($page) => $page->where('needsPhone', false));
});

test('decliner la question ne la repose plus', function () {
    $user = User::factory()->create(['phone' => null, 'phone_prompted_at' => null]);
    UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);

    $this->actingAs($user)->post(route('phone.dismiss'))->assertRedirect();

    expect($user->fresh()->phone)->toBeNull()
        ->and($user->fresh()->phone_prompted_at)->not->toBeNull();

    // Rien n'est bloque, et la question ne revient pas.
    $this->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('needsPhone', false));
});
