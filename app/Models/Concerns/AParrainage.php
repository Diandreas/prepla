<?php

namespace App\Models\Concerns;

use App\Models\CenterCommission;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Parrainage cote centre : code aleatoire, comptes parraines, commissions.
 * Cote LanguageCenter. Reserve au lot D ; le pendant cote compte est
 * App\Models\Concerns\Utilisateur\AParrainage.
 */
trait AParrainage
{
    // Sans I, L, O, 0 ni 1 : un code recopie a la main ne se trompe pas de lettre.
    public const ALPHABET_PARRAINAGE = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * Tout centre recoit son code a sa creation, quel que soit le chemin
     * (super administrateur, /enseigner, invitation) : aucun ne l'oublie.
     */
    public static function bootAParrainage(): void
    {
        static::creating(function ($centre) {
            if (blank($centre->referral_code)) {
                $centre->referral_code = static::nouveauCodeParrainage();
            }
        });
    }

    public static function nouveauCodeParrainage(): string
    {
        $alphabet = self::ALPHABET_PARRAINAGE;
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    /** Le code au format attendu, ou null : la casse et les espaces sont pardonnes. */
    public static function normaliserCodeParrainage(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        return preg_match('/^['.self::ALPHABET_PARRAINAGE.']{8}$/', $code) ? $code : null;
    }

    /** Le code de ce centre ; pose a la volee si un centre n'en avait pas encore. */
    public function codeParrainage(): string
    {
        if (blank($this->referral_code)) {
            $this->forceFill(['referral_code' => static::nouveauCodeParrainage()])->save();
        }

        return $this->referral_code;
    }

    public function comptesParraines(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by_center_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(CenterCommission::class, 'center_id');
    }

    /** Le compte (ou l'adresse) fait-il partie du personnel de ce centre, present ou invite ? */
    public function compteParmiSonPersonnel(User $user): bool
    {
        if ($this->staff()->whereKey($user->id)->exists()) {
            return true;
        }

        $email = strtolower((string) $user->email);
        if ($email !== '' && strtolower((string) $this->owner_email) === $email) {
            return true;
        }

        return \App\Models\TeacherInvitation::where('center_id', $this->id)
            ->whereRaw('lower(email) = ?', [$email])
            ->exists();
    }
}
