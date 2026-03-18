<?php

namespace App\Services\AI;

class WritingCorrectorService
{
    public function correct(string $text, string $taskDescription, string $examType = 'IELTS'): array
    {
        // Mock implementation - replace with Mistral API later
        $wordCount = str_word_count($text);
        $score = min(9, max(1, round($wordCount / 30)));

        return [
            'score' => $score,
            'band_scores' => [
                'task_achievement' => $score,
                'coherence_cohesion' => max(1, $score - 0.5),
                'lexical_resource' => max(1, $score - 0.5),
                'grammar_accuracy' => max(1, $score - 1),
            ],
            'corrections' => [
                [
                    'original' => '',
                    'corrected' => '',
                    'explanation' => 'This is a placeholder. Connect the Mistral API for real corrections.',
                ],
            ],
            'feedback' => 'This is mock feedback. Connect the Mistral API for detailed writing analysis.',
            'word_count' => $wordCount,
        ];
    }
}
