<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumSkeleton extends Model
{
    protected static function booted(): void
    {
        static::retrieved(function (self $path): void {
            $objectives = $path->objectives ?? [];
            $changed = false;
            foreach ($objectives as &$objective) {
                if (($objective['status'] ?? '') === 'current_lesson') {
                    $objective['status'] = 'current';
                    $changed = true;
                }
            }
            unset($objective);
            if ($changed) {
                // Legacy lesson state is equivalent to current, never completed.
                $path->objectives = $objectives;
            }
        });
    }

    protected $fillable = [
        'user_id',
        'exam_id',
        'objectives',
        'current_objective_index',
        'consecutive_successes',
        'consecutive_failures',
    ];

    protected function casts(): array
    {
        return [
            'objectives' => 'array',
            'current_objective_index' => 'integer',
            'consecutive_successes' => 'integer',
            'consecutive_failures' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'user_id', 'user_id');
    }

    /**
     * Get the current macro objective.
     */
    /** L'échelle CEFR, du plus simple au plus avancé. */
    public const CEFR_LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    /**
     * Niveau visé par un objectif du parcours.
     *
     * Le tableau de bord créait chaque nœud de pratique au niveau 'A1' écrit en dur,
     * et les leçons reprenaient le niveau du profil — qui ne bouge jamais. Un
     * apprenant traversait donc tout son parcours, jusqu'aux objectifs les plus
     * avancés, en ne recevant que des exercices de débutant.
     *
     * Les parcours générés portent désormais leur niveau objectif par objectif. Pour
     * ceux qui existaient déjà, on le déduit de la position : un palier par tiers de
     * parcours, sans jamais dépasser deux crans au-dessus du niveau de départ.
     */
    public function levelForObjective(int $index, ?string $startLevel = null): string
    {
        $objectives = $this->objectives ?? [];
        $stored = $objectives[$index]['level'] ?? null;

        if (is_string($stored) && in_array($stored, self::CEFR_LEVELS, true)) {
            return $stored;
        }

        return self::levelForPosition($startLevel ?? 'A1', $index, count($objectives));
    }

    /**
     * Fige une bonne fois le niveau des objectifs qui n'en portent pas.
     *
     * La déduction dépend du niveau de départ : la recalculer à chaque fois ferait
     * glisser tous les niveaux dès que l'apprenant monte d'un cran, et « tous les
     * objectifs de mon niveau sont finis » ne serait jamais vrai. On l'écrit donc
     * une seule fois, au premier passage.
     */
    public function ensureObjectiveLevels(string $startLevel): void
    {
        $objectives = $this->objectives ?? [];
        $total = count($objectives);
        $changed = false;

        foreach ($objectives as $index => $objective) {
            $level = $objective['level'] ?? null;
            if (is_string($level) && in_array($level, self::CEFR_LEVELS, true)) {
                continue;
            }

            $objectives[$index]['level'] = self::levelForPosition($startLevel, $index, $total);
            $changed = true;
        }

        if ($changed) {
            $this->objectives = $objectives;
            $this->save();
        }
    }

    /** Un objectif qui est un examen de fin de niveau, pas une lecon a etudier. */
    public static function levelExamObjective(string $level, int $order): array
    {
        return [
            'order' => $order,
            'title' => "Examen de niveau {$level}",
            'concept' => 'level_exam.' . strtolower($level),
            'level' => $level,
            'status' => 'pending',
            'priority' => 'high',
            'is_level_exam' => true,
        ];
    }

    /**
     * Place un examen a la fin de chaque palier du parcours.
     *
     * Un niveau s'achevait sans rien pour le consolider : on enchainait sur le
     * palier suivant sans jamais verifier que le precedent tenait. La promotion
     * existait dans le code mais rien ne la declenchait, et la table des
     * evaluations est restee vide depuis l'ouverture.
     *
     * L'index courant est reporte : inserer devant lui le decalerait sur un autre
     * objectif que celui que l'apprenant a sous les yeux.
     */
    public function ensureLevelExams(): void
    {
        $objectives = $this->objectives ?? [];
        if ($objectives === []) {
            return;
        }

        $current = $this->current_objective_index;
        $rebuilt = [];
        $newCurrent = null;
        $inserted = 0;
        $lessonIndexMap = [];

        foreach ($objectives as $index => $objective) {
            $lessonIndexMap[$index] = count($rebuilt);
            if ($index === $current) {
                $newCurrent = count($rebuilt);
            }
            $rebuilt[] = $objective;

            if (($objective['is_level_exam'] ?? false) === true) {
                continue;
            }

            $level = $objective['level'] ?? null;
            $nextLevel = $objectives[$index + 1]['level'] ?? null;

            // Fin de palier : soit le niveau change juste apres, soit c'est la fin.
            if ($level === null || $level === $nextLevel) {
                continue;
            }

            $dejaPresent = ($objectives[$index + 1]['is_level_exam'] ?? false) === true;
            if ($dejaPresent) {
                continue;
            }

            $exam = self::levelExamObjective($level, count($rebuilt));
            // Un palier deja entierement termine garde son examen a passer : c'est
            // justement lui qui doit valider le niveau.
            $rebuilt[] = $exam;
            $inserted++;
        }

        if ($inserted === 0) {
            return;
        }

        foreach ($rebuilt as $index => $objective) {
            $rebuilt[$index]['order'] = $index;
        }

        $this->objectives = $rebuilt;
        $this->current_objective_index = $newCurrent ?? $current;
        $this->save();
        // Lessons carry an objective index too: keep their identity after insertion.
        $this->lessons()->get()->each(function (Lesson $lesson) use ($lessonIndexMap) {
            $oldIndex = $lesson->skeleton_objective_index;
            if ($oldIndex !== null && isset($lessonIndexMap[$oldIndex]) && $lessonIndexMap[$oldIndex] !== $oldIndex) {
                $lesson->update(['skeleton_objective_index' => $lessonIndexMap[$oldIndex]]);
            }
        });
    }

    /** L'examen de fin de palier a passer en premier, s'il en reste un. */
    public function pendingLevelExam(): ?array
    {
        foreach ($this->objectives ?? [] as $index => $objective) {
            if (($objective['is_level_exam'] ?? false) !== true) {
                continue;
            }
            if (($objective['status'] ?? 'pending') === 'done') {
                continue;
            }

            // Un examen ne s'ouvre que si tout son palier est termine.
            $level = $objective['level'] ?? null;
            $palierTenu = true;
            foreach ($this->objectives as $autreIndex => $autre) {
                if ($autreIndex >= $index || ($autre['level'] ?? null) !== $level) {
                    continue;
                }
                if (($autre['is_level_exam'] ?? false) === true) {
                    continue;
                }
                if (($autre['status'] ?? 'pending') !== 'done') {
                    $palierTenu = false;
                    break;
                }
            }

            // A later exam cannot bypass the first unfinished level.
            return $palierTenu ? $objective + ['index' => $index] : null;
        }

        return null;
    }

    /** Marque l'examen d'un palier comme passe. */
    public function completeLevelExam(string $level): void
    {
        $objectives = $this->objectives ?? [];
        $pending = $this->pendingLevelExam();
        if (!$pending || $pending['level'] !== $level) {
            return;
        }

        $objectives[$pending['index']]['status'] = 'done';
        if ($this->current_objective_index === $pending['index']) {
            foreach ($objectives as $index => $objective) {
                if (($objective['status'] ?? 'pending') === 'pending') {
                    $objectives[$index]['status'] = 'current';
                    $this->current_objective_index = $index;
                    break;
                }
            }
        }

        $this->objectives = $objectives;
        $this->save();
    }

    /** Déduction par position, pour les parcours construits avant que le niveau soit stocké. */
    public static function levelForPosition(string $startLevel, int $index, int $total): string
    {
        $start = array_search($startLevel, self::CEFR_LEVELS, true);
        if ($start === false) {
            $start = 0;
        }

        $step = (int) floor(($index * 3) / max(1, $total)); // 0, 1 ou 2

        return self::CEFR_LEVELS[min($start + $step, count(self::CEFR_LEVELS) - 1)];
    }

    public function currentObjective(): ?array
    {
        $objectives = $this->objectives ?? [];
        return $objectives[$this->current_objective_index] ?? null;
    }

    /**
     * Parcours entierement termine.
     *
     * currentObjective() renvoie l'objectif a l'index courant sans regarder son
     * statut : arrive au bout, il rendait donc un objectif deja termine, et
     * l'apprenant tournait en rond sur sa derniere lecon au lieu d'avancer.
     */
    public function isComplete(): bool
    {
        $objectives = $this->objectives ?? [];
        if ($objectives === []) {
            return false;
        }

        foreach ($objectives as $objective) {
            if (($objective['status'] ?? 'pending') !== 'done') {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all pending/current objectives.
     */
    public function remainingObjectives(): array
    {
        return collect($this->objectives ?? [])
            ->filter(fn ($o) => in_array($o['status'] ?? 'pending', ['pending', 'current', 'current_practice']))
            ->values()
            ->toArray();
    }

    /**
     * Mark the current objective's lesson as done and advance to the next objective.
     * The old objective is marked 'current_practice' so the practice phase can still be accessed.
     */
    public function advanceToPractice(): void
    {
        $objectives = $this->objectives;

        if (isset($objectives[$this->current_objective_index])) {
            $objectives[$this->current_objective_index]['status'] = 'current_practice';
        }

        // Advance to the next pending objective so the next lesson is different
        $nextIndex = null;
        for ($i = $this->current_objective_index + 1; $i < count($objectives); $i++) {
            if (in_array($objectives[$i]['status'] ?? 'pending', ['pending', 'current'])) {
                $nextIndex = $i;
                break;
            }
        }

        if ($nextIndex !== null) {
            $objectives[$nextIndex]['status'] = 'current';
            $this->current_objective_index = $nextIndex;
        }

        $this->objectives = $objectives;
        $this->save();
    }

    /**
     * Find the objective currently in its practice phase (status 'current_practice'),
     * regardless of where current_objective_index points — advanceToPractice() moves
     * the index forward to the next lesson as soon as the theory is done, so the
     * practice that's being completed is usually *behind* the current index.
     */
    public function practiceObjectiveIndex(): ?int
    {
        foreach (($this->objectives ?? []) as $i => $o) {
            if (($o['status'] ?? '') === 'current_practice') {
                return $i;
            }
        }
        return null;
    }

    /**
     * Mark a successfully-practiced objective as done. Pass the index returned by
     * practiceObjectiveIndex(). Ensures there is still a 'current' objective to work on.
     */
    public function completePractice(int $index): void
    {
        $objectives = $this->objectives;
        if (!isset($objectives[$index])) {
            return;
        }

        $objectives[$index]['status'] = 'done';

        // Make sure at least one objective is 'current' so the journey keeps moving.
        $hasCurrent = collect($objectives)->contains(fn ($o) => ($o['status'] ?? '') === 'current');
        if (!$hasCurrent) {
            foreach ($objectives as $i => $o) {
                if (($o['status'] ?? 'pending') === 'pending') {
                    $objectives[$i]['status'] = 'current';
                    $this->current_objective_index = $i;
                    break;
                }
            }
        }

        $this->objectives = $objectives;
        $this->consecutive_successes = 0;
        $this->consecutive_failures = 0;
        $this->save();
    }

    /**
     * Force-complete a practice objective the learner is stuck on after too many
     * consecutive failures (see NextLessonGenerator's stuck-objective handling).
     * Without this, consecutive_failures had no ceiling: a learner who never
     * clears the 60% mastery threshold on a concept could stay on it forever,
     * since the only exit was passing the threshold. Marks it 'done' (not
     * silently deleted) so progress/stats still reflect it was attempted.
     */
    public function forceCompleteStuckPractice(int $index): void
    {
        $this->completePractice($index);
    }

    /**
     * Mark the current objective as done and advance.
     */
    public function advanceToNextObjective(): void
    {
        $objectives = $this->objectives;

        if (isset($objectives[$this->current_objective_index])) {
            $objectives[$this->current_objective_index]['status'] = 'done';
        }

        // Find next pending
        $nextIndex = null;
        for ($i = $this->current_objective_index + 1; $i < count($objectives); $i++) {
            if (($objectives[$i]['status'] ?? 'pending') === 'pending') {
                $nextIndex = $i;
                break;
            }
        }

        if ($nextIndex !== null) {
            $objectives[$nextIndex]['status'] = 'current';
            $this->current_objective_index = $nextIndex;
        }

        $this->objectives = $objectives;
        $this->consecutive_successes = 0;
        $this->consecutive_failures = 0;
        $this->save();
    }

    /**
     * Skip ahead (for high performers) — advance by N objectives.
     */
    public function skipAhead(int $count = 1): void
    {
        $objectives = $this->objectives;

        // Mark current and skipped as done
        for ($skip = 0; $skip <= $count; $skip++) {
            $idx = $this->current_objective_index + $skip;
            if (isset($objectives[$idx])) {
                if (($objectives[$idx]['is_level_exam'] ?? false)
                    || ($objectives[$idx]['is_remedial'] ?? false)) {
                    return;
                }
                $objectives[$idx]['status'] = 'done';
            }
        }

        // Find next pending after skip
        $nextIndex = null;
        for ($i = $this->current_objective_index + $count + 1; $i < count($objectives); $i++) {
            if (($objectives[$i]['status'] ?? 'pending') === 'pending') {
                $nextIndex = $i;
                break;
            }
        }

        if ($nextIndex !== null) {
            $objectives[$nextIndex]['status'] = 'current';
            $this->current_objective_index = $nextIndex;
        }

        $this->objectives = $objectives;
        $this->consecutive_successes = 0;
        $this->save();
    }

    /**
     * Insert a new objective at a specific position.
     */
    public function insertObjective(array $objective, int $afterIndex): void
    {
        $objectives = $this->objectives;
        $objective['status'] = $objective['status'] ?? 'pending';
        $objective['priority'] = $objective['priority'] ?? 'normal';

        array_splice($objectives, $afterIndex + 1, 0, [$objective]);

        // Re-index order values
        foreach ($objectives as $i => &$o) {
            $o['order'] = $i;
        }

        $this->objectives = $objectives;

        // Adjust current index if insertion was before it
        if ($afterIndex < $this->current_objective_index) {
            $this->current_objective_index++;
        }

        $this->save();
        $this->lessons()
            ->where('skeleton_objective_index', '>=', $afterIndex + 1)
            ->increment('skeleton_objective_index');
    }
}
