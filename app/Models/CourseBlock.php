<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Un bloc d'une seance : son contenu depend du type (App\Services\Cours\BlocsDeCours). */
class CourseBlock extends Model
{
    protected $fillable = ['course_session_id', 'position', 'type', 'titre', 'contenu', 'obligatoire'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'contenu' => 'array', 'obligatoire' => 'boolean'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'course_session_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(CourseBlockProgress::class);
    }
}
