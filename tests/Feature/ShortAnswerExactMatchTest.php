<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Services\ExerciseScoringService;
use Illuminate\Support\Facades\Http;

/**
 * Un exercice est généré AVEC sa correction. Soumettre au modèle une réponse
 * identique à celle attendue coûtait du quota, ajoutait une attente, et rendait la
 * correction dépendante d'un fournisseur qui peut tomber.
 */
test('une reponse identique a celle attendue est corrigee sans appeler l IA', function () {
    // Toute requête sortante ferait échouer ce test : c'est là tout son objet.
    Http::preventStrayRequests();

    $language = Language::create(['slug' => 'english', 'name' => 'English', 'native_name' => 'English', 'flag' => 'gb']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'ielts', 'name' => 'IELTS']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'writing', 'name' => 'Writing', 'skill_type' => 'writing']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'short-answer', 'name' => 'Short answer',
        'skill_type' => 'writing', 'component_key' => 'short-answer',
    ]);

    $expected = 'She has been living in Berlin since last year';

    $exercise = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'difficulty' => 'B2', 'content' => [],
        'questions' => [[
            'id' => 'q1', 'type' => 'short-answer',
            'text' => 'Rewrite the sentence in the present perfect continuous.',
            'correct_answer' => $expected,
            'explanation' => 'Present perfect continuous : has been + participe présent.',
        ]],
    ]);

    // Même réponse, à la casse et aux espaces près : la normalisation doit suffire.
    $result = app(ExerciseScoringService::class)->score($exercise, ['q1' => '  she has been living in BERLIN since last year ']);

    expect($result['feedback'][0]['correct'])->toBeTrue()
        ->and((float) $result['feedback'][0]['accuracy'])->toBe(100.0)
        ->and($result['feedback'][0]['explanation'])->toContain('Present perfect continuous')
        ->and($result['score'])->toBe(1);
});
