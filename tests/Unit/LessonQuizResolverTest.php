<?php

use App\Models\Lesson;

/**
 * Quiz de lecon : la lettre etait essayee AVANT le texte, donc une bonne reponse
 * d'un seul caractere (« a » parmi « a / an / the ») etait lue comme la lettre A
 * et le quiz annoncait la premiere option.
 */
test('le quiz de lecon ne confond plus un mot d une lettre avec un choix', function () {
    $question = ['options' => ['the', 'a', 'an'], 'correct_answer' => 'a'];

    expect(Lesson::resolveCorrectAnswerText($question))->toBe('a')
        ->and(Lesson::isQuestionCorrect($question, 'a'))->toBeTrue()
        ->and(Lesson::isQuestionCorrect($question, 'the'))->toBeFalse();

    // Un chiffre aussi : « 2 » parmi « 1 / 2 / 3 » annoncait « 3 ».
    $chiffres = ['options' => ['1', '2', '3'], 'correct_answer' => '2'];
    expect(Lesson::resolveCorrectAnswerText($chiffres))->toBe('2');

    // Et la lettre reste comprise quand elle ne designe aucune option.
    $lettre = ['options' => ['She knew.', 'She was knew.'], 'correct_answer' => 'A'];
    expect(Lesson::resolveCorrectAnswerText($lettre))->toBe('She knew.');
});
