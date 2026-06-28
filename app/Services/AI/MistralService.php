<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MistralService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.mistral.ai/v1';

    public function __construct()
    {
        $this->apiKey = config('services.mistral.api_key', '');
    }

    public function chat(array $messages, string $model = 'mistral-small-latest'): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = Http::withoutVerifying()
                ->withToken($this->apiKey)
                ->timeout(45)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => $messages,
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.3,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }

            Log::warning('Mistral API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Mistral API exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Extract text from an image (e.g. a photo of a handwritten essay) using
     * Mistral's OCR endpoint. Accepts a base64 data URL (data:image/...;base64,...).
     */
    public function ocr(string $imageDataUrl): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = Http::withoutVerifying()
                ->withToken($this->apiKey)
                ->timeout(60)
                ->post("{$this->baseUrl}/ocr", [
                    'model' => 'mistral-ocr-latest',
                    'document' => [
                        'type' => 'image_url',
                        'image_url' => $imageDataUrl,
                    ],
                ]);

            if ($response->successful()) {
                $pages = $response->json('pages', []);
                $text = collect($pages)
                    ->pluck('markdown')
                    ->filter()
                    ->implode("\n\n");

                return trim($text) !== '' ? trim($text) : null;
            }

            Log::warning('Mistral OCR error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Mistral OCR exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    public function chatRaw(array $messages, string $model = 'mistral-small-latest'): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = Http::withoutVerifying()
                ->withToken($this->apiKey)
                ->timeout(45)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.5,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }
        } catch (\Exception $e) {
            Log::error('Mistral API exception', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
