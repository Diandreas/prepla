<?php

namespace App\Services\Center;

/**
 * Calculs de couleurs pour la marque d'un centre : format, contraste (formule
 * WCAG 2.1), texte lisible sur une couleur, variante pour le theme sombre.
 *
 * Le meme calcul existe cote client pour l'apercu en direct
 * (resources/js/lib/marque-centre.js) ; c'est celui-ci qui decide.
 */
class Couleurs
{
    /** Contraste minimal d'une couleur de marque sur fond blanc (WCAG, elements d'interface et grand texte). */
    public const CONTRASTE_MINIMAL = 3.0;

    /** Fond du theme sombre (app.css, .dark --background : hsl(218, 47%, 12%)). */
    public const FOND_SOMBRE = '#101B2D';

    public const BLANC = '#FFFFFF';

    /** Texte fonce employe sur une couleur claire. */
    public const TEXTE_FONCE = '#0B1322';

    public static function estHexa(?string $couleur): bool
    {
        return is_string($couleur) && preg_match('/^#[0-9a-fA-F]{6}$/', $couleur) === 1;
    }

    /** « #4a90e2 » -> « #4A90E2 » : une seule ecriture en base. */
    public static function normaliser(string $couleur): string
    {
        return strtoupper($couleur);
    }

    /** @return array{0: int, 1: int, 2: int} */
    private static function rgb(string $hexa): array
    {
        $h = ltrim($hexa, '#');

        return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2))];
    }

    private static function hexa(array $rgb): string
    {
        return '#'.strtoupper(implode('', array_map(fn ($c) => str_pad(dechex((int) round(max(0, min(255, $c)))), 2, '0', STR_PAD_LEFT), $rgb)));
    }

    public static function luminance(string $hexa): float
    {
        $lin = array_map(function (int $c) {
            $s = $c / 255;

            return $s <= 0.03928 ? $s / 12.92 : (($s + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hexa));

        return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
    }

    /** Rapport de contraste entre deux couleurs, de 1 a 21. */
    public static function contraste(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /** Le texte (blanc ou fonce) le plus lisible pose sur cette couleur. */
    public static function texteSur(string $fond): string
    {
        return self::contraste($fond, self::BLANC) >= self::contraste($fond, self::TEXTE_FONCE)
            ? self::BLANC
            : self::TEXTE_FONCE;
    }

    /**
     * La couleur, eclaircie juste assez pour rester visible sur le fond sombre.
     * Une couleur de marque foncee disparaitrait sinon dans le theme sombre.
     */
    public static function pourFondSombre(string $couleur): string
    {
        $rgb = self::rgb($couleur);
        $resultat = self::hexa($rgb);

        for ($part = 0.0; $part <= 1.0 && self::contraste($resultat, self::FOND_SOMBRE) < self::CONTRASTE_MINIMAL; $part += 0.05) {
            $resultat = self::hexa(array_map(fn ($c) => $c + (255 - $c) * $part, $rgb));
        }

        return $resultat;
    }
}
