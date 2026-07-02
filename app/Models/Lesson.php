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
    ];

    protected function casts(): array
    {
        return [
            'key_takeaways' => 'array',
            'common_mistakes' => 'array',
            'comprehension_quiz' => 'array',
            'based_on_errors' => 'array',
            'generated_at' => 'datetime',
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
        $userClean = strtolower(trim((string)$userAnswer));
        $correctClean = strtolower(trim((string)$correctAnswer));
        
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
        
        if (!empty($userText) && !empty($correctText) && $userText === $correctText) {
            return true;
        }
        
        return false;
    }

    /**
     * Resolve a quiz question's stored correct answer to the full option text.
     *
     * The AI generator stores `correct_answer` as a letter ("A".."D") or an
     * index, while `options` holds the actual answer texts and the frontend
     * submits the chosen option's full text. This maps the letter/index back to
     * its option text so grading and display compare like-for-like. Falls back
     * to the raw value when it can't be resolved (already text, no options…).
     */
    public static function resolveCorrectAnswerText(array $question): string
    {
        $correct = trim((string)($question['correct_answer'] ?? ''));
        $options = $question['options'] ?? [];

        if (!is_array($options) || empty($options)) {
            return $correct;
        }

        // Single letter → index (A=0, B=1, …), case-insensitive.
        if (preg_match('/^[a-zA-Z]$/', $correct)) {
            $idx = ord(strtoupper($correct)) - ord('A');
            if (isset($options[$idx])) {
                return (string)$options[$idx];
            }
        }

        // Numeric index.
        if (is_numeric($correct) && isset($options[(int)$correct])) {
            return (string)$options[(int)$correct];
        }

        // Already the full text of one of the options.
        foreach ($options as $opt) {
            if (static::checkAnswerMatch($correct, (string)$opt)) {
                return (string)$opt;
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
        if ($userAnswer === null) return false;
        return static::checkAnswerMatch($userAnswer, static::resolveCorrectAnswerText($question));
    }

    /**
     * Helper to verify if the user passed the comprehension quiz.
     */
    public function isComprehensionPassed(array $answers): bool
    {
        $quiz = $this->comprehension_quiz ?? [];
        if (empty($quiz)) return true;

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
