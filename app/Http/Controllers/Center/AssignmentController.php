<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Exercise;
use App\Models\LanguageCenter;
use App\Models\Lesson;
use App\Services\Center\AssignmentProgressService;
use App\Services\Center\ClassementCentre;
use App\Services\Center\FicheExercice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
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

    public function index(Request $request, AssignmentProgressService $progress, ClassementCentre $classement): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        $examenParDefaut = $center->loadMissing('defaultExam.language')->defaultExam;

        // Un responsable d'espace voit tout ; un enseignant seulement ses classes.
        // Sans cela, la liste montrait les devoirs des collegues et chaque clic
        // tombait sur un refus, la politique n'autorisant que ses propres classes.
        $mesClasses = $this->classroomIdsFor($request->user(), $center);

        $devoirs = Assignment::whereIn('classroom_id', $mesClasses)
            ->with(['classroom:id,name,level,exam_id', 'classroom.exam.language', 'creator:id,name'])
            ->withCount(['items', 'items as exercise_items_count' => fn (Builder $q) => $q->where('itemable_type', Exercise::class)])
            ->latest()
            ->get();

        // L'avancement de toutes les classes en deux requetes, pas une par devoir.
        $avancement = $progress->syntheses($devoirs);
        $aujourdhui = now()->startOfDay();

        $assignments = $devoirs->map(function (Assignment $a) use ($avancement, $aujourdhui, $classement, $examenParDefaut) {
            $av = $avancement[$a->id] ?? ['eleves' => 0, 'finis' => 0, 'commences' => 0];
            $echu = $a->isOverdue();
            $tousFinis = $av['eleves'] > 0 && $av['finis'] >= $av['eleves'];

            // Brouillon tant qu'il n'est pas publie ; termine quand toute la
            // classe a fini ou que l'echeance est passee ; publie sinon.
            $statut = ! $a->isPublished() ? 'brouillon' : (($echu || $tousFinis) ? 'termine' : 'publie');

            return [
                'id' => $a->id,
                'title' => $a->title,
                'classroom' => $a->classroom ? [
                    'id' => $a->classroom->id,
                    'name' => $a->classroom->name,
                    'level' => ClassementCentre::niveau($a->classroom->level),
                    'langue' => $classement->langueDeLaClasse($a->classroom, $examenParDefaut),
                ] : null,
                'creator' => $a->creator?->name,
                'items_count' => $a->items_count,
                'exercises_count' => $a->exercise_items_count,
                'due_at' => $a->due_at?->toIso8601String(),
                // Le jour de remise tel que l'enseignant l'a saisi, et l'ecart en
                // jours calendaires, calcules ici : le navigateur, a un autre
                // fuseau, decalerait une echeance « a minuit » d'un jour.
                'due_date' => $a->due_at?->toDateString(),
                'days_left' => $a->due_at ? (int) round($aujourdhui->diffInDays($a->due_at->copy()->startOfDay(), false)) : null,
                'overdue' => $echu,
                'published' => $a->isPublished(),
                'created_at' => $a->created_at?->toIso8601String(),
                'statut' => $statut,
                // Echeance passee et des eleves qui n'ont pas fini.
                'en_retard' => $a->isPublished() && $echu && $av['finis'] < $av['eleves'],
                'progress' => [
                    'students' => $av['eleves'],
                    'finished' => $av['finis'],
                    'started' => $av['commences'],
                ],
            ];
        })->values();

        return Inertia::render('center/assignments/index', [
            'assignments' => $assignments,
            'classrooms' => $center->classrooms()
                ->whereKey($mesClasses)
                ->whereNull('archived_at')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        // Revenu du createur d'exercices : l'exercice tout juste cree arrive
        // deja coche. On ne coche que ce qui appartient au centre.
        $demandes = array_values(array_filter(array_map('intval', explode(',', (string) $request->query('exercice', '')))));
        $preselection = $demandes === [] ? collect() : Exercise::where('center_id', $center->id)
            ->whereKey($demandes)
            ->pluck('id');

        // Venu de la fiche d'un eleve (« Donner un devoir ») : sa classe arrive deja
        // choisie. Seulement une classe que ce membre de l'equipe encadre.
        $permises = collect($this->classroomIdsFor($request->user(), $center))->map(fn ($id) => (int) $id);
        $classeDemandee = (int) $request->query('classe', 0);
        $classeDemandee = $permises->contains($classeDemandee) ? $classeDemandee : null;

        return Inertia::render('center/assignments/create', [
            'classeDemandee' => $classeDemandee,
            // Meme regle a la creation : proposer une classe qu'il n'encadre pas
            // menait a un refus apres coup, apres avoir choisi ses exercices.
            'classrooms' => $center->classrooms()
                ->whereNull('archived_at')
                ->whereKey($this->classroomIdsFor($request->user(), $center))
                ->get(['id', 'name', 'exam_id', 'level']),
            // Un titre lisible, le type et le niveau : « Multiple Choice #12 »
            // ne disait pas de quel exercice il s'agissait.
            'exercises' => Exercise::where('center_id', $center->id)
                ->with(FicheExercice::RELATIONS)
                ->latest()
                ->get()
                ->map(fn (Exercise $e) => FicheExercice::ligne($e)),
            'preselection' => $preselection,
        ]);
    }

    /**
     * La banque PrePla pour la classe choisie : les exercices en service de son
     * examen, au niveau de la classe. Chargee a la demande : elle compte des
     * milliers d'exercices.
     */
    public function vivier(Request $request): JsonResponse
    {
        $validated = $request->validate(['classroom_id' => 'required|integer|exists:classrooms,id']);
        $classroom = Classroom::findOrFail($validated['classroom_id']);
        $this->authorize('update', $classroom);

        $niveau = $this->niveauDeLaClasse($classroom);

        $exercices = $this->vivierDe($classroom)
            ->when($niveau, fn (Builder $q) => $q->where('difficulty', $niveau))
            ->with(FicheExercice::RELATIONS)
            ->meilleurDAbord($niveau)
            ->limit(60)
            ->get()
            ->map(fn (Exercise $e) => FicheExercice::ligne($e));

        return response()->json([
            'exercises' => $exercices,
            'niveau' => $niveau,
        ]);
    }

    /**
     * Ajoute des exercices a un devoir deja publie, depuis sa page, sans le
     * recreer. Memes regles qu'a la creation.
     */
    public function addExercises(Request $request, Assignment $assignment)
    {
        $this->authorize('update', $assignment);

        $validated = $request->validate([
            'exercise_ids' => 'required|array|min:1',
            'exercise_ids.*' => 'integer',
        ]);

        $ids = $this->exercicesAutorises($assignment->classroom, $validated['exercise_ids']);
        $deja = $assignment->items()->where('itemable_type', Exercise::class)->pluck('itemable_id')->map(fn ($id) => (int) $id)->all();
        $ordre = (int) $assignment->items()->max('sort_order');
        $ajouts = $ids->reject(fn ($id) => in_array($id, $deja, true))->values();

        foreach ($ajouts as $exId) {
            $assignment->items()->create([
                'itemable_type' => Exercise::class,
                'itemable_id' => $exId,
                'sort_order' => ++$ordre,
            ]);
        }

        $message = $ajouts->isEmpty()
            ? 'Cet exercice fait déjà partie du devoir.'
            : ($ajouts->count() > 1 ? $ajouts->count().' exercices ajoutés au devoir.' : 'Exercice ajouté au devoir.');

        return redirect()->route('center.assignments.show', $assignment->id)->with('success', $message);
    }

    /** Le niveau CECRL de la classe (« B1 », « B1 soir » donnent B1), ou null. */
    private function niveauDeLaClasse(Classroom $classroom): ?string
    {
        return preg_match('/^(A1|A2|B1|B2|C1|C2)/', strtoupper(trim((string) $classroom->level)), $m) ? $m[1] : null;
    }

    /**
     * Le vivier PrePla de la classe : ni exercice d'un centre, ni pratique d'une
     * lecon personnelle, ni etape d'un parcours, ni epreuve blanche ; de l'examen
     * de la classe, et en service. Sans examen connu, rien : on ne devine pas.
     */
    private function vivierDe(Classroom $classroom): Builder
    {
        $examen = $classroom->exam_id ?? $classroom->center?->default_exam_id;

        return Exercise::query()
            ->whereNull('center_id')->whereNull('lesson_id')
            ->whereNull('node_id')->whereNull('mock_exam_id')
            ->where('exam_id', $examen ?? 0)
            ->enService();
    }

    /**
     * Les exercices qu'on peut donner a cette classe : ceux de son centre, et le
     * vivier PrePla de son examen. Un exercice prive d'un AUTRE centre, comme
     * un numero inconnu, fait refuser toute la demande : rien n'est cree.
     *
     * @param  array<int, mixed>  $demandes
     * @return Collection<int, int>  dans l'ordre demande
     */
    private function exercicesAutorises(Classroom $classroom, array $demandes): Collection
    {
        $demandes = collect($demandes)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        $duCentre = Exercise::where('center_id', $classroom->center_id)->whereKey($demandes->all())->pluck('id');
        $duVivier = $this->vivierDe($classroom)->whereKey($demandes->all())->pluck('id');
        $permis = $duCentre->merge($duVivier)->map(fn ($id) => (int) $id)->unique()->all();

        if ($demandes->isEmpty() || count($permis) !== $demandes->count()) {
            throw ValidationException::withMessages([
                'exercise_ids' => "Un exercice choisi n'appartient ni à votre centre ni à la banque PrePla de l'examen de cette classe.",
            ]);
        }

        return $demandes;
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

        // Les exercices du centre, et la banque PrePla de l'examen de la classe.
        // Jamais ceux d'un autre centre : la demande entiere est alors refusee.
        $exerciseIds = $this->exercicesAutorises($classroom, $validated['exercise_ids']);

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

        // Les exercices du devoir, lisibles : titre, type, niveau. Les relations
        // sont chargees d'un coup pour tous.
        $exercices = new EloquentCollection($assignment->items
            ->where('itemable_type', Exercise::class)
            ->sortBy('sort_order')
            ->pluck('itemable')
            ->filter()
            ->values()
            ->all());
        $exercices->load(FicheExercice::RELATIONS);
        $peutModifier = $request->user()->can('update', $assignment);

        return Inertia::render('center/assignments/show', [
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'classroom' => $assignment->classroom?->name,
                'due_at' => $assignment->due_at,
                'items_count' => $assignment->items->count(),
            ],
            'exercises' => $exercices->map(fn (Exercise $e) => FicheExercice::ligne($e))->values(),
            // Ce qu'on peut encore y ajouter : les exercices du centre.
            'centerExercises' => $peutModifier
                ? Exercise::where('center_id', $assignment->classroom?->center_id)
                    ->with(FicheExercice::RELATIONS)->latest()->get()
                    ->map(fn (Exercise $e) => FicheExercice::ligne($e))
                : [],
            'canEdit' => $peutModifier,
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
