<?php

namespace App\Http\Controllers;

use App\Models\DictionaryWord;
use App\Models\UserWordProgress;
use App\Services\AI\MistralService;
use App\Services\PersonalLexiconService;
use App\Services\TTS\DeepgramTtsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DictionaryController extends Controller
{
    /**
     * The dictionary stores a word under one code per language.
     *
     * Exercises identify their language by slug ('german'), the rest of the app by
     * name ('Allemand') or ISO code ('de'). A word looked up from an exercise used to
     * land under 'german' while the lexicon only ever reads 'de', so it was saved and
     * then never seen again. Everything is normalised here, to ISO.
     */
    public static function languageCode(?string $language): string
    {
        $map = [
            'en' => 'en', 'english' => 'en', 'anglais' => 'en',
            'de' => 'de', 'german' => 'de', 'deutsch' => 'de', 'allemand' => 'de', 'german (deutsch)' => 'de',
            'fr' => 'fr', 'french' => 'fr', 'français' => 'fr', 'francais' => 'fr',
            'es' => 'es', 'spanish' => 'es', 'español' => 'es', 'espagnol' => 'es',
        ];

        return $map[mb_strtolower(trim((string) $language))] ?? 'en';
    }

    /** The language written out, for a prompt the AI has to answer in. */
    private static function languageName(string $isoCode): string
    {
        return ['de' => 'allemand', 'fr' => 'français', 'es' => 'espagnol'][$isoCode] ?? 'anglais';
    }

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
        $lexicon = app(PersonalLexiconService::class);
        $lexicon->importLegacy($user);
        $words = UserWordProgress::where('user_id', $user->id)
            ->with('dictionaryWord')
            ->orderBy('updated_at', 'desc')
            ->get();

        $reviewableCount = $lexicon->due($user)->count();

        return Inertia::render('practice/dictionary', [
            'words' => $words,
            'reviewableCount' => $reviewableCount,
            'language' => $lexicon->language($user),
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

        $isoCode = self::languageCode($langName);

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
                Log::info("Dictionary: Growing local database for {$isoCode}...");
                $mistral = app(MistralService::class);

                $prompt = "Génère 10 mots utiles en {$langName} adaptés à un apprenant de niveau {$userLevel} (CECRL) — des mots de ce niveau ou légèrement en dessous, JAMAIS au-dessus de {$userLevel}. Réponds UNIQUEMENT en JSON avec ce format : [{\"word\": \"...\", \"definition\": \"...\", \"example\": \"...\", \"translation\": \"...\", \"skill_level\": \"{$userLevel}\"}]. La 'definition' est dans la langue cible, 'example' est une phrase contenant le mot, 'translation' est en français.";

                $response = $mistral->chat([
                    ['role' => 'system', 'content' => 'Tu es un expert en lexicographie académique. Réponds uniquement avec un JSON pur.'],
                    ['role' => 'user', 'content' => $prompt],
                ]);

                $aiWords = json_decode($response, true);
                if (is_array($aiWords)) {
                    foreach ($aiWords as $w) {
                        // Avoid duplicates in the global dictionary
                        $exists = DictionaryWord::where('language', $isoCode)->where('word', $w['word'])->exists();
                        if (! $exists) {
                            DictionaryWord::create([
                                'word' => $w['word'],
                                'language' => $isoCode,
                                'definition' => $w['definition'],
                                'example' => $w['example'],
                                'translation' => $w['translation'],
                                'skill_level' => $w['skill_level'] ?? 'B2',
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
                Log::error('Dictionary Discovery Exception: '.$e->getMessage());
            }
        }

        if ($newWords->isEmpty()) {
            return back()->with('error', 'Aucun nouveau mot trouvé. Réessayez plus tard.');
        }

        foreach ($newWords as $word) {
            UserWordProgress::create([
                'user_id' => $user->id,
                'dictionary_word_id' => $word->id,
                'status' => 'discovered',
            ]);
        }

        return back()->with('success', "{$newWords->count()} nouveaux mots ajoutés à votre dictionnaire !");
    }

    /**
     * Add a looked-up word to the learner's lexicon.
     *
     * The button inside an exercise used to post to the vocabulary endpoint, which
     * writes to another table entirely: the word was stored, the lexicon listed none
     * of it, and the learner rightly concluded nothing had been added. It now lands
     * where "Mon Lexique" reads, and comes back as JSON so a session in progress is
     * not navigated away from.
     */
    public function save(Request $request)
    {
        $validated = $request->validate([
            'dictionary_word_id' => 'required|integer|exists:dictionary_words,id',
        ]);

        $progress = UserWordProgress::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'dictionary_word_id' => $validated['dictionary_word_id'],
            ],
            ['status' => 'discovered']
        );

        return response()->json([
            'saved' => true,
            // Already in the lexicon: the learner met this word before.
            'already_known' => ! $progress->wasRecentlyCreated,
        ]);
    }

    /**
     * Generate TTS audio for a word.
     */
    public function audio(DictionaryWord $word)
    {
        $tts = app(DeepgramTtsService::class);
        $url = $tts->speak($word->word, $word->language);

        if (! $url) {
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
        $limit = (int) ($request->validate(['limit' => 'nullable|integer|min:1|max:10'])['limit'] ?? 5);

        // Get words that need review (priority: oldest review date or recently discovered)
        $wordsToReview = app(PersonalLexiconService::class)->due($user)
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
            'results' => 'required|array|min:1|max:10',
            'results.*.progress_id' => 'required|distinct|exists:user_word_progress,id',
            'results.*.is_correct' => 'required|boolean',
            'results.*.mode' => 'required|in:word2def,def2word,translation,gapfill,dictation,recall',
            'results.*.answer' => 'required|string|max:2000',
        ]);

        $ids = array_column($validated['results'], 'progress_id');
        abort_unless(UserWordProgress::where('user_id', $request->user()->id)->whereIn('id', $ids)->count() === count($ids), 403);

        return DB::transaction(function () use ($validated) {
            $xpTotal = 0;
            foreach ($validated['results'] as $result) {
                $progress = UserWordProgress::with('dictionaryWord')->lockForUpdate()->findOrFail($result['progress_id']);
                abort_unless($progress->user_id === auth()->id(), 403);
                $word = $progress->dictionaryWord;
                $expected = match ($result['mode']) {
                    'word2def' => $word->definition, 'translation' => $word->translation, default => $word->word,
                };
                $correct = filled($expected) && mb_strtolower(trim($result['answer'])) === mb_strtolower(trim($expected));
                // Recognition and free recall are separate evidence. Replaying today's
                // same word never creates extra mastery or XP.
                if ($progress->last_reviewed_at?->isToday()) {
                    continue;
                }
                $recall = in_array($result['mode'], ['gapfill', 'dictation', 'recall'], true);
                if ($correct) {
                    $progress->increment($recall ? 'recall_count' : 'recognition_count');
                }
                $progress->refresh();
                $status = ! $correct ? 'learning' : (($progress->recall_count >= 2 && $progress->recognition_count >= 1) ? 'mastered' : 'learning');
                $days = $status === 'mastered' ? 7 : ($progress->recall_count + $progress->recognition_count >= 2 ? 3 : 1);
                $progress->update(['status' => $status, 'last_reviewed_at' => now(),
                    'next_review_at' => $correct ? now()->addDays($days) : now()->addMinutes(15)]);
                if ($correct) {
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
                'message' => "Session terminée ! +{$xpTotal} XP",
            ]);
        });
    }

    /**
     * Look up a specific word (API/Legacy).
     */
    public function lookup(string $language, string $word)
    {
        $isoCode = self::languageCode($language);
        $wordData = DictionaryWord::whereIn('language', array_unique([$isoCode, $language]))
            ->where('word', $word)
            ->first();

        if (! $wordData) {
            // Fallback: Use AI to define the word
            try {
                $mistral = app(MistralService::class);
                $langName = self::languageName($isoCode);

                $prompt = "Définit le mot '{$word}' en {$langName}. Réponds UNIQUEMENT en JSON avec ce format : {\"word\": \"{$word}\", \"definition\": \"...\", \"example\": \"...\", \"translation\": \"...\", \"skill_level\": \"B2\"}. La traduction doit être en français.";

                $response = $mistral->chat([
                    ['role' => 'system', 'content' => 'Tu es un lexicographe expert. Réponds uniquement en JSON pur.'],
                    ['role' => 'user', 'content' => $prompt],
                ]);

                $data = json_decode($response, true);
                if (isset($data['definition'])) {
                    $wordData = DictionaryWord::create([
                        'word' => $word,
                        'language' => $isoCode,
                        'definition' => $data['definition'],
                        'example' => $data['example'] ?? '',
                        'translation' => $data['translation'] ?? '',
                        'skill_level' => $data['skill_level'] ?? 'B2',
                    ]);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Generation failed'], 500);
            }
        }

        if (! $wordData) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json($wordData);
    }
}
