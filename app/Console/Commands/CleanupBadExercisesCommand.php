<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use Illuminate\Console\Command;

/**
 * Supprime les exercices "junk"/cassés de la base :
 *  - signature du fallback IA mort ("What is this exercise about?" / "sample passage for…")
 *  - les exercices diagram-labeling (retirés : pas d'images disponibles)
 *
 * Suppression sûre : user_exercise_attempts est en cascadeOnDelete,
 * exercises.node_id est en nullOnDelete.
 *
 * Usage :
 *   php artisan exercises:cleanup --dry-run
 *   php artisan exercises:cleanup
 */
class CleanupBadExercisesCommand extends Command
{
    protected $signature = 'exercises:cleanup {--dry-run : Compter sans supprimer}';

    protected $description = 'Supprime les exercices junk (fallback IA) et diagram-labeling';

    public function handle(): int
    {
        // 1) Junk fallback : le JSON est stocké en texte → LIKE fonctionne (SQLite & MySQL).
        $junkQuery = Exercise::query()->where(function ($q) {
            $q->where('questions', 'like', '%What is this exercise about?%')
              ->orWhere('content', 'like', '%sample passage for%');
        });

        // 2) diagram-labeling : retiré de la rotation, pas d'images.
        $diagramQuery = Exercise::query()->whereHas('exerciseType', function ($q) {
            $q->where('component_key', 'diagram-labeling');
        });

        $junkCount = (clone $junkQuery)->count();
        $diagramCount = (clone $diagramQuery)->count();

        $this->info("Junk (fallback IA) : {$junkCount}");
        $this->info("diagram-labeling   : {$diagramCount}");

        if ($this->option('dry-run')) {
            $this->warn('Dry-run : rien supprimé.');
            return self::SUCCESS;
        }

        $deletedJunk = $junkQuery->delete();
        $deletedDiagram = $diagramQuery->delete();

        $this->info("✅ Supprimés — junk: {$deletedJunk}, diagram-labeling: {$deletedDiagram}");
        return self::SUCCESS;
    }
}
