<?php

use App\Services\AI\MistralService;
use App\Services\TTS\DeepgramTtsService;

/**
 * Une clé d'API absente doit dégrader les fonctions IA, jamais faire tomber le site.
 *
 * Les deux services lisaient leur clé avec la valeur par défaut de config(), qui ne
 * s'applique que pour une clé absente : celle-ci existe dans config/services.php et
 * vaut null quand la variable d'environnement n'est pas définie. null sur une
 * propriété typée string levait une TypeError dans le constructeur, et toute page
 * résolvant le service renvoyait une 500. Le .env a déjà été effacé deux fois par une
 * restauration du VPS.
 */
test('les services IA se construisent sans cle au lieu de faire tomber le site', function () {
    config(['services.mistral.api_key' => null, 'services.deepgram.api_key' => null]);

    expect(fn () => new MistralService())->not->toThrow(TypeError::class);
    expect(fn () => new DeepgramTtsService())->not->toThrow(TypeError::class);
});

test('sans cle, le tuteur IA repond vide au lieu de lever', function () {
    config(['services.mistral.api_key' => null]);

    expect((new MistralService())->chat([['role' => 'user', 'content' => 'Bonjour']]))->toBeNull();
});

test('la page d accueil reste servie quand aucune cle n est configuree', function () {
    config(['services.mistral.api_key' => null, 'services.deepgram.api_key' => null]);

    $this->get('/')->assertOk();
    $this->get('/login')->assertOk();
});
