<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Exercise;
use App\Models\LanguageCenter;
use App\Models\Lesson;
use App\Services\Center\AssignmentProgressService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        $assignments = Assignment::whereHas('classroom', fn ($q) => $q->where('center_id', $center->id))
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
            'classrooms' => $center->classrooms()->whereNull('archived_at')->get(['id', 'name']),
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

        return redirect()->route('center.assignments.show', $assignment->id)
            ->with('success', 'Devoir créé et publié.');
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
