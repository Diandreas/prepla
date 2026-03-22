<?php

namespace App\Services\TTS;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeepgramTtsService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.deepgram.com/v1/speak';

    // Deepgram Aura voices per language
    protected array $voices = [
        'en' => 'aura-asteria-en',
        'fr' => 'aura-orpheus-en', // Deepgram doesn't have native FR yet, fallback
        'de' => 'aura-orpheus-en', // Same fallback for DE
    ];

    public function __construct()
    {
        $this->apiKey = config('services.deepgram.api_key', '');
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
            return Storage::disk('public')->url($filename);
        }

        try {
            $model = $voice ?? ($this->voices[$lang] ?? 'aura-asteria-en');

            $response = Http::withHeaders([
                'Authorization' => "Token {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$this->baseUrl}?model={$model}&encoding=mp3", [
                    'text' => $text,
                ]);

            if ($response->successful()) {
                // Ensure directory exists
                Storage::disk('public')->makeDirectory('tts');

                // Save the audio file
                Storage::disk('public')->put($filename, $response->body());

                return Storage::disk('public')->url($filename);
            }

            Log::warning('Deepgram TTS API error', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 200),
            ]);
        } catch (\Exception $e) {
            Log::error('Deepgram TTS exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Check if the service is configured.
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
