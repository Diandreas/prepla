<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Sélectionne des images dans la bibliothèque pré-générée (storage/app/public/exercise-images/{exam}/)
 * pour les exercices qui ont besoin de visuel (diagram-labeling, graph-description...).
 *
 * Évite l'affichage "Diagramme non disponible" en piochant une image cohérente.
 */
class ImageLibraryService
{
    private const BASE_DIR = 'exercise-images';

    /**
     * Retourne une URL d'image aléatoire pour un examen + une catégorie sémantique.
     * Categories : 'graph' (charts), 'process' (diagrams), 'map' (plans).
     *
     * @return string|null URL publique (ex: /storage/exercise-images/ielts/process-water-cycle.jpg) ou null
     */
    public function pickFor(string $examSlug, ?string $category = null): ?string
    {
        $examSlug = strtolower($examSlug);
        $dir = storage_path('app/public/' . self::BASE_DIR . '/' . $examSlug);

        if (!File::exists($dir)) {
            return null;
        }

        $files = collect(File::files($dir))
            ->map(fn ($f) => $f->getFilename())
            ->filter(fn ($name) => preg_match('/\.(jpg|jpeg|png|webp)$/i', $name));

        if ($category) {
            // Categories are encoded in the filename prefix: 'bar-chart-...', 'process-...', 'map-...'
            $prefixes = match ($category) {
                'graph'   => ['bar-chart', 'line-graph', 'pie-chart', 'table', 'mixed-graph'],
                'process' => ['process-'],
                'map'     => ['map-'],
                default   => [],
            };
            if (!empty($prefixes)) {
                $files = $files->filter(function ($name) use ($prefixes) {
                    foreach ($prefixes as $p) {
                        if (str_starts_with($name, $p)) return true;
                    }
                    return false;
                });
            }
        }

        if ($files->isEmpty()) {
            return null;
        }

        $picked = $files->random();
        return Storage::disk('public')->url(self::BASE_DIR . '/' . $examSlug . '/' . $picked);
    }

    /**
     * Devine la catégorie depuis le component_key de l'exercice.
     */
    public function categoryFor(string $componentKey): ?string
    {
        return match ($componentKey) {
            // diagram-labeling retiré (pas d'images disponibles dans la librairie).
            'graph-description' => 'graph',
            default             => null,
        };
    }
}
