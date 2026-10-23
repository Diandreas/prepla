<?php

namespace App\Services\Content;

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExerciseType;

/**
 * La structure officielle d'un examen, partie par partie, telle que le candidat
 * la rencontre le jour de l'epreuve.
 *
 * Un examen ne se presente pas comme une liste de formats d'exercices. telc
 * numerote ses parties — « Leseverstehen, Teil 1 », « Sprachbausteine, Teil 2 »
 * — chacune avec sa tache, son nombre d'items et ses points. C'est sous ces
 * noms que le candidat revise, et c'est ainsi que les sites de preparation les
 * presentent.
 *
 * La base connaissait deja cette structure : les configurations la decrivent
 * sous la cle « parts », et le seeder la recopie dans le plan de chaque niveau.
 * Mais personne ne la lisait. L'apprenant voyait donc nos noms de formats
 * internes — « matching-headings », « mcq-cloze » — au lieu des parties de son
 * examen.
 *
 * Les sections, elles, sont PARTAGEES entre les niveaux : une ligne par examen
 * et par slug. Les parties, non — le telc A1 n'a pas de Sprachbausteine et le
 * C2 a un module double. Elles se lisent donc dans le plan du niveau, comme les
 * durees.
 */
class StructureOfficielle
{
    /**
     * Les parties de cet examen a ce niveau, dans l'ordre de l'epreuve.
     *
     * Chaque partie porte son identite officielle et l'identifiant du type
     * d'exercice qui permet de s'y entrainer — ou null quand ce type n'est pas
     * installe, pour que l'appelant sache qu'il ne peut pas en proposer
     * l'entrainement plutot que de renvoyer un lien mort.
     *
     * @return list<array<string, mixed>>
     */
    public function parties(Exam $exam, ?string $niveau): array
    {
        $plan = $this->plan($exam, $niveau);
        if (! $plan) {
            return [];
        }

        $typesParSlug = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id))
            ->with('section:id,slug')
            ->get()
            ->groupBy(fn ($type) => $type->section?->slug.'/'.$type->slug);

        $parties = [];

        foreach ($plan->sections_config ?? [] as $section) {
            if (empty($section['slug']) || empty($section['parts'])) {
                continue;
            }

            foreach ($section['parts'] as $partie) {
                // Une ancienne configuration decrit ses parties sans leur donner
                // de type : elles restent affichables, mais pas jouables.
                $type = isset($partie['type'])
                    ? $typesParSlug->get($section['slug'].'/'.$partie['type'])?->first()
                    : null;

                $parties[] = [
                    'section_slug' => $section['slug'],
                    'section_name' => $section['name'] ?? $section['slug'],
                    'skill_type' => $section['skill_type'] ?? null,
                    'part' => $partie['part'] ?? null,
                    'name' => $partie['name'] ?? null,
                    'name_fr' => $partie['name_fr'] ?? ($partie['description'] ?? null),
                    'task' => $partie['task'] ?? ($partie['description'] ?? null),
                    'items' => $partie['items'] ?? null,
                    'item_range' => $partie['item_range'] ?? null,
                    'points' => $partie['points'] ?? null,
                    'source' => $partie['source'] ?? null,
                    'exercise_type_id' => $type?->id,
                ];
            }
        }

        return $parties;
    }

    /**
     * Les types d'exercices qui NE correspondent a aucune partie de l'epreuve a
     * ce niveau.
     *
     * Ce sont les exercices pedagogiques : utiles pour progresser, mais qui ne
     * reproduisent pas une tache de l'examen. Les melanger aux parties
     * officielles laissait croire au candidat qu'il s'entrainait au format alors
     * qu'il travaillait un exercice de notre invention.
     *
     * @return list<int>  identifiants des types d'exercices
     */
    public function typesPedagogiques(Exam $exam, ?string $niveau): array
    {
        $officiels = array_filter(array_column($this->parties($exam, $niveau), 'exercise_type_id'));

        $plan = $this->plan($exam, $niveau);
        $slugsDuNiveau = collect($plan?->sections_config ?? [])->pluck('slug')->filter()->all();

        return ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id)
            ->when($slugsDuNiveau, fn ($q) => $q->whereIn('slug', $slugsDuNiveau)))
            ->whereNotIn('id', $officiels ?: [0])
            ->pluck('id')
            ->all();
    }

    /** Ce niveau a-t-il une structure officielle decrite partie par partie ? */
    public function estDecrite(Exam $exam, ?string $niveau): bool
    {
        return $this->parties($exam, $niveau) !== [];
    }

    private function plan(Exam $exam, ?string $niveau): ?ExamBlueprint
    {
        if (! $niveau) {
            return null;
        }

        return ExamBlueprint::where('exam_id', $exam->id)->where('level', $niveau)->first();
    }
}
