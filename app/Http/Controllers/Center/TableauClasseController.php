<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Services\Center\TableauDeClasse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Le tableau de bord d'une classe, en JSON, lu par
 * resources/js/components/center/tableau-de-classe.tsx sur la fiche de la classe.
 *
 * Droits : ceux de la fiche (ClassroomPolicy::view) : le responsable pour
 * toutes les classes du centre, l'enseignant pour les siennes, jamais la
 * classe d'un autre centre.
 */
class TableauClasseController extends Controller
{
    public function __invoke(Request $request, Classroom $classroom, TableauDeClasse $tableau): JsonResponse
    {
        $this->authorize('view', $classroom);

        // Le centre affiche (EnsureCenterStaff) : une classe d'un autre centre
        // du meme membre ne se lit pas depuis celui-ci.
        /** @var LanguageCenter|null $centre */
        $centre = $request->attributes->get('center');
        abort_if($centre && $classroom->center_id !== $centre->id, 403);

        return response()->json($tableau->pour($classroom));
    }
}
