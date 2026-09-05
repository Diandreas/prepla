<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumedTrial extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email',
        'trial_granted_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_granted_at' => 'datetime',
        ];
    }

    /** Whether this email has ever been granted a trial before (case-insensitive). */
    public static function alreadyUsedBy(string $email): bool
    {
        return static::where('email', static::fingerprint($email))->exists();
    }

    /** Record that this email has consumed its trial. Idempotent. */
    public static function recordFor(string $email): void
    {
        static::firstOrCreate(
            ['email' => static::fingerprint($email)],
            ['trial_granted_at' => now()],
        );
    }

    /** Keep only a one-way anti-abuse fingerprint, never the address itself. */
    private static function fingerprint(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }
}
