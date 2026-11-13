<?php

namespace App\Services\Billing;

use App\Models\ActivationManuelle;
use App\Models\CommissionDelegue;
use App\Models\Delegue;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Les delegues : qui a amene quel etudiant, a quel prix il paie, et ce que
 * chaque paiement rapporte a son delegue.
 *
 * Un etudiant n'a qu'un delegue, pose une seule fois (lien /d/{code} a
 * l'inscription, code saisi sur la page d'abonnement, ou par l'equipe a
 * l'activation). Le changer ensuite detournerait les commissions d'un autre.
 */
class Delegues
{
    public const CLE = 'prepla_delegue';

    public const DUREE_COOKIE = 60 * 24 * 30;

    public static function memoriser(Request $request, string $code): void
    {
        $request->session()->put(self::CLE, $code);
        Cookie::queue(self::CLE, $code, self::DUREE_COOKIE);
    }

    /** A la creation d'un compte : le code range par le lien /d/{code}, s'il y en a un. */
    public static function rattacherALInscription(User $user, Request $request): ?Delegue
    {
        $brut = $request->session()->pull(self::CLE) ?? $request->cookie(self::CLE);
        Cookie::queue(Cookie::forget(self::CLE));
        $delegue = Delegue::parCode(is_string($brut) ? $brut : null);

        return $delegue && self::rattacher($user, $delegue) ? $delegue : null;
    }

    /**
     * Rattache un compte a un delegue s'il n'en a pas encore. Un delegue ne se
     * rattache pas a lui-meme : il toucherait une commission sur ses propres
     * paiements.
     */
    public static function rattacher(User $user, Delegue $delegue): bool
    {
        if ($user->delegue_id || ! $delegue->actif || $delegue->user_id === $user->id) {
            return false;
        }

        $user->forceFill(['delegue_id' => $delegue->id, 'delegue_at' => now()])->save();

        return true;
    }

    /**
     * Les prix Mobile Money de ce compte : reduits si son code en donne une. Un
     * code d'offre peut aussi imposer le prix du mois (QR code a 5 000 FCFA).
     */
    public static function prix(User $user): array
    {
        $prix = config('prepla.mobile_money.prix');
        $delegue = self::delegueActif($user);
        if (! $delegue) {
            return $prix;
        }
        $reduits = array_map(fn ($p) => $delegue->prixReduit((int) $p), $prix);
        if ($delegue->prix_mensuel) {
            $reduits['mensuel'] = $delegue->prix_mensuel;
        }

        return $reduits;
    }

    /** Le prix affiche barre a cote : le prix normal, ou celui que l'offre annonce. */
    public static function prixBarres(User $user): array
    {
        $prix = config('prepla.mobile_money.prix');
        $delegue = self::delegueActif($user);
        if ($delegue?->prix_barre) {
            $prix['mensuel'] = $delegue->prix_barre;
        }

        return $prix;
    }

    public static function delegueActif(User $user): ?Delegue
    {
        $delegue = $user->delegue_id ? Delegue::find($user->delegue_id) : null;

        return $delegue?->actif ? $delegue : null;
    }

    /**
     * La commission d'une activation payee. Chaque renouvellement en ecrit une.
     * Sans montant saisi, rien n'est ecrit : on ne paie pas un pourcentage de
     * zero, et l'equipe voit la ligne manquante sur la page des delegues.
     */
    public static function enregistrerCommission(ActivationManuelle $activation): ?CommissionDelegue
    {
        $user = $activation->user;
        $delegue = $user?->delegue_id ? Delegue::find($user->delegue_id) : null;
        if (! $delegue || ! $activation->montant) {
            return null;
        }

        return CommissionDelegue::firstOrCreate(['activation_manuelle_id' => $activation->id], [
            'delegue_id' => $delegue->id,
            'user_id' => $user->id,
            'montant' => $activation->montant,
            'taux' => $delegue->taux,
            'commission' => (int) round($activation->montant * $delegue->taux / 100),
            'periode' => now('Africa/Douala')->format('Y-m'),
        ]);
    }

    /** Une activation annulee n'a rien rapporte. */
    public static function annulerCommission(ActivationManuelle $activation): void
    {
        CommissionDelegue::where('activation_manuelle_id', $activation->id)->whereNull('annulee_le')->update(['annulee_le' => now()]);
    }

    /** Ce que le delegue a amene et gagne. */
    public static function bilan(Delegue $delegue): array
    {
        $commissions = CommissionDelegue::where('delegue_id', $delegue->id);
        $comptees = (clone $commissions)->whereNull('annulee_le');

        return [
            'inscrits' => User::where('delegue_id', $delegue->id)->count(),
            'abonnes' => (clone $comptees)->distinct('user_id')->count('user_id'),
            'encaisse' => (int) (clone $comptees)->sum('montant'),
            'gagne' => (int) (clone $comptees)->sum('commission'),
            'du' => (int) (clone $commissions)->due()->sum('commission'),
            'verse' => (int) (clone $comptees)->whereNotNull('versee_le')->sum('commission'),
            'a_reprendre' => (int) (clone $commissions)->whereNotNull('annulee_le')->whereNotNull('versee_le')->sum('commission'),
        ];
    }
}
