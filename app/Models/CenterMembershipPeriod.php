<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une periode pendant laquelle un utilisateur a appartenu a un centre avec un
 * role donne. ended_at nul : il en est toujours membre. Ecrite par
 * CenterUserObserver ; sert au decompte des eleves actifs (ElevesActifs).
 */
class CenterMembershipPeriod extends Model
{
    protected $fillable = [
        'center_id',
        'user_id',
        'role',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
