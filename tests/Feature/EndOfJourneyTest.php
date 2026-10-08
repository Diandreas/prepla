<?php

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Http;

/**
 * Tout parcours se termine par son examen de palier. Des que l'apprenant le
 * reussissait, l'objectif courant RESTAIT cet examen termine : « Continuer ma
 * séance » le renvoyait au tableau de bord avec « Termine la pratique avant cet
 * examen » — alors qu'il n'y avait plus rien a terminer — et le prolongement du
 * parcours, pose juste apres, n'etait jamais atteint. Impasse definitive.
 */
function parcoursTermineSurExamen(string $niveau = 'A1'): array
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => $niveau,
        'native_language' => 'Français', 'onboarding_completed_at' => now(),
    ]);

    $skeleton = CurriculumSkeleton::create([
        'user_id' => $user->id, 'exam_id' => $exam->id,
        'objectives' => collect(range(0, 2))->map(fn ($i) => [
            'order' => $i, 'title' => "Objectif {$niveau} {$i}", 'concept' => 'grammar.basic',
            'level' => $niveau, 'status' => 'done', 'priority' => 'normal',
        ])->all(),
        'current_objective_index' => 2, 'consecutive_successes' => 3, 'consecutive_failures' => 0,
    ]);

    // Exactement l'etat laisse par un examen reussi : l'examen est 'done' et
    // l'index s'est arrete dessus.
    $skeleton->ensureLevelExams();
    $objectifs = $skeleton->objectives;
    $objectifs[3]['status'] = 'done';
    $skeleton->update(['objectives' => $objectifs, 'current_objective_index' => 3]);

    return [$user, $skeleton];
}

test('un parcours termine sur son examen reussi se prolonge au lieu de se bloquer', function () {
    config(['services.mistral.api_key' => 'test-key']);

    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
        'objectives' => collect(range(0, 9))->map(fn ($i) => [
            'order' => $i, 'title' => "Objectif A2 numéro {$i}",
            'concept' => 'grammar.tense.past', 'level' => 'A2',
        ])->all(),
        'title' => 'Le prétérit',
        'theory_markdown' => "# Le prétérit\n\nGestern spielte ich.",
        'key_takeaways' => ['-te pour les verbes faibles'],
        'common_mistakes' => [],
        'comprehension_quiz' => [[
            'question' => 'Quelle forme ?', 'options' => ['spielte', 'spiele'], 'correct_answer' => 'spielte',
        ]],
    ])]]]])]);

    [$user] = parcoursTermineSurExamen('A2');

    $this->actingAs($user)->get(route('lessons.next'))
        ->assertRedirect()
        // Avant : retour au tableau de bord avec un message faux.
        ->assertSessionMissing('error');

    $skeleton = CurriculumSkeleton::where('user_id', $user->id)->sole();
    $objectifs = collect($skeleton->objectives);

    // 3 objectifs faits + l'examen A1 + 10 nouveaux + l'examen du nouveau palier.
    expect($objectifs)->toHaveCount(15)
        ->and($objectifs[4]['status'])->toBe('current')
        ->and($objectifs[4]['level'])->toBe('A2')
        ->and(Lesson::where('user_id', $user->id)->count())->toBe(1);
});

test('le prolongement tient meme quand l IA ne repond pas', function () {
    config(['services.mistral.api_key' => 'test-key']);

    // Quota sature : c'est arrive en production. Le prolongement rendait la main et
    // l'apprenant retombait dans l'impasse que ce code est cense supprimer.
    Http::fake(['*' => Http::response(['message' => 'Service tier capacity exceeded'], 429)]);

    [$user] = parcoursTermineSurExamen('A1');

    $this->actingAs($user)->get(route('lessons.next'))->assertRedirect();

    $skeleton = CurriculumSkeleton::where('user_id', $user->id)->sole();
    $objectifs = collect($skeleton->objectives);

    expect($objectifs->count())->toBeGreaterThan(4)
        ->and($objectifs[4]['status'])->toBe('current')
        // Le programme de reference du niveau, pas une deduction par tiers.
        ->and($objectifs[4]['level'])->toBe('A1')
        ->and($skeleton->current_objective_index)->toBe(4);
});
