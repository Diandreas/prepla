<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveParticipant extends Model
{
    protected $fillable = ['live_session_id', 'user_id', 'joined_at', 'last_seen_at', 'hand_raised_at', 'notes'];

    protected function casts(): array
    {
        return ['joined_at' => 'datetime', 'last_seen_at' => 'datetime', 'hand_raised_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
