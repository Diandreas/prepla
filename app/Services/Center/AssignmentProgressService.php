<?php

namespace App\Services\Center;

use App\Models\Assignment;
use App\Models\Exercise;
use App\Models\UserExerciseAttempt;
use Illuminate\Support\Facades\DB;

/**
 * Derives each student's progress on an assignment WITHOUT a dedicated
 * completion table: an exercise item is "done" when the student has a
 * UserExerciseAttempt on it created after the assignment was published.
 */
class AssignmentProgressService
{
    /**
     * @return array<int, array{user_id:int,name:string,email:string,done:int,total:int,status:string,avg_accuracy:?float}>
     */
    public function perStudent(Assignment $assignment): array
    {
        $assignment->loadMissing(['classroom.students', 'items']);

        // Exercise items only (lessons aren't auto-tracked via attempts here).
        $exerciseIds = $assignment->items
            ->where('itemable_type', Exercise::class)
            ->pluck('itemable_id')
            ->all();

        $total = count($exerciseIds);
        $publishedAt = $assignment->published_at;
        $overdue = $assignment->isOverdue();

        $students = $assignment->classroom?->students ?? collect();

        // One query: attempts by these students on these exercises after publish.
        $studentIds = $students->pluck('id')->all();
        $attempts = collect();
        if ($total > 0 && ! empty($studentIds)) {
            $q = UserExerciseAttempt::whereIn('user_id', $studentIds)
                ->whereIn('exercise_id', $exerciseIds);
            if ($publishedAt) {
                $q->where('created_at', '>=', $publishedAt);
            }
            $attempts = $q->get(['user_id', 'exercise_id', 'accuracy_percent']);
        }

        $byUser = $attempts->groupBy('user_id');

        return $students->map(function ($student) use ($byUser, $total, $overdue) {
            $rows = $byUser->get($student->id, collect());
            $doneExerciseIds = $rows->pluck('exercise_id')->unique();
            $done = $doneExerciseIds->count();
            $avg = $rows->isNotEmpty() ? round((float) $rows->avg('accuracy_percent'), 1) : null;

            if ($total > 0 && $done >= $total) {
                $status = 'done';
            } elseif ($done > 0) {
                $status = $overdue ? 'late' : 'in_progress';
            } else {
                $status = $overdue ? 'late' : 'not_started';
            }

            return [
                'user_id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'done' => $done,
                'total' => $total,
                'status' => $status,
                'avg_accuracy' => $avg,
            ];
        })->values()->all();
    }

    /**
     * L'avancement de plusieurs devoirs d'un coup, pour leur liste : combien
     * d'eleves de la classe ont tout fini, combien ont commence. Deux requetes
     * quel que soit le nombre de devoirs (les eleves des classes, puis les
     * exercices faits agreges par devoir et par eleve) : jamais une par devoir.
     *
     * Meme regle que perStudent() : un exercice compte s'il a ete tente apres
     * la publication du devoir.
     *
     * @param  iterable<Assignment>  $assignments  avec exercise_items_count (withCount)
     * @return array<int, array{eleves:int, finis:int, commences:int}>  par id de devoir
     */
    public function syntheses(iterable $assignments): array
    {
        $assignments = collect($assignments);
        if ($assignments->isEmpty()) {
            return [];
        }

        $elevesParClasse = DB::table('classroom_user')
            ->whereIn('classroom_id', $assignments->pluck('classroom_id')->unique()->all())
            ->where('role_in_class', 'student')
            ->get(['classroom_id', 'user_id'])
            ->groupBy('classroom_id')
            ->map(fn ($lignes) => $lignes->count());

        $faits = DB::table('assignments as a')
            ->join('assignment_items as ai', function ($join) {
                $join->on('ai.assignment_id', '=', 'a.id')->where('ai.itemable_type', Exercise::class);
            })
            ->join('classroom_user as cu', function ($join) {
                $join->on('cu.classroom_id', '=', 'a.classroom_id')->where('cu.role_in_class', 'student');
            })
            ->join('user_exercise_attempts as t', function ($join) {
                $join->on('t.user_id', '=', 'cu.user_id')->on('t.exercise_id', '=', 'ai.itemable_id');
            })
            ->whereIn('a.id', $assignments->pluck('id')->all())
            ->where(fn ($q) => $q->whereNull('a.published_at')->orWhereColumn('t.created_at', '>=', 'a.published_at'))
            ->groupBy('a.id', 'cu.user_id')
            ->selectRaw('a.id as assignment_id, cu.user_id, COUNT(DISTINCT t.exercise_id) as faits')
            ->get()
            ->groupBy('assignment_id');

        return $assignments->mapWithKeys(function (Assignment $a) use ($elevesParClasse, $faits) {
            $total = (int) ($a->exercise_items_count ?? 0);
            $lignes = $faits->get($a->id, collect());
            $finis = $total > 0 ? $lignes->filter(fn ($l) => (int) $l->faits >= $total)->count() : 0;

            return [$a->id => [
                'eleves' => (int) $elevesParClasse->get($a->classroom_id, 0),
                'finis' => $finis,
                'commences' => $lignes->count() - $finis,
            ]];
        })->all();
    }
}
