<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un cours en direct d'une classe (module « Live class »).
 *
 * La video passe par Google Meet (meeting_uri) ; PrePla ne garde que le
 * planning et l'interactivite autour du cours.
 */
class LiveSession extends Model
{
    protected $fillable = [
        'center_id', 'classroom_id', 'created_by', 'series_uuid', 'title', 'agenda',
        'starts_at', 'duration_minutes', 'access_type', 'meet_space_name', 'meeting_uri',
        'meeting_code', 'started_at', 'ended_at', 'cancelled_at', 'recording_url', 'summary',
        'assignment_id', 'replay_published', 'google_attendance',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'replay_published' => 'boolean',
            'google_attendance' => 'array',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(LiveParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LiveMessage::class);
    }

    public function polls(): HasMany
    {
        return $this->hasMany(LivePoll::class);
    }

    public function finPrevue(): CarbonInterface
    {
        return $this->starts_at->copy()->addMinutes($this->duration_minutes);
    }

    /**
     * a_venir | en_cours | termine | annule.
     * En cours : lance par l'enseignant, ou dans son creneau prevu, et pas termine.
     */
    public function statut(?CarbonInterface $maintenant = null): string
    {
        $maintenant ??= now();
        if ($this->cancelled_at) {
            return 'annule';
        }
        if ($this->ended_at) {
            return 'termine';
        }
        if ($this->started_at) {
            return 'en_cours';
        }
        if ($maintenant->gte($this->starts_at) && $maintenant->lt($this->finPrevue())) {
            return 'en_cours';
        }

        return $maintenant->lt($this->starts_at) ? 'a_venir' : 'termine';
    }

    /** Pas annule, pas termine. */
    public function scopeOuverts(Builder $q): Builder
    {
        return $q->whereNull('cancelled_at')->whereNull('ended_at');
    }
}
