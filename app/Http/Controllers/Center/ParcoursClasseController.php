<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ClassroomPathStep;
use App\Models\Exercise;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Services\Center\ParcoursDeClasse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le parcours d'une classe, construit par le centre : une suite d'exercices
 * dans l'ordre, pris dans les exercices du centre ou dans la banque PrePla de
 * l'examen de la classe.
 *
 * Droits : ceux de la modification de la classe (ClassroomPolicy::update) :
 * le responsable pour toutes les classes du centre, l'enseignant pour les
 * siennes, jamais la classe d'un autre centre.
 */
class ParcoursClasseController extends Controller
{
    public function __construct(private ParcoursDeClasse $parcours) {}

    public function show(Request $request, Classroom $classroom): Response
    {
        $this->authorize('update', $classroom);

        /** @var LanguageCenter $centre */
        $centre = $request->attributes->get('center');
        $classroom->load(['exam:id,name', 'center']);

        $etapes = $this->parcours->etapes($classroom);
        $total = $etapes->count();
        $dejaPris = $etapes->pluck('exercise_id')->map(fn ($id) => (int) $id)->all();

        // Les etapes que les eleves suivent vraiment : une etape prise dans le
        // vivier d'un ancien examen de la classe, ou dont l'exercice a ete
        // retire, n'est plus servie. On la signale ici pour qu'on la retire.
        $jouables = $this->parcours->etapesJouables($classroom, $etapes);
        $idsJouables = $jouables->pluck('id')->all();
        $totalJouables = $jouables->count();

        // L'avancement de chaque eleve : x/y etapes, et celle ou il en est.
        $eleves = $classroom->students()->orderBy('name')->get(['users.id', 'users.name']);
        $faites = $this->parcours->etapesFaites($jouables, $eleves->pluck('id'));
        $avancement = $eleves->map(function (User $eleve) use ($jouables, $faites, $totalJouables) {
            $siennes = $faites->get($eleve->id, collect());
            $actuelle = $jouables->first(fn ($e) => ! $siennes->contains($e->id));

            return [
                'id' => $eleve->id,
                'name' => $eleve->name,
                'faites' => $siennes->count(),
                'total' => $totalJouables,
                'actuelle' => $actuelle ? [
                    'numero' => $jouables->search($actuelle) + 1,
                    'titre' => $this->parcours->titre($actuelle),
                ] : null,
            ];
        })->values();

        // La banque PrePla : l'examen de la classe, son niveau par defaut.
        $niveauClasse = ParcoursDeClasse::niveau($classroom->level);
        $filtres = [
            'q' => trim((string) $request->query('q', '')),
            'competence' => (string) $request->query('competence', ''),
            'niveau' => (string) $request->query('niveau', $niveauClasse ?? ''),
        ];
        $examen = $this->parcours->examen($classroom);

        $banque = Exercise::query()
            ->whereNull('center_id')->whereNull('lesson_id')->whereNull('node_id')->whereNull('mock_exam_id')
            ->where('exam_id', $examen ?? 0)
            ->when($filtres['niveau'] !== '', fn ($q) => $q->where('difficulty', $filtres['niveau']))
            ->when(isset(ParcoursDeClasse::COMPETENCES[$filtres['competence']]), fn ($q) => $q->whereHas(
                'exerciseType', fn ($t) => $t->where('skill_type', $filtres['competence'])
            ))
            ->when($filtres['q'] !== '', function ($q) use ($filtres) {
                $mot = '%'.str_replace(['%', '_'], ['\%', '\_'], $filtres['q']).'%';
                $q->where(fn ($r) => $r->where('content', 'like', $mot)
                    ->orWhereHas('exerciseType', fn ($t) => $t->where('name', 'like', $mot)));
            })
            ->with('exerciseType:id,name,skill_type')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(fn (Exercise $e) => $this->exercice($e, $dejaPris));

        $duCentre = Exercise::where('center_id', $classroom->center_id)
            ->with('exerciseType:id,name,skill_type')
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (Exercise $e) => $this->exercice($e, $dejaPris));

