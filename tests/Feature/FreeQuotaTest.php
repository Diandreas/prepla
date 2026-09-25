<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Http;

/**
 * La limite gratuite doit fermer la porte AVANT l'effort. Posée sur l'envoi, elle
 * renvoyait vers l'abonnement un apprenant qui venait de finir sa séance : travail
 * perdu, aucune correction, aucun XP.
 */
function freeLearner(): array
{
    Http::preventStrayRequests();

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'sort_order' => 1, 'title' => 'Begrüßungen', 'node_type' => 'lesson', 'level' => 'A1',
    ]);

    $exercises = collect(range(1, 3))->map(fn ($i) => Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'node_id' => $node->id, 'order_in_node' => $i, 'difficulty' => 'A1', 'content' => [],
        'questions' => [[
            'id' => 'q1', 'type' => 'mcq', 'text' => "Frage {$i}",
            'options' => ['Ja', 'Nein'], 'correct_answer' => 'A', 'explanation' => 'Oui.',
        ]],
    ]));

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id,
        'current_level' => 'A1',
        'onboarding_completed_at' => now(),
        'trial_ends_at' => now()->subDay(), // essai terminé : compte gratuit
    ]);

    return [$user, $node, $exercises];
}

test('la seance deja commencee est corrigee, meme quand le quota vient d etre atteint', function () {
    [$user, $node, $exercises] = freeLearner();

    // L'apprenant a déjà consommé ses 3 exercices gratuits du jour.
    foreach ($exercises as $exercise) {
        UserExerciseAttempt::create([
            'user_id' => $user->id, 'exercise_id' => $exercise->id, 'answers' => [],
            'score' => 1, 'accuracy_percent' => 100, 'time_spent' => 10, 'xp_earned' => 5, 'feedback' => [],
        ]);
    }

    $this->actingAs($user)->post(route('exercise.submit_session', $node), [
        'exercise_ids' => [$exercises[0]->id],
        'answers_by_exercise' => [$exercises[0]->id => ['q1' => 'A']],
        'time_spent' => 60,
    ])->assertRedirect(route('node.session_result', $node)); // corrigé, pas jeté

    expect(UserExerciseAttempt::where('user_id', $user->id)->count())->toBe(4);
});

test('une nouvelle seance est refusee a l entree quand le quota est atteint', function () {
    [$user, $node, $exercises] = freeLearner();

    foreach ($exercises as $exercise) {
        UserExerciseAttempt::create([
            'user_id' => $user->id, 'exercise_id' => $exercise->id, 'answers' => [],
            'score' => 1, 'accuracy_percent' => 100, 'time_spent' => 10, 'xp_earned' => 5, 'feedback' => [],
        ]);
    }

    $this->actingAs($user)->get(route('node.start', $node))
        ->assertRedirect(route('subscription.index'))
        ->assertSessionHas('error', fn (string $message) => str_contains($message, 'exercices gratuits'));
});

test('un apprenant gratuit sous son quota entre normalement', function () {
    [$user, $node] = freeLearner();

    $this->actingAs($user)->get(route('node.start', $node))->assertOk();
});
