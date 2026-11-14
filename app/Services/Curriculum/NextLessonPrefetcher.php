<?php

namespace App\Services\Curriculum;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Prepare la lecon suivante en arriere-plan (seuil de Doherty).
 *
 * Pourquoi : generer une lecon par l'IA prend plusieurs secondes. Jusqu'ici, cette
 * attente tombait au moment ou l'apprenant cliquait sur « Lecon suivante ». En la
 * lancant des qu'il termine une lecon ou une pratique, la lecon est deja en base
 * quand il clique : NextLessonGenerator::generate() la retrouve et la sert aussitot.
 *
 * Pas de queue worker en production : le travail tourne apres l'envoi de la reponse
 * HTTP (app()->terminating), donc l'apprenant n'attend pas.
 *
 * Verrou : deux fins de lecon rapprochees (double clic, deux onglets) ne doivent pas
 * payer deux generations. Cache::add est atomique : seul le premier pose le verrou.
 */
class NextLessonPrefetcher
{
    /** Duree du verrou : couvre largement une generation, puis se libere seul. */
    public const LOCK_SECONDS = 300;

    /** Les demandes deja planifiees pendant cette requete (un seul terminating par apprenant). */
    protected array $planned = [];

    public function __construct(protected NextLessonGenerator $generator) {}

    public static function lockKey(int $userId): string
    {
        return "prefetch-lecon-suivante:{$userId}";
    }

    /**
     * Planifie la generation. Renvoie true si elle a ete planifiee, false si elle
     * etait deja en cours (verrou pris) ou si la fonction est coupee.
     */
    public function schedule(User $user): bool
    {
        if (! config('prepla.generation.prefetch_lecon_suivante', true)) {
            return false;
        }
        if (isset($this->planned[$user->id])) {
            return false;
        }
        if (! Cache::add(self::lockKey($user->id), now()->timestamp, self::LOCK_SECONDS)) {
            return false;
        }

        $this->planned[$user->id] = true;
        $userId = $user->id;

        app()->terminating(function () use ($userId) {
            $this->run($userId);
        });

        return true;
    }

    /**
     * Genere vraiment. Une panne ne doit jamais remonter a l'apprenant : il aura
     * simplement l'ancienne attente au clic, comme avant.
     */
    public function run(int $userId): void
    {
        try {
            $user = User::find($userId);
            if ($user) {
                $this->generator->generate($user);
            }
        } catch (Throwable $e) {
            Log::warning('Pre-generation de la lecon suivante echouee', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            Cache::forget(self::lockKey($userId));
            unset($this->planned[$userId]);
        }
    }
}
