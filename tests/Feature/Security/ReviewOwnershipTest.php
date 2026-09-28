<?php

use App\Models\User;
use App\Models\UserError;
use App\Models\UserProfile;
use App\Models\UserVocabulary;

/**
 * Le calendrier de révision d'un apprenant n'appartient qu'à lui.
 *
 * Les deux points d'écriture de la révision espacée étaient liés à leur modèle par
 * identifiant, sans vérifier le propriétaire. En énumérant les identifiants, un compte
 * pouvait déclarer révisée — voire acquise — l'erreur ou le mot d'un autre : la
 * prochaine révision était repoussée, et la notion cessait de revenir chez la personne
 * concernée. Les pages de lecture des résultats vérifiaient déjà le propriétaire.
 */
function reviewLearner(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);

    return $user;
}

test('une erreur ne peut pas etre declaree revisee par un autre compte', function () {
    $owner = reviewLearner();
    $intruder = reviewLearner();

    $error = UserError::create([
        'user_id' => $owner->id,
        'question_id' => 'q1',
        'question_text' => 'Gestern ___ ich ins Kino.',
        'user_answer' => 'gehe',
        'correct_answer' => 'ging',
        'skill_type' => 'grammar',
        'exercise_type_slug' => 'gap-fill',
        'reviewed_count' => 0,
        'mastered' => false,
    ]);

    $this->actingAs($intruder)
        ->postJson(route('errors.submit-review', $error), ['correct' => true])
        ->assertForbidden();

    $error->refresh();
    expect($error->reviewed_count)->toBe(0)
        ->and($error->mastered)->toBeFalse()
        ->and($error->next_review_at)->toBeNull();
});

test('le proprietaire revise normalement son erreur', function () {
    $owner = reviewLearner();

    $error = UserError::create([
        'user_id' => $owner->id,
        'question_id' => 'q1',
        'question_text' => 'Gestern ___ ich ins Kino.',
        'user_answer' => 'gehe',
        'correct_answer' => 'ging',
        'skill_type' => 'grammar',
        'exercise_type_slug' => 'gap-fill',
        'reviewed_count' => 0,
        'mastered' => false,
    ]);

    $this->actingAs($owner)
        ->postJson(route('errors.submit-review', $error), ['correct' => true])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($error->fresh()->reviewed_count)->toBe(1);
});

test('un mot du lexique ne peut pas etre revise par un autre compte', function () {
    $owner = reviewLearner();
    $intruder = reviewLearner();

    $vocab = UserVocabulary::create([
        'user_id' => $owner->id,
        'word' => 'Bahnhof',
        'language_slug' => 'german',
        'definition' => 'Ort, an dem Züge halten.',
        'next_review_at' => now()->addDay(),
    ]);
    $scheduledFor = $vocab->next_review_at;

    $this->actingAs($intruder)
        ->postJson(route('vocabulary.submit-review', $vocab), ['quality' => 5])
        ->assertForbidden();

    expect($vocab->fresh()->next_review_at->timestamp)->toBe($scheduledFor->timestamp);
});
