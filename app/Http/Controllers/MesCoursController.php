<?php

namespace App\Http\Controllers;

use App\Models\CenterMedia;
use App\Models\CourseBlock;
use App\Models\CourseBlockProgress;
use App\Models\CoursePath;
use App\Models\CourseSession;
use App\Models\User;
use App\Services\AI\WritingCorrectorService;
use App\Services\Center\ParcoursEleveDeCentre;
use App\Services\Cours\BlocsDeCours;
use App\Services\Cours\ProgressionDeCours;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * « Mes cours » : les parcours publies par le centre pour les classes de
 * l'eleve, page du parcours (cadenas, prochaine etape) et lecteur de seance.
 *
 * Jamais un parcours brouillon, un modele, ou celui d'une autre classe ou
 * d'un autre centre (404) ; une seance fermee renvoie vers le parcours.
 */
class MesCoursController extends Controller
{
    public function __construct(
        private ParcoursEleveDeCentre $eleve,
        private ProgressionDeCours $progression,
        private BlocsDeCours $blocs,
    ) {}

    public function index(Request $request): Response
    {
        $parcours = $this->sesParcours($request->user());

        return Inertia::render('cours/index', [
            'parcours' => $parcours->map(fn (CoursePath $p) => $this->progression->pourLEleve($p, $request->user()->id))->values(),
        ]);
    }

    public function show(Request $request, CoursePath $parcours): Response
    {
        $this->autoriser($request->user(), $parcours);

        return Inertia::render('cours/parcours', [
            'parcours' => $this->progression->pourLEleve($parcours, $request->user()->id),
        ]);
    }

    public function seance(Request $request, CourseSession $seance): Response|RedirectResponse
    {
        $user = $request->user();
        $parcours = $this->autoriserSeance($user, $seance);
        if ($parcours === null) {
            return redirect()->route('cours.show', $seance->module->course_path_id)
                ->with('error', 'Cette séance n’est pas encore ouverte.');
        }

        $etats = $this->progression->etatsDesBlocs($seance->blocks, collect([$user->id]))[$user->id] ?? [];
        $progres = CourseBlockProgress::whereIn('course_block_id', $seance->blocks->pluck('id'))->where('user_id', $user->id)->get()->keyBy('course_block_id');
        $medias = CenterMedia::where('center_id', $parcours->center_id)
            ->whereIn('id', $seance->blocks->map(fn ($b) => $b->contenu['media_id'] ?? null)->filter())->get()->keyBy('id')->all();

        return Inertia::render('cours/seance', [
            'apercu' => false,
            'parcours' => ['id' => $parcours->id, 'titre' => $parcours->titre],
            'seance' => $this->progression->decrireSeance($seance) + ['blocs_ordre_impose' => $seance->blocs_ordre_impose],
            'blocs' => $seance->blocks->map(function (CourseBlock $b) use ($medias, $etats, $progres) {
                $p = $progres->get($b->id);

                return $this->blocs->pourEleve($b, $medias) + ['etat' => [
                    'fait' => $etats[$b->id]['fait'] ?? false,
                    'score' => $etats[$b->id]['score'] ?? null,
                    'ecoutes' => $p?->ecoutes ?? 0,
                    'pourcentage_vu' => $p?->pourcentage_vu ?? 0,
                    'reponse' => $b->type === 'oral' && isset($p?->reponse['audio'])
                        ? ['audio_url' => Storage::disk('public')->url($p->reponse['audio'])]
                        : ($b->type === 'ecrit' ? $p?->reponse : null),
                    'correction' => in_array($b->type, ['ecrit', 'oral'], true) && $p ? [
                        'statut' => $p->correction_statut,
                        'ia' => $p->correction_ia,
                        'commentaire' => $p->commentaire_enseignant,
                        'note' => $p->note_enseignant,
                    ] : null,
                ]];
            })->values(),
            'retourUrl' => route('cours.show', $parcours),
        ]);
    }

