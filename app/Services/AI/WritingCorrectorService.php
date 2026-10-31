<?php

namespace App\Services\AI;

class WritingCorrectorService
{
    protected MistralService $mistral;

    public function __construct(MistralService $mistral)
    {
        $this->mistral = $mistral;
    }

    /**
     * @param  list<string>  $criteresOfficiels  les criteres de l'epreuve visee
     *   (« Inhalt », « Textaufbau »...). Ils etaient enregistres avec l'examen mais
     *   lus par personne : la correction notait sur ses criteres generiques, meme
     *   quand l'epreuve en annonce quatre bien precis.
     */
    public function correct(string $text, string $taskDescription, string $examType = 'IELTS', string $feedbackLanguage = 'Français', ?string $cefrLevel = null, array $criteresOfficiels = []): array
    {
        $criteres = $criteresOfficiels === []
            ? ''
            : "

OFFICIAL CRITERIA for this exam paper — judge the text against these, and name them in your feedback:
- ".implode("
- ", $criteresOfficiels)."
";

        $levelCalibration = $cefrLevel
            ? "\n\nIMPORTANT — level calibration:\n- The student's CEFR level is {$cefrLevel}. Calibrate your expectations and scoring to what a {$cefrLevel} learner should realistically produce — do not penalise for advanced structures/vocabulary they are not expected to master yet.\n"
            : '';

        $systemPrompt = "You are an expert $examType examiner. Evaluate the user's writing text based on the standard $examType grading criteria.
Task description: $taskDescription{$criteres}{$levelCalibration}

IMPORTANT — language of feedback:
- Write ALL feedback, explanations and the `explanation` of every correction in {$feedbackLanguage} (the learner's native language). Do NOT write the feedback in English unless {$feedbackLanguage} is English.
- Keep the `original` and `corrected` fields in the SAME language as the learner's text (do not translate the text itself).

IMPORTANT — scoring contract:
- Use the requested exam's writing expectations and the learner's level, but express ALL numeric scores on PrepLa's normalized practice scale from 0 to 9.
- This is a pedagogical indicator, not an official exam score. Never switch to a native exam scale such as 20, 30 or 100 points.
- Keep the four band_scores keys below as shared pedagogical dimensions, even when the exam uses different official criterion names.

IMPORTANT — every correction MUST carry a `category` written as `family.subcategory`.
It is what lets the learner's programme — and the learner's teacher — react: a
composition full of `vocabulary.range` mistakes should bring vocabulary work next.
Choose the UNDERLYING weakness, not the surface symptom. Allowed values:
- grammar.tense, grammar.agreement, grammar.word-order, grammar.article, grammar.preposition,
  grammar.modal, grammar.pronoun, grammar.case, grammar.verb-form, grammar.conjugation,
  grammar.negation, grammar.subordination
- vocabulary.lexical (wrong word), vocabulary.register (wrong formality), vocabulary.collocation,
  vocabulary.range (repetitive or too basic for the level), vocabulary.word-formation, vocabulary.false-friend
- spelling.accent, spelling.capitalisation, spelling.typo
- punctuation
- coherence.connectors, coherence.structure, coherence.reference, coherence.relevance

IMPORTANT — `diagnostic`: beyond individual mistakes, describe the DEEP PATTERNS a
teacher would note in the margin — the ones no single correction shows. Give 2 to 4
observations, each on ONE dimension, each backed by EVIDENCE from the text (quote
the words, count the repetitions, name the missing structure). Examples of the
precision expected: \"« gut » appears 5 times, never a synonym\"; \"only main
clauses: no subordinate clause with weil/dass/obwohl\"; \"informal greeting in a
formal letter\". Do NOT restate individual corrections; do NOT give generic praise.
Dimensions: lexical_range, lexical_precision, syntactic_complexity, grammatical_control,
coherence, register, task_fulfilment.

IMPORTANT — corrections (for inline highlighting):
- The `corrections` array MUST list EVERY concrete mistake: spelling, grammar, word form, punctuation, capitalisation, word choice.
- Each `original` MUST be an EXACT substring copied verbatim from the learner's text (so it can be located and highlighted). Never paraphrase it.
- `corrected` is the fixed version of that exact span.
- If the text is short, still return at least one correction per mistake found. Return an empty array ONLY if the text is genuinely error-free.

Output your evaluation as a JSON object with the following structure:
{
    \"score\": 0.0, // normalized practice score 0-9, not an official exam result
    \"band_scores\": {
        \"task_achievement\": 0.0,
        \"coherence_cohesion\": 0.0,
        \"lexical_resource\": 0.0,
        \"grammar_accuracy\": 0.0
    },
    \"corrections\": [
        {
            \"original\": \"exact substring from the learner's text\",
            \"corrected\": \"corrected version of that span\",
            \"category\": \"family.subcategory, from the allowed list above (e.g. grammar.word-order)\",
            \"explanation\": \"why it was corrected — ONE short sentence, in {$feedbackLanguage}. No preamble.\"
        }
    ],
    \"diagnostic\": [
        {
            \"dimension\": \"ONE of: lexical_range, lexical_precision, syntactic_complexity, grammatical_control, coherence, register, task_fulfilment\",
            \"observation\": \"the pattern, with its evidence from the text — ONE or TWO sentences in {$feedbackLanguage}\",
            \"severity\": \"strength | minor | major\"
        }
    ],
    \"feedback\": \"Concise overall feedback in {$feedbackLanguage}: 3-4 short sentences max — the main strength, the main weakness, and one concrete tip to improve. No restating the task, no filler intro/outro.\"
}";

        $response = $this->mistral->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $text]
        ]);

        $wordCount = str_word_count($text);

        if ($response) {
            $data = json_decode($response, true);
            if ($data) {
                $data['word_count'] = $wordCount;
                return $data;
            }
        }

        return [
            'score' => 0,
            'band_scores' => [
                'task_achievement' => 0,
                'coherence_cohesion' => 0,
                'lexical_resource' => 0,
                'grammar_accuracy' => 0,
            ],
            'corrections' => [],
            'feedback' => 'Erreur de connexion à Mistral AI. Veuillez réessayer.',
            'word_count' => $wordCount,
        ];
    }
}
