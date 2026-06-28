<?php

namespace App\Services\AI;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepgramSttService
{
    public function transcribe(UploadedFile $file, ?string $lang = null): ?string
    {
        // IMPORTANT: read the key via config(), NOT env(). With config caching
        // enabled in prod (php artisan config:cache), env() returns null → STT
        // silently failed → empty transcription → "0% / n'a pas répondu".
        $apiKey = config('services.deepgram.api_key') ?: env('DEEPGRAM_API_KEY');
        if (!$apiKey) {
            Log::warning('DEEPGRAM_API_KEY missing for STT');
            return null;
        }

        try {
            $audio = file_get_contents($file->getPathname());
            $size = $audio === false ? 0 : strlen($audio);

            // An empty/too-small recording can't be transcribed — don't waste an API
            // call, and let the caller surface a clear "we couldn't hear you" message.
            if ($size < 200) {
                Log::warning('STT: audio too small, skipping Deepgram', ['bytes' => $size]);
                return null;
            }

            $queryParams = [
                'model' => 'nova-2',
                'smart_format' => 'true',
            ];

            if ($lang) {
                // Map full names/slugs to Deepgram ISO codes
                $langMap = [
                    'english' => 'en',
                    'french' => 'fr',
                    'german' => 'de',
                    'spanish' => 'es',
                ];
                $langCode = $langMap[strtolower($lang)] ?? strtolower($lang);
                $queryParams['language'] = $langCode;
            } else {
                $queryParams['detect_language'] = 'true';
            }

            $queryString = http_build_query($queryParams);

            $response = Http::withHeaders([
                'Authorization' => "Token {$apiKey}",
                'Content-Type' => 'audio/webm',
            ])->timeout(30)->withBody($audio, 'audio/webm')
              ->post("https://api.deepgram.com/v1/listen?{$queryString}");

            if ($response->successful()) {
                $transcript = $response->json('results.channels.0.alternatives.0.transcript');
                if ($transcript === null || trim($transcript) === '') {
                    // API answered 200 but heard nothing — log the payload to diagnose.
                    Log::warning('Deepgram STT empty transcript', [
                        'bytes' => $size,
                        'lang' => $queryParams['language'] ?? 'auto',
                        'confidence' => $response->json('results.channels.0.alternatives.0.confidence'),
                    ]);
                    return '';
                }
                return $transcript;
            }

            Log::error('Deepgram API STT failed', ['status' => $response->status(), 'response' => substr($response->body(), 0, 300)]);
        } catch (\Exception $e) {
            Log::error('Deepgram STT exception', ['message' => $e->getMessage()]);
        }

        return null;
    }
}
