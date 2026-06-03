<?php

namespace App\Http\Controllers;

use App\Services\TTS\DeepgramTtsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TtsController extends Controller
{
    public function speak(Request $request, DeepgramTtsService $tts): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:5000',
            'lang' => 'sometimes|string|in:en,fr,de,english,french,german',
        ]);

        $url = $tts->speak($validated['text'], $validated['lang'] ?? 'en');

        if ($url) {
            return response()->json(['audio_url' => $url]);
        }

        return response()->json(['error' => 'TTS generation failed'], 500);
    }
}
