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
    public function currentObjective(): ?array
    {
        $objectives = $this->objectives ?? [];
        return $objectives[$this->current_objective_index] ?? null;
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
