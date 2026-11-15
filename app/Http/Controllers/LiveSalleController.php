<?php

namespace App\Http\Controllers;

use App\Models\CenterMedia;
use App\Models\Classroom;
use App\Models\Exercise;
use App\Models\LiveMessage;
use App\Models\LiveParticipant;
use App\Models\LivePoll;
use App\Models\LivePollAnswer;
use App\Models\LiveQuestionVote;
use App\Models\LiveSession;
use App\Models\User;
use App\Services\Live\GoogleMeet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * La salle PrePla d'un live : ce qui accompagne la video Google Meet.
 *
 * Main levee, questions votees, reactions rapides, sondages et quiz eclair,
 * ressources partagees, chat de la seance, notes personnelles. La page relit
 * l'etat par un sondage HTTP court (GET etat, toutes les 3 s environ) : pas de
 * websocket, le petit serveur PrePla ne porte que du JSON leger.
 *
 * Acces : les eleves de la classe du live, et l'equipe qui peut modifier cette
 * classe (ClassroomPolicy::update). Tout autre compte recoit 404.
 */
class LiveSalleController extends Controller
{
    /** @return 'animateur'|'eleve' */
    private function role(Request $request, LiveSession $live): string
    {
        abort_unless(GoogleMeet::configure(), 404);
        $user = $request->user();
        $classe = $live->classroom;
        abort_if(! $classe || $classe->center_id !== $live->center_id, 404);

        if ($user->can('update', $classe)) {
            return 'animateur';
        }
        abort_unless($classe->archived_at === null && $classe->students()->whereKey($user->id)->exists(), 404);

        return 'eleve';
    }

    private function animateur(Request $request, LiveSession $live): void
    {
        abort_unless($this->role($request, $live) === 'animateur', 403);
    }

    private function ouvert(LiveSession $live): void
    {
        abort_if($live->cancelled_at || $live->ended_at, 422, 'Ce live est terminé.');
    }

    private function participant(LiveSession $live, User $user): LiveParticipant
    {
        return LiveParticipant::firstOrCreate(['live_session_id' => $live->id, 'user_id' => $user->id]);
    }

    public function show(Request $request, LiveSession $live): InertiaResponse
    {
        $role = $this->role($request, $live);
        $live->load('classroom:id,name,center_id');
        $notes = $role === 'eleve'
            ? LiveParticipant::where('live_session_id', $live->id)->where('user_id', $request->user()->id)->value('notes')
            : null;

        $exercices = $role === 'animateur'
            ? Exercise::where('center_id', $live->center_id)->horsBrouillon()->with('exerciseType:id,name')->latest()->limit(100)->get()
                ->map(fn (Exercise $e) => ['id' => $e->id, 'titre' => ($e->exerciseType?->name ?? 'Exercice').' #'.$e->id])
            : [];
        $medias = $role === 'animateur'
            ? CenterMedia::where('center_id', $live->center_id)->latest()->limit(100)->get()
                ->map(fn (CenterMedia $m) => ['id' => $m->id, 'titre' => $m->title ?: $m->original_name, 'type' => $m->type])
            : [];

        return Inertia::render('live/salle', [
            'live' => [
                'id' => $live->id,
                'title' => $live->title,
                'agenda' => $live->agenda,
                'classe' => $live->classroom->name,
                'starts_at' => $live->starts_at->toIso8601String(),
                'duration_minutes' => $live->duration_minutes,
                'statut' => $live->statut(),
                'summary' => $live->summary,
                'recording_url' => $live->replay_published || $role === 'animateur' ? $live->recording_url : null,
                'assignment_id' => $live->assignment_id,
            ],
            'role' => $role,
            'notes' => $notes,
            'exercices' => $exercices,
            'medias' => $medias,
        ]);
    }

