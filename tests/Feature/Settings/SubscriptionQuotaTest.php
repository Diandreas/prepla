<?php

use App\Http\Middleware\EnsureExerciseQuota;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', EnsureExerciseQuota::class])
        ->get('/__test/quota-guarded', fn () => 'ok');

    $language = Language::create([
        'slug' => 'en', 'name' => 'English', 'native_name' => 'English', 'flag' => '🇬🇧',
    ]);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'ielts', 'name' => 'IELTS']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    $this->exercise = Exercise::create([
        'exercise_type_id' => $type->id, 'exam_id' => $exam->id,
        'content' => [], 'questions' => [], 'difficulty' => 'B1',
    ]);
});

function makeAttempt(User $user, int $count = 1, array $overrides = []): void
{
    UserExerciseAttempt::factory()->count($count)->create(array_merge([
        'user_id' => $user->id,
        'exercise_id' => test()->exercise->id,
    ], $overrides));
}

test('free user under the daily limit passes through', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/__test/quota-guarded')
        ->assertOk()
        ->assertSee('ok');
});

test('free user is redirected to subscription page once the daily limit is reached', function () {
    $user = User::factory()->create();

    makeAttempt($user, EnsureExerciseQuota::DAILY_FREE_LIMIT);

    $this->actingAs($user)
        ->get('/__test/quota-guarded')
        ->assertRedirect(route('subscription.index'))
        ->assertSessionHas('error');
});

test('attempts from a previous day do not count toward today\'s limit', function () {
    $user = User::factory()->create();

    makeAttempt($user, EnsureExerciseQuota::DAILY_FREE_LIMIT, ['created_at' => now()->subDay()]);

    $this->actingAs($user)
        ->get('/__test/quota-guarded')
        ->assertOk();
});

test('premium (subscribed) user is never blocked by the quota', function () {
    $user = User::factory()->create(['stripe_id' => 'cus_test123']);
    $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test123',
        'stripe_status' => 'active',
        'stripe_price' => 'price_1TbjMVA4jGtQdWrshf7v2nQr',
        'quantity' => 1,
    ]);

    makeAttempt($user, EnsureExerciseQuota::DAILY_FREE_LIMIT + 5);

    $this->actingAs($user)
        ->get('/__test/quota-guarded')
        ->assertOk();
});

test('user still inside their onboarding trial is never blocked by the quota', function () {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'trial_ends_at' => now()->addDays(3),
    ]);

    makeAttempt($user, EnsureExerciseQuota::DAILY_FREE_LIMIT + 5);

    $this->actingAs($user)
        ->get('/__test/quota-guarded')
        ->assertOk();
});
