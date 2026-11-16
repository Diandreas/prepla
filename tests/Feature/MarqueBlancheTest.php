<?php

use App\Mail\WelcomeMail;
use App\Models\Exam;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Center\Marque;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Marque blanche de l'INSTANCE (config/marque.php) : le meme depot sert PrePla
 * et d'autres sites. Sans MARQUE_*, PrePla doit rester strictement identique.
 */
beforeEach(fn () => $this->withoutVite());

function marqueFsz(): void
{
    config([
        'marque.nom' => 'Fliessende Sprache Zentrum',
        'marque.nom_court' => 'FSZ',
        'marque.slogan' => 'Deutsch lernen',
        'marque.logo' => '/marques/fsz/logo.png',
        'marque.couleur_primaire' => '#C8102E',
        'marque.couleur_secondaire' => '#1A1A1A',
        'marque.langues_interface' => ['de', 'fr', 'en'],
        'marque.langue_defaut' => 'de',
        'marque.examens' => ['goethe', 'telc'],
        'marque.masquer_kids' => true,
        'marque.mascotte' => false,
    ]);
}

function apprenantMarque(): User
{
    $u = User::factory()->create();
    UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);

    return $u;
}

test('sans variable MARQUE_*, la marque reste celle de PrePla, a l identique', function () {
    expect(Marque::parDefaut())->toBe([
        'nom' => 'PrePla',
        'logo_url' => '/icons/logo.png?v=4',
        'couleurs' => ['primaire' => '#4A90E2', 'accent' => '#0B2D63'],
        'masquer_prepla' => false,
        'centre_id' => null,
        'theme' => null,
    ]);

    $this->actingAs(apprenantMarque())->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('instance.nom', 'PrePla')
            ->where('instance.personnalisee', false)
            ->where('instance.examens', [])
            ->where('marque.nom', 'PrePla'));

    $this->app['auth']->guard()->logout();
    $html = $this->get('/login')->getContent();
    expect($html)->toContain('/manifest.json?v=4')
        ->toContain('content="#0b2d63"')
        ->toContain('<meta property="og:site_name" content="PrePla">')
        ->not->toContain('id="marque-centre"');

    expect((new WelcomeMail(apprenantMarque()))->envelope()->subject)->toContain('Bienvenue sur PrePla');
});

test('une marque personnalisee arrive dans les props Inertia, le titre et les couleurs', function () {
    marqueFsz();

    $this->actingAs(apprenantMarque())->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('instance.nom', 'Fliessende Sprache Zentrum')
            ->where('instance.personnalisee', true)
            ->where('instance.langues_interface', ['de', 'fr', 'en'])
            ->where('instance.masquer_kids', true)
            ->where('instance.mascotte', false)
            ->where('marque.nom', 'Fliessende Sprache Zentrum')
            ->where('marque.masquer_prepla', true)
            ->where('marque.couleurs.primaire', '#C8102E'));

    $this->app['auth']->guard()->logout();
    $html = $this->get('/login')->getContent();
    expect($html)->toContain('Fliessende Sprache Zentrum')
        ->toContain('id="marque-centre"')
        ->toContain('--primary:#C8102E')
        ->toContain('/manifest.webmanifest')
        ->not->toContain('og:site_name" content="PrePla"')
        ->not->toContain('apple-mobile-web-app-title" content="PrePla"');
});

test('les e-mails portent le nom de l instance', function () {
    marqueFsz();

    $mail = new WelcomeMail(apprenantMarque());
    expect($mail->envelope()->subject)->toContain('Fliessende Sprache Zentrum')->not->toContain('PrePla');
    expect($mail->render())->toContain('Fliessende Sprache Zentrum')->not->toContain('PrePla');
});

test('le manifeste PWA suit la marque, et reste celui de PrePla par defaut', function () {
    $defaut = $this->get('/manifest.webmanifest')->assertOk()->json();
    expect($defaut['short_name'])->toBe('PrePla');

    marqueFsz();
    $this->get('/manifest.webmanifest')->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJsonPath('short_name', 'FSZ')
        ->assertJsonPath('theme_color', '#1A1A1A')
        ->assertJsonPath('lang', 'de')
        ->assertJsonMissingPath('screenshots');
});

test('seuls les examens de l instance sont proposes', function () {
    $allemand = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $francais = Language::create(['slug' => 'french', 'name' => 'Français', 'native_name' => 'Français', 'flag' => 'fr', 'is_active' => true]);
    $goethe = Exam::create(['language_id' => $allemand->id, 'slug' => 'goethe', 'name' => 'Goethe-Zertifikat']);
    Exam::create(['language_id' => $allemand->id, 'slug' => 'telc', 'name' => 'telc Deutsch']);
    $delf = Exam::create(['language_id' => $francais->id, 'slug' => 'delf', 'name' => 'DELF']);

    expect(Exam::proposables()->count())->toBe(3);

    marqueFsz();
    expect(Exam::proposables()->pluck('slug')->sort()->values()->all())->toBe(['goethe', 'telc']);

    $u = User::factory()->create();
    UserProfile::factory()->for($u)->create();
    $this->actingAs($u)->get(route('onboarding.exam'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('languages', 1)
            ->where('languages.0.slug', 'german')
            ->has('languages.0.exams', 2));

    // Un formulaire envoye a la main ne choisit pas un examen masque.
    $this->actingAs($u)->post(route('onboarding.exam.store'), ['exam_id' => $delf->id])->assertSessionHasErrors('exam_id');
    $this->actingAs($u)->post(route('onboarding.exam.store'), ['exam_id' => $goethe->id])->assertSessionHasNoErrors();
});

test('la commande marque:verifier affiche la marque et signale les fichiers manquants', function () {
    $this->artisan('marque:verifier')->expectsOutputToContain('PrePla')->assertSuccessful();

    marqueFsz();
    $this->artisan('marque:verifier')
        ->expectsOutputToContain('Fliessende Sprache Zentrum')
        ->expectsOutputToContain('Fichier manquant (logo) : /marques/fsz/logo.png')
        ->assertFailed();
});