    /** L'etat de la salle, relu en boucle par la page. ?depuis = dernier id de chat connu. */
    public function etat(Request $request, LiveSession $live): JsonResponse
    {
        $role = $this->role($request, $live);
        $user = $request->user();
        $depuis = max(0, (int) $request->query('depuis', 0));
        $statut = $live->statut();

        if ($role === 'eleve' && $statut === 'en_cours') {
            $this->participant($live, $user)->forceFill(['last_seen_at' => now()])->save();
        }

        $chat = LiveMessage::where('live_session_id', $live->id)->where('kind', 'chat')->where('id', '>', $depuis)
            ->with('user:id,name')->orderBy('id')->limit(100)->get()
            ->map(fn (LiveMessage $m) => ['id' => $m->id, 'auteur' => $m->user?->name, 'moi' => $m->user_id === $user->id, 'body' => $m->body, 'at' => $m->created_at->toIso8601String()]);

        $mesVotes = LiveQuestionVote::where('user_id', $user->id)
            ->whereIn('live_message_id', LiveMessage::where('live_session_id', $live->id)->where('kind', 'question')->select('id'))
            ->pluck('live_message_id')->all();
        $questions = LiveMessage::where('live_session_id', $live->id)->where('kind', 'question')
            ->withCount('votes')->with('user:id,name')
            ->orderByRaw('answered_at is not null')->orderByDesc('votes_count')->orderBy('id')->limit(100)->get()
            ->map(fn (LiveMessage $m) => [
                'id' => $m->id,
                'body' => $m->body,
                'votes' => $m->votes_count,
                'repondue' => $m->answered_at !== null,
                'aVote' => in_array($m->id, $mesVotes, true),
                'moi' => $m->user_id === $user->id,
                // Le nom de l'auteur seulement pour l'enseignant : les eleves osent mieux.
                'auteur' => $role === 'animateur' ? $m->user?->name : null,
            ]);

        $ressources = LiveMessage::where('live_session_id', $live->id)->where('kind', 'resource')->orderByDesc('id')->limit(50)->get()
            ->map(fn (LiveMessage $m) => ['id' => $m->id, 'titre' => $m->body] + (array) $m->payload);

        $sondage = LivePoll::where('live_session_id', $live->id)->latest('id')->first();
        $sondageFiche = null;
        if ($sondage) {
            $reponses = LivePollAnswer::where('live_poll_id', $sondage->id)->get(['user_id', 'option']);
            $maReponse = $reponses->firstWhere('user_id', $user->id)?->option;
            $voirResultats = $role === 'animateur' || $maReponse !== null || $sondage->closed_at;
            $sondageFiche = [
                'id' => $sondage->id,
                'question' => $sondage->question,
                'options' => $sondage->options,
                'ferme' => $sondage->closed_at !== null,
                'maReponse' => $maReponse,
                'quiz' => $sondage->correct_option !== null,
                'correct' => $role === 'animateur' || $sondage->closed_at ? $sondage->correct_option : null,
                'resultats' => $voirResultats ? collect($sondage->options)->keys()->map(fn ($i) => $reponses->where('option', $i)->count())->all() : null,
                'total' => $reponses->count(),
            ];
        }

        $moi = LiveParticipant::where('live_session_id', $live->id)->where('user_id', $user->id)->first();
        $etat = [
            'statut' => $statut,
            'starts_at' => $live->starts_at->toIso8601String(),
            'meeting' => $statut === 'en_cours' || $role === 'animateur',
            'chat' => $chat,
            'questions' => $questions,
            'ressources' => $ressources,
            'sondage' => $sondageFiche,
            'maMain' => $moi?->hand_raised_at !== null,
        ];

        if ($role === 'animateur') {
            $etat['mains'] = LiveParticipant::where('live_session_id', $live->id)->whereNotNull('hand_raised_at')
                ->with('user:id,name')->orderBy('hand_raised_at')->get()
                ->map(fn ($p) => ['user_id' => $p->user_id, 'name' => $p->user?->name, 'depuis' => $p->hand_raised_at->toIso8601String()]);
            $reactions = LiveMessage::where('live_session_id', $live->id)->where('kind', 'reaction')
                ->where('created_at', '>=', now()->subMinutes(3))->get(['body']);
            $etat['reactions'] = collect(LiveMessage::REACTIONS)->mapWithKeys(fn ($r) => [$r => $reactions->where('body', $r)->count()]);
            $etat['connectes'] = LiveParticipant::where('live_session_id', $live->id)->where('last_seen_at', '>=', now()->subSeconds(30))->count();
            $etat['rejoints'] = LiveParticipant::where('live_session_id', $live->id)->whereNotNull('joined_at')->count();
        }

        return response()->json($etat);
    }

    /** « Rejoindre » : on note la presence PrePla, puis on ouvre Google Meet. */
    public function rejoindre(Request $request, LiveSession $live): RedirectResponse
    {
        $role = $this->role($request, $live);
        abort_unless($live->meeting_uri, 404);
        if ($role === 'eleve') {
            abort_unless($live->statut(now()->addMinutes(10)) === 'en_cours', 422, 'Le live n’a pas encore commencé.');
            $p = $this->participant($live, $request->user());
            $p->forceFill(['joined_at' => $p->joined_at ?? now(), 'last_seen_at' => now()])->save();
        }

        return redirect()->away($live->meeting_uri);
    }

    public function main(Request $request, LiveSession $live): JsonResponse
    {
        abort_unless($this->role($request, $live) === 'eleve', 403);
        $this->ouvert($live);
        $p = $this->participant($live, $request->user());
        $p->forceFill(['hand_raised_at' => $p->hand_raised_at ? null : now()])->save();

        return response()->json(['maMain' => $p->hand_raised_at !== null]);
    }

