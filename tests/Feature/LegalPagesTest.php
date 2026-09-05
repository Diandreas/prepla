<?php

use function Pest\Laravel\get;

it('publishes the account deletion instructions without authentication', function () {
    get(route('account-deletion'))
        ->assertOk()
        ->assertSee('Suppression du compte et des données')
        ->assertSee(route('login'))
        ->assertSee('prepla.mirlab@gmail.com');
});

it('links to the account deletion instructions from the privacy policy', function () {
    get(route('privacy'))
        ->assertOk()
        ->assertSee(route('account-deletion'));
});
