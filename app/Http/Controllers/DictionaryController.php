<?php

namespace App\Http\Controllers;

use App\Services\Dictionary\DictionaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DictionaryController extends Controller
{
    public function __construct(private DictionaryService $dictionary) {}

    /**
     * GET /api/dictionary/{language}/{word}
     */
    public function lookup(Request $request, string $language, string $word): JsonResponse
    {
        if (! in_array($language, DictionaryService::supportedLanguages())) {
            return response()->json(['error' => 'Unsupported language'], 422);
        }

        $word = mb_strtolower(trim($word));

        if (empty($word) || mb_strlen($word) > 100) {
            return response()->json(['error' => 'Invalid word'], 422);
        }

        $result = $this->dictionary->lookup($word, $language);

        if (empty($result['definitions']) && empty($result['phonetics'])) {
            return response()->json(['error' => 'Word not found'], 404);
        }

        return response()->json($result);
    }
}
