<?php

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\LearningPathNode;
use App\Models\Lesson;
use App\Models\MockExam;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\Content\ExerciseSchemaRegistry;
use App\Services\Content\StarterPracticeLibrary;
use App\Services\ExerciseScoringService;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

function starterPracticeFixture(string $language = 'german', string $component = 'mcq', string $skill = 'reading'): array
{
    $languageModel = Language::firstOrCreate(['slug' => $language], [
        'name' => ucfirst($language), 'native_name' => ucfirst($language), 'flag' => 'test',
    ]);
    $exam = Exam::create([
        'language_id' => $languageModel->id,
        'slug' => 'starter-test-'.Exam::count(),
        'name' => 'Practice test',
    ]);
    $section = ExamSection::create([
        'exam_id' => $exam->id, 'slug' => $skill, 'name' => ucfirst($skill), 'skill_type' => $skill,
    ]);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => $component, 'name' => $component,
        'skill_type' => $skill, 'component_key' => $component,
    ]);

    return [$exam, $section, $type];
}

function starterPracticeUser(Exam $exam, string $level = 'A2'): User
{
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id,
        'current_level' => $level,
        'onboarding_completed_at' => now(),
    ]);

    return $user;
}

beforeEach(function () {
    // These tests must never consume a real provider quota, even on regression.
    Http::preventStrayRequests();
    Http::fake();
});

afterEach(function () {
    Http::assertNothingSent();
});

$starterCases = [];
foreach (['english', 'french', 'german'] as $language) {
    foreach (['A1', 'A2'] as $level) {
        foreach (['mcq', 'gap-fill', 'matching'] as $component) {
            $starterCases["$language $level $component"] = [$language, $level, $component];
        }
    }
}

test('starter templates contain five valid questions and score locally', function (string $language, string $level, string $component) {
    [$exam, , $type] = starterPracticeFixture($language, $component);
    $library = app(StarterPracticeLibrary::class);
    $template = $library->template($exam, $type, $level);

    expect($template)->toBeArray()
        ->and($template['title'])->toBeString()->not->toBeEmpty()
        ->and($template['description'])->toBeString()->not->toBeEmpty()
        ->and($template['content']['instructions'])->toBeString()->not->toBeEmpty()
        ->and($template['questions'])->toHaveCount(5);

    [$valid, $error, $questions] = ExerciseSchemaRegistry::validateQuestions($component, $template['questions']);
    expect($valid)->toBeTrue($error ?? '')
        ->and(array_unique(array_column($questions, 'id')))->toHaveCount(5);

    foreach ($questions as $question) {
        expect($question['type'])->toBe($component)
            ->and(trim($question['explanation']))->not->toBeEmpty();

        if ($component === 'gap-fill') {
            expect(substr_count($question['text'], '___'))->toBe(1)
                ->and(preg_match('/\s/u', trim($question['correct_answer'])))->toBe(0);
        } else {
            expect($question['options'])->toHaveCount(4)
                ->and(array_unique($question['options']))->toHaveCount(4)
                ->and($question['correct_answer'])->toBeIn(['A', 'B', 'C', 'D']);
        }
    }

    $exercise = $library->ensure($exam, $type, $level);
    expect($exercise->difficulty)->toBe($level)
        ->and($exercise->is_ai_generated)->toBeFalse()
        ->and($exercise->content['source'])->toBe('starter-library')
        ->and($exercise->center_id)->toBeNull()
        ->and($exercise->lesson_id)->toBeNull()
        ->and($exercise->node_id)->toBeNull()
        ->and($exercise->mock_exam_id)->toBeNull();

    $rightAnswers = [];
    $wrongAnswers = [];
    foreach ($exercise->questions as $question) {
        $rightAnswers[$question['id']] = $question['correct_answer'];
        $wrongAnswers[$question['id']] = $component === 'gap-fill'
            ? 'definitely-incorrect'
            : ($question['correct_answer'] === 'A' ? 'B' : 'A');
    }

    $scorer = app(ExerciseScoringService::class);
    $correct = $scorer->score($exercise, $rightAnswers);
    $incorrect = $scorer->score($exercise, $wrongAnswers);
    expect($correct['score'])->toBe(5)
        ->and((float) $correct['accuracy'])->toBe(100.0)
        ->and($correct['xp'])->toBe(10)
        ->and($incorrect['score'])->toBe(0)
        ->and((float) $incorrect['accuracy'])->toBe(0.0)
        ->and($incorrect['xp'])->toBe(0);
    foreach ($incorrect['feedback'] as $index => $feedback) {
        expect($feedback['correct'])->toBeFalse()
            ->and($feedback['explanation'])->toBe($questions[$index]['explanation']);
    }
})->with($starterCases);

