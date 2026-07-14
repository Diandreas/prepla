<?php

namespace App\Services\AI;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseType;
use Illuminate\Support\Facades\Log;

class ExerciseGeneratorService
{
    public function __construct(
        protected MistralService $mistral,
        protected \App\Services\ImageLibraryService $imageLibrary
    ) {}

    public function generateBatch(array $typeIds, Exam $exam, string $difficulty = 'B1'): array
    {
        $exercises = [];
        foreach ($typeIds as $typeId) {
            $exerciseType = ExerciseType::find($typeId);
            if ($exerciseType) {
                $exercises[] = $this->generate($exerciseType, $exam, $difficulty);
            }
        }
        return $exercises;
    }

    public function generate(ExerciseType $exerciseType, Exam $exam, string $difficulty = 'B1', ?array $lessonContext = null): Exercise
    {
        $exam->loadMissing('language');
        $language = $exam->language?->name ?? 'English';
        $languageNative = $exam->language?->native_name ?? $language;

        $prompt = $this->buildPrompt($exerciseType, $exam, $difficulty, $language, $languageNative, $lessonContext);

        // Up to 2 attempts: if the first generation contains structurally broken
        // MCQ-style questions (duplicate options, correct_answer letter out of
        // range, etc.), retry the WHOLE batch once before falling back to
        // dropping only the broken questions. This is what was producing exercises
        // where the marked-correct option didn't match the explanation at all.
        $data = null;
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $response = $this->mistral->chat([
                ['role' => 'system', 'content' => 'You are an expert language exam content creator. Always respond with valid JSON only.'],
                ['role' => 'user', 'content' => $prompt],
            ]);

            if (!$response) {
                continue;
            }
            $candidate = json_decode($response, true);
            if (!isset($candidate['content'], $candidate['questions']) || count($candidate['questions']) === 0) {
                continue;
            }

            $candidate['questions'] = $this->normalizeOptionLetters($candidate['questions']);
            $candidate['questions'] = $this->shuffleOptions($candidate['questions']);
            $candidate['questions'] = $this->dropInvalidChoiceQuestions($candidate['questions'], $exerciseType->component_key);
            $candidate['questions'] = $this->dropInvalidMultiFieldQuestions($candidate['questions'], $exerciseType->component_key);

            if (count($candidate['questions']) > 0) {
                $data = $candidate;
                break;
            }
        }

        if ($data) {
            // For visual exercise types (diagram-labeling, graph-description), pick
            // an image from the pre-generated library instead of leaving image_url null.
            $imageCategory = $this->imageLibrary->categoryFor($exerciseType->component_key);
            if ($imageCategory) {
                $exam->loadMissing('language');
                $examSlug = strtolower($exam->slug ?? $exam->name);
                $data['questions'] = array_map(function ($q) use ($examSlug, $imageCategory) {
                    if (empty($q['image_url'])) {
                        $pickedUrl = $this->imageLibrary->pickFor($examSlug, $imageCategory);
                        if ($pickedUrl) {
                            $q['image_url'] = $pickedUrl;
                        }
                    }
                    return $q;
                }, $data['questions']);
            }

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

        // AI failed: do NOT persist a meaningless placeholder exercise ("What is
        // this exercise about?") — that junk was being shown to learners. Throw so
        // the caller can skip this type / retry / show a clean message instead.
        Log::warning('ExerciseGenerator: AI failed, no exercise generated', [
            'exercise_type' => $exerciseType->component_key,
            'exam_id' => $exam->id,
        ]);
        throw new \RuntimeException('AI exercise generation failed for ' . $exerciseType->component_key);
    }

    /** Strip letter prefixes like "A) ", "B) " that Mistral sometimes adds to options. */
    private function normalizeOptionLetters(array $questions): array
    {
        return array_map(function ($q) {
            if (isset($q['options']) && is_array($q['options'])) {
                $q['options'] = array_map(
                    fn($opt) => is_string($opt) ? preg_replace('/^[A-D]\)\s*/u', '', $opt) : $opt,
                    $q['options']
                );
            }
            return $q;
        }, $questions);
    }

