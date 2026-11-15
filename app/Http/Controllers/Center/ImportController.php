<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\LanguageCenter;
use App\Services\Center\Import\ImportEleves;
use App\Services\Center\Import\ImportQcm;
use App\Services\Center\Import\LecteurFichier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Import en masse cote centre : eleves (CSV) et QCM (CSV ou JSON).
 *
 * Deux temps : « apercu » analyse le fichier sans rien ecrire, puis
 * l'import renvoie le meme fichier et applique les lignes valides. Rien n'est
 * garde entre les deux : l'import ne fait jamais confiance a un apercu envoye
 * par le navigateur.
 *
 * Isolation : le centre vient du middleware (EnsureCenterStaff), les classes
 * et les examens sont cherches dans CE centre uniquement.
 */
class ImportController extends Controller
{
    /** Taille maximale d'un fichier, en kilo-octets. */
    public const TAILLE_MAX_KO = 512;

    public function page(Request $request): Response
    {
        $centre = $this->centre($request);
        $user = $request->user();

        return Inertia::render('center/import', [
            'classes' => Classroom::where('center_id', $centre->id)->whereNull('archived_at')->orderBy('name')->get()
                ->filter(fn (Classroom $c) => $user->can('update', $c))
                ->map(fn (Classroom $c) => $c->name)->values(),
            'examens' => Exam::orderBy('name')->get(['id', 'slug', 'name'])
                ->filter(fn (Exam $e) => $centre->proposeExamen($e->id))
                ->map(fn (Exam $e) => ['slug' => $e->slug, 'name' => $e->name])->values(),
            'peutImporterExercices' => $user->can('create', Exercise::class),
            'limites' => [
                'taille_ko' => self::TAILLE_MAX_KO,
                'eleves' => ImportEleves::MAX_LIGNES,
                'exercices' => ImportQcm::MAX_LIGNES,
            ],
            'places' => $centre->seatsAvailable(),
        ]);
    }

    public function modeleEleves(): StreamedResponse
    {
        return $this->csv('modele-eleves.csv', [
            ['nom', 'email', 'telephone', 'classe'],
            ['Awa Diallo', 'awa.diallo@exemple.com', '+33 6 12 34 56 78', 'B1 du soir'],
            ['Karl Weber', 'karl.weber@exemple.com', '', 'B1 du soir'],
        ]);
    }

    public function modeleExercices(Request $request): StreamedResponse
    {
        // L'exemple porte un examen que le centre propose vraiment.
        $centre = $this->centre($request);
        $slug = Exam::orderBy('name')->get(['id', 'slug'])->first(fn (Exam $e) => $centre->proposeExamen($e->id))?->slug ?? 'delf';

        return $this->csv('modele-qcm.csv', [
            ['question', 'options', 'bonne_reponse', 'competence', 'niveau', 'examen', 'explication'],
            ['Où se trouve Anna ?', 'Au bureau | À la maison | Au cinéma', 'A', 'reading', 'A2', $slug, 'Elle travaille.'],
            ['Quel temps fait-il demain ?', 'Il pleut | Il neige | Il fait beau', 'Il fait beau', 'listening', 'A2', $slug, ''],
        ]);
    }

    public function apercuEleves(Request $request, ImportEleves $import): JsonResponse
    {
        $lignes = $import->analyser($this->centre($request), $request->user(), $this->lire($request, ImportEleves::MAX_LIGNES, false));

        return response()->json(['lignes' => $lignes, 'resume' => $this->resume($lignes)]);
    }

    public function importerEleves(Request $request, ImportEleves $import): JsonResponse
    {
        $centre = $this->centre($request);
        $analyse = $import->analyser($centre, $request->user(), $this->lire($request, ImportEleves::MAX_LIGNES, false));

        return response()->json(['bilan' => $import->importer($centre, $analyse), 'lignes' => $analyse]);
    }

    public function apercuExercices(Request $request, ImportQcm $import): JsonResponse
    {
        $this->authorize('create', Exercise::class);
        $lignes = $import->analyser($this->centre($request), $this->lire($request, ImportQcm::MAX_LIGNES, true));

        return response()->json(['lignes' => $lignes, 'resume' => $this->resume($lignes)]);
    }

    public function importerExercices(Request $request, ImportQcm $import): JsonResponse
    {
        $this->authorize('create', Exercise::class);
        $centre = $this->centre($request);
        $analyse = $import->analyser($centre, $this->lire($request, ImportQcm::MAX_LIGNES, true));

        return response()->json(['bilan' => $import->importer($centre, $request->user(), $analyse), 'lignes' => $analyse]);
    }

    // ───────────────────────────── helpers ─────────────────────────────

    private function centre(Request $request): LanguageCenter
    {
        return $request->attributes->get('center');
    }

    /** @return list<array<string, mixed>> */
    private function lire(Request $request, int $maxLignes, bool $json): array
    {
        $request->validate([
            'fichier' => ['required', 'file', 'max:'.self::TAILLE_MAX_KO, 'extensions:'.($json ? 'csv,txt,json' : 'csv,txt')],
        ], [
            'fichier.max' => 'Le fichier dépasse '.self::TAILLE_MAX_KO.' Ko.',
            'fichier.extensions' => $json ? 'Envoyez un fichier .csv ou .json.' : 'Envoyez un fichier .csv.',
        ]);

        /** @var UploadedFile $fichier */
        $fichier = $request->file('fichier');

        return LecteurFichier::lignes($fichier, $maxLignes, $json);
    }

    /** @return array<string, int> Le nombre de lignes par statut. */
    private function resume(array $lignes): array
    {
        return collect($lignes)->countBy('statut')->all();
    }

    /** @param  list<list<string>>  $lignes */
    private function csv(string $nom, array $lignes): StreamedResponse
    {
        return response()->streamDownload(function () use ($lignes) {
            $out = fopen('php://output', 'w');
            // Marque UTF-8 : Excel ouvre les accents correctement.
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($lignes as $l) {
                fputcsv($out, $l, ';', '"', '');
            }
            fclose($out);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