    /**
     * Enregistre l'avancement d'un bloc. action :
     * - terminer : texte, document, vocabulaire, lien, consigne, live, audio ;
     * - ecoute : une ecoute de plus d'un audio (refusee au-dela du maximum) ;
     * - video : pourcentage vu (fait quand il atteint le seuil) ;
     * - quiz : reponses corrigees ici (fait quand le score atteint le seuil) ;
     * - ecrit / oral : la production, qui part a la correction.
     */
    public function progression(Request $request, CourseBlock $bloc, WritingCorrectorService $correcteur): JsonResponse
    {
        $user = $request->user();
        $seance = $bloc->session;
        $parcours = $this->autoriserSeance($user, $seance);
        abort_if($parcours === null, 403, 'Cette séance n’est pas encore ouverte.');

        // Ordre impose : les blocs obligatoires precedents d'abord.
        if ($seance->blocs_ordre_impose) {
            $avant = $seance->blocks->filter(fn ($b) => $b->obligatoire && ($b->position < $bloc->position || ($b->position === $bloc->position && $b->id < $bloc->id)));
            $etats = $this->progression->etatsDesBlocs($avant, collect([$user->id]))[$user->id] ?? [];
            abort_unless($avant->every(fn ($b) => $etats[$b->id]['fait'] ?? false), 403, 'Terminez d’abord les étapes précédentes.');
        }

        $action = (string) $request->input('action', 'terminer');
        $p = CourseBlockProgress::firstOrNew(['course_block_id' => $bloc->id, 'user_id' => $user->id]);
        $c = $bloc->contenu ?? [];
        $retour = [];

        switch ($bloc->type.':'.$action) {
            case 'audio:ecoute':
                $max = $c['ecoutes_max'] ?? null;
                if ($max !== null && $p->ecoutes >= $max) {
                    throw ValidationException::withMessages(['ecoutes' => 'Nombre d’écoutes atteint.']);
                }
                $p->ecoutes = $p->ecoutes + 1;
                break;

            case 'video:video':
                $vu = max(0, min(100, $request->integer('pourcentage')));
                $p->pourcentage_vu = max($p->pourcentage_vu ?? 0, $vu);
                if ($p->pourcentage_vu >= (int) ($c['seuil_visionnage'] ?? 0)) {
                    $p->termine_at ??= now();
                }
                break;

            case 'quiz:quiz':
                $resultat = $this->blocs->corrigerQuiz($c, (array) $request->input('reponses', []));
                $p->score = max($p->score ?? 0, $resultat['score']);
                $p->reponse = ['reponses' => $request->input('reponses', [])];
                if ($resultat['score'] >= (int) ($c['seuil'] ?? 60)) {
                    $p->termine_at ??= now();
                }
                $retour = $resultat + ['reussi' => $resultat['score'] >= (int) ($c['seuil'] ?? 60)];
                break;

            case 'ecrit:ecrit':
                $texte = trim((string) $request->validate(['texte' => ['required', 'string', 'max:20000']])['texte']);
                $mots = count(preg_split('/\s+/u', $texte, -1, PREG_SPLIT_NO_EMPTY));
                if (($c['mots_min'] ?? null) && $mots < $c['mots_min']) {
                    throw ValidationException::withMessages(['texte' => "Au moins {$c['mots_min']} mots ({$mots} pour le moment)."]);
                }
                if (($c['mots_max'] ?? null) && $mots > $c['mots_max']) {
                    throw ValidationException::withMessages(['texte' => "Au plus {$c['mots_max']} mots ({$mots} pour le moment)."]);
                }
                $p->reponse = ['texte' => $texte, 'mots' => $mots];
                $p->termine_at = now();
                // La file « A corriger » de l'enseignant (suivi du parcours),
                // et une premiere correction IA quand elle repond.
                $p->correction_statut = 'a_corriger';
                try {
                    $ia = $correcteur->correct($texte, (string) ($c['consigne'] ?? ''), 'PrePla', 'Français');
                    $p->correction_ia = empty($ia['evaluation_indisponible']) ? $ia : null;
                } catch (Throwable) {
                    $p->correction_ia = null;
                }
                break;

            case 'oral:oral':
                $fichier = $request->validate(['audio' => ['required', 'file', 'max:20480', 'mimes:webm,weba,ogg,oga,mp3,mpga,m4a,mp4,wav']])['audio'];
                $chemin = "course-productions/{$parcours->center_id}/{$user->id}/".Str::uuid().'.'.($fichier->getClientOriginalExtension() ?: 'webm');
                Storage::disk('public')->putFileAs(dirname($chemin), $fichier, basename($chemin));
                if (isset($p->reponse['audio'])) {
                    Storage::disk('public')->delete($p->reponse['audio']);
                }
                $p->reponse = ['audio' => $chemin];
                $p->termine_at = now();
                $p->correction_statut = 'a_corriger';
                break;

            default:
                if ($action !== 'terminer' || in_array($bloc->type, ['quiz', 'ecrit', 'oral', 'exercice'], true)
                    || ($bloc->type === 'video' && (int) ($c['seuil_visionnage'] ?? 0) > 0 && ($c['source'] ?? '') === 'media')) {
                    abort(422, 'Action impossible pour ce bloc.');
                }
                $p->termine_at ??= now();
        }

        $p->save();

        return response()->json($retour + [
            'fait' => $p->termine_at !== null,
            'score' => $p->score,
            'ecoutes' => $p->ecoutes,
            'pourcentage_vu' => $p->pourcentage_vu,
        ]);
    }

    /** @return Collection<int, CoursePath> */
    private function sesParcours(User $user): Collection
    {
        $centre = $this->eleve->centre($user);
        if (! $centre) {
            return collect();
        }
        $classes = $this->eleve->classes($user, $centre)->pluck('id');

        return CoursePath::where('center_id', $centre->id)->where('statut', CoursePath::PUBLIE)
            ->whereIn('classroom_id', $classes)->orderBy('id')->get();
    }

    private function autoriser(User $user, CoursePath $parcours): void
    {
        abort_unless($this->sesParcours($user)->contains('id', $parcours->id), 404);
    }

    /** Le parcours si la seance est ouverte a cet eleve ; null si elle est fermee. 404 si elle ne le concerne pas. */
    private function autoriserSeance(User $user, CourseSession $seance): ?CoursePath
    {
        $parcours = $seance->module->path;
        $this->autoriser($user, $parcours);
        abort_unless($seance->estPubliee(), 404);

        $seances = $this->progression->seances($parcours);
        $etats = $this->progression->etatsDesBlocs($seances->flatMap(fn ($s) => $s->blocks), collect([$user->id]))[$user->id] ?? [];
        [$ouverte] = $this->progression->ouvertures($seances, $etats)[$seance->id] ?? [false];
        $seance->setRelation('blocks', $seances->firstWhere('id', $seance->id)?->blocks ?? $seance->blocks);

        return $ouverte ? $parcours : null;
    }
}