    /**
     * Shuffle options so the correct answer isn't ALWAYS in position A (Mistral
     * systematically writes the right option first). Reorders options AND any
     * parallel arrays (image_prompts/image_options for picture-mcq) in LOCKSTEP,
     * keeping image↔label paired, then re-points the letter answer.
     */
    private function shuffleOptions(array $questions): array
    {
        return array_map(function ($q) {
            $opts = $q['options'] ?? null;
            $ca = $q['correct_answer'] ?? null;
            if (!is_array($opts) || count($opts) < 2 || !is_string($ca)) {
                return $q;
            }
            $letter = strtoupper(trim($ca));
            if (!preg_match('/^[A-Z]$/', $letter)) {
                return $q; // answer isn't a letter (free text) → leave as is
            }
            $correctIdx = ord($letter) - 65;
            if ($correctIdx < 0 || $correctIdx >= count($opts)) {
                return $q;
            }
            $order = range(0, count($opts) - 1);
            for ($i = count($order) - 1; $i > 0; $i--) {
                $j = random_int(0, $i);
                [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
            }
            $reorder = fn ($arr) => (is_array($arr) && count($arr) === count($order))
                ? array_map(fn ($k) => $arr[$k], $order) : $arr;

            $q['options'] = $reorder($opts);
            if (isset($q['image_prompts'])) $q['image_prompts'] = $reorder($q['image_prompts']);
            if (isset($q['image_options'])) $q['image_options'] = $reorder($q['image_options']);

            $newPos = array_search($correctIdx, $order, true);
            $q['correct_answer'] = chr(65 + ($newPos === false ? 0 : $newPos));
            return $q;
        }, $questions);
    }

    /**
     * Drop questions whose options/correct_answer are structurally broken —
     * this is what caused exercises where the option marked "correct" didn't
     * match the explanation at all (duplicate options, correct_answer letter
     * pointing outside the options array, or an empty/blank option chosen as
     * correct). Non-choice question types (gap-fill, essay, etc.) pass through
     * untouched since they have no `options` array to validate.
     * $componentKey additionally gates a required non-empty `explanation` for
     * the grammar-drilling types that already ask for one in their prompt
     * format (mcq, gap-fill, sentence-completion) — these are exactly the
     * items where a learner most needs the rule explained, and it was being
     * silently omitted by Mistral without any retry being triggered.
     */
    /**
     * Generic placeholder/sentinel content that has occasionally leaked out of
     * Mistral as a fallback response instead of a real exercise (e.g. a
     * matching exercise whose payload was actually "What is this exercise
     * about?" / "Language practice" in English, regardless of the requested
     * language/component) — confirmed on real generated data (Exercise #354).
     */
    private const SENTINEL_TEXTS = ['what is this exercise about', 'language practice'];

    private function dropInvalidChoiceQuestions(array $questions, ?string $componentKey = null): array
    {
        $explanationRequiredTypes = ['mcq', 'gap-fill', 'sentence-completion'];
        $requireExplanation = in_array($componentKey, $explanationRequiredTypes, true);

        return array_values(array_filter($questions, function ($q) use ($requireExplanation, $componentKey) {
            // Reject if the question's own declared `type` doesn't match the
            // component_key it was generated for — Mistral occasionally returns
            // a generic/wrong-type payload (confirmed: a "matching" request
            // answered with type "mcq" and English placeholder content instead
            // of a real exercise in the target language).
            $declaredType = $q['type'] ?? null;
            if ($componentKey && is_string($declaredType) && $declaredType !== $componentKey
                && !in_array($declaredType, ['role-play', 'synthesis'], true)) {
                // role-play/synthesis are legitimate shared render types used by
                // several component_keys (oral-debate, negotiation, synthesis-essay…).
                return false;
            }
            $text = mb_strtolower(trim((string)($q['text'] ?? '')));
            foreach (self::SENTINEL_TEXTS as $sentinel) {
                if ($text !== '' && str_contains($text, $sentinel)) {
                    return false;
                }
            }

            $opts = $q['options'] ?? null;
            if (!is_array($opts)) {
                if ($requireExplanation && empty(trim((string)($q['explanation'] ?? '')))) {
                    return false;
                }
                return true; // not a choice-based question type — nothing else to validate
            }

            // Options must be non-empty, distinct strings.
            $normalized = array_map(fn($o) => is_string($o) ? trim(mb_strtolower($o)) : null, $opts);
            if (in_array(null, $normalized, true) || in_array('', $normalized, true)) {
                return false;
            }
            if (count(array_unique($normalized)) !== count($normalized)) {
                return false; // duplicate options → shuffle/answer becomes ambiguous
            }

            $ca = $q['correct_answer'] ?? null;
            if (!is_string($ca)) {
                return false;
            }
            $letter = strtoupper(trim($ca));
            if (!preg_match('/^[A-Z]$/', $letter)) {
                return false; // choice question must resolve to a single letter after shuffling
            }
            $idx = ord($letter) - 65;
            if ($idx < 0 || $idx >= count($opts)) {
                return false;
            }

            if ($requireExplanation && empty(trim((string)($q['explanation'] ?? '')))) {
                return false;
            }

            return true;
        }));
    }

    /**
     * Validate the multi-field question types (correct_answers is a key→value
     * map rather than a single letter). Without this, exercises like
     * summary-completion could ship with a correct_answers value that isn't
     * even present in word_list — the student could then NEVER pick the right
     * answer from the dropdown, since it only lists word_list entries.
     */
    private function dropInvalidMultiFieldQuestions(array $questions, string $componentKey): array
    {
        $multiFieldTypes = ['note-completion', 'form-completion', 'summary-completion', 'table-completion', 'flow-chart-completion', 'multiple-matching', 'diagram-labeling', 'open-cloze'];
        if (!in_array($componentKey, $multiFieldTypes, true)) {
            return $questions;
        }

        $norm = fn($s) => is_string($s) ? trim(mb_strtolower($s)) : null;

        return array_values(array_filter($questions, function ($q) use ($componentKey, $norm) {
            $ca = $q['correct_answers'] ?? null;

            switch ($componentKey) {
                case 'note-completion':
                    $blanks = array_keys(array_filter($q['notes'] ?? [], fn($n) => ($n['value'] ?? null) === ''));
                    return empty($blanks) || (is_array($ca) && count($ca) > 0);

                case 'form-completion':
                    $blanks = array_keys(array_filter($q['fields'] ?? [], fn($f) => ($f['value'] ?? null) === ''));
                    return empty($blanks) || (is_array($ca) && count($ca) > 0);

                case 'flow-chart-completion':
                    $blanks = array_keys(array_filter($q['steps'] ?? [], fn($s) => !empty($s['is_blank'])));
                    return empty($blanks) || (is_array($ca) && count($ca) > 0);

                case 'summary-completion':
                    if (!is_array($ca) || !isset($q['word_list']) || !is_array($q['word_list'])) {
                        return !is_array($ca) ? true : false;
                    }
                    $wordList = array_map($norm, $q['word_list']);
                    foreach ($ca as $v) {
                        if (!in_array($norm($v), $wordList, true)) {
                            return false; // answer not selectable from the dropdown → unsolvable
                        }
                    }
                    return true;

                case 'table-completion':
                    $rows = $q['rows'] ?? null;
                    if (!is_array($rows)) {
                        return true;
                    }
                    $blankCount = 0;
                    foreach ($rows as $row) {
                        if (is_array($row)) {
                            foreach ($row as $cell) {
                                if ($cell === '') $blankCount++;
                            }
                        }
                    }
                    return $blankCount === 0 || (is_array($ca) && count($ca) > 0);

                case 'multiple-matching':
                    $textIds = array_map(fn($t) => $t['id'] ?? null, $q['texts'] ?? []);
                    $statementIds = array_map(fn($s) => $s['id'] ?? null, $q['statements'] ?? []);
                    if (empty($statementIds) || !is_array($ca)) {
                        return !empty($statementIds) ? false : true;
                    }
                    foreach ($statementIds as $sid) {
                        if (!array_key_exists($sid, $ca) || !in_array($ca[$sid], $textIds, true)) {
                            return false;
                        }
                    }
                    return true;

                case 'diagram-labeling':
                    $labelIds = array_map(fn($l) => $l['id'] ?? null, $q['labels'] ?? []);
                    if (empty($labelIds)) {
                        return true;
                    }
                    if (!is_array($ca)) {
                        return false;
                    }
                    foreach ($labelIds as $lid) {
                        if (empty($ca[$lid] ?? null)) {
                            return false;
                        }
                    }
                    return true;

                case 'open-cloze':
                    $text = $q['text'] ?? '';
                    preg_match_all('/\((\d+)\)___/', is_string($text) ? $text : '', $matches);
                    $gapCount = count($matches[1] ?? []);
                    return $gapCount === 0 || (is_array($ca) && count($ca) >= $gapCount);

                default:
                    return true;
            }
        }));
    }

    /**
     * Scale a writing task's min/max word count to the CEFR level, instead of
     * a fixed value. Confirmed on real data (Exercise #359) that a fixed
     * 150-250 words for essay-editor was applied even at A1, where the actual
     * CEFR/Goethe expectation is closer to 30-40 words — wildly unrealistic
     * for a true beginner. Ratios roughly follow short-answer's existing
     * C1/C2 special-case, generalised across the whole scale.
     */
    private function wordRangeFor(string $difficulty): array
    {
        return match ($difficulty) {
            'A0', 'A1' => [30, 50],
            'A2' => [50, 80],
            'B1' => [80, 120],
            'B2' => [120, 180],
            'C1', 'C2' => [180, 250],
            default => [80, 120],
        };
    }

    private function buildPrompt(ExerciseType $exerciseType, Exam $exam, string $difficulty, string $language, string $languageNative, ?array $lessonContext = null): string
    {
        $componentKey = $exerciseType->component_key;
        $skillType = $exerciseType->section?->skill_type ?? 'reading';
        $nativeLang = $lessonContext['native_language'] ?? 'Français';

        // Lesson-aware context: when an exercise is generated AFTER a lesson, it MUST test
        // the lesson's specific concept (e.g. "grammar.simple_present") — not random content.
        $lessonDirective = '';
        if ($lessonContext) {
            $title = $lessonContext['title'] ?? '';
            $concept = $lessonContext['concept'] ?? '';
            $isSynthesis = !empty($lessonContext['is_synthesis']);
            $conceptsMixed = $lessonContext['concepts_to_mix'] ?? [];

            if ($isSynthesis) {
                $conceptsList = implode(', ', $conceptsMixed);
                $lessonDirective = <<<DIRECTIVE

CRITICAL — CHAPTER SYNTHESIS (BOSS LEVEL)
This is the END-OF-CHAPTER synthesis. The student just completed multiple
lessons in this chapter. The concepts covered are: {$conceptsList}.

This exercise MUST mix questions covering DIFFERENT concepts from the list above.
- Each of the 5 questions should test a DIFFERENT concept from the list (one per concept)
- Questions must require RECALL from long-term memory (not just re-reading a passage)
- Difficulty matches the chapter level
- Instructions in {$nativeLang} for A0/A1/A2 students
- This is a high-stakes test of mastery — write clear, fair, distinct questions

Output 5 questions. Each question's `text` should clearly indicate which concept
it tests (e.g. "Pronoms : ___" or "Présent simple : ___").
DIRECTIVE;
            } else {
                $lessonDirective = <<<DIRECTIVE

CRITICAL — LESSON-ALIGNED EXERCISE
The student just finished the lesson: "{$title}" (concept: {$concept}).
This exercise MUST test that specific concept. Examples:
- If concept involves verb conjugation: gap-fills must blank out the verb to conjugate
- If concept involves pronouns: gap-fills must blank out the pronoun
- If concept involves vocabulary: gap-fills must blank out a vocabulary item the lesson taught
- If concept involves grammar rule: produce sentences that require applying THAT rule

FORBIDDEN: producing a generic reading-comprehension where the gap answer is
literally findable by copying a word from the passage 1-2 sentences earlier.
The student must apply REASONING from the lesson, not visual recall.

For beginners (A0/A1/A2 native: {$nativeLang}):
- Instructions/questions in {$nativeLang}
- Example sentences in {$language} with {$nativeLang} translations
- Each gap-fill should isolate ONE concept point at a time
DIRECTIVE;
            }
        }

        [$wMin, $wMax] = $this->wordRangeFor($difficulty);

        $isListening = $skillType === 'listening';
        // In LISTENING, the audio is the CONTENT to understand (a 60-100-word
        // dialogue/monologue), and questions test comprehension of it by ear. The
        // spoken passage must carry the same audio_text on EACH question so the
        // player can play it. The questions must NOT restate the audio in writing.
        $audioField = $isListening ? ', audio_text (string: the SAME spoken passage of 60-100 words in ' . $language . ' — a short dialogue or monologue — repeated identically on every question; questions ask about details/intentions/facts that can only be answered by listening, NOT by reading the question)' : '';

        $listeningDirective = $isListening
            ? "\n\nLISTENING RULES:\n- audio_text is the recording (a 60-100-word dialogue/monologue). It is the SAME on every question.\n- Questions test comprehension BY EAR (who, what, where, why, intention). Do NOT write the answer in the question.\n- Never just name a term and ask to match it to its definition — that's not listening."
            : '';

        // A beginner (A0/A1/A2) cannot parse a scenario/role instruction written
        // entirely in the target language — they don't know enough of it yet. So
        // for speaking exercises, the STAGE DIRECTIONS (scenario, role, candidate
        // hints) go in the student's native language, while the examiner's actual
        // spoken lines (what gets read aloud via TTS) stay in the target language —
        // that's the whole point of a listening/speaking exercise.
        $isBeginnerLevel = in_array($difficulty, ['A0', 'A1', 'A2'], true);
        $scenarioLang = $isBeginnerLevel ? $nativeLang : $language;
        $speakingDirective = $isBeginnerLevel
            ? "\n\nBEGINNER SPEAKING ({$difficulty}): the student does not read {$language} fluently yet.\n- \"scenario\", \"role\", and every candidate \"prompt\" (hint) MUST be written in {$nativeLang}, so the student understands WHAT to do.\n- Only the examiner's spoken \"text\" lines (read aloud via TTS) stay in {$language}, and must use SIMPLE, SHORT, high-frequency {$difficulty}-level sentences.\n- Never require the student to have read a {$language} instruction to know how to respond — the instruction itself must be in {$nativeLang}."
            : '';

        // Same beginner problem as speaking, but for the plain "text" instruction
        // field of types that have a REAL standalone instruction (as opposed to
        // summary-completion/open-cloze, where "text" IS the content to fill in —
        // translating that would break the exercise). Only applies when there is
        // no lessonContext already handling the native-language instruction.
        $instructionAdaptedTypes = ['form-completion', 'table-completion', 'flow-chart-completion', 'diagram-labeling', 'gapped-text', 'ordering'];
        $instructionLang = ($isBeginnerLevel && in_array($componentKey, $instructionAdaptedTypes, true)) ? $nativeLang : $language;
        $instructionDirective = ($isBeginnerLevel && in_array($componentKey, $instructionAdaptedTypes, true) && !$lessonContext)
            ? "\n\nBEGINNER INSTRUCTION ({$difficulty}): the student does not read {$language} fluently yet.\n- The \"text\" instruction field MUST be written in {$nativeLang}, so the student understands WHAT is asked.\n- The actual content to complete/label/reorder (fields/rows/steps/labels/paragraphs/sentence) MUST STAY in {$language} — only the meta-instruction telling the student what to do switches language."
            : '';

        $questionFormat = match ($componentKey) {
            // --- Original components ---
            'mcq' => 'Array of 3 questions, each with: id (string q1/q2/q3), type ("mcq"), text (question in ' . $language . '), options (array of exactly 4 answer choices in ' . $language . ', do NOT include letters like "A)" prefix), correct_answer (ONLY the letter: "A", "B", "C", or "D" corresponding to the correct option index), explanation (string in ' . $language . ')' . $audioField,
            'true-false-ng' => 'Array of 3 statements, each with: id (string), type ("true-false-ng"), text (statement in ' . $language . '), correct_answer ("True"/"False"/"Not Given"), explanation (string)' . $audioField,
            'gap-fill' => 'Array of 3 items, each with: id (string), type ("gap-fill"), text (sentence with ___ for blank, in ' . $language . '), correct_answer (string), explanation (string)' . $audioField,
            'matching' => 'Array of 4 questions, each with: id (string q1-q4), type ("matching"), text (the term/concept to identify in ' . $language . '), options (array of exactly 4 definitions in ' . $language . ' — one correct, three plausible distractors, shuffled), correct_answer (ONLY the letter "A", "B", "C", or "D" for the correct option)' . $audioField,
            'essay-editor' => 'Array of 1 question with: id (string), type ("essay-editor"), text (writing prompt in ' . $language . ', scoped to what a ' . $difficulty . ' learner can realistically write about), min_words (' . $wMin . '), max_words (' . $wMax . '), correct_answer (null — scored manually)',
            'sentence-completion' => 'Array of 3 items, each with: id (string), type ("sentence-completion"), text (incomplete sentence in ' . $language . '), options (array of 4 choices), correct_answer (letter "A"/"B"/"C"/"D"), explanation (string)' . $audioField,
            'short-answer' => 'Array of 3 items, each with: id (string), type ("short-answer"), text (question in ' . $language . '), correct_answer (' . (in_array($difficulty, ['C1', 'C2'], true) ? 'a concise written answer, one short sentence ~10-15 words, capturing the key idea' : 'short answer string, max 3 words') . '), explanation (string)' . $audioField,
            'note-completion' => 'Array of 1 question with: id (string), type ("note-completion"), notes (array of objects {label: string, value: string} where value is blank "" for items the student must fill, or pre-filled text), correct_answers (object whose keys are the 0-BASED INDEX of each blank note in the notes array — e.g. if notes[1] and notes[3] are blank, correct_answers is {"1": "...", "3": "..."})' . ($isListening ? ', audio_text (string: spoken passage 80-120 words in ' . $language . ' — the recording students listen to)' : ''),
            'ordering' => 'Array of 1 question with: id (string), type ("ordering"), text (instruction in ' . $instructionLang . '), items (array of 5-6 items to reorder, in CORRECT order), correct_order (array of ids in correct sequence)',
            'dictation' => 'Array of 1 question with: id (string), type ("dictation"), audio_text (text to be read aloud in ' . $language . ', 30-50 words), text (instruction), correct_answer (the exact text)',
            'open-cloze' => 'Array of 1 question with: id (string), type ("open-cloze"), text (a COHERENT connected passage of 80-120 words in ' . $language . ' with 8-10 numbered gaps written EXACTLY as (1)___, (2)___, (3)___ … in reading order; each gap targets grammar/function words the student must PRODUCE — articles, prepositions, verb forms, conjunctions, pronouns — NOT random content words copyable from context), correct_answers (object mapping "1","2",… to the exact expected word for each gap)',
            'word-formation' => 'Array of 3 items, each with: id (string), type ("word-formation"), text (sentence with ___ gap), root_word (base word to transform), correct_answer (the transformed word), explanation (string)',
            'key-word-transformation' => 'Array of 3 items, each with: id (string), type ("key-word-transformation"), original_sentence (in ' . $language . '), key_word (word that must be used), correct_answer (transformed sentence), explanation (string)',

            // --- Sprint 1: Simple text components ---
            'short-writing' => 'Array of 1 question with: id (string), type ("short-writing"), text (writing prompt in ' . $language . ', e.g. write a postcard/email/message), context (optional context string), min_words (30), max_words (80), correct_answer (null)',
            'form-completion' => 'Array of 1 question with: id (string), type ("form-completion"), text (instruction in ' . $instructionLang . '), fields (array of 6-8 objects {label: string in ' . $language . ', value: string or "" for blanks, type: "text"|"date"|"select", options?: string[] for select}), correct_answers (object whose keys are the 0-BASED INDEX of each blank field in the fields array — e.g. if fields[2] is blank, correct_answers is {"2": "..."})',
            'summary-completion' => 'Array of 1 question with: id (string), type ("summary-completion"), text (summary passage in ' . $language . ' with ___ for each blank), word_list (array of 8-10 words, including correct answers plus distractors), correct_answers (object mapping blank index "0","1",etc. to correct word)',

            // --- Sprint 2: Table/visual components ---
            'table-completion' => 'Array of 1 question with: id (string), type ("table-completion"), text (instruction in ' . $instructionLang . '), headers (array of column header strings), rows (2D array of strings, use "" for blank cells students must fill), correct_answers (object mapping "rowIndex-colIndex" to correct value for each blank cell)',
            'flow-chart-completion' => 'Array of 1 question with: id (string), type ("flow-chart-completion"), text (instruction in ' . $instructionLang . '), steps (array of objects {text: string or "" for blanks, is_blank: boolean}), correct_answers (object mapping blank step indices to correct text)',
            'multiple-matching' => 'Array of 1 question with: id (string), type ("multiple-matching"), texts (array of 4 objects {id: "A"/"B"/"C"/"D", title: string, content: string in ' . $language . '}), statements (array of 6 objects {id: "s1"-"s6", text: string in ' . $language . '}), correct_answers (object mapping statement id to text id, e.g. {"s1":"B","s2":"A",...})',

            // --- Sprint 3: Interactive components ---
            'insert-text' => 'Array of 1 question with: id (string), type ("insert-text"), sentence (the sentence to insert, in ' . $language . '), passage (text with markers [A], [B], [C], [D] where sentence could be inserted), correct_answer ("A"/"B"/"C"/"D")',
            'gapped-text' => 'Array of 1 question with: id (string), type ("gapped-text"), text (instruction in ' . $instructionLang . '), passage_parts (array of strings, the main text split at gaps), paragraphs (array of objects {id: "p1"-"p5", text: string in ' . $language . '} — paragraphs to place in gaps), correct_order (array of paragraph ids in correct gap order)',
            'graph-description' => 'Array of 1 question with: id (string), type ("graph-description"), text (writing prompt describing the chart in ' . $language . '), chart_data ({type: "bar"|"line"|"pie", labels: string[], datasets: [{label: string, data: number[]}]}), min_words (' . $wMin . '), max_words (' . $wMax . '), correct_answer (null)',
            'academic-discussion' => 'Array of 1 question with: id (string), type ("academic-discussion"), professor_prompt (discussion topic in ' . $language . '), student_posts (array of 2 objects {name: string, text: opinion in ' . $language . '}), writing_prompt (instruction for student response in ' . $language . '), min_words (' . $wMin . '), max_words (' . $wMax . '), correct_answer (null)',

            // --- Sprint 4: Audio/Speaking components ---
            // scenario/role/prompt use $scenarioLang (native language for A0-A2 beginners,
            // target language otherwise) since they're instructions the student must
            // understand — only the examiner's spoken "text" stays in $language.
            'speaking-recorder' => 'Array of 1 question with: id (string), type ("speaking-recorder"), text (speaking prompt in ' . $scenarioLang . ', e.g. describe an experience, give opinion on topic), prep_time (30), speak_time (60), image_url (null), correct_answer (null)',
            'role-play' => 'Array of 1 question with: id (string), type ("role-play"), scenario (situation description in ' . $scenarioLang . '), role (candidate role in ' . $scenarioLang . '), dialogue_turns (array of 4-6 objects {speaker: "examiner"|"candidate", text: string for examiner lines in ' . $language . ', prompt: string for candidate hints in ' . $scenarioLang . '}), correct_answer (null)',
            // Interactive speaking — all rendered by the role-play component (type "role-play"),
            // alternating examiner (spoken via TTS) ↔ candidate (records, live-scored).
            'oral-debate' => 'Array of 1 question with: id (string), type ("role-play"), scenario (a debatable topic + the examiner\'s stance, in ' . $scenarioLang . '), role ("Tu défends ton point de vue face à l\'examinateur" translated to ' . $scenarioLang . ' if needed), dialogue_turns (array of 6 objects ALTERNATING: examiner turns have text = a provocative claim/counter-argument in ' . $language . '; candidate turns have prompt = what to argue, in ' . $scenarioLang . ', e.g. "Réfute cet argument avec un exemple"), correct_answer (null)',
            'negotiation' => 'Array of 1 question with: id (string), type ("role-play"), scenario (a situation requiring agreement, e.g. planning an outing together / sharing tasks, in ' . $scenarioLang . '), role (candidate role, in ' . $scenarioLang . '), dialogue_turns (array of 6 objects ALTERNATING: examiner text = a proposal or objection in ' . $language . '; candidate prompt = in ' . $scenarioLang . ', e.g. "Propose une alternative et justifie" / "Trouve un compromis"), correct_answer (null)',
            'speaking-elicitation' => 'Array of 1 question with: id (string), type ("role-play"), scenario (a context where the candidate must OBTAIN information, e.g. about a job ad / a course, in ' . $scenarioLang . '), role ("Tu poses des questions pour obtenir des informations" in ' . $scenarioLang . '), dialogue_turns (array of 5 objects: each examiner turn text = a brief setup/answer in ' . $language . ', each candidate prompt = in ' . $scenarioLang . ', e.g. "Pose une question pour savoir : [horaires/prix/lieu/…]"), correct_answer (null)',
            'listen-repeat' => 'Array of 3 items, each with: id (string), type ("listen-repeat"), audio_text (a sentence of 8-15 words in ' . $language . ' to be read aloud and repeated), correct_answer (the same sentence exactly — used to score pronunciation fidelity)',
            'picture-mcq' => 'Array of 3 questions, each with: id (string), type ("picture-mcq"), text (the question/instruction in ' . $language . '), image_prompts (array of EXACTLY 4 short English scene descriptions for image generation, e.g. "a woman drinking coffee at a café", "a man riding a bicycle"; concrete, distinct, simple), options (array of 4 short ' . $language . ' labels matching each image), correct_answer (the letter "A","B","C" or "D" of the right picture)' . ($isListening ? ', audio_text (the SAME spoken passage on each question — what the student hears and must match to a picture)' : ''),
            // TOEFL 2026
            'complete-the-words' => 'Array of 1 question with: id (string), type ("complete-the-words"), text (an academic paragraph of 60-90 words in ' . $language . ' where 6-10 words have MISSING LETTERS shown as the first 2-3 letters followed by underscores for each missing letter, e.g. "The eco___ grew while gov______ spending fell"), correct_answers (object mapping "0","1",… in reading order to the FULL correct word for each blanked word)',
            'build-a-sentence' => 'Array of 3 questions, each with: id (string), type ("build-a-sentence"), text (the preceding line of a short student exchange in ' . $language . ', for context), words (array of the sentence tokens IN CORRECT ORDER — the UI shuffles them), correct_answer (the full correct sentence string)',
            'listen-choose-response' => 'Array of 3 questions, each with: id (string), type ("listen-choose-response"), audio_text (a single short spoken sentence or question in ' . $language . ' — e.g. "Do you know where the library is?"), options (array of 4 possible spoken replies in ' . $language . ', one pragmatically correct, three plausible but wrong), correct_answer (the letter of the best reply)',
            // Écriture guidée (AI-évaluée)
            'guided-rewrite' => 'Array of 1 question with: id (string), type ("guided-rewrite"), source_text (a passage of 80-120 words in ' . $language . '), text (instruction in ' . $language . ': reformuler/résumer le passage en utilisant les mots imposés), must_use (array of 3-5 mandatory words/expressions in ' . $language . '), min_words (40), max_words (80), correct_answer (null — scored by AI)',
            'text-continuation' => 'Array of 1 question with: id (string), type ("text-continuation"), source_text (the BEGINNING of a story/fait divers, 2-3 sentences in ' . $language . '), text (instruction: continuer le texte de façon cohérente), min_words (80), max_words (120), correct_answer (null — scored by AI)',

            // --- Sprint 5: Complex components ---
            'diagram-labeling' => 'Array of 1 question with: id (string), type ("diagram-labeling"), text (instruction in ' . $instructionLang . '), image_url (null — will use placeholder), labels (array of 4-6 objects {id: "l1"-"l6", x: number 10-90, y: number 10-90, answer: correct label string}), correct_answers (object mapping label id to correct text)',
            'synthesis' => 'Array of 1 question with: id (string), type ("synthesis"), documents (array of 2-3 objects {title: string, content: text of 100-150 words in ' . $language . '}), writing_prompt (synthesis instruction in ' . $language . '), min_words (220), max_words (250), correct_answer (null)',
            'synthesis-essay' => 'Array of 1 question with: id (string), type ("synthesis") [the component renders "synthesis"], documents (array of 2-3 objects {title: string, content: text of 120-180 words in ' . $language . ' presenting DIFFERENT viewpoints on a topic}), writing_prompt (instruction in ' . $language . ': first SYNTHESISE the documents, THEN take and defend a personal position — DALF C2 style), min_words (250), max_words (300), correct_answer (null)',
            'integrated-task' => 'Array of 1 question with: id (string), type ("integrated-task"), reading_passage ({title: string, content: 200-word text in ' . $language . '}), audio_text (100-word listening text in ' . $language . ' for TTS), audio_lang ("' . strtolower(substr($language, 0, 2)) . '"), response_type ("writing"), writing_prompt (instruction in ' . $language . '), min_words (150), max_words (225), correct_answer (null)',

            default => 'Array of 3 questions with: id (string), type ("mcq"), text, options (4 choices), correct_answer, explanation',
        };

        // Determine content structure based on component type.
        // For lesson-aligned grammar/vocab exercises (gap-fill, sentence-completion,
        // short-answer, mcq, matching), the passage is usually a distractor: it tempts
        // students to copy-paste instead of applying the concept. Skip the passage when
        // we have lesson context, unless the skill is reading.
        $needsPassage = in_array($componentKey, ['mcq', 'true-false-ng', 'gap-fill', 'matching', 'sentence-completion', 'short-answer', 'open-cloze']);
        if ($needsPassage && $lessonContext && $skillType !== 'reading') {
            $needsPassage = false;
        }

        $contentStructure = $needsPassage
            ? '"content": {"passage": "A ' . $difficulty . '-level text in ' . $language . ' (150-200 words) — questions must require INFERENCE, not literal copying from the passage", "instructions": "Instructions in ' . $language . '"}'
            : '"content": {"instructions": "Instructions in ' . $language . '"}';

        $gapFillBan = $componentKey === 'gap-fill'
            ? "\n\nCRITICAL FOR GAP-FILL (grammar drilling — make it CHALLENGING):\n- The answer to each blank MUST NOT appear verbatim in the passage or in any other question.\n- Each gap tests grammar or vocabulary the student has to KNOW and PRODUCE, not COPY.\n- When the gap is a VERB: put the infinitive/base form in parentheses right after the blank so the learner knows what to conjugate, e.g. (in {$language}) 'Gestern ___ (gehen) ich ins Kino.' → answer 'ging' (correct tense + person).\n- When the gap tests WORD FORM/ENDINGS (e.g. German adjective endings, plurals, declensions): give the base word in parentheses and require the correctly inflected form, e.g. 'Ich sehe einen ___ (groß) Hund.' → 'großen'.\n- Vary tenses/persons/cases across the 3 items so it really tests the rule.\n- Bad: 'Madrid is a ___ city' with 'big' visible earlier. NEVER do this."
            : '';

        // Comprehension grounding: for reading/listening, EVERY question must be
        // answerable from the passage/recording alone. This stops the recurring
        // problem of questions asking about facts that are not in the text.
        $isComprehension = in_array($skillType, ['reading', 'listening'], true);
        $comprehensionGuard = $isComprehension
            ? "\n\nCRITICAL FOR THIS COMPREHENSION EXERCISE:\n- EVERY question's correct answer MUST be explicitly stated in, or directly inferable from, the passage/recording. NEVER ask about a detail, name, number or fact that is not present in the text.\n- Distractors must be plausible but clearly wrong according to the text.\n- For listening, the passage IS the transcript that will be read aloud (the student does NOT see it) — questions must be answerable by ear, so keep them concrete and not dependent on exact spelling.\n- Do NOT require outside/world knowledge the text doesn't provide."
            : '';

        // Level guard: keep vocabulary and structures within the target CEFR level so
        // the learner isn't tested on notions far beyond what they're learning.
        $levelGuard = "\n\nLEVEL: Keep vocabulary, grammar and topics appropriate for CEFR {$difficulty}. Do not use structures or rare vocabulary clearly above {$difficulty}.";

        return <<<PROMPT
Generate a {$exerciseType->name} exercise for the {$exam->name} exam at CEFR {$difficulty} level.
The exercise must be entirely in {$language} ({$languageNative}) — this is a {$language} language exam.
Skill tested: {$skillType}.{$lessonDirective}{$gapFillBan}{$comprehensionGuard}{$levelGuard}{$listeningDirective}{$speakingDirective}{$instructionDirective}

Return JSON with exactly this structure:
{
  {$contentStructure},
  "questions": [{$questionFormat}]
}

Important: ALL text (passage, questions, options, explanations) must be in {$language}, EXCEPT where a field above is explicitly marked to be in the student's native language (beginner speaking/instruction fields). No English unless it's an English exam.
For writing/speaking exercises, correct_answer should be null (these are scored differently).

CRITICAL FOR ANY QUESTION WITH "options": correct_answer MUST be the letter of
the option that is WORD-FOR-WORD identical to one of the strings in "options".
Before answering, re-read your own "options" array and pick the letter that
matches EXACTLY — do not answer with a word that isn't literally one of the
options (e.g. do not explain "Morgen" if "Morgen" isn't in the options list).
The "explanation" must refer only to the text of the actual chosen option.

CRITICAL FOR MULTI-FIELD TYPES (correct_answers is a key→value map): the keys
of correct_answers MUST exactly match the blank positions declared in the
structural field (notes/fields/steps/rows/labels) — do not invent extra keys
or omit any blank. FOR SUMMARY-COMPLETION SPECIFICALLY: every value in
correct_answers MUST be a word/phrase that appears WORD-FOR-WORD in word_list
— re-check that each correct answer is literally present in your own
word_list array before answering, since the student can only pick from it.
PROMPT;
    }

    // (getFallbackContent / getFallbackQuestions supprimés : ils créaient des
    //  exercices placeholder "What is this exercise about?" montrés aux apprenants.
    //  Le générateur throw désormais en cas d'échec IA — pas de junk persisté.)

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
