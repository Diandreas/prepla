<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Exercise;
use App\Models\LanguageCenter;
use App\Models\Lesson;
use App\Services\Center\AssignmentProgressService;
use App\Notifications\AssignmentPublishedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    /** Les classes sur lesquelles cette personne a reellement la main. */
    private function classroomIdsFor(\App\Models\User $user, LanguageCenter $center): array
    {
        if ($user->isSuperAdmin() || $user->centerRole() === 'center_admin') {
            return $center->classrooms()->pluck('id')->all();
        }

        return $user->classrooms()
            ->wherePivot('role_in_class', 'teacher')
            ->where('classrooms.center_id', $center->id)
            ->pluck('classrooms.id')
            ->all();
    }

    public function index(Request $request): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        // Un responsable d'espace voit tout ; un enseignant seulement ses classes.
        // Sans cela, la liste montrait les devoirs des collegues et chaque clic
        // tombait sur un refus, la politique n'autorisant que ses propres classes.
        $mesClasses = $this->classroomIdsFor($request->user(), $center);

        $assignments = Assignment::whereIn('classroom_id', $mesClasses)
            ->with(['classroom:id,name', 'items'])
            ->latest()
            ->get()
            ->map(fn (Assignment $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'classroom' => $a->classroom?->name,
                'items_count' => $a->items->count(),
                'due_at' => $a->due_at,
                'published' => $a->isPublished(),
            ]);

        return Inertia::render('center/assignments/index', [
            'assignments' => $assignments,
        ]);
    }

    public function create(Request $request): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        return Inertia::render('center/assignments/create', [
            // Meme regle a la creation : proposer une classe qu'il n'encadre pas
            // menait a un refus apres coup, apres avoir choisi ses exercices.
            'classrooms' => $center->classrooms()
                ->whereNull('archived_at')
                ->whereKey($this->classroomIdsFor($request->user(), $center))
                ->get(['id', 'name']),
            'exercises' => Exercise::where('center_id', $center->id)
                ->with('exerciseType:id,name')
                ->latest()
                ->get()
                ->map(fn (Exercise $e) => [
                    'id' => $e->id,
                    'label' => ($e->exerciseType?->name ?? 'Exercice') . ' #' . $e->id,
                ]),
        ]);
    }

    public function store(Request $request)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string',
            'due_at' => 'nullable|date',
            'exercise_ids' => 'required|array|min:1',
            'exercise_ids.*' => 'integer',
        ]);

        $classroom = Classroom::findOrFail($validated['classroom_id']);
        $this->authorize('update', $classroom); // same-center + teacher/admin check

        // Only this center's exercises may be assigned.
        $exerciseIds = Exercise::where('center_id', $center->id)
            ->whereIn('id', $validated['exercise_ids'])
            ->pluck('id');

        abort_if($exerciseIds->isEmpty(), 422, 'Aucun exercice valide du centre sélectionné.');

        $assignment = $classroom->assignments()->create([
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
            'published_at' => now(), // published immediately in this lot
        ]);

        foreach ($exerciseIds as $i => $exId) {
            $assignment->items()->create([
                'itemable_type' => Exercise::class,
                'itemable_id' => $exId,
                'sort_order' => $i,
            ]);
        }

        // Un devoir publié que personne n'annonce n'est découvert qu'au hasard d'une
        // ouverture de l'application. On prévient les élèves de la classe visée.
        $students = $classroom->students()->get();
        $prevenus = $students->count();

        // La file tourne en mode synchrone : un envoi qui échoue (notification
        // refusée, SMTP indisponible) remonterait ici et ferait échouer la
        // publication d'un devoir pourtant enregistré. L'enseignant doit garder son
        // devoir, et savoir que l'avis n'est peut-être pas parti.
        try {
            Notification::send(
                $students,
                AssignmentPublishedNotification::forAssignment($assignment, $request->user()->name)
            );
            $message = "Devoir créé et publié. {$prevenus} élève" . ($prevenus > 1 ? 's prévenus.' : ' prévenu.');
        } catch (\Throwable $e) {
            Log::warning('Devoir publié mais notification non partie', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);
            $message = "Devoir créé et publié, mais l'avis aux élèves n'a pas pu être envoyé. Préviens-les directement.";
        }

        return redirect()->route('center.assignments.show', $assignment->id)->with('success', $message);
    }

    public function show(Request $request, Assignment $assignment, AssignmentProgressService $progress): Response
    {
        $this->authorize('view', $assignment);

        $assignment->load(['classroom.students', 'items.itemable']);

        return Inertia::render('center/assignments/show', [
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'classroom' => $assignment->classroom?->name,
                'due_at' => $assignment->due_at,
                'items_count' => $assignment->items->count(),
            ],
            'rows' => $progress->perStudent($assignment),
        ]);
    }

    public function destroy(Request $request, Assignment $assignment)
    {
        $this->authorize('delete', $assignment);
        $assignment->delete();

        return redirect()->route('center.assignments.index')->with('success', 'Devoir supprimé.');
    }
}
