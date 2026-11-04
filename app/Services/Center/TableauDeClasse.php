<?php

namespace App\Services\Center;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Exercise;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Le tableau de bord d'une classe : chiffres de la semaine, activite des six
 * dernieres semaines, reussite par competence et classement des eleves.
 *
 * Seuls les eleves de CETTE classe comptent (classroom_user, role student).
 *
 * Le nombre de requetes ne depend pas du nombre d'eleves : les eleves, leurs
 * profils, puis des agregats groupes (par eleve, par competence, par semaine)
 * avec des sommes conditionnelles, enfin les devoirs echus et les tentatives
 * qui les concernent. Le SQL reste portable (SQLite des tests, MySQL).
 *
 * Les periodes sont glissantes : « cette semaine » = les 7 derniers jours,
 * comparee aux 7 jours d'avant ; « ce mois » = les 30 derniers jours, compare
 * aux 30 d'avant. Un mercredi, comparer trois jours a une semaine pleine ferait
 * croire a une chute.
 *
 * L'XP du classement est la somme de `xp_earned` des tentatives de la periode.
 * LeaderboardEntry n'a que des lignes hebdomadaires (ni mois, ni depuis le
 * debut) et xp_total du profil n'a pas de date : seules les tentatives donnent
 * l'XP, les exercices et la precision d'une meme periode, a la meme source.
 */
class TableauDeClasse
{
    /** Cle => duree en jours (null : depuis le debut). */
    public const PERIODES = ['semaine' => 7, 'mois' => 30, 'tout' => null];

    public const SEMAINES = 6;

    /** Les competences, dites comme a l'examen (memes libelles que le parcours). */
    public const COMPETENCES = ParcoursDeClasse::COMPETENCES;

    public function pour(Classroom $classroom): array
    {
        $maintenant = now();

        $eleves = $classroom->students()->orderBy('users.name')->get(['users.id', 'users.name']);
        $ids = $eleves->pluck('id')->all();

        $series = UserProfile::whereIn('user_id', $ids ?: [0])
            ->get(['user_id', 'streak_current', 'streak_last_date'])
            ->mapWithKeys(fn (UserProfile $p) => [$p->user_id => $p->serieEffective()]);

        $parEleve = $this->agregatsParEleve($ids, $maintenant);
        $semaines = $this->semaines($ids, $maintenant);

        $courante = end($semaines) ?: null;
        $precedente = count($semaines) > 1 ? $semaines[count($semaines) - 2] : null;

        return [
            'genere_le' => $maintenant->toIso8601String(),
            'kpis' => [
                'eleves_total' => count($ids),
                'eleves_actifs' => $courante['actifs'] ?? 0,
                'exercices' => $courante['exercices'] ?? 0,
                'exercices_avant' => $precedente['exercices'] ?? 0,
                'temps_secondes' => $courante['temps_secondes'] ?? 0,
                'temps_avant_secondes' => $precedente['temps_secondes'] ?? 0,
                'precision' => $courante['precision'] ?? null,
                'precision_avant' => $precedente['precision'] ?? null,
                'devoirs' => $this->devoirsATemps($classroom, $ids),
            ],
            'semaines' => $semaines,
            'competences' => $this->competences($ids, $maintenant),
            'classement' => $this->classements($eleves, $parEleve, $series),
        ];
    }

    /** Debut de la periode (et de la precedente), null pour « depuis le debut ». */
    private function bornes(?int $jours, Carbon $maintenant): ?array
    {
        if ($jours === null) {
            return null;
        }

        return [
            'debut' => $maintenant->copy()->subDays($jours)->toDateTimeString(),
            'avant' => $maintenant->copy()->subDays(2 * $jours)->toDateTimeString(),
        ];
    }

    /** Une requete : toutes les periodes de tous les eleves, groupees par eleve. */
    private function agregatsParEleve(array $ids, Carbon $maintenant): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $select = ['user_id', 'MAX(created_at) as derniere'];
        $bindings = [];

