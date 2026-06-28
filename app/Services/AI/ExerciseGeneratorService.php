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

    private function buildPrompt(ExerciseType $exerciseType, Exam $exam, string $difficulty, string $language, string $languageNative, ?array $lessonContext = null): string
    {
        $componentKey = $exerciseType->component_key;
        $skillType = $exerciseType->section?->skill_type ?? 'reading';

        // Lesson-aware context: when an exercise is generated AFTER a lesson, it MUST test
        // the lesson's specific concept (e.g. "grammar.simple_present") — not random content.
        $lessonDirective = '';
        if ($lessonContext) {
            $title = $lessonContext['title'] ?? '';
            $concept = $lessonContext['concept'] ?? '';
            $nativeLang = $lessonContext['native_language'] ?? 'Français';
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

        $isListening = $skillType === 'listening';
        $audioField = $isListening ? ', audio_text (string: 1-2 sentences in ' . $language . ' that will be read aloud by TTS — the spoken question or instruction)' : '';

        $questionFormat = match ($componentKey) {
            // --- Original components ---
            'mcq' => 'Array of 3 questions, each with: id (string q1/q2/q3), type ("mcq"), text (question in ' . $language . '), options (array of exactly 4 answer choices in ' . $language . ', do NOT include letters like "A)" prefix), correct_answer (ONLY the letter: "A", "B", "C", or "D" corresponding to the correct option index), explanation (string in ' . $language . ')' . $audioField,
            'true-false-ng' => 'Array of 3 statements, each with: id (string), type ("true-false-ng"), text (statement in ' . $language . '), correct_answer ("True"/"False"/"Not Given"), explanation (string)' . $audioField,
            'gap-fill' => 'Array of 3 items, each with: id (string), type ("gap-fill"), text (sentence with ___ for blank, in ' . $language . '), correct_answer (string), explanation (string)' . $audioField,
            'matching' => 'Array of 4 questions, each with: id (string q1-q4), type ("matching"), text (the term/concept to identify in ' . $language . '), options (array of exactly 4 definitions in ' . $language . ' — one correct, three plausible distractors, shuffled), correct_answer (ONLY the letter "A", "B", "C", or "D" for the correct option)' . $audioField,
            'essay-editor' => 'Array of 1 question with: id (string), type ("essay-editor"), text (writing prompt in ' . $language . '), min_words (integer, e.g. 150), max_words (integer, e.g. 250), correct_answer (null — scored manually)',
            'sentence-completion' => 'Array of 3 items, each with: id (string), type ("sentence-completion"), text (incomplete sentence in ' . $language . '), options (array of 4 choices), correct_answer (letter "A"/"B"/"C"/"D"), explanation (string)' . $audioField,
            'short-answer' => 'Array of 3 items, each with: id (string), type ("short-answer"), text (question in ' . $language . '), correct_answer (short answer string, max 3 words), explanation (string)' . $audioField,
            'note-completion' => 'Array of 1 question with: id (string), type ("note-completion"), notes (array of objects {label: string, value: string} where value is blank "" for items the student must fill, or pre-filled text), correct_answers (object mapping blank indices to correct strings)' . ($isListening ? ', audio_text (string: spoken passage 80-120 words in ' . $language . ' — the recording students listen to)' : ''),
            'ordering' => 'Array of 1 question with: id (string), type ("ordering"), text (instruction in ' . $language . '), items (array of 5-6 items to reorder, in CORRECT order), correct_order (array of ids in correct sequence)',
            'dictation' => 'Array of 1 question with: id (string), type ("dictation"), audio_text (text to be read aloud in ' . $language . ', 30-50 words), text (instruction), correct_answer (the exact text)',
            'open-cloze' => 'Array of 1 question with: id (string), type ("open-cloze"), text (passage with numbered gaps like (1)___, (2)___), correct_answers (object mapping "1" to answer, "2" to answer, etc.)',
            'word-formation' => 'Array of 3 items, each with: id (string), type ("word-formation"), text (sentence with ___ gap), root_word (base word to transform), correct_answer (the transformed word), explanation (string)',
            'key-word-transformation' => 'Array of 3 items, each with: id (string), type ("key-word-transformation"), original_sentence (in ' . $language . '), key_word (word that must be used), correct_answer (transformed sentence), explanation (string)',

            // --- Sprint 1: Simple text components ---
            'short-writing' => 'Array of 1 question with: id (string), type ("short-writing"), text (writing prompt in ' . $language . ', e.g. write a postcard/email/message), context (optional context string), min_words (30), max_words (80), correct_answer (null)',
            'form-completion' => 'Array of 1 question with: id (string), type ("form-completion"), text (instruction in ' . $language . '), fields (array of 6-8 objects {label: string in ' . $language . ', value: string or "" for blanks, type: "text"|"date"|"select", options?: string[] for select}), correct_answers (object mapping blank field indices to correct values)',
            'summary-completion' => 'Array of 1 question with: id (string), type ("summary-completion"), text (summary passage in ' . $language . ' with ___ for each blank), word_list (array of 8-10 words, including correct answers plus distractors), correct_answers (object mapping blank index "0","1",etc. to correct word)',

            // --- Sprint 2: Table/visual components ---
            'table-completion' => 'Array of 1 question with: id (string), type ("table-completion"), text (instruction in ' . $language . '), headers (array of column header strings), rows (2D array of strings, use "" for blank cells students must fill), correct_answers (object mapping "rowIndex-colIndex" to correct value for each blank cell)',
            'flow-chart-completion' => 'Array of 1 question with: id (string), type ("flow-chart-completion"), text (instruction in ' . $language . '), steps (array of objects {text: string or "" for blanks, is_blank: boolean}), correct_answers (object mapping blank step indices to correct text)',
            'multiple-matching' => 'Array of 1 question with: id (string), type ("multiple-matching"), texts (array of 4 objects {id: "A"/"B"/"C"/"D", title: string, content: string in ' . $language . '}), statements (array of 6 objects {id: "s1"-"s6", text: string in ' . $language . '}), correct_answers (object mapping statement id to text id, e.g. {"s1":"B","s2":"A",...})',

            // --- Sprint 3: Interactive components ---
            'insert-text' => 'Array of 1 question with: id (string), type ("insert-text"), sentence (the sentence to insert, in ' . $language . '), passage (text with markers [A], [B], [C], [D] where sentence could be inserted), correct_answer ("A"/"B"/"C"/"D")',
            'gapped-text' => 'Array of 1 question with: id (string), type ("gapped-text"), text (instruction in ' . $language . '), passage_parts (array of strings, the main text split at gaps), paragraphs (array of objects {id: "p1"-"p5", text: string in ' . $language . '} — paragraphs to place in gaps), correct_order (array of paragraph ids in correct gap order)',
            'graph-description' => 'Array of 1 question with: id (string), type ("graph-description"), text (writing prompt describing the chart in ' . $language . '), chart_data ({type: "bar"|"line"|"pie", labels: string[], datasets: [{label: string, data: number[]}]}), min_words (150), max_words (250), correct_answer (null)',
            'academic-discussion' => 'Array of 1 question with: id (string), type ("academic-discussion"), professor_prompt (discussion topic in ' . $language . '), student_posts (array of 2 objects {name: string, text: opinion in ' . $language . '}), writing_prompt (instruction for student response in ' . $language . '), min_words (100), max_words (150), correct_answer (null)',

            // --- Sprint 4: Audio/Speaking components ---
            'speaking-recorder' => 'Array of 1 question with: id (string), type ("speaking-recorder"), text (speaking prompt in ' . $language . ', e.g. describe an experience, give opinion on topic), prep_time (30), speak_time (60), image_url (null), correct_answer (null)',
            'role-play' => 'Array of 1 question with: id (string), type ("role-play"), scenario (situation description in ' . $language . '), role (candidate role in ' . $language . '), dialogue_turns (array of 4-6 objects {speaker: "examiner"|"candidate", text: string for examiner lines, prompt: string for candidate hints}), correct_answer (null)',

            // --- Sprint 5: Complex components ---
            'diagram-labeling' => 'Array of 1 question with: id (string), type ("diagram-labeling"), text (instruction in ' . $language . '), image_url (null — will use placeholder), labels (array of 4-6 objects {id: "l1"-"l6", x: number 10-90, y: number 10-90, answer: correct label string}), correct_answers (object mapping label id to correct text)',
            'synthesis' => 'Array of 1 question with: id (string), type ("synthesis"), documents (array of 2-3 objects {title: string, content: text of 100-150 words in ' . $language . '}), writing_prompt (synthesis instruction in ' . $language . '), min_words (220), max_words (250), correct_answer (null)',
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
            ? "\n\nCRITICAL FOR GAP-FILL:\n- The answer to each blank MUST NOT appear verbatim in the passage or in any other question.\n- Each gap tests grammar or vocabulary the student has to KNOW, not COPY.\n- Bad example: passage says 'Madrid is a big city' → gap 'Madrid is a ___ city' (answer 'big' is visible). NEVER do this.\n- Good example: passage says 'Anna lives in Madrid' → gap 'Anna ___ (live) in Madrid' (answer 'lives' — tests verb conjugation, the verb form is NOT in the passage)."
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
Skill tested: {$skillType}.{$lessonDirective}{$gapFillBan}{$comprehensionGuard}{$levelGuard}

Return JSON with exactly this structure:
{
  {$contentStructure},
  "questions": [{$questionFormat}]
}

Important: ALL text (passage, questions, options, explanations) must be in {$language}. No English unless it's an English exam.
For writing/speaking exercises, correct_answer should be null (these are scored differently).
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
