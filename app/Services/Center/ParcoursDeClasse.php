<?php

namespace App\Services\Center;

use App\Models\Classroom;
use App\Models\ClassroomPathStep;
use App\Models\Exercise;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Le parcours d'une classe, construit par son centre (« Parcours créé par le
 * centre », AContenuEleves).
 *
 * Une etape = un exercice, du centre ou du vivier PrePla de l'examen de la
 * classe. Jamais l'exercice prive d'un autre centre, ni un exercice de lecon,
 * de noeud ou d'epreuve blanche : ceux-la appartiennent a quelqu'un d'autre.
 *
 * La progression n'a pas de table : une etape est faite quand l'eleve a tente
 * son exercice (avec au moins seuil_reussite % de reussite si un seuil est
 * fixe). Un exercice deja fait avant d'entrer dans le parcours compte donc :
 * l'eleve n'a pas a refaire ce qu'il a deja reussi.
 */
class ParcoursDeClasse
{
    /** Les competences, dites comme a l'examen. */
    public const COMPETENCES = [
        'listening' => 'Compréhension orale',
        'reading' => 'Compréhension écrite',
        'writing' => 'Production écrite',
        'speaking' => 'Production orale',
        'grammar' => 'Grammaire',
        'vocabulary' => 'Vocabulaire',
    ];

    /** « B1 », « b1 », « B1.2 » ou « B1 intensif » donnent B1 ; le reste, rien. */
    public static function niveau(?string $niveau): ?string
    {
        $niveau = strtoupper(trim((string) $niveau));

        return preg_match('/^(A0|A1|A2|B1|B2|C1|C2)/', $niveau, $m) ? $m[1] : null;
    }

    /** L'examen de la classe : le sien, sinon celui du centre par defaut. */
    public function examen(Classroom $classe): ?int
    {
        return $classe->exam_id ?? $classe->center?->default_exam_id;
    }

    /**
     * Les exercices qu'on peut mettre dans le parcours de cette classe : ceux du
     * centre, et le vivier PrePla en service de l'examen de la classe.
     */
    public function exercicesAutorises(Classroom $classe): Builder
    {
        $examen = $this->examen($classe);

        return Exercise::query()->where(function (Builder $q) use ($classe, $examen) {
            $q->where(fn (Builder $centre) => $centre->where('center_id', $classe->center_id)->horsBrouillon())
                ->orWhere(fn (Builder $vivier) => $this->vivier($vivier, $examen));
        });
    }

    /** Le vivier PrePla d'un examen : ni centre, ni lecon, ni noeud, ni epreuve blanche. */
    private function vivier(Builder $q, ?int $examen): Builder
    {
        return $q->whereNull('center_id')->whereNull('lesson_id')
            ->whereNull('node_id')->whereNull('mock_exam_id')
            // Sans examen connu, pas de vivier : on ne devine pas.
            ->where('exam_id', $examen ?? 0);
    }

    /**
     * L'exercice a-t-il sa place dans le parcours de cette classe ? Vaut pour
     * l'ajout ET pour le jeu : une etape prise dans le vivier d'un ancien examen
     * de la classe, ou un exercice retire depuis, n'est plus servie a l'eleve.
     */
    public function peutAjouter(Classroom $classe, int $exerciceId): bool
    {
        return in_array($exerciceId, $this->jouables($classe, [$exerciceId]), true);
    }

