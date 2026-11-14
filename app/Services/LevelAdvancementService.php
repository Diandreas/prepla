<?php

namespace App\Services;

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\LevelAssessment;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;

class LevelAdvancementService
{
    private const CEFR_ORDER = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    /** Précision moyenne exigée sur les dernières séances pour monter d'un cran. */
    public const ADVANCE_THRESHOLD = 70;

    /** Séances de compréhension (lecture, écoute, grammaire...) exigées au niveau. */
    public const MIN_RECEPTION = 5;

    /** Productions écrites, et orales, exigées au niveau courant. */
    public const MIN_PRODUCTION = 2;

    /**
     * Moyenne exigée sur chaque production. Plus basse que la réception : une
     * rédaction notée 60 % au bon niveau est déjà une vraie réussite.
     */
    public const PRODUCTION_THRESHOLD = 60;

    /**
     * Monte l'apprenant d'un niveau quand il a bouclé tout son niveau courant.
     *
     * Jusqu'ici, rien n'appelait ce service : la table des évaluations est restée
     * vide depuis l'ouverture, et personne n'a jamais changé de niveau après son
     * test de positionnement. Un apprenant pouvait finir un parcours entier, des
     * pronoms de base à la fluidité avancée, en restant marqué A1.
     *
     * Règle retenue : tous les objectifs de son niveau sont terminés, et le
     * bilan du niveau (voir bilanDuNiveau) est complet : la compréhension tient
     * la moyenne, ET l'écrit ET l'oral ont été pratiqués au niveau avec un seuil
     * minimal. Une simple moyenne laissait monter quelqu'un qui n'avait jamais
     * écrit ni parlé à ce niveau.
     */
    public function assessAfterObjective(int $userId, CurriculumSkeleton $skeleton): ?string
    {
        $profile = UserProfile::where('user_id', $userId)->first();
        if (!$profile) {
            return null;
        }

        $currentLevel = $profile->current_level ?? 'A1';
        $nextLevel = $this->getNextLevel($currentLevel);
        if (!$nextLevel) {
            return null; // déjà au sommet de l'échelle
        }

        $objectives = $skeleton->objectives ?? [];
        $foundAtLevel = false;

        foreach ($objectives as $index => $objective) {
            if ($skeleton->levelForObjective($index, $currentLevel) !== $currentLevel) {
                continue;
            }

            $foundAtLevel = true;
            if (($objective['status'] ?? 'pending') !== 'done') {
                return null; // il reste du travail à ce niveau
            }
        }

        if (!$foundAtLevel) {
            return null;
        }

        $bilan = $this->bilanDuNiveau($userId, $skeleton->exam_id, $currentLevel);
        if (!$bilan['pret']) {
            return null;
        }

        LevelAssessment::create([
            'user_id' => $userId,
            'exam_id' => $skeleton->exam_id,
            'assessed_level' => $nextLevel,
            'previous_level' => $currentLevel,
            'assessment_type' => 'curriculum',
            'score_details' => [
                'average_accuracy' => $bilan['reception']['moyenne'],
                'threshold' => self::ADVANCE_THRESHOLD,
                'sessions_considered' => $bilan['reception']['seances'],
                'writing' => $bilan['ecrit'],
                'speaking' => $bilan['oral'],
            ],
            'assessed_at' => now(),
        ]);

        $profile->update(['current_level' => $nextLevel]);

        return $nextLevel;
    }

