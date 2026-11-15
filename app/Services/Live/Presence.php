<?php

namespace App\Services\Live;

use App\Models\LiveMessage;
use App\Models\LivePollAnswer;
use App\Models\LiveSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * La presence a un live, eleve par eleve.
 *
 * Source Google (conferenceRecords/participants) si elle a pu etre lue : on
 * rapproche le nom affiche dans Meet du nom de l'eleve. Sinon, presence PrePla :
 * a clique « Rejoindre », ou a interagi (chat, question, reaction, sondage).
 */
class Presence
{
    /** @return list<array{id:int, name:string, present:bool, source:?string, minutes:?int, rejoint_a:?string, interactions:int}> */
    public function pour(LiveSession $live): array
    {
        $eleves = $live->classroom->students()->orderBy('name')->get(['users.id', 'users.name']);
        $participants = $live->participants()->get()->keyBy('user_id');
        $messages = LiveMessage::where('live_session_id', $live->id)->whereNotNull('user_id')
            ->selectRaw('user_id, count(*) as n')->groupBy('user_id')->pluck('n', 'user_id');
        $reponses = LivePollAnswer::whereIn('live_poll_id', $live->polls()->pluck('id'))
            ->selectRaw('user_id, count(*) as n')->groupBy('user_id')->pluck('n', 'user_id');

        $google = collect($live->google_attendance ?? [])->groupBy(fn ($p) => Str::lower(Str::ascii(trim((string) $p['nom']))));

        return $eleves->map(function (User $e) use ($participants, $messages, $reponses, $google) {
            $p = $participants->get($e->id);
            $interactions = (int) ($messages[$e->id] ?? 0) + (int) ($reponses[$e->id] ?? 0);
            $chezGoogle = $google->get(Str::lower(Str::ascii(trim($e->name))));
            $minutes = null;
            if ($chezGoogle) {
                $minutes = (int) $chezGoogle->sum(fn ($s) => $s['debut'] && $s['fin']
                    ? Carbon::parse($s['debut'])->diffInMinutes(Carbon::parse($s['fin'])) : 0);
            }
            $source = $chezGoogle ? 'google' : (($p?->joined_at || $interactions > 0) ? 'prepla' : null);

            return [
                'id' => $e->id,
                'name' => $e->name,
                'present' => $source !== null,
                'source' => $source,
                'minutes' => $minutes,
                'rejoint_a' => $p?->joined_at?->toIso8601String(),
                'interactions' => $interactions,
            ];
        })->values()->all();
    }
}
