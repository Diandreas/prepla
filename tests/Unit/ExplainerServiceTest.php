<?php

use App\Services\AI\ExplainerService;
use App\Services\AI\MistralService;

test('explainer chat forwards the conversation with its tutor prompt', function () {
    $conversation = [
        ['role' => 'user', 'content' => 'Où placer le verbe en allemand ?'],
        ['role' => 'assistant', 'content' => 'En deuxième position dans une phrase affirmative.'],
        ['role' => 'user', 'content' => 'Donne-moi un exemple.'],
    ];
    $reply = '**Heute lerne ich Deutsch.**';
    $mistral = Mockery::mock(MistralService::class);
    $mistral->shouldReceive('chatRaw')->once()
        ->with(Mockery::on(function (array $messages) use ($conversation) {
            expect($messages[0]['role'])->toBe('system');
            expect($messages[0]['content'])->toContain('helpful AI language tutor');
            expect(array_slice($messages, 1))->toBe($conversation);

            return true;
        }))
        ->andReturn($reply);

    expect((new ExplainerService($mistral))->chat($conversation))->toBe($reply);
});

test('explainer chat exposes unavailable or empty replies instead of inventing a message', function (?string $reply) {
    $mistral = Mockery::mock(MistralService::class);
    $mistral->shouldReceive('chatRaw')->once()->andReturn($reply);

    expect((new ExplainerService($mistral))->chat([
        ['role' => 'user', 'content' => 'Explique le présent.'],
    ]))->toBeNull();
})->with([null, '', " \n\t "]);
