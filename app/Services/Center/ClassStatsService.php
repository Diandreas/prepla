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
        $students = $classroom->students()->get(['users.id', 'users.name']);
        $ids = $students->pluck('id')->all();

        // Une requete groupee par eleve, au lieu de quatre requetes par eleve.
        $activite = empty($ids) ? collect() : UserExerciseAttempt::whereIn('user_id', $ids)
            ->selectRaw('user_id, count(*) as n, avg(accuracy_percent) as moyenne, max(created_at) as derniere, coalesce(sum(time_spent), 0) as temps')
            ->groupBy('user_id')
            ->toBase()
            ->get()
            ->keyBy('user_id');

        $perStudent = [];
        foreach ($students as $student) {
            $a = $activite->get($student->id);
            // Sans tentative notee, pas de moyenne : « 0 % » ferait croire a un
            // eleve qui rate tout.
            $avg = $a?->moyenne !== null ? round((float) $a->moyenne, 1) : null;

            // La derniere activite, en ISO avec son fuseau : la chaine brute de la
            // base, sans fuseau, etait prise pour de l'heure locale par un navigateur.
            $last = $a?->derniere;

            $perStudent[] = [
                'user_id' => $student->id,
                'name' => $student->name,
                'attempts' => (int) ($a?->n ?? 0),
                'avg_accuracy' => $avg,
                'last_active' => $last ? \Illuminate\Support\Carbon::parse($last, 'UTC')->toIso8601String() : null,
                'temps_total_secondes' => (int) ($a?->temps ?? 0),
            ];
        }

        // Les faiblesses de toute la classe en une requete : les memes filtres que
        // UserError::categoryStats, sommes sur les eleves de la classe.
        $commonWeaknesses = []; // category|subcategory => total count
        if (! empty($ids)) {
            $erreurs = UserError::whereIn('user_id', $ids)
                ->where('mastered', false)
                ->whereNotNull('error_category')
                ->where('error_category', '!=', 'session_mistake')
                ->selectRaw('error_category, subcategory, count(*) as count')
                ->groupBy('error_category', 'subcategory')
                ->toBase()
                ->get();
            foreach ($erreurs as $w) {
                $key = $w->error_category . ($w->subcategory ? '|' . $w->subcategory : '');
                $commonWeaknesses[$key] = ($commonWeaknesses[$key] ?? 0) + (int) $w->count;
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
            $topWeaknesses[] = [
                'category' => $cat,
                'subcategory' => $sub,
                // En francais, comme sur la fiche de l'apprenant : « Grammar · Tense »
                // ne parlait a personne dans un centre francophone.
                'libelle' => SuiviApprenant::libelleCategorie((string) $cat, $sub ?: null),
                'count' => $count,
            ];
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
