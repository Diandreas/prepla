<?php

namespace App\Services\Content;

use App\Models\Lesson;

/** Structural checks are language-independent, not a substitute for teacher review. */
class LessonQuizQuality
{
    public function normalize(array $quiz): array
    {
        return array_map(function ($q) {
            if (! is_array($q)) return [];
            // L'enonce est LU, pas transtype : rendu en objet, le transtypage levait
            // « Array to string conversion » et la lecon ne s'ouvrait plus du tout.
            $q['question'] = trim(preg_replace('/\s*\[\s*\]\s*$/u', '', \App\Models\Exercise::optionText($q['question'] ?? '')));
            $options = is_array($q['options'] ?? null) ? $q['options'] : [];
            if (empty($q['type'])) {
                $q['type'] = count($options) ? 'mcq' : 'recall';
                // Legacy word banks were incorrectly stored as multiple-choice options.
                if ($options && is_string($q['correct_answer'] ?? null)
                    && ! in_array(Lesson::resolveCorrectAnswerText($q), $options, true)
                    && count($options) >= 3
                    && collect($options)->every(fn ($word) => is_string($word) && ! preg_match('/\s/u', trim($word)))) {
                    $q['type'] = 'sentence-order';
                    $q['words'] = $options;
                    $options = [];
                }
            }
            $q['options'] = $options;
            return $q;
        }, $quiz);
    }

    public function valid(array $quiz): bool
    {
        if (! $quiz) return false;
        foreach ($this->normalize($quiz) as $q) {
            if (empty($q['question']) || ! is_string($q['correct_answer'] ?? null) || trim($q['correct_answer']) === '') return false;
            $options = $q['options'];
            if ($q['type'] === 'mcq') {
                if (count($options) < 2 || ! collect($options)->every(fn ($o) => is_string($o) && trim($o) !== '')) return false;
                $normalized = array_map(fn ($s) => mb_strtolower(trim($s)), $options);
                if (count(array_unique($normalized)) !== count($options) || ! in_array(Lesson::resolveCorrectAnswerText($q), $options, true)) return false;
            } elseif ($q['type'] === 'sentence-order') {
                $words = $q['words'] ?? [];
                if (! is_array($words) || count($words) < 2 || ! collect($words)->every(fn ($w) => is_string($w) && trim($w) !== '')) return false;
                $tokens = function ($text) {
                    $tokens = preg_split('/\s+/u', mb_strtolower(trim($text, " \t\n\r.!?。！？")), -1, PREG_SPLIT_NO_EMPTY);
                    sort($tokens);
                    return $tokens;
                };
                if ($options || $tokens(implode(' ', $words)) !== $tokens($q['correct_answer'])) return false;
            } elseif ($q['type'] !== 'recall' || $options) {
                return false;
            }
        }
        return true;
    }
}
