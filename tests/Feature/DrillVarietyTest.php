<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\Content\StarterPracticeLibrary;
use Illuminate\Support\Facades\Http;

/**
 * « Autre exercice » doit donner un autre exercice.
 *
 * L'ancien ordre servait d'abord n'importe quel exercice existant du bon niveau :
 * dès qu'il y en avait un, il revenait à chaque clic. Le vivier ne grandissait plus,
 * la génération n'était plus jamais appelée, et l'apprenant refaisait les mêmes cinq
 * questions indéfiniment.
 */
function drillWorld(string $level = 'A2'): array
{
    $language = Language::firstOrCreate(['slug' => 'german'], [
        'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de',
    ]);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'drill-variety', 'name' => 'Drill exam']);
    $section = ExamSection::create([
        'exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading',
    ]);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'mcq',
        'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id,
        'current_level' => $level,
        'onboarding_completed_at' => now(),
    ]);

    return [$exam, $type, $user];
}

function drillExercise(Exam $exam, ExerciseType $type, string $level = 'A2'): Exercise
{
    return Exercise::create([
        'exam_id' => $exam->id,
        'exercise_type_id' => $type->id,
        'exam_section_id' => $type->section_id,
        'difficulty' => $level,
        'content' => ['instructions' => 'Test'],
        'questions' => [[
            'id' => 'q1', 'type' => 'mcq', 'text' => 'Wo?',
            'options' => ['Bonn', 'Berlin', 'Köln', 'Hamburg'],
            'correct_answer' => 'A', 'explanation' => 'Bonn.',
        ]],
    ]);
}

function drillDone(User $user, Exercise $exercise): void
{
    UserExerciseAttempt::create([
        'user_id' => $user->id, 'exercise_id' => $exercise->id,
        'answers' => [], 'score' => 1, 'accuracy_percent' => 100,
    ]);
}

beforeEach(fn () => Http::preventStrayRequests());

test('un exercice jamais fait est prefere, sans appeler l IA', function () {
    [$exam, $type, $user] = drillWorld();
    $unseen = drillExercise($exam, $type);

    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));

    $this->actingAs($user)
        ->get(route('practice.drill.type', [$exam, $type]))
        ->assertRedirect(route('exercise.show', $unseen));
});

test('la serie preparee est servie avant tout appel a l IA', function () {
    // Elle est gratuite et immédiate : la consommer avant de générer préserve le
    // quota du fournisseur, qui est la ressource rare.
    [$exam, $type, $user] = drillWorld();

    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));

    $this->actingAs($user)->get(route('practice.drill.type', [$exam, $type]))->assertRedirect();

    $starter = Exercise::sole();
    expect($starter->content['source'])->toBe('starter-library')
        ->and($starter->difficulty)->toBe('A2');
});

test('une fois la serie preparee faite, le clic suivant genere du neuf', function () {
    [$exam, $type, $user] = drillWorld();
    $starter = app(StarterPracticeLibrary::class)->ensure($exam, $type, 'A2');
    drillDone($user, $starter);

    // L'exercice neuf ne doit exister qu'à l'issue de la génération : le créer avant
    // en ferait un inédit déjà en base, servi sans qu'aucun appel n'ait lieu.
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldReceive('generate')
        ->once()->andReturnUsing(fn () => drillExercise($exam, $type)));

    $response = $this->actingAs($user)->get(route('practice.drill.type', [$exam, $type]));

    $fresh = Exercise::whereNull('catalog_key')->sole();
    $response->assertRedirect(route('exercise.show', $fresh));
});

test('en dernier ressort, un exercice deja fait vaut mieux qu un ecran d echec', function () {
    // Niveau hors du catalogue : plus aucun filet préparé, et l'IA ne répond pas.
    [$exam, $type, $user] = drillWorld('A0');
    $done = drillExercise($exam, $type, 'A0');
    drillDone($user, $done);

    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldReceive('generate')
        ->andThrow(new RuntimeException('Provider unavailable in test')));

    $this->actingAs($user)
        ->get(route('practice.drill.type', [$exam, $type]))
        ->assertRedirect(route('exercise.show', $done));

    expect(app(StarterPracticeLibrary::class)->ensure($exam, $type, 'A0'))->toBeNull();
});

test('sans rien nulle part, l apprenant recoit un message clair', function () {
    [$exam, $type, $user] = drillWorld('A0');

    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldReceive('generate')
        ->andThrow(new RuntimeException('Provider unavailable in test')));

    $this->actingAs($user)
        ->get(route('practice.drill.type', [$exam, $type]))
        ->assertRedirect(route('practice.exam', $exam))
        ->assertSessionHas('error');
});
