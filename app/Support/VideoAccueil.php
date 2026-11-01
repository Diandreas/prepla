<?php

namespace App\Support;

/**
 * La video du haut de la page d'accueil : « PrePla, par ou commencer ? », 34 s,
 * en francais, sous-titres incrustes.
 *
 * Les fichiers sont dans public/videos/ ; scripts/video/fabriquer-videos-accueil.sh
 * les refait a partir des deux montages (16:9 et 9:16). Le visiteur voit le poids
 * de ce qu'il va telecharger avant de cliquer : il est lu sur le fichier reel, pas
 * ecrit a la main. S'il manque un fichier, le haut de page s'affiche sans video
 * plutot qu'avec un lecteur vide.
 */
final class VideoAccueil
{
    private const NOM = 'par-ou-commencer';

    /** Duree du film, en secondes. */
    private const DUREE = 34;

    /**
     * L'instant ou commence la carte de fin du film. Elle montre une image de
     * bouton : de vrais boutons la recouvrent a partir de la.
     */
    private const FIN = 31.2;

    private const FICHIERS = [
        // L'apercu muet de 4 s, en boucle, sans piste son.
        'boucle_640' => 'boucle-640.mp4',
        'boucle_960' => 'boucle-960.mp4',
        // Le film, avec le son : paysage dans le cadre, portrait en plein ecran sur telephone.
        'paysage' => '720p.mp4',
        'paysage_leger' => '360p.mp4',
        'portrait' => 'portrait-540.mp4',
        'portrait_leger' => 'portrait-360.mp4',
        // L'affiche est la premiere image de la boucle : l'apercu prend le relais sans saut.
        'affiche_640' => 'affiche-640.webp',
        'affiche_1280' => 'affiche-1280.webp',
        'affiche_640_jpg' => 'affiche-640.jpg',
        'affiche_1280_jpg' => 'affiche-1280.jpg',
        'affiche_portrait' => 'affiche-portrait.webp',
        // Les vignettes des trois etapes, tirees du film.
        'etape_1' => 'etape-1.webp',
        'etape_2' => 'etape-2.webp',
        'etape_3' => 'etape-3.webp',
    ];

    /** @return array{sources: array<string, string>, poids: array<string, int>, duree: int, fin: float}|null */
    public static function donnees(): ?array
    {
        $sources = [];
        $poids = [];

        foreach (self::FICHIERS as $cle => $suffixe) {
            $relatif = 'videos/'.self::NOM.'-'.$suffixe;
            $chemin = public_path($relatif);
            if (! is_file($chemin)) {
                return null;
            }

            // La date du fichier dans l'adresse : un nouveau montage n'est pas
            // masque par le cache du navigateur.
            $sources[$cle] = '/'.$relatif.'?v='.filemtime($chemin);
            $poids[$cle] = (int) filesize($chemin);
        }

        return ['sources' => $sources, 'poids' => $poids, 'duree' => self::DUREE, 'fin' => self::FIN];
    }
}
