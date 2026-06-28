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

            // Open-ended short-answer (C1/C2 written response = a sentence, not 1-3
            // words) can't be exact-matched → evaluate with AI. Detect by length of
            // the expected answer (>4 words ⇒ open response).
            if ($questionType === 'short-answer' && is_string($correctAnswer)
                && str_word_count(trim($correctAnswer)) > 4) {
                $questionType = 'short-answer-open';
                $aiEvaluatedTypes[] = 'short-answer-open';
            }

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

                    $prompt = $question['prompt'] ?? $question['text'] ?? "Respond to the prompt";

                    // SPEAKING (audio answer) → formative evaluation: transcript checked
                    // for relevance/coverage, PASS at >= 50%, always returns
                    // covered/missing points + tips to continue.
                    $isSpeaking = ($userAnswer instanceof UploadedFile)
                        || in_array($questionType, ['speaking-recorder', 'role-play', 'speaking'], true);

                    if ($isSpeaking) {
                        $aiResult = $this->mistralEval->evaluateSpeaking($prompt, $textToEvaluate, $langName);
                        $isCorrect = (bool) $aiResult['isCorrect'];
                        $feedback[] = [
                            'question_id' => $questionId,
                            'correct' => $isCorrect,
                            'accuracy' => $aiResult['accuracy'],
                            'explanation' => $aiResult['explanation'],
                            'covered_points' => $aiResult['covered_points'] ?? [],
                            'missing_points' => $aiResult['missing_points'] ?? [],
                            'error_category' => $aiResult['error_category'] ?? null,
                            'error_subcategory' => $aiResult['error_subcategory'] ?? null,
                            'transcription' => $textToEvaluate,
                        ];
                    } else {
                        // Standard evaluation for typed short answers.
                        $aiResult = $this->mistralEval->evaluate($prompt, $textToEvaluate, $langName);
                        $isCorrect = (bool) $aiResult['isCorrect'];
                        $feedback[] = [
                            'question_id' => $questionId,
                            'correct' => $isCorrect,
                            'accuracy' => $aiResult['accuracy'],
                            'explanation' => $aiResult['explanation'],
                            'error_category' => $aiResult['error_category'] ?? null,
                            'error_subcategory' => $aiResult['error_subcategory'] ?? null,
                            'transcription' => ($userAnswer instanceof UploadedFile) ? $textToEvaluate : null,
                        ];
                    }
                }

                if ($isCorrect) $correct++;
                continue;
            }

            // ─── ORDER-BASED BRANCH (ordering, gapped-text) ───
            // The expected answer is a SEQUENCE (correct_order). The array-diff
            // branch below would ignore order (any permutation would pass), so we
            // compare position by position here.
            $correctOrder = $question['correct_order'] ?? null;
            if (is_array($correctOrder) && !empty($correctOrder)) {
                // Build the user's ordered sequence:
                //  - ordering: userAnswer is already an ordered array of item texts;
                //    compare against $question['items'] (given in correct order).
                //  - gapped-text: userAnswer is a {gapIndex: key} map → order by gap index.
                $userSeq = [];
                if (is_array($userAnswer)) {
                    $isList = array_keys($userAnswer) === range(0, count($userAnswer) - 1);
                    if ($isList) {
                        $userSeq = array_values($userAnswer);
                    } else {
                        ksort($userAnswer, SORT_NATURAL);
                        $userSeq = array_values($userAnswer);
                    }
                }
                // Expected sequence: prefer items (texts) for ordering, else correct_order (ids).
                $expectedSeq = $correctOrder;
                if (isset($question['items']) && is_array($question['items']) && count($question['items']) === count($userSeq)) {
                    $expectedSeq = $question['items'];
                }
                $n = min(count($expectedSeq), count($userSeq));
                $hit = 0;
                for ($i = 0; $i < $n; $i++) {
                    if (strtolower(trim((string)($userSeq[$i] ?? ''))) === strtolower(trim((string)($expectedSeq[$i] ?? '')))) {
                        $hit++;
                    }
                }
                $accuracy = count($expectedSeq) > 0 ? ($hit / count($expectedSeq)) * 100 : 0;
                $isCorrect = $accuracy >= 70;
                if ($isCorrect) $correct++;
                $feedback[] = [
                    'question_id' => $questionId,
                    'correct' => $isCorrect,
                    'accuracy' => $accuracy,
                    'correct_answer' => $correctOrder,
                    'explanation' => $question['explanation'] ?? null,
                ];
                continue;
            }

            // ─── RECORD-BASED OR MULTI-FIELD BRANCH ───
            // correct_answers est une map clé→réponse (note/form/table/summary…).
            // On NE filtre PAS sur array_is_list : des clés numériques séquentielles
            // (0,1,2 — note-completion, form-completion, summary-completion) sont
            // détectées comme "liste" par PHP mais restent bien une map de réponses.
            $correctAnswers = $question['correct_answers'] ?? null;
            if (is_array($correctAnswers) && !empty($correctAnswers) && is_array($userAnswer)) {
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
                // Defensive: multi-field exercises (FormCompletion, TableCompletion, FlowChart…)
                // submit an array against a scalar correct_answer. Join the values so the cast doesn't crash.
                if (is_array($userAnswer)) {
                    $userAnswer = implode(' ', array_map('strval', array_filter(
                        $userAnswer,
                        fn ($v) => $v !== null && $v !== ''
                    )));
                }
                $normalUser = strtolower(trim((string)$userAnswer));
                $normalCorrect = strtolower(trim((string)$correctAnswer));
                // An empty user answer is NEVER correct, even if the expected answer
                // is also empty/missing (malformed exercise). This stopped multi-field
                // exercises like form-completion from showing "success" with nothing entered.
                $isCorrect = $normalUser !== '' && $normalUser === $normalCorrect;

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
                'error_category' => !$isCorrect ? 'session_mistake' : null,
                'error_subcategory' => null,
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
            'explanation' => $isAudio
                ? "On n'a pas réussi à t'entendre. Vérifie ton micro, parle plus fort et un peu plus longtemps, puis réessaie."
                : "Aucune réponse fournie.",
            // Always present so the UI can surface the state (even if empty). Empty
            // string = "we tried to transcribe but heard nothing".
            'transcription' => $isAudio ? '' : null,
            'covered_points' => [],
            'missing_points' => [],
        ];
    }

    public function explainMistake(string $prompt, string $userAnswer, string $correctAnswer, string $language): string
    {
        return $this->mistralEval->explainMistake($prompt, $userAnswer, $correctAnswer, $language);
    }
}
