<?php

use App\Services\AI\ExerciseGeneratorService;

/**
 * Mistral ecrivait un jeu de role parfaitement forme — scenario, role,
 * dialogue_turns — puis l'etiquetait « multiple_choice ». Le validateur ne
 * regardait que l'etiquette et jetait toute la fournee : les debats oraux du
 * telc B1 echouaient a chaque essai alors que leur contenu etait bon.
 *
 * L'etiquette ne sert a rien pour l'affichage, c'est le component_key qui
 * decide. Mais elle ne doit pas servir de passe-droit : une vraie reponse a
 * cote reste refusee.
 */
function rectifier(array $questions, string $composant): array
{
    $methode = new ReflectionMethod(ExerciseGeneratorService::class, 'rectifierLeType');
    $methode->setAccessible(true);

    return $methode->invoke(app(ExerciseGeneratorService::class), $questions, $composant);
}

test('un jeu de role bien forme mais mal etiquete est recupere', function () {
    $recu = rectifier([[
        'id' => 'q1', 'type' => 'multiple_choice',
        'scenario' => 'Du diskutierst über Hausaufgaben.',
        'role' => 'Du verteidigst deine Meinung.',
        'dialogue_turns' => [['speaker' => 'examiner', 'text' => 'Hausaufgaben sind sinnlos.']],
        'correct_answer' => null,
    ]], 'role-play');

    expect($recu[0]['type'])->toBe('role-play')
        ->and($recu[0]['scenario'])->toBe('Du diskutierst über Hausaufgaben.');
});

test('un champ surnumeraire est retire au lieu de faire echouer la validation', function () {
    $recu = rectifier([[
        'id' => 'q1', 'type' => 'multiple_choice',
        'scenario' => 'x', 'role' => 'y',
        'dialogue_turns' => [['speaker' => 'examiner', 'text' => 'z']],
        // Le composant n'affiche pas d'options : gardees, elles faisaient refuser
        // la question comme un QCM sans reponse valide.
        'options' => ['a', 'b'],
        'correct_answer' => null,
    ]], 'role-play');

    expect($recu[0]['type'])->toBe('role-play')
        ->and($recu[0])->not->toHaveKey('options');
});

test('un vrai QCM de lecture rendu pour un expose oral reste refuse', function () {
    $qcm = [[
        'id' => 'q1', 'type' => 'reading-multiple-choice',
        'text' => 'Was macht Anna am Wochenende?',
        'options' => ['Sie liest', 'Sie schläft'],
        'correct_answer' => 'A',
    ]];

    // L'etiquette n'est pas corrigee : la question sera refusee, et c'est juste.
    expect(rectifier($qcm, 'speaking-recorder')[0]['type'])->toBe('reading-multiple-choice');
});

test('un jeu de role ampute de sa structure n est pas maquille', function () {
    $recu = rectifier([[
        'id' => 'q1', 'type' => 'multiple_choice',
        'scenario' => 'x',
        // ni role ni dialogue_turns : ce n'est pas un jeu de role
        'correct_answer' => null,
    ]], 'role-play');

    expect($recu[0]['type'])->toBe('multiple_choice');
});

test('une question deja bien etiquetee n est pas touchee', function () {
    $question = ['id' => 'q1', 'type' => 'speaking-recorder', 'text' => 'Erzähle von deiner Reise.',
        'prep_time' => 30, 'speak_time' => 60];

    expect(rectifier([$question], 'speaking-recorder')[0])->toBe($question);
});

test('les types a choix ne sont pas concernes', function () {
    $question = ['id' => 'q1', 'type' => 'autre-chose', 'text' => 'x', 'options' => ['a', 'b'], 'correct_answer' => 'A'];

    // On ne rectifie que la ou l'etiquette est le seul defaut constate.
    expect(rectifier([$question], 'mcq')[0]['type'])->toBe('autre-chose');
});
