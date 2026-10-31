<?php

/**
 * Chaque page publique de l'application annonce ce qu'elle est.
 *
 * Toutes passent par le meme gabarit Inertia, qui annoncait donc le meme titre,
 * la meme description et la meme image a tout le monde : un moteur ne pouvait
 * pas distinguer l'accueil de l'inscription, et un lien partage sortait toujours
 * avec le meme apercu.
 */
test('l accueil porte son titre et sa banniere', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<title inertia>Préparation aux examens de langue avec l&#039;IA — PrePla</title>', false)
        ->assertSee('og/prepla.png', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->assertDontSee('name="robots" content="noindex', false);
});

test('l inscription porte sa propre banniere', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Créer un compte — essai gratuit 7 jours — PrePla', false)
        ->assertSee('og/inscription.png', false);
});

/**
 * Une page de connexion n'a rien a offrir a quelqu'un qui cherche : elle reste
 * accessible et partageable, mais hors index.
 */
test('la connexion est tenue hors des moteurs', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false);
});

test('le sitemap n annonce pas une page tenue hors index', function () {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee(url('/register'), false)
        ->assertDontSee('<loc>'.url('/login').'</loc>', false);
});

/**
 * Une page qui ne figure pas dans config/seo.php doit quand meme decrire le
 * site, plutot que de ne rien decrire.
 */
test('une route absente de la configuration prend la description du site', function () {
    $valeurs = \App\Support\Referencement::pour('route-qui-n-existe-pas');

    expect($valeurs['titre'])->toBe("Préparation aux examens de langue avec l'IA")
        ->and($valeurs['image'])->toEndWith('/og/prepla.png')
        ->and($valeurs['indexable'])->toBeTrue();
});

test('chaque banniere annoncee existe vraiment', function () {
    $images = collect(config('seo.pages'))
        ->pluck('image')
        ->push(config('seo.defaut.image'))
        ->filter()
        ->unique();

    foreach ($images as $image) {
        expect(file_exists(public_path($image)))->toBeTrue("Banniere annoncee mais absente : {$image}");
    }
});
