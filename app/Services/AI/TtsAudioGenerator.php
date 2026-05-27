<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Génère de l'audio TTS via Deepgram Aura et le cache en MP3 dans storage/app/public/exercise-audio/.
 *
 * Voix disponibles (Aura-1, le plus économique à $0.015 / 1k chars) :
 *   - aura-asteria-en  (US female)   ← défaut anglais
 *   - aura-luna-en     (US female)
 *   - aura-stella-en   (US female)
 *   - aura-athena-en   (UK female)
 *   - aura-arcas-en    (US male)
 *   - aura-orpheus-en  (US male)
 *   - aura-zeus-en     (US male, profond)
 *   - aura-helios-en   (UK male)
 *
 * Note: l'API Deepgram Aura est principalement EN à ce jour. Pour FR/DE on aura besoin
 * d'une voix multilingue (Aura-2 supporte +30 langues, $0.030 / 1k chars).
 */
class TtsAudioGenerator
{
    private const ENDPOINT = 'https://api.deepgram.com/v1/speak';
    private const DISK = 'public';
    private const DIRECTORY = 'exercise-audio';
    private const TIMEOUT_SECONDS = 30;

    /**
     * Génère un fichier audio MP3 pour un texte donné et renvoie l'URL publique.
     * Le résultat est cached par hash(text + voice + model) — les appels identiques
     * réutilisent le MP3 existant sans rappeler l'API.
     *
     * @return string|null URL publique du MP3 (ex: /storage/exercise-audio/abc123.mp3) ou null en cas d'échec
     */
    public function generate(string $text, string $voice = 'aura-asteria-en', ?string $model = null): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $model = $model ?? $voice;     // Deepgram utilise model=aura-asteria-en pour Aura-1
        $hash = hash('sha256', $voice . '|' . $text);
        $filename = self::DIRECTORY . '/' . $hash . '.mp3';

        // Cache hit ?
        if (Storage::disk(self::DISK)->exists($filename)) {
            return Storage::disk(self::DISK)->url($filename);
        }

        $apiKey = config('services.deepgram.api_key');
        if (!$apiKey) {
            Log::warning('DEEPGRAM_API_KEY missing for TTS');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Token {$apiKey}",
                'Content-Type'  => 'application/json',
            ])
                ->timeout(self::TIMEOUT_SECONDS)
                ->withBody(json_encode(['text' => $text]), 'application/json')
                ->post(self::ENDPOINT . '?model=' . urlencode($model));

            if (!$response->successful()) {
                Log::error('Deepgram TTS failed', [
                    'status'   => $response->status(),
                    'response' => substr($response->body(), 0, 500),
                    'voice'    => $voice,
                    'len'      => mb_strlen($text),
                ]);
                return null;
            }

            $audioBinary = $response->body();
            if (strlen($audioBinary) < 100) {
                Log::warning('Deepgram TTS returned suspiciously small payload', ['bytes' => strlen($audioBinary)]);
                return null;
            }

            Storage::disk(self::DISK)->put($filename, $audioBinary);

            return Storage::disk(self::DISK)->url($filename);
        } catch (\Exception $e) {
            Log::error('Deepgram TTS exception', ['message' => $e->getMessage(), 'voice' => $voice]);
            return null;
        }
    }

    /**
     * Génère l'audio pour une question d'exercice listening.
     * Décide quel texte synthétiser selon la structure de la question.
     *
     * @return string|null URL publique du MP3
     */
    public function generateForQuestion(array $question, string $language = 'en'): ?string
    {
        $text = $this->extractListenableText($question);
        if (!$text) {
            return null;
        }

        $voice = $this->defaultVoiceFor($language);
        return $this->generate($text, $voice);
    }

    /**
     * Détermine quel champ d'une question contient le texte à dire à voix haute.
     */
    private function extractListenableText(array $question): ?string
    {
        // Champ explicite audio_text (renvoyé par le générateur d'exercices IA pour les listening)
        if (!empty($question['audio_text']) && is_string($question['audio_text'])) {
            return $question['audio_text'];
        }

        // Fallback: passage de listening intégré directement dans le content
        if (!empty($question['passage']) && is_string($question['passage'])) {
            return $question['passage'];
        }

        // Fallback: question text itself (for exercises generated before audio_text was added)
        if (!empty($question['text']) && is_string($question['text'])) {
            return $question['text'];
        }

        return null;
    }

    /**
     * Voix par défaut selon la langue cible de l'audio.
     */
    public function defaultVoiceFor(string $language): string
    {
        return match (strtolower($language)) {
            'en', 'english' => 'aura-asteria-en',
            'fr', 'french'  => 'aura-2-thalia-fr',     // Aura-2 multilingue (FR)
            'de', 'german'  => 'aura-2-amalthea-de',   // Aura-2 multilingue (DE)
            default         => 'aura-asteria-en',
        };
    }
}