        foreach (self::PERIODES as $cle => $jours) {
            $b = $this->bornes($jours, $maintenant);
            if ($b === null) {
                $select[] = "COUNT(*) as {$cle}_n";
                $select[] = "COALESCE(SUM(xp_earned), 0) as {$cle}_xp";
                $select[] = "AVG(accuracy_percent) as {$cle}_prec";
                $select[] = "COUNT(DISTINCT DATE(created_at)) as {$cle}_jours";

                continue;
            }

            $dans = 'created_at >= ?';
            $avant = 'created_at >= ? AND created_at < ?';
            $select[] = "SUM(CASE WHEN {$dans} THEN 1 ELSE 0 END) as {$cle}_n";
            $select[] = "SUM(CASE WHEN {$dans} THEN COALESCE(xp_earned, 0) ELSE 0 END) as {$cle}_xp";
            $select[] = "AVG(CASE WHEN {$dans} THEN accuracy_percent END) as {$cle}_prec";
            $select[] = "COUNT(DISTINCT CASE WHEN {$dans} THEN DATE(created_at) END) as {$cle}_jours";
            $select[] = "AVG(CASE WHEN {$avant} THEN accuracy_percent END) as {$cle}_prec_avant";
            array_push($bindings, $b['debut'], $b['debut'], $b['debut'], $b['debut'], $b['avant'], $b['debut']);
        }

