<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserProfile;
use App\Services\AI\ExerciseGeneratorService;

/**
 * L'exercice similaire envoyait sa bonne reponse au navigateur avant que
 * l'apprenant ne reponde. Elle reste desormais cote serveur, qui corrige.
 */
beforeEach(function () {
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $this->exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $section = ExamSection::create(['exam_id' => $this->exam->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    $this->type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'grammar', 'component_key' => 'mcq',
    ]);
    $this->section = $section;

    $this->user = User::factory()->create();
    UserProfile::factory()->for($this->user)->create([
        'target_exam_id' => $this->exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(),
    ]);
    $this->error = UserError::create([
        'user_id' => $this->user->id, 'skill_type' => 'grammar', 'question_text' => 'Ich ___ Lina.',
        'question_id' => 'q1', 'exercise_id' => null, 'user_answer' => 'bin',
        'correct_answer' => 'heiße', 'explanation' => 'Verbe heißen.', 'mastered' => false,
        'next_review_at' => now()->subDay(), 'exercise_type_slug' => 'mcq',
    ]);

    $exercise = Exercise::create([
        'exam_id' => $this->exam->id, 'exercise_type_id' => $this->type->id, 'exam_section_id' => $section->id,
        'difficulty' => 'A1', 'content' => [],
        'questions' => [[
            'id' => 'g1', 'type' => 'mcq', 'text' => 'Wie ___ du ?',
            'options' => ['heißt', 'bist'], 'correct_answer' => 'A', 'explanation' => 'Deuxieme personne.',
        ]],
    ]);
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldReceive('generate')->andReturn($exercise));
});

test('l exercice similaire ne contient pas la reponse attendue', function () {
    $response = $this->actingAs($this->user)->postJson(route('errors.similar', $this->error))->assertOk();

    expect($response->json('question'))->not->toHaveKey('correct_answer')
        ->not->toHaveKey('explanation')
        ->and($response->json('question.prompt'))->toBe('Wie ___ du ?')
        ->and($response->getContent())->not->toContain('Deuxieme personne');
});

test('le serveur corrige l exercice similaire, juste comme faux', function () {
    $this->actingAs($this->user)->postJson(route('errors.similar', $this->error))->assertOk();
    $this->postJson(route('errors.similar.check', $this->error), ['answer' => 'heißt'])
        ->assertOk()
        ->assertJson(['correct' => true, 'correct_answer' => 'A) heißt', 'explanation' => 'Deuxieme personne.']);

    $this->postJson(route('errors.similar', $this->error))->assertOk();
    $this->postJson(route('errors.similar.check', $this->error), ['answer' => 'bist'])
        ->assertOk()->assertJson(['correct' => false]);
});

test('une correction ne se rejoue pas', function () {
    $this->actingAs($this->user)->postJson(route('errors.similar', $this->error))->assertOk();
    $this->postJson(route('errors.similar.check', $this->error), ['answer' => 'bist'])->assertOk();

    $this->postJson(route('errors.similar.check', $this->error), ['answer' => 'heißt'])->assertStatus(409);
});

test('sans exercice pose, rien n est corrige', function () {
    $this->actingAs($this->user)->postJson(route('errors.similar.check', $this->error), ['answer' => 'heißt'])->assertStatus(409);
});

test('on ne corrige pas l exercice d un autre apprenant', function () {
    $autre = User::factory()->create();
    UserProfile::factory()->for($autre)->create(['target_exam_id' => $this->exam->id, 'onboarding_completed_at' => now()]);
    $this->actingAs($autre)->postJson(route('errors.similar.check', $this->error), ['answer' => 'heißt'])->assertForbidden();
});
