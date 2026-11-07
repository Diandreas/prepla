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

            // Mots a remettre en ordre ecrits DANS l'enonce (« heißt / du / Wie ? »)
            // au lieu d'une liste a part : l'IA le fait souvent. La question etait
            // rejetee, et avec elle toute la lecon. Les etiquettes se deduisent de
            // la bonne reponse, et la liste est retiree de l'enonce.
            if (($q['type'] ?? null) === 'sentence-order' && empty($q['words']) && is_string($q['correct_answer'] ?? null)) {
                $phrase = preg_replace('/[\s.!?。！？]+$/u', '', trim($q['correct_answer']));
                $mots = preg_split('/\s+/u', $phrase, -1, PREG_SPLIT_NO_EMPTY);
                if (count($mots) >= 2) {
                    // Un ordre melange mais stable : jamais celui de la reponse.
                    $q['words'] = array_merge(array_slice($mots, 1), [$mots[0]]);
                    // « … une phrase correcte : heißt / du / Wie ? » -> « … une phrase correcte. »
                    $sansListe = preg_replace('/\s*[:：]\s*[«"“]?[^:«»"“”]*\/[^:«»"“”]*[»"”]?\s*[?!.]?\s*$/u', '', $q['question']);
                    if (is_string($sansListe) && trim($sansListe) !== '') {
                        $q['question'] = rtrim(trim($sansListe), ' .').'.';
                    }
                }
            }

            return $q;
        }, $quiz);
    }

    /**
     * Les seules questions jouables. Une question mal formee n'invalide plus
     * toute la lecon : on la retire, tant qu'il en reste assez pour un quiz.
     */
    public function questionsValides(array $quiz): array
    {
        return array_values(array_filter($this->normalize($quiz), fn ($q) => $this->valid([$q])));
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
