<?php

namespace App\Services\Content;

use App\Models\User;
use App\Models\UserExerciseAttempt;

/**
 * La difficulte A L'INTERIEUR d'un niveau.
 *
 * Un apprenant A1 recevait des exercices A1 du premier au dernier jour : le
 * niveau ne bougeait qu'apres l'examen de palier, et entre les deux tout se
 * ressemblait. Une apprenante l'a dit : « trop simple ». On distingue trois
 * paliers, tires de ses derniers resultats a ce niveau :
 *
 *  1. Découverte — debut du niveau, ou resultats fragiles ;
 *  2. Consolidation — il reussit la plupart du temps ;
 *  3. Vers le niveau suivant — il reussit presque tout, regulierement.
 *
 * Le palier redescend aussi : trois exercices rates d'affilee le ramenent au
 * palier inferieur, plutot que de le laisser buter.
 */
class PalierDeDifficulte
{
    public const DECOUVERTE = 1;

    public const CONSOLIDATION = 2;

    public const VERS_LE_SUIVANT = 3;

    /** Les derniers exercices du niveau qui comptent. */
    private const FENETRE = 8;

    public function pour(User $user, string $niveau): int
    {
        $precisions = UserExerciseAttempt::query()
            ->join('exercises', 'exercises.id', '=', 'user_exercise_attempts.exercise_id')
            ->where('user_exercise_attempts.user_id', $user->id)
            ->where('exercises.difficulty', $niveau)
            ->whereNotNull('user_exercise_attempts.accuracy_percent')
            ->orderByDesc('user_exercise_attempts.created_at')
            ->orderByDesc('user_exercise_attempts.id')
            ->limit(self::FENETRE)
            ->pluck('user_exercise_attempts.accuracy_percent')
            ->map(fn ($p) => (float) $p);

        return self::depuis($precisions->all());
    }

    /**
     * Le palier tire d'une suite de precisions, la plus recente d'abord.
     *
     * @param  list<float>  $precisions
     */
    public static function depuis(array $precisions): int
    {
        $nombre = count($precisions);
        if ($nombre < 3) {
            return self::DECOUVERTE;
        }

        $moyenne = array_sum($precisions) / $nombre;
        $palier = match (true) {
            $nombre >= 6 && $moyenne >= 85 => self::VERS_LE_SUIVANT,
            $moyenne >= 65 => self::CONSOLIDATION,
            default => self::DECOUVERTE,
        };

        // Trois echecs recents : on redescend d'un cran.
        $recents = array_slice($precisions, 0, 3);
        if (count(array_filter($recents, fn ($p) => $p < 50)) === 3) {
            $palier = max(self::DECOUVERTE, $palier - 1);
        }

        return $palier;
    }

    public static function libelle(int $palier): string
    {
        return match ($palier) {
            self::VERS_LE_SUIVANT => 'Vers le niveau suivant',
            self::CONSOLIDATION => 'Consolidation',
            default => 'Découverte',
        };
    }

    /** La consigne donnee au generateur d'exercices pour ce palier, a ce niveau. */
    public static function consigne(int $palier, string $niveau): string
    {
        return match ($palier) {
            self::VERS_LE_SUIVANT => "\n\nDIFFICULTY WITHIN {$niveau}: UPPER {$niveau}. The learner succeeds almost every time at this level: make it genuinely challenging while staying {$niveau}. Use the longest texts allowed, less frequent {$niveau} vocabulary, several pieces of information to combine, distractors that are plausible and close to the answer (paraphrases, partial truths, the right word in the wrong context), and at least one item that requires inference rather than spotting a repeated word.",
            self::CONSOLIDATION => "\n\nDIFFICULTY WITHIN {$niveau}: MID {$niveau}. The learner usually succeeds: go beyond the very first words of the level. Vary the structures, paraphrase the text in the questions instead of repeating it word for word, and make distractors plausible.",
            default => "\n\nDIFFICULTY WITHIN {$niveau}: EARLY {$niveau}. Keep to the most frequent words and simplest structures of the level, short texts, one piece of information per item.",
        };
    }

    /**
     * La consigne d'une lecon selon sa place dans les etapes du niveau : les
     * premieres restent tres guidees, les suivantes demandent d'appliquer.
     */
    public static function consigneDeLecon(int $position, int $total, string $niveau): string
    {
        if ($total < 2) {
            return '';
        }

        $etape = $position + 1;
        $avancement = $position / ($total - 1);

        return match (true) {
            $avancement >= 0.66 => "\n\nPROGRESSION: this is lesson {$etape} of {$total} at {$niveau}, near the end of the level. The student already knows the basics of {$niveau}: use longer and more varied example sentences, combine this objective with structures from earlier lessons, and make the quiz ask the student to APPLY the rule in new sentences (no question answerable by recognising a word shown in the lesson).",
            $avancement >= 0.33 => "\n\nPROGRESSION: this is lesson {$etape} of {$total} at {$niveau}, in the middle of the level. Go beyond the simplest forms: varied examples, and at least two quiz items that require producing or transforming a sentence rather than recognising one.",
            default => "\n\nPROGRESSION: this is lesson {$etape} of {$total} at {$niveau}, at the start of the level. Keep it simple and guided, but the quiz must still check understanding, not just copying a word shown in the lesson.",
        };
    }

    /**
     * La tranche de la lecon dans son niveau, avec les memes bornes que
     * consigneDeLecon : elle sert de cle au cache des lecons, puisqu'une lecon de
     * debut et une lecon de fin de niveau ne sont pas ecrites pareil.
     */
    public static function trancheDeLecon(int $position, int $total): string
    {
        if ($total < 2) {
            return 'unique';
        }

        $avancement = $position / ($total - 1);

        return match (true) {
            $avancement >= 0.66 => 'fin',
            $avancement >= 0.33 => 'milieu',
            default => 'debut',
        };
    }
}
