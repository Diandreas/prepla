<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningPathNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'sort_order',
        'title',
        'description',
        'icon',
        'skill_type',
        'level',
        'xp_reward',
        'node_type',
        'exercise_ids',
    ];

    protected function casts(): array
    {
        return [
            'exercise_ids' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
