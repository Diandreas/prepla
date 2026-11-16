<?php

use App\Services\AI\MistralService;
use App\Services\AI\WritingCorrectorService;

test('writing correction keeps a normalized practice scale for non IELTS exams', function () {
    $mistral = Mockery::mock(MistralService::class);
    $mistral->shouldReceive('chat')->once()->with(Mockery::on(function (array $messages) {
        expect($messages[0]['content'])
            ->toContain('expert Goethe-Zertifikat examiner')
            ->toContain("student's CEFR level is A2")
            ->toContain("normalized practice scale from 0 to 9")
            ->toContain('not an official exam score')
            ->toContain('in Français');
        expect($messages[1])->toBe(['role' => 'user', 'content' => 'Ich lerne Deutsch.']);

        return true;
    }))->andReturn(json_encode([
        'score' => 7,
        'band_scores' => ['grammar_accuracy' => 7],
        'corrections' => [],
        'feedback' => 'La phrase est correcte.',
    ]));

    $result = (new WritingCorrectorService($mistral))->correct('Ich lerne Deutsch.', '', 'Goethe-Zertifikat', 'Français', 'A2');

    expect($result['score'])->toBe(7)
        ->and($result['band_scores']['grammar_accuracy'])->toBe(7)
        ->and($result['word_count'])->toBe(3);
});

test('les mots accentues comptent pour un seul mot', function () {
    // str_word_count coupait « été », « déjà », « Müller » et « Straße » sur leurs lettres accentuees.
    expect(\App\Models\Exercise::nombreDeMots("L'été est déjà là, dit Müller à la Straße !"))->toBe(9)
        ->and(\App\Models\Exercise::nombreDeMots('Ça marche — vraiment ?'))->toBe(3)
        // Un accent tape a part (e + accent combinant) ne coupe pas le mot.
        ->and(\App\Models\Exercise::nombreDeMots("e\u{0301}te\u{0301} pre\u{0301}vu"))->toBe(2)
        ->and(\App\Models\Exercise::nombreDeMots('Er wohnt in Köln seit 2019.'))->toBe(6);

    $mistral = Mockery::mock(MistralService::class);
    $mistral->shouldReceive('chat')->andReturn(json_encode(['score' => 5, 'corrections' => [], 'feedback' => 'Bien.']));
    $result = (new WritingCorrectorService($mistral))->correct('Je suis allé à Genève hier.', '', 'DELF', 'Français', 'A2');

    expect($result['word_count'])->toBe(6);
});
