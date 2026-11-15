<?php

namespace App\Services\Live;

use App\Models\CenterGoogleAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Le client minimal de l'API Google Meet REST v2 (et de l'OAuth Google).
 *
 * https://developers.google.com/workspace/meet/api/guides/overview
 * - spaces.create : POST https://meet.googleapis.com/v2/spaces
 * - conferenceRecords.list (filtre space.name) puis .participants.list
 * - conferenceRecords.recordings.list : le fichier Drive de l'enregistrement
 *
 * Aucun flux video ne passe par PrePla.
 */
class GoogleMeet
{
    public const SCOPE_CREER = 'https://www.googleapis.com/auth/meetings.space.created';

    public const SCOPE_LIRE = 'https://www.googleapis.com/auth/meetings.space.readonly';

    private const API = 'https://meet.googleapis.com/v2';

    private const TOKEN = 'https://oauth2.googleapis.com/token';

    /** Sans identifiants OAuth, le module Live class est masque. */
    public static function configure(): bool
    {
        return filled(config('services.google_meet.client_id')) && filled(config('services.google_meet.client_secret'));
    }

    public function urlAutorisation(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google_meet.client_id'),
            'redirect_uri' => config('services.google_meet.redirect'),
            'response_type' => 'code',
            'scope' => implode(' ', ['openid', 'email', self::SCOPE_CREER, self::SCOPE_LIRE]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    /** @return array{access_token:string, refresh_token?:string, expires_in?:int, scope?:string, id_token?:string} */
    public function echangerCode(string $code): array
    {
        $r = Http::asForm()->post(self::TOKEN, [
            'code' => $code,
            'client_id' => config('services.google_meet.client_id'),
            'client_secret' => config('services.google_meet.client_secret'),
            'redirect_uri' => config('services.google_meet.redirect'),
            'grant_type' => 'authorization_code',
        ]);
        if (! $r->successful() || ! $r->json('access_token')) {
            throw new RuntimeException('Échange du code Google refusé.');
        }

        return $r->json();
    }

    /** L'adresse e-mail lue dans l'id_token (sans en verifier la signature : affichage seulement). */
    public static function emailDuJeton(?string $idToken): ?string
    {
        $parties = explode('.', (string) $idToken);
        if (count($parties) < 2) {
            return null;
        }
        $donnees = json_decode(base64_decode(strtr($parties[1], '-_', '+/')), true);

        return is_array($donnees) ? ($donnees['email'] ?? null) : null;
    }

    /** Un jeton d'acces valide, renouvele avec le refresh_token s'il expire. */
    public function jeton(CenterGoogleAccount $compte): string
    {
        if ($compte->expires_at && $compte->expires_at->isAfter(now()->addMinute())) {
            return $compte->access_token;
        }
        if (! $compte->refresh_token) {
            return $compte->access_token;
        }

        $r = Http::asForm()->post(self::TOKEN, [
            'client_id' => config('services.google_meet.client_id'),
            'client_secret' => config('services.google_meet.client_secret'),
            'refresh_token' => $compte->refresh_token,
            'grant_type' => 'refresh_token',
        ]);
        if (! $r->successful() || ! $r->json('access_token')) {
            throw new RuntimeException('Le compte Google doit être reconnecté.');
        }
        $compte->forceFill([
            'access_token' => $r->json('access_token'),
            'expires_at' => now()->addSeconds((int) $r->json('expires_in', 3600)),
        ])->save();

        return $compte->access_token;
    }

    /**
     * spaces.create : une salle Meet. accessType OPEN (le lien suffit) ou
     * TRUSTED (domaine Workspace + invites ; les autres demandent a entrer).
     *
     * @return array{name:string, meetingUri:string, meetingCode:?string}
     */
    public function creerEspace(CenterGoogleAccount $compte, string $accessType = 'OPEN'): array
    {
        $r = Http::withToken($this->jeton($compte))->acceptJson()
            ->post(self::API.'/spaces', ['config' => ['accessType' => $accessType]]);
        if (! $r->successful() || ! $r->json('meetingUri')) {
            throw new RuntimeException('Google Meet a refusé la création de la salle ('.$r->status().').');
        }

        return [
            'name' => (string) $r->json('name'),
            'meetingUri' => (string) $r->json('meetingUri'),
            'meetingCode' => $r->json('meetingCode'),
        ];
    }

    /**
     * Apres le cours : les participants et les enregistrements des conferences
     * tenues dans cet espace depuis $depuis. Demande le scope readonly.
     *
     * @return array{participants: list<array{nom:string, debut:?string, fin:?string}>, enregistrement:?string}
     */
    public function bilan(CenterGoogleAccount $compte, string $espace, \DateTimeInterface $depuis): array
    {
        $jeton = $this->jeton($compte);
        $filtre = sprintf('space.name = "%s" AND start_time >= "%s"', $espace, $depuis->format('Y-m-d\TH:i:s\Z'));
        $records = Http::withToken($jeton)->acceptJson()
            ->get(self::API.'/conferenceRecords', ['filter' => $filtre])
            ->throw()->json('conferenceRecords', []);

        $participants = [];
        $enregistrement = null;
        foreach ($records as $record) {
            $nomRecord = $record['name'] ?? null;
            if (! $nomRecord) {
                continue;
            }
            $liste = Http::withToken($jeton)->acceptJson()
                ->get(self::API.'/'.$nomRecord.'/participants', ['pageSize' => 250])
                ->json('participants', []);
            foreach ($liste as $p) {
                $nom = $p['signedinUser']['displayName'] ?? $p['anonymousUser']['displayName'] ?? $p['phoneUser']['displayName'] ?? null;
                if ($nom) {
                    $participants[] = ['nom' => $nom, 'debut' => $p['earliestStartTime'] ?? null, 'fin' => $p['latestEndTime'] ?? null];
                }
            }
            $enr = Http::withToken($jeton)->acceptJson()->get(self::API.'/'.$nomRecord.'/recordings')->json('recordings', []);
            foreach ($enr as $e) {
                $enregistrement ??= $e['driveDestination']['exportUri'] ?? null;
            }
        }

        return ['participants' => $participants, 'enregistrement' => $enregistrement];
    }
}