        return UserExerciseAttempt::query()
            ->whereIn('user_id', $ids)
            ->selectRaw(implode(', ', $select), $bindings)
            ->groupBy('user_id')
            ->toBase()
            ->get()
            ->keyBy('user_id');
    }

    /**
     * Les six dernieres semaines glissantes, de la plus ancienne a la courante :
     * exercices, precision, eleves actifs, temps. Une seule requete.
     */
    private function semaines(array $ids, Carbon $maintenant): array
    {
        $tranches = [];
        for ($i = self::SEMAINES - 1; $i >= 0; $i--) {
            $fin = $maintenant->copy()->subDays(7 * $i);
            $tranches[] = ['debut' => $fin->copy()->subDays(7), 'fin' => $fin];
        }

        $ligne = null;
        if (! empty($ids)) {
            $select = [];
            $bindings = [];
            foreach ($tranches as $k => $t) {
                // La tranche courante inclut l'instant present ; les autres
                // s'arretent juste avant le debut de la suivante.
                $dans = $k === count($tranches) - 1 ? 'created_at >= ?' : 'created_at >= ? AND created_at < ?';
                $b = $k === count($tranches) - 1
                    ? [$t['debut']->toDateTimeString()]
                    : [$t['debut']->toDateTimeString(), $t['fin']->toDateTimeString()];
                $select[] = "SUM(CASE WHEN {$dans} THEN 1 ELSE 0 END) as n{$k}";
                $select[] = "AVG(CASE WHEN {$dans} THEN accuracy_percent END) as p{$k}";
                $select[] = "COUNT(DISTINCT CASE WHEN {$dans} THEN user_id END) as a{$k}";
                $select[] = "SUM(CASE WHEN {$dans} THEN COALESCE(time_spent, 0) ELSE 0 END) as t{$k}";
                array_push($bindings, ...$b, ...$b, ...$b, ...$b);
            }

            $ligne = UserExerciseAttempt::query()
                ->whereIn('user_id', $ids)
                ->where('created_at', '>=', $tranches[0]['debut']->toDateTimeString())
                ->selectRaw(implode(', ', $select), $bindings)
                ->toBase()
                ->first();
        }

        return collect($tranches)->map(fn ($t, $k) => [
            'debut' => $t['debut']->toIso8601String(),
            'fin' => $t['fin']->toIso8601String(),
            'exercices' => (int) ($ligne->{"n{$k}"} ?? 0),
            'precision' => isset($ligne->{"p{$k}"}) ? round((float) $ligne->{"p{$k}"}, 1) : null,
            'actifs' => (int) ($ligne->{"a{$k}"} ?? 0),
            'temps_secondes' => (int) ($ligne->{"t{$k}"} ?? 0),
        ])->values()->all();
    }

    /** Reussite par competence, pour chaque periode. Une requete groupee par competence. */
    private function competences(array $ids, Carbon $maintenant): array
    {
        $vide = array_fill_keys(array_keys(self::PERIODES), []);
        if (empty($ids)) {
            return $vide;
        }

        $select = ['exercise_types.skill_type as competence'];
        $bindings = [];
        foreach (self::PERIODES as $cle => $jours) {
            $b = $this->bornes($jours, $maintenant);
            if ($b === null) {
                $select[] = "COUNT(*) as {$cle}_n";
                $select[] = "AVG(user_exercise_attempts.accuracy_percent) as {$cle}_prec";

                continue;
            }
            $dans = 'user_exercise_attempts.created_at >= ?';
            $select[] = "SUM(CASE WHEN {$dans} THEN 1 ELSE 0 END) as {$cle}_n";
            $select[] = "AVG(CASE WHEN {$dans} THEN user_exercise_attempts.accuracy_percent END) as {$cle}_prec";
            array_push($bindings, $b['debut'], $b['debut']);
        }

        $lignes = DB::table('user_exercise_attempts')
            ->join('exercises', 'exercises.id', '=', 'user_exercise_attempts.exercise_id')
            ->join('exercise_types', 'exercise_types.id', '=', 'exercises.exercise_type_id')
            ->whereIn('user_exercise_attempts.user_id', $ids)
            ->selectRaw(implode(', ', $select), $bindings)
            ->groupBy('exercise_types.skill_type')
            ->get();

        $ordre = array_keys(self::COMPETENCES);
        foreach (array_keys(self::PERIODES) as $cle) {
            $vide[$cle] = $lignes
                ->filter(fn ($l) => (int) $l->{"{$cle}_n"} > 0 && $l->{"{$cle}_prec"} !== null)
                ->map(fn ($l) => [
                    'cle' => (string) $l->competence,
                    'libelle' => self::COMPETENCES[$l->competence] ?? ucfirst((string) $l->competence),
                    'exercices' => (int) $l->{"{$cle}_n"},
                    'precision' => round((float) $l->{"{$cle}_prec"}, 1),
                ])
                ->sortBy(fn ($c) => ($i = array_search($c['cle'], $ordre, true)) === false ? 99 : $i)
                ->values()
                ->all();
        }

        return $vide;
    }

    /**
     * Les devoirs echus rendus a temps : pour chaque devoir publie dont
     * l'echeance est passee, un eleve l'a rendu a temps s'il a fait chacun de
     * ses exercices entre la publication et l'echeance (une echeance a minuit
     * vaut pour toute la journee, comme Assignment::depassee). Deux requetes.
     *
     * @return array{rendus:int, attendus:int, devoirs:int}|null  null si aucun devoir echu
     */
    private function devoirsATemps(Classroom $classroom, array $ids): ?array
    {
        $devoirs = $classroom->assignments()
            ->whereNotNull('published_at')
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now())
            ->with(['items' => fn ($q) => $q->where('itemable_type', Exercise::class)])
            ->get(['id', 'classroom_id', 'published_at', 'due_at'])
            ->filter(fn (Assignment $a) => Assignment::depassee($a->due_at) && $a->items->isNotEmpty())
            ->values();

        if ($devoirs->isEmpty()) {
            return null;
        }
        if (empty($ids)) {
            return ['rendus' => 0, 'attendus' => 0, 'devoirs' => $devoirs->count()];
        }

        $limite = fn (Assignment $a) => $a->due_at->format('H:i:s') === '00:00:00' ? $a->due_at->copy()->endOfDay() : $a->due_at;

        $tentatives = UserExerciseAttempt::query()
            ->whereIn('user_id', $ids)
            ->whereIn('exercise_id', $devoirs->flatMap(fn ($a) => $a->items->pluck('itemable_id'))->unique()->values())
            ->where('created_at', '>=', $devoirs->min('published_at'))
            ->where('created_at', '<=', $devoirs->map($limite)->max())
            ->toBase()
            ->get(['user_id', 'exercise_id', 'created_at'])
            ->groupBy('user_id');

        $rendus = 0;
        foreach ($devoirs as $devoir) {
            $exercices = $devoir->items->pluck('itemable_id')->map(fn ($id) => (int) $id)->unique();
            $fin = $limite($devoir);
            foreach ($ids as $id) {
                $faits = collect($tentatives->get($id, []))
                    ->filter(function ($t) use ($devoir, $fin) {
                        $quand = Carbon::parse($t->created_at);

                        return $quand->gte($devoir->published_at) && $quand->lte($fin);
                    })
                    ->pluck('exercise_id')->map(fn ($e) => (int) $e)->unique();
                if ($exercices->diff($faits)->isEmpty()) {
                    $rendus++;
                }
            }
        }

        return ['rendus' => $rendus, 'attendus' => $devoirs->count() * count($ids), 'devoirs' => $devoirs->count()];
    }

    /** Un classement par periode : XP, puis exercices, puis precision, puis nom. */
    private function classements(Collection $eleves, Collection $parEleve, Collection $series): array
    {
        $resultat = [];

        foreach (array_keys(self::PERIODES) as $cle) {
            $lignes = $eleves->map(function (User $u) use ($cle, $parEleve, $series) {
                $a = $parEleve->get($u->id);
                $prec = $a?->{"{$cle}_prec"};
                $precAvant = $cle === 'tout' ? null : $a?->{"{$cle}_prec_avant"};
                $precision = $prec !== null ? round((float) $prec, 1) : null;

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'xp' => (int) ($a?->{"{$cle}_xp"} ?? 0),
                    'exercices' => (int) ($a?->{"{$cle}_n"} ?? 0),
                    'precision' => $precision,
                    // En points de precision, contre la periode precedente de meme duree.
                    'progression' => $precision !== null && $precAvant !== null
                        ? round($precision - (float) $precAvant, 1)
                        : null,
                    'jours_actifs' => (int) ($a?->{"{$cle}_jours"} ?? 0),
                    'serie' => (int) ($series[$u->id] ?? 0),
                    'derniere_activite' => $a?->derniere ? Carbon::parse($a->derniere, 'UTC')->toIso8601String() : null,
                ];
            })->sort(function ($x, $y) {
                return [$y['xp'], $y['exercices'], $y['precision'] ?? -1] <=> [$x['xp'], $x['exercices'], $x['precision'] ?? -1]
                    ?: strcoll($x['name'], $y['name']);
            })->values();

            // Le rang : deux eleves a egalite parfaite partagent le meme.
            $rang = 0;
            $precedent = null;
            $lignes = $lignes->map(function ($l, $i) use (&$rang, &$precedent) {
                $cleTri = [$l['xp'], $l['exercices'], $l['precision']];
                if ($cleTri !== $precedent) {
                    $rang = $i + 1;
                    $precedent = $cleTri;
                }

                return ['rang' => $rang] + $l;
            })->all();

            // Le plus regulier : le plus de jours d'activite (au moins deux).
            $regulier = collect($lignes)->filter(fn ($l) => $l['jours_actifs'] >= 2)
                ->sort(fn ($x, $y) => [$y['jours_actifs'], $y['exercices']] <=> [$x['jours_actifs'], $x['exercices']])
                ->first();

            // La plus forte progression (au moins un point).
            $progression = collect($lignes)->filter(fn ($l) => ($l['progression'] ?? 0) >= 1)
                ->sortByDesc('progression')->first();

            $resultat[$cle] = [
                'eleves' => $lignes,
                'plus_regulier' => $regulier['id'] ?? null,
                'meilleure_progression' => $progression['id'] ?? null,
            ];
        }

        return $resultat;
    }
}
