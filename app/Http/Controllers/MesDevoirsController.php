<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Exercise;
use App\Services\Center\ParcoursEleveDeCentre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * « Mes devoirs » : le travail donne par le centre, cote eleve.
 *
 * Les devoirs n'apparaissaient que dans un bandeau de l'accueil, sans page a
 * eux : le bouton « Faire mon devoir » doit mener quelque part.
 */
class MesDevoirsController extends Controller
{
    public function __construct(private ParcoursEleveDeCentre $parcours) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $centre = $this->parcours->centre($user);
        if (! $centre) {
            return redirect()->route('dashboard');
        }

        $devoirs = $this->parcours->devoirs($user, $centre);

        return Inertia::render('devoirs/index', [
            'aFaire' => $devoirs->filter(fn ($d) => $d['total'] > 0 && ! $d['termine'])->values(),
            'termines' => $devoirs->filter(fn ($d) => $d['termine'])->values(),
            'contenuCentre' => $centre->reserveSesElevesASonContenu(),
            // Sans classe, il n'a rien pu recevoir : on lui dit d'entrer son code.
            'sansClasse' => $this->parcours->classes($user, $centre)->isEmpty(),
        ]);
    }

    public function show(Request $request, Assignment $assignment): Response|RedirectResponse
    {
        $user = $request->user();
        $centre = $this->parcours->centre($user);
        if (! $centre) {
            return redirect()->route('dashboard');
        }

        // Un devoir de SES classes, deja publie. Sinon on ne dit meme pas qu'il existe.
        $devoir = $this->parcours->devoirs($user, $centre)->firstWhere('id', $assignment->id);
        abort_if($devoir === null, 404);

        $assignment->load('items');
        $ids = $assignment->items->where('itemable_type', Exercise::class)->pluck('itemable_id');
        $exercices = Exercise::whereIn('id', $ids)->horsBrouillon()->with('exerciseType:id,name,slug,component_key')->get(['id', 'exercise_type_id'])->keyBy('id');
        $faits = \App\Models\UserExerciseAttempt::where('user_id', $user->id)
            ->whereIn('exercise_id', $ids)
            ->where('created_at', '>=', $assignment->published_at)
            ->pluck('exercise_id')->map(fn ($id) => (int) $id)->unique();

        return Inertia::render('devoirs/show', [
            'devoir' => $devoir,
            'exercices' => $ids->map(fn ($id) => [
                'id' => (int) $id,
                // Le nom technique anglais (« Multiple Choice ») ; la page pose le
                // nom francais depuis le slug (libelleDuType).
                'type' => $exercices->get($id)?->exerciseType?->name ?? 'Exercice',
                'type_slug' => $exercices->get($id)?->exerciseType?->slug,
                'component_key' => $exercices->get($id)?->exerciseType?->component_key,
                'fait' => $faits->contains((int) $id),
            ])->filter(fn ($e) => $exercices->has($e['id']))->values(),
        ]);
    }
}
