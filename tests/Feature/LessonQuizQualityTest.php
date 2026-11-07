<?php

use App\Services\Content\LessonQuizQuality;
use App\Models\Lesson;

test('lesson word banks must contain exactly the answer tokens in every language', function () {
    $quality = new LessonQuizQuality;
    foreach (['She was cooking dinner.', 'Elle préparait le dîner.', 'Sie kochte das Abendessen.', 'Ella preparaba la cena.', 'هي تطبخ الطعام'] as $answer) {
        $words = explode(' ', rtrim($answer, '.'));
        $question = ['type' => 'sentence-order', 'question' => 'Arrange', 'options' => [], 'words' => array_reverse($words), 'correct_answer' => $answer];
        expect($quality->valid([$question]))->toBeTrue();
        array_pop($question['words']);
        expect($quality->valid([$question]))->toBeFalse();
    }
});

test('legacy malformed lesson 67 question is identified rather than graded as a choice', function () {
    $quality = new LessonQuizQuality;
    $quiz = [['question' => 'Rearrange []', 'options' => ['was', 'cooking', 'dinner'], 'correct_answer' => 'She was cooking dinner.']];
    expect($quality->normalize($quiz)[0]['type'])->toBe('sentence-order')
        ->and($quality->normalize($quiz)[0]['question'])->toBe('Rearrange')
        ->and($quality->valid($quiz))->toBeFalse();
});

test('a shared first letter never validates a wrong lesson choice or open answer', function () {
    expect(Lesson::isQuestionCorrect(['options' => ['She knew.', 'She was knew.'], 'correct_answer' => 'She knew.'], 'She was knew.'))->toBeFalse()
        ->and(Lesson::isQuestionCorrect(['options' => [], 'correct_answer' => 'She knew.'], 'She was knew.'))->toBeFalse()
        ->and(Lesson::isQuestionCorrect(['options' => ['She knew.', 'She was knew.'], 'correct_answer' => 'A'], 'She knew.'))->toBeTrue();
});

test('les mots a ordonner ecrits dans l enonce deviennent des etiquettes', function () {
    $quality = new LessonQuizQuality;
    $quiz = [['type' => 'sentence-order', 'question' => "Remettez les mots dans l'ordre pour former une phrase correcte : heißt / du / Wie ?", 'options' => [], 'correct_answer' => 'Wie heißt du?']];
    $q = $quality->normalize($quiz)[0];

    expect($q['words'])->toBe(['heißt', 'du', 'Wie'])
        ->and($q['question'])->toBe("Remettez les mots dans l'ordre pour former une phrase correcte.")
        ->and($quality->valid($quiz))->toBeTrue();

    $guillemets = [['type' => 'sentence-order', 'question' => 'Remettez les mots dans l’ordre pour former une phrase correcte : « bald / Bis / ! »', 'options' => [], 'correct_answer' => 'Bis bald!']];
    expect($quality->valid($guillemets))->toBeTrue()
        ->and($quality->normalize($guillemets)[0]['question'])->toBe('Remettez les mots dans l’ordre pour former une phrase correcte.');
});

test('une question defectueuse est retiree sans perdre les autres', function () {
    $quality = new LessonQuizQuality;
    $quiz = [
        ['type' => 'mcq', 'question' => 'Bonjour le matin ?', 'options' => ['Guten Morgen!', 'Gute Nacht!'], 'correct_answer' => 'Guten Morgen!'],
        ['type' => 'mcq', 'question' => 'Cassée', 'options' => ['A', 'B'], 'correct_answer' => 'Z'],
        ['type' => 'recall', 'question' => 'Corrigez : Ich heiße du Anna.', 'options' => [], 'correct_answer' => 'Ich heiße Anna.'],
    ];

    expect($quality->valid($quiz))->toBeFalse()
        ->and($quality->questionsValides($quiz))->toHaveCount(2);
});
