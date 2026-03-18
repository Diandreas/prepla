<?php

namespace App\Services\AI;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseType;

class ExerciseGeneratorService
{
    public function generate(ExerciseType $exerciseType, Exam $exam, string $difficulty = 'B1'): Exercise
    {
        $prompt = $this->buildPrompt($exerciseType, $exam, $difficulty);

        // For now, generate a mock exercise. Replace with Mistral API call later.
        $content = $this->generateMockContent($exerciseType, $exam);
        $questions = $this->generateMockQuestions($exerciseType);

        return Exercise::create([
            'exercise_type_id' => $exerciseType->id,
            'exam_id' => $exam->id,
            'content' => $content,
            'questions' => $questions,
            'difficulty' => $difficulty,
            'xp_reward' => $this->calculateXpReward($difficulty),
            'is_ai_generated' => true,
        ]);
    }

    private function buildPrompt(ExerciseType $exerciseType, Exam $exam, string $difficulty): string
    {
        return "Generate a {$exerciseType->name} exercise for {$exam->name} at {$difficulty} level. "
            . "Return JSON with 'content' and 'questions' fields.";
    }

    private function generateMockContent(ExerciseType $exerciseType, Exam $exam): array
    {
        $passages = [
            'The rapid advancement of technology has transformed the way we communicate. Social media platforms have connected billions of people worldwide, enabling instant communication across continents. However, this digital revolution has also raised concerns about privacy, misinformation, and the impact on mental health.',
            'Climate change remains one of the most pressing challenges of our time. Rising global temperatures are causing more frequent extreme weather events, threatening biodiversity, and impacting food production. Scientists emphasize the need for immediate action to reduce greenhouse gas emissions.',
            'Education systems around the world are evolving to meet the demands of the 21st century. Traditional classroom-based learning is being supplemented with online courses, interactive tools, and personalized learning paths powered by artificial intelligence.',
        ];

        return [
            'passage' => $passages[array_rand($passages)],
            'instructions' => "Read the passage and answer the following questions.",
        ];
    }

    private function generateMockQuestions(ExerciseType $exerciseType): array
    {
        $componentKey = $exerciseType->component_key;

        return match ($componentKey) {
            'mcq' => [
                [
                    'id' => 'q1',
                    'type' => 'mcq',
                    'text' => 'What is the main topic of the passage?',
                    'options' => ['Technology and communication', 'Sports and entertainment', 'Cooking techniques', 'Space exploration'],
                    'correct_answer' => 'Technology and communication',
                    'explanation' => 'The passage primarily discusses how technology has transformed communication.',
                ],
                [
                    'id' => 'q2',
                    'type' => 'mcq',
                    'text' => 'According to the passage, what concern has the digital revolution raised?',
                    'options' => ['Lower internet speeds', 'Privacy concerns', 'Higher costs', 'Fewer jobs'],
                    'correct_answer' => 'Privacy concerns',
                    'explanation' => 'The passage mentions concerns about privacy, misinformation, and mental health.',
                ],
                [
                    'id' => 'q3',
                    'type' => 'mcq',
                    'text' => 'How many people are connected through social media?',
                    'options' => ['Millions', 'Billions', 'Thousands', 'Hundreds'],
                    'correct_answer' => 'Billions',
                    'explanation' => 'The passage states that social media platforms have connected billions of people worldwide.',
                ],
            ],
            'true-false-ng' => [
                [
                    'id' => 'q1',
                    'type' => 'true-false-ng',
                    'text' => 'Social media has connected millions of people.',
                    'correct_answer' => 'False',
                    'explanation' => 'The passage says billions, not millions.',
                ],
                [
                    'id' => 'q2',
                    'type' => 'true-false-ng',
                    'text' => 'The digital revolution has raised privacy concerns.',
                    'correct_answer' => 'True',
                    'explanation' => 'This is directly stated in the passage.',
                ],
                [
                    'id' => 'q3',
                    'type' => 'true-false-ng',
                    'text' => 'Social media was invented in the 1990s.',
                    'correct_answer' => 'Not Given',
                    'explanation' => 'The passage does not mention when social media was invented.',
                ],
            ],
            'gap-fill' => [
                [
                    'id' => 'q1',
                    'type' => 'gap-fill',
                    'text' => 'Technology has transformed the way we ___.',
                    'correct_answer' => 'communicate',
                    'explanation' => 'The passage states technology has transformed how we communicate.',
                ],
                [
                    'id' => 'q2',
                    'type' => 'gap-fill',
                    'text' => 'Social media enables instant communication across ___.',
                    'correct_answer' => 'continents',
                    'explanation' => 'The passage mentions communication across continents.',
                ],
            ],
            default => [
                [
                    'id' => 'q1',
                    'type' => 'mcq',
                    'text' => 'What is the main idea of this passage?',
                    'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                    'correct_answer' => 'Option A',
                    'explanation' => 'This is a sample question.',
                ],
            ],
        };
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
