<?php

namespace App\Services\Center;

use App\Models\Assignment;
use App\Models\Exercise;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Les statistiques du tableau de bord du centre, de la fiche d'un eleve et de
 * la page Contenu.
 *
 * Tout se calcule sur une liste d'eleves deja bornee par l'appelant (les
 * classes de l'enseignant, ou tout le centre pour le responsable) : rien ici
 * ne lit un eleve hors de cette liste. Chaque bloc est UNE requete groupee,
 * quel que soit le nombre d'eleves.
 *
 * Les semaines sont glissantes (les 7 derniers jours, puis les 7 d'avant...),
 * comme le tableau d'une classe : un mercredi, comparer trois jours a une
 * semaine pleine ferait croire a une chute. Les jours, eux (calendrier, jour de
 * la semaine, heure), se lisent a l'heure du centre : une seance a 0 h 30 a
 * Douala tombe la veille en UTC.
 */
class StatistiquesCentre
{
    public const SEMAINES = 8;

    public const COMPETENCES = ParcoursDeClasse::COMPETENCES;

    public const JOURS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

    /**
     * Huit semaines glissantes, de la plus ancienne a la courante : exercices,
     * precision moyenne, eleves actifs, temps. Une requete.
     *
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    public function semaines(array $ids, ?Carbon $maintenant = null): array
    {
        $maintenant ??= now();
        $tranches = [];
        for ($i = self::SEMAINES - 1; $i >= 0; $i--) {
            $fin = $maintenant->copy()->subDays(7 * $i);
            $tranches[] = ['debut' => $fin->copy()->subDays(7), 'fin' => $fin];
        }

        $ligne = null;
        if ($ids !== []) {
            $select = [];
            $bindings = [];
            $derniere = count($tranches) - 1;
            foreach ($tranches as $k => $t) {
                $dans = $k === $derniere ? 'created_at >= ?' : 'created_at >= ? AND created_at < ?';
                $b = $k === $derniere
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
            'minutes' => (int) round(((int) ($ligne->{"t{$k}"} ?? 0)) / 60),
        ])->values()->all();
    }

    /**
     * La reussite par competence, en francais, dans l'ordre de l'examen. Une requete.
     *
     * @param  list<int>  $ids
     * @return list<array{cle:string, libelle:string, exercices:int, precision:float}>
     */
    public function competences(array $ids, ?Carbon $depuis = null): array
    {
        if ($ids === []) {
            return [];
        }

        $ordre = array_keys(self::COMPETENCES);

        return DB::table('user_exercise_attempts as t')
            ->join('exercises as e', 'e.id', '=', 't.exercise_id')
            ->join('exercise_types as y', 'y.id', '=', 'e.exercise_type_id')
            ->whereIn('t.user_id', $ids)
            ->whereNotNull('t.accuracy_percent')
            ->when($depuis, fn ($q) => $q->where('t.created_at', '>=', $depuis->toDateTimeString()))
            ->groupBy('y.skill_type')
            ->selectRaw('y.skill_type as competence, COUNT(*) as n, AVG(t.accuracy_percent) as p')
            ->get()
            ->filter(fn ($l) => $l->competence !== null)
            ->map(fn ($l) => [
                'cle' => (string) $l->competence,
                'libelle' => self::COMPETENCES[$l->competence] ?? ucfirst((string) $l->competence),
                'exercices' => (int) $l->n,
                'precision' => round((float) $l->p, 1),
            ])
            ->sortBy(fn ($c) => ($i = array_search($c['cle'], $ordre, true)) === false ? 99 : $i)
            ->values()
            ->all();
    }

    /**
     * L'activite de chaque classe : 7 derniers jours contre les 7 d'avant. Une
     * requete, groupee par classe.
     *
     * @param  list<int>  $classIds
     * @return array<int, array{exercices:int, avant:int, actifs:int, precision:float|null}>  par id de classe
     */
    public function activiteDesClasses(array $classIds, ?Carbon $maintenant = null): array
    {
        if ($classIds === []) {
            return [];
        }
        $maintenant ??= now();
        $debut = $maintenant->copy()->subDays(7)->toDateTimeString();
        $avant = $maintenant->copy()->subDays(14)->toDateTimeString();

        return DB::table('classroom_user as cu')
            ->join('user_exercise_attempts as t', 't.user_id', '=', 'cu.user_id')
            ->whereIn('cu.classroom_id', $classIds)
            ->where('cu.role_in_class', 'student')
            ->where('t.created_at', '>=', $avant)
            ->groupBy('cu.classroom_id')
            ->selectRaw(
                'cu.classroom_id as classe,
                SUM(CASE WHEN t.created_at >= ? THEN 1 ELSE 0 END) as n,
                SUM(CASE WHEN t.created_at < ? THEN 1 ELSE 0 END) as n_avant,
                COUNT(DISTINCT CASE WHEN t.created_at >= ? THEN t.user_id END) as actifs,
                AVG(CASE WHEN t.created_at >= ? THEN t.accuracy_percent END) as p',
                [$debut, $debut, $debut, $debut]
            )
            ->get()
            ->mapWithKeys(fn ($l) => [(int) $l->classe => [
                'exercices' => (int) $l->n,
                'avant' => (int) $l->n_avant,
                'actifs' => (int) $l->actifs,
                'precision' => $l->p !== null ? round((float) $l->p, 1) : null,
            ]])
            ->all();
    }

    /**
     * Quand on travaille : exercices par jour de la semaine (lundi d'abord) et
     * par heure, a l'heure du centre, sur huit semaines. Une requete.
     *
     * @param  list<int>  $ids
     * @return array{jours: list<array{jour:string, exercices:int}>, heures: list<int>, pic: array{debut:int, fin:int, part:int}|null}
     */
    public function rythme(array $ids, string $fuseau, ?Carbon $maintenant = null): array
    {
        $maintenant ??= now();
        $jours = array_fill(0, 7, 0);
        $heures = array_fill(0, 24, 0);

        if ($ids !== []) {
            $local = $this->local('created_at', $fuseau);
            $lignes = UserExerciseAttempt::query()
                ->whereIn('user_id', $ids)
                ->where('created_at', '>=', $maintenant->copy()->subDays(7 * self::SEMAINES)->toDateTimeString())
                ->selectRaw($this->jourSemaine($local).' as j, '.$this->heure($local).' as h, COUNT(*) as n')
                ->groupByRaw($this->jourSemaine($local).', '.$this->heure($local))
                ->toBase()
                ->get();

            foreach ($lignes as $l) {
                // 0 = dimanche cote SQL ; la page commence la semaine le lundi.
                $jours[((int) $l->j + 6) % 7] += (int) $l->n;
                $heures[(int) $l->h] += (int) $l->n;
            }
        }

        // Le creneau de deux heures le plus charge.
        $total = array_sum($heures);
        $pic = null;
        if ($total > 0) {
            $meilleur = 0;
            foreach (range(0, 23) as $h) {
                $n = $heures[$h] + $heures[($h + 1) % 24];
                if ($n > $meilleur) {
                    $meilleur = $n;
                    $pic = ['debut' => $h, 'fin' => ($h + 2) % 24, 'part' => (int) round($n / $total * 100)];
                }
            }
        }

        return [
            'jours' => array_map(fn ($nom, $n) => ['jour' => $nom, 'exercices' => $n], self::JOURS, $jours),
            'heures' => $heures,
            'pic' => $pic,
        ];
    }

    /**
     * Le calendrier de regularite d'un eleve : un carre par jour sur huit
     * semaines pleines (du lundi), a l'heure du centre. Une requete.
     *
     * @return list<array{date:string, exercices:int, minutes:int, futur:bool}>
     */
    public function calendrier(User $eleve, string $fuseau): array
    {
        $aujourdhui = now($fuseau)->startOfDay();
        $debut = $aujourdhui->copy()->startOfWeek(Carbon::MONDAY)->subWeeks(self::SEMAINES - 1);
        $local = $this->local('created_at', $fuseau);

        $parJour = UserExerciseAttempt::query()
            ->where('user_id', $eleve->id)
            ->where('created_at', '>=', $debut->copy()->utc()->toDateTimeString())
            ->selectRaw($this->date($local).' as d, COUNT(*) as n, COALESCE(SUM(time_spent), 0) as s')
            ->groupByRaw($this->date($local))
            ->toBase()
            ->get()
            ->keyBy('d');

        $jours = [];
        for ($jour = $debut->copy(), $i = 0; $i < 7 * self::SEMAINES; $i++, $jour->addDay()) {
            $date = $jour->toDateString();
            $l = $parJour->get($date);
            $jours[] = [
                'date' => $date,
                'exercices' => (int) ($l->n ?? 0),
                'minutes' => (int) round(((int) ($l->s ?? 0)) / 60),
                'futur' => $jour->gt($aujourdhui),
            ];
        }

        return $jours;
    }

    /**
     * Les devoirs rendus a temps parmi ceux dont l'echeance est tombee dans les
     * 7 derniers jours, et dans les 7 d'avant. Un eleve a rendu a temps s'il a
     * fait chaque exercice du devoir entre sa publication et son echeance (une
     * echeance a minuit vaut pour toute la journee). Quatre requetes.
     *
     * @param  list<int>  $classIds
     * @return array{rendus:int, attendus:int, rendus_avant:int, attendus_avant:int}
     */
    public function devoirsATemps(array $classIds, ?Carbon $maintenant = null): array
    {
        $maintenant ??= now();
        $vide = ['rendus' => 0, 'attendus' => 0, 'rendus_avant' => 0, 'attendus_avant' => 0];
        if ($classIds === []) {
            return $vide;
        }

        $devoirs = $this->devoirs($classIds)
            ->where('due_at', '>=', $maintenant->copy()->subDays(15))
            ->where('due_at', '<=', $maintenant)
            ->get(['id', 'classroom_id', 'published_at', 'due_at'])
            ->filter(fn (Assignment $a) => Assignment::depassee($a->due_at) && $a->items->isNotEmpty())
            ->filter(fn (Assignment $a) => self::limite($a)->gte($maintenant->copy()->subDays(14)))
            ->values();

        if ($devoirs->isEmpty()) {
            return $vide;
        }

        $eleves = DB::table('classroom_user')
            ->whereIn('classroom_id', $devoirs->pluck('classroom_id')->unique()->all())
            ->where('role_in_class', 'student')
            ->get(['classroom_id', 'user_id'])
            ->groupBy('classroom_id');
        $premieres = $this->premieresTentatives($devoirs->pluck('id')->all());
        $semaine = $maintenant->copy()->subDays(7);

        foreach ($devoirs as $devoir) {
            $cle = self::limite($devoir)->gte($semaine) ? '' : '_avant';
            foreach ($eleves->get($devoir->classroom_id, collect()) as $e) {
                $vide['attendus'.$cle]++;
                if ($this->statut($devoir, $premieres->get($devoir->id.'|'.$e->user_id, []), $maintenant) === 'a_temps') {
                    $vide['rendus'.$cle]++;
                }
            }
        }

        return $vide;
    }

    /**
     * Les devoirs d'un eleve dans les classes donnees : rendus a temps, rendus
     * en retard, en retard, a faire, et les derniers. Trois requetes.
     *
     * @param  list<int>  $classIds
     * @return array{a_temps:int, rendus_en_retard:int, en_retard:int, a_faire:int, derniers: list<array<string, mixed>>}
     */
    public function devoirsDeLEleve(User $eleve, array $classIds, ?Carbon $maintenant = null): array
    {
        $maintenant ??= now();
        $compte = ['a_temps' => 0, 'rendus_en_retard' => 0, 'en_retard' => 0, 'a_faire' => 0, 'derniers' => []];
        if ($classIds === []) {
            return $compte;
        }

        $devoirs = $this->devoirs($classIds)
            ->where('published_at', '<=', $maintenant)
            ->orderByDesc('published_at')
            ->get(['id', 'classroom_id', 'title', 'published_at', 'due_at'])
            ->filter(fn (Assignment $a) => $a->items->isNotEmpty())
            ->values();

        if ($devoirs->isEmpty()) {
            return $compte;
        }

        $premieres = $this->premieresTentatives($devoirs->pluck('id')->all(), $eleve->id);

        foreach ($devoirs as $devoir) {
            $statut = $this->statut($devoir, $premieres->get($devoir->id.'|'.$eleve->id, []), $maintenant);
            $compte[$statut]++;
            if (count($compte['derniers']) < 6) {
                $compte['derniers'][] = [
                    'id' => $devoir->id,
                    'titre' => $devoir->title,
                    'echeance' => $devoir->due_at?->toIso8601String(),
                    'statut' => $statut,
                ];
            }
        }

        return $compte;
    }

    /**
     * Pour chaque exercice : combien de fois joue, par combien d'eleves, avec
     * quelle reussite moyenne, par les seuls eleves donnes. Une requete.
     *
     * @param  list<int>  $exerciseIds
     * @param  list<int>  $ids
     * @return array<int, array{joue:int, eleves:int, reussite:float|null}>
     */
    public function parExercice(array $exerciseIds, array $ids): array
    {
        if ($exerciseIds === [] || $ids === []) {
            return [];
        }

        return UserExerciseAttempt::query()
            ->whereIn('exercise_id', $exerciseIds)
            ->whereIn('user_id', $ids)
            ->groupBy('exercise_id')
            ->selectRaw('exercise_id, COUNT(*) as n, COUNT(DISTINCT user_id) as eleves, AVG(accuracy_percent) as p')
            ->toBase()
            ->get()
            ->mapWithKeys(fn ($l) => [(int) $l->exercise_id => [
                'joue' => (int) $l->n,
                'eleves' => (int) $l->eleves,
                'reussite' => $l->p !== null ? round((float) $l->p, 1) : null,
            ]])
            ->all();
    }

    /** Les devoirs publies de ces classes, avec leurs seuls exercices. */
    private function devoirs(array $classIds)
    {
        return Assignment::query()
            ->whereIn('classroom_id', $classIds)
            ->whereNotNull('published_at')
            ->with(['items' => fn ($q) => $q->where('itemable_type', Exercise::class)->select(['id', 'assignment_id', 'itemable_id', 'itemable_type'])]);
    }

    public static function limite(Assignment $a): Carbon
    {
        return $a->due_at->format('H:i:s') === '00:00:00' ? $a->due_at->copy()->endOfDay() : $a->due_at->copy();
    }

    /**
     * La premiere tentative de chaque eleve sur chaque exercice de chaque
     * devoir, apres sa publication. Une requete.
     *
     * @return Collection<string, array<int, string>>  « devoir|eleve » => [exercice => date]
     */
    private function premieresTentatives(array $devoirIds, ?int $eleve = null): Collection
    {
        return DB::table('assignments as a')
            ->join('assignment_items as ai', function ($j) {
                $j->on('ai.assignment_id', '=', 'a.id')->where('ai.itemable_type', Exercise::class);
            })
            ->join('classroom_user as cu', function ($j) {
                $j->on('cu.classroom_id', '=', 'a.classroom_id')->where('cu.role_in_class', 'student');
            })
            ->join('user_exercise_attempts as t', function ($j) {
                $j->on('t.user_id', '=', 'cu.user_id')->on('t.exercise_id', '=', 'ai.itemable_id');
            })
            ->whereIn('a.id', $devoirIds)
            ->when($eleve, fn ($q) => $q->where('cu.user_id', $eleve))
            ->whereColumn('t.created_at', '>=', 'a.published_at')
            ->groupBy('a.id', 'cu.user_id', 'ai.itemable_id')
            ->selectRaw('a.id as devoir, cu.user_id as eleve, ai.itemable_id as exercice, MIN(t.created_at) as premiere')
            ->get()
            ->groupBy(fn ($l) => $l->devoir.'|'.$l->eleve)
            ->map(fn ($lignes) => $lignes->pluck('premiere', 'exercice')->all());
    }

    /** a_temps, rendus_en_retard, en_retard (echu, pas fini) ou a_faire. */
    private function statut(Assignment $devoir, array $premieres, Carbon $maintenant): string
    {
        $exercices = $devoir->items->pluck('itemable_id')->map(fn ($id) => (int) $id)->unique();
        $fini = $exercices->every(fn ($id) => isset($premieres[$id]));

        if ($fini) {
            if ($devoir->due_at === null) {
                return 'a_temps';
            }
            $limite = self::limite($devoir);
            $derniere = collect($premieres)->only($exercices->all())->map(fn ($d) => Carbon::parse($d))->max();

            return $derniere->lte($limite) ? 'a_temps' : 'rendus_en_retard';
        }

        return $devoir->due_at !== null && self::limite($devoir)->lt($maintenant) ? 'en_retard' : 'a_faire';
    }

    /** La colonne, decalee de l'UTC enregistre vers l'heure du centre (SQLite, MySQL, PostgreSQL). */
    private function local(string $colonne, string $fuseau): string
    {
        $decalage = now($fuseau)->getOffset() - now(config('app.timezone', 'UTC'))->getOffset();

        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "DATE_ADD({$colonne}, INTERVAL {$decalage} SECOND)",
            'pgsql' => "({$colonne} + INTERVAL '{$decalage} seconds')",
            default => "datetime({$colonne}, '".($decalage >= 0 ? '+' : '')."{$decalage} seconds')",
        };
    }

    private function jourSemaine(string $expr): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "(DAYOFWEEK({$expr}) - 1)",
            'pgsql' => "CAST(EXTRACT(DOW FROM {$expr}) AS INTEGER)",
            default => "CAST(strftime('%w', {$expr}) AS INTEGER)",
        };
    }

    private function heure(string $expr): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "HOUR({$expr})",
            'pgsql' => "CAST(EXTRACT(HOUR FROM {$expr}) AS INTEGER)",
            default => "CAST(strftime('%H', {$expr}) AS INTEGER)",
        };
    }

    private function date(string $expr): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "DATE({$expr})",
            'pgsql' => "CAST({$expr} AS DATE)",
            default => "date({$expr})",
        };
    }
}
