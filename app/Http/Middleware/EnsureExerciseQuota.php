<?php

namespace App\Http\Middleware;

use App\Models\CenterUser;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the "3 free exercises per day" limit advertised on the landing page
 * for non-premium users. Previously this limit existed only in marketing copy
 * — no controller checked subscribed()/hasPremiumAccess() before serving an
 * exercise, so a free user had technically unlimited access.
 *
 * Monté sur les routes d'ENTRÉE (démarrage d'un nœud, ouverture d'un exercice).
 * Il gardait autrefois les routes de soumission : l'apprenant faisait sa séance
 * entière, puis se voyait rediriger vers l'abonnement au moment de l'envoyer —
 * sans correction, sans XP et sans progression. On refuse d'ouvrir plutôt que de
 * jeter un travail déjà fait.
 */
class EnsureExerciseQuota
{
    public const DAILY_FREE_LIMIT = 3;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->hasPremiumAccess() || self::exempte($user)) {
            return $next($request);
        }

        // The count-then-act check below has a TOCTOU race: the controller's
        // AI scoring call happens between this count and the attempt insert,
        // so several concurrent submissions can all pass the check before
        // any row exists. A per-user lock serializes submissions for the
        // duration of the request, closing that window.
        $lock = Cache::lock("exercise-quota:{$user->id}", 30);

        if (!$lock->block(5)) {
            return redirect()
                ->route('subscription.index')
                ->with('error', 'Une autre soumission est en cours, réessaie dans quelques secondes.');
        }

        try {
            $completedToday = UserExerciseAttempt::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->count();

            if ($completedToday >= self::DAILY_FREE_LIMIT) {
                return redirect()
                    ->route('subscription.index')
                    ->with('error', "Tu as atteint ta limite de " . self::DAILY_FREE_LIMIT . " exercices gratuits aujourd'hui. Reviens demain, ou passe au Premium pour continuer sans limite.");
            }

            return $next($request);
        } finally {
            $lock->release();
        }
    }

    /**
     * Hors quota gratuit, quel que soit l'abonnement. Partage avec l'interface :
     * le compteur « x/3 » ne s'affiche qu'a qui le middleware bloquera vraiment.
     */
    public static function exempte(User $user): bool
    {
        // Un eleve inscrit dans la classe d'un enseignant n'est pas un apprenant
        // libre : son travail lui est donne par son professeur. Le quota gratuit le
        // renvoyait vers l'abonnement au quatrieme exercice de son devoir, alors que
        // ce devoir vient de son etablissement.
        if ($user->isSuperAdmin() || $user->isCenterStudent()) {
            return true;
        }

        // Le personnel est dans le meme cas : un enseignant qui prepare ses exercices
        // ou verifie un devoir n'est pas un apprenant libre. Mais seulement dans un
        // centre ouvert par PrePla : un espace libre-service s'ouvre en un clic sur
        // /enseigner, et l'exemption y remplacait l'abonnement. Son personnel garde
        // la limite gratuite pour son propre entrainement.
        return self::encadreUnCentreExempte($user);
    }

    /**
     * La personne fait-elle partie du personnel d'un centre ouvert par PrePla ?
     *
     * On regarde TOUTES ses appartenances, pas le seul centre courant : un
     * enseignant d'un centre client qui ouvre aussi son espace sur /enseigner y
     * devient responsable, et ce role passe avant le sien dans le centre client
     * quand rien n'est choisi en session. Il perdait l'exemption des qu'il agissait
     * dans son espace, alors qu'il reste membre du personnel du centre client.
     */
    private static function encadreUnCentreExempte(User $user): bool
    {
        return CenterUser::with('center')
            ->where('user_id', $user->id)
            ->whereIn('role', ['center_admin', 'teacher'])
            ->get()
            ->contains(fn (CenterUser $a) => (bool) $a->center?->exempteSonPersonnelDuQuota());
    }
}
