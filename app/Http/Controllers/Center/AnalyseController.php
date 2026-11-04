<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Services\Center\AnalyseIa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * L'analyse IA d'un eleve, d'une classe ou du centre, en JSON : la carte
 * « Analyse IA » la charge apres l'affichage de la page, pour que l'attente de
 * l'IA ne retarde jamais la fiche.
 *
 * GET : lecture seule. Jamais d'appel a l'IA ni d'ecriture (un GET n'est pas
 *       protege par CSRF : une balise image sur un autre site ne doit rien coûter).
 * POST (protege par CSRF) : genere l'analyse si elle n'est plus a jour (mode
 *       « auto », borne par AnalyseIa : relance par sujet, plafond par centre) ;
 *       avec « actualiser », la refait meme a jour, une fois par heure et par sujet.
 *
 * Jamais d'erreur 500 a l'ecran : si quelque chose casse, on repond un etat
 * « indisponible » que la carte affiche calmement.
 */
class AnalyseController extends Controller
{
    public function eleve(Request $request, User $user, AnalyseIa $analyse): JsonResponse
    {
        $center = $this->verifierAccesEleve($request, $user);

        return $this->repondre($request, $center, 'eleve', $user->id, fn (string $mode) => $analyse->eleve($center, $user, $mode));
    }

    public function classe(Request $request, Classroom $classroom, AnalyseIa $analyse): JsonResponse
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        // Jamais la classe d'un autre centre, meme pour un responsable.
        abort_unless((int) $classroom->center_id === $center->id, 404);
        $this->authorize('view', $classroom);

        return $this->repondre($request, $center, 'classe', $classroom->id, fn (string $mode) => $analyse->classe($center, $classroom, $mode));
    }

    public function centre(Request $request, AnalyseIa $analyse): JsonResponse
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        // Le centre entier : c'est la vue du responsable, pas d'un enseignant.
        abort_unless($request->user()->centerRoleIs('center_admin'), 403);

        return $this->repondre($request, $center, 'centre', $center->id, fn (string $mode) => $analyse->centre($center, $mode));
    }

    private function repondre(Request $request, LanguageCenter $center, string $type, int $id, \Closure $produire): JsonResponse
    {
        $mode = ! $request->isMethod('post') ? 'lire' : ($request->boolean('actualiser') ? 'forcer' : 'auto');

        try {
            if ($mode === 'forcer') {
                $cle = AnalyseIa::cleActualisation($center, $type, $id);
                if (RateLimiter::tooManyAttempts($cle, 1)) {
                    $minutes = (int) ceil(RateLimiter::availableIn($cle) / 60);

                    return response()->json([
                        'message' => "L’analyse a déjà été actualisée récemment. Nouvel essai possible dans {$minutes} min.",
                        'analyse' => $produire('lire'),
                    ], 429);
                }
                RateLimiter::hit($cle, AnalyseIa::ACTUALISATION_SECONDES);
            }

            return response()->json(['analyse' => $produire($mode)]);
        } catch (\Throwable $e) {
            Log::error('analyse-ia: echec', ['type' => $type, 'id' => $id, 'erreur' => $e->getMessage()]);

            return response()->json(['analyse' => [
                'sujet' => $type,
                'etat' => 'indisponible',
                'a_jour' => false,
                'source' => null,
                'generee_le' => null,
                'message' => 'L’analyse n’est pas disponible pour le moment. Réessayez dans quelques minutes.',
                'contenu' => null,
                'actualisable_dans' => 0,
            ]]);
        }
    }

    /**
     * Les memes droits que la fiche (StudentController::verifierAcces) : un eleve
     * DE CE CENTRE ; le responsable voit tout le monde, un enseignant seulement
     * les eleves de ses propres classes.
     */
    private function verifierAccesEleve(Request $request, User $user): LanguageCenter
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        $actor = $request->user();

        abort_unless(
            $user->centers()->where('language_centers.id', $center->id)->wherePivot('role', 'student')->exists(),
            403
        );

        if (! $actor->centerRoleIs('center_admin')) {
            $sharesClassroom = $user->classrooms()
                ->wherePivot('role_in_class', 'student')
                ->where('center_id', $center->id)
                ->whereHas('teachers', fn ($q) => $q->whereKey($actor->id))
                ->exists();

            abort_unless($sharesClassroom, 403);
        }

        return $center;
    }
}
