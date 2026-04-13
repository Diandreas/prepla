<?php

namespace App\Http\Controllers;

use App\Models\UserVocabulary;
use App\Services\Dictionary\DictionaryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VocabularyController extends Controller
{
    public function __construct(
        private DictionaryService $dictionary
    ) {}

    // GET /vocabulary - list all user words
    public function index(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        $language = $profile->targetExam?->language;

        $words = UserVocabulary::where('user_id', $user->id)
            ->when($language, fn($q) => $q->where('language_slug', $language->slug))
            ->orderByDesc('created_at')
            ->paginate(20);

        $dueCount = UserVocabulary::dueForReview($user->id)->count();

        return Inertia::render('vocabulary/index', [
            'words' => $words,
            'dueCount' => $dueCount,
        ]);
    }

    // GET /vocabulary/learn - learn new words
    public function learn(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        $language = $profile->targetExam?->language;

        return Inertia::render('vocabulary/learn', [
            'language' => $language?->slug ?? 'english',
        ]);
    }

    /**
     * API for the discovery page - get a random word
     */
    public function random(string $languageSlug)
    {
        $user = auth()->user();
        $langMap = [
            'english' => 'en', 'french' => 'fr', 'german' => 'de', 'spanish' => 'es'
        ];
        $isoCode = $langMap[$languageSlug] ?? 'en';

        // 1. Exclude words user already has in vocabulary
        $excludeWords = UserVocabulary::where('user_id', $user->id)
            ->where('language_slug', $languageSlug)
            ->pluck('word');

        $word = \App\Models\DictionaryWord::where('language', $isoCode)
            ->whereNotIn('word', $excludeWords)
            ->inRandomOrder()
            ->first();

        if (!$word) {
            // Fallback: If no more words in local DB, use DictionaryController::discover logic to grow it
            // For now, let's just return a placeholder or an error
            return response()->json(['error' => 'No more new words available.'], 404);
        }

        return response()->json([
            'word' => $word->word,
            'definition' => $word->definition,
            'example' => $word->example,
            'translation' => $word->translation,
            'ipa' => null, // Not yet in dictionary_words table
        ]);
    }

    // GET /vocabulary/review - review due words (spaced repetition)
    public function review(Request $request)
    {
        $user = $request->user();

        $words = UserVocabulary::dueForReview($user->id)
            ->limit(20)
            ->get();

        return Inertia::render('vocabulary/review', [
            'words' => $words,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'word' => 'required_without:dictionary_word_id|string|max:100',
            'dictionary_word_id' => 'required_without:word|integer|exists:dictionary_words,id',
            'language_slug' => 'required_without:dictionary_word_id|string',
            'source' => 'string|in:exercise,manual,dictionary',
        ]);

        $user = $request->user();
        $source = $validated['source'] ?? 'manual';

        if ($request->has('dictionary_word_id')) {
            $dictWord = \App\Models\DictionaryWord::find($validated['dictionary_word_id']);
            $word = $dictWord->word;
            $language = $dictWord->language;
            $data = [
                'definitions' => [['definition' => $dictWord->definition]],
                'phonetics' => [[]],
                'audio' => [[]],
                'examples' => $dictWord->example ? [$dictWord->example] : [],
                'translation' => $dictWord->translation,
            ];
            $source = 'dictionary';
        } else {
            $word = $validated['word'];
            $language = $validated['language_slug'];
            // Fetch definition from dictionary AI service
            $data = $this->dictionary->lookup($word, $language);
        }

        $vocab = UserVocabulary::updateOrCreate(
            [
                'user_id' => $user->id,
                'word' => $word,
                'language_slug' => $language,
            ],
            [
                'definition' => $data['definitions'][0]['definition'] ?? null,
                'ipa' => $data['phonetics'][0]['ipa'] ?? null,
                'audio_url' => $data['audio'][0]['url'] ?? null,
                'examples' => array_slice($data['examples'] ?? [], 0, 3),
                'source' => $source,
                'next_review_at' => now()->addDay(),
            ]
        );

        return back()->with('success', 'Word added');
    }

    // POST /vocabulary/{vocab}/review - submit review result
    public function submitReview(Request $request, UserVocabulary $vocab)
    {
        $validated = $request->validate([
            'quality' => 'required|integer|min:0|max:5',
        ]);

        $vocab->updateSpacedRepetition($validated['quality']);

        return response()->json(['success' => true]);
    }
}
