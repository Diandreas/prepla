<?php

namespace App\Services\Center;

use App\Models\CenterGoogleDrive;
use App\Models\LanguageCenter;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Le dialogue avec Google Drive, par la facade Http de Laravel (pas de SDK :
 * une douzaine d'appels REST ne justifient pas google/apiclient et son poids).
 *
 * Scope drive.file seulement : l'application ne voit que les fichiers qu'elle
 * a crees ou que le centre a choisis dans le Picker. Aucun scope restreint,
 * donc pas d'audit de securite Google a passer.
 */
class GoogleDrive
{
    public const SCOPES = 'openid email https://www.googleapis.com/auth/drive.file';

    public const FOLDER_MIME = 'application/vnd.google-apps.folder';

    private const AUTH = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN = 'https://oauth2.googleapis.com/token';

    private const REVOKE = 'https://oauth2.googleapis.com/revoke';

    private const USERINFO = 'https://openidconnect.googleapis.com/v1/userinfo';

    private const FILES = 'https://www.googleapis.com/drive/v3/files';

    private const UPLOAD = 'https://www.googleapis.com/upload/drive/v3/files';

    /** Sans identifiants Google dans le .env, la fonction est masquee partout. */
    public static function estConfigure(): bool
    {
        return filled(config('services.google_drive.client_id')) && filled(config('services.google_drive.client_secret'));
    }

    public function redirection(): string
    {
        return route('center.google.callback');
    }

    public function urlAutorisation(string $state): string
    {
        return self::AUTH.'?'.http_build_query([
            'client_id' => config('services.google_drive.client_id'),
            'redirect_uri' => $this->redirection(),
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    /** @return array{access_token: string, refresh_token: ?string} */
    public function echangerCode(string $code): array
    {
        $r = Http::asForm()->post(self::TOKEN, [
            'code' => $code,
            'client_id' => config('services.google_drive.client_id'),
            'client_secret' => config('services.google_drive.client_secret'),
            'redirect_uri' => $this->redirection(),
            'grant_type' => 'authorization_code',
        ]);
        $this->verifier($r, 'échange du code');

        return [
            'access_token' => (string) $r->json('access_token'),
            'refresh_token' => $r->json('refresh_token'),
        ];
    }

    public function email(string $accessToken): ?string
    {
        $r = Http::withToken($accessToken)->get(self::USERINFO);

        return $r->successful() ? $r->json('email') : null;
    }

    /** Un jeton d'acces frais pour ce centre, garde en cache 50 minutes. */
    public function jeton(CenterGoogleDrive $drive): string
    {
        return Cache::remember($this->cleJeton($drive), 3000, function () use ($drive) {
            $r = Http::asForm()->post(self::TOKEN, [
                'client_id' => config('services.google_drive.client_id'),
                'client_secret' => config('services.google_drive.client_secret'),
                'refresh_token' => $drive->refresh_token,
                'grant_type' => 'refresh_token',
            ]);
            $this->verifier($r, 'rafraîchissement du jeton');

            return (string) $r->json('access_token');
        });
    }

    public function oublierJeton(CenterGoogleDrive $drive): void
    {
        Cache::forget($this->cleJeton($drive));
    }

    private function cleJeton(CenterGoogleDrive $drive): string
    {
        return "google-drive-jeton-{$drive->center_id}-{$drive->id}-".($drive->connecte_le?->timestamp ?? 0);
    }

    public function revoquer(string $token): void
    {
        // Sans consequence si Google repond mal : le jeton est efface chez nous quand meme.
        Http::asForm()->post(self::REVOKE, ['token' => $token]);
    }

    public function creerDossier(string $token, string $nom, ?string $parent = null): string
    {
        $r = Http::withToken($token)->post(self::FILES.'?fields=id', array_filter([
            'name' => $nom,
            'mimeType' => self::FOLDER_MIME,
            'parents' => $parent ? [$parent] : null,
        ]));
        $this->verifier($r, 'création du dossier');

        return (string) $r->json('id');
    }

    /** « PrePla - <centre> » et ses sous-dossiers Videos, Audios, Documents, Sauvegardes. */
    public function creerArborescence(string $token, LanguageCenter $centre): array
    {
        $racine = $this->creerDossier($token, 'PrePla - '.$centre->name);
        $dossiers = [];
        foreach (CenterGoogleDrive::SOUS_DOSSIERS as $nom) {
            $dossiers[$nom] = $this->creerDossier($token, $nom, $racine);
        }

        return ['racine' => $racine, 'dossiers' => $dossiers];
    }

    /**
     * Ouvre une session d'envoi resumable dans le dossier voulu et renvoie son
     * adresse. Le navigateur y envoie ensuite le fichier par morceaux, sans
     * passer par PrePla. L'en-tete Origin compte : Google n'accepte ensuite les
     * morceaux du navigateur (CORS) que depuis cette origine.
     */
    public function sessionEnvoi(CenterGoogleDrive $drive, string $dossier, string $nom, string $mime, int $taille, string $origine): string
    {
        $r = Http::withToken($this->jeton($drive))
            ->withHeaders([
                'X-Upload-Content-Type' => $mime,
                'X-Upload-Content-Length' => (string) $taille,
                'Origin' => $origine,
            ])
            ->post(self::UPLOAD.'?uploadType=resumable&fields=id,name,mimeType,size', [
                'name' => $nom,
                'parents' => [$drive->dossier($dossier)],
            ]);
        $this->verifier($r, 'ouverture de l’envoi');
        $url = $r->header('Location');
        if (! $url) {
            throw new RuntimeException('Google Drive n’a pas renvoyé d’adresse d’envoi.');
        }

        return $url;
    }

    /** @return array{id: string, name: string, mimeType: string, size: int} */
    public function fichier(CenterGoogleDrive $drive, string $id): array
    {
        $r = Http::withToken($this->jeton($drive))->get(self::FILES.'/'.rawurlencode($id), ['fields' => 'id,name,mimeType,size']);
        $this->verifier($r, 'lecture du fichier');

        return [
            'id' => (string) $r->json('id'),
            'name' => (string) $r->json('name'),
            'mimeType' => (string) $r->json('mimeType'),
            'size' => (int) $r->json('size', 0),
        ];
    }

    /** Lecture par lien : toute personne qui a le lien peut lire le fichier. */
    public function rendreLisibleParLien(CenterGoogleDrive $drive, string $id): void
    {
        $r = Http::withToken($this->jeton($drive))->post(self::FILES.'/'.rawurlencode($id).'/permissions', [
            'type' => 'anyone',
            'role' => 'reader',
        ]);
        $this->verifier($r, 'partage par lien');
    }

    /** Depose un fichier JSON (petit : envoi multipart en une fois). */
    public function deposerJson(CenterGoogleDrive $drive, string $dossier, string $nom, string $json): string
    {
        $limite = 'prepla'.bin2hex(random_bytes(8));
        $meta = json_encode(['name' => $nom, 'mimeType' => 'application/json', 'parents' => [$drive->dossier($dossier)]]);
        $corps = "--{$limite}\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n{$meta}\r\n"
            ."--{$limite}\r\nContent-Type: application/json\r\n\r\n{$json}\r\n--{$limite}--";

        $r = Http::withToken($this->jeton($drive))
            ->withBody($corps, "multipart/related; boundary={$limite}")
            ->post(self::UPLOAD.'?uploadType=multipart&fields=id');
        $this->verifier($r, 'dépôt de la sauvegarde');

        return (string) $r->json('id');
    }

    /** Le contenu du fichier, en flux, avec la plage demandee par le lecteur. */
    public function lire(CenterGoogleDrive $drive, string $id, ?string $range = null): Response
    {
        return Http::withToken($this->jeton($drive))
            ->withHeaders(array_filter(['Range' => $range]))
            ->withOptions(['stream' => true])
            ->get(self::FILES.'/'.rawurlencode($id).'?alt=media');
    }

    private function verifier(Response $r, string $etape): void
    {
        if ($r->failed()) {
            throw new RuntimeException("Google Drive a refusé l’étape « {$etape} » (HTTP {$r->status()}).");
        }
    }
}
