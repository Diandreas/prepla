<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'exercise_type_id',
        'exam_id',
        'mock_exam_id',
        'exam_section_id',
        'content',
        'questions',
        'difficulty',
        'xp_reward',
        'is_ai_generated',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'questions' => 'array',
            'is_ai_generated' => 'boolean',
        ];
    }

    public function exerciseType(): BelongsTo
    {
        return $this->belongsTo(ExerciseType::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function mockExam(): BelongsTo
    {
        return $this->belongsTo(MockExam::class);
    }

    public function examSection(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(UserExerciseAttempt::class);
    }
}
