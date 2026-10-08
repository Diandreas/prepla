<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Http;

/**
 * « Ecriture guidee » : la question etait ecartee comme impossible a repondre,
 * donc la redaction d'un eleve etait notee zero quoi qu'il ecrive.
 */
test('une ecriture guidee est conservee et notee par l IA', function () {
    config(['services.mistral.api_key' => 'test-key']);
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
        'isCorrect' => true, 'accuracy' => 80, 'error_category' => null,
        'error_subcategory' => null,
        'explanation' => ['concept' => 'Bonne structure.', 'evidence' => '', 'hint' => ''],
    ])]]]])]);

    $language = Language::create(['slug' => 'english', 'name' => 'English', 'native_name' => 'English', 'flag' => 'gb']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'ielts', 'name' => 'IELTS']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'writing', 'name' => 'Writing', 'skill_type' => 'writing']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'guided-writing', 'name' => 'Ecriture guidee',
        'skill_type' => 'writing', 'component_key' => 'guided-writing',
    ]);

    $exercise = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'difficulty' => 'B1', 'content' => [],
        'questions' => [[
            'id' => 'q1', 'type' => 'guided-writing',
            'text' => 'Ecris un courriel de reclamation en 80 mots.',
        ]],
    ]);

    expect($exercise->answerableQuestions())->toHaveCount(1);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['target_exam_id' => $exam->id, 'onboarding_completed_at' => now()]);

    $resultat = app(\App\Services\ExerciseScoringService::class)->score(
        $exercise,
        ['q1' => 'Dear Sir, I am writing to complain about the delivery of my order which never arrived.'],
        $user
    );

    expect($resultat['score'])->toBe(1)
        ->and($resultat['accuracy'])->toBeGreaterThan(0)
        ->and($resultat['feedback'][0]['correct'])->toBeTrue();
});
