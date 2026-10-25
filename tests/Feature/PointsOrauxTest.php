<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\UserExerciseAttempt;
use App\Services\AI\MistralService;

/**
 * Les 538 exercices oraux deja ecrits ne portaient aucun point a cocher. On ne
 * les regenere pas : certains portent les tentatives d'apprenants, et les
 * refaire jetterait ce travail. On ajoute les points a la consigne existante.
 */
function oralSansPoints(string $composant = 'speaking-recorder'): Exercise
{
    $langue = Language::firstOrCreate(['slug' => 'german'],
        ['name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::firstOrCreate(['slug' => 'points-oraux'], ['language_id' => $langue->id, 'name' => 'telc Deutsch']);
    $section = ExamSection::firstOrCreate(['exam_id' => $exam->id, 'slug' => 'sprechen'],
        ['name' => 'Sprechen', 'skill_type' => 'speaking', 'time_limit' => 15]);
    $type = ExerciseType::firstOrCreate(['section_id' => $section->id, 'slug' => $composant],
        ['name' => $composant, 'skill_type' => 'speaking', 'component_key' => $composant]);

    return Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'A1',
        'content' => ['title' => 'Vorstellung'],
        'questions' => [['id' => 'q1', 'type' => $composant, 'text' => 'Stell dich vor.']],
    ]);
}

function iaQuiRend(array $charge): void
{
    $faux = new class($charge) extends MistralService
    {
        public static array $charge = [];

        public function __construct(array $c = []) { self::$charge = $c; }

        public function chat(array $messages, string $model = 'mistral-small-latest'): ?string
        {
            return json_encode(self::$charge);
        }
    };
    app()->instance(MistralService::class, $faux);
}

test('les points sont ajoutes sans toucher au reste', function () {
    $ex = oralSansPoints();
    iaQuiRend(['points' => ['dit son prénom', 'dit d’où il vient', 'dit ce qu’il fait']]);

    $this->artisan('prepla:points-oraux')->assertSuccessful();

    $ex->refresh();
    expect($ex->questions[0]['expected_points'])->toHaveCount(3)
        // La consigne d'origine est intacte : on enrichit, on ne regenere pas.
        ->and($ex->questions[0]['text'])->toBe('Stell dich vor.');
});

test('un exercice qui en a deja n est pas retouche', function () {
    $ex = oralSansPoints();
    $ex->questions = [['id' => 'q1', 'type' => 'speaking-recorder', 'text' => 'x',
        'expected_points' => ['déjà là']]];
    $ex->saveQuietly();
    iaQuiRend(['points' => ['a', 'b', 'c']]);

    $this->artisan('prepla:points-oraux')->assertSuccessful();

    expect($ex->fresh()->questions[0]['expected_points'])->toBe(['déjà là']);
});

test('une liste trop courte est refusee', function () {
    // Deux points ne font pas une grille d'examinateur.
    $ex = oralSansPoints();
    iaQuiRend(['points' => ['dit son prénom', 'dit son âge']]);

    $this->artisan('prepla:points-oraux')->assertSuccessful();

    expect($ex->fresh()->questions[0]['expected_points'] ?? null)->toBeNull();
});

test('une phrase entiere n est pas un point a cocher', function () {
    $ex = oralSansPoints();
    iaQuiRend(['points' => [
        'dit son prénom',
        'il devrait idéalement parvenir à expliquer de manière détaillée les raisons pour lesquelles il apprend cette langue',
        'dit son métier',
    ]]);

    $this->artisan('prepla:points-oraux')->assertSuccessful();

    expect($ex->fresh()->questions[0]['expected_points'] ?? null)->toBeNull();
});

test('les tentatives d apprenants survivent', function () {
    $ex = oralSansPoints();
    iaQuiRend(['points' => ['a', 'b', 'c']]);
    \Illuminate\Support\Facades\DB::table('user_exercise_attempts')->insert([
        'user_id' => \App\Models\User::create(['name' => 'X', 'email' => 'po'.uniqid().'@e.test',
            'password' => 'mot-de-passe-de-test'])->id,
        'exercise_id' => $ex->id, 'answers' => json_encode([]), 'score' => 50,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->artisan('prepla:points-oraux')->assertSuccessful();

    expect(Exercise::find($ex->id))->not->toBeNull()
        ->and(UserExerciseAttempt::where('exercise_id', $ex->id)->count())->toBe(1);
});

test('le mode sec ne touche a rien', function () {
    $ex = oralSansPoints();
    iaQuiRend(['points' => ['a', 'b', 'c']]);

    $this->artisan('prepla:points-oraux', ['--sec' => true])->assertSuccessful();

    expect($ex->fresh()->questions[0]['expected_points'] ?? null)->toBeNull();
});
