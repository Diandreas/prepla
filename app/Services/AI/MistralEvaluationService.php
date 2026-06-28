<?php

namespace App\Services\AI;

class MistralEvaluationService
{
    private MistralService $mistral;

    public function __construct(MistralService $mistral)
    {
        $this->mistral = $mistral;
    }

    public function evaluate(string $prompt, string $userAnswer, string $language = 'German'): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => "You are a language examiner. The target language is $language. Evaluate the student's answer based on relevancy, grammar, meaning, and vocabulary. Always reply in valid JSON format ONLY:
{
  \"isCorrect\": boolean,
  \"accuracy\": integer (0-100),
  \"error_category\": \"category.subcategory (e.g. grammar.tense, vocabulary.lexical, spelling, etc.) — only if the answer is wrong, null if correct\",
  \"error_subcategory\": \"specific subcategory (e.g. past_simple_vs_present_perfect, article_usage) — only if wrong, null if correct\",
  \"explanation\": {
    \"concept\": \"brief explanation of the grammar/vocab rule, written in $language\",
    \"evidence\": \"the correct form or sentence — wrap the key corrected part in <evidence>...</evidence> tags\",
    \"hint\": \"one short actionable tip in $language\",
    \"french_translation\": {
      \"concept\": \"same concept explanation in French\",
      \"evidence\": \"same evidence in French\",
      \"hint\": \"same hint in French\"
    }
  }
}