    /**
     * Parmi ces exercices, ceux qui ont encore leur place dans le parcours de
     * la classe.
     *
     * @param  iterable<int, int>  $exercices
     * @return array<int, int>
     */
    public function jouables(Classroom $classe, iterable $exercices): array
    {
        $ids = collect($exercices)->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        // whereIn sur le numero : le scope « en service » ne s'applique pas a un
        // exercice designe par son numero ; on le reapplique a la main, pour le
        // vivier comme pour les exercices du centre.
        return $this->exercicesAutorises($classe)->whereIn('id', $ids)->enService()
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** Les etapes, dans l'ordre, avec leur exercice. */
    public function etapes(Classroom $classe): Collection
    {
        return $classe->pathSteps()->with('exercise.exerciseType:id,name,skill_type')->get();
    }

    /**
     * Les etapes que l'eleve suit : celles dont l'exercice a encore sa place
     * (examen actuel de la classe, exercice en service). Les autres restent
     * cote centre, signalees, pour que l'enseignant les retire ou les remplace.
     */
    public function etapesJouables(Classroom $classe, ?Collection $etapes = null): Collection
    {
        $etapes ??= $this->etapes($classe);
        $jouables = $this->jouables($classe, $etapes->pluck('exercise_id'));

        return $etapes->filter(fn (ClassroomPathStep $e) => in_array((int) $e->exercise_id, $jouables, true))->values();
    }

    /** Ce qu'on affiche d'une etape, cote centre comme cote eleve. */
    public function decrire(ClassroomPathStep $etape, int $numero, int $total): array
    {
        $exercice = $etape->exercise;
        $competence = $exercice?->exerciseType?->skill_type;

        return [
            'id' => $etape->id,
            'numero' => $numero,
            'total' => $total,
            'exercise_id' => $etape->exercise_id,
            'titre' => $this->titre($etape),
            'consigne' => $etape->consigne,
            'seuil_reussite' => $etape->seuil_reussite,
            'type' => $exercice?->exerciseType?->name ?? 'Exercice',
            'competence' => self::COMPETENCES[$competence] ?? null,
            'niveau' => $exercice?->difficulty,
            'source' => $exercice?->center_id ? 'centre' : 'prepla',
        ];
    }

    public function titre(ClassroomPathStep $etape): string
    {
        $exercice = $etape->exercise;

        return trim((string) $etape->titre) !== ''
            ? $etape->titre
            : (string) (($exercice?->content['title'] ?? null) ?: ($exercice?->exerciseType?->name ?? 'Exercice'));
    }

    /**
     * Les etapes faites par chacun de ces eleves.
     *
     * @param  Collection<int, int>  $eleves
     * @return Collection<int, Collection<int, int>> id de l'eleve => ids des etapes faites
     */
    public function etapesFaites(Collection $etapes, Collection $eleves): Collection
    {
        if ($etapes->isEmpty() || $eleves->isEmpty()) {
            return $eleves->mapWithKeys(fn ($id) => [$id => collect()]);
        }

        // Une seule requete : la meilleure reussite de chacun sur chaque exercice.
        $meilleures = UserExerciseAttempt::query()
            ->whereIn('user_id', $eleves)
            ->whereIn('exercise_id', $etapes->pluck('exercise_id')->unique())
            ->selectRaw('user_id, exercise_id, MAX(COALESCE(accuracy_percent, 0)) as meilleure')
            ->groupBy('user_id', 'exercise_id')
            ->get()
            ->groupBy('user_id');

        return $eleves->mapWithKeys(function ($id) use ($etapes, $meilleures) {
            $siennes = ($meilleures->get($id) ?? collect())->keyBy(fn ($t) => (int) $t->exercise_id);

            return [$id => $etapes->filter(function (ClassroomPathStep $etape) use ($siennes) {
                $tentative = $siennes->get($etape->exercise_id);

                return $tentative !== null
                    && ($etape->seuil_reussite === null || (float) $tentative->meilleure >= $etape->seuil_reussite);
            })->pluck('id')->values()];
        });
    }

    /**
     * Le parcours vu par l'eleve : chaque etape avec son etat (fait, en cours,
     * a venir). « En cours » est la premiere etape non faite.
     */
    public function pourLEleve(User $eleve, Classroom $classe): array
    {
        $etapes = $this->etapesJouables($classe);
        $faites = $this->etapesFaites($etapes, collect([$eleve->id]))->get($eleve->id, collect());
        $total = $etapes->count();
        $actuelle = $etapes->first(fn ($e) => ! $faites->contains($e->id));

        return [
            'total' => $total,
            'faites' => $faites->count(),
            'termine' => $total > 0 && $actuelle === null,
            'actuelle' => $actuelle ? $this->decrire($actuelle, $etapes->search($actuelle) + 1, $total) : null,
            'etapes' => $etapes->values()->map(fn ($e, $i) => $this->decrire($e, $i + 1, $total) + [
                'statut' => $faites->contains($e->id) ? 'fait' : ($actuelle?->id === $e->id ? 'en_cours' : 'a_venir'),
            ])->all(),
        ];
    }

    /** Cet exercice est-il une etape encore jouable du parcours de l'une de ces classes ? */
    public function exerciceDansLeParcours(Collection $classes, int $exerciceId): bool
    {
        if ($classes->isEmpty()) {
            return false;
        }

        return Classroom::whereIn('id', $classes)
            ->whereHas('pathSteps', fn ($q) => $q->where('exercise_id', $exerciceId))
            ->with('center')
            ->get()
            ->contains(fn (Classroom $c) => $this->peutAjouter($c, $exerciceId));
    }
}
