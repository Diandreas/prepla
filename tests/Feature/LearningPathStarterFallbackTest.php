<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\User;
use App\Models\UserLearningProgress;
use App\Models\UserProfile;
use App\Services\AI\ExerciseGeneratorService;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * La bibliothèque sans IA n'était branchée que sur la pratique libre, jamais sur le
 * parcours — or c'est le parcours que suivent les apprenants. Quand le fournisseur
 * d'IA ne répondait plus et que la base ne contenait encore aucun exercice pour cet
 * examen, le parcours s'arrêtait sur « la génération des exercices a échoué ».
 */
function pathFallbackWorld(string $level = 'A2'): array
{
    $language = Language::firstOrCreate(['slug' => 'german'], [
        'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de',
    ]);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'path-fallback', 'name' => 'Path exam']);
    $section = ExamSection::create([
        'exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading',
    ]);
    ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'QCM',
        'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id,
        'current_level' => $level,
        'onboarding_completed_at' => now(),
    ]);

    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'user_id' => $user->id, 'sort_order' => 1,
        'title' => 'Objectif', 'node_type' => 'practice', 'level' => $level,
    ]);
    UserLearningProgress::create([
        'user_id' => $user->id, 'node_id' => $node->id, 'status' => 'available',
    ]);

    return [$user, $node];
}

beforeEach(fn () => Http::preventStrayRequests());

test('le parcours sert un exercice sans IA plutot que de s arreter', function () {
    [$user, $node] = pathFallbackWorld('A2');

    // Le fournisseur est indisponible et la base ne contient aucun exercice.
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldReceive('generate')
        ->andThrow(new RuntimeException('Provider unavailable in test')));
    expect(Exercise::count())->toBe(0);

    $this->actingAs($user)
        ->get(route('node.start', $node))
        ->assertInertia(fn (Assert $page) => $page->component('exercises/player')->has('exercises', 1));

    $served = Exercise::sole();
    expect($served->content['source'])->toBe('starter-library')
        ->and($served->difficulty)->toBe('A2')
        ->and($served->is_ai_generated)->toBeFalse();
});

test('aucun exercice n est invente au dessus du niveau couvert', function () {
    // La bibliothèque s'arrête à A2 : au-dessus, mieux vaut le dire que servir du
    // contenu d'un autre niveau, qui gonflerait la moyenne servant à la montée de
    // niveau. Le manque est un manque de contenu, pas un défaut à contourner.
    [$user, $node] = pathFallbackWorld('C1');

    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldReceive('generate')
        ->andThrow(new RuntimeException('Provider unavailable in test')));

    $this->actingAs($user)
        ->get(route('node.start', $node))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error');

    expect(Exercise::count())->toBe(0);
});
