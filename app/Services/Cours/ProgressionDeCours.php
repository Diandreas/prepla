<?php

namespace App\Services\Cours;

use App\Models\CourseBlock;
use App\Models\CourseBlockProgress;
use App\Models\CoursePath;
use App\Models\CourseSession;
use App\Models\UserExerciseAttempt;
use Illuminate\Support\Collection;

/**
 * L'avancement des eleves dans un parcours, et ce qui leur est ouvert.
 *
 * - un bloc est fait quand l'eleve l'a termine ; un bloc « exercice » se
 *   deduit de ses tentatives (meilleure reussite >= seuil), comme l'ancien
 *   parcours d'etapes : la reprise des anciens parcours ne perd rien ;
 * - un quiz est fait quand son score atteint le seuil ;
 * - une seance est finie quand tous ses blocs obligatoires le sont ;
 * - une seance s'ouvre selon sa regle : libre, apres la seance obligatoire
 *   precedente, apres la reussite de ses quiz, ou a une date ; jamais avant
 *   sa date de debut. Une seance brouillon n'existe pas pour l'eleve.
 */
class ProgressionDeCours
{
    /** Les seances visibles, dans l'ordre du parcours (modules puis seances). */
    public function seances(CoursePath $parcours, bool $avecBrouillons = false): Collection
    {
        $parcours->loadMissing('modules.sessions.blocks');

        return $parcours->modules->flatMap(fn ($m) => $m->sessions)
            ->filter(fn (CourseSession $s) => $avecBrouillons || $s->estPubliee())
            ->values();
    }

    /**
     * Les blocs faits par chaque eleve.
     *
     * @param  Collection<int, CourseBlock>  $blocs
     * @param  Collection<int, int>  $eleves
     * @return array<int, array<int, array{fait: bool, score: ?int}>> eleve => bloc => etat
     */
    public function etatsDesBlocs(Collection $blocs, Collection $eleves): array
    {
        $etats = [];
        if ($blocs->isEmpty() || $eleves->isEmpty()) {
            return $eleves->mapWithKeys(fn ($id) => [$id => []])->all();
        }
        $progres = CourseBlockProgress::whereIn('course_block_id', $blocs->pluck('id'))->whereIn('user_id', $eleves)->get()
            ->groupBy('user_id');
        $exercices = $blocs->where('type', 'exercice')->map(fn ($b) => $b->contenu['exercise_id'] ?? null)->filter()->unique();
        $meilleures = $exercices->isEmpty() ? collect() : UserExerciseAttempt::query()
            ->whereIn('user_id', $eleves)->whereIn('exercise_id', $exercices)
            ->selectRaw('user_id, exercise_id, MAX(COALESCE(accuracy_percent, 0)) as meilleure')
            ->groupBy('user_id', 'exercise_id')->get()->groupBy('user_id');

        foreach ($eleves as $id) {
            $siens = ($progres->get($id) ?? collect())->keyBy('course_block_id');
            $tentatives = ($meilleures->get($id) ?? collect())->keyBy(fn ($t) => (int) $t->exercise_id);
            foreach ($blocs as $b) {
                $p = $siens->get($b->id);
                if ($b->type === 'exercice') {
                    $t = $tentatives->get((int) ($b->contenu['exercise_id'] ?? 0));
                    $seuil = $b->contenu['seuil'] ?? null;
                    $etats[$id][$b->id] = [
                        'fait' => $t !== null && ($seuil === null || (float) $t->meilleure >= $seuil),
                        'score' => $t !== null ? (int) round((float) $t->meilleure) : null,
                    ];

                    continue;
                }
                $etats[$id][$b->id] = ['fait' => $p?->termine_at !== null, 'score' => $p?->score];
            }
        }

        return $etats;
    }

    /** @param  array<int, array{fait: bool, score: ?int}>  $etats */
    public function seanceFinie(CourseSession $s, array $etats): bool
    {
        $blocs = $s->blocks->where('obligatoire', true);
        if ($blocs->isEmpty()) {
            $blocs = $s->blocks;
        }

        return $blocs->every(fn ($b) => $etats[$b->id]['fait'] ?? false);
    }

    /** Moyenne des quiz (et exercices) de la seance, ou null s'il n'y en a aucun de tente. */
    public function scoreSeance(CourseSession $s, array $etats): ?int
    {
        $scores = $s->blocks->whereIn('type', ['quiz', 'exercice'])
            ->map(fn ($b) => $etats[$b->id]['score'] ?? null)->filter(fn ($x) => $x !== null);

        return $scores->isEmpty() ? null : (int) round($scores->avg());
    }

