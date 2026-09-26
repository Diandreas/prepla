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
use App\Services\ErrorSpacedRepetitionService;
use Illuminate\Support\Facades\Http;

/**
 * La planification des révisions multipliait l'intervalle sans plafond : au bout
 * d'une quinzaine de bonnes réponses la date tombait en l'an 8243, puis au-delà de
 * ce que PHP sait relire — et la fin de séance répondait 500, définitivement.
 */
test('l intervalle de revision ne depasse jamais un an', function () {
    $user = User::factory()->create();
    $error = UserError::create([
        'user_id' => $user->id, 'exercise_id' => null, 'question_id' => 'q1',
        'question_text' => 'Frage', 'user_answer' => 'A', 'correct_answer' => 'B',
        'skill_type' => 'grammar', 'exercise_type_slug' => 'mcq', 'ease_factor' => 2.5, 'interval_days' => 200, 'reviewed_count' => 9,
    ]);

    $service = app(ErrorSpacedRepetitionService::class);

    // Vingt bonnes réponses d'affilée : sans plafond, l'intervalle explosait.
    foreach (range(1, 20) as $ignored) {
        $service->schedule($error, true);
    }

    expect($error->interval_days)->toBeLessThanOrEqual(ErrorSpacedRepetitionService::MAX_INTERVAL_DAYS)
        ->and($error->next_review_at->year)->toBeLessThan(2100)
        // La date reste relisible : c'est ce qui cassait la fin de séance.
        ->and($error->fresh()->next_review_at)->not->toBeNull();
});

test('une bonne reponse ne touche que l erreur de son propre exercice', function () {
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

    // Deux exercices dont les questions portent le même identifiant 'q1'.
    [$answered, $other] = collect([1, 2])->map(fn ($i) => Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'node_id' => $node->id, 'order_in_node' => $i, 'difficulty' => 'A1', 'content' => [],
        'questions' => [[
            'id' => 'q1', 'type' => 'mcq', 'text' => "Frage {$i}",
            'options' => ['Ja', 'Nein'], 'correct_answer' => 'A', 'explanation' => 'Oui.',
        ]],
    ]))->all();

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(),
    ]);

    $foreign = UserError::create([
        'user_id' => $user->id, 'exercise_id' => $other->id, 'question_id' => 'q1',
        'question_text' => 'Frage 2', 'user_answer' => 'B', 'correct_answer' => 'A',
        'skill_type' => 'reading', 'exercise_type_slug' => 'mcq', 'ease_factor' => 2.5, 'interval_days' => 1, 'reviewed_count' => 0,
    ]);

    // L'apprenant répond juste au q1 de l'AUTRE exercice.
    $this->actingAs($user)->post(route('exercise.submit_session', $node), [
        'exercise_ids' => [$answered->id],
        'answers_by_exercise' => [$answered->id => ['q1' => 'A']],
        'time_spent' => 30,
    ])->assertRedirect();

    expect($foreign->fresh()->reviewed_count)->toBe(0); // intacte
});
