<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    /**
     * La série effective accompagne toujours le profil envoyé à l'interface :
     * sans elle, le bandeau lirait `streak_current` et afficherait une série
     * morte depuis des semaines.
     */
    protected $appends = ['serie_effective'];

    protected $fillable = [
        'user_id',
        'target_exam_id',
        'target_score',
        'exam_date',
        'current_level',
        'level_source',
        'plan',
        'interface_language',
        'native_language',
        'xp_total',
        'streak_current',
        'streak_last_date',
        'onboarding_completed_at',
        'trial_ends_at',
        'learning_preferences',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'streak_last_date' => 'date',
            'onboarding_completed_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'learning_preferences' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetExam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'target_exam_id');
    }

    /**
     * La série RÉELLEMENT en cours, pas la dernière atteinte.
     *
     * `streak_current` garde sa valeur tant que l'apprenant ne revient pas :
     * il n'est remis à 1 qu'au prochain jour d'activité, et aucune tâche
     * planifiée ne le fait retomber. Quelqu'un parti depuis trois semaines
     * lisait encore « 7 jours d'affilée ».
     *
     * Une série est vivante si le dernier jour travaillé est aujourd'hui ou
     * hier. Au-delà, elle vaut zéro — et on le dit.
     */
    /** La série effective, telle qu'envoyée à l'interface. */
    public function getSerieEffectiveAttribute(): int
    {
        return $this->serieEffective();
    }

    public function serieEffective(): int
    {
        if (! $this->streak_last_date || ! $this->streak_current) {
            return 0;
        }

        $dernier = \Illuminate\Support\Carbon::parse($this->streak_last_date)->startOfDay();

        return $dernier->greaterThanOrEqualTo(now()->startOfDay()->subDay())
            ? (int) $this->streak_current
            : 0;
    }
}
