<?php

namespace App\Services\AI;

/**
 * Traduit la note d'entrainement d'une redaction (0 a 9, quatre dimensions
 * communes) dans l'echelle de l'examen vise.
 *
 * Pourquoi ici et pas dans le prompt : l'IA garde UNE echelle stable (0 a 9),
 * qui sert aux statistiques (accuracy en %) et aux comparaisons entre
 * examens. La conversion vers l'echelle officielle est faite par le code, de
 * facon previsible et testable : un candidat DELF lit une note sur 25, un
 * candidat Goethe une lettre par critere, un candidat IELTS une bande.
 *
 * Ce n'est JAMAIS une note officielle : chaque grille porte la mention.
 */
class GrilleOfficielleEcrit
{
    public const MENTION = 'Estimation, pas une note officielle.';

    /** Les quatre dimensions communes rendues par le correcteur. */
    private const DIMENSIONS = ['task_achievement', 'coherence_cohesion', 'lexical_resource', 'grammar_accuracy'];

    /**
     * @param  array<string, mixed>  $bandScores  les quatre dimensions sur 9
     * @return array<string, mixed>|null  null quand l'examen n'a pas de grille connue
     */
    public static function calculer(string $examen, mixed $score, array $bandScores): ?array
    {
        $famille = self::famille($examen);
        if ($famille === null) {
            return null;
        }

        $global = self::borne($score);
        $dims = [];
        foreach (self::DIMENSIONS as $cle) {
            // Une dimension absente prend la note globale : mieux qu'un zero
            // qui ferait chuter la note officielle sans raison.
            $dims[$cle] = isset($bandScores[$cle]) && is_numeric($bandScores[$cle])
                ? self::borne($bandScores[$cle])
                : $global;
        }

        $grille = match ($famille) {
            'delf' => self::delf($dims),
            'tcf' => self::tcf($global),
            'tef' => self::tef($global),
            'goethe' => self::goethe($dims, str_contains(mb_strtolower($examen), 'telc') ? 'telc' : 'Goethe'),
            'ielts' => self::ielts($dims),
        };

        return $grille + ['mention' => self::MENTION];
    }

    /** La famille de grille, d'apres le nom de l'examen. */
    public static function famille(string $examen): ?string
    {
        $nom = mb_strtolower($examen);

        return match (true) {
            str_contains($nom, 'delf') || str_contains($nom, 'dalf') => 'delf',
            str_contains($nom, 'tcf') => 'tcf',
            str_contains($nom, 'tef') => 'tef',
            str_contains($nom, 'goethe') || str_contains($nom, 'telc') => 'goethe',
            str_contains($nom, 'ielts') => 'ielts',
            default => null,
        };
    }

    private static function borne(mixed $note): float
    {
        return max(0.0, min(9.0, is_numeric($note) ? (float) $note : 0.0));
    }

    /** Arrondi au demi-point, comme les grilles DELF et les bandes IELTS. */
    private static function demi(float $valeur): float
    {
        return round($valeur * 2) / 2;
    }

    /**
     * DELF / DALF : production ecrite sur 25. Les criteres officiels sont
     * regroupes en quatre blocs dont les poids suivent la grille du B1 :
     * consigne et adequation (9), coherence (4), lexique (6), grammaire (6).
     */
    private static function delf(array $d): array
    {
        $blocs = [
            ['Respect de la consigne et adéquation sociolinguistique', 'task_achievement', 9],
            ['Cohérence et cohésion', 'coherence_cohesion', 4],
            ['Compétence lexicale et orthographe lexicale', 'lexical_resource', 6],
            ['Compétence grammaticale et orthographe grammaticale', 'grammar_accuracy', 6],
        ];

        $criteres = [];
        $total = 0.0;
        foreach ($blocs as [$nom, $cle, $max]) {
            $note = self::demi($d[$cle] / 9 * $max);
            $total += $note;
            $criteres[] = ['nom' => $nom, 'valeur' => self::nombre($note).' / '.$max];
        }

        return [
            'examen' => 'DELF / DALF',
            'note' => $total,
            'note_max' => 25,
            'affichage' => self::nombre($total).' / 25',
            // Au DELF, moins de 5 sur 25 a une epreuve est eliminatoire.
            'commentaire' => $total < 5 ? 'Sous la note éliminatoire (5 / 25).' : null,
            'criteres' => $criteres,
        ];
    }