        // Copier le parcours d'une autre classe qu'il peut modifier.
        $autres = $centre->classrooms()
            ->whereNull('archived_at')
            ->whereKeyNot($classroom->id)
            ->withCount('pathSteps')
            ->orderBy('name')
            ->get()
            ->filter(fn (Classroom $c) => $c->path_steps_count > 0 && $request->user()->can('update', $c))
            ->map(fn (Classroom $c) => ['id' => $c->id, 'name' => $c->name, 'etapes' => $c->path_steps_count])
            ->values();

        return Inertia::render('center/classes/parcours', [
            'classroom' => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'level' => $classroom->level,
                'exam' => $classroom->exam?->name ?? $centre->defaultExam?->name,
            ],
            'contenuCentre' => $centre->reserveSesElevesASonContenu(),
            'peutChangerLeReglage' => $request->user()->centerRoleIs('center_admin'),
            'etapes' => $etapes->values()->map(fn ($e, $i) => $this->parcours->decrire($e, $i + 1, $total) + [
                'jouable' => in_array($e->id, $idsJouables, true),
            ]),
            'avancement' => $avancement,
            'banque' => $banque,
            'exercicesCentre' => $duCentre,
            'filtres' => $filtres,
            'competences' => collect(ParcoursDeClasse::COMPETENCES)->map(fn ($libelle, $cle) => ['value' => $cle, 'label' => $libelle])->values(),
            'autresClasses' => $autres,
        ]);
    }

    public function store(Request $request, Classroom $classroom): RedirectResponse
    {
        $this->authorize('update', $classroom);

        $valide = $request->validate($this->regles() + [
            'exercise_id' => ['required', 'integer'],
        ]);

        // Jamais l'exercice prive d'un autre centre, ni hors de l'examen de la classe.
        if (! $this->parcours->peutAjouter($classroom, (int) $valide['exercise_id'])) {
            return back()->withErrors(['exercise_id' => 'Cet exercice ne peut pas entrer dans le parcours de cette classe.']);
        }

        // Deux fois le meme exercice : une seule tentative validerait les deux
        // etapes, et l'avancement serait faux.
        if ($classroom->pathSteps()->where('exercise_id', (int) $valide['exercise_id'])->exists()) {
            return back()->withErrors(['exercise_id' => 'Cet exercice est déjà dans le parcours de cette classe.']);
        }

        $classroom->pathSteps()->create([
            'position' => ((int) $classroom->pathSteps()->max('position')) + 1,
            'exercise_id' => (int) $valide['exercise_id'],
            'titre' => $valide['titre'] ?? null,
            'consigne' => $valide['consigne'] ?? null,
            'seuil_reussite' => $valide['seuil_reussite'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Étape ajoutée au parcours.');
    }

    public function update(Request $request, Classroom $classroom, ClassroomPathStep $etape): RedirectResponse
    {
        $this->authorize('update', $classroom);
        abort_unless($etape->classroom_id === $classroom->id, 404);

        $etape->update($request->validate($this->regles()));

        return back()->with('success', 'Étape modifiée.');
    }

    /** Monter ou descendre d'un cran : on echange avec la voisine. */
    public function deplacer(Request $request, Classroom $classroom, ClassroomPathStep $etape): RedirectResponse
    {
        $this->authorize('update', $classroom);
        abort_unless($etape->classroom_id === $classroom->id, 404);

        $sens = $request->validate(['sens' => ['required', Rule::in(['haut', 'bas'])]])['sens'];

        DB::transaction(function () use ($classroom, $etape, $sens) {
            $this->renumeroter($classroom);
            $etapes = $classroom->pathSteps()->get()->values();
            $i = $etapes->search(fn ($e) => $e->id === $etape->id);
            $j = $sens === 'haut' ? $i - 1 : $i + 1;
            if ($i === false || ! $etapes->has($j)) {
                return;
            }
            $voisine = $etapes[$j];
            $ici = $etapes[$i]->position;
            $etapes[$i]->update(['position' => $voisine->position]);
            $voisine->update(['position' => $ici]);
        });

        return back();
    }

    public function destroy(Classroom $classroom, ClassroomPathStep $etape): RedirectResponse
    {
        $this->authorize('update', $classroom);
        abort_unless($etape->classroom_id === $classroom->id, 404);

        DB::transaction(function () use ($classroom, $etape) {
            $etape->delete();
            $this->renumeroter($classroom);
        });

        return back()->with('success', 'Étape retirée du parcours.');
    }

    /**
     * Copier le parcours d'une autre classe, a la suite des etapes existantes.
     * Les exercices du vivier d'un autre examen ne sont pas repris.
     */
    public function copier(Request $request, Classroom $classroom): RedirectResponse
    {
        $this->authorize('update', $classroom);

        $source = Classroom::findOrFail($request->validate(['source_id' => ['required', 'integer']])['source_id']);
        abort_unless($source->center_id === $classroom->center_id && $source->id !== $classroom->id, 404);
        $this->authorize('update', $source);

        $copiees = 0;
        $ecartees = 0;
        $doublons = 0;
        DB::transaction(function () use ($source, $classroom, $request, &$copiees, &$ecartees, &$doublons) {
            $position = (int) $classroom->pathSteps()->max('position');
            $presents = $classroom->pathSteps()->pluck('exercise_id')->map(fn ($id) => (int) $id)->all();
            foreach ($source->pathSteps()->get() as $etape) {
                // Deja dans le parcours (copie faite deux fois, exercices en
                // commun) : on ne double pas l'etape.
                if (in_array((int) $etape->exercise_id, $presents, true)) {
                    $doublons++;

                    continue;
                }
                if (! $this->parcours->peutAjouter($classroom, $etape->exercise_id)) {
                    $ecartees++;

                    continue;
                }
                $presents[] = (int) $etape->exercise_id;
                $classroom->pathSteps()->create([
                    'position' => ++$position,
                    'exercise_id' => $etape->exercise_id,
                    'titre' => $etape->titre,
                    'consigne' => $etape->consigne,
                    'seuil_reussite' => $etape->seuil_reussite,
                    'created_by' => $request->user()->id,
                ]);
                $copiees++;
            }
        });

        $message = $copiees === 0
            ? 'Aucune étape copiée.'
            : "{$copiees} étape".($copiees > 1 ? 's copiées' : ' copiée')." depuis « {$source->name} ».";
        if ($ecartees > 0) {
            $message .= " {$ecartees} écartée".($ecartees > 1 ? 's' : '').' : exercice d’un autre examen ou retiré.';
        }
        if ($doublons > 0) {
            $message .= " {$doublons} déjà dans le parcours.";
        }

        return back()->with('success', $message);
    }

    /** @return array<string, mixed> */
    private function regles(): array
    {
        return [
            'titre' => ['nullable', 'string', 'max:120'],
            'consigne' => ['nullable', 'string', 'max:2000'],
            'seuil_reussite' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /** Positions 1, 2, 3… sans trou, dans l'ordre actuel. */
    private function renumeroter(Classroom $classe): void
    {
        $classe->pathSteps()->get()->values()->each(function (ClassroomPathStep $e, int $i) {
            if ($e->position !== $i + 1) {
                $e->update(['position' => $i + 1]);
            }
        });
    }

    /** @param  array<int, int>  $dejaPris */
    private function exercice(Exercise $e, array $dejaPris): array
    {
        $competence = $e->exerciseType?->skill_type;

        return [
            'id' => $e->id,
            'titre' => (string) (($e->content['title'] ?? null) ?: ($e->exerciseType?->name ?? 'Exercice')),
            'type' => $e->exerciseType?->name ?? 'Exercice',
            'competence' => ParcoursDeClasse::COMPETENCES[$competence] ?? null,
            'niveau' => $e->difficulty,
            'deja_pris' => in_array($e->id, $dejaPris, true),
        ];
    }
}
