<?php

use App\Models\{User, UserProfile, UserVocabulary, DictionaryWord, UserWordProgress};

test('old saved words without a level can open the dictionary repeatedly without losing progress', function () {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);
    $legacy = UserVocabulary::create(['user_id' => $user->id, 'word' => 'Woche', 'language_slug' => 'german', 'definition' => 'Une semaine', 'examples' => ['Diese Woche lerne ich.'], 'next_review_at' => now()->addDay()]);
    $this->actingAs($user)->get('/dictionary')->assertOk();
    $word = DictionaryWord::where('word', 'Woche')->firstOrFail();
    $progress = UserWordProgress::where('user_id', $user->id)->where('dictionary_word_id', $word->id)->firstOrFail();
    $progress->update(['status' => 'mastered']);
    $this->get('/dictionary')->assertOk();
    expect($word->skill_level)->toBe('A1')->and($word->language)->toBe('de')
        ->and($progress->fresh()->status)->toBe('mastered')
        ->and(UserWordProgress::where('user_id', $user->id)->count())->toBe(1)
        ->and($legacy->fresh()->word)->toBe('Woche');
});
