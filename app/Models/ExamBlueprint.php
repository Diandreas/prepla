<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamBlueprint extends Model
{
    protected $fillable = [
        'exam_id',
        'level',
        'variant',
        'name',
        'total_duration_minutes',
        'scoring_config',
        'sections_config',
        'description',
    ];

    protected $casts = [
        'scoring_config' => 'array',
        'sections_config' => 'array',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ExamSection::class, 'blueprint_id');
    }
}
