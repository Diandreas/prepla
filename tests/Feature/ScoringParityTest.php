<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Services\ExerciseScoringService;
use Illuminate\Support\Facades\Http;

/**
 * The session player and the offline space correct answers on the device. Both must
 * agree with the server, otherwise a learner sees "incorrect" for an answer the final
 * report counts as right. The same cases drive tests/js/scoring-parity.test.mjs.
 */
test('the server correction agrees with the client rules on the shared cases', function () {
    // Every fixture question carries an explanation: no AI call may happen here.
    Http::preventStrayRequests();

    $cases = json_decode(file_get_contents(base_path('tests/fixtures/scoring-parity.json')), true)['cases'];

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'parity-exam', 'name' => 'Parity exam']);
    $section = ExamSection::create([
        'exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading',
    ]);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);

    // Fixture ids repeat across cases; renumber so every case is scored on its own.
    $questions = [];
    $answers = [];
    foreach ($cases as $index => $case) {
        $questions[] = array_merge($case['question'], ['id' => "case-{$index}"]);
        $answers["case-{$index}"] = $case['answer'];
    }

    $exercise = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'difficulty' => 'A2', 'xp_reward' => 10, 'content' => [], 'questions' => $questions,
    ]);

    $result = app(ExerciseScoringService::class)->score($exercise, $answers);

    foreach ($cases as $index => $case) {
        $feedback = $result['feedback'][$index];
        expect($feedback['correct'])->toBe($case['correct'], "Divergence serveur/client sur : {$case['name']}");

        if (array_key_exists('accuracy', $case)) {
            expect((float) $feedback['accuracy'])->toEqualWithDelta((float) $case['accuracy'], 0.01);
        }
    }

    expect($result['score'])->toBe(count(array_filter($cases, fn (array $case) => $case['correct'])));
});
