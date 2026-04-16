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
        'chapter_name',
        'chapter_order',
        'sort_order',
        'title',
        'description',
        'icon',
        'skill_type',
        'level',
        'xp_reward',
        'node_type',
        'exercise_ids',
        'blueprint_id',
        'prerequisites',
        'mastery_score',
        'exercises_count',
    ];

    protected function casts(): array
    {
        return [
            'exercise_ids' => 'array',
            'prerequisites' => 'array',
        ];
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(ExamBlueprint::class, 'blueprint_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function lessons(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lesson::class, 'node_id');
    }
}
