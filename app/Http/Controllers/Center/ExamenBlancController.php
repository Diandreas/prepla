<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\LanguageCenter;
use App\Models\MockExam;
use App\Models\MockExamPassage;
use App\Notifications\AssignmentPublishedNotification;
use App\Services\Center\ExamensBlancsCentre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Les examens blancs composes par le centre : liste, creation, modification,
 * mise en devoir d'une classe et resultats.
 *
 * Un centre ne voit et ne modifie que les siens (center_id). Les epreuves
 * PrePla et le catalogue d'exercices restent partages, en lecture seule.
 */
class ExamenBlancController extends Controller
{
    private const NIVEAUX = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    public function __construct(private ExamensBlancsCentre $examens) {}

    public function index(Request $request): Response
    {
        $center = $this->centre($request);

        $examens = MockExam::where('center_id', $center->id)
            ->with(['blueprint.exam:id,name', 'creator:id,name'])
            ->withCount(['items', 'passages'])
            ->latest()
            ->get()
            ->map(fn (MockExam $m) => [
                'id' => $m->id,
                'title' => $m->title,
                'exam' => $m->blueprint?->exam?->name,
                'level' => $m->level,
                'published' => $m->is_published,
                'items_count' => $m->items_count,
                'passages_count' => $m->passages_count,
                'duree' => $m->dureeTotale(),
                'creator' => $m->creator?->name,
            ]);

        return Inertia::render('center/examens-blancs/index', ['examens' => $examens]);
    }

    public function create(Request $request): Response
    {
        return $this->formulaire($request, null);
    }

    public function edit(Request $request, MockExam $mockExam): Response
    {
        $this->sien($request, $mockExam);

        return $this->formulaire($request, $mockExam);
    }

    public function store(Request $request): RedirectResponse
    {
        $center = $this->centre($request);
        $donnees = $this->valider($request);
        $exam = Exam::findOrFail($donnees['exam_id']);
        $plan = $this->examens->plan($exam, $donnees['level'] ?? null);
        if (! $plan) {
            throw ValidationException::withMessages(['exam_id' => "Cet examen n'a pas encore de structure."]);
        }

        $mock = DB::transaction(function () use ($center, $donnees, $exam, $plan, $request) {
            $mock = MockExam::create([
                'blueprint_id' => $plan->id,
                'center_id' => $center->id,
                'creator_id' => $request->user()->id,
                'level' => $donnees['level'] ?? null,
                'title' => $donnees['title'],
                'description' => $donnees['description'] ?? null,
                'durees_sections' => $donnees['durees'] ?? [],
                'is_published' => false,
            ]);
            $this->examens->enregistrerItems($mock, $center, $exam, $donnees['items'] ?? []);
            $this->publierSiDemande($mock, (bool) ($donnees['publier'] ?? false));

            return $mock;
        });

        return redirect()->route('center.examens-blancs.show', $mock->id)->with('success', 'Examen blanc enregistré.');
    }

    public function update(Request $request, MockExam $mockExam): RedirectResponse
    {
        $center = $this->sien($request, $mockExam);
        $donnees = $this->valider($request);
        $exam = Exam::findOrFail($donnees['exam_id']);
        $plan = $this->examens->plan($exam, $donnees['level'] ?? null);
        if (! $plan) {
            throw ValidationException::withMessages(['exam_id' => "Cet examen n'a pas encore de structure."]);
        }

        DB::transaction(function () use ($mockExam, $center, $donnees, $exam, $plan) {
            $mockExam->update([
                'blueprint_id' => $plan->id,
                'level' => $donnees['level'] ?? null,
                'title' => $donnees['title'],
                'description' => $donnees['description'] ?? null,
                'durees_sections' => $donnees['durees'] ?? [],
            ]);
            $this->examens->enregistrerItems($mockExam, $center, $exam, $donnees['items'] ?? []);
            $this->publierSiDemande($mockExam, (bool) ($donnees['publier'] ?? $mockExam->is_published));
        });

        return redirect()->route('center.examens-blancs.show', $mockExam->id)->with('success', 'Examen blanc mis à jour.');
    }

    public function destroy(Request $request, MockExam $mockExam): RedirectResponse
    {
        $this->sien($request, $mockExam);
        // Un devoir qui le portait ne pointerait plus sur rien.
        \App\Models\AssignmentItem::where('itemable_type', MockExam::class)->where('itemable_id', $mockExam->id)->delete();
        $mockExam->delete();

        return redirect()->route('center.examens-blancs.index')->with('success', 'Examen blanc supprimé.');
    }

