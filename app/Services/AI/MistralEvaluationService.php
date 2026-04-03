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
                'content' => "You are a language examiner. The target language is $language. Evaluate the student's answer based on relevancy, grammar, meaning, and vocabulary. Always reply in valid JSON format ONLY: {\"isCorrect\": boolean, \"accuracy\": integer (0-100), \"feedback\": string (brief corrections or feedback, written in French)}."
            ],
            [
                'role' => 'user',
                'content' => "Prompt/Question: $prompt\n\nStudent Answer: $userAnswer"
            ]
        ];

        $jsonString = $this->mistral->chat($messages);
        
        if (!$jsonString) {
            return ['isCorrect' => false, 'accuracy' => 0, 'feedback' => "L'IA n'a pas pu évaluer la réponse (timeout ou erreur)."];
        }

        $decoded = json_decode($jsonString, true);
        
        return [
            'isCorrect' => $decoded['isCorrect'] ?? false,
            'accuracy' => (int)($decoded['accuracy'] ?? 0),
            'feedback' => $decoded['feedback'] ?? "Aucun retour détaillé.",
        ];
    }

    /**
     * Explains a mistake conceptually.
     */
    public function explainMistake(string $prompt, string $userAnswer, string $correctAnswer, string $language): string
    {
        $systemPrompt = "You are an expert language teacher ($language). A student made a mistake in an exercise. 
        Instead of just giving the answer, explain the underlying CONCEPT (grammar, vocabulary, syntax) so they understand WHY it was wrong and how to improve.
        Keep it concise, encouraging, and in the target language ($language). Always provide a French translation of the explanation at the end.";
        $userPrompt = "Exercise context/prompt: $prompt\nStudent's wrong answer: $userAnswer\nCorrect answer: $correctAnswer\n\nPlease explain the concept briefly.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];

        try {
            return $this->mistral->chat($messages);
        } catch (\Exception $e) {
            return "Une erreur est survenue lors de l'explication.";
        }
    }
}
