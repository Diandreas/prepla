<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'user_id',
        'center_id',
        'creator_id',
        'node_id',
        'skeleton_objective_index',
        'title',
        'concept',
        'theory_markdown',
        'key_takeaways',
        'common_mistakes',
        'comprehension_quiz',
        'based_on_errors',
        'status',
        'generated_at',
        'key_vocabulary',
    ];

    protected function casts(): array
    {
        return [
            'key_takeaways' => 'array',
            'common_mistakes' => 'array',
            'comprehension_quiz' => 'array',
            'based_on_errors' => 'array',
            'generated_at' => 'datetime',
            'key_vocabulary' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(LearningPathNode::class, 'node_id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    /**
     * Scope: lessons for a user in chronological order.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId)->orderBy('created_at', 'desc');
    }

    /**
     * Scope: consolidation/remedial lessons only.
     */
    public function scopeConsolidation($query)
    {
        return $query->whereIn('status', ['consolidation', 'remedial']);
    }

    /**
     * Check if the user passed the comprehension quiz.
     */
    public static function checkAnswerMatch($userAnswer, $correctAnswer): bool
    {
        $userClean = strtolower(trim((string) $userAnswer));
        $correctClean = strtolower(trim((string) $correctAnswer));

        if ($userClean === $correctClean) {
            return true;
        }

        // Extract leading letter (e.g., "A) text" -> letter "A", text "text")
        preg_match('/^([a-z])[\)\.-]?\s*(.*)$/', $userClean, $userMatches);
        preg_match('/^([a-z])[\)\.-]?\s*(.*)$/', $correctClean, $correctMatches);

        $userLetter = $userMatches[1] ?? $userClean;
        $correctLetter = $correctMatches[1] ?? $correctClean;

        // Case 1: Just the letters match. (e.g., both are A, or user answered 'a) text' and correct is 'A')
        // We only do this if correct answer is explicitly designed as a letter or if parsed letters match.
        // Wait, if correct is "C", correctLetter is "c". userLetter is "c". Match!
        if ($userLetter === $correctLetter) {
            return true;
        }

        // Case 2: The text bodies match. (e.g. user selected "B) Option 2" and correct is "A) Option 2" (typo in DB))
        $userText = $userMatches[2] ?? $userClean;
        $correctText = $correctMatches[2] ?? $correctClean;

        if (! empty($userText) && ! empty($correctText) && $userText === $correctText) {
            return true;
        }

        return false;
    }

    /**
     * Resolve a quiz question's stored correct answer to the full option text.
     *
     * Les deux prompts generateurs demandent le TEXTE de l'option ; la lettre et
     * l'indice ne sont que des replis pour ce qu'ils renvoient parfois quand meme.
     * Les hypotheses sont donc rangees du plus sur au plus devinatoire : texte
     * exact, puis lettre, puis indice. Dans l'autre ordre, une bonne reponse d'un
     * seul caractere (« a » parmi « a / an / the ») etait lue comme la lettre A et
     * le quiz annoncait la premiere option ; « 2 » parmi « 1 / 2 / 3 » annoncait
     * « 3 ». Repli sur la valeur brute quand rien ne correspond.
     */
    public static function resolveCorrectAnswerText(array $question): string
    {
        $correct = trim((string) ($question['correct_answer'] ?? ''));
        // Les choix peuvent arriver sous forme d'objets, ou en carte lettree glissee
        // dans la liste : Exercise::optionList remet tout a plat.
        $options = Exercise::optionList($question['options'] ?? []);

        if ($options === []) {
            return $correct;
        }

        // 1. La valeur EST le texte d'une option : c'est ce que les prompts demandent.
        foreach ($options as $texte) {
            if ($texte !== '' && mb_strtolower(trim($correct)) === mb_strtolower(trim($texte))) {
                return $texte;
            }
        }

        // 2. Lettre → indice (A=0, B=1, …), sans tenir compte de la casse.
        if (preg_match('/^[a-zA-Z]$/', $correct)) {
            $texte = $options[ord(strtoupper($correct)) - ord('A')] ?? '';
            if ($texte !== '') {
                return $texte;
            }
        }

        // 3. Indice numerique.
        if (is_numeric($correct)) {
            $texte = $options[(int) $correct] ?? '';
            if ($texte !== '') {
                return $texte;
            }
        }

        return $correct;
    }

    /**
     * Whether the user's answer to a single quiz question is correct, resolving
     * the stored letter/index correct answer against the option texts first.
     */
    public static function isQuestionCorrect(array $question, $userAnswer): bool
    {
        if ($userAnswer === null) {
            return false;
        }

        if (in_array($question['type'] ?? '', ['recall', 'sentence-order'], true) || empty($question['options'])) {
            $normalize = fn ($value) => preg_replace('/\s+/u', ' ', mb_strtolower(trim(str_replace('’', "'", (string) $value), " \t\n\r\0\x0B.!?")));
            // Les reponses acceptees arrivent parfois en une seule chaine
            // (« a; an ») ou melangees a des objets : transtypees telles quelles,
            // elles levaient « Array to string conversion » et le quiz ne se
            // corrigeait plus. On les lit, on ecarte ce qui n'est pas du texte.
            $variantes = $question['accepted_answers'] ?? null;
            if (is_string($variantes)) {
                $variantes = preg_split('/\s*[;,]\s*/u', $variantes, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            }
            $variantes = array_filter(is_array($variantes) ? $variantes : [], 'is_scalar');
            $attendu = $question['correct_answer'] ?? '';
            $accepted = array_merge([is_scalar($attendu) ? $attendu : ''], $variantes);
            return $normalize($userAnswer) !== '' && collect($accepted)->contains(fn ($answer) => $normalize($answer) === $normalize($userAnswer));
        }

        $resolved = static::resolveCorrectAnswerText($question);
        // Compare complete choices, never merely their first letters.
        $given = static::resolveCorrectAnswerText(array_merge($question, ['correct_answer' => $userAnswer]));
        return mb_strtolower(trim($given)) === mb_strtolower(trim($resolved));
    }

    /**
     * Helper to verify if the user passed the comprehension quiz.
     */
    public function isComprehensionPassed(array $answers): bool
    {
        $quiz = $this->comprehension_quiz ?? [];
        if (empty($quiz)) {
            return true;
        }

        $correct = 0;
        foreach ($quiz as $index => $question) {
            $userAnswer = $answers[$index] ?? null;
            if (static::isQuestionCorrect($question, $userAnswer)) {
                $correct++;
            }
        }

        return $correct >= (count($quiz) * 0.66); // 2/3 threshold
    }
}
