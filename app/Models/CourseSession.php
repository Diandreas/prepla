<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une seance d'un parcours. Regles de deroulement :
 * - deblocage : libre | completion | quiz (seuil_quiz) | date (deblocage_at) ;
 * - debut_at : pas avant ; limite_at : date limite affichee (en retard ensuite) ;
 * - obligatoire, duree_minutes, blocs_ordre_impose ;
 * - statut brouillon : jamais visible d'un eleve.
 */
class CourseSession extends Model
{
    public const DEBLOCAGES = ['libre', 'completion', 'quiz', 'date'];

    protected $fillable = [
        'course_module_id', 'position', 'titre', 'description', 'statut', 'obligatoire', 'blocs_ordre_impose',
        'duree_minutes', 'deblocage', 'seuil_quiz', 'deblocage_at', 'debut_at', 'limite_at',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'obligatoire' => 'boolean',
            'blocs_ordre_impose' => 'boolean',
            'duree_minutes' => 'integer',
            'seuil_quiz' => 'integer',
            'deblocage_at' => 'datetime',
            'debut_at' => 'datetime',
            'limite_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(CourseBlock::class)->orderBy('position')->orderBy('id');
    }

    public function estPubliee(): bool
    {
        return $this->statut === CoursePath::PUBLIE;
    }
}
