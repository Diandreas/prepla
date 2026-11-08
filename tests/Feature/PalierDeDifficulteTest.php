<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\AI\MistralService;
use App\Services\Content\PalierDeDifficulte;

/**
 * La difficulte monte dans le niveau a mesure que l'apprenant reussit : une
 * apprenante trouvait les exercices A1 « trop simples », tous pareils du premier
 * au dernier jour.
 */
function palierFixture(): array
{
    $langue = Language::firstOrCreate(['slug' => 'german'], ['name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'palier', 'name' => 'Palier exam']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading']);
    $type = ExerciseType::create(['section_id' => $section->id, 'slug' => 'mcq', 'name' => 'QCM', 'skill_type' => 'reading', 'component_key' => 'mcq']);

    return [$exam, $type];
}

function palierExercice(Exam $exam, ExerciseType $type, array $contenu = []): Exercise
{
    return Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'A1',
        'content' => array_merge(['passage' => 'Text'], $contenu), 'questions' => [], 'is_ai_generated' => true,
    ]);
}

function palierTentatives(User $user, Exercise $exercice, array $precisions): void
{
    foreach (array_reverse($precisions) as $i => $p) {
        $t = UserExerciseAttempt::create([
            'user_id' => $user->id, 'exercise_id' => $exercice->id, 'answers' => [], 'score' => $p, 'accuracy_percent' => $p,
        ]);
        $t->forceFill(['created_at' => now()->subMinutes(100 - $i)])->save();
    }
}

test('le palier monte avec la reussite et redescend apres trois echecs', function () {
    expect(PalierDeDifficulte::depuis([]))->toBe(1)
        ->and(PalierDeDifficulte::depuis([100, 100]))->toBe(1)
        ->and(PalierDeDifficulte::depuis([80, 70, 75]))->toBe(2)
        ->and(PalierDeDifficulte::depuis([90, 95, 100, 90, 85, 100]))->toBe(3)
        ->and(PalierDeDifficulte::depuis([95, 100, 100]))->toBe(2)
        ->and(PalierDeDifficulte::depuis([40, 30, 20, 100, 100, 100, 100, 100]))->toBe(1)
        ->and(PalierDeDifficulte::depuis([40, 50, 60]))->toBe(1);
});

test('le palier se lit dans les derniers exercices du niveau de l apprenant', function () {
    [$exam, $type] = palierFixture();
    $user = User::factory()->create();
    palierTentatives($user, palierExercice($exam, $type), [100, 95, 90, 100, 90, 95]);

    expect(app(PalierDeDifficulte::class)->pour($user, 'A1'))->toBe(3)
        ->and(app(PalierDeDifficulte::class)->pour($user, 'B1'))->toBe(1);
});

test('a qui reussit tout, l exercice le plus exigeant passe d abord', function () {
    [$exam, $type] = palierFixture();
    $facile = palierExercice($exam, $type, ['palier' => 1]);
    $dur = palierExercice($exam, $type, ['palier' => 3]);
    // Sans palier ecrit : la reussite des autres le classe. Reussi par tous = facile.
    $reussiParTous = palierExercice($exam, $type);
    palierTentatives(User::factory()->create(), $reussiParTous, [100, 100, 95]);

    $ids = fn (int $palier) => Exercise::where('exam_id', $exam->id)->meilleurDAbord('A1', $palier)->pluck('id')->all();

    expect($ids(3)[0])->toBe($dur->id)
        ->and($ids(1)[0])->not->toBe($dur->id)
        ->and(array_slice($ids(1), 0, 2))->toContain($facile->id)
        ->and(array_slice($ids(1), 0, 2))->toContain($reussiParTous->id);
});

test('le generateur recoit la consigne du palier et la note sur l exercice', function () {
    [$exam, $type] = palierFixture();
    $prompts = [];
    $this->mock(MistralService::class, function ($mock) use (&$prompts) {
        $mock->shouldReceive('chat')->andReturnUsing(function ($messages) use (&$prompts) {
            $prompts[] = collect($messages)->pluck('content')->implode("\n");

            return json_encode([
                'content' => ['passage' => trim(str_repeat('Anna wohnt in Bonn und arbeitet im Büro. ', 13)), 'instructions' => 'Lies und antworte.'],
                'questions' => collect(['q1', 'q2', 'q3'])->map(fn ($id) => [
                    'id' => $id, 'type' => 'mcq', 'text' => "Frage {$id}?",
                    'options' => ['Bonn', 'Berlin', 'Hamburg', 'Köln'], 'correct_answer' => 'A', 'explanation' => 'Der Text nennt Bonn.',
                ])->all(),
            ]);
        });
    });

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A1', null, null, null, null, 3);

    expect($prompts[0])->toContain('DIFFICULTY WITHIN A1: UPPER A1')
        ->and($exercice->content['palier'])->toBe(3);
});

test('une lecon de fin de niveau demande d appliquer, pas de reconnaitre', function () {
    expect(PalierDeDifficulte::consigneDeLecon(0, 35, 'A1'))->toContain('start of the level')
        ->and(PalierDeDifficulte::consigneDeLecon(17, 35, 'A1'))->toContain('middle of the level')
        ->and(PalierDeDifficulte::consigneDeLecon(30, 35, 'A1'))->toContain('APPLY')
        ->and(PalierDeDifficulte::consigneDeLecon(0, 1, 'A1'))->toBe('');
});
