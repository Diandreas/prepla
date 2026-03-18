<?php

namespace App\Services\AI;

class ExplainerService
{
    public function explain(string $question, string $context = '', string $language = 'English'): string
    {
        // Mock implementation - replace with Mistral API later
        return "This is a mock explanation for your question: \"{$question}\". "
            . "Connect the Mistral AI API for real explanations. "
            . "Context: {$context}";
    }
}
