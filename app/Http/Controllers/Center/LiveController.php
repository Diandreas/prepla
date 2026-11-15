<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\CenterGoogleAccount;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\LiveSession;
use App\Notifications\LiveClassNotification;
use App\Services\Live\GoogleMeet;
use App\Services\Live\Presence;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Module « Live class », cote equipe du centre : planifier, lancer, terminer un
 * cours en direct Google Meet, puis en faire le bilan (presence, enregistrement,
 * resume, devoir, replay).
 *
 * Droits : ClassroomPolicy::update sur la classe du live (le responsable pour
 * tout le centre, l'enseignant pour ses classes), jamais un autre centre.
 * Sans identifiants OAuth Google, toutes ces routes repondent 404.
 */
class LiveController extends Controller
{
    public function __construct(private GoogleMeet $meet, private Presence $presence) {}

    private function centre(Request $request): LanguageCenter
    {
        abort_unless(GoogleMeet::configure(), 404);

        return $request->attributes->get('center');
    }

    private function compte(Request $request, LanguageCenter $centre): ?CenterGoogleAccount
    {
        return CenterGoogleAccount::where('center_id', $centre->id)->where('user_id', $request->user()->id)->first();
    }

    private function autoriser(Request $request, LiveSession $live): LanguageCenter
    {
        $centre = $this->centre($request);
        abort_unless($live->center_id === $centre->id, 404);
        $this->authorize('update', $live->classroom);

        return $centre;
    }

    public function index(Request $request): Response
    {
        $centre = $this->centre($request);
        $user = $request->user();

        $classes = $centre->classrooms()->whereNull('archived_at')->orderBy('name')->get()
            ->filter(fn (Classroom $c) => $user->can('update', $c))->values();
        $idsClasses = $classes->pluck('id');
        $filtre = (int) $request->query('classe', 0);

        $lives = LiveSession::where('center_id', $centre->id)
            ->whereIn('classroom_id', $filtre && $idsClasses->contains($filtre) ? [$filtre] : $idsClasses)
            ->with('classroom:id,name')
            ->withCount(['participants as rejoints_count' => fn ($q) => $q->whereNotNull('joined_at')])
            ->orderBy('starts_at')
            ->get();

        $fiche = fn (LiveSession $l) => $this->fiche($l);
        $statuts = $lives->groupBy(fn (LiveSession $l) => in_array($l->statut(), ['a_venir', 'en_cours'], true) ? 'avenir' : 'passes');
        $compte = $this->compte($request, $centre);

        return Inertia::render('center/lives/index', [
            'classes' => $classes->map(fn (Classroom $c) => ['id' => $c->id, 'name' => $c->name, 'eleves' => $c->students()->count()]),
            'classeChoisie' => $filtre ?: null,
            'aVenir' => $statuts->get('avenir', collect())->map($fiche)->values(),
            'passes' => $statuts->get('passes', collect())->sortByDesc('starts_at')->take(60)->map($fiche)->values(),
            'google' => [
                'connecte' => (bool) $compte,
                'email' => $compte?->google_email,
                'lectureAutorisee' => (bool) $compte?->aLeScope(GoogleMeet::SCOPE_LIRE),
            ],
            'accessTypeParDefaut' => config('services.google_meet.access_type', 'OPEN'),
        ]);
    }

    private function fiche(LiveSession $l): array
    {
        return [
            'id' => $l->id,
            'title' => $l->title,
            'agenda' => $l->agenda,
            'classe' => ['id' => $l->classroom_id, 'name' => $l->classroom?->name],
            'starts_at' => $l->starts_at->toIso8601String(),
            'duration_minutes' => $l->duration_minutes,
            'statut' => $l->statut(),
            'meeting_uri' => $l->meeting_uri,
            'serie' => $l->series_uuid !== null,
            'rejoints' => $l->rejoints_count ?? null,
            'recording_url' => $l->recording_url,
        ];
    }

    public function store(Request $request, Classroom $classroom): RedirectResponse
    {
        $centre = $this->centre($request);
        abort_unless($classroom->center_id === $centre->id, 404);
        $this->authorize('update', $classroom);

        $data = $request->validate([
            'mode' => ['required', Rule::in(['maintenant', 'planifie', 'serie'])],
            'title' => ['required', 'string', 'max:160'],
            'agenda' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['required_unless:mode,maintenant', 'nullable', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'repetitions' => ['required_if:mode,serie', 'nullable', 'integer', 'min:2', 'max:26'],
            'access_type' => ['nullable', Rule::in(['OPEN', 'TRUSTED'])],
            // Sans compte Google connecte : un lien Meet colle a la main.
            'meeting_uri' => ['nullable', 'url', 'regex:#^https://meet\.google\.com/#', 'max:255'],
        ]);

        $debut = $data['mode'] === 'maintenant' ? CarbonImmutable::now() : CarbonImmutable::parse($data['starts_at']);
        if ($data['mode'] !== 'maintenant' && $debut->isPast()) {
            return back()->withErrors(['starts_at' => 'La date doit être dans le futur.']);
        }

        $salle = ['name' => null, 'meetingUri' => $data['meeting_uri'] ?? null, 'meetingCode' => null];
        if (! $salle['meetingUri']) {
            $compte = $this->compte($request, $centre);
            if (! $compte) {
                return back()->withErrors(['google' => 'Connectez votre compte Google, ou collez un lien Meet.']);
            }
            try {
                $salle = $this->meet->creerEspace($compte, $data['access_type'] ?? config('services.google_meet.access_type', 'OPEN'));
            } catch (RuntimeException $e) {
                Log::warning('Live class : création Meet refusée', ['center' => $centre->id, 'erreur' => $e->getMessage()]);

                return back()->withErrors(['google' => $e->getMessage()]);
            }
        }

        $nombre = $data['mode'] === 'serie' ? (int) $data['repetitions'] : 1;
        $serie = $nombre > 1 ? (string) Str::uuid() : null;
        $lives = collect(range(0, $nombre - 1))->map(fn (int $i) => LiveSession::create([
            'center_id' => $centre->id,
            'classroom_id' => $classroom->id,
            'created_by' => $request->user()->id,
            'series_uuid' => $serie,
            'title' => $data['title'],
            'agenda' => $data['agenda'] ?? null,
            'starts_at' => $debut->addWeeks($i),
            'duration_minutes' => $data['duration_minutes'],
            'access_type' => $data['access_type'] ?? config('services.google_meet.access_type', 'OPEN'),
            'meet_space_name' => $salle['name'],
            'meeting_uri' => $salle['meetingUri'],
            'meeting_code' => $salle['meetingCode'],
            'started_at' => $data['mode'] === 'maintenant' ? now() : null,
        ]));

        $premier = $lives->first();
        $premier->setRelation('classroom', $classroom->loadMissing('center'));
        Notification::send($classroom->students()->get(), LiveClassNotification::pour($premier, $data['mode'] === 'maintenant', $nombre));

        if ($data['mode'] === 'maintenant') {
            return redirect()->route('live.show', $premier);
        }

        return redirect()->route('center.lives.index', ['classe' => $classroom->id])
            ->with('success', $nombre > 1 ? "Série de {$nombre} lives planifiée." : 'Live planifié.');
    }

    public function demarrer(Request $request, LiveSession $live): RedirectResponse
    {
        $this->autoriser($request, $live);
        abort_if($live->cancelled_at || $live->ended_at, 422);
        if (! $live->started_at) {
            $live->update(['started_at' => now()]);
            $live->load('classroom.center');
            Notification::send($live->classroom->students()->get(), LiveClassNotification::pour($live, true));
        }

        return redirect()->route('live.show', $live);
    }

    public function terminer(Request $request, LiveSession $live): RedirectResponse
    {
        $centre = $this->autoriser($request, $live);
        if (! $live->ended_at) {
            $live->update(['ended_at' => now(), 'started_at' => $live->started_at ?? $live->starts_at]);
        }
        $live->messages()->whereNull('answered_at')->where('kind', 'question')->update(['answered_at' => now()]);
        $live->participants()->update(['hand_raised_at' => null]);

        // Presence et enregistrement lus chez Google, si le compte l'autorise.
        $compte = $this->compte($request, $centre);
        if ($compte && $live->meet_space_name && $compte->aLeScope(GoogleMeet::SCOPE_LIRE)) {
            try {
                $bilan = $this->meet->bilan($compte, $live->meet_space_name, $live->started_at->copy()->subMinutes(30));
                $live->update([
                    'google_attendance' => $bilan['participants'],
                    'recording_url' => $live->recording_url ?? $bilan['enregistrement'],
                ]);
            } catch (\Throwable $e) {
                Log::info('Live class : bilan Google indisponible', ['live' => $live->id, 'erreur' => $e->getMessage()]);
            }
        }

        return redirect()->route('center.lives.show', $live)->with('success', 'Live terminé.');
    }

    public function annuler(Request $request, LiveSession $live): RedirectResponse
    {
        $this->autoriser($request, $live);
        abort_if($live->started_at !== null, 422);
        $live->update(['cancelled_at' => now()]);

        return back()->with('success', 'Live annulé.');
    }

    /** Le bilan d'un live : presence, interactions, et le suivi a donner. */
    public function show(Request $request, LiveSession $live): Response
    {
        $this->autoriser($request, $live);
        $live->load('classroom:id,name');

        return Inertia::render('center/lives/show', [
            'live' => $this->fiche($live) + [
                'summary' => $live->summary,
                'assignment_id' => $live->assignment_id,
                'replay_published' => $live->replay_published,
                'started_at' => $live->started_at?->toIso8601String(),
                'ended_at' => $live->ended_at?->toIso8601String(),
                'duree_reelle' => $live->started_at && $live->ended_at ? (int) $live->started_at->diffInMinutes($live->ended_at) : null,
            ],
            'presence' => $this->presence->pour($live),
            'questions' => $live->messages()->where('kind', 'question')->withCount('votes')->with('user:id,name')
                ->orderByDesc('votes_count')->get()
                ->map(fn ($m) => ['id' => $m->id, 'body' => $m->body, 'auteur' => $m->user?->name, 'votes' => $m->votes_count]),
            'sondages' => $live->polls()->with('answers')->get()->map(fn ($p) => [
                'question' => $p->question,
                'options' => $p->options,
                'correct' => $p->correct_option,
                'resultats' => collect($p->options)->keys()->map(fn ($i) => $p->answers->where('option', $i)->count()),
            ]),
            'devoirs' => Assignment::where('classroom_id', $live->classroom_id)->latest()->limit(50)->get(['id', 'title']),
        ]);
    }

    public function update(Request $request, LiveSession $live): RedirectResponse
    {
        $this->autoriser($request, $live);
        $data = $request->validate([
            'recording_url' => ['nullable', 'url', 'max:500'],
            'summary' => ['nullable', 'string', 'max:10000'],
            'assignment_id' => ['nullable', 'integer', Rule::exists('assignments', 'id')->where('classroom_id', $live->classroom_id)],
            'replay_published' => ['boolean'],
        ]);
        if (($data['replay_published'] ?? false) && empty($data['recording_url'])) {
            return back()->withErrors(['recording_url' => 'Ajoutez le lien de l’enregistrement pour publier le replay.']);
        }
        $live->update($data);

        return back()->with('success', 'Bilan enregistré.');
    }

    // --- Connexion Google (OAuth, state anti-CSRF) ---------------------------

    public function connecter(Request $request): RedirectResponse
    {
        $this->centre($request);
        $state = Str::random(40);
        $request->session()->put('live_google_state', $state);

        return redirect()->away($this->meet->urlAutorisation($state));
    }

    public function retour(Request $request): RedirectResponse
    {
        $centre = $this->centre($request);
        $attendu = (string) $request->session()->pull('live_google_state', '');
        if ($attendu === '' || ! hash_equals($attendu, (string) $request->query('state'))) {
            abort(403);
        }
        if (! $request->filled('code')) {
            return redirect()->route('center.lives.index')->with('error', 'Connexion Google annulée.');
        }

        try {
            $jetons = $this->meet->echangerCode((string) $request->query('code'));
        } catch (RuntimeException $e) {
            return redirect()->route('center.lives.index')->with('error', $e->getMessage());
        }

        $existant = $this->compte($request, $centre);
        CenterGoogleAccount::updateOrCreate(
            ['center_id' => $centre->id, 'user_id' => $request->user()->id],
            [
                'google_email' => GoogleMeet::emailDuJeton($jetons['id_token'] ?? null),
                'access_token' => $jetons['access_token'],
                // Google ne renvoie le refresh_token qu'au premier consentement.
                'refresh_token' => $jetons['refresh_token'] ?? $existant?->refresh_token,
                'expires_at' => now()->addSeconds((int) ($jetons['expires_in'] ?? 3600)),
                'scopes' => $jetons['scope'] ?? null,
            ],
        );

        return redirect()->route('center.lives.index')->with('success', 'Compte Google connecté.');
    }

    public function deconnecter(Request $request): RedirectResponse
    {
        $centre = $this->centre($request);
        $this->compte($request, $centre)?->delete();

        return back()->with('success', 'Compte Google déconnecté.');
    }
}