    /** TCF : expression ecrite sur 20, rattachee a un niveau du CECRL. */
    private static function tcf(float $global): array
    {
        $note = (int) round($global / 9 * 20);
        $niveau = match (true) {
            $note >= 16 => 'C2',
            $note >= 14 => 'C1',
            $note >= 10 => 'B2',
            $note >= 6 => 'B1',
            $note >= 4 => 'A2',
            $note >= 1 => 'A1',
            default => 'non classé',
        };

        return [
            'examen' => 'TCF',
            'note' => $note,
            'note_max' => 20,
            'niveau' => $niveau,
            'affichage' => "{$note} / 20 · niveau {$niveau}",
            'commentaire' => null,
            'criteres' => [],
        ];
    }

    /** TEF : expression ecrite sur 450, rattachee a un niveau du CECRL. */
    private static function tef(float $global): array
    {
        $note = (int) round($global / 9 * 450);
        $niveau = match (true) {
            $note >= 415 => 'C1',
            $note >= 371 => 'B2',
            $note >= 300 => 'B1',
            $note >= 268 => 'A2',
            $note >= 181 => 'A1',
            default => 'non classé',
        };

        return [
            'examen' => 'TEF',
            'note' => $note,
            'note_max' => 450,
            'niveau' => $niveau,
            'affichage' => "{$note} / 450 · niveau {$niveau}",
            'commentaire' => null,
            'criteres' => [],
        ];
    }

    /**
     * Goethe / telc : une appreciation de A (pleinement) a E (pas du tout) par
     * critere, et des points sur 100 ; il en faut 60 pour reussir le module.
     */
    private static function goethe(array $d, string $nomExamen): array
    {
        $criteres = [];
        foreach ([
            ['Erfüllung (Aufgabenerfüllung)', 'task_achievement'],
            ['Kohärenz', 'coherence_cohesion'],
            ['Wortschatz', 'lexical_resource'],
            ['Strukturen', 'grammar_accuracy'],
        ] as [$nom, $cle]) {
            $criteres[] = ['nom' => $nom, 'valeur' => self::lettre($d[$cle])];
        }

        $points = (int) round(array_sum($d) / count($d) / 9 * 100);

        return [
            'examen' => $nomExamen,
            'note' => $points,
            'note_max' => 100,
            'affichage' => "{$points} / 100",
            'commentaire' => $points >= 60 ? 'Au-dessus du seuil de réussite (60 / 100).' : 'Sous le seuil de réussite (60 / 100).',
            'criteres' => $criteres,
        ];
    }

    private static function lettre(float $note): string
    {
        return match (true) {
            $note >= 8 => 'A',
            $note >= 6.5 => 'B',
            $note >= 5 => 'C',
            $note >= 3 => 'D',
            default => 'E',
        };
    }

    /** IELTS : une bande de 0 a 9 par critere, et la moyenne au demi-point. */
    private static function ielts(array $d): array
    {
        $criteres = [];
        foreach ([
            ['Task Achievement', 'task_achievement'],
            ['Coherence and Cohesion', 'coherence_cohesion'],
            ['Lexical Resource', 'lexical_resource'],
            ['Grammatical Range and Accuracy', 'grammar_accuracy'],
        ] as [$nom, $cle]) {
            $criteres[] = ['nom' => $nom, 'valeur' => 'Band '.self::nombre(self::demi($d[$cle]))];
        }

        $bande = self::demi(array_sum($d) / count($d));

        return [
            'examen' => 'IELTS',
            'note' => $bande,
            'note_max' => 9,
            'affichage' => 'Band '.self::nombre($bande),
            'commentaire' => null,
            'criteres' => $criteres,
        ];
    }

    /** 17.5 -> « 17,5 », 18.0 -> « 18 ». */
    private static function nombre(float $valeur): string
    {
        return str_replace('.', ',', (string) ($valeur == floor($valeur) ? (int) $valeur : $valeur));
    }
}
