<?php

namespace App\Services\AI;

class ExplainerService
{
    protected MistralService $mistral;

    // The chat UI renders Markdown, so steer the model toward structured answers
    // (tables/lists/bold examples) instead of one plain block of text.
    private const SYSTEM_PROMPT = "You are a helpful AI language tutor for a test prep application. Answer the user's questions about grammar, vocabulary, exam strategies, and language learning. Respond concisely and effectively in French or the user's language.

FORMATTING (very important — the chat renders Markdown):
- Always answer in **Markdown**, never one big block of plain text.
- Use short paragraphs, **bold** for key terms, and bullet lists for steps or rules.
- When explaining a rule, a conjugation, a comparison, or several cases, USE A MARKDOWN TABLE (| col | col |) instead of prose — it reads like a clear schema.
- Put example sentences on their own lines with the key part in **bold**.
- Keep it focused: a couple of short paragraphs + one table or list is ideal.";

    public function __construct(MistralService $mistral)
    {
        $this->mistral = $mistral;
    }

    public function explain(string $question, string $context = '', string $language = 'English'): string
    {
        $messages = [
            ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
            ['role' => 'user', 'content' => "Context: {$context}\nQuestion: {$question}"]
        ];
        $response = $this->mistral->chatRaw($messages);
        return $response ?? "Désolé, je n'ai pas pu me connecter à l'API Mistral pour le moment.";
    }

    public function chat(array $messages): string
    {
        $apiMessages = [['role' => 'system', 'content' => self::SYSTEM_PROMPT]];
        foreach ($messages as $msg) {
            $apiMessages[] = $msg;
        }
        $response = $this->mistral->chatRaw($apiMessages);
        return $response ?? "Désolé, je n'ai pas pu me connecter à l'API Mistral pour le moment.";
    }
}
