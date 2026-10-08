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
 * Le verrou ne protégeait que des envois simultanés. Renvoyer la MÊME fin de séance
 * plus tard — bouton Retour, double envoi, actualisation — créditait à nouveau l'XP,
 * une tentative par exercice et une progression de nœud.
 */
test('renvoyer la meme fin de seance ne credite rien une seconde fois', function () {
    Http::preventStrayRequests();

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'sort_order' => 1, 'title' => 'Begrüßungen', 'node_type' => 'general', 'level' => 'A1',
    ]);

    collect(range(1, 3))->each(fn ($i) => Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'node_id' => $node->id, 'order_in_node' => $i, 'difficulty' => 'A1', 'content' => [],
        'questions' => [[
            'id' => 'q1', 'type' => 'mcq', 'text' => "Frage {$i}",
            'options' => ['Ja', 'Nein'], 'correct_answer' => 'A', 'explanation' => 'Oui.',
        ]],
    ]));

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(), 'xp_total' => 0,
    ]);

    // La seance est servie : elle emporte son jeton a usage unique.
    $jeton = $this->actingAs($user)->get(route('node.start', $node))
        ->assertOk()
        ->viewData('page')['props']['sessionToken'];

    expect($jeton)->toBeString()->and(strlen($jeton))->toBe(32);

    $exercises = Exercise::where('node_id', $node->id)->pluck('id');
    $envoi = [
        'exercise_ids' => $exercises->all(),
        'answers_by_exercise' => $exercises->mapWithKeys(fn ($id) => [$id => ['q1' => 'A']])->all(),
        'time_spent' => 120,
        'session_token' => $jeton,
    ];

    $this->post(route('exercise.submit_session', $node), $envoi)
        ->assertRedirect(route('node.session_result', $node->id));

    $xpApres = $user->profile->fresh()->xp_total;
    $tentatives = UserExerciseAttempt::where('user_id', $user->id)->count();

    expect($tentatives)->toBe(3)->and($xpApres)->toBeGreaterThan(0);

    // Le renvoi exact de la meme seance : rien ne doit bouger.
    $this->post(route('exercise.submit_session', $node), $envoi)
        ->assertRedirect(route('node.session_result', $node->id))
        ->assertSessionHas('error', 'Cette séance a déjà été corrigée.');

    expect(UserExerciseAttempt::where('user_id', $user->id)->count())->toBe($tentatives)
        ->and($user->profile->fresh()->xp_total)->toBe($xpApres);
});
