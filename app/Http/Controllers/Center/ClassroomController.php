<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Services\Center\ClassementCentre;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function index(Request $request, ClassementCentre $classement): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        $examenParDefaut = $center->loadMissing('defaultExam.language')->defaultExam;

        // A teacher only browses the classrooms they're attached to; a
        // center_admin sees every classroom of the center.
        $classrooms = $classement->classesVisibles($center, $request->user())
            ->withCount('students')
            ->with(['exam.language', 'teachers:id,name', 'students:id'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Classroom $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'room' => $c->room,
                'schedule' => $c->schedule,
                'level' => ClassementCentre::niveau($c->level),
                // Comme la langue : sans examen, celui du centre. Sinon une classe
                // ancienne (exam_id vide) formait un groupe a part, « Examen par
                // defaut », a cote du groupe de ce meme examen.
                'exam' => ($c->exam ?? $examenParDefaut)?->name,
                // La langue de son examen, a defaut celle de l'examen par defaut
                // du centre : c'est elle qu'on range et qu'on filtre.
                'langue' => $classement->langueDeLaClasse($c, $examenParDefaut),
                'teachers' => $c->teachers->map(fn (User $t) => ['id' => $t->id, 'name' => $t->name])->values(),
                'invite_code' => $c->invite_code,
                'students_count' => $c->students_count,
                // Pour compter les personnes d'un groupe de classes : un eleve
                // inscrit dans deux classes du groupe ne compte qu'une fois.
                'student_ids' => $c->students->pluck('id')->values(),
            ]);

        return Inertia::render('center/classes/index', [
            'classrooms' => $classrooms,
            // Seulement les examens que le centre propose (personnalisation).
            'exams' => Exam::proposables($center)->get(['id', 'name']),
            'seatsAvailable' => $center->seatsAvailable(),
            // Le responsable cree la classe d'un enseignant directement : il doit
            // pouvoir la lui confier des la creation.
            'enseignants' => $request->user()->centerRoleIs('center_admin')
                ? $center->members()->wherePivotIn('role', ['center_admin', 'teacher'])->orderBy('name')->get(['users.id', 'users.name'])
                    ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'role' => $u->pivot->role])->values()
                : [],
        ]);
    }

    public function store(Request $request)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'nullable|string|max:10',
            'room' => 'nullable|string|max:60',
            'schedule' => 'nullable|string|max:120',
            'exam_id' => ['nullable', 'exists:exams,id', new \App\Rules\ExamenDuCentre($center)],
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'integer',
        ]);

        // Enseignants choisis par le responsable : seulement du personnel de CE
        // centre. Un enseignant ne confie pas de classe a un collegue.
        $choisis = [];
        if (! empty($validated['teacher_ids']) && $request->user()->centerRoleIs('center_admin')) {
            $choisis = $center->members()->whereKey($validated['teacher_ids'])
                ->wherePivotIn('role', ['center_admin', 'teacher'])->pluck('users.id')->all();
            abort_if(count($choisis) !== count(array_unique($validated['teacher_ids'])), 422, "Un enseignant choisi ne fait pas partie de l'équipe du centre.");
        }

        $classroom = $center->classrooms()->create([
            'name' => $validated['name'],
            'level' => $validated['level'] ?? null,
            'room' => $validated['room'] ?? null,
            'schedule' => $validated['schedule'] ?? null,
            'exam_id' => $validated['exam_id'] ?? $center->default_exam_id,
            'invite_code' => Classroom::generateInviteCode(),
        ]);

        // Sans choix, la personne qui cree la classe la tient, comme avant.
        $tenue = $choisis ?: [$request->user()->id];
        $classroom->members()->syncWithoutDetaching(
            collect($tenue)->mapWithKeys(fn ($id) => [$id => ['role_in_class' => 'teacher']])->all()
        );

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
                'room' => $classroom->room,
                'schedule' => $classroom->schedule,
                'exam' => $classroom->exam?->name,
                // L'identifiant, pas seulement le nom : sans lui, impossible de
                // pre-remplir le formulaire de modification de la classe.
                'exam_id' => $classroom->exam_id,
                'invite_code' => $classroom->invite_code,
            ],
            'students' => $students,
            'stats' => $stats->forClassroom($classroom),
            // Le parcours construit par le centre pour cette classe (ParcoursClasseController).
            'parcoursEtapes' => $classroom->pathSteps()->count(),
            'contenuCentre' => (bool) $classroom->center?->reserveSesElevesASonContenu(),
            'exams' => \App\Models\Exam::proposables($classroom->center)->with('language:id,name')->get(['id', 'name', 'language_id'])
                ->map(fn ($exam) => ['id' => $exam->id, 'name' => $exam->name, 'language' => $exam->language?->name]),
        ]);
    }

    public function update(Request $request, Classroom $classroom)
    {
        $this->authorize('update', $classroom);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'nullable|string|max:10',
            'room' => 'nullable|string|max:60',
            'schedule' => 'nullable|string|max:120',
            'exam_id' => ['nullable', 'exists:exams,id', new \App\Rules\ExamenDuCentre($classroom->center)],
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

    /**
     * Archive (jamais de suppression dure) : une classe a des devoirs et des
     * tentatives d'élèves rattachés dont on veut garder l'historique. La
     * colonne archived_at existait déjà en base mais n'était exposée par
     * aucune route/UI — une classe créée ne pouvait jamais être retirée.
     */
    public function archive(Request $request, Classroom $classroom)
    {
        $this->authorize('delete', $classroom);
        $classroom->update(['archived_at' => now()]);

        return redirect()->route('center.classes.index')->with('success', "Classe « {$classroom->name} » archivée.");
    }
}
