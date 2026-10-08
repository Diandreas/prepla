<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserProfile;
use App\Services\ExerciseScoringService;
use Illuminate\Support\Facades\Http;

/**
 * Le generateur rend parfois les choix d'un QCM sous forme d'objets
 * (`[{"text": "Ja"}, …]`) au lieu de chaines. L'ecran savait deja les lire ; la
 * correction, non : elle passait le choix brut a une comparaison typee `?string`
 * et levait une TypeError — 500 a l'envoi de la seance, tout le travail perdu.
 * Et la reponse attendue retombait sur la lettre nue, « Bonne reponse : A ».
 */
function qcmAuxChoixMalFormes(array $question): array
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'grammar', 'component_key' => 'mcq',
    ]);
    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'sort_order' => 1, 'title' => 'Salutations', 'node_type' => 'general', 'level' => 'A1',
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

test('une reponse fausse sur des choix mal formes est corrigee sans planter', function () {
    Http::preventStrayRequests();

    [$user, $exercise, $node] = qcmAuxChoixMalFormes([
        'id' => 'q1', 'type' => 'mcq', 'text' => 'Wie heißt du ?',
        // Choix sous forme d'objets : ce que rend parfois le generateur.
        'options' => [['text' => 'Ich heiße Lina.'], ['text' => 'Ich bin Montag.']],
        'correct_answer' => 'A', 'explanation' => 'On repond par son prenom.',
    ]);

    $this->actingAs($user)->post(route('exercise.submit_session', $node), [
        'exercise_ids' => [$exercise->id],
        'answers_by_exercise' => [$exercise->id => ['q1' => 'b']],
        'time_spent' => 30,
    ])->assertRedirect()->assertSessionHasNoErrors();

    // Et l'erreur enregistree donne le texte du choix, pas « A ».
    expect(UserError::where('user_id', $user->id)->sole()->correct_answer)->toBe('A) Ich heiße Lina.');
});

test('une lettre juste est creditee meme quand le choix est un objet', function () {
    Http::preventStrayRequests();

    [$user, $exercise] = qcmAuxChoixMalFormes([
        'id' => 'q1', 'type' => 'mcq', 'text' => 'Wie heißt du ?',
        'options' => [['label' => 'Ich heiße Lina.'], ['label' => 'Ich bin Montag.']],
        // Reponse attendue ecrite en clair : la lettre doit quand meme etre creditee.
        'correct_answer' => 'Ich heiße Lina.', 'explanation' => 'On repond par son prenom.',
    ]);

    $resultat = app(ExerciseScoringService::class)->score($exercise, ['q1' => 'a'], $user);

    expect($resultat['score'])->toBe(1)
        ->and($resultat['feedback'][0]['correct'])->toBeTrue();
});

test('la reponse attendue se lit meme quand les choix sont des objets', function () {
    $service = app(ExerciseScoringService::class);

    expect($service->expectedAnswerText([
        'correct_answer' => 'C',
        'options' => [['text' => 'Montag'], ['value' => 'Dienstag'], ['text' => 'Am Sonntag']],
    ]))->toBe('C) Am Sonntag');

    // Un choix vraiment illisible retombe sur la lettre, sans « Array » ni plantage.
    expect($service->expectedAnswerText([
        'correct_answer' => 'B',
        'options' => [['text' => 'Montag'], [[]]],
    ]))->toBe('B');
});

test('le quiz de lecon lit aussi un choix mal forme', function () {
    $question = [
        'options' => [['text' => 'She knew.'], ['text' => 'She was knew.']],
        'correct_answer' => 'A',
    ];

    expect(Lesson::resolveCorrectAnswerText($question))->toBe('She knew.')
        ->and(Lesson::isQuestionCorrect($question, 'She knew.'))->toBeTrue()
        ->and(Lesson::isQuestionCorrect($question, 'She was knew.'))->toBeFalse();
});

/**
 * Forme relevee sur les donnees de production (exercice 391) : la carte lettree
 * ENTIERE repetee a chaque rang. Lu rang par rang, chaque choix rendait sa
 * premiere valeur — l'apprenant voyait quatre fois la meme reponse, et « Bonne
 * reponse » affichait le choix A quelle que soit la lettre attendue.
 */
test('une carte lettree repetee a chaque rang est remise a plat', function () {
    $carte = [
        'A' => 'Present Simple (habitual action)',
        'B' => 'Present Continuous (action happening now)',
        'C' => 'Past Simple (completed action)',
        'D' => 'Future Simple (planned action)',
    ];

    expect(Exercise::optionList([$carte, $carte, $carte, $carte]))->toBe(array_values($carte))
        // Posee directement, ou glissee une seule fois dans la liste : meme resultat.
        ->and(Exercise::optionList($carte))->toBe(array_values($carte))
        ->and(Exercise::optionList([$carte]))->toBe(array_values($carte))
        // Une liste ordinaire n'est pas touchee.
        ->and(Exercise::optionList(['Ja', 'Nein']))->toBe(['Ja', 'Nein'])
        ->and(Exercise::optionList([['text' => 'Ja'], ['text' => 'Nein']]))->toBe(['Ja', 'Nein']);

    $service = app(ExerciseScoringService::class);

    expect($service->expectedAnswerText(['correct_answer' => 'B', 'options' => [$carte, $carte, $carte, $carte]]))
        ->toBe('B) Present Continuous (action happening now)');
});
