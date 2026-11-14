<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un exercice place par un centre dans une partie de son examen blanc. */
class MockExamItem extends Model
{
    protected $fillable = ['mock_exam_id', 'exercise_id', 'section_slug', 'partie', 'sort_order'];

    public function mockExam(): BelongsTo
    {
        return $this->belongsTo(MockExam::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