    /**
     * Ce que l'apprenant a montré à son niveau, compétence par compétence, et
     * ce qui lui manque encore pour monter (phrases prêtes à afficher).
     *
     * Seules comptent les séances faites à ce niveau et sur cet examen : les
     * exercices d'un niveau inférieur (repêchage du parcours, espace hors ligne)
     * tiraient la moyenne vers le haut et faisaient monter sur du plus facile.
     *
     * @return array{pret: bool, reception: array, ecrit: array, oral: array, manques: list<string>}
     */
    public function bilanDuNiveau(int $userId, int $examId, string $level): array
    {
        $seances = UserExerciseAttempt::query()
            ->where('user_exercise_attempts.user_id', $userId)
            ->join('exercises', 'exercises.id', '=', 'user_exercise_attempts.exercise_id')
            ->leftJoin('exercise_types', 'exercise_types.id', '=', 'exercises.exercise_type_id')
            ->where('exercises.difficulty', $level)
            ->where('exercises.exam_id', $examId)
            ->orderByDesc('user_exercise_attempts.created_at')
            ->orderByDesc('user_exercise_attempts.id')
            ->get(['user_exercise_attempts.accuracy_percent', 'exercise_types.skill_type']);

        // Lecture, ecoute, grammaire, vocabulaire : tout ce qui n'est pas une
        // production compte comme de la reception.
        $parFamille = $seances->groupBy(fn ($s) => match ($s->skill_type) {
            'writing' => 'ecrit',
            'speaking' => 'oral',
            default => 'reception',
        });

        $resume = function (string $famille, int $minimum, int $seuil, int $fenetre) use ($parFamille): array {
            $notes = ($parFamille[$famille] ?? collect())->take($fenetre)->pluck('accuracy_percent');
            $moyenne = $notes->isEmpty() ? null : round((float) $notes->avg(), 1);

            return [
                'seances' => $notes->count(),
                'moyenne' => $moyenne,
                'minimum' => $minimum,
                'seuil' => $seuil,
                'ok' => $notes->count() >= $minimum && $moyenne !== null && $moyenne >= $seuil,
            ];
        };

        $reception = $resume('reception', self::MIN_RECEPTION, self::ADVANCE_THRESHOLD, 20);
        $ecrit = $resume('ecrit', self::MIN_PRODUCTION, self::PRODUCTION_THRESHOLD, 10);
        $oral = $resume('oral', self::MIN_PRODUCTION, self::PRODUCTION_THRESHOLD, 10);

        $manques = [];
        foreach ([
            [$reception, 'exercices de compréhension'],
            [$ecrit, 'productions écrites'],
            [$oral, 'productions orales'],
        ] as [$partie, $nom]) {
            if ($partie['ok']) {
                continue;
            }
            if ($partie['seances'] < $partie['minimum']) {
                $reste = $partie['minimum'] - $partie['seances'];
                $manques[] = "Encore {$reste} {$nom} au niveau {$level} (minimum {$partie['minimum']}).";
            } else {
                $manques[] = "Tes {$nom} au niveau {$level} sont à {$partie['moyenne']} % en moyenne : il faut {$partie['seuil']} %.";
            }
        }

        return [
            'pret' => $manques === [],
            'reception' => $reception,
            'ecrit' => $ecrit,
            'oral' => $oral,
            'manques' => $manques,
        ];
    }

    public function assessAfterBossNode(int $userId, int $examId, float $score): ?string
    {
        $profile = UserProfile::where('user_id', $userId)->first();
        if (!$profile) {
            return null;
        }

        $currentLevel = $profile->current_level ?? 'A1';
        $threshold = self::ADVANCE_THRESHOLD;

        if ($score < $threshold) {
            return null;
        }

        $nextLevel = $this->getNextLevel($currentLevel);
        if (!$nextLevel) {
            return null; // Already at C2
        }

        // Record assessment
        LevelAssessment::create([
            'user_id' => $userId,
            'exam_id' => $examId,
            'assessed_level' => $nextLevel,
            'previous_level' => $currentLevel,
            'assessment_type' => 'boss_test',
            'score_details' => ['score' => $score, 'threshold' => $threshold],
            'assessed_at' => now(),
        ]);

        // Update user profile
        $profile->update(['current_level' => $nextLevel]);

        return $nextLevel;
    }

    public function shouldSuggestAssessment(int $userId, int $examId): bool
    {
        $recentAttempts = UserExerciseAttempt::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(30)
            ->pluck('accuracy_percent');

        if ($recentAttempts->count() < 10) {
            return false;
        }

        return $recentAttempts->avg() >= 85;
    }

    private function getNextLevel(string $current): ?string
    {
        $index = array_search($current, self::CEFR_ORDER);
        if ($index === false || $index >= count(self::CEFR_ORDER) - 1) {
            return null;
        }
        return self::CEFR_ORDER[$index + 1];
    }
}
