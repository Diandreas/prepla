<?php

use App\Models\User;
use App\Models\UserProfile;

function sourceFront(string $chemin): string
{
    return file_get_contents(base_path($chemin));
}

function blocMouvementReduit(): string
{
    $css = sourceFront('resources/css/app.css');
    expect(preg_match('/^@media \(prefers-reduced-motion: reduce\) \{(.*?)\n\}/ms', $css, $m))->toBe(1);

    return $m[1];
}

it('ne fige plus les indicateurs de chargement quand le mouvement est reduit', function () {
    $bloc = blocMouvementReduit();

    // Plus de regle globale qui ecrase toutes les animations.
    expect($bloc)->not->toContain('*, *::before')
        ->and($bloc)->not->toContain('animation-duration')
        ->and($bloc)->not->toContain('animate-spin')
        ->and($bloc)->not->toContain('transition-duration');

    // Seules les animations decoratives sont coupees, par classe.
    foreach (['.coche-pop', '.answer-shake', '.confetti-piece', '.renard-entree', '.animate-bounce', '.xp-envol', '.xp-vers-compteur'] as $classe) {
        expect($bloc)->toContain($classe);
    }
});

it('joue un son court WebAudio sur la reponse, coupable et memorise', function () {
    $son = sourceFront('resources/js/lib/bip-reponse.ts');
    expect($son)->toContain('createOscillator')
        ->and($son)->toContain('localStorage.getItem')
        ->and($son)->toContain('localStorage.setItem')
        ->and(substr_count($son, 'try {'))->toBeGreaterThanOrEqual(3);

    $lecteur = sourceFront('resources/js/pages/exercises/player.tsx');
    expect($lecteur)->toContain('jouerSonReponse(isRight)')
        ->and($lecteur)->toContain('data-bouton-son')
        ->and($lecteur)->toContain('aria-pressed={sonActif}')
        ->and($lecteur)->not->toContain("playSound(isRight ? 'correct' : 'incorrect')");
});

it('fait monter le gain d XP vers le compteur de l en-tete a la fin de l exercice', function () {
    expect(sourceFront('resources/js/components/app-sidebar-header.tsx'))->toContain('data-compteur-xp');
    expect(sourceFront('resources/js/lib/xp-vers-compteur.ts'))->toContain("querySelector<HTMLElement>('[data-compteur-xp]')")
        ->toContain('prefers-reduced-motion');
    expect(sourceFront('resources/js/pages/exercise/result.tsx'))->toContain('envolerXpVersCompteur(carteXp.current');
});

it('branche les poses ecoute, serie-perdue et merci', function () {
    expect(sourceFront('resources/js/components/exercises/speaking-recorder.tsx'))->toContain('<Renard pose="ecoute"');
    expect(sourceFront('resources/js/pages/settings/subscription.tsx'))->toContain('<Renard pose="merci"');
    expect(sourceFront('resources/js/components/daily-mission.tsx'))->toContain("seriePerdue ? 'serie-perdue'");
    expect(sourceFront('resources/js/pages/dashboard.tsx'))->toContain('seriePerdue={Boolean(profile?.serie_perdue)}');
});

it('dit qu une serie est perdue seulement si elle existait', function () {
    $user = User::factory()->create();

    $perdue = UserProfile::factory()->for($user)->create(['streak_current' => 5, 'streak_last_date' => now()->subDays(3)]);
    expect($perdue->serie_perdue)->toBeTrue()->and($perdue->serie_effective)->toBe(0)
        ->and($perdue->toArray())->toHaveKey('serie_perdue', true);

    $perdue->update(['streak_last_date' => now()]);
    expect($perdue->fresh()->serie_perdue)->toBeFalse();

    $perdue->update(['streak_current' => 0, 'streak_last_date' => null]);
    expect($perdue->fresh()->serie_perdue)->toBeFalse();
});
