<?php

/**
 * La page des structures doit s'ouvrir SANS COMPTE.
 *
 * Elle est faite pour etre envoyee a un enseignant qui verifie nos donnees en
 * face des documents de l'organisme : derriere une authentification, elle ne
 * sert a rien.
 */
test('la page des structures s ouvre sans etre connecte', function () {
    $this->get(route('structures'))
        ->assertOk()
        ->assertSee('Structures des examens')
        // Les quatre examens d'allemand y figurent.
        ->assertSee('Goethe', false)
        ->assertSee('ÖSD', false)
        ->assertSee('telc', false)
        ->assertSee('TestDaF', false);
});

test('la page se filtre par langue', function () {
    $allemand = $this->get(route('structures', ['langue' => 'german']))->assertOk();

    $allemand->assertSee('Allemand')
        // Un examen d'une autre langue ne doit plus apparaitre.
        ->assertDontSee('B2 First (FCE)', false);
});

/**
 * La distinction entre une partie verifiee et une partie reconstituee est tout
 * l'interet de la page : sans elle, on ferait valider a l'enseignant des donnees
 * que nous n'avons pas verifiees.
 */
test('la page distingue les parties verifiees des parties reconstituees', function () {
    $this->get(route('structures', ['langue' => 'german']))
        ->assertOk()
        ->assertSee('officiel')
        ->assertSee('reconstituée', false);
});

/**
 * Les parties au choix doivent se voir : c'est une correction recente, et
 * l'enseignant est le mieux place pour dire si le choix annonce est complet.
 */
test('une partie au choix est signalee comme telle', function () {
    $this->get(route('structures', ['langue' => 'english']))
        ->assertOk()
        ->assertSee('format au choix du candidat');
});
