<?php

namespace App\Services\AI;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepgramSttService
{
    public function transcribe(UploadedFile $file): ?string
    {
        $apiKey = env('DEEPGRAM_API_KEY');
        if (!$apiKey) {
            Log::warning('DEEPGRAM_API_KEY missing for STT');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Token {$apiKey}",
                'Content-Type' => $file->getMimeType(),
            ])->timeout(30)->send('POST', 'https://api.deepgram.com/v1/listen?model=nova-2&detect_language=true&smart_format=true', [
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
