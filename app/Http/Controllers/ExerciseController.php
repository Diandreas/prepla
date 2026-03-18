<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExerciseController extends Controller
{
    public function show(Exercise $exercise): Response
    {
        $exercise->load(['exerciseType.section', 'exam.language']);

        return Inertia::render('exercise/show', [
            'exercise' => $exercise,
        ]);
    }

    public function submit(Request $request, Exercise $exercise)
    {
        $validated = $request->validate([
            'answers' => 'required|array',
            'time_spent' => 'required|integer|min:0',
        ]);

        // Score the exercise
        $result = $this->scoreExercise($exercise, $validated['answers']);

        $attempt = UserExerciseAttempt::create([
            'user_id' => auth()->id(),
            'exercise_id' => $exercise->id,
            'answers' => $validated['answers'],
            'score' => $result['score'],
            'accuracy_percent' => $result['accuracy'],
            'time_spent' => $validated['time_spent'],
            'xp_earned' => $result['xp'],
            'feedback' => $result['feedback'],
        ]);

        // Update user XP
        $profile = auth()->user()->profile;
        if ($profile) {
            $profile->increment('xp_total', $result['xp']);
        }

        return redirect()->route('exercise.result', $attempt);
    }

    public function result(UserExerciseAttempt $attempt): Response
    {
        $attempt->load(['exercise.exerciseType.section', 'exercise.exam.language']);

        return Inertia::render('exercise/result', [
            'attempt' => $attempt,
        ]);
    }

    private function scoreExercise(Exercise $exercise, array $answers): array
    {
        $questions = $exercise->questions;
        $correct = 0;
        $total = count($questions);
        $feedback = [];

        foreach ($questions as $index => $question) {
            $questionId = $question['id'] ?? (string)$index;
            $userAnswer = $answers[$questionId] ?? null;
            $correctAnswer = $question['correct_answer'] ?? null;

            $isCorrect = false;
            if (is_array($correctAnswer)) {
                $isCorrect = is_array($userAnswer) && empty(array_diff($correctAnswer, $userAnswer)) && empty(array_diff($userAnswer, $correctAnswer));
            } else {
                $isCorrect = strtolower(trim((string)$userAnswer)) === strtolower(trim((string)$correctAnswer));
            }

            if ($isCorrect) $correct++;

            $feedback[] = [
                'question_id' => $questionId,
                'correct' => $isCorrect,
                'correct_answer' => $correctAnswer,
                'explanation' => $question['explanation'] ?? null,
            ];
        }

        $accuracy = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
        $xp = (int) round(($accuracy / 100) * $exercise->xp_reward);

        return [
            'score' => $correct,
            'accuracy' => $accuracy,
            'xp' => $xp,
            'feedback' => $feedback,
        ];
    }
}
