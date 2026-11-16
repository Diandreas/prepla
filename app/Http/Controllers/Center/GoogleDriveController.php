<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\CenterGoogleDrive;
use App\Models\CenterMedia;
use App\Models\LanguageCenter;
use App\Services\Center\GoogleDrive;
use App\Services\Center\SauvegardeDrive;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Le Google Drive du centre : connexion (responsable seulement), envoi direct
 * navigateur -> Drive, choix de fichiers deja dans le Drive (Picker),
 * sauvegarde des contenus.
 *
 * Le centre vient TOUJOURS de EnsureCenterStaff (centre courant). Sans
 * identifiants Google dans le .env, toutes ces routes repondent 404 : la
 * fonction est simplement absente.
 */
class GoogleDriveController extends Controller
{
    public function __construct(private GoogleDrive $google) {}

    /** Les types de la mediatheque et le sous-dossier Drive ou ils vont. */
    private const DOSSIERS = ['video' => 'Videos', 'audio' => 'Audios', 'document' => 'Documents', 'image' => 'Documents'];

    public function connect(Request $request): RedirectResponse
    {
        $this->responsable($request);
        $state = Str::random(40);
        $request->session()->put('google_drive_state', ['state' => $state, 'center_id' => $this->centre($request)->id]);

        return redirect()->away($this->google->urlAutorisation($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $this->responsable($request);
        $centre = $this->centre($request);
        $attendu = $request->session()->pull('google_drive_state');

        if (! is_array($attendu) || ! hash_equals((string) $attendu['state'], (string) $request->query('state'))
            || (int) $attendu['center_id'] !== $centre->id) {
            return $this->retour('Connexion à Google Drive refusée : la demande a expiré. Recommencez.', true);
        }
        if ($request->filled('error') || ! $request->filled('code')) {
            return $this->retour('Connexion à Google Drive annulée.', true);
        }

        try {
            $jetons = $this->google->echangerCode((string) $request->query('code'));
            if (! $jetons['refresh_token']) {
                return $this->retour(\App\Support\MarqueInstance::texte('Google n’a pas donné d’accès durable. Retirez PrePla des applications de votre compte Google puis recommencez.'), true);
            }
            $arbo = $this->google->creerArborescence($jetons['access_token'], $centre);
            $email = $this->google->email($jetons['access_token']);
        } catch (RuntimeException $e) {
            report($e);

            return $this->retour('Google Drive n’a pas répondu correctement. Réessayez dans un instant.', true);
        }

        CenterGoogleDrive::updateOrCreate(['center_id' => $centre->id], [
            'email' => $email,
            'refresh_token' => $jetons['refresh_token'],
            'root_folder_id' => $arbo['racine'],
            'folders' => $arbo['dossiers'],
            'connected_by' => $request->user()->id,
            'connecte_le' => now(),
        ]);

        return $this->retour(\App\Support\MarqueInstance::texte('Google Drive connecté : vos nouveaux contenus iront dans le dossier « PrePla - ').$centre->name.' ».');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $this->responsable($request);
        $drive = CenterGoogleDrive::where('center_id', $this->centre($request)->id)->first();
        if ($drive) {
            $this->google->revoquer($drive->refresh_token);
            $this->google->oublierJeton($drive);
            $drive->delete();
        }

        return $this->retour('Google Drive déconnecté. Vos fichiers restent dans votre Drive.');
    }

    /** Ouvre une session d'envoi resumable : le fichier part ensuite du navigateur vers Google. */
    public function sessionEnvoi(Request $request): JsonResponse
    {
        $drive = $this->drive($request);
        $v = $request->validate([
            'type' => 'required|in:video,audio,document,image',
            'name' => 'required|string|max:255',
            'mime' => 'required|string|max:120',
            'size' => 'required|integer|min:1',
        ]);

        try {
            $url = $this->google->sessionEnvoi($drive, self::DOSSIERS[$v['type']], $v['name'], $v['mime'], (int) $v['size'], $request->getSchemeAndHttpHost());
        } catch (RuntimeException $e) {
            report($e);

            return response()->json(['message' => 'Google Drive refuse l’envoi pour le moment. Réessayez.'], 502);
        }

        return response()->json(['upload_url' => $url]);
    }

    /** Enregistre un fichier du Drive (envoye ou choisi dans le Picker) dans la mediatheque. */
    public function enregistrer(Request $request): JsonResponse
    {
        $drive = $this->drive($request);
        $centre = $this->centre($request);
        $v = $request->validate([
            'drive_file_id' => 'required|string|max:200',
            'type' => 'nullable|in:video,audio,document,image',
            'title' => 'nullable|string|max:160',
            'source_rights' => 'required|string|max:255',
            'rights_certified' => 'accepted',
        ], [
            'source_rights.required' => 'Indiquez la source et les droits de ce fichier.',
            'rights_certified.accepted' => 'Cochez la case : vous certifiez avoir les droits de diffuser ce fichier à vos élèves.',
        ]);

        try {
            // Le serveur relit le fichier chez Google : nom, type et taille ne
            // viennent pas du navigateur. Avec drive.file, Google refuse un
            // fichier que ce centre n'a ni envoye ni choisi.
            $fichier = $this->google->fichier($drive, $v['drive_file_id']);
            $this->google->rendreLisibleParLien($drive, $fichier['id']);
        } catch (RuntimeException $e) {
            report($e);

            return response()->json(['message' => 'Ce fichier est introuvable dans votre Google Drive.'], 422);
        }

        $media = CenterMedia::updateOrCreate(
            ['center_id' => $centre->id, 'drive_file_id' => $fichier['id']],
            [
                'uploaded_by' => $request->user()->id,
                'type' => $v['type'] ?? self::typeDuMime($fichier['mimeType']),
                'provider' => 'google_drive',
                'path' => 'drive:'.$fichier['id'],
                'original_name' => $fichier['name'],
                'mime' => $fichier['mimeType'],
                'size' => $fichier['size'],
                'title' => trim((string) ($v['title'] ?? '')) ?: $fichier['name'],
                'source_rights' => trim($v['source_rights']),
                'rights_certified_at' => now(),
            ],
        );

        return response()->json($media->fiche());
    }

    /** Ce que le Picker de Google demande au navigateur : un jeton court, la cle API, l'id du projet. */
    public function picker(Request $request): JsonResponse
    {
        $drive = $this->drive($request);

        try {
            $jeton = $this->google->jeton($drive);
        } catch (RuntimeException $e) {
            report($e);

            return response()->json(['message' => 'Google Drive ne répond pas. Reconnectez-le dans les paramètres.'], 502);
        }

        return response()->json([
            'access_token' => $jeton,
            'api_key' => config('services.google_drive.api_key'),
            'app_id' => config('services.google_drive.app_id'),
        ]);
    }

    public function sauvegarder(Request $request, SauvegardeDrive $sauvegarde): RedirectResponse
    {
        $this->responsable($request);
        $drive = $this->drive($request);

        try {
            $sauvegarde->sauvegarder($drive);
        } catch (RuntimeException $e) {
            report($e);

            return $this->retour('La sauvegarde a échoué : Google Drive n’a pas répondu. Réessayez.', true);
        }

        return $this->retour('Sauvegarde déposée dans le dossier « Sauvegardes » de votre Drive.');
    }

    public static function typeDuMime(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            str_starts_with($mime, 'image/') => 'image',
            default => 'document',
        };
    }

    private function centre(Request $request): LanguageCenter
    {
        abort_unless(GoogleDrive::estConfigure(), 404);

        return $request->attributes->get('center');
    }

    private function responsable(Request $request): void
    {
        abort_unless(GoogleDrive::estConfigure(), 404);
        abort_unless($request->user()->centerRoleIs('center_admin'), 403);
    }

    private function drive(Request $request): CenterGoogleDrive
    {
        $drive = CenterGoogleDrive::where('center_id', $this->centre($request)->id)->first();
        abort_unless($drive !== null, 409, 'Google Drive n’est pas connecté pour ce centre.');

        return $drive;
    }

    private function retour(string $message, bool $erreur = false): RedirectResponse
    {
        return redirect()->route('center.parametres')->with($erreur ? 'error' : 'success', $message);
    }
}
