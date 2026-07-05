<?php

namespace App\Services\AI;

class WritingCorrectorService
{
    protected MistralService $mistral;

    public function __construct(MistralService $mistral)
    {
        $this->mistral = $mistral;
    }

    public function correct(string $text, string $taskDescription, string $examType = 'IELTS', string $feedbackLanguage = 'Français', ?string $cefrLevel = null): array
    {
        $levelCalibration = $cefrLevel
            ? "\n\nIMPORTANT — level calibration:\n- The student's CEFR level is {$cefrLevel}. Calibrate your expectations and scoring to what a {$cefrLevel} learner should realistically produce — do not penalise for advanced structures/vocabulary they are not expected to master yet.\n"
            : '';

        $systemPrompt = "You are an expert $examType examiner. Evaluate the user's writing text based on the standard $examType grading criteria.
Task description: $taskDescription{$levelCalibration}

IMPORTANT — language of feedback:
- Write ALL feedback, explanations and the `explanation` of every correction in {$feedbackLanguage} (the learner's native language). Do NOT write the feedback in English unless {$feedbackLanguage} is English.
- Keep the `original` and `corrected` fields in the SAME language as the learner's text (do not translate the text itself).

IMPORTANT — corrections (for inline highlighting):
- The `corrections` array MUST list EVERY concrete mistake: spelling, grammar, word form, punctuation, capitalisation, word choice.
- Each `original` MUST be an EXACT substring copied verbatim from the learner's text (so it can be located and highlighted). Never paraphrase it.
- `corrected` is the fixed version of that exact span.
- If the text is short, still return at least one correction per mistake found. Return an empty array ONLY if the text is genuinely error-free.

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
            \"original\": \"exact substring from the learner's text\",
            \"corrected\": \"corrected version of that span\",
            \"explanation\": \"why it was corrected, in {$feedbackLanguage}\"
        }
    ],
    \"feedback\": \"Detailed overall feedback (strengths and weaknesses), written in {$feedbackLanguage}.\"
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
