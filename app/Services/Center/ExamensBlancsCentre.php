<?php

namespace App\Services\Center;

use App\Models\Assignment;
use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\Exercise;
use App\Models\LanguageCenter;
use App\Models\MockExam;
use App\Models\MockExamPassage;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Services\Content\StructureOfficielle;
use App\Services\ExerciseScoringService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Examen blanc compose par un centre.
 *
 * Le centre part de la structure officielle de l'examen (sections et parties,
 * niveau par niveau, jamais mutualisee), choisit pour chaque partie un
 * exercice du centre ou du catalogue PrePla de cet examen, et regle le
 * minutage section par section. L'eleve le passe dans le lecteur d'examen
 * blanc habituel ; on garde son score par section pour le centre.
 */
class ExamensBlancsCentre
{
    public function __construct(
        private StructureOfficielle $structure,
        private ExerciseScoringService $scoring,
    ) {}

    /**
     * Le plan de l'examen a ce niveau : celui du niveau s'il existe, sinon
     * l'epreuve unique (TCF, TEF, TOEFL... n'ont pas de paliers).
     */
    public function plan(Exam $exam, ?string $niveau): ?ExamBlueprint
    {
        $plans = ExamBlueprint::where('exam_id', $exam->id);

        return ($niveau ? (clone $plans)->where('level', $niveau)->first() : null)
            ?? (clone $plans)->whereNull('level')->orderBy('id')->first()
            ?? (clone $plans)->orderBy('id')->first();
    }

    /**
     * Sections et parties proposees, avec la duree officielle de chaque section.
     *
     * @return list<array{slug: string, nom: string, duree: int|null, parties: list<array<string, mixed>>}>
     */
    public function sections(Exam $exam, ?string $niveau): array
    {
        $plan = $this->plan($exam, $niveau);
        $config = collect($plan?->sections_config ?? [])->filter(fn ($s) => ! empty($s['slug']))->keyBy('slug');
        $parties = collect($this->structure->parties($exam, $niveau))->groupBy('section_slug');

        // Sans structure decrite, les sections de l'examen, une partie libre chacune.
        $slugs = $config->keys()->merge($parties->keys())->unique()->values();
        if ($slugs->isEmpty()) {
            $slugs = $exam->sections()->where('slug', '!=', 'level-assessment')->pluck('slug');
        }
        $nomsSections = $exam->sections()->pluck('name', 'slug');

        return $slugs->map(function (string $slug) use ($config, $parties, $nomsSections) {
            $s = $config->get($slug, []);
            $ses = $parties->get($slug, collect());

            return [
                'slug' => $slug,
                'nom' => $s['name'] ?? $nomsSections[$slug] ?? $slug,
                'duree' => isset($s['time_limit']) ? (int) $s['time_limit'] : (isset($s['duration']) ? (int) $s['duration'] : null),
                'parties' => $ses->isEmpty()
                    ? [['cle' => 'libre', 'libelle' => null, 'tache' => null, 'items' => null, 'exercise_type_id' => null]]
                    : $ses->values()->map(fn ($p, $i) => [
                        'cle' => (string) ($p['part'] ?? ($i + 1)),
                        'libelle' => $p['name_fr'] ?? $p['name'] ?? null,
                        'tache' => $p['task'] ?? null,
                        'items' => $p['items'] ?? null,
                        'exercise_type_id' => $p['exercise_type_id'] ?? null,
                    ])->all(),
            ];
        })->values()->all();
    }

    /**
     * Les exercices qu'un centre peut placer dans un examen blanc de cet examen :
     * les siens, et le catalogue PrePla en service. Jamais ceux d'un autre centre.
     */
    public function exercicesPermis(LanguageCenter $center, Exam $exam): Builder
    {
        return Exercise::query()
            ->where('exam_id', $exam->id)
            ->where(fn (Builder $q) => $q->where('center_id', $center->id)
                ->orWhere(fn (Builder $v) => $v->whereNull('center_id')->whereNull('lesson_id')
                    ->whereNull('node_id')->whereNull('mock_exam_id')->enService()));
    }

    /** Les exercices proposes a l'ecran : ceux du centre d'abord, puis le catalogue au niveau. */
    public function exercicesProposes(LanguageCenter $center, Exam $exam, ?string $niveau): Collection
    {
        $ligne = fn (Exercise $e) => FicheExercice::ligne($e) + ['exercise_type_id' => $e->exercise_type_id];

        $duCentre = Exercise::where('center_id', $center->id)->where('exam_id', $exam->id)
            ->with(FicheExercice::RELATIONS)->latest()->limit(200)->get();
        $catalogue = $this->exercicesPermis($center, $exam)->whereNull('center_id')
            ->when($niveau, fn (Builder $q) => $q->where('difficulty', $niveau))
            ->with(FicheExercice::RELATIONS)->limit(300)->get();

        return $duCentre->concat($catalogue)->map($ligne)->values();
    }

