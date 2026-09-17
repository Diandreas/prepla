<?php

use App\Models\User;
use App\Models\UserProfile;
use App\Services\AI\MistralService;

beforeEach(function () {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);
    $this->actingAs($user);
});

test('explainer returns a service error when the AI cannot reply', function () {
    $this->mock(MistralService::class, function ($mock) {
        $mock->shouldReceive('chatRaw')->once()->andReturnNull();
    });

    $this->postJson('/ai-tools/explainer/ask', [
        'messages' => [['role' => 'user', 'content' => 'Explique le présent en allemand.']],
    ])
        ->assertStatus(503)
        ->assertJsonStructure(['error'])
        ->assertJsonMissingPath('reply');
});

test('explainer returns a successful AI reply unchanged', function () {
    $reply = 'Le verbe conjugué occupe la **deuxième position**.';
    $this->mock(MistralService::class, function ($mock) use ($reply) {
        $mock->shouldReceive('chatRaw')->once()->andReturn($reply);
    });

    $this->postJson('/ai-tools/explainer/ask', [
        'messages' => [['role' => 'user', 'content' => 'Où placer le verbe ?']],
    ])
        ->assertOk()
        ->assertExactJson(['reply' => $reply]);
});

test('explainer rejects invalid messages without calling the AI', function () {
    $this->mock(MistralService::class, fn ($mock) => $mock->shouldNotReceive('chatRaw'));

    $this->postJson('/ai-tools/explainer/ask', [
        'messages' => [['role' => 'user', 'content' => '']],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('messages.0.content');
});
