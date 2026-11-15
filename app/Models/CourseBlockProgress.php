<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseBlockProgress extends Model
{
    protected $table = 'course_block_progress';

    protected $fillable = [
        'course_block_id', 'user_id', 'termine_at', 'score', 'pourcentage_vu', 'ecoutes', 'reponse',
        'correction_statut', 'correction_ia', 'commentaire_enseignant', 'note_enseignant', 'corrige_par',
    ];

    protected function casts(): array
    {
        return [
            'termine_at' => 'datetime',
            'score' => 'integer',
            'pourcentage_vu' => 'integer',
            'ecoutes' => 'integer',
            'reponse' => 'array',
            'correction_ia' => 'array',
            'note_enseignant' => 'integer',
        ];
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(CourseBlock::class, 'course_block_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
