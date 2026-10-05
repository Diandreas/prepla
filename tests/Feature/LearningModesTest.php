<?php

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserLearningProgress;
use App\Models\UserProfile;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\AI\TtsAudioGenerator;
use App\Services\ExerciseScoringService;
use App\Services\LearningJourneyService;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $language = Language::create(['slug' => 'english', 'name' => 'English', 'native_name' => 'English', 'flag' => 'en']);
    $this->exam = Exam::create(['language_id' => $language->id, 'slug' => 'modes-exam', 'name' => 'Modes']);
    $this->section = ExamSection::create(['exam_id' => $this->exam->id, 'slug' => 'practice', 'name' => 'Practice', 'skill_type' => 'grammar']);
    $this->user = User::factory()->create();
    UserProfile::factory()->for($this->user)->create(['target_exam_id' => $this->exam->id, 'onboarding_completed_at' => now(), 'learning_preferences' => ['speaking_enabled' => false, 'audio_enabled' => false]]);
    $this->node = LearningPathNode::create(['exam_id' => $this->exam->id, 'title' => 'Introduce yourself', 'sort_order' => 1, 'node_type' => 'lesson', 'level' => 'A1']);
    $this->actingAs($this->user);
});

test('un debutant ne recoit pas un examen complet meme via son URL directe', function () {
    $this->get(route('practice.simulate', $this->exam))->assertRedirect(route('practice.exam', $this->exam))->assertSessionHas('error');
    $this->get(route('practice.exam', $this->exam))->assertInertia(fn (Assert $page) => $page->component('practice/exam-dashboard')->where('learnerLevel', 'A1'));
});

test('un sujet publie A1 reste accessible mais pas un sujet B2 pour le meme examen', function () {
    $type = ExerciseType::create(['section_id' => $this->section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'grammar', 'component_key' => 'mcq']);
    $mocks = [];
    foreach (['A1', 'B2'] as $level) {
        $blueprint = App\Models\ExamBlueprint::create(['exam_id' => $this->exam->id, 'level' => $level, 'name' => $level, 'total_duration_minutes' => 15, 'scoring_config' => [], 'sections_config' => []]);
        $mock = App\Models\MockExam::create(['blueprint_id' => $blueprint->id, 'title' => $level, 'is_published' => true]);
        Exercise::create(['exam_id' => $this->exam->id, 'exam_section_id' => $this->section->id, 'exercise_type_id' => $type->id, 'mock_exam_id' => $mock->id, 'difficulty' => $level, 'content' => [], 'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'Hello?', 'options' => ['Yes', 'No'], 'correct_answer' => 'Yes']]]);
        $mocks[$level] = $mock;
    }
    $this->get(route('practice.simulate', $this->exam))->assertInertia(fn (Assert $page) => $page->component('practice/exam-simulator')->where('mockExam.id', $mocks['A1']->id)->where('totalExamsTime', 15)->has('availableMockExams', 1));
    $this->get(route('practice.simulate', $this->exam).'?mock_exam_id='.$mocks['B2']->id)->assertRedirect(route('practice.exam', $this->exam));
});

test('sans micro et audio les exercices en cache sont filtres sans nouvelle generation', function () {
    foreach (['speaking', 'listening', 'grammar', 'grammar', 'grammar'] as $index => $skill) {
        $type = ExerciseType::firstOrCreate(['section_id' => $this->section->id, 'slug' => $skill], ['name' => $skill, 'component_key' => $skill === 'speaking' ? 'speaking-recorder' : 'mcq', 'skill_type' => $skill]);
        Exercise::create(['exam_id' => $this->exam->id, 'exam_section_id' => $this->section->id, 'exercise_type_id' => $type->id, 'node_id' => $this->node->id, 'order_in_node' => $index + 1, 'difficulty' => 'A1', 'content' => [], 'questions' => [['id' => 'q1', 'text' => 'Hello?', 'type' => 'mcq', 'options' => ['Yes', 'No'], 'correct_answer' => 'Yes']]]);
    }
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));
    $this->mock(TtsAudioGenerator::class, fn ($mock) => $mock->shouldNotReceive('generateForQuestion'));
    $this->get(route('node.start', $this->node))->assertInertia(fn (Assert $page) => $page->component('exercises/player')->has('exercises', 3)->where('exercises.0.exercise_type.skill_type', 'grammar')->where('exercises.2.exercise_type.skill_type', 'grammar'));
});

