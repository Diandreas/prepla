<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Sondage ou quiz eclair lance pendant un live (correct_option renseigne = quiz). */
class LivePoll extends Model
{
    protected $fillable = ['live_session_id', 'question', 'options', 'correct_option', 'closed_at'];

    protected function casts(): array
    {
        return ['options' => 'array', 'correct_option' => 'integer', 'closed_at' => 'datetime'];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(LivePollAnswer::class);
    }
}
