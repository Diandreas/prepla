<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\CenterMedia;
use App\Models\LanguageCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    /** Taille maximale d'un audio, en Ko : une piste d'examen complete tient dans 100 Mo. */
    public const AUDIO_MAX_KO = 102400;

    /** Taille maximale d'une image, en Ko. */
    public const IMAGE_MAX_KO = 5120;

    /**
     * Formats audio acceptes. mp4/m4a/aac viennent des iPhone et des logiciels
     * de montage, webm/ogg des navigateurs ; mpga/adts/oga/weba sont les extensions
     * que PHP devine parfois pour ces memes fichiers.
     */
    public const AUDIO_FORMATS = 'mp3,mpga,m4a,mp4,aac,adts,wav,ogg,oga,webm,weba';

    /**
     * La limite reelle du serveur, en octets : la plus petite de
     * upload_max_filesize et post_max_size. Si elle est sous 100 Mo, PHP
     * refuse le fichier avant Laravel ; on le dit clairement au centre.
     */
    public static function limiteServeurOctets(): int
    {
        $tailles = array_filter([
            self::enOctets((string) ini_get('upload_max_filesize')),
            self::enOctets((string) ini_get('post_max_size')),
        ], fn (int $o) => $o > 0);

        return $tailles === [] ? PHP_INT_MAX : min($tailles);
    }

    /** « 110M », « 2G », « 512K » ou un nombre d'octets. */
    public static function enOctets(string $valeur): int
    {
        $valeur = trim($valeur);
        if ($valeur === '') {
            return 0;
        }
        $nombre = (int) $valeur;

        return match (strtolower(substr($valeur, -1))) {
            'g' => $nombre * 1024 ** 3,
            'm' => $nombre * 1024 ** 2,
            'k' => $nombre * 1024,
            default => $nombre,
        };
    }

    /** Message affiche quand le fichier depasse la limite du serveur. */
    public static function messageLimiteServeur(): string
    {
        $mo = intdiv(self::limiteServeurOctets(), 1024 * 1024);

        return "Ce fichier dépasse la limite actuelle du serveur ({$mo} Mo). Compressez-le (MP3 en 128 kbit/s par exemple) ou contactez PrePla.";
    }

    public function index(Request $request)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        $query = CenterMedia::where('center_id', $center->id)->latest();
        if (in_array($request->query('type'), ['image', 'audio'], true)) {
            $query->where('type', $request->query('type'));
        }

        return response()->json([
            'media' => $query->get()->map(fn (CenterMedia $m) => $m->fiche()),
        ]);
    }

    /** La page de la mediatheque : les audios du centre, leur nom et leurs droits. */
    public function page(Request $request): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        return Inertia::render('center/media', [
            'media' => CenterMedia::where('center_id', $center->id)->latest()->get()
                ->map(fn (CenterMedia $m) => $m->fiche()),
            'limites' => ['audio_mo' => intdiv(self::AUDIO_MAX_KO, 1024), 'image_mo' => intdiv(self::IMAGE_MAX_KO, 1024),
                'serveur_mo' => self::limiteServeurOctets() === PHP_INT_MAX ? null : intdiv(self::limiteServeurOctets(), 1024 * 1024)],
        ]);
    }

    public function store(Request $request)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        // Un audio d'examen peut etre un sujet sous licence : le centre dit d'ou
        // il vient et certifie pouvoir le diffuser, sinon on refuse l'envoi.
        $validated = $request->validate([
            'type' => 'required|in:image,audio',
            'image' => 'required_if:type,image|image|mimes:jpeg,jpg,png,webp|max:'.self::IMAGE_MAX_KO,
            'audio' => 'required_if:type,audio|file|mimes:'.self::AUDIO_FORMATS.'|max:'.self::AUDIO_MAX_KO,
            'title' => 'nullable|string|max:160',
            'source_rights' => 'required_if:type,audio|nullable|string|max:255',
            'rights_certified' => 'required_if:type,audio|accepted_if:type,audio',
        ], [
            'audio.uploaded' => self::messageLimiteServeur(),
            'image.uploaded' => self::messageLimiteServeur(),
            'source_rights.required_if' => 'Indiquez la source et les droits de ce fichier.',
            'rights_certified.required_if' => 'Cochez la case : vous certifiez avoir les droits de diffuser ce fichier à vos élèves.',
            'rights_certified.accepted_if' => 'Cochez la case : vous certifiez avoir les droits de diffuser ce fichier à vos élèves.',
        ]);

        $file = $request->file($validated['type']);
        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension();
        // Unique name per upload (no deterministic hash -> no accidental overwrite).
        $path = "center-media/{$center->id}/".Str::uuid().'.'.$ext;

        Storage::disk('public')->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        $audio = $validated['type'] === 'audio';
        $media = CenterMedia::create([
            'center_id' => $center->id,
            'uploaded_by' => $request->user()->id,
            'type' => $validated['type'],
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'title' => trim((string) ($validated['title'] ?? '')) ?: null,
            'source_rights' => $audio ? trim((string) $validated['source_rights']) : ($validated['source_rights'] ?? null),
            'rights_certified_at' => $audio ? now() : null,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json($media->fiche());
    }

    /** Renommer une piste, ou corriger sa source et ses droits. */
    public function update(Request $request, CenterMedia $medium)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        abort_unless($medium->center_id === $center->id, 403);

        $validated = $request->validate([
            'title' => 'required|string|max:160',
            'source_rights' => ($medium->type === 'audio' ? 'required' : 'nullable').'|string|max:255',
        ], [
            'source_rights.required' => 'Indiquez la source et les droits de ce fichier.',
        ]);

        $medium->update([
            'title' => trim($validated['title']),
            'source_rights' => isset($validated['source_rights']) ? trim($validated['source_rights']) : $medium->source_rights,
        ]);

        return response()->json($medium->fiche());
    }

    public function destroy(Request $request, CenterMedia $medium)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        abort_unless($medium->center_id === $center->id, 403);

        Storage::disk('public')->delete($medium->path);
        $medium->delete();

        return response()->json(['ok' => true]);
    }
}
