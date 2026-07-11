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
        'center_id',
        'creator_id',
        'lesson_id',
        'mock_exam_id',
        'exam_section_id',
        // node_id/order_in_node absents d'ici = update() silencieusement ignoré →
        // les exercices générés n'étaient jamais rattachés à leur nœud, et chaque
        // ouverture de session relançait la génération IA complète.
        'node_id',
        'order_in_node',
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

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Lesson::class);
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

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
