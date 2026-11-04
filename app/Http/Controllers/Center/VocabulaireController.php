<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Services\Center\SuiviApprenant;
use App\Services\Center\VocabulaireApprenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ou en est le vocabulaire de mes eleves ?
 *
 * Deux vues : la classe entiere (qui revise, qui laisse ses mots de cote, quels
 * mots resistent a plusieurs eleves) et un eleve (chacun de ses mots, son etape,
 * sa prochaine revision, et ses fautes de vocabulaire dans les exercices).
 */
class VocabulaireController extends Controller
{
    public function eleve(Request $request, User $user, VocabulaireApprenant $vocabulaire, SuiviApprenant $suivi): Response
    {
        $center = $this->verifierAcces($request, $user);
        $suivi = $suivi->pourLeCentre($center);

        return Inertia::render('center/students/vocabulaire', [
            'student' => [
                'id' => $user->id,
                'name' => $user->name,
                'level' => $user->profile?->current_level,
            ],
            'etapes' => VocabulaireApprenant::ETAPES,
            ...$vocabulaire->pourEleve($user),
            // Les mots du lexique ne disent pas tout : les fautes de vocabulaire
            // faites dans les exercices, avec leur correction, completent le tableau.
            'fautes' => collect($suivi->faiblesses($user))
                ->where('categorie', 'vocabulary')
                ->values()
                ->all(),
        ]);
    }

    public function classe(Classroom $classroom, VocabulaireApprenant $vocabulaire): Response
    {
        $this->authorize('view', $classroom);

        return Inertia::render('center/classes/vocabulaire', [
            'classroom' => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'level' => $classroom->level,
            ],
            'etapes' => VocabulaireApprenant::ETAPES,
            ...$vocabulaire->pourClasse($classroom),
        ]);
    }

    /**
     * Meme regle que la fiche de l'eleve (StudentController::verifierAcces) :
     * un ELEVE de ce centre ; le responsable voit tout le monde, un enseignant
     * seulement les eleves de ses classes.
     */
    private function verifierAcces(Request $request, User $user): LanguageCenter
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
