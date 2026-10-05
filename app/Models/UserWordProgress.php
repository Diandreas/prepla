<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWordProgress extends Model
{
    protected $fillable = [
        'user_id',
        'dictionary_word_id',
        'status',
        'last_reviewed_at',
        'next_review_at', 'recognition_count', 'recall_count',
    ];

    protected function casts(): array
    {
        return ['last_reviewed_at' => 'datetime', 'next_review_at' => 'datetime',
            'recognition_count' => 'integer', 'recall_count' => 'integer'];
    }

    public function scopeDueForReview($query, int $userId)
    {
        return $query->where('user_id', $userId)->where(function ($q) {
            $q->where('next_review_at', '<=', now())
                ->orWhere(fn ($legacy) => $legacy->whereNull('next_review_at')->where('status', '!=', 'mastered'));
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dictionaryWord()
    {
        return $this->belongsTo(DictionaryWord::class, 'dictionary_word_id');
    }
}
