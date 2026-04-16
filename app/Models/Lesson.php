<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'user_id',
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
    public function isComprehensionPassed(array $answers): bool
    {
        $quiz = $this->comprehension_quiz ?? [];
        if (empty($quiz)) return true;

        $correct = 0;
        foreach ($quiz as $index => $question) {
            $userAnswer = $answers[$index] ?? null;
            $correctAnswer = $question['correct_answer'] ?? null;
            if ($userAnswer !== null && strtolower(trim((string)$userAnswer)) === strtolower(trim((string)$correctAnswer))) {
                $correct++;
            }
        }

        return $correct >= (count($quiz) * 0.66); // 2/3 threshold
    }
}
