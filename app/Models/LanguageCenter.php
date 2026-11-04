<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LanguageCenter extends Model
{
    use HasFactory;

    /**
     * Cle de settings posee sur un espace ouvert en libre-service via /enseigner.
     * Un centre cree par l'equipe (super administrateur) ne la porte pas.
     */
    public const LIBRE_SERVICE = 'libre_service';

    protected $fillable = [
        'name',
        'slug',
        'owner_email',
        'seats_limit',
        'default_exam_id',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'seats_limit' => 'integer',
        ];
    }

    public function members(): BelongsToMany
    {
        // Pivot uses center_id (not the inferred language_center_id).
        return $this->belongsToMany(User::class, 'center_user', 'center_id', 'user_id')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function staff(): BelongsToMany
    {
        return $this->members()->wherePivotIn('role', ['center_admin', 'teacher']);
    }

    public function students(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'student');
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'center_id');
    }

    public function defaultExam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'default_exam_id');
    }

    /** Seats currently used = number of student members. */
    public function seatsUsed(): int
    {
        return $this->members()->wherePivot('role', 'student')->count();
    }

    public function seatsAvailable(): int
    {
        return max(0, $this->seats_limit - $this->seatsUsed());
    }

    /** Espace ouvert par un enseignant lui-meme, sans passer par PrePla. */
    public function estLibreService(): bool
    {
        return (bool) ($this->settings[self::LIBRE_SERVICE] ?? false);
    }

    /**
     * Le personnel de ce centre s'entraine-t-il sans la limite du compte gratuit ?
     *
     * Oui pour un centre ouvert par PrePla (un client, ou la demonstration). Non pour
     * un espace ouvert en libre-service : n'importe quel compte en ouvre un en une
     * etape, et l'exemption aurait remplace l'abonnement en un clic. Ses outils
     * enseignant restent entiers ; seul son propre entrainement reste limite.
     */
    public function exempteSonPersonnelDuQuota(): bool
    {
        return ! $this->estLibreService();
    }
}
