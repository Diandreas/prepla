<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;

function onboardedUser(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed_at' => now(),
    ]);

    return $user;
}

beforeEach(function () {
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

test('a user cannot view another user\'s exercise attempt result', function () {
    $owner = onboardedUser();
    $attacker = onboardedUser();

    $attempt = UserExerciseAttempt::factory()->create([
        'user_id' => $owner->id,
        'exercise_id' => $this->exercise->id,
    ]);

    $this->actingAs($attacker)
        ->get(route('exercise.result', ['attempt' => $attempt->id]))
        ->assertForbidden();
});

test('the owner can view their own exercise attempt result', function () {
    $owner = onboardedUser();

    $attempt = UserExerciseAttempt::factory()->create([
        'user_id' => $owner->id,
        'exercise_id' => $this->exercise->id,
    ]);

    $this->actingAs($owner)
        ->get(route('exercise.result', ['attempt' => $attempt->id]))
        ->assertOk();
});

test('a user cannot view another user\'s attempt detail in the results page', function () {
    $owner = onboardedUser();
    $attacker = onboardedUser();

    $attempt = UserExerciseAttempt::factory()->create([
        'user_id' => $owner->id,
        'exercise_id' => $this->exercise->id,
    ]);

    $this->actingAs($attacker)
        ->get(route('results.attempt', ['attempt' => $attempt->id]))
        ->assertForbidden();
});
