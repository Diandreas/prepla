<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use Illuminate\Http\Request;

/**
 * Qui tient quelle classe. Un enseignant n'etait rattache qu'aux classes qu'il
 * creait lui-meme ou que son invitation ouvrait : le responsable ne pouvait ni
 * lui confier une classe existante, ni la lui retirer. Un enseignant peut tenir
 * plusieurs classes, et une classe avoir plusieurs enseignants.
 */
class EquipeClassesController extends Controller
{
    public function attacher(Request $request, Classroom $classroom)
    {
        [$center, $enseignantId] = $this->verifier($request, $classroom);

        $classroom->members()->syncWithoutDetaching([$enseignantId => ['role_in_class' => 'teacher']]);

        return back()->with('success', "Classe « {$classroom->name} » attribuée.");
    }

    public function retirer(Request $request, Classroom $classroom)
    {
        [$center, $enseignantId] = $this->verifier($request, $classroom);

        // Seulement son role d'enseignant dans cette classe : jamais un eleve.
        $classroom->members()->wherePivot('role_in_class', 'teacher')->detach($enseignantId);

        return back()->with('success', "Classe « {$classroom->name} » retirée.");
    }

    /** @return array{0: LanguageCenter, 1: int} */
    private function verifier(Request $request, Classroom $classroom): array
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        // Reserve au responsable, comme la page Equipe.
        abort_unless($request->user()->centerRoleIs('center_admin'), 403);
        abort_unless($classroom->center_id === $center->id && $classroom->archived_at === null, 404);

        $validated = $request->validate(['user_id' => 'required|integer']);

        // Seulement un membre du personnel de CE centre.
        $estDuPersonnel = $center->members()
            ->whereKey($validated['user_id'])
            ->wherePivotIn('role', ['center_admin', 'teacher'])
            ->exists();
        abort_unless($estDuPersonnel, 422, "Cette personne ne fait pas partie de l'équipe du centre.");

        return [$center, (int) $validated['user_id']];
    }
}
