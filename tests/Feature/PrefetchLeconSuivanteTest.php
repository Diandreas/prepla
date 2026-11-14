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

// --- Le chemin interactif prend le meme verrou que la pre-generation ---------

function apprenantAvecObjectifCourant(): User
{
    $language = \App\Models\Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = \App\Models\Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $user = User::factory()->create();
    \App\Models\UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1',
        'native_language' => 'Français', 'onboarding_completed_at' => now(),
    ]);
    \App\Models\CurriculumSkeleton::create([
        'user_id' => $user->id, 'exam_id' => $exam->id,
        'objectives' => collect(range(0, 2))->map(fn ($i) => [
            'order' => $i, 'title' => "Objectif {$i}", 'concept' => 'grammar.verrou'.$i,
            'level' => 'A1', 'status' => $i === 0 ? 'current' : 'pending', 'priority' => 'normal',
        ])->all(),
        'current_objective_index' => 0, 'consecutive_successes' => 0, 'consecutive_failures' => 0,
    ]);

    return $user;
}

function fauxMistralCompte(): object
{
    config(['services.mistral.api_key' => 'test-key']);
    $compteur = (object) ['appels' => 0];
    \Illuminate\Support\Facades\Http::fake(function () use ($compteur) {
        $compteur->appels++;

        return \Illuminate\Support\Facades\Http::response(['choices' => [['message' => ['content' => json_encode([
            'title' => 'Le verbe sein',
            'theory_markdown' => "# Sein\n\nIch bin.",
            'key_takeaways' => ['bin'], 'common_mistakes' => [],
            'comprehension_quiz' => [[
                'type' => 'mcq', 'question' => 'Quelle forme ?', 'options' => ['bin', 'bist', 'ist', 'sind'],
                'correct_answer' => 'bin', 'explanation' => 'ich bin',
            ]],
        ])]]]]);
    });

    return $compteur;
}

beforeEach(function () {
    config(['prepla.generation.attente_verrou_lecon' => 0.3, 'prepla.generation.pause_verrou_lecon_ms' => 20]);
    // Le cache disque des lecons masquerait les appels a l'IA.
    \Illuminate\Support\Facades\File::deleteDirectory(storage_path('app/lessons/v2-progression/german_via_franais'));
});

it('pendant une pre-generation, le clic attend et relit la lecon au lieu d\'en generer une seconde', function () {
    $compteur = fauxMistralCompte();
    $user = apprenantAvecObjectifCourant();

    // La pre-generation tient le verrou et a deja ecrit la lecon.
    Cache::add(NextLessonPrefetcher::lockKey($user->id), 1, 300);
    app(NextLessonGenerator::class)->generate($user, lockHeld: true);
    expect($compteur->appels)->toBe(1);

    $lecon = app(NextLessonGenerator::class)->generate($user);

    expect($lecon)->not->toBeNull()
        ->and($compteur->appels)->toBe(1)
        ->and(\App\Models\Lesson::where('user_id', $user->id)->where('skeleton_objective_index', 0)->count())->toBe(1);
});

it('ne genere rien tant que le verrou est tenu, meme sans lecon encore ecrite', function () {
    $compteur = fauxMistralCompte();
    $user = apprenantAvecObjectifCourant();
    Cache::add(NextLessonPrefetcher::lockKey($user->id), 1, 300);

    expect(app(NextLessonGenerator::class)->generate($user))->toBeNull()
        ->and($compteur->appels)->toBe(0)
        ->and(\App\Models\Lesson::where('user_id', $user->id)->count())->toBe(0);
});

it('reprend la main quand le verrou se libere pendant l\'attente, sans regenerer', function () {
    $compteur = fauxMistralCompte();
    $user = apprenantAvecObjectifCourant();
    config(['prepla.generation.attente_verrou_lecon' => 4]);

    // La pre-generation a fini d'ecrire ; son verrou expire dans une seconde.
    Cache::add(NextLessonPrefetcher::lockKey($user->id), 1, 300);
    app(NextLessonGenerator::class)->generate($user, lockHeld: true);
    Cache::put(NextLessonPrefetcher::lockKey($user->id), 1, 1);

    $lecon = app(NextLessonGenerator::class)->generate($user);

    expect($lecon)->not->toBeNull()
        ->and($compteur->appels)->toBe(1)
        ->and(\App\Models\Lesson::where('user_id', $user->id)->count())->toBe(1)
        ->and(Cache::has(NextLessonPrefetcher::lockKey($user->id)))->toBeFalse();
});

it('pre-generation puis clic, ou clic puis pre-generation : une seule lecon et un seul appel', function () {
    $compteur = fauxMistralCompte();
    $user = apprenantAvecObjectifCourant();

    expect(app(NextLessonPrefetcher::class)->schedule($user))->toBeTrue();
    app()->terminate();
    $a = app(NextLessonGenerator::class)->generate($user);
    $b = app(NextLessonGenerator::class)->generate($user);
    expect(app(NextLessonPrefetcher::class)->schedule($user))->toBeTrue();
    app()->terminate();

    expect($a->id)->toBe($b->id)
        ->and($compteur->appels)->toBe(1)
        ->and(\App\Models\Lesson::where('user_id', $user->id)->count())->toBe(1)
        ->and(Cache::has(NextLessonPrefetcher::lockKey($user->id)))->toBeFalse();
});

it('le chemin interactif libere le verrou meme si la generation plante', function () {
    config(['services.mistral.api_key' => 'test-key']);
    \Illuminate\Support\Facades\Http::fake(fn () => throw new RuntimeException('reseau'));
    $user = apprenantAvecObjectifCourant();

    try {
        app(NextLessonGenerator::class)->generate($user);
    } catch (Throwable) {
    }

    expect(Cache::has(NextLessonPrefetcher::lockKey($user->id)))->toBeFalse();
});
