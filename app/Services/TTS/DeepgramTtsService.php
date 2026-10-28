<?php

namespace App\Services\TTS;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeepgramTtsService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.deepgram.com/v1/speak';

    // Only EN, FR, DE are supported
    protected array $voices = [
        'en'      => 'aura-2-thalia-en',
        'fr'      => 'aura-2-agathe-fr',
        'de'      => 'aura-2-julius-de',
        'english' => 'aura-2-thalia-en',
        'french'  => 'aura-2-agathe-fr',
        'german'  => 'aura-2-julius-de',
    ];

    public function __construct()
    {
        // Same trap as MistralService: the key is present but null when the env var is
        // unset, so config()'s default never applies and a typed property rejects null.
        $this->apiKey = config('services.deepgram.api_key') ?? '';
    }

    /**
     * Generate speech from text. Returns the public URL of the cached audio file.
     */
    public function speak(string $text, string $lang = 'en', ?string $voice = null): ?string
    {
        if (empty($this->apiKey) || empty(trim($text))) {
            return null;
        }

        // Cache key based on text hash + lang
        $hash = md5($text . $lang . ($voice ?? ''));
        $filename = "tts/{$hash}.mp3";

        // Return cached file if exists
        if (Storage::disk('public')->exists($filename)) {
            return Storage::url($filename);
        }

        try {
            $model = $voice ?? ($this->voices[$lang] ?? 'aura-asteria-en');

            // Deepgram refuse au-dela de 2000 caracteres (413, « Input text
            // exceeds maximum character limit »). Un texte plus long rendait
            // simplement null : l'epreuve d'ecoute restait MUETTE, et rien ne
            // le disait — une discussion de B1 ou un expose depassent
            // facilement ce seuil. On decoupe donc aux frontieres de phrase et
            // on recolle les morceaux en un seul fichier.
            $morceaux = $this->decouper($text);
            $audio = '';

            foreach ($morceaux as $morceau) {
                $response = Http::withHeaders([
                    'Authorization' => "Token {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])
                    // Un long passage met plus de 30 secondes a revenir : la
                    // synthese etait coupee en plein telechargement.
                    ->timeout(120)
                    ->post("{$this->baseUrl}?model={$model}&encoding=mp3", [
                        'text' => $morceau,
                    ]);

                if (! $response->successful()) {
                    Log::warning('Deepgram TTS API error', [
                        'status' => $response->status(),
                        'body' => substr($response->body(), 0, 200),
                        'longueur' => mb_strlen($morceau),
                    ]);

                    return null;
                }

                $audio .= $response->body();
            }

            if ($audio === '') {
                return null;
            }

            Storage::disk('public')->makeDirectory('tts');
            Storage::disk('public')->put($filename, $audio);

            return Storage::url($filename);
        } catch (\Exception $e) {
            Log::error('Deepgram TTS exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /** La limite de l'API, avec une marge pour la ponctuation de fin. */
    private const MAX_CARACTERES = 1800;

    /**
     * Coupe un texte long en morceaux dicibles, aux frontieres de phrase.
     *
     * On ne coupe jamais au milieu d'une phrase : la voix de synthese perdrait
     * l'intonation, et l'apprenant entendrait une cesure au mauvais endroit.
     * Une phrase qui depasserait a elle seule la limite est coupee aux espaces,
     * faute de mieux.
     *
     * @return array<int, string>
     */
    private function decouper(string $texte): array
    {
        if (mb_strlen($texte) <= self::MAX_CARACTERES) {
            return [$texte];
        }

        // On garde le separateur : le point et le saut de ligne portent la
        // respiration du texte.
        $phrases = preg_split('/(?<=[.!?:])\s+|\n+/u', $texte, -1, PREG_SPLIT_NO_EMPTY) ?: [$texte];

        $morceaux = [];
        $courant = '';

        foreach ($phrases as $phrase) {
            foreach ($this->couperAuxEspaces($phrase) as $bout) {
                if ($courant !== '' && mb_strlen($courant) + 1 + mb_strlen($bout) > self::MAX_CARACTERES) {
                    $morceaux[] = $courant;
                    $courant = '';
                }
                $courant = $courant === '' ? $bout : $courant.' '.$bout;
            }
        }

        if (trim($courant) !== '') {
            $morceaux[] = $courant;
        }

        return $morceaux;
    }

    /** @return array<int, string> */
    private function couperAuxEspaces(string $phrase): array
    {
        if (mb_strlen($phrase) <= self::MAX_CARACTERES) {
            return [$phrase];
        }

        $bouts = [];
        $courant = '';
        foreach (preg_split('/\s+/u', $phrase, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $mot) {
            if ($courant !== '' && mb_strlen($courant) + 1 + mb_strlen($mot) > self::MAX_CARACTERES) {
                $bouts[] = $courant;
                $courant = '';
            }
            $courant = $courant === '' ? $mot : $courant.' '.$mot;
        }
        if ($courant !== '') {
            $bouts[] = $courant;
        }

        return $bouts;
    }

    /**
     * Check if the service is configured.
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
