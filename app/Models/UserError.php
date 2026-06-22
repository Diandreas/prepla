<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserError extends Model
{
    protected $fillable = [
        'user_id', 'exercise_id', 'question_id', 'question_text',
        'user_answer', 'correct_answer', 'explanation', 'skill_type', 'exercise_type_slug',
        'error_category', 'subcategory', 'reviewed_count', 'last_reviewed_at', 'mastered',
        'ease_factor', 'interval_days', 'next_review_at',
    ];

    protected function casts(): array
    {
        return [
            'mastered' => 'boolean',
            'last_reviewed_at' => 'datetime',
            'next_review_at' => 'datetime',
            'ease_factor' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function markReviewed(bool $correct): void
    {
        $this->reviewed_count++;
        $this->last_reviewed_at = now();
        if ($correct && $this->reviewed_count >= 3) {
            $this->mastered = true;
        }
        $this->save();
    }

    public function scopeUnmastered($query, $userId)
    {
        return $query->where('user_id', $userId)->where('mastered', false)->orderByDesc('created_at');
    }

    /**
     * Exercise types whose mistakes are CONCEPTUAL — they can be usefully re-tested
     * with a freshly generated question on the same concept (grammar, vocabulary,
     * word formation, writing, etc.). Comprehension mistakes (reading/listening on
     * a one-off passage) are NOT here: re-showing them only makes the learner
     * memorise that passage, which doesn't build the underlying skill.
     */
    public const CONCEPT_SLUGS = [
        'gap-fill', 'sentence-completion', 'word-formation', 'open-cloze',
        'mcq-cloze', 'key-word-transformation', 'grammar-mcq',
        'vocabulary-card', 'essay', 'essay-editor', 'short-writing',
        'letter-writing', 'letter-email-writing',
    ];

    /**
     * Decide the pedagogical family of an error from its exercise type / skill.
     * Returns 'concept' (re-practisable) or 'comprehension' (diagnostic only).
     */
    public static function classifyFamily(?string $exerciseTypeSlug, ?string $skillType): string
    {
        if ($exerciseTypeSlug && in_array($exerciseTypeSlug, self::CONCEPT_SLUGS, true)) {
            return 'concept';
        }
        if (in_array($skillType, ['grammar', 'vocabulary', 'use-of-english', 'writing'], true)) {
            return 'concept';
        }
        // reading / listening comprehension, speaking, etc.
        return 'comprehension';
    }

    public function isConcept(): bool
    {
        return self::classifyFamily($this->exercise_type_slug, $this->skill_type) === 'concept';
    }

    public function scopeConcept($query, $userId)
    {
        return $query->where('user_id', $userId)->where('mastered', false)
            ->where(function ($q) {
                $q->whereIn('exercise_type_slug', self::CONCEPT_SLUGS)
                  ->orWhereIn('skill_type', ['grammar', 'vocabulary', 'use-of-english', 'writing']);
            });
    }

    /**
     * Scope: errors due for SM-2 review.
     */
    public function scopeDueForReview($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->where('mastered', false)
            ->whereNotNull('next_review_at')
            ->where('next_review_at', '<=', now())
            ->orderBy('next_review_at');
    }

    /**
     * Scope: errors by category for diagnostics.
     */
    public function scopeByCategory($query, $userId, ?string $category = null)
    {
        $query = $query->where('user_id', $userId)->where('mastered', false);
        if ($category) {
            $query->where('error_category', $category);
        }
        return $query;
    }

    /**
     * Get error category stats for a user (for diagnosis dashboard).
     */
    public static function categoryStats(int $userId): array
    {
        return static::where('user_id', $userId)
            ->where('mastered', false)
            ->whereNotNull('error_category')
            ->where('error_category', '!=', 'session_mistake')
            ->selectRaw('error_category, subcategory, count(*) as count')
            ->groupBy('error_category', 'subcategory')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }
}
