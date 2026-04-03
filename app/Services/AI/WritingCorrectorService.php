<?php

namespace App\Services\AI;

class WritingCorrectorService
{
    protected MistralService $mistral;

    public function __construct(MistralService $mistral)
    {
        $this->mistral = $mistral;
    }

    public function correct(string $text, string $taskDescription, string $examType = 'IELTS'): array
    {
        $systemPrompt = "You are an expert $examType examiner. Evaluate the user's writing text based on the standard $examType grading criteria.
Task description: $taskDescription
Output your evaluation as a JSON object with the following structure:
{
    \"score\": 0.0, // overall band score 1-9
    \"band_scores\": {
        \"task_achievement\": 0.0,
        \"coherence_cohesion\": 0.0,
        \"lexical_resource\": 0.0,
        \"grammar_accuracy\": 0.0
    },
    \"corrections\": [
        {
            \"original\": \"incorrect string\",
            \"corrected\": \"corrected string\",
            \"explanation\": \"why it was corrected\"
        }
    ],
    \"feedback\": \"Detailed overall feedback about strengths and weaknesses.\"
}";

        $response = $this->mistral->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $text]
        ]);

        $wordCount = str_word_count($text);

        if ($response) {
            $data = json_decode($response, true);
            if ($data) {
                $data['word_count'] = $wordCount;
                return $data;
            }
        }

        return [
            'score' => 0,
            'band_scores' => [
                'task_achievement' => 0,
                'coherence_cohesion' => 0,
                'lexical_resource' => 0,
                'grammar_accuracy' => 0,
            ],
            'corrections' => [],
            'feedback' => 'Erreur de connexion à Mistral AI. Veuillez réessayer.',
            'word_count' => $wordCount,
        ];
    }
}
