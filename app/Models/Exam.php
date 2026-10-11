<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'language_id',
        'slug',
        'name',
        'max_score',
        'scoring_type',
        'levels',
    ];

    protected function casts(): array
    {
        return [
            'levels' => 'array',
        ];
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * Les examens qu'on propose vraiment.
     *
     * La base en porte dix-huit, mais sept langues ne sont pas travaillees :
     * leurs examens n'ont qu'un squelette generique. L'interrupteur existait
     * deja sur la langue — il n'etait respecte qu'a deux endroits sur neuf, si
     * bien qu'un examen « masque » restait proposé dans les reglages du profil,
     * dans les outils IA et dans tous les ecrans enseignant.
     *
     * Les ecrans de super-administration, eux, ne filtrent pas : il faut pouvoir
     * voir ce qu'on a masque.
     */
    public function scopeProposables($query)
    {
        return $query->whereHas('language', fn ($q) => $q->where('is_active', true));
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ExamSection::class);
    }

    public function blueprints(): HasMany
    {
        return $this->hasMany(ExamBlueprint::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function learningPathNodes(): HasMany
    {
        return $this->hasMany(LearningPathNode::class);
    }
}
