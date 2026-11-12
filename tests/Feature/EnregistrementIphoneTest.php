<?php

use App\Models\User;
use App\Models\UserProfile;
use App\Services\AI\DeepgramSttService;
use App\Services\AI\MistralEvaluationService;
use Illuminate\Http\UploadedFile;

/**
 * L'iPhone enregistre en AAC dans un conteneur mp4 (Safari ne sait pas faire de
 * webm). Le serveur identifie ce fichier comme « mp4 » et le refusait : un
 * apprenant sur iPhone ne pouvait rendre aucune reponse orale.
 *
 * Les fichiers de tests/fixtures sont de vrais enregistrements (« Dog »).
 */
function iphoneApprenant(): User
{
    $u = User::factory()->create();
    UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);

    return $u;
}

function iphoneFichier(string $nom, string $type): UploadedFile
{
    return new UploadedFile(base_path("tests/fixtures/{$nom}"), $nom, $type, null, true);
}

beforeEach(function () {
    $stt = Mockery::mock(DeepgramSttService::class);
    $stt->shouldReceive('transcribe')->andReturn('Dog.');
    app()->instance(DeepgramSttService::class, $stt);

    $eval = Mockery::mock(MistralEvaluationService::class);
    $eval->shouldReceive('evaluateSpeaking')->andReturn(['isCorrect' => true, 'accuracy' => 80, 'explanation' => 'Bien.']);
    app()->instance(MistralEvaluationService::class, $eval);
});

test('un enregistrement d iPhone (AAC dans du mp4) est accepte pour un tour de parole', function () {
    $this->actingAs(iphoneApprenant())
        ->post(route('api.exercise.evaluate-turn'), ['audio' => iphoneFichier('iphone-aac.mp4', 'audio/mp4'), 'prompt' => 'Say dog', 'lang' => 'english'], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonMissingValidationErrors('audio');
});

test('l enregistrement webm de Chrome et d Android reste accepte', function () {
    $this->actingAs(iphoneApprenant())
        ->post(route('api.exercise.evaluate-turn'), ['audio' => iphoneFichier('chrome-opus.webm', 'audio/webm'), 'prompt' => 'Say dog', 'lang' => 'english'], ['Accept' => 'application/json'])
        ->assertOk();
});

test('un fichier qui n est pas du son reste refuse', function () {
    $this->actingAs(iphoneApprenant())
        ->post(route('api.exercise.evaluate-turn'), ['audio' => UploadedFile::fake()->create('script.php', 5, 'application/x-php'), 'prompt' => 'x'], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('audio');
});