    /**
     * Verifie et range les exercices choisis.
     *
     * @param  list<array{exercise_id: int, section_slug: string, partie?: string|null}>  $choix
     */
    public function enregistrerItems(MockExam $mock, LanguageCenter $center, Exam $exam, array $choix): void
    {
        $ids = collect($choix)->pluck('exercise_id')->map(fn ($id) => (int) $id)->unique();
        $permis = $ids->isEmpty() ? collect() : $this->exercicesPermis($center, $exam)->whereKey($ids->all())->pluck('id');

        if ($permis->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'items' => "Un exercice choisi n'appartient ni à votre centre ni au catalogue PrePla de cet examen.",
            ]);
        }

        $mock->items()->delete();
        foreach (array_values($choix) as $i => $c) {
            $mock->items()->create([
                'exercise_id' => (int) $c['exercise_id'],
                'section_slug' => $c['section_slug'],
                'partie' => $c['partie'] ?? null,
                'sort_order' => $i,
            ]);
        }
    }

    /** Les exercices de l'examen blanc, dans l'ordre de l'epreuve. */
    public function exercicesDe(MockExam $mock): Collection
    {
        $mock->loadMissing('items');
        $exercices = Exercise::whereIn('id', $mock->items->pluck('exercise_id'))->with('exerciseType')->get()->keyBy('id');

        return $mock->items->map(fn ($item) => $exercices->get($item->exercise_id))->filter()->values();
    }

    /**
     * Le devoir publie, dans une classe de cet eleve, qui porte cet examen blanc.
     * null : il n'a pas a le passer.
     */
    public function devoirDeLEleve(User $user, MockExam $mock, ParcoursEleveDeCentre $parcours): ?Assignment
    {
        $ids = $parcours->devoirs($user)->pluck('id');

        return Assignment::whereIn('id', $ids)
            ->whereHas('items', fn ($q) => $q->where('itemable_type', MockExam::class)->where('itemable_id', $mock->id))
            ->orderByDesc('published_at')
            ->first();
    }

    /**
     * Corrige la copie : chaque exercice est note comme en simulation, une
     * partie laissee vide vaut zero, et le score se lit section par section.
     *
     * @param  array<int|string, mixed>  $reponsesParExercice
     */
    public function corriger(MockExam $mock, User $user, array $reponsesParExercice, int $tempsPasse, ?Assignment $devoir): MockExamPassage
    {
        $sectionDe = $mock->items()->pluck('section_slug', 'exercise_id');
        $parSection = [];
        $xp = 0;

        foreach ($this->exercicesDe($mock) as $exercice) {
            $source = $reponsesParExercice[$exercice->id] ?? [];
            $reponses = [];
            foreach ($exercice->questions ?? [] as $index => $question) {
                $qId = $question['id'] ?? (string) $index;
                if (is_array($source) && isset($source[$qId])) {
                    $reponses[$qId] = $source[$qId];
                }
            }

            $precision = 0;
            if ($reponses !== []) {
                $resultat = $this->scoring->score($exercice, $reponses);
                $precision = (int) round($resultat['accuracy']);
                $xp += (int) $resultat['xp'];

                UserExerciseAttempt::create([
                    'user_id' => $user->id,
                    'exercise_id' => $exercice->id,
                    'answers' => $reponses,
                    'score' => $resultat['score'],
                    'accuracy_percent' => $resultat['accuracy'],
                    'time_spent' => 0,
                    'xp_earned' => $resultat['xp'],
                    'feedback' => $resultat['feedback'],
                ]);
            }

            $parSection[$sectionDe[$exercice->id] ?? 'autre'][] = $precision;
        }

        $scores = collect($parSection)->map(fn ($v) => (int) round(array_sum($v) / max(count($v), 1)))->all();
        $toutes = collect($parSection)->flatten();

        if ($xp > 0 && $user->profile) {
            $user->profile->increment('xp_total', $xp);
        }

        return MockExamPassage::create([
            'mock_exam_id' => $mock->id,
            'user_id' => $user->id,
            'assignment_id' => $devoir?->id,
            'temps_passe' => $tempsPasse,
            'score' => $toutes->isEmpty() ? 0 : (int) round($toutes->avg()),
            'scores_sections' => $scores,
        ]);
    }
}
