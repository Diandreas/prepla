<?php

namespace App\Services\Live;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Le client minimal de l'API REST Daily.co (fournisseur video par defaut du
 * module Live class). https://docs.daily.co/reference/rest-api
 *
 * - POST /rooms : une salle privee par live (ou par serie), nom imprevisible ;
 * - POST /meeting-tokens : un jeton court par personne, delivre par PrePla
 *   seulement aux membres de la classe et a l'equipe qui l'anime ;
 * - GET /meetings?room= : la presence reelle apres le cours ;
 * - GET /recordings?room_name= puis /recordings/{id}/access-link : le replay ;
 * - DELETE /rooms/{nom} : a l'annulation.
 *
 * La cle (services.daily.api_key) ne quitte jamais le serveur.
 */
class DailyCo
{
    private const API = 'https://api.daily.co/v1';

    public static function configure(): bool
    {
        return filled(config('services.daily.api_key'));
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(self::API)->withToken((string) config('services.daily.api_key'))->acceptJson()->timeout(15);
    }

    /** @return array{name:string, url:string} */
    public function creerSalle(CarbonInterface $debut, CarbonInterface $fin): array
    {
        $proprietes = [
            'nbf' => $debut->copy()->subMinutes(15)->getTimestamp(),
            'exp' => $fin->copy()->addMinutes(60)->getTimestamp(),
            'enable_chat' => false, // le chat est celui de PrePla
            'enable_screenshare' => true,
            'enable_knocking' => false,
            'enable_prejoin_ui' => true,
            'eject_at_room_exp' => true,
            'max_participants' => max(2, (int) config('services.daily.max_participants', 60)),
        ];
        if (config('services.daily.enregistrement')) {
            $proprietes['enable_recording'] = 'cloud';
        }

        $r = $this->http()->post('/rooms', [
            'name' => 'prepla-'.Str::lower(Str::random(24)),
            'privacy' => 'private',
            'properties' => $proprietes,
        ]);
        if (! $r->successful() || ! $r->json('name') || ! $r->json('url')) {
            throw new RuntimeException('Création de la salle vidéo refusée par Daily ('.$r->status().').');
        }

        return ['name' => (string) $r->json('name'), 'url' => (string) $r->json('url')];
    }

    public function jeton(string $salle, int $userId, string $nom, bool $proprietaire, CarbonInterface $expiration): string
    {
        $proprietes = [
            'room_name' => $salle,
            'user_name' => Str::limit($nom, 60, ''),
            'user_id' => (string) $userId,
            'is_owner' => $proprietaire,
            'exp' => $expiration->getTimestamp(),
            'eject_at_token_exp' => false,
        ];
        if ($proprietaire && config('services.daily.enregistrement')) {
            $proprietes['enable_recording'] = 'cloud';
        }
        $r = $this->http()->post('/meeting-tokens', ['properties' => $proprietes]);
        if (! $r->successful() || ! $r->json('token')) {
            throw new RuntimeException('Jeton Daily refusé ('.$r->status().').');
        }

        return (string) $r->json('token');
    }

    public function supprimerSalle(string $salle): void
    {
        $r = $this->http()->delete('/rooms/'.rawurlencode($salle));
        if (! $r->successful() && $r->status() !== 404) {
            throw new RuntimeException('Suppression de la salle Daily refusée ('.$r->status().').');
        }
    }

    /**
     * Les passages dans la salle (format commun avec Google : nom, debut, fin).
     *
     * @return list<array{nom:string, debut:?string, fin:?string, user_id:?string}>
     */
    public function presence(string $salle): array
    {
        $r = $this->http()->get('/meetings', ['room' => $salle, 'limit' => 100]);
        if (! $r->successful()) {
            throw new RuntimeException('Présence Daily illisible ('.$r->status().').');
        }

        $passages = [];
        foreach ((array) $r->json('data', []) as $reunion) {
            foreach ((array) ($reunion['participants'] ?? []) as $p) {
                $debut = isset($p['join_time']) ? (int) $p['join_time'] : null;
                $passages[] = [
                    'nom' => (string) ($p['user_name'] ?? ''),
                    'user_id' => isset($p['user_id']) ? (string) $p['user_id'] : null,
                    'debut' => $debut ? date(DATE_ATOM, $debut) : null,
                    'fin' => $debut && isset($p['duration']) ? date(DATE_ATOM, $debut + (int) $p['duration']) : null,
                ];
            }
        }

        return $passages;
    }

    /** Le lien de telechargement du dernier enregistrement cloud termine, s'il y en a un. */
    public function enregistrement(string $salle): ?string
    {
        $r = $this->http()->get('/recordings', ['room_name' => $salle, 'limit' => 10]);
        if (! $r->successful()) {
            return null;
        }
        $fini = collect((array) $r->json('data', []))->first(fn ($e) => ($e['status'] ?? '') === 'finished');
        if (! $fini || empty($fini['id'])) {
            return null;
        }
        $lien = $this->http()->get('/recordings/'.rawurlencode((string) $fini['id']).'/access-link');

        return $lien->successful() ? ($lien->json('download_link') ?: null) : null;
    }
}
