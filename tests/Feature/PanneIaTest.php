<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Services\AI\MistralEvaluationService;
use App\Services\ExerciseScoringService;
use Illuminate\Support\Facades\Http;

/**
 * Une panne de l'IA n'est pas une faute de l'apprenant.
 *
 * Quand le modele ne repondait pas, l'evaluation renvoyait `isCorrect => false,
 * accuracy => 0`. Le zero ne restait pas a l'ecran : il entrait dans le score
 * de la seance, dans la progression de palier, dans le calcul de la competence
 * la plus faible, et une erreur etait inscrite au dossier avec une categorie
 * que personne n'avait constatee.
 *
 * Quelqu'un qui vient de bien travailler etait penalise pour une coupure
 * reseau. C'est le genre de chose qui fait partir.
 */
function exerciceARediger(): Exercise
{
    $langue = Language::firstOrCreate(['slug' => 'german'],
        ['name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::firstOrCreate(['slug' => 'panne-exam'], ['language_id' => $langue->id, 'name' => 'telc Deutsch']);
    $section = ExamSection::firstOrCreate(['exam_id' => $exam->id, 'slug' => 'schreiben'],
        ['name' => 'Schreiben', 'skill_type' => 'writing', 'time_limit' => 30]);
    // « short-writing » est evalue par l'IA : c'est le chemin qu'on teste.
    $type = ExerciseType::firstOrCreate(['section_id' => $section->id, 'slug' => 'short-writing'],
        ['name' => 'Message court', 'skill_type' => 'writing', 'component_key' => 'short-writing']);

    return Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B1',
        'content' => ['title' => 'Rédige'],
        'questions' => [['id' => 'q1', 'type' => 'short-writing', 'text' => 'Beschreibe deinen Tag.', 'correct_answer' => null]],
    ]);
}

test('l evaluation avoue quand elle n a pas pu juger', function () {
    Http::fake(['api.mistral.ai/*' => Http::response('', 500)]);

    $ecrit = app(MistralEvaluationService::class)->evaluate('x', 'une vraie réponse', 'German', 'B1');
    $oral = app(MistralEvaluationService::class)->evaluateSpeaking('x', 'une vraie réponse', 'German');

    expect($ecrit['evaluation_indisponible'])->toBeTrue()
        ->and($oral['evaluation_indisponible'])->toBeTrue();
});

test('une panne ne compte pas comme une mauvaise reponse', function () {
    Http::fake(['api.mistral.ai/*' => Http::response('', 500)]);

    $resultat = app(ExerciseScoringService::class)->score(exerciceARediger(), ['q1' => 'Mein Tag war gut.']);

    // La question sort du denominateur : elle ne vaut pas zero, elle ne compte pas.
    expect($resultat['feedback'][0]['technical_failure'])->toBeTrue()
        ->and($resultat['feedback'][0]['correct'])->toBeFalse();
});

test('une panne n inscrit aucune erreur au dossier de l apprenant', function () {
    Http::fake(['api.mistral.ai/*' => Http::response('', 500)]);

    $resultat = app(ExerciseScoringService::class)->score(exerciceARediger(), ['q1' => 'Mein Tag war gut.']);

    // Une categorie inventee fausserait la remediation ET la revision espacee :
    // l'apprenant reviserait une faute qu'il n'a jamais faite.
    expect($resultat['feedback'][0]['error_category'])->toBeNull()
        ->and($resultat['feedback'][0]['error_subcategory'])->toBeNull();
});

test('une vraie mauvaise reponse reste une mauvaise reponse', function () {
    // Le garde-fou ne doit pas devenir une excuse : quand l'IA repond, son
    // verdict s'applique.
    Http::fake(['api.mistral.ai/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
        'isCorrect' => false, 'accuracy' => 20, 'error_category' => 'grammar.tense',
        'explanation' => ['concept' => 'Mauvais temps.', 'evidence' => '', 'hint' => 'Revois le parfait.'],
    ])]]]], 200)]);

    $resultat = app(ExerciseScoringService::class)->score(exerciceARediger(), ['q1' => 'Ich gehen gestern.']);

    expect($resultat['feedback'][0]['technical_failure'] ?? false)->toBeFalse()
        ->and($resultat['feedback'][0]['correct'])->toBeFalse()
        ->and($resultat['feedback'][0]['error_category'])->toBe('grammar.tense');
});

test('une bonne reponse reste bonne', function () {
    Http::fake(['api.mistral.ai/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
        'isCorrect' => true, 'accuracy' => 90,
    ])]]]], 200)]);

    $resultat = app(ExerciseScoringService::class)->score(exerciceARediger(), ['q1' => 'Mein Tag war schön.']);

    expect($resultat['feedback'][0]['correct'])->toBeTrue()
        ->and($resultat['feedback'][0]['technical_failure'] ?? false)->toBeFalse();
});

