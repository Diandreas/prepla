<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\TeacherInvitation;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * L'equipe d'un centre, vue par son responsable : qui enseigne, quelles
 * classes, combien d'eleves, et si ces classes travaillent.
 *
 * Un responsable pouvait inviter un enseignant, mais ne voyait nulle part qui
 * faisait partie de son centre, ni quelle classe etait a qui. Il devait ouvrir
 * les classes une a une pour le deviner.
 */
class EquipeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        // Reserve au responsable : un enseignant n'a pas a voir ses collegues.
        abort_unless($request->user()->centerRoleIs('center_admin'), 403);

        $classes = Classroom::where('center_id', $center->id)
            ->whereNull('archived_at')
            ->with(['teachers:id,name', 'students:id'])
            ->orderBy('name')
            ->get();

        // La derniere activite d'eleve par classe : une classe qui ne travaille
        // plus se voit d'un coup d'oeil, sans ouvrir chaque fiche.
        $activite = $classes->mapWithKeys(fn (Classroom $k) => [
            $k->id => ($ids = $k->students->pluck('id'))->isEmpty()
                ? null
                : UserExerciseAttempt::whereIn('user_id', $ids)->max('created_at'),
        ]);

        $ligneDeClasse = fn (Classroom $k) => [
            'id' => $k->id,
            'name' => $k->name,
            'level' => $k->level,
            'eleves' => $k->students->count(),
            'derniere_activite' => $activite[$k->id] ? Carbon::parse($activite[$k->id], 'UTC')->toIso8601String() : null,
        ];

        $equipe = $center->members()
            ->wherePivotIn('role', ['center_admin', 'teacher'])
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->pivot->role,
                'depuis' => $u->pivot->joined_at ? Carbon::parse($u->pivot->joined_at)->toIso8601String() : null,
                'classes' => $classes
                    ->filter(fn (Classroom $k) => $k->teachers->contains('id', $u->id))
                    ->map($ligneDeClasse)
                    ->values(),
            ]);

        return Inertia::render('center/equipe', [
            'centre' => ['id' => $center->id, 'name' => $center->name],
            'equipe' => $equipe,
            // Une classe sans enseignant est une classe dont personne ne s'occupe.
            'classesSansEnseignant' => $classes
                ->filter(fn (Classroom $k) => $k->teachers->isEmpty())
                ->map($ligneDeClasse)
                ->values(),
            'invitations' => TeacherInvitation::where('center_id', $center->id)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->latest()
                ->get(['id', 'name', 'email', 'role', 'expires_at'])
                ->map(fn (TeacherInvitation $i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'email' => $i->email,
                    'role' => $i->role,
                    'expire' => $i->expires_at?->toIso8601String(),
                ]),
        ]);
    }
}
