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
