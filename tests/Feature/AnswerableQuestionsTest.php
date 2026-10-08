<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Http;

/**
 * Du contenu cassé atteignait les apprenants : une consigne « complète les notes »
 * dont aucune case n'était vide, donc rien à remplir et aucun moyen de répondre ;
 * une lettre attendue hors de la liste des choix, donc comptée fausse quoi qu'on
 * réponde. Relevé sur les données réelles : 14 questions sur 1224.
 */
test('une question sans case a remplir est reconnue comme impossible', function () {
    // Le cas rencontré en production : huit lignes de notes, aucune vide.
    expect(Exercise::questionIsAnswerable([
        'type' => 'note-completion',
        'notes' => [['label' => 'Nom', 'value' => 'Anna'], ['label' => 'Ville', 'value' => 'Berlin']],
        'correct_answers' => ['0' => 'Anna', '1' => 'Berlin'],
    ]))->toBeFalse();

    // La même question avec une case vide est jouable.
    expect(Exercise::questionIsAnswerable([
        'type' => 'note-completion',
        'notes' => [['label' => 'Nom', 'value' => ''], ['label' => 'Ville', 'value' => 'Berlin']],
        'correct_answers' => ['0' => 'Anna'],
    ]))->toBeTrue();
});

test('une lettre attendue hors des choix est reconnue comme impossible', function () {
    expect(Exercise::questionIsAnswerable([
        'type' => 'mcq', 'text' => 'Wie heißt du ?',
        'options' => ['Anna', 'Berlin'], 'correct_answer' => 'D',
    ]))->toBeFalse();

    expect(Exercise::questionIsAnswerable([
        'type' => 'mcq', 'text' => 'Wie heißt du ?',
        'options' => ['Anna', 'Berlin'], 'correct_answer' => 'B',
    ]))->toBeTrue();
});

test('une question ouverte reste jouable sans reponse attendue', function () {
    // Jugée par l'IA : l'absence de réponse attendue est normale, pas un défaut.
    expect(Exercise::questionIsAnswerable(['type' => 'speaking-recorder', 'prompt' => 'Présente-toi.']))->toBeTrue()
        ->and(Exercise::questionIsAnswerable(['type' => 'essay-editor', 'prompt' => 'Rédige un essai.']))->toBeTrue()
        // Mais un QCM sans réponse attendue ne peut pas être corrigé.
        ->and(Exercise::questionIsAnswerable(['type' => 'mcq', 'text' => 'Wie ?', 'options' => ['a', 'b']]))->toBeFalse();
});

test('la seance ne sert pas les questions impossibles', function () {
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

    // Un exercice mi-jouable : deux bonnes questions, une impossible.
    $exercise = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'node_id' => $node->id, 'order_in_node' => 1, 'difficulty' => 'A1', 'content' => [],
        'questions' => [
            ['id' => 'q1', 'type' => 'mcq', 'text' => 'Frage 1', 'options' => ['Ja', 'Nein'], 'correct_answer' => 'A', 'explanation' => 'Oui.'],
            ['id' => 'q2', 'type' => 'mcq', 'text' => 'Frage 2', 'options' => ['Ja', 'Nein'], 'correct_answer' => 'Z', 'explanation' => 'Impossible.'],
            ['id' => 'q3', 'type' => 'mcq', 'text' => 'Frage 3', 'options' => ['Ja', 'Nein'], 'correct_answer' => 'B', 'explanation' => 'Oui.'],
        ],
    ]);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($user)->get(route('node.start', $node))
        ->assertOk()
        ->assertInertia(function ($page) {
            $servies = collect($page->toArray()['props']['exercises'][0]['questions']);

            // La question impossible ne part pas a l'ecran ; les deux autres oui.
            expect($servies)->toHaveCount(2)
                ->and($servies->pluck('id')->all())->toBe(['q1', 'q3']);

            return true;
        });

    // Le contenu d'origine reste intact en base : il est reparable par l'enseignant.
    expect(count($exercise->fresh()->questions))->toBe(3)
        ->and(count($exercise->fresh()->answerableQuestions()))->toBe(2);
});
