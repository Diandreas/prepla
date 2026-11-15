<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Un element du fil d'une seance : chat, question, reaction ou ressource partagee. */
class LiveMessage extends Model
{
    public const REACTIONS = ['compris', 'trop_vite', 'repete'];

    protected $fillable = ['live_session_id', 'user_id', 'kind', 'body', 'payload', 'answered_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'answered_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(LiveQuestionVote::class);
    }
}
