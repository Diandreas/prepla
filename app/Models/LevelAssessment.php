<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LevelAssessment extends Model
{
    protected $fillable = [
        'user_id', 'exam_id', 'assessed_level', 'previous_level',
        'assessment_type', 'score_details', 'assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'score_details' => 'array',
            'assessed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
