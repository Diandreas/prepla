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

    // POST /vocabulary - add a word
    public function store(Request $request)
    {
        $validated = $request->validate([
            'word' => 'required|string|max:100',
            'language_slug' => 'required|string',
            'source' => 'string|in:exercise,manual,dictionary',
        ]);

        $user = $request->user();

        // Fetch definition from dictionary
        $data = $this->dictionary->lookup($validated['word'], $validated['language_slug']);

        $vocab = UserVocabulary::updateOrCreate(
            [
                'user_id' => $user->id,
                'word' => $validated['word'],
                'language_slug' => $validated['language_slug'],
            ],
            [
                'definition' => $data['definitions'][0]['definition'] ?? null,
                'ipa' => $data['phonetics'][0]['ipa'] ?? null,
                'audio_url' => $data['audio'][0]['url'] ?? null,
                'examples' => array_slice($data['examples'] ?? [], 0, 3),
                'source' => $validated['source'] ?? 'manual',
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
