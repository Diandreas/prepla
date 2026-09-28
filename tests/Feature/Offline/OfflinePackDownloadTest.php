<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AI\ExerciseGeneratorService;
use Illuminate\Support\Facades\Http;

function offlinePackFixture(string $language = 'german', array $components = ['mcq', 'gap-fill', 'matching', 'short-answer']): array
{
    $languageModel = Language::firstOrCreate(['slug' => $language], [
        'name' => ucfirst($language), 'native_name' => ucfirst($language), 'flag' => 'test',
    ]);
    $exam = Exam::create([
        'language_id' => $languageModel->id, 'slug' => 'offline-'.Exam::count(), 'name' => 'Offline exam',
    ]);
    $section = ExamSection::create([
        'exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading',
    ]);
    $types = [];
    foreach ($components as $component) {
        $types[$component] = ExerciseType::create([
            'section_id' => $section->id, 'slug' => $component, 'name' => $component,
            'skill_type' => 'reading', 'component_key' => $component,
        ]);
    }

    return [$exam, $section, $types];
}

function offlinePackLearner(Exam $exam, string $level = 'A2'): User
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
    // A downloadable pack must never depend on a remote provider.
    Http::preventStrayRequests();
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));
});

test('the catalogue lists only the formats prepared for the learner level', function () {
    [$exam, , $types] = offlinePackFixture();
    $user = offlinePackLearner($exam);

    $response = $this->actingAs($user)->getJson(route('offline.packs.index'));

    $response->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('level', 'A2')
        ->assertJsonCount(3, 'packs');

    expect(collect($response->json('packs'))->pluck('format')->sort()->values()->all())
        ->toBe(['gap-fill', 'matching', 'mcq'])
        ->and(collect($response->json('packs'))->pluck('questions_count')->unique()->all())->toBe([5])
        ->and($types['short-answer'])->not->toBeNull();

    // Listing prepares nothing in the database: only a download does.
    $this->assertDatabaseCount('exercises', 0);
});

test('downloading a pack returns a self-sufficient series and stays idempotent', function () {
    [$exam, , $types] = offlinePackFixture();
    $user = offlinePackLearner($exam);
    $this->actingAs($user);

    $response = $this->postJson(route('offline.packs.store', [$exam, $types['mcq']]));
    $response->assertOk();

    $exercise = Exercise::sole();
    expect($response->json('exercise_id'))->toBe($exercise->id)
        ->and($response->json('pack_id'))->toBe($exercise->catalog_key)
        ->and($response->json('level'))->toBe('A2')
        ->and($response->json('format'))->toBe('mcq')
        ->and($response->json('questions'))->toHaveCount(5);

    foreach ($response->json('questions') as $question) {
        // The device corrects on its own: answers and explanations travel with the pack.
        expect($question['correct_answer'])->toBeString()->not->toBeEmpty()
            ->and($question['explanation'])->toBeString()->not->toBeEmpty()
            ->and($question['id'])->toBeString()->not->toBeEmpty();
    }

    $this->postJson(route('offline.packs.store', [$exam, $types['mcq']]))
        ->assertOk()
        ->assertJsonPath('exercise_id', $exercise->id);
    $this->assertDatabaseCount('exercises', 1);
});

test('a format without prepared series cannot be downloaded', function () {
    [$exam, , $types] = offlinePackFixture();
    $user = offlinePackLearner($exam);

    $this->actingAs($user)
        ->postJson(route('offline.packs.store', [$exam, $types['short-answer']]))
        ->assertNotFound();

    $this->assertDatabaseCount('exercises', 0);
});

test('a learner level without prepared series receives nothing to download', function () {
    // Le catalogue couvre A1 à C2 ; 'A0' est hors cadre et ne doit rien proposer.
    [$exam, , $types] = offlinePackFixture();
    $user = offlinePackLearner($exam, 'A0');
    $this->actingAs($user);

    $this->getJson(route('offline.packs.index'))->assertOk()->assertJsonCount(0, 'packs');
    $this->postJson(route('offline.packs.store', [$exam, $types['mcq']]))->assertNotFound();
});

test('a type from another exam is refused', function () {
    [$exam] = offlinePackFixture();
    [, , $foreignTypes] = offlinePackFixture();
    $user = offlinePackLearner($exam);

    $this->actingAs($user)
        ->postJson(route('offline.packs.store', [$exam, $foreignTypes['mcq']]))
        ->assertNotFound();
});

test('packs are reserved for signed-in learners', function () {
    [$exam, , $types] = offlinePackFixture();

    $this->getJson(route('offline.packs.index'))->assertUnauthorized();
    $this->postJson(route('offline.packs.store', [$exam, $types['mcq']]))->assertUnauthorized();
});
