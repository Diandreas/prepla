<?php

namespace App\Services\Center;

use App\Models\LanguageCenter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

/**
 * Parrainage par un centre : le lien /r/{code} range le code, la creation du
 * compte le lit et rattache le compte au centre.
 *
 * Seuls les NOUVEAUX comptes sont rattaches : les controleurs d'inscription
 * (formulaire et Google) appellent rattacher() juste apres la creation, jamais
 * sur une connexion a un compte existant.
 */
class Parrainage
{
    /** Cle de session et nom du cookie qui portent le code. */
    public const CLE = 'prepla_parrainage';

    /** Duree de vie du cookie : 30 jours, en minutes. */
    public const DUREE_COOKIE = 60 * 24 * 30;

    /** Range le code (deja normalise) en session et dans un cookie de 30 jours. */
    public static function memoriser(Request $request, string $code): void
    {
        $request->session()->put(self::CLE, $code);
        Cookie::queue(self::CLE, $code, self::DUREE_COOKIE);
    }

    /**
     * Rattache un compte qu'on vient de creer au centre dont le code a ete
     * memorise, si ce code est valide, le centre actif, et le compte etranger
     * au personnel de ce centre. Le code est oublie dans tous les cas.
     */
    public static function rattacher(User $user, Request $request): ?LanguageCenter
    {
        $brut = $request->session()->pull(self::CLE) ?? $request->cookie(self::CLE);
        Cookie::queue(Cookie::forget(self::CLE));

        $code = LanguageCenter::normaliserCodeParrainage(is_string($brut) ? $brut : null);
        if (! $code || $user->referred_by_center_id) {
            return null;
        }

        $centre = LanguageCenter::where('referral_code', $code)->first();
        if (! $centre || ! $centre->is_active) {
            return null;
        }

        // Pas de parrainage de soi-meme : un membre du personnel (ou une adresse
        // invitee a l'etre) ne rapporte pas de commission a son propre centre.
        if ($centre->compteParmiSonPersonnel($user)) {
            Log::info('Parrainage refuse : compte du personnel du centre', ['center_id' => $centre->id, 'user_id' => $user->id]);

            return null;
        }

        $user->forceFill([
            'referred_by_center_id' => $centre->id,
            'referred_at' => now(),
        ])->save();

        return $centre;
    }
}
