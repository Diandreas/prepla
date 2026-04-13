<?php

namespace App\Services\AI;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepgramSttService
{
    public function transcribe(UploadedFile $file, ?string $lang = null): ?string
    {
        $apiKey = env('DEEPGRAM_API_KEY');
        if (!$apiKey) {
            Log::warning('DEEPGRAM_API_KEY missing for STT');
            return null;
        }

        try {
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
            ])->timeout(30)->send('POST', "https://api.deepgram.com/v1/listen?{$queryString}", [
                'body' => file_get_contents($file->getPathname())
            ]);

            if ($response->successful()) {
                return $response->json('results.channels.0.alternatives.0.transcript');
            }

            Log::error('Deepgram API STT failed', ['status' => $response->status(), 'response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Deepgram STT exception', ['message' => $e->getMessage()]);
        }

        return null;
    }
}
