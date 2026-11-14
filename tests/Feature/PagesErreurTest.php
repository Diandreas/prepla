<?php

use Illuminate\Support\Facades\Route;

// Pages d'erreur sur mesure (resources/views/errors) : le bon statut, le
// renard, un bouton de retour, et la langue du navigateur.

it('affiche une page 404 sur mesure en francais pour un navigateur francais', function () {
    // Le client de test envoie « en-us » par defaut : on dit francais.
    $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
        ->get('/cette-adresse-n-existe-pas-du-tout')
        ->assertNotFound()
        ->assertSee('Cette page s’est perdue', false)
        ->assertSee('/illustrations/etats/404-perdu.webp', false)
        ->assertSee('/illustrations/prepla-guide/welcome.png', false)
        ->assertSee('Retour à l’accueil', false)
        ->assertSee('lang="fr"', false);
});

it('affiche la page 404 en anglais si le navigateur le prefere', function () {
    $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
        ->get('/cette-adresse-n-existe-pas-du-tout')
        ->assertNotFound()
        ->assertSee('This page got lost')
        ->assertSee('Back to home')
        ->assertSee('lang="en"', false)
        ->assertDontSee('Cette page s’est perdue', false);
});

it('affiche une page 500 sur mesure sans details techniques', function () {
    config(['app.debug' => false]);
    Route::get('/_test-panne', fn () => throw new RuntimeException('secret interne'));

    $this->withHeader('Accept-Language', 'fr')
        ->get('/_test-panne')
        ->assertStatus(500)
        ->assertSee('Petit souci de notre côté', false)
        ->assertSee('/illustrations/etats/500-repare.webp', false)
        ->assertSee('Retour à l’accueil', false)
        ->assertDontSee('secret interne');
});

it('affiche la page 500 en anglais si le navigateur le prefere', function () {
    config(['app.debug' => false]);
    Route::get('/_test-panne', fn () => throw new RuntimeException('boom'));

    $this->withHeader('Accept-Language', 'en')
        ->get('/_test-panne')
        ->assertStatus(500)
        ->assertSee('A small problem on our side')
        ->assertSee('Back to home');
});
