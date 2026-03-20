<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'blueprint_id',
        'slug',
        'name',
        'skill_type',
        'time_limit',
        'sort_order',
        'question_count',
        'scoring_weight',
        'max_score',
        'parts_config',
        'rubric',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'parts_config' => 'array',
            'rubric' => 'array',
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

    public function exerciseTypes(): HasMany
    {
        return $this->hasMany(ExerciseType::class, 'section_id');
    }
}
