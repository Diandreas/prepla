<?php

namespace App\Services\Center;

use App\Models\Classroom;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;

/**
 * Aggregated, class-level view: average accuracy, who's falling behind
 * (décrocheurs), and the weaknesses common to the class — built by summing each
 * student's existing diagnostic (UserError::categoryStats) and attempts.
 */
class ClassStatsService
{
    public function forClassroom(Classroom $classroom): array
    {
        $students = $classroom->students()->get();

        $perStudent = [];
        $commonWeaknesses = []; // category|subcategory => total count

        foreach ($students as $student) {
            $attempts = UserExerciseAttempt::where('user_id', $student->id);
            $count = (clone $attempts)->count();
            // Sans tentative notee, pas de moyenne : « 0 % » ferait croire a un
            // eleve qui rate tout.
            $moyenne = (clone $attempts)->avg('accuracy_percent');
            $avg = $moyenne !== null ? round((float) $moyenne, 1) : null;

            // La derniere activite, en ISO avec son fuseau. `value('created_at')`
            // rendait la chaine brute de la base, sans fuseau : un navigateur la
            // prenait pour de l'heure locale, et l'affichait decalee.
            $last = (clone $attempts)->latest()->value('created_at');

            $perStudent[] = [
                'user_id' => $student->id,
                'name' => $student->name,
                'attempts' => $count,
                'avg_accuracy' => $avg,
                'last_active' => $last ? \Illuminate\Support\Carbon::parse($last, 'UTC')->toIso8601String() : null,
                'temps_total_secondes' => (int) (clone $attempts)->sum('time_spent'),
            ];

            foreach (UserError::categoryStats($student->id) as $w) {
                $key = $w['error_category'] . ($w['subcategory'] ? '|' . $w['subcategory'] : '');
                $commonWeaknesses[$key] = ($commonWeaknesses[$key] ?? 0) + (int) $w['count'];
            }
        }

        // Class average over students who have attempts.
        $withAttempts = array_filter($perStudent, fn ($s) => $s['attempts'] > 0);
        $classAvg = ! empty($withAttempts)
            ? round(array_sum(array_column($withAttempts, 'avg_accuracy')) / count($withAttempts), 1)
            : null;

        // Décrocheurs: no activity in the last 7 days OR no attempts at all.
        // diffInDays is signed (negative for past dates) → use abs().
        $dropouts = array_values(array_filter($perStudent, function ($s) {
            if ($s['attempts'] === 0) {
                return true;
            }
            return $s['last_active'] && abs(now()->diffInDays($s['last_active'])) >= 7;
        }));

        // Top common weaknesses (sorted desc).
        arsort($commonWeaknesses);
        $topWeaknesses = [];
        foreach (array_slice($commonWeaknesses, 0, 8, true) as $key => $count) {
            [$cat, $sub] = array_pad(explode('|', $key), 2, null);
            $topWeaknesses[] = ['category' => $cat, 'subcategory' => $sub, 'count' => $count];
        }

        return [
            'student_count' => count($perStudent),
            'class_avg_accuracy' => $classAvg,
            'dropouts' => array_map(fn ($d) => ['user_id' => $d['user_id'], 'name' => $d['name'], 'attempts' => $d['attempts']], $dropouts),
            'common_weaknesses' => $topWeaknesses,
            'per_student' => $perStudent,
        ];
    }
}
