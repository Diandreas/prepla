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

    /**
     * Rescue for a question with more blanks than its expected answer defines.
     *
     * Generated content sometimes carries two blanks in a sentence but a single
     * expected word. Every blank is fillable, so a learner naturally fills them all —
     * and the joined answer could never match, marking a right answer wrong. When the
     * learner supplied MORE values than the expected answer has words, the surplus
     * blanks are ones the exercise never defined an answer for: they must not count
     * against them. What the correction can judge, it still judges — the expected
     * words have to be there, in order.
     *
     * Only reachable on a scalar expected answer against several filled fields, which
     * is malformed content by construction; well-formed items never get here.
     */
    protected function coversUndefinedBlanks(array $givenValues, string $normalCorrect): bool
    {
        $expectedWords = preg_split('/\s+/', trim($normalCorrect), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($expectedWords === [] || count($givenValues) <= count($expectedWords)) {
            return false;
        }

        $cursor = 0;
        foreach ($givenValues as $value) {
            if ($cursor < count($expectedWords)
                && $this->normalizeForComparison($value) === $expectedWords[$cursor]) {
                $cursor++;
            }
        }

        return $cursor === count($expectedWords);
    }

    /**
     * Pour note-completion/form-completion, renvoie les index (string) des
     * items réellement BLANCS (value === ''), d'après la structure de la
     * question elle-même — indépendamment de ce que contient correct_answers.
     * Renvoie null si la question n'a pas ce genre de structure (notes/fields),
     * auquel cas on ne restreint rien (autres types multi-champs inchangés).
     */
    private function blankFieldIndices(array $question): ?array
    {
        $items = $question['notes'] ?? $question['fields'] ?? null;
        if (!is_array($items)) {
            return null;
        }

        $indices = [];
        foreach ($items as $i => $item) {
            if (is_array($item) && ($item['value'] ?? null) === '') {
                $indices[] = (string) $i;
            }
        }

        return $indices;
    }

    public function score(Exercise $exercise, array $answers): array
    {
        // Les questions impossibles — aucune case a remplir, lettre attendue hors des
        // choix — ne sont plus servies a l'apprenant. Les compter ici le punirait pour
        // des questions qu'il n'a jamais vues : servir et corriger doivent s'accorder
        // sur la meme regle.
        $questions = $exercise->answerableQuestions();
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
            // « Ecriture guidee » : notee par l'IA comme les autres redactions.
            'guided-writing',
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

            // Une question que l'apprenant n'a pas pu faire : exercice généré sans
            // contenu utilisable, ou composant qui a planté et proposé de passer.
            // Le repère n'était reconnu que par la branche IA ; ailleurs il était
            // comparé à la réponse attendue, donc compté faux. L'apprenant perdait
            // des points pour une panne de notre côté. Elle sort du dénominateur,
            // comme un échec technique de transcription.
            if (is_string($userAnswer) && in_array($userAnswer, ['__skipped__', '__no_dialogue__'], true)) {
                $technicalFailures++;
                $feedback[] = [
                    'question_id' => $questionId,
                    'correct' => false,
                    'accuracy' => 0,
                    'explanation' => "Cette question n'a pas pu être présentée correctement. Elle ne compte pas dans ton score.",
                    'technical_failure' => true,
                ];
                continue;
            }

            // Interactive speaking components already evaluate each recording
            // turn-by-turn in the UI and submit a signed-down score marker at the
            // end ("completed:NN" / "repeat:NN"). Re-sending that marker to the
            // language model, or comparing it to the target sentence, made the
            // final session report disagree with the score the learner just saw.
            if (is_string($userAnswer)
                && in_array($questionType, ['role-play', 'oral-debate', 'negotiation', 'speaking-elicitation', 'listen-repeat'], true)
                && preg_match('/^(completed|repeat):(\d{1,3})$/', $userAnswer, $scoreMatch)) {
                $accuracy = max(0, min(100, (int) $scoreMatch[2]));
                $isCorrect = $accuracy >= 50;
                if ($isCorrect) {
                    $correct++;
                }
                $feedback[] = [
                    'question_id' => $questionId,
                    'correct' => $isCorrect,
                    'accuracy' => $accuracy,
                    'explanation' => $isCorrect
                        ? 'Exercice oral validé.'
                        : 'Continue à t’entraîner pour atteindre au moins 50 %.',
                ];
                continue;
            }

            // Open-ended short-answer (C1/C2 written response = a sentence, not 1-3
            // words) can't be exact-matched → evaluate with AI. Detect by length of
            // the expected answer (>4 words ⇒ open response).
            if ($questionType === 'short-answer' && is_string($correctAnswer)
                && str_word_count(trim($correctAnswer)) > 4) {
                // Une reponse identique a celle attendue n'a pas besoin d'etre soumise
                // au modele : l'exercice est genere AVEC sa correction, et faire juger
                // une evidence coute du quota, ajoute une attente, et expose la
                // correction a une panne du fournisseur.
                if (is_string($userAnswer)
                    && $this->normalizeForComparison($userAnswer) === $this->normalizeForComparison($correctAnswer)) {
                    $correct++;
                    $feedback[] = [
                        'question_id' => $questionId,
                        'correct' => true,
                        'accuracy' => 100,
                        'explanation' => $question['explanation'] ?? 'Exactement la reponse attendue.',
                    ];
                    continue;
                }

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
                    
                    // Les criteres de l'epreuve visee, quand elle en declare : la
                    // correction les nommait jamais, alors qu'ils sont enregistres
                    // avec l'examen (« Inhalt », « Textaufbau », « Korrektheit »...).
                    $criteres = collect($exercise->examSection?->rubric['criteria'] ?? [])
                        ->map(fn ($critere) => Exercise::optionText($critere['name'] ?? $critere))
                        ->filter()
                        ->values()
                        ->all();

                    $aiResult = $this->writingCorrector->correct($textToEvaluate, $question['prompt'] ?? $question['text'] ?? "Write an essay", $exercise->exam?->name ?? 'IELTS', 'Français', $cefrLevel, $criteres);
                    
                    // Normalize IELTS 1-9 to 0-1. La note est verifiee : rendue en
                    // texte ou en objet, la division levait une erreur et la seance
                    // entiere partait en 500.
                    $noteBrute = $aiResult['score'] ?? 0;
                    $points = (is_numeric($noteBrute) ? (float) $noteBrute : 0.0) / 9;
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
                // Chaque element est LU : le generateur rend parfois les items en objets
                // ({id, text}), et l'element brut partait dans une comparaison typee
                // ?string — TypeError, donc 500 a l'envoi de la seance, pour l'apprenant
                // qui venait justement de terminer l'exercice. Sa seance devenait meme
                // definitivement inenvoyable, puisque le renvoi retombait sur la meme
                // erreur.
                $expectedSeq = array_map(fn ($valeur) => Exercise::optionText($valeur), $correctOrder);
                if (isset($question['items']) && is_array($question['items']) && count($question['items']) === count($userSeq)) {
                    $expectedSeq = array_map(fn ($valeur) => Exercise::optionText($valeur), $question['items']);
                }
                $n = min(count($expectedSeq), count($userSeq));
                $hit = 0;
                for ($i = 0; $i < $n; $i++) {
                    $donne = Exercise::optionText($userSeq[$i] ?? '');
                    if ($this->normalizeForComparison($donne) === $this->normalizeForComparison($expectedSeq[$i] ?? '')) {
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
                // Du contenu généré par l'IA a parfois une correct_answers avec plus
                // d'entrées que de blancs réels (clé pointant vers une note déjà
                // pré-remplie, ou simplement trop d'entrées) — ces entrées ne sont
                // JAMAIS remplissables par l'élève (aucun champ ne lui est proposé),
                // ce qui plafonnait fieldTotal artificiellement haut et rendait
                // l'exercice structurellement impossible à réussir (score bloqué
                // sous 70% quoi qu'il réponde). On restreint aux index réellement
                // blancs quand la structure de la question le permet de le savoir.
                $blanks = $this->blankFieldIndices($question);
                if ($blanks !== null) {
                    $restricted = array_intersect_key(
                        $correctAnswers,
                        array_flip($blanks)
                    );
                    if (!empty($restricted)) {
                        $correctAnswers = $restricted;
                    }
                }

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
                    if (true) {
                        $n = $this->normalizeForComparison(Exercise::optionText($v));
                        if ($n !== '') {
                            $givenPool[$n] = ($givenPool[$n] ?? 0) + 1;
                        }
                    }
                }

                foreach ($correctAnswers as $key => $expected) {
                    // La valeur attendue est LUE quelle que soit sa forme : rendue en
                    // objet, elle devenait une chaine vide et le champ n'etait jamais
                    // creditable — l'exercice sortait a 0 % quoi que l'apprenant ecrive.
                    $expectedNorm = $this->normalizeForComparison(Exercise::optionText($expected));
                    $given = $userAnswer[$key] ?? '';
                    $givenNorm = $this->normalizeForComparison(Exercise::optionText($given));

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
                $givenValues = [];
                if (is_array($userAnswer)) {
                    $givenValues = array_values(array_map('strval', array_filter(
                        $userAnswer,
                        fn ($v) => $v !== null && $v !== ''
                    )));
                    $userAnswer = implode(' ', $givenValues);
                }
                $normalUser = $this->normalizeForComparison((string)$userAnswer);
                $normalCorrect = $this->normalizeForComparison((string)$correctAnswer);
                // Word tiles need not contain final punctuation; missing words still fail.
                if (($question['type'] ?? '') === 'build-a-sentence') {
                    $normalizeSentence = fn ($text) => preg_replace('/\s+/u', ' ', trim(preg_replace('/[.!?]+$/u', '', $text)));
                    $normalUser = $normalizeSentence($normalUser);
                    $normalCorrect = $normalizeSentence($normalCorrect);
                }
                // An empty user answer is NEVER correct, even if the expected answer
                // is also empty/missing (malformed exercise). This stopped multi-field
                // exercises like form-completion from showing "success" with nothing entered.
                $isCorrect = $normalUser !== '' && $normalUser === $normalCorrect;

                if (!$isCorrect) {
                    $isCorrect = $this->coversUndefinedBlanks($givenValues, $normalCorrect);
                }

                // Index-based matching fallback. Le choix passe par optionText : rendu
                // sous forme d'objet par l'IA, il arrivait brut dans une comparaison
                // typee ?string et levait une TypeError — 500 a l'envoi de la seance.
                if (!$isCorrect && preg_match('/^[a-d]$/', $normalUser) && isset($question['options'])) {
                    $options = Exercise::optionList($question['options'] ?? []);
                    $letterIndex = ord($normalUser) - ord('a');
                    $texteChoix = $options[$letterIndex] ?? '';
                    if ($texteChoix !== '') {
                        $isCorrect = $this->normalizeForComparison($texteChoix) === $normalCorrect;
                    }
                }
            }

            // Une lettre seule n'apprend rien : on rend le texte de l'option. Garde-fou
            // indispensable — si le mot juste EST une lettre (« a / an / the »), le
            // resoudre comme un indice remplacerait la bonne reponse par la premiere
            // option. On ne resout donc que si la valeur n'est pas elle-meme une option.
            $opts = Exercise::optionList($question['options'] ?? null);
            $resolve = function ($value) use ($opts) {
                if ($opts === [] || ! is_string($value) || ! preg_match('/^[A-Za-z]$/', trim($value))) {
                    return $value;
                }

                foreach ($opts as $texte) {
                    if ($texte !== '' && $this->normalizeForComparison($texte) === $this->normalizeForComparison($value)) {
                        return $value;
                    }
                }

                $texte = $opts[ord(strtoupper(trim($value))) - 65] ?? '';

                return $texte !== '' ? $texte : $value;
            };

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
                     $explanation = $this->mistralEval->explainMistake(
                         $question['prompt'] ?? $question['text'] ?? '',
                         is_string($userAnswer) ? $resolve($userAnswer) : $this->getTextToEvaluate($userAnswer),
                         Exercise::optionText($resolve($correctAnswer ?? '')),
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
                'correct_answer' => is_string($correctAnswer) ? $resolve($correctAnswer) : $correctAnswer,
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

    /**
     * La réponse attendue, écrite pour un humain : le bilan de séance doit pouvoir
     * la montrer telle quelle, au lieu d'une lettre ou d'un tableau brut.
     * Reflète expectedAnswerText() de resources/js/lib/scoring.js.
     */
    public function expectedAnswerText(array $question): string
    {
        // Champs à trous (notes, tableaux, formulaires) : seules les cases vides comptent.
        $map = $question['correct_answers'] ?? null;
        if (is_array($map) && $map !== []) {
            $blanks = $this->blankFieldIndices($question);
            $values = [];
            foreach ($map as $key => $value) {
                if ($blanks !== null && !in_array((string) $key, $blanks, true)) {
                    continue;
                }
                if (is_scalar($value) && (string) $value !== '') {
                    $values[] = (string) $value;
                }
            }
            if ($values !== []) {
                return implode(', ', $values);
            }
        }

        // Remise en ordre : c'est la séquence entière qui est attendue.
        $order = $question['correct_order'] ?? null;
        if (is_array($order) && $order !== []) {
            $items = $question['items'] ?? null;
            $sequence = (is_array($items) && count($items) === count($order)) ? $items : $order;

            // Chaque element est LU : transtyper un objet donnait « Array » et une
            // alerte PHP, qui fait tomber le bilan de seance en 500.
            return implode(' → ', array_filter(array_map(fn ($value) => Exercise::optionText($value), $sequence)));
        }

        $answer = $question['correct_answer'] ?? null;
        if (is_array($answer)) {
            return implode(', ', array_filter(array_map(fn ($value) => Exercise::optionText($value), $answer)));
        }
        if (!is_scalar($answer) || (string) $answer === '') {
            return '';
        }

        // QCM : une lettre seule n'apprend rien, on rend "C) Am Sonntag". Le choix
        // peut etre un objet rendu par l'IA : optionText le lit, sinon on retombe sur
        // la lettre plutot que d'afficher « Array ».
        $letter = strtoupper(trim((string) $answer));
        $options = Exercise::optionList($question['options'] ?? null);
        if ($options !== [] && preg_match('/^[A-D]$/', $letter)) {
            $texteChoix = $options[ord($letter) - ord('A')] ?? '';
            if ($texteChoix !== '') {
                return $letter.') '.$texteChoix;
            }
        }

        return (string) $answer;
    }

    public function explainMistake(string $prompt, string $userAnswer, string $correctAnswer, string $language): string
    {
        return $this->mistralEval->explainMistake($prompt, $userAnswer, $correctAnswer, $language);
    }
}
