<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Services\Center\ClassStatsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgressController extends Controller
{
    /** Download the class progression as a CSV for the center's own reporting. */
    public function exportCsv(Request $request, Classroom $classroom, ClassStatsService $stats): StreamedResponse
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        abort_unless($classroom->center_id === $center->id, 403);

        $data = $stats->forClassroom($classroom);
        $filename = 'progression-' . $classroom->invite_code . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            // BOM so Excel reads UTF-8 accents correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Élève', 'Exercices faits', 'Précision moyenne (%)', 'Dernière activité']);

            foreach ($data['per_student'] as $s) {
                fputcsv($out, [
                    $s['name'],
                    $s['attempts'],
                    $s['avg_accuracy'] ?? '',
                    $s['last_active'] ? \Illuminate\Support\Carbon::parse($s['last_active'])->format('Y-m-d') : '',
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Faiblesses communes de la classe']);
            fputcsv($out, ['Catégorie', 'Sous-catégorie', 'Occurrences']);
            foreach ($data['common_weaknesses'] as $w) {
                fputcsv($out, [$w['category'], $w['subcategory'] ?? '', $w['count']]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
