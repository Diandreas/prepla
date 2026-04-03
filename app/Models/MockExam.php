<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MockExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'blueprint_id',
        'title',
        'description',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(ExamBlueprint::class, 'blueprint_id');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }
}
