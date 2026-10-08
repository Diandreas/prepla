<?php

namespace App\Http\Controllers;

use App\Models\CenterUser;
use App\Models\Classroom;
use App\Models\CurriculumSkeleton;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class JoinCenterController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        // Seul le PERSONNEL est bloque ici. Un eleve deja inscrit pouvait changer de
        // classe au sein de son etablissement, mais le formulaire disparaissait et le
        // message lui parlait d'un centre, pas de sa classe.
        return Inertia::render('join', [
            'alreadyInCenter' => $user->center() !== null && ! $user->centerRoleIs('student'),
            'currentCenter' => $user->center()?->name,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();

        // Le personnel d'un centre ne s'inscrit pas comme eleve : il apparaitrait dans
        // la liste d'eleves de sa propre classe et dans ses statistiques.
        if ($user->center() !== null && ! $user->centerRoleIs('student')) {
            throw ValidationException::withMessages([
                'code' => "Vous encadrez déjà un espace : ce code est réservé aux élèves.",
            ]);
        }

        $classroom = Classroom::with('center')
            ->whereNull('archived_at')
            ->where('invite_code', strtoupper(trim($validated['code'])))
            ->first();

        if (! $classroom || ! $classroom->center || ! $classroom->center->is_active) {
            throw ValidationException::withMessages([
                'code' => "Code invalide ou centre indisponible.",
            ]);
        }

        $center = $classroom->center;
        $centreActuel = $user->center();

        // Un eleve change de classe DANS son etablissement ; passer d'un
        // etablissement a un autre reste un geste administratif.
        if ($centreActuel && $centreActuel->id !== $center->id) {
            throw ValidationException::withMessages([
                'code' => "Ce code appartient à un autre établissement. Demandez à « {$centreActuel->name} » de vous détacher d'abord.",
            ]);
        }

        if ($classroom->members()->where('users.id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'code' => "Vous êtes déjà dans la classe « {$classroom->name} ».",
            ]);
        }

        // Seat check : une place est deja prise par un eleve qui change de classe.
        if (! $centreActuel && $center->seatsAvailable() < 1) {
            throw ValidationException::withMessages([
                'code' => "Ce centre a atteint sa limite de licences. Contactez votre établissement.",
            ]);
        }

        $parcoursConserve = false;

        DB::transaction(function () use ($user, $center, $classroom, &$parcoursConserve) {
            CenterUser::firstOrCreate(
                ['center_id' => $center->id, 'user_id' => $user->id],
                ['role' => 'student', 'joined_at' => now()],
            );

            $classroom->members()->syncWithoutDetaching([
                $user->id => ['role_in_class' => 'student'],
            ]);

            // L'examen de la classe n'est repris que si l'apprenant n'a PAS encore de
            // parcours. Sinon on changeait son examen cible sous ses pieds : son
            // programme restait ecrit pour l'ancien examen, et le tableau de bord ne
            // retrouvait plus rien — parcours gele, semaines de travail inaccessibles.
            // Les devoirs du centre ne dependent pas de l'examen cible.
            $examId = $classroom->exam_id ?? $center->default_exam_id;
            $aDejaUnParcours = CurriculumSkeleton::where('user_id', $user->id)->exists();

            if ($examId && $user->profile && ! $aDejaUnParcours) {
                $user->profile->update(['target_exam_id' => $examId]);
            }

            $parcoursConserve = $aDejaUnParcours
                && $examId
                && (int) $user->profile?->target_exam_id !== (int) $examId;
        });

        $message = "Vous avez rejoint « {$center->name} » — classe « {$classroom->name} ».";
        if ($parcoursConserve) {
            $message .= " Votre parcours en cours est conservé : les devoirs de la classe s'ajoutent à vos séances.";
        }

        return redirect()->route('dashboard')->with('success', $message);
    }
}
