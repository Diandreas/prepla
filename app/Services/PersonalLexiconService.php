<?php

namespace App\Services;

use App\Http\Controllers\DictionaryController;
use App\Models\DictionaryWord;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserVocabulary;
use App\Models\UserWordProgress;

class PersonalLexiconService
{
    public function language(User $user): string
    {
        return DictionaryController::languageCode($user->profile?->targetExam?->language?->slug);
    }

    public function due(User $user)
    {
        $language = $this->language($user);

        return UserWordProgress::dueForReview($user->id)
            ->whereHas('dictionaryWord', fn ($q) => $q->where('language', $language));
    }

    // Non-destructive bridge: old saved words stay intact; there is one visible lexicon.
    public function importLegacy(User $user): void
    {
        foreach (UserVocabulary::where('user_id', $user->id)->get() as $legacy) {
            $word = $this->word($legacy->word, DictionaryController::languageCode($legacy->language_slug), [
                'definition' => $legacy->definition ?? '', 'example' => $legacy->examples[0] ?? '',
                'translation' => '', 'skill_level' => null,
            ]);
            UserWordProgress::firstOrCreate(['user_id' => $user->id, 'dictionary_word_id' => $word->id],
                ['status' => 'discovered', 'next_review_at' => $legacy->next_review_at]);
        }
    }

    public function word(string $text, string $language, array $data): DictionaryWord
    {
        // Legacy words have no assessed CEFR level; let the schema default apply.
        if (! isset($data['skill_level']) || trim((string) $data['skill_level']) === '') {
            unset($data['skill_level']);
        }
        return DictionaryWord::where('language', $language)->whereRaw('LOWER(word) = ?', [mb_strtolower(trim($text))])->first()
            ?? DictionaryWord::create(['word' => trim($text), 'language' => $language] + $data);
    }

    public function lessonWords(User $user, Lesson $lesson): array
    {
        $items = collect($lesson->key_vocabulary ?? [])->filter(fn ($item) => is_array($item)
            && is_string($item['word'] ?? null) && mb_strlen(trim($item['word'])) > 0
            && mb_strlen($item['word']) <= 100 && is_string($item['translation'] ?? null)
            && is_string($item['example'] ?? null)
            && mb_stripos($item['example'], trim($item['word'])) !== false
            && mb_stripos((string) $lesson->theory_markdown, trim($item['word'])) !== false)->take(5);
        $language = $this->language($user);

        return $items->map(function ($item) use ($user, $lesson, $language) {
            $word = $this->word($item['word'], $language, [
                'definition' => $item['definition'] ?? $item['translation'],
                'translation' => $item['translation'], 'example' => $item['example'],
                'skill_level' => $lesson->node?->level ?? $user->profile?->current_level ?? 'A1',
            ]);

            return ['id' => $word->id, 'word' => $word->word, 'translation' => $item['translation'],
                'example' => $item['example'], 'saved' => UserWordProgress::where('user_id', $user->id)
                    ->where('dictionary_word_id', $word->id)->exists()];
        })->values()->all();
    }
}
