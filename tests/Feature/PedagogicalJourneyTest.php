<?php

use App\Models\{CurriculumSkeleton, Exam, ExamSection, Exercise, ExerciseType, Language, Lesson, User, UserProfile};
use App\Services\AI\ExerciseGeneratorService;
use Inertia\Testing\AssertableInertia as Assert;

test('english A1 exam opens with fifteen reviewed questions while AI is unavailable', function () {
    $language = Language::create(['slug' => 'english', 'name' => 'English', 'native_name' => 'English', 'flag' => 'en']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'reviewed-a1', 'name' => 'English']);
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now()]);
    CurriculumSkeleton::create(['user_id' => $user->id, 'exam_id' => $exam->id, 'current_objective_index' => 1, 'objectives' => [
        ['title' => 'Basics', 'concept' => 'basics', 'level' => 'A1', 'status' => 'done'],
        ['title' => 'Examen de niveau A1', 'concept' => 'level_exam.a1', 'level' => 'A1', 'status' => 'current', 'is_level_exam' => true],
    ]]);
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));
    $response = $this->actingAs($user)->get(route('level.exam', 'A1'));
    $response->assertRedirect();
    $exercises = Exercise::where('exam_id', $exam->id)->get();
    expect($exercises)->toHaveCount(3)->and($exercises->sum(fn ($exercise) => count($exercise->questions)))->toBe(15);
    $this->get($response->headers->get('Location'))->assertInertia(fn (Assert $page) => $page->component('exercises/player')->has('exercises', 3));
    $node = $exercises->first()->node_id;
    $answers = $exercises->mapWithKeys(fn ($exercise) => [$exercise->id => collect($exercise->questions)->pluck('correct_answer', 'id')->all()])->all();
    $this->post(route('exercise.submit_session', $node), [
        'exercise_ids' => [(string) $exercises->first()->id], 'answers_by_exercise' => $answers,
    ])->assertStatus(422);
    $this->post(route('exercise.submit_session', $node), [
        'exercise_ids' => $exercises->pluck('id')->map(fn ($id) => (string) $id)->all(),
        'answers_by_exercise' => $answers, 'time_spent' => '240',
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect($user->profile->fresh()->current_level)->toBe('A2');
});

test('open sentence answers require the whole correct sentence', function () {
    $question = ['type' => 'recall', 'correct_answer' => 'I am a student.', 'accepted_answers' => ["I'm a student."]];
    expect(Lesson::isQuestionCorrect($question, 'I is a student.'))->toBeFalse()
        ->and(Lesson::isQuestionCorrect($question, 'I like tea.'))->toBeFalse()
        ->and(Lesson::isQuestionCorrect($question, 'i am a student'))->toBeTrue()
        ->and(Lesson::isQuestionCorrect($question, 'I’m a student.'))->toBeTrue();
});

test('oral shortcut supplies a short beginner exercise even without an existing oral section', function () {
    $language = Language::create(['slug' => 'english', 'name' => 'English', 'native_name' => 'English', 'flag' => 'en']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'oral-path', 'name' => 'English']);
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now()]);
    $this->actingAs($user)->get(route('practice.skill', 'speaking'))->assertRedirect();
    $exercise = Exercise::where('exam_id', $exam->id)->firstOrFail();
    expect($exercise->questions)->toHaveCount(3)->and($exercise->exerciseType->skill_type)->toBe('speaking');
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));
    $this->get(route('practice.drill.type', [$exam, $exercise->exercise_type_id]))->assertRedirect(route('exercise.show', $exercise));
});

test('audio shortcut opens the target exams listening exercises', function () {
    $language = Language::create(['slug' => 'english', 'name' => 'English', 'native_name' => 'English', 'flag' => 'en']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'audio-path', 'name' => 'English']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'listening', 'name' => 'Écoute', 'skill_type' => 'listening']);
    $type = ExerciseType::create(['section_id' => $section->id, 'slug' => 'mcq', 'component_key' => 'mcq', 'name' => 'Listening', 'skill_type' => 'listening']);
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['target_exam_id' => $exam->id, 'onboarding_completed_at' => now()]);
    $availability = app(App\Services\Content\PracticeAvailability::class);
    expect($availability->forUser($user))->toBe(['speaking' => true, 'listening' => false]);
    $exercise = Exercise::create(['exam_id' => $exam->id, 'exam_section_id' => $section->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B2', 'content' => [], 'questions' => [['id' => 'q1', 'text' => 'Listen', 'type' => 'mcq', 'options' => ['A', 'B'], 'correct_answer' => 'A']]]);
    expect($availability->forUser($user)['listening'])->toBeFalse();
    $exercise->update(['difficulty' => 'A1']);
    expect($availability->forUser($user)['listening'])->toBeTrue();
    $this->actingAs($user)->get(route('practice.skill', 'listening'))->assertRedirect(route('practice.section', [$exam, $section]));
});
