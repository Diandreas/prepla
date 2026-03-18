<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period_type',
        'period_key',
        'xp',
        'rank',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