    /** La fiche : sa composition, les classes a qui le donner, et les resultats. */
    public function show(Request $request, MockExam $mockExam): Response
    {
        $center = $this->sien($request, $mockExam);
        $mockExam->load(['blueprint.exam', 'items']);
        $exam = $mockExam->blueprint?->exam;
        $sections = $exam ? $this->examens->sections($exam, $mockExam->level) : [];

        // Le dernier passage de chaque eleve.
        $passages = MockExamPassage::where('mock_exam_id', $mockExam->id)
            ->with('user:id,name')
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->values()
            ->map(fn (MockExamPassage $p) => [
                'eleve' => $p->user?->name,
                'score' => $p->score,
                'sections' => $p->scores_sections ?? [],
                'temps' => (int) round($p->temps_passe / 60),
                'date' => $p->created_at?->toIso8601String(),
            ]);

        return Inertia::render('center/examens-blancs/show', [
            'examen' => [
                'id' => $mockExam->id,
                'title' => $mockExam->title,
                'description' => $mockExam->description,
                'exam' => $exam?->name,
                'level' => $mockExam->level,
                'published' => $mockExam->is_published,
                'duree' => $mockExam->dureeTotale(),
                'items_count' => $mockExam->items->count(),
            ],
            'sections' => collect($sections)->map(fn ($s) => [
                'slug' => $s['slug'],
                'nom' => $s['nom'],
                'duree' => (int) ($mockExam->durees_sections[$s['slug']] ?? 0),
                'exercices' => $mockExam->items->where('section_slug', $s['slug'])->count(),
            ])->values(),
            'passages' => $passages,
            'classes' => $center->classrooms()->whereNull('archived_at')
                ->whereKey($this->classesDe($request, $center))
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Donne l'examen blanc en devoir a une classe. */
    public function donner(Request $request, MockExam $mockExam): RedirectResponse
    {
        $this->sien($request, $mockExam);
        $donnees = $request->validate([
            'classroom_id' => 'required|integer|exists:classrooms,id',
            'due_at' => 'nullable|date',
        ]);
        $classe = Classroom::findOrFail($donnees['classroom_id']);
        $this->authorize('update', $classe);
        abort_unless((int) $classe->center_id === (int) $mockExam->center_id, 403);

        if (! $mockExam->is_published) {
            throw ValidationException::withMessages(['classroom_id' => "Publiez l'examen blanc avant de le donner à une classe."]);
        }

        $devoir = $classe->assignments()->create([
            'created_by' => $request->user()->id,
            'title' => $mockExam->title,
            'instructions' => $mockExam->description,
            'due_at' => $donnees['due_at'] ?? null,
            'published_at' => now(),
        ]);
        $devoir->items()->create(['itemable_type' => MockExam::class, 'itemable_id' => $mockExam->id, 'sort_order' => 0]);

        try {
            Notification::send($classe->students()->get(), AssignmentPublishedNotification::forAssignment($devoir, $request->user()->name));
        } catch (\Throwable $e) {
            Log::warning('Examen blanc donne mais notification non partie', ['assignment_id' => $devoir->id, 'error' => $e->getMessage()]);
        }

        return redirect()->route('center.examens-blancs.show', $mockExam->id)->with('success', 'Examen blanc donné à la classe.');
    }

    private function formulaire(Request $request, ?MockExam $mock): Response
    {
        $center = $this->centre($request);
        $examId = (int) ($request->query('exam_id') ?? $mock?->blueprint?->exam_id ?? $center->default_exam_id ?? 0);
        $niveau = $request->query('level', $mock?->level);
        $niveau = in_array($niveau, self::NIVEAUX, true) ? $niveau : null;
        $exam = $examId ? Exam::find($examId) : null;

        return Inertia::render('center/examens-blancs/edit', [
            'examen' => $mock ? [
                'id' => $mock->id,
                'title' => $mock->title,
                'description' => $mock->description,
                'published' => $mock->is_published,
                'durees' => (object) ($mock->durees_sections ?? []),
                'items' => $mock->items()->get(['exercise_id', 'section_slug', 'partie']),
            ] : null,
            'exams' => Exam::orderBy('name')->get(['id', 'name']),
            'niveaux' => self::NIVEAUX,
            'examId' => $exam?->id,
            'niveau' => $niveau,
            'sections' => $exam ? $this->examens->sections($exam, $niveau) : [],
            'exercices' => $exam ? $this->examens->exercicesProposes($center, $exam, $niveau) : [],
        ]);
    }

    /** @return array<string, mixed> */
    private function valider(Request $request): array
    {
        return $request->validate([
            'exam_id' => 'required|integer|exists:exams,id',
            'level' => ['nullable', Rule::in(self::NIVEAUX)],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'durees' => 'nullable|array',
            'durees.*' => 'integer|min:0|max:600',
            'items' => 'nullable|array|max:200',
            'items.*.exercise_id' => 'required|integer',
            'items.*.section_slug' => 'required|string|max:100',
            'items.*.partie' => 'nullable|string|max:100',
            'publier' => 'nullable|boolean',
        ]);
    }

    private function publierSiDemande(MockExam $mock, bool $publier): void
    {
        if ($publier && $mock->items()->count() === 0) {
            throw ValidationException::withMessages(['items' => 'Choisissez au moins un exercice avant de publier.']);
        }
        $mock->update(['is_published' => $publier]);
    }

    private function centre(Request $request): LanguageCenter
    {
        return $request->attributes->get('center');
    }

    /** Un examen blanc d'un autre centre, ou de PrePla, n'existe pas ici. */
    private function sien(Request $request, MockExam $mock): LanguageCenter
    {
        $center = $this->centre($request);
        abort_unless($mock->center_id !== null && (int) $mock->center_id === (int) $center->id, 404);

        return $center;
    }

    /** Meme regle que les devoirs : le responsable a toutes les classes, l'enseignant les siennes. */
    private function classesDe(Request $request, LanguageCenter $center): array
    {
        $user = $request->user();
        if ($user->isSuperAdmin() || $user->centerRole() === 'center_admin') {
            return $center->classrooms()->pluck('id')->all();
        }

        return $user->classrooms()->wherePivot('role_in_class', 'teacher')
            ->where('classrooms.center_id', $center->id)->pluck('classrooms.id')->all();
    }
}
