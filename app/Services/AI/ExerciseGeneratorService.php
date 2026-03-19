<?php

namespace App\Services\AI;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseType;
use Illuminate\Support\Facades\Log;

class ExerciseGeneratorService
{
    public function __construct(protected MistralService $mistral) {}

    public function generate(ExerciseType $exerciseType, Exam $exam, string $difficulty = 'B1'): Exercise
    {
        $exam->loadMissing('language');
        $language = $exam->language?->name ?? 'English';
        $languageNative = $exam->language?->native_name ?? $language;

        $prompt = $this->buildPrompt($exerciseType, $exam, $difficulty, $language, $languageNative);

        $response = $this->mistral->chat([
            ['role' => 'system', 'content' => 'You are an expert language exam content creator. Always respond with valid JSON only.'],
            ['role' => 'user', 'content' => $prompt],
        ]);

        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['content'], $data['questions']) && count($data['questions']) > 0) {
                // Strip letter prefixes like "A) ", "B) " that Mistral sometimes adds to options
                $data['questions'] = array_map(function ($q) {
                    if (isset($q['options']) && is_array($q['options'])) {
                        $q['options'] = array_map(
                            fn($opt) => preg_replace('/^[A-D]\)\s*/u', '', $opt),
                            $q['options']
                        );
                    }
                    return $q;
                }, $data['questions']);

                return Exercise::create([
                    'exercise_type_id' => $exerciseType->id,
                    'exam_id' => $exam->id,
                    'content' => $data['content'],
                    'questions' => $data['questions'],
                    'difficulty' => $difficulty,
                    'xp_reward' => $this->calculateXpReward($difficulty),
                    'is_ai_generated' => true,
                ]);
            }
        }

        // Fallback to mock if AI fails
        Log::warning('ExerciseGenerator: AI failed, using mock content');
        return Exercise::create([
            'exercise_type_id' => $exerciseType->id,
            'exam_id' => $exam->id,
            'content' => $this->getFallbackContent($language),
            'questions' => $this->getFallbackQuestions($exerciseType->component_key, $language),
            'difficulty' => $difficulty,
            'xp_reward' => $this->calculateXpReward($difficulty),
            'is_ai_generated' => true,
        ]);
    }

    private function buildPrompt(ExerciseType $exerciseType, Exam $exam, string $difficulty, string $language, string $languageNative): string
    {
        $componentKey = $exerciseType->component_key;
        $skillType = $exerciseType->section?->skill_type ?? 'reading';

        $questionFormat = match ($componentKey) {
            'mcq' => 'Array of 3 questions, each with: id (string q1/q2/q3), type ("mcq"), text (question in ' . $language . '), options (array of exactly 4 answer choices in ' . $language . ', do NOT include letters like "A)" prefix), correct_answer (ONLY the letter: "A", "B", "C", or "D" corresponding to the correct option index), explanation (string in ' . $language . ')',
            'true-false-ng' => 'Array of 3 statements, each with: id (string), type ("true-false-ng"), text (statement in ' . $language . '), correct_answer ("True"/"False"/"Not Given"), explanation (string)',
            'gap-fill' => 'Array of 3 items, each with: id (string), type ("gap-fill"), text (sentence with ___ for blank, in ' . $language . '), correct_answer (string), explanation (string)',
            'matching' => 'Array of 4 questions, each with: id (string q1-q4), type ("matching"), text (the term/concept to identify in ' . $language . '), options (array of exactly 4 definitions in ' . $language . ' — one correct, three plausible distractors, shuffled), correct_answer (ONLY the letter "A", "B", "C", or "D" for the correct option)',
            default => 'Array of 3 questions with: id (string), type ("mcq"), text, options (4 choices), correct_answer, explanation',
        };

        return <<<PROMPT
Generate a {$exerciseType->name} exercise for the {$exam->name} exam at CEFR {$difficulty} level.
The exercise must be entirely in {$language} ({$languageNative}) — this is a {$language} language exam.
Skill tested: {$skillType}.

Return JSON with exactly this structure:
{
  "content": {
    "passage": "A {$difficulty}-level text in {$language} (150-200 words for reading, shorter for other skills)",
    "instructions": "Instructions in {$language}"
  },
  "questions": [{$questionFormat}]
}

Important: ALL text (passage, questions, options, explanations) must be in {$language}. No English unless it's an English exam.
PROMPT;
    }

    private function getFallbackContent(string $language): array
    {
        return [
            'passage' => "This is a sample passage for {$language} language practice.",
            'instructions' => 'Read the passage and answer the questions.',
        ];
    }

    private function getFallbackQuestions(string $componentKey, string $language): array
    {
        return [
            [
                'id' => 'q1',
                'type' => 'mcq',
                'text' => 'What is this exercise about?',
                'options' => ['Language practice', 'Sports', 'Cooking', 'Music'],
                'correct_answer' => 'Language practice',
                'explanation' => 'This is a language practice exercise.',
            ],
        ];
    }

    private function calculateXpReward(string $difficulty): int
    {
        return match ($difficulty) {
            'A1' => 5,
            'A2' => 8,
            'B1' => 10,
            'B2' => 15,
            'C1' => 20,
            'C2' => 25,
            default => 10,
        };
    }
}
