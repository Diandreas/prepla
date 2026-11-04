<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CenterMonthlyActivity;
use App\Models\LanguageCenter;
use App\Services\Center\ElevesActifs;
use App\Services\Center\PeriodesAppartenance;
use App\Support\ExportTableur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Les releves mensuels des centres, pour le super administrateur seul
 * (routes/admin-releves.php, groupe admin.* : EnsureSuperAdmin).
 *
 * Une page a onglets. Onglet « Eleves actifs » (lot E) : par mois, tous les
 * centres ; ou par centre, tous ses mois. Le mois en cours est calcule a la
 * volee et marque « en cours » ; un mois termine affiche ce qu'a arrete la
 * commande centres:actifs, et rien d'autre.
 */
class ReleveController extends Controller
{
    public function __construct(private readonly ElevesActifs $actifs) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/releves', [
            'onglet' => 'actifs',
            'actifs' => $this->donneesActifs($request),
        ]);
    }

    public function actifsCsv(Request $request): StreamedResponse
    {
        $donnees = $this->donneesActifs($request);
        $suffixe = $donnees['centre'] !== null
            ? (LanguageCenter::find($donnees['centre'])?->slug ?? 'centre')
            : $donnees['mois'];

        return response()->streamDownload(function () use ($donnees) {
            $sortie = fopen('php://output', 'w');
            // Le BOM fait ouvrir le fichier en UTF-8 par Excel.
            fwrite($sortie, "\xEF\xBB\xBF");
            fputcsv($sortie, ['Centre', 'Identifiant', 'Mois', 'Élèves actifs', 'Palier', 'Calculé le', 'État'], ';');
            foreach ($donnees['lignes'] as $l) {
                fputcsv($sortie, ExportTableur::neutraliser([
                    $l['centre'],
                    $l['slug'],
                    $l['periode'],
                    $l['actifs'] ?? '',
                    $l['palier'] ?? '',
                    $l['computed_at'] ?? '',
                    $l['en_cours'] ? 'en cours' : ($l['actifs'] === null ? 'non calculé' : 'arrêté'),
                ]), ';');
            }
            fclose($sortie);
        }, "releve-actifs-{$suffixe}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Arrete le releve d'un mois termine (meme regle que la commande). */
    public function actifsCalculer(Request $request, PeriodesAppartenance $periodes): RedirectResponse
    {
        $valide = $request->validate([
            'mois' => ['required', 'string', 'regex:/^20\d\d-(0[1-9]|1[0-2])$/'],
            'forcer' => ['sometimes', 'boolean'],
        ]);

        try {
            if (! ElevesActifs::estTermine($valide['mois'])) {
                throw new InvalidArgumentException("Le mois {$valide['mois']} n'est pas terminé : son relevé serait partiel.");
            }
            $periodes->reconcilier();
            $r = $this->actifs->releverMois($valide['mois'], (bool) ($valide['forcer'] ?? false));
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Relevé {$valide['mois']} : {$r['calcules']} centre(s) calculé(s), {$r['ignores']} déjà relevé(s).");
    }

    /**
     * @return array{mois: string, centre: int|null, mois_disponibles: array<int, string>, centres: array<int, array{id: int, name: string}>, mois_termine: bool, non_calcules: int, lignes: array<int, array<string, mixed>>}
     */
    private function donneesActifs(Request $request): array
    {
        $courant = ElevesActifs::moisCourant();
        $mois = (string) $request->query('mois', '');
        if (! ElevesActifs::moisValide($mois) || $mois > $courant) {
            $mois = ElevesActifs::moisPrecedent();
        }

        $centres = LanguageCenter::orderBy('name')->get(['id', 'name', 'slug', 'is_active']);
        $centreId = $request->integer('centre') ?: null;
        $centre = $centreId ? $centres->firstWhere('id', $centreId) : null;

        $lignes = $centre
            ? $this->historiqueDuCentre($centre, $courant)
            : $this->moisPourTousLesCentres($centres, $mois, $courant);

        $moisDisponibles = CenterMonthlyActivity::distinct()->pluck('periode')
            ->push($courant, ElevesActifs::moisPrecedent(), $mois)
            ->unique()->sortDesc()->values()->all();

        return [
            'mois' => $mois,
            'centre' => $centre?->id,
            'mois_disponibles' => $moisDisponibles,
            'centres' => $centres->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()->all(),
            'mois_termine' => ElevesActifs::estTermine($mois),
            'non_calcules' => $centre ? 0 : collect($lignes)->where('en_cours', false)->whereNull('actifs')->count(),
            'lignes' => $lignes,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function moisPourTousLesCentres(Collection $centres, string $mois, string $courant): array
    {
        $enCours = $mois === $courant;
        $vifs = $enCours ? $this->actifs->compterParCentre($mois) : [];
        $arretes = $enCours ? collect() : CenterMonthlyActivity::where('periode', $mois)->get()->keyBy('center_id');

        return $centres->map(function (LanguageCenter $c) use ($mois, $enCours, $vifs, $arretes) {
            if ($enCours) {
                $n = $vifs[$c->id] ?? 0;

                return $this->ligne($c, $mois, $n, ElevesActifs::palier($n), null, true);
            }
            $r = $arretes->get($c->id);

            return $this->ligne($c, $mois, $r?->active_students, $r?->palier, $r?->computed_at?->toIso8601String(), false);
        })->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function historiqueDuCentre(LanguageCenter $c, string $courant): array
    {
        $n = $this->actifs->compter($c->id, $courant);
        $lignes = [$this->ligne($c, $courant, $n, ElevesActifs::palier($n), null, true)];

        foreach (CenterMonthlyActivity::where('center_id', $c->id)->orderByDesc('periode')->get() as $r) {
            $lignes[] = $this->ligne($c, $r->periode, $r->active_students, $r->palier, $r->computed_at?->toIso8601String(), false);
        }

        return $lignes;
    }

    /** @return array<string, mixed> */
    private function ligne(LanguageCenter $c, string $mois, ?int $actifs, ?string $palier, ?string $calcule, bool $enCours): array
    {
        return [
            'centre_id' => $c->id,
            'centre' => $c->name,
            'slug' => $c->slug,
            'is_active' => (bool) $c->is_active,
            'periode' => $mois,
            'actifs' => $actifs,
            'palier' => $palier,
            'computed_at' => $calcule,
            'en_cours' => $enCours,
        ];
    }
}
