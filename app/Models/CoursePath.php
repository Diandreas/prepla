<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Un parcours construit par un centre (App\Services\Cours\CreateurDeParcours).
 * classroom_id nul : modele reutilisable, jamais vu par un eleve.
 */
class CoursePath extends Model
{
    public const BROUILLON = 'brouillon';

    public const PUBLIE = 'publie';

    protected $fillable = ['center_id', 'classroom_id', 'titre', 'description', 'statut', 'source_path_id', 'created_by'];

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('position')->orderBy('id');
    }

    public function sessions(): HasManyThrough
    {
        return $this->hasManyThrough(CourseSession::class, CourseModule::class);
    }

    public function estPublie(): bool
    {
        return $this->statut === self::PUBLIE;
    }
}
