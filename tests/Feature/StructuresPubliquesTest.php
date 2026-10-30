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

/**
 * La page doit ANCRER : montrer l'exercice reel, pas seulement le tableau.
 *
 * Un enseignant peut verifier une structure sur le papier ; il ne peut pas dire
 * si nos exercices ressemblent a l'epreuve sans les voir. Les epreuves ecrites a
 * la main sont donc depliees sous leur partie, avec leur texte, leurs questions,
 * la bonne reponse et son explication.
 */
test('un exercice ecrit a la main est montre en entier sous sa partie', function () {
    $page = $this->get(route('structures', ['langue' => 'german']))->assertOk();

    $page->assertSee("Voir l'exercice tel qu'il est servi", false)
        // Le texte allemand lui-meme, pas seulement son resume.
        ->assertSee('Mein Jahr in Leipzig', false)
        // Une affirmation du Teil 1, sa reponse et son explication.
        ->assertSee('Marisol wollte von Anfang an nach Leipzig.', false)
        ->assertSee('Réponse :', false)
        ->assertSee('Richtig', false);
});

/**
 * Une partie de comprehension orale porte le texte de l'enregistrement : sans
 * lui, l'enseignant ne peut juger ni le niveau de langue ni la longueur.
 */
test('une partie d ecoute montre le texte de son enregistrement', function () {
    $this->get(route('structures', ['langue' => 'german']))
        ->assertOk()
        ->assertSee('Enregistrement — texte lu', false);
});
