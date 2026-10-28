<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Services\ExerciseScoringService;

/**
 * Le ß et le « ss » sont la meme reponse.
 *
 * La regle est allemande avant d'etre la notre : les deux graphies sont admises,
 * et la Suisse n'ecrit jamais le ß. Surtout, un apprenant sur un clavier
 * francais n'a pas cette touche — il tapait « Gartenstrasse », la seule forme a
 * sa portee, et la correction la refusait. On lui comptait une faute pour une
 * touche qu'il n'a pas.
 */
function exerciceAvecReponse(string $attendue): Exercise
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-test', 'name' => 'Goethe test']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Lesen', 'skill_type' => 'reading']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'gap-fill', 'name' => 'Texte a trous',
        'skill_type' => 'reading', 'component_key' => 'gap-fill',
    ]);

    return Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'difficulty' => 'A1', 'content' => ['instructions' => 'Complete.'],
        'questions' => [[
            'id' => 'q1', 'type' => 'gap-fill',
            'text' => 'Ich wohne in der ___ 12.', 'correct_answer' => $attendue,
        ]],
    ]);
}

test('ecrire ss au lieu de ss-tset compte juste', function (string $saisie) {
    $exercice = exerciceAvecReponse('Gartenstraße');

    $resultat = app(ExerciseScoringService::class)->score($exercice, ['q1' => $saisie]);

    expect($resultat['feedback'][0]['correct'])->toBeTrue();
})->with(['Gartenstrasse', 'gartenstrasse', 'Gartenstraße', '  Gartenstrasse  ']);

test('l equivalence vaut dans les deux sens', function () {
    // La reponse attendue peut elle-meme etre ecrite sans ß.
    $exercice = exerciceAvecReponse('Strasse');

    $resultat = app(ExerciseScoringService::class)->score($exercice, ['q1' => 'Straße']);

    expect($resultat['feedback'][0]['correct'])->toBeTrue();
});

test('une reponse reellement fausse le reste', function () {
    $exercice = exerciceAvecReponse('Gartenstraße');

    $resultat = app(ExerciseScoringService::class)->score($exercice, ['q1' => 'Bahnhofstrasse']);

    expect($resultat['feedback'][0]['correct'])->toBeFalse();
});
