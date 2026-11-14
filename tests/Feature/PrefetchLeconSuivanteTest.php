<?php

use App\Models\User;
use App\Services\Curriculum\NextLessonGenerator;
use App\Services\Curriculum\NextLessonPrefetcher;
use Illuminate\Support\Facades\Cache;

// Seuil de Doherty : la lecon suivante est preparee apres la reponse, une seule fois.

beforeEach(function () {
    config(['prepla.generation.prefetch_lecon_suivante' => true]);
});

it('genere la lecon suivante une seule fois meme si on la demande deux fois', function () {
    $user = User::factory()->create();
    $generator = Mockery::mock(NextLessonGenerator::class);
    $generator->shouldReceive('generate')->once()->andReturn(null);
    app()->instance(NextLessonGenerator::class, $generator);

    $prefetcher = app(NextLessonPrefetcher::class);
    expect($prefetcher->schedule($user))->toBeTrue();
    // Un second appel (double clic, autre onglet) ne planifie rien.
    expect(app(NextLessonPrefetcher::class)->schedule($user))->toBeFalse();
    expect(Cache::has(NextLessonPrefetcher::lockKey($user->id)))->toBeTrue();

    // Fin de requete : la generation tourne, puis le verrou se libere.
    app()->terminate();

    expect(Cache::has(NextLessonPrefetcher::lockKey($user->id)))->toBeFalse();
});

it('ne fait rien quand la pre-generation est coupee', function () {
    config(['prepla.generation.prefetch_lecon_suivante' => false]);
    $user = User::factory()->create();
    $generator = Mockery::mock(NextLessonGenerator::class);
    $generator->shouldNotReceive('generate');
    app()->instance(NextLessonGenerator::class, $generator);

    expect(app(NextLessonPrefetcher::class)->schedule($user))->toBeFalse();
    app()->terminate();
});

it('libere le verrou meme si la generation plante', function () {
    $user = User::factory()->create();
    $generator = Mockery::mock(NextLessonGenerator::class);
    $generator->shouldReceive('generate')->once()->andThrow(new RuntimeException('IA en panne'));
    app()->instance(NextLessonGenerator::class, $generator);

    app(NextLessonPrefetcher::class)->schedule($user);
    app()->terminate();

    expect(Cache::has(NextLessonPrefetcher::lockKey($user->id)))->toBeFalse();
});

it('respecte un verrou deja pose par une autre requete', function () {
    $user = User::factory()->create();
    Cache::add(NextLessonPrefetcher::lockKey($user->id), 1, 300);
    $generator = Mockery::mock(NextLessonGenerator::class);
    $generator->shouldNotReceive('generate');
    app()->instance(NextLessonGenerator::class, $generator);

    expect(app(NextLessonPrefetcher::class)->schedule($user))->toBeFalse();
    app()->terminate();
});
