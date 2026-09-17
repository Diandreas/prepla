<?php

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserLearningProgress;
use App\Models\UserProfile;
use App\Services\AI\RoadmapGeneratorService;

test('changing the target exam resets personal progress and keeps the shared path nodes', function () {
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $currentExam = Exam::create(['language_id' => $language->id, 'slug' => 'exam-a', 'name' => 'Exam A']);
    $newExam = Exam::create(['language_id' => $language->id, 'slug' => 'exam-b', 'name' => 'Exam B']);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $currentExam->id,
        'current_level' => 'A2',
        'onboarding_completed_at' => now(),
    ]);

    $node = LearningPathNode::create([
        'exam_id' => $currentExam->id, 'sort_order' => 1,
        'title' => 'Shared node', 'node_type' => 'practice', 'level' => 'A2',
    ]);
    UserLearningProgress::create([
        'user_id' => $user->id, 'node_id' => $node->id,
        'status' => 'completed', 'exercises_required' => 3, 'exercises_done' => 3,
    ]);
    CurriculumSkeleton::create([
        'user_id' => $user->id, 'exam_id' => $currentExam->id,
        'objectives' => [], 'current_objective_index' => 0,
    ]);
    Lesson::create(['user_id' => $user->id, 'title' => 'Old lesson', 'theory_markdown' => 'Old theory']);

    $this->mock(RoadmapGeneratorService::class, fn ($mock) => $mock->shouldReceive('generateForUser')->once());

    $this->actingAs($user)
        ->patch(route('profile.update_learning'), ['target_exam_id' => $newExam->id])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($user->profile->fresh()->target_exam_id)->toBe($newExam->id)
        ->and(CurriculumSkeleton::count())->toBe(0)
        ->and(Lesson::count())->toBe(0)
        ->and(UserLearningProgress::count())->toBe(0)
        // Path nodes belong to their exam, not to one learner.
        ->and(LearningPathNode::find($node->id))->not->toBeNull();
});

test('updating other learning settings leaves progress untouched', function () {
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'exam-a', 'name' => 'Exam A']);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id,
        'current_level' => 'A2',
        'onboarding_completed_at' => now(),
    ]);
    Lesson::create(['user_id' => $user->id, 'title' => 'Kept lesson', 'theory_markdown' => 'Kept theory']);

    $this->mock(RoadmapGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generateForUser'));

    $this->actingAs($user)
        ->patch(route('profile.update_learning'), ['target_exam_id' => $exam->id, 'interface_language' => 'en'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Lesson::count())->toBe(1)
        ->and($user->profile->fresh()->interface_language)->toBe('en')
        ->and(UserExerciseAttempt::count())->toBe(0);
});