ERROR CATEGORIES TAXONOMY (use these exact categories):
- grammar.tense (verb tense errors: past_simple, present_perfect, future, etc.)
- grammar.agreement (subject-verb agreement, gender agreement, etc.)
- grammar.word-order (incorrect word placement)
- grammar.article (a/an/the/zero article errors)
- grammar.preposition (wrong preposition)
- grammar.modal (modal verb errors)
- grammar.pronoun (pronoun reference errors)
- vocabulary.lexical (wrong word choice)
- vocabulary.register (wrong formality level)
- vocabulary.collocation (unnatural word combination)
- spelling (orthographic errors)
- punctuation (punctuation errors)
- coherence (logical flow issues)
- pragmatic (inappropriate for context)
- listening.detail (missed specific information)
- listening.inference (failed to infer meaning)
- reading.skim (failed to get the gist)
- reading.scan (failed to locate specific info)
- speaking.pronunciation (pronunciation issues)
- speaking.fluency (hesitation, breaks)
- speaking.accuracy (grammatical errors in speech)
- writing.structure (poor essay/text organization)
- writing.cohesion (weak linking between ideas)"
            ],
            [
                'role' => 'user',
                'content' => "Prompt/Question: $prompt\n\nStudent Answer: $userAnswer"
            ]
        ];

        $jsonString = $this->mistral->chat($messages);

        if (!$jsonString) {
            return [
                'isCorrect' => false,
                'accuracy' => 0,
                'error_category' => null,
                'error_subcategory' => null,
                'explanation' => [
                    'concept' => "L'IA n'a pas pu évaluer la réponse.",
                    'evidence' => '',
                    'hint' => '',
                    'french_translation' => ['concept' => "L'IA n'a pas pu évaluer la réponse.", 'evidence' => '', 'hint' => ''],
                ],
            ];
        }

        $decoded = json_decode($jsonString, true);

        return [
            'isCorrect' => $decoded['isCorrect'] ?? false,
            'accuracy' => (int)($decoded['accuracy'] ?? 0),
            'error_category' => $decoded['error_category'] ?? null,
            'error_subcategory' => $decoded['error_subcategory'] ?? null,
            'explanation' => $decoded['explanation'] ?? [
                'concept' => "Aucun retour détaillé.",
                'evidence' => '',
                'hint' => '',
                'french_translation' => ['concept' => 'Aucun retour détaillé.', 'evidence' => '', 'hint' => ''],
            ],
        ];
    }

    /**
     * Formative evaluation for SPEAKING answers (transcribed from the learner's
     * audio). Unlike the strict evaluate(), this is encouraging: a relevant answer
     * that covers the task passes at >= 50%, and we ALWAYS return concrete
     * "what you did well" + "how to go further" tips so the learner can continue.
     *
     * @param string $transcript  The speech-to-text of what the learner said.
     * @param string $prompt      The speaking task / what they were asked to say.
     */
    public function evaluateSpeaking(string $prompt, string $transcript, string $language = 'German', array $expectedPoints = []): array
    {
        // When a teacher (B2B center) authored explicit expected points, evaluate
        // coverage against THOSE points specifically, not just generic task points.
        $expectedBlock = '';
        if (! empty($expectedPoints)) {
            $list = implode("\n", array_map(fn ($p) => '- ' . $p, $expectedPoints));
            $expectedBlock = "\n\nThe response is expected to cover these specific points (evaluate covered_points / missing_points against THIS list):\n{$list}";
        }

        $messages = [
            [
                'role' => 'system',
                'content' => "You are a friendly speaking examiner for $language. You receive the TRANSCRIPT of what a student said out loud (speech-to-text — minor transcription noise is normal, do NOT penalise spelling/punctuation).

Evaluate FORMATIVELY and ENCOURAGINGLY:
- Did the student address the task and produce relevant content in $language?
- Did they cover the key points the task asked for?
- Is it broadly understandable (don't over-penalise small grammar slips)?

SCORING: a relevant attempt that covers the task should score 50-100. Reserve scores below 50 for off-topic, empty, wrong-language, or unintelligible answers. PASS threshold is 50: set isCorrect=true when accuracy >= 50.

ALWAYS, even when the answer passes, give continuation help: what they said well, and 1-2 concrete things to add/improve to go further (richer vocabulary, a connector, a missing point).

Reply in valid JSON ONLY:
{
  \"isCorrect\": boolean,            // true if accuracy >= 50
  \"accuracy\": integer 0-100,
  \"covered_points\": [\"...\"],      // task points the student covered
  \"missing_points\": [\"...\"],      // task points still missing (can be empty)
  \"error_category\": \"speaking.fluency|speaking.accuracy|vocabulary.lexical|null\",
  \"error_subcategory\": \"string|null\",
  \"explanation\": {
    \"concept\": \"1-2 sentences in $language: what was good + the main thing to improve\",
    \"evidence\": \"a better/example phrase in $language, key part in <evidence>...</evidence>\",
    \"hint\": \"one concrete tip in $language to go further\",
    \"french_translation\": { \"concept\": \"...in French\", \"evidence\": \"...in French\", \"hint\": \"...in French\" }
  }
}"
            ],
            [
                'role' => 'user',
                'content' => "Speaking task: $prompt{$expectedBlock}\n\nStudent transcript: $transcript"
            ]
        ];

        $jsonString = $this->mistral->chat($messages);

        $fallback = [
            'isCorrect' => false,
            'accuracy' => 0,
            'covered_points' => [],
            'missing_points' => [],
            'error_category' => null,
            'error_subcategory' => null,
            'explanation' => [
                'concept' => "L'IA n'a pas pu évaluer ta réponse orale.",
                'evidence' => '',
                'hint' => 'Réessaie en parlant un peu plus longtemps et clairement.',
                'french_translation' => ['concept' => "L'IA n'a pas pu évaluer ta réponse orale.", 'evidence' => '', 'hint' => 'Réessaie en parlant un peu plus longtemps et clairement.'],
            ],
        ];

        if (!$jsonString) return $fallback;
        $decoded = json_decode($jsonString, true);
        if (!is_array($decoded)) return $fallback;

        $accuracy = (int)($decoded['accuracy'] ?? 0);
        return [
            // Hard rule: >= 50 always validates, regardless of what the model put in isCorrect.
            'isCorrect' => $accuracy >= 50,
            'accuracy' => $accuracy,
            'covered_points' => $decoded['covered_points'] ?? [],
            'missing_points' => $decoded['missing_points'] ?? [],
            'error_category' => $decoded['error_category'] ?? null,
            'error_subcategory' => $decoded['error_subcategory'] ?? null,
            'explanation' => $decoded['explanation'] ?? $fallback['explanation'],
        ];
    }

    /**
     * Explains a mistake conceptually.
     */
    public function explainMistake(string $prompt, string $userAnswer, string $correctAnswer, string $language): string
    {
        $systemPrompt = "You are an expert language teacher ($language). A student made a mistake in an exercise. 
        Instead of just giving the answer, explain the underlying CONCEPT (grammar, vocabulary, syntax) so they understand WHY it was wrong and how to improve.
        IMPORTANT: In your explanation, if there is a specific sentence in the provided context/passage that proves the correct answer, you MUST enclose that exact sentence in <evidence>...</evidence> tags within your explanation.
        Keep it concise, encouraging, and in the target language ($language). Always provide a French translation of the explanation at the end.";
        $userPrompt = "Exercise context/prompt: $prompt\nStudent's wrong answer: $userAnswer\nCorrect answer: $correctAnswer\n\nPlease explain the concept briefly and identify the source evidence if applicable.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];

        try {
            // chatRaw → no json_object response_format (explanation is free text with <evidence> tags)
            $response = $this->mistral->chatRaw($messages);
            return $response ?: "Une erreur est survenue lors de l'explication.";
        } catch (\Exception $e) {
            return "Une erreur est survenue lors de l'explication.";
        }
    }
}
