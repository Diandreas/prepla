<?php

namespace App\Http\Controllers;

use App\Models\CenterGoogleDrive;
use App\Models\CenterMedia;
use App\Services\Center\GoogleDrive;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lecture d'un media range dans le Google Drive d'un centre.
 *
 * Seul un membre du centre (eleve, enseignant, responsable) y accede : un
 * eleve d'un autre centre recoit 404, comme si le fichier n'existait pas.
 *
 * Choix de lecture :
 * - documents (PDF...) : redirection vers la page de lecture de Google Drive ;
 * - audio, video, image : relais en flux, avec l'en-tete Range. La redirection
 *   vers un lien Drive public ne marche pas dans <audio>/<video> : Google
 *   renvoie une page HTML (avertissement antivirus au-dela de ~100 Mo, ou
 *   connexion), sans Range, donc pas d'avance rapide ni de lecture sur iPhone.
 *   Le relais lit le Drive avec le jeton du centre et renvoie les octets au fur
 *   et a mesure : rien n'est ecrit sur le disque du serveur.
 */
class MediaDriveController extends Controller
{
    public function __invoke(Request $request, CenterMedia $medium, GoogleDrive $google): Response
    {
        abort_unless($medium->provider === 'google_drive' && $medium->drive_file_id, 404);
        $membre = $request->user()->centers()->where('language_centers.id', $medium->center_id)->exists();
        abort_unless($membre || $request->user()->isSuperAdmin(), 404);

        if ($medium->type === 'document') {
            return redirect()->away('https://drive.google.com/file/d/'.rawurlencode($medium->drive_file_id).'/view');
        }

        $drive = CenterGoogleDrive::where('center_id', $medium->center_id)->first();
        abort_unless($drive !== null && GoogleDrive::estConfigure(), 404);

        try {
            $reponse = $google->lire($drive, $medium->drive_file_id, $request->header('Range'));
        } catch (RuntimeException $e) {
            report($e);
            abort(502);
        }
        abort_if($reponse->failed() && $reponse->status() !== 416, 404);

        $entetes = array_filter([
            'Content-Type' => $medium->mime ?: $reponse->header('Content-Type'),
            'Content-Length' => $reponse->header('Content-Length'),
            'Content-Range' => $reponse->header('Content-Range'),
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=3600',
        ]);

        $corps = $reponse->toPsrResponse()->getBody();

        return new StreamedResponse(function () use ($corps) {
            while (! $corps->eof()) {
                echo $corps->read(256 * 1024);
                flush();
            }
        }, $reponse->status(), $entetes);
    }
}
