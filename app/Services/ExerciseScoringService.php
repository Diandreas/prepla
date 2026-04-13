<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\UserExerciseAttempt;
use App\Services\AI\MistralEvaluationService;
use App\Services\AI\WritingCorrectorService;
use App\Services\AI\DeepgramSttService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ExerciseScoringService
{
    protected MistralEvaluationService $mistralEval;
    protected WritingCorrectorService $writingCorrector;
    protected DeepgramSttService $stt;

    public function __construct(
        MistralEvaluationService $mistralEval,
        WritingCorrectorService $writingCorrector,
        DeepgramSttService $stt
    ) {
        $this->mistralEval = $mistralEval;
        $this->writingCorrector = $writingCorrector;
        $this->stt = $stt;
    }

    public function score(Exercise $exercise, array $answers): array
    {
        $questions = $exercise->questions;
        $correct = 0;
        $total = count($questions);
        $feedback = [];

        // Exercise types that require AI evaluation
        $aiEvaluatedTypes = [
            'essay', 'essay-editor', 'speaking', 'writing', 'short-writing', 
            'graph-description', 'academic-discussion', 'speaking-recorder', 
            'role-play', 'synthesis', 'integrated-task'
        ];

        foreach ($questions as $index => $question) {
            $questionId = $question['id'] ?? (string)$index;
            $userAnswer = $answers[$questionId] ?? null;
            $correctAnswer = $question['correct_answer'] ?? null;
            $questionType = $question['type'] ?? $exercise->exerciseType->slug ?? '';

            // ─── AI EVALUATED BRANCH ───
            if (in_array($questionType, $aiEvaluatedTypes)) {
                
                if (empty($userAnswer)) {
                    $feedback[] = $this->createEmptyFeedback($questionId);
                    continue;
                }

                $langName = $exercise->exam?->language?->name ?? 'English';
                $langSlug = $exercise->exam?->language?->slug ?? 'en';
                
                // Use specialized WritingCorrector for complex essays
                if (in_array($questionType, ['essay', 'essay-editor', 'integrated-task', 'synthesis'])) {
                    $textToEvaluate = $this->getTextToEvaluate($userAnswer, $langSlug);
                    if (empty($textToEvaluate)) {
                        $feedback[] = $this->createEmptyFeedback($questionId, $userAnswer instanceof UploadedFile);
                        continue;
                    }
                    
                    $aiResult = $this->writingCorrector->correct($textToEvaluate, $question['prompt'] ?? $question['text'] ?? "Write an essay", $exercise->exam?->name ?? 'IELTS');
                    
                    $points = ($aiResult['score'] ?? 0) / 9; // Normalize IELTS 1-9 to 0-1
                    $isCorrect = $points >= 0.6;
                    $accuracy = ($points * 100);
                    
                    $feedback[] = [
                        'question_id' => $questionId,
                        'correct' => $isCorrect,
                        'accuracy' => $accuracy,
                        'band_score' => $aiResult['score'] ?? 0,
                        'sub_scores' => $aiResult['band_scores'] ?? [],
                        'corrections' => $aiResult['corrections'] ?? [],
                        'explanation' => $aiResult['feedback'] ?? "Analyse effectuée.",
                        'transcription' => ($userAnswer instanceof UploadedFile) ? $textToEvaluate : null,
                    ];
                } else {
                    $textToEvaluate = $this->getTextToEvaluate($userAnswer, $langSlug);
                    if (empty($textToEvaluate)) {
                        $feedback[] = $this->createEmptyFeedback($questionId, $userAnswer instanceof UploadedFile);
                        continue;
                    }

                    // Use standard evaluation for others (short answers, speaking)
                    $prompt = $question['prompt'] ?? $question['text'] ?? "Respond to the prompt";
                    $aiResult = $this->mistralEval->evaluate($prompt, $textToEvaluate, $langName);

                    $isCorrect = (bool) $aiResult['isCorrect'];
                    $feedback[] = [
                        'question_id' => $questionId,
                        'correct' => $isCorrect,
                        'accuracy' => $aiResult['accuracy'],
                        'explanation' => $aiResult['explanation'],
                        'transcription' => ($userAnswer instanceof UploadedFile) ? $textToEvaluate : null,
                    ];
                }

                if ($isCorrect) $correct++;
                continue;
            }

            // ─── RECORD-BASED OR MULTI-FIELD BRANCH ───
            $correctAnswers = $question['correct_answers'] ?? null;
            if (is_array($correctAnswers) && is_array($userAnswer) && !array_is_list($correctAnswers)) {
                $fieldCorrect = 0;
                $fieldTotal = count($correctAnswers);
                foreach ($correctAnswers as $key => $expected) {
                    $given = $userAnswer[$key] ?? '';
                    if (strtolower(trim((string)$given)) === strtolower(trim((string)$expected))) {
                        $fieldCorrect++;
                    }
                }
                $accuracy = $fieldTotal > 0 ? ($fieldCorrect / $fieldTotal) * 100 : 0;
                $isCorrect = $accuracy >= 70; // 70% threshold for "correct" mark
                
                if ($isCorrect) $correct++;
                
                $feedback[] = [
                    'question_id' => $questionId,
                    'correct' => $isCorrect,
                    'accuracy' => $accuracy,
                    'correct_answer' => $correctAnswers,
                    'explanation' => $question['explanation'] ?? null,
                ];
                continue;
            }

            // ─── STANDARD EXACT MATCH BRANCH ───
            $isCorrect = false;
            if (is_array($correctAnswer)) {
                $isCorrect = is_array($userAnswer) && empty(array_diff($correctAnswer, $userAnswer)) && empty(array_diff($userAnswer, $correctAnswer));
            } else {
                $normalUser = strtolower(trim((string)$userAnswer));
                $normalCorrect = strtolower(trim((string)$correctAnswer));
                $isCorrect = $normalUser === $normalCorrect;

                // Index-based matching fallback
                if (!$isCorrect && preg_match('/^[a-d]$/', $normalUser) && isset($question['options'])) {
                    $options = $question['options'] ?? [];
                    $letterIndex = ord($normalUser) - ord('a');
                    if (isset($options[$letterIndex])) {
                        $isCorrect = strtolower(trim($options[$letterIndex])) === $normalCorrect;
                    }
                }
            }

            if (!$isCorrect) {
                 // Try to get a conceptual explanation
                 $explanation = $question['explanation'] ?? null;
                 
                 // If no static explanation, use AI for non-MCQ
                 if (!$explanation && !isset($question['options'])) {
                     $explanation = $this->mistralEval->explainMistake(
                         $question['prompt'] ?? $question['text'] ?? '',
                         $textToEvaluate ?? $this->getTextToEvaluate($userAnswer),
                         $correctAnswer ?? '',
                         $exercise->exam?->language?->name ?? 'English'
                     );
                 }
            }

            if ($isCorrect) $correct++;

            $feedback[] = [
                'question_id' => $questionId,
                'correct' => $isCorrect,
                'accuracy' => (float)($accuracy ?? ($isCorrect ? 100 : 0)),
                'correct_answer' => $correctAnswer,
                'explanation' => $explanation ?? $question['explanation'] ?? null,
            ];
        }

        $accuracyPercent = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
        $xpReward = $exercise->xp_reward ?? ($total * 10);
        $xpEarned = (int) round(($accuracyPercent / 100) * $xpReward);

        return [
            'score' => $correct,
            'accuracy' => $accuracyPercent,
            'xp' => $xpEarned,
            'feedback' => $feedback,
        ];
    }

    protected function getTextToEvaluate($userAnswer, ?string $lang = null): string
    {
        if ($userAnswer instanceof UploadedFile) {
            try {
                return $this->stt->transcribe($userAnswer, $lang) ?? '';
            } catch (\Exception $e) {
                Log::error('STT failed: ' . $e->getMessage());
                return '';
            }
        }
        return is_string($userAnswer) ? trim($userAnswer) : '';
    }

    protected function createEmptyFeedback(string $questionId, bool $isAudio = false): array
    {
        return [
            'question_id' => $questionId,
            'correct' => false,
            'accuracy' => 0,
            'explanation' => $isAudio ? "L'audio n'a pas pu être retranscrit (vide ou muet)." : "Aucune réponse fournie.",
        ];
    }

    public function explainMistake(string $prompt, string $userAnswer, string $correctAnswer, string $language): string
    {
        return $this->mistralEval->explainMistake($prompt, $userAnswer, $correctAnswer, $language);
    }
}