    /**
     * Le parcours vu par un eleve : chaque seance avec son etat et la raison
     * d'un cadenas, la prochaine etape, la progression par module.
     */
    public function pourLEleve(CoursePath $parcours, int $eleveId, bool $apercu = false): array
    {
        $seances = $this->seances($parcours, $apercu);
        $blocs = $seances->flatMap(fn ($s) => $s->blocks);
        $etats = $this->etatsDesBlocs($blocs, collect([$eleveId]))[$eleveId] ?? [];
        $ouvertes = $this->ouvertures($seances, $etats);

        $prochaine = null;
        $modules = [];
        foreach ($parcours->modules as $module) {
            $siennes = $seances->filter(fn ($s) => $s->course_module_id === $module->id)->values();
            if ($siennes->isEmpty() && ! $apercu) {
                continue;
            }
            $lignes = $siennes->map(function (CourseSession $s) use ($etats, $ouvertes, &$prochaine) {
                $finie = $this->seanceFinie($s, $etats);
                [$ouverte, $raison] = $ouvertes[$s->id];
                $ligne = $this->decrireSeance($s) + [
                    'finie' => $finie,
                    'ouverte' => $ouverte,
                    'raison' => $raison,
                    'score' => $this->scoreSeance($s, $etats),
                    'blocs_faits' => $s->blocks->filter(fn ($b) => $etats[$b->id]['fait'] ?? false)->count(),
                    'en_retard' => ! $finie && $s->limite_at !== null && $s->limite_at->isPast(),
                ];
                if ($prochaine === null && $ouverte && ! $finie) {
                    $prochaine = $ligne;
                }

                return $ligne;
            })->all();
            $modules[] = [
                'id' => $module->id,
                'titre' => $module->titre,
                'description' => $module->description,
                'finies' => collect($lignes)->where('finie', true)->count(),
                'total' => count($lignes),
                'seances' => $lignes,
            ];
        }

        $total = $seances->count();
        $finies = collect($modules)->sum('finies');

        return [
            'id' => $parcours->id,
            'titre' => $parcours->titre,
            'description' => $parcours->description,
            'total' => $total,
            'finies' => $finies,
            'termine' => $total > 0 && $finies === $total,
            'prochaine' => $prochaine,
            'modules' => $modules,
        ];
    }

    /**
     * Ouverte ou non, et pourquoi, pour chaque seance.
     *
     * @return array<int, array{0: bool, 1: ?string}>
     */
    public function ouvertures(Collection $seances, array $etats): array
    {
        $resultat = [];
        $precedente = null; // derniere seance obligatoire rencontree
        foreach ($seances as $s) {
            $ouverte = true;
            $raison = null;
            if ($s->debut_at !== null && $s->debut_at->isFuture()) {
                $ouverte = false;
                $raison = ['debut', $s->debut_at->toIso8601String()];
            } elseif ($s->deblocage === 'date' && $s->deblocage_at !== null && $s->deblocage_at->isFuture()) {
                $ouverte = false;
                $raison = ['date', $s->deblocage_at->toIso8601String()];
            } elseif (in_array($s->deblocage, ['completion', 'quiz'], true) && $precedente !== null) {
                if (! $this->seanceFinie($precedente, $etats)) {
                    $ouverte = false;
                    $raison = ['completion', $precedente->titre];
                } elseif ($s->deblocage === 'quiz' && $s->seuil_quiz !== null) {
                    $score = $this->scoreSeance($precedente, $etats);
                    if ($score !== null && $score < $s->seuil_quiz) {
                        $ouverte = false;
                        $raison = ['quiz', $precedente->titre, $s->seuil_quiz];
                    }
                }
            }
            $resultat[$s->id] = [$ouverte, $raison === null ? null : ['code' => $raison[0], 'valeur' => $raison[1], 'seuil' => $raison[2] ?? null]];
            if ($s->obligatoire) {
                $precedente = $s;
            }
        }

        return $resultat;
    }

    /** « audio + texte, 12 min » : ce qui attend l'eleve dans la seance. */
    public function decrireSeance(CourseSession $s): array
    {
        $types = $s->blocks->map(fn (CourseBlock $b) => $b->type === 'audio' && ($b->contenu['mode_texte'] ?? 'aucun') !== 'aucun'
            ? 'audio_texte' : $b->type)->unique()->values()->all();

        return [
            'id' => $s->id,
            'titre' => $s->titre,
            'description' => $s->description,
            'obligatoire' => $s->obligatoire,
            'duree_minutes' => $s->duree_minutes,
            'types' => $types,
            'nb_blocs' => $s->blocks->count(),
            'debut_at' => $s->debut_at?->toIso8601String(),
            'limite_at' => $s->limite_at?->toIso8601String(),
            'brouillon' => ! $s->estPubliee(),
        ];
    }
}
