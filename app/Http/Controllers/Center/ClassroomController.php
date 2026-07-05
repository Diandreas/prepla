<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\LanguageCenter;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        $user = $request->user();

        $query = $center->classrooms()
            ->withCount('students')
            ->with('exam:id,name')
            ->orderByDesc('created_at');

        // A teacher only browses the classrooms they're attached to; a
        // center_admin sees every classroom of the center.
        if (! $user->centerRoleIs('center_admin')) {
            $query->whereHas('teachers', fn ($q) => $q->whereKey($user->id));
        }

        $classrooms = $query->get()
            ->map(fn (Classroom $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'level' => $c->level,
                'exam' => $c->exam?->name,
                'invite_code' => $c->invite_code,
                'students_count' => $c->students_count,
            ]);

        return Inertia::render('center/classes/index', [
            'classrooms' => $classrooms,
            'exams' => Exam::get(['id', 'name']),
            'seatsAvailable' => $center->seatsAvailable(),
        ]);
    }

    public function store(Request $request)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'nullable|string|max:10',
            'exam_id' => 'nullable|exists:exams,id',
        ]);

        $classroom = $center->classrooms()->create([
            'name' => $validated['name'],
            'level' => $validated['level'] ?? null,
            'exam_id' => $validated['exam_id'] ?? $center->default_exam_id,
            'invite_code' => Classroom::generateInviteCode(),
        ]);

        // The acting teacher is attached so they manage it by default.
        $classroom->members()->syncWithoutDetaching([
            $request->user()->id => ['role_in_class' => 'teacher'],
        ]);

        return back()->with('success', "Classe « {$classroom->name} » créée. Code : {$classroom->invite_code}");
    }

    public function show(Request $request, Classroom $classroom, \App\Services\Center\ClassStatsService $stats): Response
    {
        $this->authorize('view', $classroom);

        $classroom->load(['exam:id,name']);
        $students = $classroom->students()->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
        ]);

        return Inertia::render('center/classes/show', [
            'classroom' => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'level' => $classroom->level,
                'exam' => $classroom->exam?->name,
                'invite_code' => $classroom->invite_code,
            ],
            'students' => $students,
            'stats' => $stats->forClassroom($classroom),
        ]);
    }

    public function update(Request $request, Classroom $classroom)
    {
        $this->authorize('update', $classroom);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'nullable|string|max:10',
            'exam_id' => 'nullable|exists:exams,id',
        ]);

        $classroom->update($validated);

        return back()->with('success', 'Classe mise à jour.');
    }

    public function regenerateCode(Request $request, Classroom $classroom)
    {
        $this->authorize('update', $classroom);
        $classroom->update(['invite_code' => Classroom::generateInviteCode()]);

        return back()->with('success', "Nouveau code : {$classroom->invite_code}");
    }
}
