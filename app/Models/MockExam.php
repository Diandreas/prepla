<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MockExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'blueprint_id',
        'center_id',
        'creator_id',
        'level',
        'title',
        'description',
        'durees_sections',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'durees_sections' => 'array',
        ];
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(ExamBlueprint::class, 'blueprint_id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    /** Les exercices choisis par un centre (le catalogue, lui, reste a PrePla). */
    public function items(): HasMany
    {
        return $this->hasMany(MockExamItem::class)->orderBy('sort_order');
    }

    public function passages(): HasMany
    {
        return $this->hasMany(MockExamPassage::class);
    }

    /** Les epreuves PrePla : celles d'un centre restent a ses eleves. */
    public function scopeCatalogue(Builder $query): Builder
    {
        return $query->whereNull('mock_exams.center_id');
    }

    /** Duree totale : la somme du minutage par section. */
    public function dureeTotale(): int
    {
        return (int) array_sum(array_map('intval', $this->durees_sections ?? []));
    }
}
