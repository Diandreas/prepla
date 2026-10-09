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
use App\Services\Content\LessonQuizQuality;
use App\Services\ExerciseScoringService;
use Illuminate\Support\Facades\Http;

/**
 * Même famille que les choix de QCM rendus en objets : du contenu généré sous une
 * forme inattendue arrivait dans du code qui attendait une chaîne. Chaque cas ici
 * levait une erreur 500 à l'envoi de la séance — et l'apprenant qui venait de
 * TERMINER son exercice perdait tout, le renvoi retombant sur la même erreur.
 */
function seanceAvecQuestion(array $question, string $composant = 'ordering'): array
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => $composant, 'name' => $composant,
        'skill_type' => 'grammar', 'component_key' => $composant,
    ]);
    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'sort_order' => 1, 'title' => 'Ordre des mots', 'node_type' => 'general', 'level' => 'A1',
    ]);
    $exercise = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'node_id' => $node->id, 'order_in_node' => 1, 'difficulty' => 'A1', 'content' => [],
        'questions' => [$question],
    ]);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(),
    ]);

    return [$user, $exercise, $node];
}

test('une remise en ordre aux items rendus en objets se corrige sans planter', function () {
    Http::preventStrayRequests();

    [$user, $exercise, $node] = seanceAvecQuestion([
        'id' => 'q1', 'type' => 'ordering', 'text' => 'Remets la phrase dans l ordre',
        // Forme que le générateur rend parfois : des objets, pas des chaînes.
        'items' => [['id' => 'i1', 'text' => 'Ich'], ['id' => 'i2', 'text' => 'heiße'], ['id' => 'i3', 'text' => 'Lina']],
        'correct_order' => ['i1', 'i2', 'i3'],
        'explanation' => 'Sujet, verbe, complément.',
    ]);

    $this->actingAs($user)->post(route('exercise.submit_session', $node), [
        'exercise_ids' => [$exercise->id],
        'answers_by_exercise' => [$exercise->id => ['q1' => ['Ich', 'heiße', 'Lina']]],
        'time_spent' => 30,
    ])->assertRedirect()->assertSessionHasNoErrors();

    // La séance est bien enregistrée, et l'ordre juste est reconnu.
    $tentative = UserExerciseAttempt::where('user_id', $user->id)->sole();
    expect((int) $tentative->score)->toBe(1);
});

test('la reponse attendue d une remise en ordre se lit en clair', function () {
    $service = app(ExerciseScoringService::class);

    expect($service->expectedAnswerText([
        'items' => [['text' => 'Ich'], ['text' => 'heiße'], ['text' => 'Lina']],
        'correct_order' => ['i1', 'i2', 'i3'],
    ]))->toBe('Ich → heiße → Lina');

    // Une réponse attendue multiple rendue en objets se lit aussi.
    expect($service->expectedAnswerText([
        'correct_answer' => [['text' => 'blau'], ['text' => 'grün']],
    ]))->toBe('blau, grün');
});

test('une reponse attendue rendue en objet reste creditable', function () {
    $service = app(ExerciseScoringService::class);
    Http::preventStrayRequests();

    $language = Language::create(['slug' => 'english', 'name' => 'English', 'native_name' => 'English', 'flag' => 'gb']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'ielts', 'name' => 'IELTS']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'listening', 'name' => 'Listening', 'skill_type' => 'listening']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'note-completion', 'name' => 'Notes',
        'skill_type' => 'listening', 'component_key' => 'note-completion',
    ]);
    $exercise = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'difficulty' => 'B1', 'content' => [],
        'questions' => [[
            'id' => 'q1', 'type' => 'note-completion',
            'notes' => [['label' => 'Jour', 'value' => ''], ['label' => 'Heure', 'value' => '']],
            // Valeurs attendues rendues en objets : le champ devenait increditable.
            'correct_answers' => ['0' => ['text' => 'Montag'], '1' => ['text' => 'acht Uhr']],
        ]],
    ]);

    $resultat = $service->score($exercise, ['q1' => ['0' => 'Montag', '1' => 'acht Uhr']]);

    expect((float) $resultat['feedback'][0]['accuracy'])->toBe(100.0)
        ->and($resultat['feedback'][0]['correct'])->toBeTrue();
});

test('un quiz de lecon dont l enonce est un objet s ouvre quand meme', function () {
    $quiz = app(LessonQuizQuality::class)->normalize([[
        'question' => ['text' => 'Quelle forme ?'],
        'options' => ['spielte', 'spiele'],
        'correct_answer' => 'spielte',
    ]]);

    expect($quiz[0]['question'])->toBe('Quelle forme ?');
});
