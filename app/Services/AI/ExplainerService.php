<?php

namespace App\Services\AI;

class ExplainerService
{
    protected MistralService $mistral;

    public function __construct(MistralService $mistral)
    {
        $this->mistral = $mistral;
    }

    public function explain(string $question, string $context = '', string $language = 'English'): string
    {
        $systemPrompt = "You are a helpful AI language tutor for a test prep application. Answer the user's questions about grammar, vocabulary, exam strategies, and language learning. Respond concisely and effectively in French or the user's language.";
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Context: {$context}\nQuestion: {$question}"]
        ];
        $response = $this->mistral->chatRaw($messages);
        return $response ?? "Désolé, je n'ai pas pu me connecter à l'API Mistral pour le moment.";
    }

    public function chat(array $messages): string
    {
        $systemPrompt = "You are a helpful AI language tutor for a test prep application. Answer the user's questions about grammar, vocabulary, exam strategies, and language learning. Respond concisely and effectively in French or the user's language.";
        $apiMessages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($messages as $msg) {
            $apiMessages[] = $msg;
        }
        $response = $this->mistral->chatRaw($apiMessages);
        return $response ?? "Désolé, je n'ai pas pu me connecter à l'API Mistral pour le moment.";
    }
}
