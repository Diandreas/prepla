<?php

namespace App\Services\AI;

/**
 * Le profil d'une production — redaction ou prise de parole — tel que l'IA le
 * decrit : les tendances de fond, preuve a l'appui, qu'aucune correction isolee
 * ne montre (« "gut" revient 5 fois », « aucune subordonnee »).
 *
 * L'IA peut rendre n'importe quoi. On ne garde que ce qui est exploitable : une
 * dimension connue, une observation non vide, quatre au plus. Le reste serait du
 * bruit sur la fiche que lit le centre.
 */
final class DiagnosticDeProduction
{
    public const DIMENSIONS = [
        'lexical_range',
        'lexical_precision',
        'syntactic_complexity',
        'grammatical_control',
        'coherence',
        'register',
        'fluency',
        'task_fulfilment',
    ];

    /** @return list<array{dimension: string, observation: string, severity: string}> */
    public static function nettoyer(mixed $diagnostic): array
    {
        if (! is_array($diagnostic)) {
            return [];
        }

        $propre = [];
        foreach ($diagnostic as $d) {
            // Une observation rendue en liste ou en objet ne se lit pas : on
            // l'ignore, au lieu de faire tomber toute la correction.
            $observation = is_array($d) && is_string($d['observation'] ?? null) ? trim($d['observation']) : '';
            if ($observation === '' || ! in_array($d['dimension'] ?? null, self::DIMENSIONS, true)) {
                continue;
            }

            $propre[] = [
                'dimension' => $d['dimension'],
                'observation' => $observation,
                'severity' => in_array($d['severity'] ?? null, ['strength', 'minor', 'major'], true) ? $d['severity'] : 'minor',
            ];

            if (count($propre) === 4) {
                break;
            }
        }

        return $propre;
    }
}