    public function message(Request $request, LiveSession $live): JsonResponse
    {
        $role = $this->role($request, $live);
        $this->ouvert($live);
        $data = $request->validate([
            'kind' => ['required', Rule::in($role === 'eleve' ? ['chat', 'question', 'reaction'] : ['chat'])],
            'body' => ['required', 'string', 'max:1000'],
        ]);
        if ($data['kind'] === 'reaction') {
            $request->validate(['body' => [Rule::in(LiveMessage::REACTIONS)]]);
        }
        $m = LiveMessage::create(['live_session_id' => $live->id, 'user_id' => $request->user()->id] + $data);

        return response()->json(['id' => $m->id], 201);
    }

    public function voter(Request $request, LiveSession $live, LiveMessage $message): JsonResponse
    {
        abort_unless($this->role($request, $live) === 'eleve', 403);
        abort_unless($message->live_session_id === $live->id && $message->kind === 'question', 404);
        $this->ouvert($live);
        $vote = LiveQuestionVote::where('live_message_id', $message->id)->where('user_id', $request->user()->id)->first();
        $vote ? $vote->delete() : LiveQuestionVote::create(['live_message_id' => $message->id, 'user_id' => $request->user()->id]);

        return response()->json(['aVote' => ! $vote]);
    }

    public function repondre(Request $request, LiveSession $live, LivePoll $sondage): JsonResponse
    {
        abort_unless($this->role($request, $live) === 'eleve', 403);
        abort_unless($sondage->live_session_id === $live->id, 404);
        abort_if($sondage->closed_at !== null, 422, 'Sondage fermé.');
        $data = $request->validate(['option' => ['required', 'integer', 'min:0', 'max:'.(count($sondage->options) - 1)]]);
        LivePollAnswer::updateOrCreate(['live_poll_id' => $sondage->id, 'user_id' => $request->user()->id], ['option' => $data['option']]);

        return response()->json(['ok' => true]);
    }

    public function notes(Request $request, LiveSession $live): JsonResponse
    {
        abort_unless($this->role($request, $live) === 'eleve', 403);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:20000']]);
        $this->participant($live, $request->user())->update(['notes' => $data['notes']]);

        return response()->json(['ok' => true]);
    }

    // --- Actions de l'enseignant ----------------------------------------------

    public function ressource(Request $request, LiveSession $live): JsonResponse
    {
        $this->animateur($request, $live);
        $this->ouvert($live);
        $data = $request->validate([
            'type' => ['required', Rule::in(['lien', 'document', 'exercice'])],
            'url' => ['required_if:type,lien', 'nullable', 'url', 'max:500'],
            'titre' => ['nullable', 'string', 'max:200'],
            'id' => ['required_unless:type,lien', 'nullable', 'integer'],
        ]);

        [$titre, $url] = match ($data['type']) {
            'lien' => [($data['titre'] ?? null) ?: $data['url'], $data['url']],
            'document' => $this->document($live, (int) $data['id']),
            'exercice' => $this->exercice($live, (int) $data['id']),
        };
        $m = LiveMessage::create([
            'live_session_id' => $live->id,
            'user_id' => $request->user()->id,
            'kind' => 'resource',
            'body' => ($data['titre'] ?? null) ?: $titre,
            'payload' => ['type' => $data['type'], 'url' => $url],
        ]);

        return response()->json(['id' => $m->id], 201);
    }

    /** Un document de la mediatheque DU CENTRE du live (fichier local ou Google Drive). */
    private function document(LiveSession $live, int $id): array
    {
        $media = CenterMedia::where('center_id', $live->center_id)->findOrFail($id);
        $url = $media->url;
        if (Schema::hasColumn('center_media', 'provider') && $media->getAttribute('provider') === 'google_drive' && $media->getAttribute('drive_file_id')) {
            $url = 'https://drive.google.com/file/d/'.$media->getAttribute('drive_file_id').'/view';
        }

        return [$media->title ?: $media->original_name, $url];
    }

    /** Un exercice publie DU CENTRE du live (jamais un brouillon). */
    private function exercice(LiveSession $live, int $id): array
    {
        $e = Exercise::where('center_id', $live->center_id)->horsBrouillon()->with('exerciseType:id,name')->findOrFail($id);

        return [($e->exerciseType?->name ?? 'Exercice').' #'.$e->id, route('exercise.show', $e)];
    }

