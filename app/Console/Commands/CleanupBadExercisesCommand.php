<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use Illuminate\Console\Command;

/**
 * Supprime les exercices "junk"/cassés/dégénérés de la base :
 *  - signature du fallback IA mort ("What is this exercise about?" / "sample passage for…")
 *  - diagram-labeling (retiré : pas d'images)
 *  - matching en mode ÉCOUTE (incohérent : associer des termes écrits ≠ écouter)
 *  - QCM dégénérés : ≥3 questions dont la bonne réponse est TOUJOURS la même lettre
 *    (l'IA mettait la bonne option toujours en A → l'élève clique toujours A)
 *
 * Suppression sûre (cascadeOnDelete sur attempts, nullOnDelete sur node_id).
 *
 * Usage : php artisan exercises:cleanup [--dry-run]
 */
class CleanupBadExercisesCommand extends Command
{
    protected $signature = 'exercises:cleanup {--dry-run : Compter sans supprimer}';
    protected $description = 'Supprime les exercices junk, diagram-labeling, matching-écoute et QCM dégénérés (toujours la même réponse)';

    public function handle(): int
    {
        $dry = $this->option('dry-run');

        // 1) Junk fallback (LIKE sur le JSON stocké en texte).
        $junkQuery = Exercise::query()->where(function ($q) {
            $q->where('questions', 'like', '%What is this exercise about?%')
              ->orWhere('content', 'like', '%sample passage for%');
        });

        // 2) diagram-labeling.
        $diagramQuery = Exercise::query()->whereHas('exerciseType', fn ($q) => $q->where('component_key', 'diagram-labeling'));

        // 3) matching en listening.
        $matchingListeningQuery = Exercise::query()->whereHas('exerciseType', fn ($q) =>
            $q->where('component_key', 'matching')->where('skill_type', 'listening'));

        $junkCount = (clone $junkQuery)->count();
        $diagramCount = (clone $diagramQuery)->count();
        $matchingListeningCount = (clone $matchingListeningQuery)->count();

        // 4) QCM "toujours la même réponse" → détection PHP.
        $sameAnswerIds = [];
        Exercise::query()->with('exerciseType')->chunk(200, function ($chunk) use (&$sameAnswerIds) {
            foreach ($chunk as $e) {
                $letters = collect($e->questions ?? [])
                    ->pluck('correct_answer')
                    ->filter(fn ($a) => is_string($a) && preg_match('/^[A-Z]$/', strtoupper(trim($a))))
                    ->map(fn ($a) => strtoupper(trim($a)))
                    ->values();
                if ($letters->count() >= 3 && $letters->unique()->count() === 1) {
                    $sameAnswerIds[] = $e->id;
                }
            }
        });

        $this->info("Junk (fallback IA)      : {$junkCount}");
        $this->info("diagram-labeling        : {$diagramCount}");
        $this->info("matching en écoute      : {$matchingListeningCount}");
        $this->info('QCM toujours même rép.  : ' . count($sameAnswerIds));

        if ($dry) {
            $this->warn('Dry-run : rien supprimé.');
            return self::SUCCESS;
        }

        $d1 = $junkQuery->delete();
        $d2 = $diagramQuery->delete();
        $d3 = $matchingListeningQuery->delete();
        $d4 = !empty($sameAnswerIds) ? Exercise::whereIn('id', $sameAnswerIds)->delete() : 0;

        $this->info("✅ Supprimés — junk:{$d1}, diagram:{$d2}, matching-écoute:{$d3}, même-réponse:{$d4}");
        return self::SUCCESS;
    }
}