test('laccueil reprend la pratique avant de proposer la lecon suivante', function () {
    CurriculumSkeleton::create(['user_id' => $this->user->id, 'exam_id' => $this->exam->id, 'current_objective_index' => 1, 'objectives' => [['title' => $this->node->title, 'concept' => 'greetings', 'status' => 'current_practice', 'level' => 'A1'], ['title' => 'Numbers', 'concept' => 'numbers', 'status' => 'current', 'level' => 'A1']]]);
    Lesson::create(['user_id' => $this->user->id, 'node_id' => $this->node->id, 'skeleton_objective_index' => 0, 'title' => 'My introduction', 'concept' => 'greetings', 'theory_markdown' => 'Hello.']);
    $action = app(LearningJourneyService::class)->nextAction($this->user);
    expect($action['kind'])->toBe('practice')->and($action['url'])->toBe(route('node.start', $this->node));
});

test('un ancien statut de lecon ouvre la pratique apres le quiz sans boucle', function () {
    $path = CurriculumSkeleton::create(['user_id' => $this->user->id, 'exam_id' => $this->exam->id, 'current_objective_index' => 0, 'objectives' => [['title' => $this->node->title, 'concept' => 'greetings', 'status' => 'current_lesson', 'level' => 'A1'], ['title' => 'Numbers', 'concept' => 'numbers', 'status' => 'pending', 'level' => 'A1']]]);
    $lesson = Lesson::create(['user_id' => $this->user->id, 'node_id' => $this->node->id, 'skeleton_objective_index' => 0, 'title' => 'Introduction', 'concept' => 'greetings', 'theory_markdown' => 'Hello.', 'comprehension_quiz' => [['question' => 'Greeting?', 'options' => ['Hello', 'Goodbye'], 'correct_answer' => 'Hello']]]);
    expect($path->fresh()->currentObjective()['status'])->toBe('current');

    $this->postJson(route('lessons.quiz', $lesson), ['answers' => ['Hello']])->assertOk()->assertJsonPath('passed', true);
    expect($path->fresh()->practiceObjectiveIndex())->toBe(0);
    expect(app(LearningJourneyService::class)->nextAction($this->user)['url'])->toBe(route('node.start', $this->node));
});

test('une seance entierement indisponible ne cree ni erreur ni progression', function () {
    $type = ExerciseType::create(['section_id' => $this->section->id, 'slug' => 'speech', 'name' => 'Speech', 'skill_type' => 'speaking', 'component_key' => 'speaking-recorder']);
    $exercise = Exercise::create(['exam_id' => $this->exam->id, 'exam_section_id' => $this->section->id, 'exercise_type_id' => $type->id, 'node_id' => $this->node->id, 'difficulty' => 'A1', 'content' => [], 'questions' => [['id' => 'q1', 'type' => 'speaking-recorder', 'prompt' => 'Introduce yourself.']]]);
    $progress = UserLearningProgress::create(['user_id' => $this->user->id, 'node_id' => $this->node->id, 'status' => 'in_progress', 'exercises_done' => 0, 'exercises_required' => 3]);
    $this->mock(ExerciseScoringService::class, fn ($mock) => $mock->shouldReceive('score')->once()->andReturn(['score' => 0, 'accuracy' => 0, 'xp' => 0, 'feedback' => [['question_id' => 'q1', 'correct' => false, 'technical_failure' => true]]]));
    $this->post(route('exercise.submit_session', $this->node), ['exercise_ids' => [$exercise->id], 'answers_by_exercise' => [$exercise->id => ['q1' => ['technical_failure' => true]]]])->assertSessionHasNoErrors();
    expect($progress->fresh()->status)->toBe('in_progress')->and(UserError::where('user_id', $this->user->id)->count())->toBe(0);
    expect($this->user->profile->fresh()->streak_current)->toBe(0);
});
