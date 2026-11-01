<?php

namespace App\Services\AI;

/**
 * Les fautes relevees dans une production — redaction ou prise de parole —
 * telles que l'IA les rend : ce qui etait ecrit ou dit, la forme juste, la
 * categorie, l'explication.
 *
 * L'IA peut rendre n'importe quoi. Une explication rendue en objet au lieu d'un
 * texte faisait tomber toute la correction : la note, la tentative, parfois
 * l'examen blanc entier, et la copie restait illisible pour le centre. On ne
 * garde que les fautes exploitables, chaque champ ramene a un texte.
 */
final class CorrectionsDeProduction
{
    /** @return list<array{original: string, corrected: string, category: string, explanation: string, subcategory?: string}> */
    public static function nettoyer(mixed $corrections): array
    {
        if (! is_array($corrections)) {
            return [];
        }

        $propres = [];
        foreach ($corrections as $c) {
            if (! is_array($c)) {
                continue;
            }

            $original = self::texte($c['original'] ?? null);
            $corrige = self::texte($c['corrected'] ?? null);
            // Sans l'avant ou l'apres, la faute ne se montre pas et ne se retient pas.
            if ($original === '' || $corrige === '') {
                continue;
            }

            $explication = $c['explanation'] ?? null;
            $propre = [
                'original' => $original,
                'corrected' => $corrige,
                'category' => self::texte($c['category'] ?? null),
                // L'explication de l'oral est un objet — concept, exemple, conseil :
                // l'IA le reprend parfois ici. Le concept dit l'essentiel.
                'explanation' => self::texte(is_array($explication) ? ($explication['concept'] ?? null) : $explication),
            ];
            if (($sous = self::texte($c['subcategory'] ?? null)) !== '') {
                $propre['subcategory'] = $sous;
            }

            $propres[] = $propre;
        }

        return $propres;
    }

    /** Un texte, ou rien : un nombre se lit, un objet ou une liste ne se devine pas. */
    private static function texte(mixed $valeur): string
    {
        return is_string($valeur) || is_int($valeur) || is_float($valeur) ? trim((string) $valeur) : '';
    }
}
