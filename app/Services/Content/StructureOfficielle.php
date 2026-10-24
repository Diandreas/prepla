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
    public function __construct(private ExerciseTypeSuitability $pertinence) {}

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

                // Un type peut exister sans convenir ENCORE au niveau : la
                // discussion academique du TOEFL se prepare a partir du B2. Le
                // lien renvoyait alors l'apprenant d'ou il venait, sans un mot.
                $raison = $type ? $this->pertinence->raison($type, $niveau) : null;

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
                    'exercise_type_id' => $raison ? null : $type?->id,
                    // Pourquoi cette partie ne s'ouvre pas encore, le cas echeant.
                    'raison' => $raison,
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

        // Les MODULES du niveau, qu'il decrive ou non ses parties : un apprenant
        // A1 ne doit pas se voir proposer les Sprachbausteine, meme quand
        // l'examen ne detaille pas les parties de son A1. C'est une question
        // differente de celle du plan qui porte la structure officielle.
        $plan = $niveau
            ? ExamBlueprint::where('exam_id', $exam->id)->where('level', $niveau)->first()
            : null;
        $plan = $plan ?: $this->plan($exam, $niveau);
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

/**
     * Ce plan decrit-il vraiment la structure de l'epreuve ?
     *
     * Il ne suffit pas qu'il porte des parties : d'anciennes configurations les
     * decrivaient sans leur donner de type, et les plans derives en ont garde
     * la copie. Une partie sans type ne s'ouvre pas — au TestDaF, le plan
     * derive du C1 passait devant l'epreuve reelle et ses sept taches orales
     * s'affichaient sans pouvoir etre travaillees.
     */
    private function decritSaStructure(ExamBlueprint $plan): bool
    {
        return collect($plan->sections_config ?? [])
            ->contains(fn ($section) => collect($section['parts'] ?? [])
                ->contains(fn ($partie) => is_array($partie) && ! empty($partie['type'])));
    }

    /**
     * Le plan qui decrit cet examen pour cet apprenant.
     *
     * Tous les examens ne s'inscrivent pas a un palier. Le TCF, le TEF, le
     * TestDaF et le TOEFL n'ont qu'une epreuve, dont le resultat situe le
     * candidat ; l'IELTS a deux versions, pas six niveaux. Leur plan porte donc
     * « level = null », et chercher le plan du niveau de l'apprenant n'en
     * trouvait aucun : leurs parties n'etaient jamais affichees, alors qu'elles
     * sont decrites.
     */
    private function plan(Exam $exam, ?string $niveau): ?ExamBlueprint
    {
        $plans = ExamBlueprint::where('exam_id', $exam->id);

        if ($niveau) {
            $duNiveau = (clone $plans)->where('level', $niveau)->first();

            // Un plan DERIVE — compose par nos soins pour un niveau que l'examen
            // ne decrit pas — ne porte aucune partie. S'en contenter masquait la
            // structure officielle : au TCF, le plan « entrainement niveau B1 »
            // passait devant l'epreuve reelle, et le candidat ne voyait rien.
            if ($duNiveau && $this->decritSaStructure($duNiveau)) {
                return $duNiveau;
            }
        }

        // Le repli ne vaut que pour les examens qui ne s'inscrivent a AUCUN
        // palier. Cambridge en declare, du A2 au C2 : un apprenant A1 ne doit
        // pas se voir proposer les parties d'une autre qualification, puisque
        // l'examen ne propose rien a son niveau.
        //
        // On ne compte que les plans qui DECRIVENT vraiment un niveau. Les plans
        // derives — composes par nos soins pour un palier que l'examen ignore —
        // n'en decrivent aucun : les prendre pour des niveaux declares privait
        // le TCF de toute structure, alors qu'il n'a qu'une epreuve.
        $declareDesNiveaux = (clone $plans)->whereNotNull('level')->get()->contains(fn ($plan) => $this->decritSaStructure($plan));

        if ($niveau && $declareDesNiveaux) {
            return null;
        }

        // Sinon : l'epreuve unique, ou la premiere variante. Les variantes d'un
        // meme examen partagent leurs sections, le choix est donc sans effet.
        return (clone $plans)->whereNull('level')->orderBy('id')->first();
    }
}