    public function sondage(Request $request, LiveSession $live): JsonResponse
    {
        $this->animateur($request, $live);
        $this->ouvert($live);
        $data = $request->validate([
            'question' => ['required', 'string', 'max:300'],
            'options' => ['required', 'array', 'min:2', 'max:6'],
            'options.*' => ['required', 'string', 'max:120'],
            'correct' => ['nullable', 'integer', 'min:0'],
        ]);
        abort_if(isset($data['correct']) && $data['correct'] >= count($data['options']), 422);
        // Un seul sondage ouvert a la fois : lancer le suivant ferme le precedent.
        LivePoll::where('live_session_id', $live->id)->whereNull('closed_at')->update(['closed_at' => now()]);
        $p = LivePoll::create([
            'live_session_id' => $live->id,
            'question' => $data['question'],
            'options' => array_values($data['options']),
            'correct_option' => $data['correct'] ?? null,
        ]);

        return response()->json(['id' => $p->id], 201);
    }

    public function fermerSondage(Request $request, LiveSession $live, LivePoll $sondage): JsonResponse
    {
        $this->animateur($request, $live);
        abort_unless($sondage->live_session_id === $live->id, 404);
        $sondage->update(['closed_at' => $sondage->closed_at ?? now()]);

        return response()->json(['ok' => true]);
    }

    public function questionRepondue(Request $request, LiveSession $live, LiveMessage $message): JsonResponse
    {
        $this->animateur($request, $live);
        abort_unless($message->live_session_id === $live->id && $message->kind === 'question', 404);
        $message->update(['answered_at' => $message->answered_at ? null : now()]);

        return response()->json(['ok' => true]);
    }

    public function baisserMain(Request $request, LiveSession $live, User $user): JsonResponse
    {
        $this->animateur($request, $live);
        LiveParticipant::where('live_session_id', $live->id)->where('user_id', $user->id)->update(['hand_raised_at' => null]);

        return response()->json(['ok' => true]);
    }

    // --- Cote eleve : bandeau et calendrier -----------------------------------

    /** Les lives a venir (14 jours) et en cours des classes de l'eleve. */
    public function prochains(Request $request): JsonResponse
    {
        if (! GoogleMeet::configure()) {
            return response()->json(['lives' => []]);
        }
        $user = $request->user();
        $classes = Classroom::query()
            ->join('classroom_user', 'classroom_user.classroom_id', '=', 'classrooms.id')
            ->where('classroom_user.user_id', $user->id)->where('classroom_user.role_in_class', 'student')
            ->whereNull('classrooms.archived_at')
            ->pluck('classrooms.id');

        $lives = LiveSession::ouverts()->whereIn('classroom_id', $classes)
            ->where('starts_at', '<=', now()->addDays(14))
            ->where('starts_at', '>=', now()->subHours(8))
            ->with('classroom:id,name')->orderBy('starts_at')->limit(10)->get()
            ->filter(fn (LiveSession $l) => in_array($l->statut(), ['a_venir', 'en_cours'], true))
            ->map(fn (LiveSession $l) => [
                'id' => $l->id,
                'title' => $l->title,
                'classe' => $l->classroom?->name,
                'starts_at' => $l->starts_at->toIso8601String(),
                'duration_minutes' => $l->duration_minutes,
                'statut' => $l->statut(),
            ])->values();

        return response()->json(['lives' => $lives]);
    }

    /**
     * Ouvrir le replay publie : l'eleve est compte comme l'ayant vu (etape
     * « replay » du parcours), puis part vers le lien d'enregistrement.
     */
    public function replay(Request $request, LiveSession $live): RedirectResponse
    {
        $role = $this->role($request, $live);
        $lien = trim((string) $live->recording_url);
        abort_if($lien === '' || ($role === 'eleve' && (! $live->replay_published || $live->cancelled_at)), 404);

        if ($role === 'eleve') {
            $p = $this->participant($live, $request->user());
            $p->replay_vu_at ??= now();
            $p->save();
        }

        return redirect()->away($lien);
    }

    public function ics(Request $request, LiveSession $live): Response
    {
        $this->role($request, $live);
        $echapper = fn (?string $t) => str_replace(["\\", ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], (string) $t);
        $fmt = fn ($d) => $d->copy()->utc()->format('Ymd\THis\Z');
        $lien = route('live.show', $live);
        $lignes = [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//PrePla//Live class//FR', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:live-'.$live->id.'@'.parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.$fmt(now()),
            'DTSTART:'.$fmt($live->starts_at),
            'DTEND:'.$fmt($live->finPrevue()),
            'SUMMARY:'.$echapper($live->title),
            'DESCRIPTION:'.$echapper(trim(($live->agenda ? $live->agenda."\n\n" : '').$lien)),
            'URL:'.$lien,
            'END:VEVENT', 'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lignes)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="live-'.$live->id.'.ics"',
        ]);
    }
}
