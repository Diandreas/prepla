<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\CenterMedia;
use App\Models\Classroom;
use App\Models\CourseBlock;
use App\Models\CourseBlockProgress;
use App\Models\CourseModule;
use App\Models\CoursePath;
use App\Models\CourseSession;
use App\Models\Exercise;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Services\Cours\BlocsDeCours;
use App\Services\Cours\CreateurDeParcours;
use App\Services\Cours\ProgressionDeCours;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le createur de parcours du centre : parcours -> modules -> seances -> blocs.
 *
 * Droits : jamais un parcours d'un autre centre (404). Un parcours rattache a
 * une classe se gere avec les droits de modification de cette classe
 * (ClassroomPolicy::update : le responsable, ou l'enseignant de la classe) ;
 * un modele (sans classe) par tout le personnel du centre.
 *
 * Les modifications de l'editeur (enregistrement automatique) repondent en
 * JSON avec l'arbre a jour.
 */
class CoursCentreController extends Controller
{
    public function __construct(
        private CreateurDeParcours $createur,
        private BlocsDeCours $blocs,
        private ProgressionDeCours $progression,
    ) {}

    public function index(Request $request): Response
    {
        $centre = $this->centre($request);
        $user = $request->user();

        $classes = $centre->classrooms()->whereNull('archived_at')->orderBy('name')->get()
            ->filter(fn (Classroom $c) => $user->can('update', $c))->values();

        $parcours = CoursePath::where('center_id', $centre->id)
            ->where(fn ($q) => $q->whereNull('classroom_id')->orWhereIn('classroom_id', $classes->pluck('id')))
            ->with('classroom:id,name')->withCount('sessions')->orderByDesc('updated_at')->get()
            ->map(fn (CoursePath $p) => [
                'id' => $p->id,
                'titre' => $p->titre,
                'statut' => $p->statut,
                'classe' => $p->classroom?->name,
                'classroom_id' => $p->classroom_id,
                'seances' => $p->sessions_count,
                'modifie_le' => $p->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('center/cours/index', [
            'parcours' => $parcours,
            'classes' => $classes->map(fn (Classroom $c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'classeChoisie' => $request->integer('classe') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $centre = $this->centre($request);
        $valide = $request->validate([
            'titre' => ['required', 'string', 'max:160'],
            'classroom_id' => ['nullable', 'integer'],
        ]);
        $classeId = $this->classeAutorisee($request, $centre, $valide['classroom_id'] ?? null);

        $parcours = DB::transaction(function () use ($centre, $valide, $classeId, $request) {
            $p = CoursePath::create([
                'center_id' => $centre->id, 'classroom_id' => $classeId, 'titre' => $valide['titre'],
                'statut' => CoursePath::BROUILLON, 'created_by' => $request->user()->id,
            ]);
            $module = $p->modules()->create(['position' => 1, 'titre' => 'Module 1']);
            $module->sessions()->create(['position' => 1, 'titre' => 'Séance 1', 'statut' => CoursePath::BROUILLON]);

            return $p;
        });

        return redirect()->route('center.cours.edit', $parcours);
    }

    public function edit(Request $request, CoursePath $parcours): Response
    {
        $centre = $this->autoriser($request, $parcours);
        $user = $request->user();

        $classes = $centre->classrooms()->whereNull('archived_at')->orderBy('name')->get()
            ->filter(fn (Classroom $c) => $user->can('update', $c))
            ->map(fn (Classroom $c) => ['id' => $c->id, 'name' => $c->name])->values();

        // Exercices choisissables : ceux du centre publies, et la banque PrePla
        // des examens du centre. Jamais un brouillon.
        $examen = $parcours->classroom?->exam_id ?? $centre->default_exam_id;
        $q = trim((string) $request->query('q', ''));
        $fiche = fn (Exercise $e) => [
            'id' => $e->id,
            'titre' => (string) (($e->content['title'] ?? null) ?: ($e->exerciseType?->name ?? 'Exercice')),
            'type' => $e->exerciseType?->name,
            'niveau' => $e->difficulty,
        ];
        $exercicesCentre = Exercise::where('center_id', $centre->id)->horsBrouillon()
            ->with('exerciseType:id,name')->latest()->limit(200)->get()->map($fiche);
        $banque = Exercise::query()->whereNull('center_id')->whereNull('lesson_id')->whereNull('node_id')->whereNull('mock_exam_id')
            ->when($examen, fn ($r) => $r->where('exam_id', $examen))
            ->when($q !== '', fn ($r) => $r->whereHas('exerciseType', fn ($t) => $t->where('name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%')))
            ->with('exerciseType:id,name')->orderByDesc('id')->limit(60)->get()->map($fiche);

        return Inertia::render('center/cours/editeur', [
            'parcours' => $this->createur->arbre($parcours),
            'classes' => $classes,
            'medias' => $this->createur->medias($centre->id),
            'exercicesCentre' => $exercicesCentre,
            'banque' => $banque,
            'types' => collect(BlocsDeCours::TYPES)->map(fn ($l, $k) => ['value' => $k, 'label' => $l])->values(),
            'liveDisponible' => Schema::hasTable('live_classes'),
            // « Creer un exercice » depuis un bloc : l'examen et le niveau de la
            // classe sont proposes au createur ; au retour, la meme seance s'ouvre.
            'examParDefaut' => $examen,
            'niveauParDefaut' => $parcours->classroom?->level,
            'seanceInitiale' => $request->integer('seance') ?: null,
        ]);
    }

    public function update(Request $request, CoursePath $parcours): JsonResponse
    {
        $centre = $this->autoriser($request, $parcours);
        $valide = $request->validate([
            'titre' => ['sometimes', 'required', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'statut' => ['sometimes', Rule::in([CoursePath::BROUILLON, CoursePath::PUBLIE])],
            'classroom_id' => ['sometimes', 'nullable', 'integer'],
        ]);
        if (array_key_exists('classroom_id', $valide)) {
            $valide['classroom_id'] = $this->classeAutorisee($request, $centre, $valide['classroom_id']);
        }
        $parcours->update($valide);

        return $this->arbreJson($parcours);
    }

    public function destroy(Request $request, CoursePath $parcours): RedirectResponse
    {
        $this->autoriser($request, $parcours);
        $parcours->delete();

        return redirect()->route('center.cours.index')->with('success', 'Parcours supprimé.');
    }

    public function dupliquer(Request $request, CoursePath $parcours): RedirectResponse
    {
        $centre = $this->autoriser($request, $parcours);
        $classeId = $this->classeAutorisee($request, $centre, $request->validate(['classroom_id' => ['nullable', 'integer']])['classroom_id'] ?? null);
        $copie = $this->createur->dupliquerParcours($parcours, $classeId, $request->user()->id);

        return redirect()->route('center.cours.edit', $copie)->with('success', 'Parcours dupliqué (en brouillon).');
    }

    public function ordre(Request $request, CoursePath $parcours): JsonResponse
    {
        $this->autoriser($request, $parcours);
        $valide = $request->validate(['modules' => ['present', 'array']]);
        $this->createur->reordonner($parcours, $valide['modules']);

        return $this->arbreJson($parcours);
    }

    // ── Modules ────────────────────────────────────────────────────────────

    public function ajouterModule(Request $request, CoursePath $parcours): JsonResponse
    {
        $this->autoriser($request, $parcours);
        $titre = $request->validate(['titre' => ['nullable', 'string', 'max:160']])['titre'] ?? null;
        $n = $parcours->modules()->count() + 1;
        $parcours->modules()->create(['position' => ((int) $parcours->modules()->max('position')) + 1, 'titre' => $titre ?: "Module {$n}"]);

        return $this->arbreJson($parcours);
    }

    public function modifierModule(Request $request, CourseModule $module): JsonResponse
    {
        $parcours = $module->path;
        $this->autoriser($request, $parcours);
        $module->update($request->validate([
            'titre' => ['sometimes', 'required', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:4000'],
        ]));

        return $this->arbreJson($parcours);
    }

    public function supprimerModule(Request $request, CourseModule $module): JsonResponse
    {
        $parcours = $module->path;
        $this->autoriser($request, $parcours);
        $module->delete();

        return $this->arbreJson($parcours);
    }

    // ── Seances ────────────────────────────────────────────────────────────

    public function ajouterSeance(Request $request, CourseModule $module): JsonResponse
    {
        $parcours = $module->path;
        $this->autoriser($request, $parcours);
        $n = $module->sessions()->count() + 1;
        $module->sessions()->create([
            'position' => ((int) $module->sessions()->max('position')) + 1,
            'titre' => $request->validate(['titre' => ['nullable', 'string', 'max:160']])['titre'] ?? "Séance {$n}",
            'statut' => CoursePath::BROUILLON,
        ]);

        return $this->arbreJson($parcours);
    }

    public function modifierSeance(Request $request, CourseSession $seance): JsonResponse
    {
        $parcours = $seance->module->path;
        $this->autoriser($request, $parcours);
        $valide = $request->validate([
            'titre' => ['sometimes', 'required', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'statut' => ['sometimes', Rule::in([CoursePath::BROUILLON, CoursePath::PUBLIE])],
            'obligatoire' => ['sometimes', 'boolean'],
            'blocs_ordre_impose' => ['sometimes', 'boolean'],
            'duree_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:600'],
            'deblocage' => ['sometimes', Rule::in(CourseSession::DEBLOCAGES)],
            'seuil_quiz' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'deblocage_at' => ['sometimes', 'nullable', 'date'],
            'debut_at' => ['sometimes', 'nullable', 'date'],
            'limite_at' => ['sometimes', 'nullable', 'date'],
        ]);
        $seance->update($valide);

        return $this->arbreJson($parcours);
    }

    public function supprimerSeance(Request $request, CourseSession $seance): JsonResponse
    {
        $parcours = $seance->module->path;
        $this->autoriser($request, $parcours);
        $seance->delete();

        return $this->arbreJson($parcours);
    }

    public function dupliquerSeance(Request $request, CourseSession $seance): JsonResponse
    {
        $parcours = $seance->module->path;
        $this->autoriser($request, $parcours);
        $this->createur->dupliquerSeance($seance);

        return $this->arbreJson($parcours);
    }

    /** « Voir comme l'eleve » : le lecteur de seance, sans rien enregistrer. */
    public function apercu(Request $request, CourseSession $seance): Response
    {
        $parcours = $seance->module->path;
        $this->autoriser($request, $parcours);
        $seance->load('blocks');
        $medias = CenterMedia::where('center_id', $parcours->center_id)->get()->keyBy('id')->all();

        return Inertia::render('cours/seance', [
            'apercu' => true,
            'parcours' => ['id' => $parcours->id, 'titre' => $parcours->titre],
            'seance' => $this->progression->decrireSeance($seance) + ['blocs_ordre_impose' => $seance->blocs_ordre_impose],
            'blocs' => $seance->blocks->map(fn ($b) => $this->blocs->pourEleve($b, $medias) + ['etat' => null])->values(),
            'retourUrl' => route('center.cours.edit', $parcours),
        ]);
    }

    // ── Blocs ──────────────────────────────────────────────────────────────

    public function ajouterBloc(Request $request, CourseSession $seance): JsonResponse
    {
        $parcours = $seance->module->path;
        $this->autoriser($request, $parcours);
        $type = $request->validate(['type' => ['required', Rule::in(array_keys(BlocsDeCours::TYPES))]])['type'];
        $seance->blocks()->create([
            'position' => ((int) $seance->blocks()->max('position')) + 1,
            'type' => $type,
            'contenu' => BlocsDeCours::vide($type),
            // Une consigne ou un lien se lisent : ils ne bloquent pas la seance.
            'obligatoire' => ! in_array($type, ['consigne', 'lien', 'live'], true),
        ]);

        return $this->arbreJson($parcours);
    }

    public function modifierBloc(Request $request, CourseBlock $bloc): JsonResponse
    {
        $parcours = $bloc->session->module->path;
        $centre = $this->autoriser($request, $parcours);
        $valide = $request->validate([
            'titre' => ['sometimes', 'nullable', 'string', 'max:160'],
            'obligatoire' => ['sometimes', 'boolean'],
            'contenu' => ['sometimes', 'array'],
        ]);
        if (array_key_exists('contenu', $valide)) {
            $valide['contenu'] = $this->blocs->normaliser($bloc->type, $valide['contenu'], $centre);
        }
        $bloc->update($valide);

        return $this->arbreJson($parcours);
    }

    public function supprimerBloc(Request $request, CourseBlock $bloc): JsonResponse
    {
        $parcours = $bloc->session->module->path;
        $this->autoriser($request, $parcours);
        $bloc->delete();

        return $this->arbreJson($parcours);
    }

    /** Video, PDF, image ou audio envoyes depuis l'editeur, dans la mediatheque du centre. */
    public function televerser(Request $request, CoursePath $parcours): JsonResponse
    {
        $centre = $this->autoriser($request, $parcours);
        $valide = $request->validate([
            'type' => ['required', Rule::in(['video', 'document', 'image', 'audio'])],
            'fichier' => ['required', 'file', match ($request->input('type')) {
                'video' => 'mimes:mp4,webm,mov,m4v|max:512000',
                'document' => 'mimes:pdf,png,jpg,jpeg,webp|max:51200',
                'image' => 'mimes:png,jpg,jpeg,webp,gif|max:'.MediaController::IMAGE_MAX_KO,
                default => 'mimes:'.MediaController::AUDIO_FORMATS.'|max:'.MediaController::AUDIO_MAX_KO,
            }],
            'titre' => ['nullable', 'string', 'max:160'],
            'droits_certifies' => ['accepted'],
        ], [
            'droits_certifies.accepted' => 'Cochez la case : vous certifiez avoir les droits de diffuser ce fichier à vos élèves.',
            'fichier.uploaded' => MediaController::messageLimiteServeur(),
        ]);
        $fichier = $request->file('fichier');
        $type = $valide['type'];
        // Un PDF ou une image envoyes comme « document » : une image reste une image.
        if ($type === 'document' && str_starts_with((string) $fichier->getMimeType(), 'image/')) {
            $type = 'image';
        }
        $chemin = "center-media/{$centre->id}/".Str::uuid().'.'.($fichier->getClientOriginalExtension() ?: $fichier->guessExtension());
        Storage::disk('public')->putFileAs(dirname($chemin), $fichier, basename($chemin));
        $media = CenterMedia::create([
            'center_id' => $centre->id, 'uploaded_by' => $request->user()->id, 'type' => $type, 'path' => $chemin,
            'original_name' => $fichier->getClientOriginalName(), 'title' => $valide['titre'] ?? null,
            'mime' => $fichier->getMimeType(), 'size' => $fichier->getSize(), 'rights_certified_at' => now(),
        ]);

        return response()->json(BlocsDeCours::ficheMedia($media) + ['nom' => $media->original_name]);
    }

    // ── Suivi enseignant ───────────────────────────────────────────────────

    public function suivi(Request $request, CoursePath $parcours): Response
    {
        $this->autoriser($request, $parcours);
        $seances = $this->progression->seances($parcours);
        $blocs = $seances->flatMap(fn ($s) => $s->blocks);
        $eleves = $parcours->classroom
            ? $parcours->classroom->students()->orderBy('name')->get(['users.id', 'users.name'])
            : collect();
        $etats = $this->progression->etatsDesBlocs($blocs, $eleves->pluck('id'));

        $lignes = $eleves->map(function (User $e) use ($seances, $etats) {
            $siens = $etats[$e->id] ?? [];

            return [
                'id' => $e->id,
                'name' => $e->name,
                'finies' => $seances->filter(fn ($s) => $this->progression->seanceFinie($s, $siens))->count(),
                'seances' => $seances->mapWithKeys(fn ($s) => [$s->id => [
                    'finie' => $this->progression->seanceFinie($s, $siens),
                    'blocs_faits' => $s->blocks->filter(fn ($b) => $siens[$b->id]['fait'] ?? false)->count(),
                    'score' => $this->progression->scoreSeance($s, $siens),
                ]]),
            ];
        })->values();

        // Productions ecrites et orales a corriger.
        $productions = CourseBlockProgress::whereIn('course_block_id', $blocs->whereIn('type', ['ecrit', 'oral'])->pluck('id'))
            ->whereIn('user_id', $eleves->pluck('id'))->whereNotNull('termine_at')
            ->with(['user:id,name', 'block:id,type,titre,course_session_id,contenu'])
            ->orderByRaw("CASE WHEN correction_statut = 'a_corriger' THEN 0 ELSE 1 END")->latest('updated_at')->get()
            ->map(fn (CourseBlockProgress $p) => [
                'id' => $p->id,
                'eleve' => $p->user?->name,
                'type' => $p->block->type,
                'seance' => $seances->firstWhere('id', $p->block->course_session_id)?->titre,
                'consigne' => $p->block->contenu['consigne'] ?? '',
                'texte' => $p->reponse['texte'] ?? null,
                'audio_url' => isset($p->reponse['audio']) ? Storage::disk('public')->url($p->reponse['audio']) : null,
                'statut' => $p->correction_statut,
                'correction_ia' => $p->correction_ia,
                'commentaire' => $p->commentaire_enseignant,
                'note' => $p->note_enseignant,
                'rendu_le' => $p->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('center/cours/suivi', [
            'parcours' => ['id' => $parcours->id, 'titre' => $parcours->titre, 'classe' => $parcours->classroom?->name],
            'seances' => $seances->map(fn ($s) => ['id' => $s->id, 'titre' => $s->titre, 'nb_blocs' => $s->blocks->count()])->values(),
            'eleves' => $lignes,
            'productions' => $productions,
        ]);
    }

    public function corriger(Request $request, CourseBlockProgress $production): RedirectResponse
    {
        $parcours = $production->block->session->module->path;
        $this->autoriser($request, $parcours);
        abort_unless(in_array($production->block->type, ['ecrit', 'oral'], true), 404);
        $valide = $request->validate([
            'commentaire' => ['nullable', 'string', 'max:5000'],
            'note' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);
        $production->update([
            'commentaire_enseignant' => $valide['commentaire'] ?? null,
            'note_enseignant' => $valide['note'] ?? null,
            'correction_statut' => 'corrige',
            'corrige_par' => $request->user()->id,
        ]);

        return back()->with('success', 'Correction enregistrée.');
    }

    // ── Outils ─────────────────────────────────────────────────────────────

    private function centre(Request $request): LanguageCenter
    {
        return $request->attributes->get('center');
    }

    /** Le parcours est-il de ce centre, et l'utilisateur peut-il le modifier ? */
    private function autoriser(Request $request, CoursePath $parcours): LanguageCenter
    {
        $centre = $this->centre($request);
        abort_unless($parcours->center_id === $centre->id, 404);
        if ($parcours->classroom_id !== null) {
            $this->authorize('update', $parcours->classroom);
        }

        return $centre;
    }

    private function classeAutorisee(Request $request, LanguageCenter $centre, $id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }
        $classe = Classroom::where('center_id', $centre->id)->whereKey((int) $id)->first();
        abort_unless($classe !== null, 404);
        $this->authorize('update', $classe);

        return $classe->id;
    }

    private function arbreJson(CoursePath $parcours): JsonResponse
    {
        $parcours->touch();

        return response()->json(['parcours' => $this->createur->arbre($parcours->fresh())]);
    }
}
