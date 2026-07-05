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
        return static::where('email', mb_strtolower($email))->exists();
    }

    /** Record that this email has consumed its trial. Idempotent. */
    public static function recordFor(string $email): void
    {
        static::firstOrCreate(
            ['email' => mb_strtolower($email)],
            ['trial_granted_at' => now()],
        );
    }
}
