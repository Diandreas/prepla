<?php

use App\Services\Media\ImagesCommons;
use Illuminate\Support\Facades\Http;

/**
 * Une illustration d'examen se verifie, elle ne se devine pas.
 *
 * La recherche de Wikimedia Commons classe par pertinence documentaire, pas par
 * sujet. Sur « sunny blue sky » son premier resultat etait un heron dans un
 * etang, et sur « city bus stop » un panneau d'arret. Pour une epreuve de
 * langue, une image a moitie juste ne vaut rien : l'apprenant doit reconnaitre
 * la chose du premier coup d'oeil, ou la question ne mesure plus rien.
 */
function reponseCommons(array $fichiers): array
{
    $pages = [];
    foreach ($fichiers as $i => [$titre, $licence]) {
        $pages[(string) $i] = [
            'title' => $titre,
            'imageinfo' => [[
                'thumburl' => 'https://exemple.test/'.rawurlencode($titre).'.jpg',
                'descriptionurl' => 'https://commons.wikimedia.org/wiki/'.rawurlencode($titre),
                'extmetadata' => [
                    'LicenseShortName' => ['value' => $licence],
                    'Artist' => ['value' => '<a href="#">Quelqu un</a>'],
                ],
            ]],
        ];
    }

    return ['query' => ['pages' => $pages]];
}

beforeEach(fn () => Http::preventStrayRequests());

test('une image hors sujet est ecartee au profit d une image qui parle du terme', function () {
    Http::fake(['commons.wikimedia.org/*' => Http::response(reponseCommons([
        ['File:Grey heron in a pond with blue sky.jpg', 'CC0'],   // premier, mais hors sujet
        ['File:Sun in a clear sky.jpg', 'CC BY-SA 4.0'],
    ]))]);

    $trouvee = app(ImagesCommons::class)->chercher('sun clear sky');

    expect($trouvee['titre'])->toBe('File:Sun in a clear sky.jpg');
});

test('a pertinence egale le domaine public passe devant', function () {
    // Il n'oblige a citer personne : une mention oubliee est une licence violee.
    Http::fake(['commons.wikimedia.org/*' => Http::response(reponseCommons([
        ['File:Red apples on a table.jpg', 'CC BY-SA 4.0'],
        ['File:Red apples in a basket.jpg', 'Public domain'],
    ]))]);

    $trouvee = app(ImagesCommons::class)->chercher('apples');

    expect($trouvee['titre'])->toBe('File:Red apples in a basket.jpg')
        ->and(app(ImagesCommons::class)->credit($trouvee))->toBe('');
});

test('rien ne vaut mieux qu une image qui ne montre pas la chose', function () {
    Http::fake(['commons.wikimedia.org/*' => Http::response(reponseCommons([
        ['File:Collection of car badges.jpg', 'CC0'],
        ['File:A map of Bulbeck Street.jpg', 'CC0'],
    ]))]);

    expect(app(ImagesCommons::class)->chercher('bananas fruit'))->toBeNull();
});

test('le credit nomme l auteur et la licence quand elle l exige', function () {
    Http::fake(['commons.wikimedia.org/*' => Http::response(reponseCommons([
        ['File:A blue city bus.jpg', 'CC BY-SA 4.0'],
    ]))]);

    $service = app(ImagesCommons::class);
    $trouvee = $service->chercher('blue city bus');

    expect($service->credit($trouvee))->toContain('CC BY-SA 4.0')
        ->and($service->credit($trouvee))->toContain('Wikimedia Commons');
});

test('le singulier et le pluriel comptent pour le meme mot', function () {
    Http::fake(['commons.wikimedia.org/*' => Http::response(reponseCommons([
        ['File:Bunch of banana on sale.jpg', 'CC0'],
    ]))]);

    expect(app(ImagesCommons::class)->chercher('bananas')['titre'])
        ->toBe('File:Bunch of banana on sale.jpg');
});
