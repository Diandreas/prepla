<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExerciseType extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'slug',
        'name',
        'skill_type',
        'component_key',
        'description',
        'scoring_rubric',
        'generation_config',
    ];

    protected function casts(): array
    {
        return [
            'scoring_rubric' => 'array',
            'generation_config' => 'array',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class, 'section_id');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }
}
