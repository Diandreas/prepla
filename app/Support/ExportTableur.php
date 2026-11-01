<?php

namespace App\Support;

/**
 * Une ligne d'export CSV, sure a ouvrir dans un tableur.
 *
 * Excel et LibreOffice executent une cellule qui commence par =, +, - ou @ :
 * un eleve nomme « =LIEN_HYPERTEXTE(...) », ou un titre d'exercice ecrit par
 * un enseignant, devenait une formule chez celui qui ouvrait l'export — de quoi
 * envoyer au clic les donnees d'un camarade. Une apostrophe devant en fait du
 * texte, affiche tel quel.
 */
final class ExportTableur
{
    /**
     * @param  array<int, mixed>  $ligne
     * @return array<int, mixed>
     */
    public static function neutraliser(array $ligne): array
    {
        return array_map(
            fn ($valeur) => is_string($valeur) && preg_match('/^[=+\-@\t\r]/', $valeur) ? "'".$valeur : $valeur,
            $ligne,
        );
    }
}
