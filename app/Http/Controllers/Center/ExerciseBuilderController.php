<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentItem;
use App\Models\CenterMedia;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\UserExerciseAttempt;
use App\Models\LanguageCenter;
use App\Rules\ExamenDuCentre;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\Center\FicheExercice;
use App\Services\Center\StatistiquesCentre;
use Illuminate\Support\Facades\DB;
use App\Services\Content\ExerciseSchemaRegistry;
use App\Services\Content\StructureOfficielle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ExerciseBuilderController extends Controller
{
    /** Generations IA par personne et par fenetre : de quoi travailler, pas de quoi vider le quota. */
    public const GENERATIONS_PAR_FENETRE = 8;

    public const FENETRE_SECONDES = 600;

    /** Ou revenir apres l'enregistrement : la creation d'un devoir. */
    private const RETOUR_DEVOIR = 'devoir';

    public function index(Request $request): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        // Deux requetes en tout (exercices, types et examens charges d'un coup) :
        // le titre, l'examen et la competence s'affichent sans N+1.
        $exercises = Exercise::where('center_id', $center->id)
            ->with(FicheExercice::RELATIONS)
            ->latest()
            ->get()
            ->map(fn (Exercise $e) => FicheExercice::ligne($e));

        // Combien de fois chaque exercice a ete joue, et avec quelle reussite :
        // par les eleves du centre pour le responsable, par ceux de ses classes
        // pour un enseignant. Deux requetes de plus, quel que soit le nombre
        // d'exercices.
        $actor = $request->user();
        $eleves = $actor->centerRoleIs('center_admin')
            ? DB::table('center_user')->where('center_id', $center->id)->where('role', 'student')->pluck('user_id')
            : DB::table('classroom_user as cu')
                ->join('classrooms as k', 'k.id', '=', 'cu.classroom_id')
                ->where('k.center_id', $center->id)
                ->where('cu.role_in_class', 'student')
                ->whereExists(fn ($q) => $q->from('classroom_user as p')
                    ->whereColumn('p.classroom_id', 'cu.classroom_id')
                    ->where('p.role_in_class', 'teacher')
                    ->where('p.user_id', $actor->id))
                ->distinct()
                ->pluck('cu.user_id');
        $usage = app(StatistiquesCentre::class)->parExercice(
            $exercises->pluck('id')->all(),
            $eleves->map(fn ($id) => (int) $id)->unique()->values()->all(),
        );
        $exercises = $exercises->map(fn (array $l) => $l + ($usage[$l['id']] ?? ['joue' => 0, 'eleves' => 0, 'reussite' => null]));

        return Inertia::render('center/exercises/index', [
            'exercises' => $exercises,
            'exams' => Exam::proposables($center)->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('center/exercises/builder', $this->builderProps($request));
    }

    public function edit(Request $request, Exercise $exercise): Response
    {
        $this->authorize('update', $exercise);

        return Inertia::render('center/exercises/builder', array_merge(
            $this->builderProps($request),
            [
                'exercise' => [
                    'id' => $exercise->id,
                    'exercise_type_id' => $exercise->exercise_type_id,
                    'exam_id' => $exercise->exam_id,
                    'difficulty' => $exercise->difficulty,
                    'content' => $exercise->content,
                    'questions' => $exercise->questions,
                    'is_ai_generated' => (bool) $exercise->is_ai_generated,
                ],
            ],
        ));
    }

    /**
     * Les parties officielles de l'examen a ce niveau, par type d'exercice :
     * c'est ce qui permet d'ecrire « Lesen, Teil 2 » sur la carte du type.
     */
    public function parties(Request $request, StructureOfficielle $structure): JsonResponse
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        $validated = $request->validate([
            'exam_id' => ['required', 'integer', 'exists:exams,id', new ExamenDuCentre($center)],
            'difficulty' => 'nullable|in:A1,A2,B1,B2,C1,C2',
        ]);

        $parType = [];
        foreach ($structure->parties(Exam::findOrFail($validated['exam_id']), $validated['difficulty'] ?? null) as $partie) {
            if (empty($partie['exercise_type_id'])) {
                continue;
            }
            $parType[$partie['exercise_type_id']][] = [
                'section' => $partie['section_name'] ?? null,
                'part' => $partie['part'] ?? null,
                'name' => $partie['name'] ?? null,
                'name_fr' => $partie['name_fr'] ?? null,
                'items' => $partie['items'] ?? null,
            ];
        }

        return response()->json(['parties' => (object) $parType]);
    }

    /**
     * IA-assistée : génère un brouillon éditable SANS le publier comme contenu
     * global. On réutilise le générateur existant puis on récupère uniquement
     * content+questions (l'exercice temporaire est supprimé).
     */
    public function aiDraft(Request $request, ExerciseGeneratorService $generator)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        $validated = $request->validate([
            'exercise_type_id' => 'required|exists:exercise_types,id',
            'exam_id' => ['required', 'exists:exams,id', new ExamenDuCentre($center)],
            'difficulty' => 'required|in:A1,A2,B1,B2,C1,C2',
            'theme' => 'nullable|string|max:120',
            'consignes' => 'nullable|string|max:600',
            'nombre_questions' => 'nullable|integer|min:1|max:20',
        ]);

        // Chaque generation coute un appel payant et prend de longues secondes :
        // un double clic, ou un « Regenerer » en boucle, vidait le quota du
        // centre. La limite compte par personne, pas par centre.
        $cle = 'createur-ia:'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($cle, self::GENERATIONS_PAR_FENETRE)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($cle) / 60));

            return response()->json([
                'error' => "Vous avez lancé beaucoup de générations d'affilée. Réessayez dans {$minutes} min, ou écrivez l'exercice vous-même.",
            ], 429);
        }
        RateLimiter::hit($cle, self::FENETRE_SECONDES);

        $type = ExerciseType::findOrFail($validated['exercise_type_id']);
        $exam = Exam::findOrFail($validated['exam_id']);

        try {
            $draft = $generator->generate($type, $exam, $validated['difficulty'], null, null, null, [
                'theme' => $validated['theme'] ?? null,
                'consignes' => $validated['consignes'] ?? null,
                'nombre_questions' => $validated['nombre_questions'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => "L'IA n'a pas réussi à écrire cet exercice. Réessayez, changez le thème, ou écrivez-le vous-même.",
            ], 422);
        }

        $payload = [
            'content' => $draft->content,
            'questions' => $draft->questions,
        ];

        // Discard the auto-persisted draft — the teacher will save the edited version.
        $draft->delete();

        return response()->json($payload);
    }

    public function store(Request $request)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        $this->authorize('create', Exercise::class);

        $data = $this->validatePayload($request);

        // Le devoir a completer, verifie AVANT de creer quoi que ce soit.
        $devoir = null;
        if ($request->filled('assignment_id')) {
            $devoir = Assignment::findOrFail((int) $request->input('assignment_id'));
            $this->authorize('update', $devoir);
        }

        $exercise = Exercise::create([
            'exercise_type_id' => $data['exercise_type_id'],
            'exam_id' => $data['exam_id'],
            'center_id' => $center->id,
            'creator_id' => $request->user()->id,
            'content' => $data['content'],
            'questions' => $data['questions'],
            'difficulty' => $data['difficulty'],
            'is_ai_generated' => $data['is_ai_generated'],
        ]);

        // Cree depuis la page d'un devoir : il y entre aussitot.
        if ($devoir) {
            $devoir->items()->create([
                'itemable_type' => Exercise::class,
                'itemable_id' => $exercise->id,
                'sort_order' => (int) $devoir->items()->max('sort_order') + 1,
            ]);

            return redirect()->route('center.assignments.show', $devoir->id)
                ->with('success', 'Exercice créé et ajouté au devoir.');
        }

        // « Enregistrer et donner en devoir », ou cree depuis la creation d'un
        // devoir : on y retourne, l'exercice deja coche.
        if ($request->input('retour') === self::RETOUR_DEVOIR) {
            return redirect()->route('center.assignments.create', ['exercice' => $exercise->id])
                ->with('success', 'Exercice créé. Il est déjà coché dans le devoir.');
        }

        return redirect()->route('center.exercises.index')
            ->with('success', 'Exercice créé.');
    }

    public function update(Request $request, Exercise $exercise)
    {
        $this->authorize('update', $exercise);
        $data = $this->validatePayload($request, $exercise);

        $exercise->update([
            'exercise_type_id' => $data['exercise_type_id'],
            'exam_id' => $data['exam_id'],
            'content' => $data['content'],
            'questions' => $data['questions'],
            'difficulty' => $data['difficulty'],
        ]);

        if ($request->input('retour') === self::RETOUR_DEVOIR) {
            return redirect()->route('center.assignments.create', ['exercice' => $exercise->id])
                ->with('success', 'Exercice mis à jour. Il est déjà coché dans le devoir.');
        }

        return redirect()->route('center.exercises.index')
            ->with('success', 'Exercice mis à jour.');
    }

    // ───────────────────────────── helpers ─────────────────────────────

    protected function builderProps(Request $request): array
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        $devoir = null;
        if ($request->filled('assignment_id')) {
            $assignment = Assignment::with('classroom')->find((int) $request->query('assignment_id'));
            if ($assignment && $request->user()->can('update', $assignment)) {
                $devoir = [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'classroom_id' => $assignment->classroom_id,
                ];
            }
        }

        return [
            // exam_id (résolu via la section) est indispensable au front pour filtrer
            // les types par l'examen choisi — chaque examen a ses PROPRES lignes
            // ExerciseType (une par section). Le slug donne le nom francais exact
            // de la tache (« matching-headings » n'est pas « matching »).
            'exerciseTypes' => ExerciseType::whereIn(
                'component_key',
                array_keys(ExerciseSchemaRegistry::all())
            )->with('section:id,exam_id,skill_type')
                ->get(['id', 'name', 'slug', 'component_key', 'skill_type', 'section_id'])
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'slug' => $t->slug,
                    'component_key' => $t->component_key,
                    'skill_type' => $t->skill_type ?? $t->section?->skill_type,
                    'exam_id' => $t->section?->exam_id,
                ]),
            // Seulement les examens que le centre propose (personnalisation), avec
            // la langue : l'apercu lit l'exercice avec la bonne voix.
            'exams' => Exam::proposables($center)->with('language:id,slug')->get(['id', 'name', 'language_id'])
                ->map(fn (Exam $e) => ['id' => $e->id, 'name' => $e->name, 'language' => $e->language?->slug]),
            'defaultExamId' => $center->default_exam_id,
            // La classe visee, facultative : elle pre-remplit examen et niveau.
            'classrooms' => $center->classrooms()->whereNull('archived_at')
                ->get(['id', 'name', 'exam_id', 'level']),
            'retour' => $request->query('retour') === self::RETOUR_DEVOIR ? self::RETOUR_DEVOIR : null,
            'devoir' => $devoir,
            'limiteIa' => [
                'par_fenetre' => self::GENERATIONS_PAR_FENETRE,
                'minutes' => intdiv(self::FENETRE_SECONDES, 60),
            ],
        ];
    }

    /** @return array{exercise_type_id:int,exam_id:int,difficulty:string,content:array,questions:array,is_ai_generated:bool} */
    protected function validatePayload(Request $request, ?Exercise $existant = null): array
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        // L'examen doit etre propose par le centre. A l'edition, l'examen deja
        // choisi reste accepte : un centre qui retire un examen de son offre
        // doit pouvoir corriger une faute dans un ancien exercice.
        $validated = $request->validate([
            'exercise_type_id' => 'required|exists:exercise_types,id',
            'exam_id' => ['required', 'exists:exams,id', new ExamenDuCentre($center, $existant?->exam_id)],
            'difficulty' => 'required|in:A1,A2,B1,B2,C1,C2',
            'content' => 'nullable|array',
            'questions' => 'required|array|min:1',
            'is_ai_generated' => 'boolean',
        ]);

        $type = ExerciseType::findOrFail($validated['exercise_type_id']);

        // Validate the authored questions against the type's family. Une erreur
        // de validation, pas un abort : le createur l'affiche a cote du bouton
        // au lieu d'une page d'erreur qui ferait perdre la saisie.
        [$ok, $error, $questions] = ExerciseSchemaRegistry::validateQuestions(
            $type->component_key,
            $validated['questions']
        );
        if (! $ok) {
            throw ValidationException::withMessages(['questions' => $error]);
        }

        // Media safety: any image_url/audio_url referenced must belong to THIS
        // center's media library (never an arbitrary external URL).
        $this->assertMediaBelongsToCenter($center, $validated['content'] ?? [], $questions);

        return [
            'exercise_type_id' => (int) $validated['exercise_type_id'],
            'exam_id' => (int) $validated['exam_id'],
            'difficulty' => $validated['difficulty'],
            'content' => $validated['content'] ?? [],
            'questions' => $questions,
            'is_ai_generated' => (bool) ($validated['is_ai_generated'] ?? false),
        ];
    }

    protected function assertMediaBelongsToCenter(LanguageCenter $center, array $content, array $questions): void
    {
        $urls = [];
        foreach (['image_url', 'audio_url'] as $k) {
            if (! empty($content[$k])) {
                $urls[] = $content[$k];
            }
        }
        foreach ($questions as $q) {
            foreach (['image_url', 'audio_url'] as $k) {
                if (! empty($q[$k])) {
                    $urls[] = $q[$k];
                }
            }
        }
        if (empty($urls)) {
            return;
        }

        $ownedUrls = CenterMedia::where('center_id', $center->id)
            ->get()
            ->map(fn (CenterMedia $m) => $m->url)
            ->all();

        foreach ($urls as $url) {
            abort_if(! in_array($url, $ownedUrls, true), 422,
                "Un média référencé n'appartient pas à votre centre.");
        }
    }
    /**
     * Retire un exercice du centre.
     *
     * Un enseignant pouvait créer et modifier ses exercices, mais jamais en retirer
     * un : un brouillon raté restait dans sa liste pour toujours. On refuse en
     * revanche d'effacer un exercice déjà donné en devoir ou déjà travaillé — cela
     * viderait le travail de ses élèves et l'historique de leurs tentatives.
     */
    public function destroy(Request $request, Exercise $exercise)
    {
        $this->authorize('delete', $exercise);

        $usedInAssignment = AssignmentItem::where('itemable_type', Exercise::class)
            ->where('itemable_id', $exercise->id)
            ->exists();

        if ($usedInAssignment) {
            return back()->with('error', "Cet exercice est utilisé dans un devoir : il ne peut pas être supprimé.");
        }

        if (UserExerciseAttempt::where('exercise_id', $exercise->id)->exists()) {
            return back()->with('error', "Des élèves ont déjà travaillé sur cet exercice : il est conservé pour leur historique.");
        }

        $exercise->delete();

        return redirect()->route('center.exercises.index')->with('success', 'Exercice supprimé.');
    }
}