test('ensuring a starter is idempotent and does not overwrite an existing catalogue exercise', function () {
    [$exam, , $type] = starterPracticeFixture();
    $library = app(StarterPracticeLibrary::class);
    $first = $library->ensure($exam, $type, 'A2');
    $first->update(['content' => array_merge($first->content, ['review_note' => 'Keep this annotation'])]);

    $again = app(StarterPracticeLibrary::class)->ensure($exam->fresh(), $type->fresh(), 'A2');
    expect($again->id)->toBe($first->id)
        ->and($again->content['review_note'])->toBe('Keep this annotation');
    $this->assertDatabaseCount('exercises', 1);

    $beginner = $library->ensure($exam, $type, 'A1');
    expect($beginner->id)->not->toBe($first->id)
        ->and($beginner->catalog_key)->not->toBe($first->catalog_key)
        ->and(array_intersect(array_column($first->questions, 'id'), array_column($beginner->questions, 'id')))->toBeEmpty();
    $this->assertDatabaseCount('exercises', 2);
});

test('the library refuses unsupported levels languages skills types and foreign exam types', function () {
    [$exam, , $type] = starterPracticeFixture();
    [$audioExam, , $audioType] = starterPracticeFixture('german', 'mcq', 'listening');
    [$otherLanguage, , $otherLanguageType] = starterPracticeFixture('spanish');
    [$unsupportedExam, , $unsupportedType] = starterPracticeFixture('german', 'short-answer');
    $library = app(StarterPracticeLibrary::class);

    expect($library->ensure($exam, $type, 'B1'))->toBeNull()
        ->and($library->ensure($audioExam, $audioType, 'A2'))->toBeNull()
        ->and($library->ensure($otherLanguage, $otherLanguageType, 'A2'))->toBeNull()
        ->and($library->ensure($unsupportedExam, $unsupportedType, 'A2'))->toBeNull()
        ->and($library->ensure($exam, $audioType, 'A2'))->toBeNull();
    $this->assertDatabaseCount('exercises', 0);
});

test('the drill route opens an A2 starter without calling the generator', function (string $component) {
    [$exam, , $type] = starterPracticeFixture('german', $component);
    $this->actingAs(starterPracticeUser($exam));
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));

    $response = $this->get(route('practice.drill.type', [$exam, $type]));
    $exercise = Exercise::sole();
    $response->assertRedirect(route('exercise.show', $exercise));
    expect($exercise->difficulty)->toBe('A2')
        ->and($exercise->content['source'])->toBe('starter-library');

    $this->get(route('practice.drill.type', [$exam, $type]))
        ->assertRedirect(route('exercise.show', $exercise));
    $this->assertDatabaseCount('exercises', 1);
})->with(['mcq', 'gap-fill', 'matching']);

test('practice does not select private lesson node mock or wrong-level content', function (string $scope) {
    [$exam, , $type] = starterPracticeFixture();
    $user = starterPracticeUser($exam);
    $this->actingAs($user);
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));

    $attributes = match ($scope) {
        'center' => ['center_id' => LanguageCenter::create(['name' => 'Private centre', 'slug' => 'private-centre'])->id],
        'lesson' => ['lesson_id' => Lesson::create([
            'user_id' => $user->id, 'title' => 'Personal lesson', 'theory_markdown' => 'Private theory',
        ])->id],
        'node' => ['node_id' => LearningPathNode::create([
            'exam_id' => $exam->id, 'sort_order' => 1, 'title' => 'Learning path', 'node_type' => 'practice',
        ])->id],
        'mock' => ['mock_exam_id' => MockExam::create([
            'blueprint_id' => ExamBlueprint::create([
                'exam_id' => $exam->id, 'name' => 'Test blueprint', 'total_duration_minutes' => 30,
                'scoring_config' => [], 'sections_config' => [],
            ])->id,
            'title' => 'Mock-only exercise',
        ])->id],
        'level' => ['difficulty' => 'B2'],
    };
    $unrelated = Exercise::create(array_merge([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id,
        'difficulty' => 'A2', 'content' => ['title' => 'Must not be selected'], 'questions' => [],
    ], $attributes));

    $response = $this->get(route('practice.drill.type', [$exam, $type]));
    $starter = Exercise::whereNotNull('catalog_key')->sole();
    $response->assertRedirect(route('exercise.show', $starter));
    expect($starter->id)->not->toBe($unrelated->id);
    $this->assertDatabaseHas('exercises', ['id' => $unrelated->id, 'catalog_key' => null]);
    $this->assertDatabaseCount('exercises', 2);
})->with(['center', 'lesson', 'node', 'mock', 'level']);

test('practice reuses a public exercise at the exact level before creating a starter', function () {
    [$exam, , $type] = starterPracticeFixture();
    $this->actingAs(starterPracticeUser($exam));
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));
    $existing = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id,
        'difficulty' => 'A2', 'content' => [], 'questions' => [],
    ]);

    $this->get(route('practice.drill.type', [$exam, $type]))
        ->assertRedirect(route('exercise.show', $existing));
    $this->assertDatabaseCount('exercises', 1);
    expect($existing->fresh()->catalog_key)->toBeNull();
});

