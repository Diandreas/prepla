<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\LearningPathNode;
use App\Models\UserExerciseAttempt;
use App\Models\UserLearningProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExerciseController extends Controller
{
    public function submitSession(Request $request, LearningPathNode $node)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'answers' => 'required|array',
        ]);

        // 1. Marquer le nœud comme complété
        $progress = UserLearningProgress::where('user_id', $user->id)
            ->where('node_id', $node->id)
            ->first();

        if ($progress) {
            $progress->update([
                'status' => 'completed',
                'exercises_done' => $progress->exercises_required,
                'exercises_required' => 3
            ]);

            // 2. Débloquer le nœud suivant dans le syllabus
            $nextNode = LearningPathNode::where('exam_id', $node->exam_id)
                ->where(function($query) use ($node) {
                    $query->where('chapter_order', '>', $node->chapter_order)
                          ->orWhere(function($q) use ($node) {
                              $q->where('chapter_order', $node->chapter_order)
                                ->where('sort_order', '>', $node->sort_order);
                          });
                })
                ->orderBy('chapter_order')
                ->orderBy('sort_order')
                ->first();

            if ($nextNode) {
                UserLearningProgress::updateOrCreate(
                    ['user_id' => $user->id, 'node_id' => $nextNode->id],
                    ['status' => 'available']
                );
            }
        }

        // 3. Ajouter de l'XP à l'utilisateur
        $xpEarned = $node->xp_reward ?? 50;
        $user->profile?->increment('xp_total', $xpEarned);

        return redirect()->route('dashboard')->with('success', "Session terminée ! +{$xpEarned} XP");
    }

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

        // Update node progress if this exercise came from a node
        $nodeCompleted = false;
        $nodeId = session('current_node_id');
        if ($nodeId) {
            $userId = auth()->id();
            $progress = UserLearningProgress::where('user_id', $userId)
                ->where('node_id', $nodeId)
                ->whereIn('status', ['in_progress', 'available'])
                ->first();

            if ($progress) {
                $progress->increment('exercises_done');
                $progress->refresh();

                if ($progress->exercises_done >= $progress->exercises_required) {
                    $progress->update(['status' => 'completed']);
                    $nodeCompleted = true;

                    // Unlock the next node for this user
                    $node = LearningPathNode::find($nodeId);
                    if ($node) {
                        $nextNode = LearningPathNode::where('exam_id', $node->exam_id)
                            ->where('sort_order', '>', $node->sort_order)
                            ->orderBy('sort_order')
                            ->first();

                        if ($nextNode) {
                            UserLearningProgress::where('user_id', $userId)
                                ->where('node_id', $nextNode->id)
                                ->where('status', 'locked')
                                ->update(['status' => 'available']);
                        }
                    }

                    session()->forget('current_node_id');
                    session(['last_node_id' => $nodeId]);
                }
            }
        }

        return redirect()->route('exercise.result', [
            'attempt' => $attempt,
            'node_completed' => $nodeCompleted ? 1 : 0,
            'node_id' => $nodeId,
        ]);
    }

    public function result(UserExerciseAttempt $attempt, \Illuminate\Http\Request $request): Response
    {
        $attempt->load(['exercise.exerciseType.section', 'exercise.exam.language']);

        // Load node progress if applicable
        $nodeProgress = null;
        $nodeId = $request->query('node_id') ?: session('last_node_id');
        $nodeCompleted = (bool) $request->query('node_completed', 0);

        if ($nodeId) {
            $progress = UserLearningProgress::where('user_id', auth()->id())
                ->where('node_id', $nodeId)
                ->first();
            if ($progress) {
                $nodeProgress = [
                    'node_id' => $nodeId,
                    'exercises_done' => $progress->exercises_done,
                    'exercises_required' => $progress->exercises_required,
                    'completed' => $nodeCompleted,
                ];
            }
        }

        return Inertia::render('exercise/result', [
            'attempt' => $attempt,
            'nodeProgress' => $nodeProgress,
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

            // Essay/speaking: no fixed correct answer — give base credit for any submission
            $questionType = $question['type'] ?? '';
            if (in_array($questionType, ['essay', 'speaking', 'writing']) || $correctAnswer === null) {
                $isCorrect = !empty(trim((string)$userAnswer));
                $feedback[] = [
                    'question_id' => $questionId,
                    'correct' => $isCorrect,
                    'correct_answer' => null,
                    'explanation' => $question['prompt'] ?? null,
                ];
                if ($isCorrect) $correct++;
                continue;
            }

            $isCorrect = false;
            if (is_array($correctAnswer)) {
                $isCorrect = is_array($userAnswer) && empty(array_diff($correctAnswer, $userAnswer)) && empty(array_diff($userAnswer, $correctAnswer));
            } else {
                $normalUser = strtolower(trim((string)$userAnswer));
                $normalCorrect = strtolower(trim((string)$correctAnswer));
                $isCorrect = $normalUser === $normalCorrect;

                // If user answered a single letter (A-D) but correct_answer is full text,
                // match by checking if the letter corresponds to the correct option index
                if (!$isCorrect && preg_match('/^[a-d]$/', $normalUser) && strlen($normalCorrect) > 2) {
                    $options = $question['options'] ?? [];
                    $letterIndex = ord($normalUser) - ord('a');
                    if (isset($options[$letterIndex])) {
                        $isCorrect = strtolower(trim($options[$letterIndex])) === $normalCorrect;
                    }
                }

                // Also handle reverse: correct_answer is a letter, user submitted full text
                if (!$isCorrect && preg_match('/^[a-d]$/', $normalCorrect) && strlen($normalUser) > 2) {
                    $options = $question['options'] ?? [];
                    $letterIndex = ord($normalCorrect) - ord('a');
                    if (isset($options[$letterIndex])) {
                        $isCorrect = strtolower(trim($options[$letterIndex])) === $normalUser;
                    }
                }
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
