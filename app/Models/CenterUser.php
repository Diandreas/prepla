<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Membership row linking a user to a single language center with a scoped role.
 * Modelled explicitly (not just a pivot) so it can be eager-loaded as
 * `user->centerMembership` to read the role + center without an extra query.
 */
class CenterUser extends Model
{
    protected $table = 'center_user';

    protected $fillable = [
        'center_id',
        'user_id',
        'role',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }
}