test('an unsupported learner level never silently receives a lower-level starter', function () {
    [$exam, , $type] = starterPracticeFixture();
    app(StarterPracticeLibrary::class)->ensure($exam, $type, 'A2');
    $this->actingAs(starterPracticeUser($exam, 'B1'));
    $this->mock(ExerciseGeneratorService::class, function ($mock) use ($exam, $type) {
        $mock->shouldReceive('generate')->once()
            ->withArgs(fn ($requestedType, $requestedExam, $level) => $requestedType->id === $type->id
                && $requestedExam->id === $exam->id && $level === 'B1')
            ->andThrow(new RuntimeException('Provider unavailable in test'));
    });

    $this->get(route('practice.drill.type', [$exam, $type]))
        ->assertRedirect(route('practice.exam', $exam))
        ->assertSessionHas('error');
    $this->assertDatabaseCount('exercises', 1);
});

test('general starters are not presented as mock exam content', function () {
    [$exam, , $type] = starterPracticeFixture();
    app(StarterPracticeLibrary::class)->ensure($exam, $type, 'A2');
    $this->actingAs(starterPracticeUser($exam));

    $this->get(route('practice.simulate', $exam))->assertInertia(fn (Assert $page) => $page
        ->component('practice/exam-simulator')
        ->has('exercises', 0)
        ->where('mockExam', null)
    );
});

test('a shared renderer never substitutes a different pedagogical format', function () {
    [$exam, , $type] = starterPracticeFixture('german', 'matching');
    $type->update(['slug' => 'matching-headings']);

    expect(app(StarterPracticeLibrary::class)->ensure($exam, $type, 'A2'))->toBeNull();
    $this->assertDatabaseCount('exercises', 0);
});

test('practice routes reject exercise types and sections from a different exam', function () {
    [$exam] = starterPracticeFixture();
    [, $foreignSection, $foreignType] = starterPracticeFixture();
    $this->actingAs(starterPracticeUser($exam));
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));

    $this->get(route('practice.drill.type', [$exam, $foreignType]))->assertNotFound();
    $this->get(route('practice.section', [$exam, $foreignSection]))->assertNotFound();
    $this->assertDatabaseCount('exercises', 0);
});

test('section cards accurately expose starter availability at the learner level', function () {
    [$exam, $section] = starterPracticeFixture();
    foreach (['gap-fill', 'matching', 'short-answer'] as $component) {
        ExerciseType::create([
            'section_id' => $section->id, 'slug' => $component, 'name' => $component,
            'skill_type' => 'reading', 'component_key' => $component,
        ]);
    }
    $user = starterPracticeUser($exam);
    $this->actingAs($user);

    $this->get(route('practice.section', [$exam, $section]))->assertInertia(fn (Assert $page) => $page
        ->component('practice/section-drills')
        ->has('exerciseTypes', 4)
        ->where('exerciseTypes', fn ($types) => collect($types)->pluck('starter_available', 'component_key')->sortKeys()->all() === collect([
            'mcq' => true, 'gap-fill' => true, 'matching' => true, 'short-answer' => false,
        ])->sortKeys()->all())
    );

    $user->profile->update(['current_level' => 'B1']);
    $this->get(route('practice.section', [$exam, $section]))->assertInertia(fn (Assert $page) => $page
        ->where('exerciseTypes', fn ($types) => collect($types)->every(fn ($type) => $type['starter_available'] === false))
    );
});

test('a starter can be corrected submitted and recorded without AI', function () {
    [$exam, , $type] = starterPracticeFixture();
    $user = starterPracticeUser($exam);
    $this->actingAs($user);
    $exercise = app(StarterPracticeLibrary::class)->ensure($exam, $type, 'A2');
    $question = $exercise->questions[0];

    $this->postJson(route('api.exercise.verify-single'), [
        'exercise_id' => $exercise->id,
        'question_id' => $question['id'],
        'answer' => $question['correct_answer'],
    ])->assertOk()->assertJson(['correct' => true, 'accuracy' => 100]);

    $response = $this->post(route('exercise.submit', $exercise), [
        'answers' => array_column($exercise->questions, 'correct_answer', 'id'),
        'time_spent' => 120,
    ]);
    $attempt = UserExerciseAttempt::sole();
    $response->assertRedirect(route('exercise.result', ['attempt' => $attempt, 'node_completed' => 0]));
    expect($attempt->exercise_id)->toBe($exercise->id)
        ->and($attempt->user_id)->toBe($user->id)
        ->and((float) $attempt->score)->toBe(5.0)
        ->and((float) $attempt->accuracy_percent)->toBe(100.0)
        ->and($attempt->xp_earned)->toBe(10)
        ->and($user->profile->fresh()->xp_total)->toBe(10);
});
