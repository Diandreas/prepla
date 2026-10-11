<?php

use App\Services\TTS\ElevenLabsTtsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * À l'examen, l'écoute est presque toujours une conversation. Une seule voix qui
 * lit les deux interlocuteurs d'un bout à l'autre empêche l'apprenant de
 * distinguer qui dit quoi — ce qui est précisément une des compétences évaluées.
 */
test('les repliques sont reconnues et attribuees a leur locuteur', function () {
    $service = app(ElevenLabsTtsService::class);

    $repliques = $service->repliques(
        "A: Guten Tag, ich suche ein Zimmer.\n".
        "B: Für wie viele Nächte?\n".
        'A: Für drei Nächte, bitte.'
    );

    expect($repliques)->toHaveCount(3)
        ->and(array_column($repliques, 'locuteur'))->toBe([0, 1, 0])
        ->and($repliques[1]['texte'])->toBe('Für wie viele Nächte?');
});

test('les tirets de dialogue alternent aussi', function () {
    $service = app(ElevenLabsTtsService::class);

    $repliques = $service->repliques("— Bonjour, je voudrais réserver.\n— Pour quel jour ?");

    expect(array_column($repliques, 'locuteur'))->toBe([0, 1]);
});

test('un monologue n est pas pris pour un dialogue', function () {
    $service = app(ElevenLabsTtsService::class);

    $monologue = 'Guten Tag! Ich heiße Anna und ich bin Studentin in Berlin. Ich wohne seit zwei Jahren hier.';

    expect($service->estUnDialogue($monologue))->toBeFalse()
        ->and($service->estUnDialogue("A: Hallo.\nB: Hallo!"))->toBeTrue();
});

test('une ligne sans marque de locuteur prolonge la replique precedente', function () {
    $service = app(ElevenLabsTtsService::class);

    $repliques = $service->repliques("A: Ich komme morgen.\nVielleicht auch übermorgen.\nB: Gut.");

    expect($repliques)->toHaveCount(2)
        ->and($repliques[0]['texte'])->toBe('Ich komme morgen. Vielleicht auch übermorgen.');
});

test('sans cle, le service se declare indisponible et ne parle pas', function () {
    config(['services.elevenlabs.api_key' => null]);
    $service = app(ElevenLabsTtsService::class);

    expect($service->disponible())->toBeFalse()
        ->and($service->parler("A: Hallo.\nB: Hallo!", 'de'))->toBeNull();
});

test('un dialogue recoit une voix par locuteur et un seul fichier', function () {
    Storage::fake('public');
    config(['services.elevenlabs.api_key' => 'cle-de-test']);
    Http::fake(['api.elevenlabs.io/*' => Http::response(str_repeat('M', 3000), 200)]);

    $service = app(ElevenLabsTtsService::class);
    $url = $service->parler("A: Guten Tag.\nB: Guten Tag! Wie geht es Ihnen?\nA: Danke, gut.", 'de');

    expect($url)->toContain('tts/el-');
    expect(Storage::disk('public')->allFiles())->toHaveCount(1);

    // Trois répliques, donc trois appels — et DEUX voix différentes, pas une.
    $voixAppelees = [];
    Http::assertSentCount(3);
    Http::recorded(function ($requete) use (&$voixAppelees) {
        $voixAppelees[] = basename(parse_url((string) $requete->url(), PHP_URL_PATH));

        return true;
    });

    expect(array_unique($voixAppelees))->toHaveCount(2);
});

test('une replique refusee annule tout le dialogue', function () {
    Storage::fake('public');
    config(['services.elevenlabs.api_key' => 'cle-de-test']);
    // Un dialogue ampute d'une replique n'est pas servable : mieux vaut rien,
    // Deepgram prendra le relais.
    Http::fake(['api.elevenlabs.io/*' => Http::response('', 429)]);

    expect(app(ElevenLabsTtsService::class)->parler("A: Hallo.\nB: Hallo!", 'de'))->toBeNull()
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});
