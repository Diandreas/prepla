<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumSkeleton extends Model
{
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
    }
}
