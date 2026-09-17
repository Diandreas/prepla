<?php

use App\Models\Assignment;
use App\Models\AssignmentItem;
use App\Models\CenterUser;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\LeaderboardEntry;
use App\Models\LearningPathNode;
use App\Models\Lesson;
use App\Models\MockExam;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use App\Services\AI\ExerciseGeneratorService;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

function accessExercise(Exam $exam, ExerciseType $type, array $attributes = []): Exercise
{
    return Exercise::create(array_merge([
        'exam_id' => $exam->id,
        'exercise_type_id' => $type->id,
        'exam_section_id' => $type->section_id,
        'difficulty' => 'A2',
        'xp_reward' => 10,
        'content' => ['title' => 'Access check'],
        'questions' => [[
            'id' => 'q1', 'type' => 'mcq', 'text' => 'Wähle A.',
            'options' => ['A) ja', 'B) nein', 'C) vielleicht', 'D) nie'],
            'correct_answer' => 'A', 'explanation' => 'A est attendu.',
        ]],
    ], $attributes));
}

function accessLearner(Exam $exam): User
{
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id,
        'current_level' => 'A2',
        'onboarding_completed_at' => now(),
    ]);

    return $user;
}

beforeEach(function () {
    Http::preventStrayRequests();

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $this->exam = Exam::create(['language_id' => $language->id, 'slug' => 'access-exam', 'name' => 'Access exam']);
    $section = ExamSection::create([
        'exam_id' => $this->exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading',
    ]);
    $this->type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    $this->center = LanguageCenter::create(['name' => 'Private centre', 'slug' => 'private-centre']);
});

test('outsiders cannot open submit or verify private centre and lesson exercises', function (string $scope) {
    $owner = accessLearner($this->exam);
    $outsider = accessLearner($this->exam);
    $exercise = accessExercise($this->exam, $this->type, match ($scope) {
        'centre' => ['center_id' => $this->center->id],
        'lesson' => ['lesson_id' => Lesson::create([
            'user_id' => $owner->id, 'title' => 'Personal lesson', 'theory_markdown' => 'Private theory',
        ])->id],
    });
    $this->actingAs($outsider);

    $this->get(route('exercise.show', $exercise))->assertForbidden();
    $this->post(route('exercise.submit', $exercise), ['answers' => ['q1' => 'A'], 'time_spent' => 5])->assertForbidden();
    $this->postJson(route('api.exercise.verify-single'), [
        'exercise_id' => $exercise->id, 'question_id' => 'q1', 'answer' => 'A',
    ])->assertForbidden();

    $this->assertDatabaseCount('user_exercise_attempts', 0);
})->with(['centre', 'lesson']);

test('a learner can open their own lesson exercise', function () {
    $owner = accessLearner($this->exam);
    $lesson = Lesson::create(['user_id' => $owner->id, 'title' => 'Personal lesson', 'theory_markdown' => 'Private theory']);
    $exercise = accessExercise($this->exam, $this->type, ['lesson_id' => $lesson->id]);

    $this->actingAs($owner)->get(route('exercise.show', $exercise))->assertOk();
});

test('centre content reaches its staff and students only once it is assigned', function () {
    $exercise = accessExercise($this->exam, $this->type, ['center_id' => $this->center->id]);
    $teacher = accessLearner($this->exam);
    CenterUser::create(['center_id' => $this->center->id, 'user_id' => $teacher->id, 'role' => 'teacher', 'joined_at' => now()]);
    $student = accessLearner($this->exam);
    CenterUser::create(['center_id' => $this->center->id, 'user_id' => $student->id, 'role' => 'student', 'joined_at' => now()]);
    $classroom = Classroom::create(['center_id' => $this->center->id, 'name' => 'A2 evening', 'invite_code' => 'ACCESS01']);
    $student->classrooms()->attach($classroom->id, ['role_in_class' => 'student']);

    $this->actingAs($teacher)->get(route('exercise.show', $exercise))->assertOk();
    $this->actingAs($student)->get(route('exercise.show', $exercise))->assertForbidden();

    $assignment = Assignment::create([
        'classroom_id' => $classroom->id, 'created_by' => $teacher->id,
        'title' => 'Homework', 'published_at' => now()->subMinute(),
    ]);
    AssignmentItem::create(['assignment_id' => $assignment->id, 'itemable_type' => Exercise::class, 'itemable_id' => $exercise->id]);

    $this->actingAs($student)->get(route('exercise.show', $exercise))->assertOk();
    $this->actingAs($student)->post(route('exercise.submit', $exercise), ['answers' => ['q1' => 'A'], 'time_spent' => 5])
        ->assertRedirect();
    expect(UserExerciseAttempt::where('user_id', $student->id)->count())->toBe(1);
});

