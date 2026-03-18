<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserExerciseAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exercise_id',
        'answers',
        'score',
        'accuracy_percent',
        'time_spent',
        'xp_earned',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'feedback' => 'array',
            'score' => 'decimal:2',
            'accuracy_percent' => 'decimal:2',
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
}
