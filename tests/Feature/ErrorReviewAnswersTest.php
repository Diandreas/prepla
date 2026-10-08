<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Http;

/**
 * La reponse attendue enregistree avec une erreur etait la valeur BRUTE : « A »
 * pour un QCM. Le centre de revision affichait « Bonne reponse : A » et
 * n'acceptait que « A » : celui qui ecrivait la vraie reponse etait compte faux,
 * a chaque revision, pour toujours.
 */
test('une erreur de QCM est enregistree avec la reponse en clair', function () {
    Http::preventStrayRequests();

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'grammar', 'component_key' => 'mcq',
    ]);
    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'sort_order' => 1, 'title' => 'Articles', 'node_type' => 'general', 'level' => 'A1',
    ]);
    $exercise = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'node_id' => $node->id, 'order_in_node' => 1, 'difficulty' => 'A1', 'content' => [],
        'questions' => [[
            'id' => 'q1', 'type' => 'mcq', 'text' => 'Wie heißt du ?',
            'options' => ['Ich heiße Lina.', 'Ich bin Montag.'],
            'correct_answer' => 'A', 'explanation' => 'On repond par son prenom.',
        ]],
    ]);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($user)->post(route('exercise.submit_session', $node), [
        'exercise_ids' => [$exercise->id],
        'answers_by_exercise' => [$exercise->id => ['q1' => 'B']],
        'time_spent' => 30,
    ])->assertRedirect();

    $erreur = UserError::where('user_id', $user->id)->sole();

    expect($erreur->correct_answer)->toBe('A) Ich heiße Lina.')
        // Donc une revision qui compare du texte a du texte devient possible.
        ->and($erreur->correct_answer)->not->toBe('A');
});

/**
 * Une erreur d'expression ecrite arrive sans reponse attendue : la revision la
 * comparait a une chaine vide, donc comptee fausse a chaque passage, pour
 * toujours, et son intervalle de rappel ne montait jamais.
 */
test('une erreur sans reponse attendue n est pas proposee au rappel', function () {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);

    $commun = [
        'user_id' => $user->id, 'skill_type' => 'writing', 'question_text' => 'Ecris un courriel.',
        'question_id' => 'q1',
        'user_answer' => 'Hello I want complain.', 'mastered' => false,
        'next_review_at' => now()->subDay(), 'exercise_type_slug' => 'short-writing',
    ];

    UserError::create($commun + ['exercise_id' => null, 'correct_answer' => '', 'explanation' => 'Il manque un auxiliaire.']);
    UserError::create($commun + ['question_id' => 'q2', 'exercise_id' => null, 'correct_answer' => 'I would like to complain.', 'explanation' => 'Forme polie.']);

    expect(UserError::conceptDue($user->id)->count())->toBe(1)
        // Elle reste visible dans le bilan : seul le rappel actif l'ecarte.
        ->and(UserError::concept($user->id)->count())->toBe(2);

    $this->actingAs($user)->get(route('errors.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('dueForReviewCount', 1));

    $this->actingAs($user)->get(route('errors.practice'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('errors', 1));
});
