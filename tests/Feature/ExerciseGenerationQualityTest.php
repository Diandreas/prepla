<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\AI\MistralService;

/**
 * La validation écarte les questions structurellement cassées (options en double,
 * lettre de bonne réponse hors des options, texte-repère anglais). Restait qu'une
 * seule rescapée sur trois suffisait : l'apprenant recevait une série amputée sans
 * rien en savoir, et le second essai prévu pour ce cas n'était jamais tenté.
 */
function generationFixture(): array
{
    $language = Language::firstOrCreate(['slug' => 'german'], [
        'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de',
    ]);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'gen-quality', 'name' => 'Gen exam']);
    $section = ExamSection::create([
        'exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading',
    ]);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'QCM',
        'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);

    return [$exam, $type];
}

function mcqPayload(array $questions): string
{
    return json_encode([
        'content' => ['passage' => 'Ein kurzer Text.', 'instructions' => 'Lies und antworte.'],
        'questions' => $questions,
    ]);
}

function goodQuestion(string $id): array
{
    return [
        'id' => $id, 'type' => 'mcq', 'text' => "Frage {$id}?",
        'options' => ['Bonn', 'Berlin', 'Hamburg', 'Köln'],
        'correct_answer' => 'A', 'explanation' => 'Der Text nennt Bonn.',
    ];
}

/** Lettre de bonne réponse hors des options : la question est insoluble. */
function brokenQuestion(string $id): array
{
    return [
        'id' => $id, 'type' => 'mcq', 'text' => "Frage {$id}?",
        'options' => ['Bonn', 'Berlin'],
        'correct_answer' => 'D', 'explanation' => 'Erklärung.',
    ];
}

test('une serie amputee declenche un second essai au lieu d etre servie telle quelle', function () {
    [$exam, $type] = generationFixture();

    $this->mock(MistralService::class, function ($mock) {
        // Premier essai : deux questions sur trois sont insolubles.
        $mock->shouldReceive('chat')->once()->andReturn(mcqPayload([
            goodQuestion('q1'), brokenQuestion('q2'), brokenQuestion('q3'),
        ]));
        // Second essai : série complète.
        $mock->shouldReceive('chat')->once()->andReturn(mcqPayload([
            goodQuestion('q1'), goodQuestion('q2'), goodQuestion('q3'),
        ]));
    });

    $exercise = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2');

    expect($exercise->questions)->toHaveCount(3);
});

test('au dernier essai, une seule question vaut mieux que rien', function () {
    [$exam, $type] = generationFixture();

    $this->mock(MistralService::class, function ($mock) {
        $mock->shouldReceive('chat')->twice()->andReturn(mcqPayload([
            goodQuestion('q1'), brokenQuestion('q2'), brokenQuestion('q3'),
        ]));
    });

    $exercise = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2');

    expect($exercise->questions)->toHaveCount(1);
});

test('une serie deja valide ne coute pas un appel de plus', function () {
    [$exam, $type] = generationFixture();

    $this->mock(MistralService::class, function ($mock) {
        $mock->shouldReceive('chat')->once()->andReturn(mcqPayload([
            goodQuestion('q1'), goodQuestion('q2'), goodQuestion('q3'),
        ]));
    });

    $exercise = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2');

    expect($exercise->questions)->toHaveCount(3);
});
