<?php

use App\Services\TTS\DeepgramTtsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Un long enregistrement doit quand meme avoir une voix.
 *
 * Le fournisseur refuse au-dela de 2000 caracteres (413, « Input text exceeds
 * maximum character limit »). Le service rendait alors null, sans rien dire de
 * plus : l'epreuve d'ecoute restait MUETTE. Une discussion de B1 ou un expose
 * depassent facilement ce seuil — c'est justement le contenu le plus long qui
 * se retrouvait sans son.
 */
beforeEach(function () {
    config()->set('services.deepgram.api_key', 'cle-de-test');
    Storage::fake('public');
    Http::preventStrayRequests();
});

test('un texte long est decoupe et recolle en un seul fichier', function () {
    // Trois phrases de 900 caracteres : 2700 au total, donc deux envois.
    $phrase = str_repeat('Der Bus faehrt heute spaeter. ', 30);   // 900 car.
    $texte = $phrase.$phrase.$phrase;
    expect(mb_strlen($texte))->toBeGreaterThan(2000);

    Http::fake(['api.deepgram.com/*' => Http::response('SON', 200)]);

    $url = app(DeepgramTtsService::class)->speak($texte, 'de');

    expect($url)->not->toBeNull();

    // Chaque envoi doit tenir sous la limite du fournisseur.
    $envois = 0;
    Http::assertSent(function ($requete) use (&$envois) {
        $envois++;
        expect(mb_strlen($requete->data()['text']))->toBeLessThanOrEqual(2000);

        return true;
    });
    expect($envois)->toBeGreaterThan(1);

    // Les morceaux sont recolles : un seul fichier, qui les porte tous.
    $fichier = str_replace('/storage/', '', $url);
    expect(Storage::disk('public')->get($fichier))->toBe(str_repeat('SON', $envois));
});

test('un texte court part en un seul envoi', function () {
    Http::fake(['api.deepgram.com/*' => Http::response('SON', 200)]);

    $url = app(DeepgramTtsService::class)->speak('Guten Morgen.', 'de');

    expect($url)->not->toBeNull();
    Http::assertSentCount(1);
});

test('le decoupage ne coupe pas au milieu d une phrase', function () {
    $texte = str_repeat('Erster Satz hier. ', 100).str_repeat('Zweiter Satz dort. ', 50);

    Http::fake(['api.deepgram.com/*' => Http::response('SON', 200)]);
    app(DeepgramTtsService::class)->speak($texte, 'de');

    Http::assertSent(function ($requete) {
        $envoye = trim($requete->data()['text']);

        // Un morceau se termine sur une ponctuation forte, jamais en plein mot :
        // sinon la voix perd l'intonation et l'apprenant entend une cesure au
        // mauvais endroit.
        return str_ends_with($envoye, '.') || str_ends_with($envoye, '!') || str_ends_with($envoye, '?');
    });
});

test('un refus du fournisseur n ecrit pas de fichier a moitie', function () {
    // Le premier morceau passe, le second est refuse : mieux vaut aucun son
    // qu'un enregistrement tronque dont personne ne saura qu'il manque la fin.
    Http::fake(['api.deepgram.com/*' => Http::sequence()
        ->push('SON', 200)
        ->push('{"err_code":"Payload Too Large"}', 413),
    ]);

    $url = app(DeepgramTtsService::class)->speak(str_repeat('Ein langer Satz hier. ', 150), 'de');

    expect($url)->toBeNull()
        ->and(Storage::disk('public')->allFiles('tts'))->toBe([]);
});
