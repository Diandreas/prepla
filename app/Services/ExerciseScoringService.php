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

    /** trim + mb_strtolower + normalize typographic apostrophes, so accented (é, ü, ß)
     *  and apostrophe-containing answers aren't wrongly marked incorrect. */
    protected function normalizeForComparison(?string $s): string
    {
        $s = trim(mb_strtolower((string)$s));
        return str_replace(['’', '`'], "'", $s);
    }

    public function score(Exercise $exercise, array $answers): array
    {
        $questions = $exercise->questions;
        $correct = 0;
        $total = count($questions);
        $technicalFailures = 0;
        $feedback = [];
        $cefrLevel = $exercise->difficulty;

        // Exercise types that require AI evaluation
        $aiEvaluatedTypes = [
            'essay', 'essay-editor', 'speaking', 'writing', 'short-writing',
            'graph-description', 'academic-discussion', 'speaking-recorder',
            'role-play', 'synthesis', 'integrated-task',
            'guided-rewrite', 'text-continuation', 'synthesis-essay',
        ];

        foreach ($questions as $index => $question) {
            // Reset per-question locals explicitly: PHP's foreach does NOT scope
            // variables per iteration, so without this, a question that skips
            // setting $accuracy/$explanation (e.g. a plain MCQ) would silently
            // inherit the value left over by a PREVIOUS question's branch —
            // confirmed cause of correct MCQ answers reporting 0% accuracy right
            // after a low-scoring table/form-completion question, and of wrong
            // answers occasionally showing a previous question's explanation.
            $accuracy = null;
            $explanation = null;

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

                // Sentinel sent by the frontend "skip this question" escape hatches
                // (role-play with no dialogue_turns, build-a-sentence with no words,
                // or the ErrorBoundary fallback) — treat as unanswered rather than
                // sending a fake string to the AI evaluator for scoring.
                if (empty($userAnswer) || $userAnswer === '__skipped__' || $userAnswer === '__no_dialogue__') {
                    $feedback[] = $this->createEmptyFeedback($questionId);
                    continue;
                }

                $langName = $exercise->exam?->language?->name ?? 'English';
                $langSlug = $exercise->exam?->language?->slug ?? 'en';
                
                // Use specialized WritingCorrector for complex essays
                if (in_array($questionType, ['essay', 'essay-editor', 'integrated-task', 'synthesis'])) {
                    $technicalFailure = false;
                    $textToEvaluate = $this->getTextToEvaluate($userAnswer, $langSlug, $technicalFailure);
                    if (empty($textToEvaluate)) {
                        if ($technicalFailure) $technicalFailures++;
                        $feedback[] = $this->createEmptyFeedback($questionId, $userAnswer instanceof UploadedFile, $technicalFailure);
                        continue;
                    }
                    
                    $aiResult = $this->writingCorrector->correct($textToEvaluate, $question['prompt'] ?? $question['text'] ?? "Write an essay", $exercise->exam?->name ?? 'IELTS', 'Français', $cefrLevel);
                    
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
                    $technicalFailure = false;
                    $textToEvaluate = $this->getTextToEvaluate($userAnswer, $langSlug, $technicalFailure);
                    if (empty($textToEvaluate)) {
                        if ($technicalFailure) $technicalFailures++;
                        $feedback[] = $this->createEmptyFeedback($questionId, $userAnswer instanceof UploadedFile, $technicalFailure);
                        continue;
                    }

                    $prompt = $question['prompt'] ?? $question['text'] ?? "Respond to the prompt";

                    // SPEAKING (audio answer) → formative evaluation: transcript checked
                    // for relevance/coverage, PASS at >= 50%, always returns
                    // covered/missing points + tips to continue.
                    $isSpeaking = ($userAnswer instanceof UploadedFile)
                        || in_array($questionType, ['speaking-recorder', 'role-play', 'speaking'], true);

                    if ($isSpeaking) {
                        // Center-authored exercises can specify exact expected points
                        // the oral answer must cover; pass them so the AI evaluates
                        // coverage against the teacher's list.
                        $expectedPoints = is_array($question['expected_points'] ?? null)
                            ? $question['expected_points']
                            : [];
                        $aiResult = $this->mistralEval->evaluateSpeaking($prompt, $textToEvaluate, $langName, $expectedPoints, $cefrLevel);
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
                        $aiResult = $this->mistralEval->evaluate($prompt, $textToEvaluate, $langName, $cefrLevel);
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
                    if ($this->normalizeForComparison($userSeq[$i] ?? '') === $this->normalizeForComparison($expectedSeq[$i] ?? '')) {
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

                // Les clés de correct_answers générées par l'IA sont imprévisibles
                // (0-based, 1-based, relatives aux blancs, parfois des labels) alors
                // que le front envoie des index absolus 0-based. Quand la clé exacte
                // ne matche pas, on vérifie si la valeur attendue a été saisie dans
                // N'IMPORTE quel champ (pool consommable pour ne pas créditer deux
                // fois la même saisie) — sinon des réponses justes sortaient à 0%.
                $givenPool = [];
                foreach ($userAnswer as $v) {
                    if (is_scalar($v)) {
                        $n = $this->normalizeForComparison((string) $v);
                        if ($n !== '') {
                            $givenPool[$n] = ($givenPool[$n] ?? 0) + 1;
                        }
                    }
                }

                foreach ($correctAnswers as $key => $expected) {
                    $expectedNorm = $this->normalizeForComparison(is_scalar($expected) ? (string) $expected : '');
                    $given = $userAnswer[$key] ?? '';
                    $givenNorm = is_scalar($given) ? $this->normalizeForComparison((string) $given) : '';

                    if ($givenNorm !== '' && $givenNorm === $expectedNorm) {
                        $fieldCorrect++;
                        if (isset($givenPool[$givenNorm])) {
                            $givenPool[$givenNorm]--;
                            if ($givenPool[$givenNorm] <= 0) unset($givenPool[$givenNorm]);
                        }
                        continue;
                    }

                    // Clé désalignée : créditer si la valeur attendue existe ailleurs.
                    if ($expectedNorm !== '' && !empty($givenPool[$expectedNorm])) {
                        $givenPool[$expectedNorm]--;
                        if ($givenPool[$expectedNorm] <= 0) unset($givenPool[$expectedNorm]);
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
                $normalize = fn ($arr) => array_map(fn ($v) => $this->normalizeForComparison((string)$v), $arr);
                $isCorrect = is_array($userAnswer)
                    && empty(array_diff($normalize($correctAnswer), $normalize($userAnswer)))
                    && empty(array_diff($normalize($userAnswer), $normalize($correctAnswer)));
            } else {
                // Defensive: multi-field exercises (FormCompletion, TableCompletion, FlowChart…)
                // submit an array against a scalar correct_answer. Join the values so the cast doesn't crash.
                if (is_array($userAnswer)) {
                    $userAnswer = implode(' ', array_map('strval', array_filter(
                        $userAnswer,
                        fn ($v) => $v !== null && $v !== ''
                    )));
                }
                $normalUser = $this->normalizeForComparison((string)$userAnswer);
                $normalCorrect = $this->normalizeForComparison((string)$correctAnswer);
                // An empty user answer is NEVER correct, even if the expected answer
                // is also empty/missing (malformed exercise). This stopped multi-field
                // exercises like form-completion from showing "success" with nothing entered.
                $isCorrect = $normalUser !== '' && $normalUser === $normalCorrect;

                // Index-based matching fallback
                if (!$isCorrect && preg_match('/^[a-d]$/', $normalUser) && isset($question['options'])) {
                    $options = $question['options'] ?? [];
                    $letterIndex = ord($normalUser) - ord('a');
                    if (isset($options[$letterIndex])) {
                        $isCorrect = $this->normalizeForComparison($options[$letterIndex]) === $normalCorrect;
                    }
                }
            }

            if (!$isCorrect) {
                 // Try to get a conceptual explanation
                 $explanation = $question['explanation'] ?? null;

                 // If no static explanation, ask the AI — including for MCQ/options
                 // questions, which previously got NO explanation at all here (the
                 // generation-time validation only guarantees one for a few
                 // component types, and older exercises predate it entirely).
                 // Resolve A/B/C/D letters to the actual option text first, so the
                 // AI (and anyone reading the raw feedback) reasons about real
                 // content instead of a bare letter.
                 if (!$explanation) {
                     $opts = $question['options'] ?? null;
                     $resolve = function ($value) use ($opts) {
                         if (is_array($opts) && is_string($value) && preg_match('/^[A-Za-z]$/', trim($value))) {
                             $idx = ord(strtoupper(trim($value))) - 65;
                             return $opts[$idx] ?? $value;
                         }
                         return $value;
                     };

                     $explanation = $this->mistralEval->explainMistake(
                         $question['prompt'] ?? $question['text'] ?? '',
                         is_string($userAnswer) ? $resolve($userAnswer) : $this->getTextToEvaluate($userAnswer),
                         (string) $resolve($correctAnswer ?? ''),
                         $exercise->exam?->language?->name ?? 'English'
                     );
                 }
            }

            $accuracy = $isCorrect ? 100 : 0;
            if ($isCorrect) $correct++;

            $feedback[] = [
                'question_id' => $questionId,
                'correct' => $isCorrect,
                'accuracy' => (float) $accuracy,
                'correct_answer' => $correctAnswer,
                'explanation' => $explanation ?? $question['explanation'] ?? null,
                'error_category' => !$isCorrect ? 'session_mistake' : null,
                'error_subcategory' => null,
            ];
        }

        // Technical STT failures are excluded from the denominator — they aren't
        // the student's fault, so they shouldn't lower accuracy/XP like a real
        // mistake would (see getTextToEvaluate's $technicalFailure param).
        $scorableTotal = max($total - $technicalFailures, 0);
        $accuracyPercent = $scorableTotal > 0 ? round(($correct / $scorableTotal) * 100, 2) : 0;
        $xpReward = $exercise->xp_reward ?? ($total * 10);
        $xpEarned = (int) round(($accuracyPercent / 100) * $xpReward);

        return [
            'score' => $correct,
            'accuracy' => $accuracyPercent,
            'xp' => $xpEarned,
            'feedback' => $feedback,
        ];
    }

    /**
     * @param bool &$technicalFailure  Set to true when transcription failed due to a
     *   TECHNICAL problem (API error, exception) rather than the student staying
     *   silent — DeepgramSttService returns null for the former, '' for the latter.
     *   Losing this distinction meant a Deepgram outage penalised the student's XP
     *   exactly like a real silent answer.
     */
    protected function getTextToEvaluate($userAnswer, ?string $lang = null, bool &$technicalFailure = false): string
    {
        if ($userAnswer instanceof UploadedFile) {
            try {
                $transcript = $this->stt->transcribe($userAnswer, $lang);
                if ($transcript === null) {
                    $technicalFailure = true;
                    return '';
                }
                return $transcript;
            } catch (\Exception $e) {
                Log::error('STT failed: ' . $e->getMessage());
                $technicalFailure = true;
                return '';
            }
        }
        return is_string($userAnswer) ? trim($userAnswer) : '';
    }

    protected function createEmptyFeedback(string $questionId, bool $isAudio = false, bool $technicalFailure = false): array
    {
        return [
            'question_id' => $questionId,
            'correct' => false,
            'accuracy' => 0,
            'explanation' => $technicalFailure
                ? "Un problème technique nous a empêché d'analyser ta réponse. Cette question ne compte pas dans ton score — réessaie."
                : ($isAudio
                    ? "On n'a pas réussi à t'entendre. Vérifie ton micro, parle plus fort et un peu plus longtemps, puis réessaie."
                    : "Aucune réponse fournie."),
            // Always present so the UI can surface the state (even if empty). Empty
            // string = "we tried to transcribe but heard nothing".
            'transcription' => $isAudio ? '' : null,
            'covered_points' => [],
            'missing_points' => [],
            'technical_failure' => $technicalFailure,
        ];
    }

    public function explainMistake(string $prompt, string $userAnswer, string $correctAnswer, string $language): string
    {
        return $this->mistralEval->explainMistake($prompt, $userAnswer, $correctAnswer, $language);
    }
}
