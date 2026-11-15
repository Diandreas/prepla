<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserExerciseAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exercise_id',
        'answers',
        'score',
        'accuracy_percent',
        'time_spent',
        'xp_earned',
        'feedback',
        // question_id => chemin de l'audio conserve (prises de parole), pour
        // que l'enseignant du centre puisse l'ecouter.
        'audios',
    ];

    /** Jamais envoye tel quel au navigateur : ce sont des chemins de stockage. */
    protected $hidden = ['audios'];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'feedback' => 'array',
            'audios' => 'array',
            'score' => 'decimal:2',
            'accuracy_percent' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /** La note de l'enseignant du centre, quand il a relu cette production. */
    public function correctionEnseignant(): HasOne
    {
        return $this->hasOne(CorrectionEnseignant::class, 'attempt_id');
    }
}
