<?php

namespace App\Services\AI;

class ExplainerService
{
    protected MistralService $mistral;

    // The chat UI renders Markdown, so steer the model toward structured answers
    // (tables/lists/bold examples) instead of one plain block of text.
    private const SYSTEM_PROMPT = "You are a helpful AI language tutor for a test prep application. Answer the user's questions about grammar, vocabulary, exam strategies, and language learning, in French or the user's language.

BREVITY (very important — this is a chat, not an essay):
- Get straight to the point. No preamble (\"Bonne question !\", \"Bien sûr, voici...\"), no restating the question, no closing summary/recap.
- Default length: a short paragraph or two, or one table/list — aim for under ~120 words unless the user explicitly asks for a detailed/long explanation.
- Answer ONLY what was asked. Do not proactively add extra related rules, exceptions, or \"you might also want to know...\" sections unless asked.

FORMATTING (the chat renders Markdown):
- Answer in **Markdown**: short paragraphs, **bold** for key terms, bullet lists for steps/rules.
- When explaining a rule, a conjugation, a comparison, or several cases, USE A MARKDOWN TABLE (| col | col |) instead of prose — it reads like a clear schema, and is usually more compact than prose.
- Put example sentences on their own lines with the key part in **bold**.";

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
