<?php

use App\Models\User;
use App\Models\UserProfile;

/**
 * Le .env n'est pas versionné et une restauration du VPS l'a déjà effacé deux fois.
 * Une page qui répond 200 ne prouve donc rien : ces tests fixent ce qui doit être
 * détecté, et ce qui doit continuer de fonctionner malgré tout.
 */
test('le controle de deploiement signale une cle absente', function () {
    config(['services.mistral.api_key' => null]);

    $this->artisan('prepla:check')
        ->expectsOutputToContain('MISTRAL_API_KEY')
        ->assertExitCode(1);
});

test('le controle de deploiement signale APP_DEBUG actif en production', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.debug' => true]);

    $this->artisan('prepla:check')
        ->expectsOutputToContain('APP_DEBUG')
        ->assertExitCode(1);
});

test('sans tarif Stripe configure, la page d abonnement reste servie', function () {
    // Le repli codé en dur ramenait des identifiants d'un autre mode Stripe : la page
    // semblait marcher et le paiement tombait en 500 au moment de débiter.
    config(['services.stripe.prices.monthly' => null, 'services.stripe.prices.annual' => null]);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);

    $this->actingAs($user)->get('/settings/subscription')->assertOk();
});

test('sans tarif Stripe configure, le paiement refuse au lieu de debiter au mauvais tarif', function () {
    config(['services.stripe.prices.monthly' => null, 'services.stripe.prices.annual' => null]);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);

    $this->actingAs($user)
        ->post('/settings/subscription/checkout', ['price_id' => 'price_1TbjMVA4jGtQdWrshf7v2nQr'])
        ->assertSessionHasErrors('price_id');
});
