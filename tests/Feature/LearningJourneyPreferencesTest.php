<?php

use App\Models\DictionaryWord;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserWordProgress;

beforeEach(function () {
    $this->learner = User::factory()->create();
    UserProfile::factory()->for($this->learner)->create(['onboarding_completed_at' => now()]);
    $this->actingAs($this->learner);
    $this->word = DictionaryWord::create(['word' => 'hello', 'language' => 'en', 'definition' => 'A greeting', 'translation' => 'bonjour', 'example' => 'Say hello.']);
    $this->progress = UserWordProgress::create(['user_id' => $this->learner->id, 'dictionary_word_id' => $this->word->id, 'status' => 'discovered']);
});

function vocabularyAnswer($progress, string $answer, string $mode = 'gapfill'): array
{
    return ['progress_id' => $progress->id, 'answer' => $answer, 'mode' => $mode, 'is_correct' => true];
}

test('les preferences de seance restent independantes et propres au compte', function () {
    $this->patch('/learning/preferences', ['speaking_enabled' => false])->assertRedirect();
    $this->patch('/learning/preferences', ['audio_enabled' => false])->assertRedirect();
    expect($this->learner->profile->fresh()->learning_preferences)->toBe(['speaking_enabled' => false, 'audio_enabled' => false]);
    $this->patchJson('/learning/preferences', ['audio_enabled' => 'invalid'])->assertUnprocessable();
});

test('le serveur corrige le vocabulaire sans croire le resultat annonce par le client', function () {
    $this->postJson('/dictionary/review-batch/submit', ['results' => [vocabularyAnswer($this->progress, 'wrong')]])->assertOk()->assertJson(['xp_earned' => 0]);
    $progress = $this->progress->fresh();
    expect($progress->status)->toBe('learning')->and($progress->recall_count)->toBe(0);
    expect($progress->next_review_at->diffInMinutes(now(), true))->toBeGreaterThan(14);
});

test('rejouer un mot le meme jour ne multiplie ni les XP ni la preuve de rappel', function () {
    $payload = ['results' => [vocabularyAnswer($this->progress, 'hello')]];
    $this->postJson('/dictionary/review-batch/submit', $payload)->assertOk()->assertJson(['xp_earned' => 2]);
    $this->postJson('/dictionary/review-batch/submit', $payload)->assertOk()->assertJson(['xp_earned' => 0]);
    expect($this->progress->fresh()->recall_count)->toBe(1)->and($this->learner->profile->fresh()->xp_total)->toBe(2);
});

test('un lot contenant le mot dun autre compte ne modifie aucun mot', function () {
    $other = User::factory()->create();
    $foreign = UserWordProgress::create(['user_id' => $other->id, 'dictionary_word_id' => $this->word->id, 'status' => 'discovered']);
    $this->postJson('/dictionary/review-batch/submit', ['results' => [vocabularyAnswer($this->progress, 'hello'), vocabularyAnswer($foreign, 'hello')]])->assertForbidden();
    expect($this->progress->fresh()->last_reviewed_at)->toBeNull()->and($this->learner->profile->fresh()->xp_total)->toBe(0);
});

test('la retention demande reconnaissance et rappel sur plusieurs jours puis programme une reprise', function () {
    $this->postJson('/dictionary/review-batch/submit', ['results' => [vocabularyAnswer($this->progress, 'A greeting', 'word2def')]])->assertOk();
    $this->travel(1)->days();
    $this->postJson('/dictionary/review-batch/submit', ['results' => [vocabularyAnswer($this->progress, 'hello')]])->assertOk();
    $this->travel(3)->days();
    $this->postJson('/dictionary/review-batch/submit', ['results' => [vocabularyAnswer($this->progress, 'hello')]])->assertOk();
    $progress = $this->progress->fresh();
    expect($progress->status)->toBe('mastered')->and($progress->recall_count)->toBe(2)->and($progress->recognition_count)->toBe(1);
    expect($progress->next_review_at->diffInDays(now(), true))->toBeGreaterThan(6);
    $this->getJson('/dictionary/review-session')->assertNotFound();
    $this->travelBack();
});
