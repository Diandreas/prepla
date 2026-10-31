<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Fabrique les bannieres de partage (Open Graph).
 *
 * Quand on colle un lien dans WhatsApp, LinkedIn ou Facebook, le reseau va
 * chercher `og:image`. Nous lui donnions l'icone carree de 512 px de la PWA :
 * les reseaux attendent du 1200x630 et recadrent le reste, d'ou un apercu
 * minuscule et coupe. Ces bannieres sont faites au bon format.
 *
 * Les PNG produits sont VERSIONNES : la production ne lance jamais cette
 * commande, elle sert le fichier. On la relance seulement pour refaire les
 * images, et il faut alors une police TrueType sur la machine.
 */
class FabriquerImagesPartage extends Command
{
    protected $signature = 'prepla:fabriquer-og {--sec : Dire ce qui serait ecrit, sans rien ecrire}';

    protected $description = 'Fabrique les bannieres Open Graph 1200x630 dans public/og';

    /** Les polices du site ne sont pas redistribuables : on prend l'equivalent le plus proche. */
    private const SERIF_ITALIQUE = ['georgiai.ttf', 'Georgia Italic.ttf', 'DejaVuSerif-Italic.ttf', 'LiberationSerif-Italic.ttf'];

    private const SANS_GRAS = ['segoeuib.ttf', 'arialbd.ttf', 'DejaVuSans-Bold.ttf', 'LiberationSans-Bold.ttf'];

    private const SANS = ['segoeui.ttf', 'arial.ttf', 'DejaVuSans.ttf', 'LiberationSans-Regular.ttf'];

    /** @var list<array{fichier: string, titre: string, sous_titre: string}> */
    private const BANNIERES = [
        [
            'fichier' => 'prepla.png',
            'titre' => "Préparation aux examens de langue",
            'sous_titre' => "Anglais · Français · Allemand — exercices générés par IA, test de niveau, parcours adapté",
        ],
        [
            'fichier' => 'structures.png',
            'titre' => "Les structures officielles des examens",
            'sous_titre' => "Chaque épreuve, chaque partie, chaque question — relevées sur les sites des organismes",
        ],
        [
            'fichier' => 'inscription.png',
            'titre' => "Essai gratuit pendant 7 jours",
            'sous_titre' => "Un test de niveau, un parcours construit pour ton examen, des exercices générés à ta mesure",
        ],
    ];

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->error("L'extension GD est absente : impossible de dessiner les bannieres.");

            return self::FAILURE;
        }

        $serif = $this->police(self::SERIF_ITALIQUE);
        $gras = $this->police(self::SANS_GRAS);
        $sans = $this->police(self::SANS);

        if (! $serif || ! $gras || ! $sans) {
            $this->error('Aucune police TrueType utilisable trouvee sur cette machine.');
            $this->line('Les images deja versionnees restent valables : cette commande ne sert qu a les refaire.');

            return self::FAILURE;
        }

        $dossier = public_path('og');

        if (! $this->option('sec') && ! is_dir($dossier) && ! mkdir($dossier, 0o755, true) && ! is_dir($dossier)) {
            $this->error("Impossible de creer {$dossier}.");

            return self::FAILURE;
        }

        foreach (self::BANNIERES as $banniere) {
            $chemin = $dossier.DIRECTORY_SEPARATOR.$banniere['fichier'];

            if ($this->option('sec')) {
                $this->line("  ecrirait  {$chemin}");

                continue;
            }

            $this->dessiner($banniere, $chemin, $serif, $gras, $sans);
            $this->line(sprintf('  %-20s %d octets', $banniere['fichier'], filesize($chemin) ?: 0));
        }

        return self::SUCCESS;
    }

    /** @param  list<string>  $noms */
    private function police(array $noms): ?string
    {
        $dossiers = [
            resource_path('fonts'),
            'C:\\Windows\\Fonts',
            '/usr/share/fonts/truetype/dejavu',
            '/usr/share/fonts/truetype/liberation',
            '/Library/Fonts',
        ];

        foreach ($dossiers as $dossier) {
            foreach ($noms as $nom) {
                $chemin = $dossier.DIRECTORY_SEPARATOR.$nom;
                if (is_file($chemin)) {
                    return $chemin;
                }
            }
        }

        return null;
    }

    /** @param  array{fichier: string, titre: string, sous_titre: string}  $banniere */
    private function dessiner(array $banniere, string $chemin, string $serif, string $gras, string $sans): void
    {
        $largeur = 1200;
        $hauteur = 630;
        $image = imagecreatetruecolor($largeur, $hauteur);

        $encre = imagecolorallocate($image, 0x0B, 0x13, 0x22);
        $blanc = imagecolorallocate($image, 0xF2, 0xF6, 0xFC);
        $or = imagecolorallocate($image, 0xF5, 0xA6, 0x23);
        $doux = imagecolorallocate($image, 0x9A, 0xAD, 0xC8);

        imagefilledrectangle($image, 0, 0, $largeur, $hauteur, $encre);

        // Un degrade discret vers le bleu de marque, pour que le fond ne soit pas
        // un aplat mort sur les fils d'actualite.
        for ($y = 0; $y < $hauteur; $y++) {
            $part = $y / $hauteur;
            $teinte = imagecolorallocatealpha(
                $image,
                (int) (0x0B + $part * 0x12),
                (int) (0x13 + $part * 0x1C),
                (int) (0x22 + $part * 0x2E),
                (int) (90 - $part * 40)
            );
            imageline($image, 0, $y, $largeur, $y, $teinte);
        }

        // Filet dore en haut : la seule couleur vive de la marque.
        imagefilledrectangle($image, 0, 0, $largeur, 8, $or);

        // Le mot-marque, comme sur le site : « Pre » clair, « Pla » dore.
        $x = 88;
        imagettftext($image, 46, 0, $x, 150, $blanc, $serif, 'Pre');
        $boite = imagettfbbox(46, 0, $serif, 'Pre');
        imagettftext($image, 46, 0, $x + ($boite[2] - $boite[0]) + 4, 150, $or, $serif, 'Pla');

        // Le titre, coupe a la main : GD ne sait pas aller a la ligne tout seul.
        $y = 260;
        foreach ($this->couper($banniere['titre'], $gras, 54, $largeur - 176) as $ligne) {
            imagettftext($image, 54, 0, 88, $y, $blanc, $gras, $ligne);
            $y += 74;
        }

        $y += 18;
        foreach ($this->couper($banniere['sous_titre'], $sans, 27, $largeur - 176) as $ligne) {
            imagettftext($image, 27, 0, 88, $y, $doux, $sans, $ligne);
            $y += 42;
        }

        imagettftext($image, 23, 0, 88, $hauteur - 56, $or, $sans, 'prepla.mirlab.cloud');

        imagepng($image, $chemin, 9);
        imagedestroy($image);
    }

    /**
     * Couper un texte en lignes qui tiennent dans la largeur donnee.
     *
     * @return list<string>
     */
    private function couper(string $texte, string $police, int $taille, int $largeurMax): array
    {
        $lignes = [];
        $courante = '';

        foreach (explode(' ', $texte) as $mot) {
            $essai = $courante === '' ? $mot : $courante.' '.$mot;
            $boite = imagettfbbox($taille, 0, $police, $essai);

            if (($boite[2] - $boite[0]) > $largeurMax && $courante !== '') {
                $lignes[] = $courante;
                $courante = $mot;

                continue;
            }

            $courante = $essai;
        }

        if ($courante !== '') {
            $lignes[] = $courante;
        }

        return $lignes;
    }
}
