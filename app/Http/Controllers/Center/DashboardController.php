<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le point de depart d'un responsable ou d'un enseignant.
 *
 * Il ne donnait que trois chiffres — classes, eleves, sieges restants — et
 * rien de ce qu'on vient y chercher en arrivant : la semaine a-t-elle ete
 * travaillee, quelle classe decroche, qui relancer. Il fallait ouvrir chaque
 * classe pour le savoir.
 *
 * Un enseignant n'y voit que SES classes ; le responsable, tout le centre.
 */
class DashboardController extends Controller
{
    /** Au-dela d'une semaine sans exercice, un eleve est a relancer. */
    private const JOURS_AVANT_RELANCE = 7;

    public function __invoke(Request $request): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        $actor = $request->user();
        $center->loadCount('classrooms');

        $classes = Classroom::where('center_id', $center->id)
            ->whereNull('archived_at')
            ->when(! $actor->centerRoleIs('center_admin'), fn ($q) => $q->whereHas('teachers', fn ($t) => $t->whereKey($actor->id)))
            ->with(['teachers:id,name', 'students:id,name'])
            ->orderBy('name')
            ->get();

        $eleves = $classes->flatMap->students->unique('id')->values();
        $ids = $eleves->pluck('id');

        $derniereParEleve = $ids->isEmpty() ? collect() : UserExerciseAttempt::whereIn('user_id', $ids)
            ->selectRaw('user_id, max(created_at) as derniere')
            ->groupBy('user_id')
            ->pluck('derniere', 'user_id');

        $semaine = $ids->isEmpty() ? null : UserExerciseAttempt::whereIn('user_id', $ids)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('count(*) as exercices, count(distinct user_id) as actifs, coalesce(sum(time_spent), 0) as secondes')
            ->first();

        $iso = fn ($quand) => $quand ? Carbon::parse($quand, 'UTC')->toIso8601String() : null;

        return Inertia::render('center/dashboard', [
            'center' => [
                'id' => $center->id,
                'name' => $center->name,
                'seats_limit' => $center->seats_limit,
                'seats_used' => $center->seatsUsed(),
                'classrooms_count' => $center->classrooms_count,
            ],
            'semaine' => [
                'eleves' => $eleves->count(),
                'actifs' => (int) ($semaine->actifs ?? 0),
                'exercices' => (int) ($semaine->exercices ?? 0),
                'minutes' => (int) round(($semaine->secondes ?? 0) / 60),
            ],
            'classes' => $classes->map(fn (Classroom $k) => [
                'id' => $k->id,
                'name' => $k->name,
                'level' => $k->level,
                'enseignants' => $k->teachers->pluck('name')->values(),
                'eleves' => $k->students->count(),
                'derniere_activite' => $iso($k->students->pluck('id')->map(fn ($id) => $derniereParEleve[$id] ?? null)->filter()->max()),
            ])->values(),
            // Qui relancer : jamais venu, ou absent depuis plus d'une semaine — le
            // plus ancien d'abord.
            'a_relancer' => $eleves
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'derniere_activite' => $iso($derniereParEleve[$u->id] ?? null)])
                ->filter(fn ($e) => $e['derniere_activite'] === null
                    || Carbon::parse($e['derniere_activite'])->lt(now()->subDays(self::JOURS_AVANT_RELANCE)))
                ->sortBy(fn ($e) => $e['derniere_activite'] ?? '0000')
                ->take(8)
                ->values(),
        ]);
    }
}
