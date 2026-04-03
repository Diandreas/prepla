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
