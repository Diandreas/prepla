<?php

use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Services\AI\DeepgramSttService;
use App\Services\AI\MistralEvaluationService;
use App\Services\AI\WritingCorrectorService;
use App\Services\Content\ExerciseSchemaRegistry;
use App\Services\ExerciseScoringService;

function scoringServiceForSelfEvaluatedExercise(): ExerciseScoringService
{
    return new ExerciseScoringService(
        Mockery::mock(MistralEvaluationService::class),
        Mockery::mock(WritingCorrectorService::class),
        Mockery::mock(DeepgramSttService::class),
    );
}

function selfEvaluatedExercise(string $type): Exercise
{
    $exercise = new Exercise([
        'questions' => [[
            'id' => 'q1',
            'type' => $type,
            'text' => 'Speak clearly.',
            'correct_answer' => null,
        ]],
        'difficulty' => 'B1',
        'xp_reward' => 10,
    ]);

    $exercise->setRelation('exerciseType', new ExerciseType([
        'slug' => $type,
        'component_key' => $type,
    ]));

    return $exercise;
}

test('role play keeps its turn by turn score in the final report', function () {
    $result = scoringServiceForSelfEvaluatedExercise()->score(
        selfEvaluatedExercise('role-play'),
        ['q1' => 'completed:78'],
    );

    expect($result)
        ->score->toBe(1)
        ->accuracy->toBe(100.0)
        ->and($result['feedback'][0])
        ->correct->toBeTrue()
        ->accuracy->toBe(78);
});

test('listen and repeat keeps a low fidelity score as not passed', function () {
    $result = scoringServiceForSelfEvaluatedExercise()->score(
        selfEvaluatedExercise('listen-repeat'),
        ['q1' => 'repeat:42'],
    );

    expect($result)
        ->score->toBe(0)
        ->accuracy->toBe(0.0)
        ->and($result['feedback'][0])
        ->correct->toBeFalse()
        ->accuracy->toBe(42);
});

test('new component schemas are registered and accept their generated payloads', function () {
    $payloads = [
        'listen-repeat' => [['id' => 'q1', 'audio_text' => 'Repeat this sentence.']],
        'picture-mcq' => [['id' => 'q1', 'text' => 'Choose an image.', 'correct_answer' => 'A']],
        'build-a-sentence' => [['id' => 'q1', 'text' => 'Build it.', 'correct_answer' => 'Thank you.']],
        'listen-choose-response' => [['id' => 'q1', 'audio_text' => 'How are you?', 'correct_answer' => 'A']],
        'complete-the-words' => [['id' => 'q1', 'text' => 'Com____ it.', 'correct_answers' => ['0' => 'Complete']]],
        'guided-writing' => [['id' => 'q1', 'text' => 'Rewrite the source text.']],
    ];

    foreach ($payloads as $componentKey => $questions) {
        [$ok, $error] = ExerciseSchemaRegistry::validateQuestions($componentKey, $questions);

        expect($ok, "{$componentKey}: {$error}")->toBeTrue();
    }
});
