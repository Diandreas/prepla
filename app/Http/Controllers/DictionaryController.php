<?php

namespace App\Http\Controllers;

use App\Models\DictionaryWord;
use App\Models\UserWordProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DictionaryController extends Controller
{
    /**
     * Display the user's learned/discovered words.
     */
    public function index()
    {
        $user = auth()->user();
        $words = UserWordProgress::where('user_id', $user->id)
            ->with('dictionaryWord')
            ->orderBy('updated_at', 'desc')
            ->get();

        return Inertia::render('practice/dictionary', [
            'words' => $words
        ]);
    }

    /**
     * Discover a new random word for the user's target language.
     */
    public function discover()
    {
        $user = auth()->user();
        $langName = $user->profile?->targetExam?->language?->name ?? 'English';
        
        // Map common language names to ISO codes for APIs
        $langMap = [
            'English' => 'en',
            'Anglais' => 'en',
            'German' => 'de',
            'German (Deutsch)' => 'de',
            'Deutsch' => 'de',
            'Allemand' => 'de',
            'French' => 'fr',
            'Français' => 'fr',
            'Spanish' => 'es',
            'Español' => 'es',
        ];

        $isoCode = $langMap[$langName] ?? 'en';

        // Find a word not yet in user's progress
        $excludeIds = UserWordProgress::where('user_id', $user->id)->pluck('dictionary_word_id');
        
        $newWord = DictionaryWord::where('language', $langName)
            ->whereNotIn('id', $excludeIds)
            ->inRandomOrder()
            ->first();

        if (!$newWord) {
            try {
                \Illuminate\Support\Facades\Log::info("Dictionary Discover: Attempting API lookup for {$isoCode}");
                
                // Fetch random word in target language
                $wordRes = \Illuminate\Support\Facades\Http::get("https://random-word-api.herokuapp.com/word?number=1&lang={$isoCode}");
                
                if (!$wordRes->successful()) {
                    \Illuminate\Support\Facades\Log::warning("Random Word API Failed: " . $wordRes->status());
                    return back()->with('error', "Le service de mots aléatoires est indisponible ({$wordRes->status()}).");
                }

                $word = $wordRes->json()[0];
                \Illuminate\Support\Facades\Log::info("Dictionary Discover: Found word '{$word}', looking up details...");

                $detailsRes = \Illuminate\Support\Facades\Http::get("https://api.dictionaryapi.dev/api/v2/entries/{$isoCode}/{$word}");
                
                if ($detailsRes->successful()) {
                    $data = $detailsRes->json()[0];
                    $newWord = DictionaryWord::create([
                        'word' => $word,
                        'language' => $langName,
                        'definition' => $data['meanings'][0]['definitions'][0]['definition'] ?? 'Keine Definition verfügbar.',
                        'example' => $data['meanings'][0]['definitions'][0]['example'] ?? 'Kein Beispiel verfügbar.',
                        'translation' => '[À traduire]',
                        'skill_level' => 'B2'
                    ]);
                    \Illuminate\Support\Facades\Log::info("Dictionary Discover: Created new word entry for '{$word}'");
                } else {
                    \Illuminate\Support\Facades\Log::warning("Dictionary API failed for '{$word}': " . $detailsRes->status());
                    return back()->with('error', "Détails non trouvés pour le mot '{$word}'. Réessayez !");
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Dictionary Discovery Exception: " . $e->getMessage());
                return back()->with('error', "Une erreur technique est survenue : " . $e->getMessage());
            }
        }

        if (!$newWord) {
            return back()->with('error', "Aucun nouveau mot trouvé pour le moment.");
        }

        UserWordProgress::create([
            'user_id' => $user->id,
            'dictionary_word_id' => $newWord->id,
            'status' => 'discovered'
        ]);

        return back()->with('success', "Nouveau mot découvert : {$newWord->word}");
    }

    /**
     * Generate an AI exercise for a specific word.
     */
    public function review(UserWordProgress $progress)
    {
        $word = $progress->dictionaryWord;
        $mistral = app(\App\Services\AI\MistralService::class);
        
        $prompt = "Génère une courte phrase d'exercice (max 15 mots) en {$word->language} où le mot '{$word->word}' est remplacé par un trou '_____'. La phrase doit être adaptée au niveau {$word->skill_level}. Rends uniquement un JSON avec la clé 'sentence' (ex: {\"sentence\": \"J'aime _____ les pommes.\"}) et rien d'autre.";
        
        $response = $mistral->chat([
            ['role' => 'system', 'content' => "Tu es un assistant pédagogique spécialisé en {$word->language}. Réponds UNIQUEMENT en JSON brut { \"sentence\": \"...\" }."],
            ['role' => 'user', 'content' => $prompt]
        ]);

        if (!$response) {
            return response()->json([
                'sentence' => "Erreur : Impossible de joindre l'IA.",
                'word_id' => $word->id,
                'progress_id' => $progress->id
            ], 500);
        }

        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['sentence'])) {
            \Illuminate\Support\Facades\Log::error("Dictionary Review JSON Error", ['response' => $response]);
            return response()->json([
                'sentence' => "Complétez la phrase avec le mot correct : _____ ({$word->translation})",
                'word_id' => $word->id,
                'progress_id' => $progress->id,
                'debug_response' => $response
            ]);
        }

        return response()->json([
            'sentence' => $data['sentence'] ?? "Complétez la phrase avec le mot correct : _____ ({$word->translation})",
            'word_id' => $word->id,
            'progress_id' => $progress->id
        ]);
    }

    /**
     * Submit an exercise result and update progress.
     */
    public function submitReview(Request $request, UserWordProgress $progress)
    {
        $validated = $request->validate([
            'answer' => 'required|string',
        ]);

        $isCorrect = strtolower(trim($validated['answer'])) === strtolower(trim($progress->dictionaryWord->word));

        if ($isCorrect) {
            $nextStatus = match ($progress->status) {
                'discovered' => 'learning',
                'learning' => 'mastered',
                default => 'mastered',
            };

            $progress->update([
                'status' => $nextStatus,
                'last_reviewed_at' => now()
            ]);

            // Award small XP bonus
            $user = auth()->user();
            if ($user instanceof \App\Models\User && $user->profile) {
                $user->profile->increment('xp_total', 2);
            }

            return response()->json([
                'success' => true,
                'status' => $nextStatus,
                'xp_earned' => 2,
                'message' => 'Excellent ! Progrès enregistré.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "Ce n'est pas tout à fait ça. Réessayez !"
        ]);
    }

    /**
     * Look up a specific word (API/Legacy).
     */
    public function lookup(string $language, string $word)
    {
        $wordData = DictionaryWord::where('language', $language)
            ->where('word', $word)
            ->first();

        if (!$wordData) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json($wordData);
    }
}
