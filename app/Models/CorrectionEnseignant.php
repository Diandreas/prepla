<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** La note et le commentaire d'un enseignant du centre sur une production. */
class CorrectionEnseignant extends Model
{
    protected $table = 'corrections_enseignant';

    protected $fillable = ['attempt_id', 'center_id', 'teacher_id', 'sous_notes', 'note', 'grille', 'commentaire'];

    protected function casts(): array
    {
        return ['sous_notes' => 'array', 'grille' => 'array', 'note' => 'float'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(UserExerciseAttempt::class, 'attempt_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** Ce que l'eleve lit sur sa page de resultat. */
    public function pourEleve(): array
    {
        return [
            'note' => $this->note,
            'grille' => $this->grille,
            'commentaire' => $this->commentaire,
            'enseignant' => $this->teacher?->name,
            'date' => $this->updated_at?->toIso8601String(),
        ];
    }
}
