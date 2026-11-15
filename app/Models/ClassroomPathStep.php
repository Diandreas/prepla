<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une etape du parcours d'une classe (App\Services\Center\ParcoursDeClasse).
 *
 * Une etape porte soit un exercice (exercise_id), soit le replay d'un live de
 * la classe (live_session_id).
 *
 * seuil_reussite : en %, facultatif. Sans seuil, une tentative suffit.
 */
class ClassroomPathStep extends Model
{
    protected $fillable = [
        'classroom_id',
        'position',
        'exercise_id',
        'live_session_id',
        'titre',
        'consigne',
        'seuil_reussite',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'seuil_reussite' => 'integer',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }

    public function estUnReplay(): bool
    {
        return $this->live_session_id !== null;
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
