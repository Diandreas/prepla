<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;

/**
 * Des epreuves d'ecoute sortaient MUETTES : ni enregistrement, ni bouton a
 * presser, juste des questions sur un son que personne n'entendait. Releve sur
 * les donnees reelles : 65 exercices sonores sur 629. Pour 41 d'entre eux, le
 * texte parle existait pourtant — range dans le contenu de l'exercice (sous
 * « audio_text », ou sous « passage » quand le generateur a cru ecrire une
 * lecture) et non dans la question, seul endroit que le lecteur regardait.
 * Un passage n'etant jamais affiche pour une epreuve d'ecoute, le texte etait
 * la, invisible et inaudible.
 */
function ecoute(array $contenu, array $questions, string $competence = 'listening', string $format = 'mcq'): Exercise
{
    $langue = Language::firstOrCreate(
        ['slug' => 'german'],
        ['name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']
    );
    $examen = Exam::firstOrCreate(
        ['slug' => 'goethe-test'],
        ['language_id' => $langue->id, 'name' => 'Goethe-Zertifikat']
    );
    $section = ExamSection::firstOrCreate(
        ['exam_id' => $examen->id, 'slug' => 'hoeren-' . $competence],
        ['name' => 'Hören', 'skill_type' => $competence]
    );
    $type = ExerciseType::firstOrCreate(
        ['section_id' => $section->id, 'slug' => $format . '-' . $competence],
        ['name' => 'Multiple Choice', 'skill_type' => $competence, 'component_key' => $format]
    );

    return Exercise::create([
        'exercise_type_id' => $type->id,
        'exam_id' => $examen->id,
        'difficulty' => 'B1',
        'content' => $contenu,
        'questions' => $questions,
    ]);
}

function questionSimple(array $extra = []): array
{
    return array_merge([
        'id' => 'q1',
        'type' => 'mcq',
        'text' => 'Wo wohnt Anna?',
        'options' => ['In Berlin', 'In München', 'In Hamburg', 'In Köln'],
        'correct_answer' => 'A',
    ], $extra);
}

test('le passage parle est retrouve quel que soit l endroit ou il est range', function () {
    $dansLaQuestion = ecoute([], [questionSimple(['audio_text' => 'Anna wohnt in Berlin.'])]);
    expect($dansLaQuestion->texteParle($dansLaQuestion->questions[0]))->toBe('Anna wohnt in Berlin.');

    $dansLeContenu = ecoute(['audio_text' => 'Anna arbeitet in einem Café.'], [questionSimple()]);
    expect($dansLeContenu->texteParle($dansLeContenu->questions[0]))->toBe('Anna arbeitet in einem Café.');

    // Le cas le plus frequent en base : le generateur a ecrit « passage », parce
    // qu'il croyait produire une lecture. Pour une ecoute, c'est la transcription.
    $sousPassage = ecoute(['passage' => 'Anna wohnt in Berlin und arbeitet in einem Café.'], [questionSimple()]);
    expect($sousPassage->texteParle($sousPassage->questions[0]))
        ->toBe('Anna wohnt in Berlin und arbeitet in einem Café.');

    // La question garde la main quand elle porte son propre enregistrement.
    $lesDeux = ecoute(['passage' => 'Du contenu'], [questionSimple(['audio_text' => 'De la question'])]);
    expect($lesDeux->texteParle($lesDeux->questions[0]))->toBe('De la question');
});

test('une epreuve d ecoute qui a son texte reste jouable', function () {
    $exercice = ecoute(['passage' => 'Anna wohnt in Berlin.'], [questionSimple()]);

    expect($exercice->answerableQuestions())->toHaveCount(1);
});

test('une epreuve d ecoute sans le moindre son est ecartee', function () {
    $muet = ecoute(['instructions' => 'Hören Sie den Text.'], [questionSimple()]);

    expect($muet->texteParle($muet->questions[0]))->toBe('');
    expect($muet->answerableQuestions())->toBe([]);
});

test('un enregistrement deja depose suffit, sans texte', function () {
    $exercice = ecoute([], [questionSimple(['audio_url' => '/storage/tts/abc.mp3'])]);

    expect($exercice->answerableQuestions())->toHaveCount(1);
});

test('une epreuve de lecture n est pas touchee par la garde', function () {
    // Meme situation — un passage, pas d'audio — mais en lecture le passage est
    // affiche a l'ecran : l'exercice est parfaitement jouable.
    $lecture = ecoute(['passage' => 'Anna wohnt in Berlin.'], [questionSimple()], 'reading');

    expect($lecture->estUneEcoute())->toBeFalse();
    expect($lecture->answerableQuestions())->toHaveCount(1);

    $sansPassage = ecoute(['instructions' => 'Lesen Sie.'], [questionSimple()], 'reading');
    expect($sansPassage->answerableQuestions())->toHaveCount(1);
});

test('les formats sonores sont gardes meme hors competence ecoute', function () {
    $dictee = ecoute(['instructions' => 'Schreiben Sie.'], [
        ['id' => 'q1', 'type' => 'dictation', 'text' => 'Écrivez', 'correct_answer' => 'Anna wohnt in Berlin.'],
    ], 'writing', 'dictation');

    expect($dictee->estUneEcoute())->toBeTrue();
    expect($dictee->answerableQuestions())->toBe([]);
});
