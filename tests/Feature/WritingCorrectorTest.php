<?php

use App\Models\Exam;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AI\WritingCorrectorService;

test('writing corrections use the learners target exam and level', function () {
    $language = Language::create([
        'slug' => 'german',
        'name' => 'Allemand',
        'native_name' => 'Deutsch',
        'flag' => 'DE',
    ]);
    $exam = Exam::create([
        'language_id' => $language->id,
        'slug' => 'goethe',
        'name' => 'Goethe-Zertifikat',
    ]);
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id,
        'current_level' => 'A2',
        'native_language' => 'fr',
        'onboarding_completed_at' => now(),
    ]);

    $this->mock(WritingCorrectorService::class, function ($mock) {
        $mock->shouldReceive('correct')->once()
            ->with('Ich lerne jeden Tag Deutsch.', 'Parle de tes habitudes.', 'Goethe-Zertifikat', 'fr', 'A2')
            ->andReturn(['score' => 7, 'band_scores' => [], 'corrections' => [], 'feedback' => 'Continue ainsi.']);
    });

    $this->actingAs($user)
        ->from('/ai-tools/writing-corrector')
        ->post('/ai-tools/writing-corrector', [
            'text' => 'Ich lerne jeden Tag Deutsch.',
            'task_description' => 'Parle de tes habitudes.',
        ])
        ->assertRedirect('/ai-tools/writing-corrector')
        ->assertSessionHas('correction.score', 7)
        ->assertSessionHas('correction.submitted_text', 'Ich lerne jeden Tag Deutsch.');
});

test('writing corrections do not assume IELTS when no target exam is selected', function () {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => null,
        'current_level' => null,
        'native_language' => null,
        'onboarding_completed_at' => now(),
    ]);

    $this->mock(WritingCorrectorService::class, function ($mock) {
        $mock->shouldReceive('correct')->once()
            ->with('My everyday language practice.', '', 'language proficiency', 'Français', null)
            ->andReturn(['score' => 6, 'band_scores' => [], 'corrections' => [], 'feedback' => 'Un bon début.']);
    });

    $this->actingAs($user)
        ->from('/ai-tools/writing-corrector')
        ->post('/ai-tools/writing-corrector', ['text' => 'My everyday language practice.'])
        ->assertRedirect('/ai-tools/writing-corrector')
        ->assertSessionHas('correction.score', 6);
});

test('writing corrections reject short texts before calling AI', function () {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);
    $this->mock(WritingCorrectorService::class, fn ($mock) => $mock->shouldNotReceive('correct'));

    $this->actingAs($user)
        ->post('/ai-tools/writing-corrector', ['text' => 'Hi'])
        ->assertSessionHasErrors('text');
});
