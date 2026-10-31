<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Services\Center\ClassStatsService;
use App\Services\Center\SuiviApprenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgressController extends Controller
{
    /** Download the class progression as a CSV for the center's own reporting. */
    public function exportCsv(Request $request, Classroom $classroom, ClassStatsService $stats, SuiviApprenant $suivi): StreamedResponse
    {
        $this->authorize('view', $classroom);

        $data = $stats->forClassroom($classroom);
        $filename = 'progression-' . $classroom->invite_code . '.csv';
        // L'heure du centre, et non l'UTC du serveur : une seance a 0 h 30 a
        // Douala tombait la veille.
        $fuseau = $suivi->fuseau($classroom->center);

        return response()->streamDownload(function () use ($data, $fuseau) {
            $out = fopen('php://output', 'w');
            // BOM so Excel reads UTF-8 accents correctly.
            fwrite($out, "\xEF\xBB\xBF");
            // Point-virgule : l'Excel francais attend ce separateur, et ouvrait un
            // fichier a virgules en une seule colonne.
            $ligne = fn (array $valeurs) => fputcsv($out, $valeurs, ';');

            $ligne(['Élève', 'Exercices faits', 'Précision moyenne (%)', 'Temps total (min)', 'Dernière activité', "Heure ({$fuseau})"]);

            foreach ($data['per_student'] as $s) {
                $quand = $s['last_active'] ? Carbon::parse($s['last_active'])->setTimezone($fuseau) : null;
                $ligne([
                    $s['name'],
                    $s['attempts'],
                    $s['avg_accuracy'] ?? '',
                    round(($s['temps_total_secondes'] ?? 0) / 60, 1),
                    $quand?->format('Y-m-d') ?? '',
                    $quand?->format('H:i') ?? '',
                ]);
            }

            $ligne([]);
            $ligne(['Faiblesses communes de la classe']);
            $ligne(['Catégorie', 'Sous-catégorie', 'Occurrences']);
            foreach ($data['common_weaknesses'] as $w) {
                $ligne([$w['category'], $w['subcategory'] ?? '', $w['count']]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
