<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserError extends Model
{
    protected $fillable = [
        'user_id', 'exercise_id', 'question_id', 'question_text',
        'user_answer', 'correct_answer', 'skill_type', 'exercise_type_slug',
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
