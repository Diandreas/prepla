<?php

namespace App\Console\Commands;

use App\Http\Controllers\Center\PersonnalisationController;
use App\Models\LanguageCenter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Donne au centre de demonstration « Institut Linguae » une marque d'exemple :
 * un logo simple (PNG fabrique ici) et deux couleurs, pour montrer la
 * personnalisation pendant une presentation.
 *
 * Le seeder de la demo commence par SUPPRIMER le centre : on ne le relance pas,
 * et on ne le modifie pas. Cette commande ne supprime rien et peut etre relancee :
 * elle ne remplit que ce qui est vide (sauf --remplacer), et le logo garde
 * toujours le meme chemin. Elle refuse de tourner en production.
 */
class DemoMarque extends Command
{
    protected $signature = 'prepla:demo-marque
        {--remplacer : Remplacer aussi une marque deja choisie pour la demonstration}';

    protected $description = 'Donne au centre de demonstration un logo d\'exemple et deux couleurs (jamais en production)';

    private const SLUG = 'institut-linguae-demo';

    /** Couleurs de l'exemple : contraste sur fond blanc superieur a 3:1. */
    public const PRIMAIRE = '#0F766E';

    public const ACCENT = '#9A3412';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Commande de démonstration : elle ne tourne pas en production.');

            return self::FAILURE;
        }

        $centre = LanguageCenter::where('slug', self::SLUG)->first();
        if (! $centre) {
            $this->info('Aucun centre de démonstration : rien à faire.');

            return self::SUCCESS;
        }

        $remplacer = (bool) $this->option('remplacer');
        $chemin = PersonnalisationController::DOSSIER_LOGOS.'/'.$centre->id.'/demo-logo.png';
        $changements = [];

        if ($remplacer || $centre->logo_path === null || ! Storage::disk('public')->exists($centre->logo_path)) {
            Storage::disk('public')->put($chemin, $this->logo());
            $changements['logo_path'] = $chemin;
        }
        if ($remplacer || $centre->brand_primary === null) {
            $changements['brand_primary'] = self::PRIMAIRE;
        }
        if ($remplacer || $centre->brand_accent === null) {
            $changements['brand_accent'] = self::ACCENT;
        }

        if ($changements === []) {
            $this->info('La démonstration a déjà sa marque : rien à changer.');

            return self::SUCCESS;
        }

        $centre->update($changements);
        $this->info('Marque de démonstration posée : '.implode(', ', array_keys($changements)).'.');

        return self::SUCCESS;
    }

    /**
     * Un logo simple de 256 px : un carre arrondi aux couleurs de la demo et
     * deux pages ouvertes, en blanc. Fabrique a chaque fois de la meme facon.
     */
    private function logo(): string
    {
        $taille = 256;
        $image = imagecreatetruecolor($taille, $taille);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));

        [$r, $g, $b] = sscanf(self::PRIMAIRE, '#%02x%02x%02x');
        $fond = imagecolorallocate($image, $r, $g, $b);
        [$r, $g, $b] = sscanf(self::ACCENT, '#%02x%02x%02x');
        $accent = imagecolorallocate($image, $r, $g, $b);
        $blanc = imagecolorallocate($image, 255, 255, 255);

        // Carre aux coins arrondis.
        $rayon = 48;
        imagefilledrectangle($image, $rayon, 0, $taille - 1 - $rayon, $taille - 1, $fond);
        imagefilledrectangle($image, 0, $rayon, $taille - 1, $taille - 1 - $rayon, $fond);
        foreach ([[$rayon, $rayon], [$taille - 1 - $rayon, $rayon], [$rayon, $taille - 1 - $rayon], [$taille - 1 - $rayon, $taille - 1 - $rayon]] as [$x, $y]) {
            imagefilledellipse($image, $x, $y, $rayon * 2, $rayon * 2, $fond);
        }

        // Livre ouvert : deux pages blanches, un dos a la couleur d'accent.
        imagefilledpolygon($image, [52, 78, 124, 92, 124, 188, 52, 174], $blanc);
        imagefilledpolygon($image, [132, 92, 204, 78, 204, 174, 132, 188], $blanc);
        imagefilledrectangle($image, 124, 88, 132, 192, $accent);

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }
}