test('replaying an identical submission records one attempt and credits XP once', function () {
    $learner = accessLearner($this->exam);
    $exercise = accessExercise($this->exam, $this->type);
    $xpBefore = $learner->profile->xp_total;
    $payload = ['answers' => ['q1' => 'A'], 'time_spent' => 12];
    $this->actingAs($learner);

    $first = $this->post(route('exercise.submit', $exercise), $payload);
    $second = $this->post(route('exercise.submit', $exercise), $payload);

    $attempt = UserExerciseAttempt::sole();
    $first->assertRedirect(route('exercise.result', ['attempt' => $attempt, 'node_completed' => 0]));
    $second->assertRedirect(route('exercise.result', ['attempt' => $attempt, 'node_completed' => 0]));
    expect($attempt->xp_earned)->toBeGreaterThan(0)
        ->and($learner->profile->fresh()->xp_total)->toBe($xpBefore + $attempt->xp_earned)
        ->and(LeaderboardEntry::sole()->xp)->toBe($attempt->xp_earned);

    $this->post(route('exercise.submit', $exercise), ['answers' => ['q1' => 'B'], 'time_spent' => 20]);
    expect(UserExerciseAttempt::count())->toBe(2);
});

test('a learning session only scores accessible exercises from its own exam', function () {
    $learner = accessLearner($this->exam);
    $node = LearningPathNode::create([
        'exam_id' => $this->exam->id, 'sort_order' => 1, 'title' => 'Practice', 'node_type' => 'practice', 'level' => 'A2',
    ]);
    $public = accessExercise($this->exam, $this->type, ['node_id' => $node->id]);
    $private = accessExercise($this->exam, $this->type, ['center_id' => $this->center->id]);
    $otherExam = Exam::create(['language_id' => $this->exam->language_id, 'slug' => 'other-exam', 'name' => 'Other exam']);
    $foreign = accessExercise($otherExam, $this->type);

    $this->actingAs($learner)->post(route('exercise.submit_session', $node), [
        'answers_by_exercise' => [
            $public->id => ['q1' => 'A'],
            $private->id => ['q1' => 'A'],
            $foreign->id => ['q1' => 'A'],
        ],
        'exercise_ids' => [$public->id, $private->id, $foreign->id],
        'time_spent' => 30,
    ])->assertRedirect(route('node.session_result', $node));

    expect(UserExerciseAttempt::pluck('exercise_id')->all())->toBe([$public->id]);
});

test('the learning session fallback never serves private centre lesson or mock content', function () {
    $learner = accessLearner($this->exam);
    $other = accessLearner($this->exam);
    $node = LearningPathNode::create([
        'exam_id' => $this->exam->id, 'sort_order' => 1, 'title' => 'Practice', 'node_type' => 'practice', 'level' => 'A2',
    ]);
    $public = accessExercise($this->exam, $this->type);
    accessExercise($this->exam, $this->type, ['center_id' => $this->center->id]);
    accessExercise($this->exam, $this->type, ['lesson_id' => Lesson::create([
        'user_id' => $other->id, 'title' => 'Someone else', 'theory_markdown' => 'Private theory',
    ])->id]);
    accessExercise($this->exam, $this->type, ['mock_exam_id' => MockExam::create([
        'blueprint_id' => ExamBlueprint::create([
            'exam_id' => $this->exam->id, 'name' => 'Blueprint', 'total_duration_minutes' => 30,
            'scoring_config' => [], 'sections_config' => [],
        ])->id,
        'title' => 'Mock only',
    ])->id]);
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldReceive('generate')
        ->andThrow(new RuntimeException('Provider unavailable in test')));

    $this->actingAs($learner)->get(route('node.start', $node))->assertInertia(fn (Assert $page) => $page
        ->component('exercises/player')
        ->has('exercises', 1)
        ->where('exercises.0.id', $public->id)
    );
});

test('an exam simulation scores exactly the served set once even when question ids repeat', function () {
    $learner = accessLearner($this->exam);
    $first = accessExercise($this->exam, $this->type);
    $second = accessExercise($this->exam, $this->type);
    $private = accessExercise($this->exam, $this->type, ['center_id' => $this->center->id]);
    $this->actingAs($learner);

    $this->get(route('practice.simulate', $this->exam))->assertInertia(fn (Assert $page) => $page
        ->component('practice/exam-simulator')
        ->where('exercises', fn ($exercises) => collect($exercises)->pluck('id')->sort()->values()->all() === [$first->id, $second->id])
    );

    $payload = [
        'answers_by_exercise' => [
            $first->id => ['q1' => 'A'],
            $second->id => ['q1' => 'B'],
            $private->id => ['q1' => 'A'],
        ],
        'time_spent' => 90,
    ];
    $this->post(route('practice.simulate.store', $this->exam), $payload)
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');
    $this->post(route('practice.simulate.store', $this->exam), $payload)
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    $attempts = UserExerciseAttempt::orderBy('exercise_id')->get();
    expect($attempts->pluck('exercise_id')->all())->toBe([$first->id, $second->id])
        ->and((float) $attempts[0]->accuracy_percent)->toBe(100.0)
        ->and((float) $attempts[1]->accuracy_percent)->toBe(0.0);
});

test('an exam simulation cannot be scored without a served set', function () {
    $learner = accessLearner($this->exam);
    accessExercise($this->exam, $this->type);

    $this->actingAs($learner)->post(route('practice.simulate.store', $this->exam), [
        'answers' => ['q1' => 'A'],
        'time_spent' => 10,
    ])->assertRedirect(route('practice.simulate', $this->exam))->assertSessionHas('error');

    $this->assertDatabaseCount('user_exercise_attempts', 0);
});
