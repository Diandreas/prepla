<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVocabulary extends Model
{
    protected $table = 'user_vocabulary';

    protected $fillable = [
        'user_id', 'word', 'language_slug', 'definition', 'ipa',
        'audio_url', 'examples', 'source', 'mastery_level',
        'ease_factor', 'interval_days', 'next_review_at',
    ];

    protected function casts(): array
    {
        return [
            'examples' => 'array',
            'ease_factor' => 'decimal:2',
            'next_review_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * SM-2 spaced repetition algorithm.
     * Quality: 0=complete blackout, 5=perfect response
     */
    public function updateSpacedRepetition(int $quality): void
    {
        if ($quality >= 3) {
            if ($this->mastery_level === 0) {
                $this->interval_days = 1;
            } elseif ($this->mastery_level === 1) {
                $this->interval_days = 6;
            } else {
                $this->interval_days = (int) round($this->interval_days * $this->ease_factor);
            }
            $this->mastery_level = min(5, $this->mastery_level + 1);
        } else {
            $this->mastery_level = 0;
            $this->interval_days = 1;
        }

        $this->ease_factor = max(1.3, $this->ease_factor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02)));
        $this->next_review_at = now()->addDays($this->interval_days);
        $this->save();
    }

    public function scopeDueForReview($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->where('next_review_at', '<=', now())
            ->orderBy('next_review_at');
    }

    public function scopeByLanguage($query, string $languageSlug)
    {
        return $query->where('language_slug', $languageSlug);
    }
}
