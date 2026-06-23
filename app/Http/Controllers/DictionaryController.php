<?php

namespace App\Http\Controllers;

use App\Models\DictionaryWord;
use App\Models\UserWordProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DictionaryController extends Controller
{
    /** CEFR levels at or below the given level (so we never suggest words above the learner). */
    private static function levelsUpTo(string $level): array
    {
        $order = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $idx = array_search(strtoupper($level), $order, true);
        if ($idx === false) {
            $idx = 0;
        }
        return array_slice($order, 0, $idx + 1);
    }

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

        $reviewableCount = UserWordProgress::where('user_id', $user->id)
            ->whereIn('status', ['discovered', 'learning'])
            ->count();

        return Inertia::render('practice/dictionary', [
            'words' => $words,
            'reviewableCount' => $reviewableCount,
        ]);
    }

    /**
     * Discover a new batch of words (5-10) for the user's target language.
     * Uses local DB first, then AI to grow the database.
     */
    public function discover()
    {
        $user = auth()->user();
        $langName = $user->profile?->targetExam?->language?->name ?? 'English';
        
        $langMap = [
            'English' => 'en', 'Anglais' => 'en',
            'German' => 'de', 'German (Deutsch)' => 'de', 'Deutsch' => 'de', 'Allemand' => 'de',
            'French' => 'fr', 'Français' => 'fr',
            'Spanish' => 'es', 'Español' => 'es',
        ];
        $isoCode = $langMap[$langName] ?? 'en';

        // Only suggest words at or below the learner's CEFR level — never above.
        $userLevel = $user->profile?->current_level ?? 'A1';
        $allowedLevels = self::levelsUpTo($userLevel);

        // 1. Try to find UNREAD words in local DB, at the learner's level
        $excludeIds = UserWordProgress::where('user_id', $user->id)->pluck('dictionary_word_id');

        $newWords = DictionaryWord::where('language', $isoCode)
            ->whereIn('skill_level', $allowedLevels)
            ->whereNotIn('id', $excludeIds)
            ->inRandomOrder()
            ->limit(5)
            ->get();

        // 2. If not enough words, grow the database via AI
        if ($newWords->count() < 5) {
            try {
                \Illuminate\Support\Facades\Log::info("Dictionary: Growing local database for {$isoCode}...");
                $mistral = app(\App\Services\AI\MistralService::class);
                
                $prompt = "Génère 10 mots utiles en {$langName} adaptés à un apprenant de niveau {$userLevel} (CECRL) — des mots de ce niveau ou légèrement en dessous, JAMAIS au-dessus de {$userLevel}. Réponds UNIQUEMENT en JSON avec ce format : [{\"word\": \"...\", \"definition\": \"...\", \"example\": \"...\", \"translation\": \"...\", \"skill_level\": \"{$userLevel}\"}]. La 'definition' est dans la langue cible, 'example' est une phrase contenant le mot, 'translation' est en français.";
                
                $response = $mistral->chat([
                    ['role' => 'system', 'content' => "Tu es un expert en lexicographie académique. Réponds uniquement avec un JSON pur."],
                    ['role' => 'user', 'content' => $prompt]
                ]);

                $aiWords = json_decode($response, true);
                if (is_array($aiWords)) {
                    foreach ($aiWords as $w) {
                        // Avoid duplicates in the global dictionary
                        $exists = DictionaryWord::where('language', $isoCode)->where('word', $w['word'])->exists();
                        if (!$exists) {
                            DictionaryWord::create([
                                'word' => $w['word'],
                                'language' => $isoCode,
                                'definition' => $w['definition'],
                                'example' => $w['example'],
                                'translation' => $w['translation'],
                                'skill_level' => $w['skill_level'] ?? 'B2'
                            ]);
                        }
                    }
                }
                
                // Re-fetch now that database is grown
                $newWords = DictionaryWord::where('language', $isoCode)
                    ->whereNotIn('id', $excludeIds)
                    ->inRandomOrder()
                    ->limit(5)
                    ->get();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Dictionary Discovery Exception: " . $e->getMessage());
            }
        }

        if ($newWords->isEmpty()) {
            return back()->with('error', "Aucun nouveau mot trouvé. Réessayez plus tard.");
        }

        foreach ($newWords as $word) {
            UserWordProgress::create([
                'user_id' => $user->id,
                'dictionary_word_id' => $word->id,
                'status' => 'discovered'
            ]);
        }

        return back()->with('success', "{$newWords->count()} nouveaux mots ajoutés à votre dictionnaire !");
    }

    /**
     * Generate TTS audio for a word.
     */
    public function audio(DictionaryWord $word)
    {
        $tts = app(\App\Services\TTS\DeepgramTtsService::class);
        $url = $tts->speak($word->word, $word->language);

        if (!$url) {
            return response()->json(['error' => 'Audio generation failed'], 500);
        }

        return response()->json(['url' => $url]);
    }

    /**
     * Dedicated full-screen (focus mode) review page.
     */
    public function reviewPage()
    {
        return Inertia::render('practice/vocab-review');
    }

    /**
     * Start a batch review session (5 or 10 words).
     */
    public function reviewSession(Request $request)
    {
        $user = auth()->user();
        $limit = $request->get('limit', 5);

        // Get words that need review (priority: oldest review date or recently discovered)
        $wordsToReview = UserWordProgress::where('user_id', $user->id)
            ->whereIn('status', ['discovered', 'learning'])
            ->with('dictionaryWord')
            ->orderBy('last_reviewed_at', 'asc')
            ->limit($limit)
            ->get();

        if ($wordsToReview->isEmpty()) {
            return response()->json(['message' => 'Aucun mot à réviser ! Tout est maîtrisé.'], 404);
        }

        // Provide a small pool of distractors (other words' translations/definitions)
        // so the frontend can build varied MCQ / matching exercises without extra calls.
        $isoCode = $wordsToReview->first()->dictionaryWord?->language ?? 'en';
        $distractors = DictionaryWord::where('language', $isoCode)
            ->whereNotIn('id', $wordsToReview->pluck('dictionary_word_id'))
            ->inRandomOrder()
            ->limit(12)
            ->get(['word', 'translation', 'definition']);

        return response()->json([
            'words' => $wordsToReview,
            'distractors' => $distractors,
        ]);
    }

    /**
     * Submit results for a batch review.
     */
    public function submitReviewBatch(Request $request)
    {
        $validated = $request->validate([
            'results' => 'required|array',
            'results.*.progress_id' => 'required|exists:user_word_progress,id',
            'results.*.is_correct' => 'required|boolean',
        ]);

        $xpTotal = 0;
        foreach ($validated['results'] as $result) {
            if ($result['is_correct']) {
                $progress = UserWordProgress::find($result['progress_id']);
                if ($progress->user_id !== auth()->id()) continue;

                $nextStatus = match ($progress->status) {
                    'discovered' => 'learning',
                    'learning' => 'mastered',
                    default => 'mastered',
                };

                $progress->update([
                    'status' => $nextStatus,
                    'last_reviewed_at' => now()
                ]);
                $xpTotal += 2;
            }
        }

        $user = auth()->user();
        if ($user->profile && $xpTotal > 0) {
            $user->profile->increment('xp_total', $xpTotal);
        }

        return response()->json([
            'success' => true,
            'xp_earned' => $xpTotal,
            'message' => "Session terminée ! +{$xpTotal} XP"
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
            // Fallback: Use AI to define the word
            try {
                $mistral = app(\App\Services\AI\MistralService::class);
                $langName = $language === 'de' ? 'allemand' : ($language === 'fr' ? 'français' : 'anglais');
                
                $prompt = "Définit le mot '{$word}' en {$langName}. Réponds UNIQUEMENT en JSON avec ce format : {\"word\": \"{$word}\", \"definition\": \"...\", \"example\": \"...\", \"translation\": \"...\", \"skill_level\": \"B2\"}. La traduction doit être en français.";
                
                $response = $mistral->chat([
                    ['role' => 'system', 'content' => "Tu es un lexicographe expert. Réponds uniquement en JSON pur."],
                    ['role' => 'user', 'content' => $prompt]
                ]);

                $data = json_decode($response, true);
                if (isset($data['definition'])) {
                    $wordData = DictionaryWord::create([
                        'word' => $word,
                        'language' => $language,
                        'definition' => $data['definition'],
                        'example' => $data['example'] ?? '',
                        'translation' => $data['translation'] ?? '',
                        'skill_level' => $data['skill_level'] ?? 'B2'
                    ]);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Generation failed'], 500);
            }
        }

        if (!$wordData) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json($wordData);
    }
}
