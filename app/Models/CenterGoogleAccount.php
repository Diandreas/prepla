<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Le compte Google d'un membre de l'equipe d'un centre (jetons chiffres en base). */
class CenterGoogleAccount extends Model
{
    protected $fillable = ['center_id', 'user_id', 'google_email', 'access_token', 'refresh_token', 'expires_at', 'scopes'];

    protected $hidden = ['access_token', 'refresh_token'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
        ];
    }

    public function aLeScope(string $scope): bool
    {
        return in_array($scope, preg_split('/\s+/', (string) $this->scopes) ?: [], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
