<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserError extends Model
{
    protected $fillable = [
        'user_id', 'exercise_id', 'question_id', 'question_text',
        'user_answer', 'correct_answer', 'skill_type', 'exercise_type_slug',
        'error_category', 'reviewed_count', 'last_reviewed_at', 'mastered',
    ];

    protected function casts(): array
    {
        return [
            'mastered' => 'boolean',
            'last_reviewed_at' => 'datetime',
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
}
