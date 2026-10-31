<?php

namespace App\Services\Content;

use App\Models\Exercise;
use Illuminate\Support\Collection;

/**
 * Les exercices qu'un visiteur peut essayer SANS COMPTE.
 *
 * L'essai sert a donner envie avant l'inscription : voir le vrai lecteur, le
 * vrai texte, la vraie correction. Mais un visiteur anonyme n'est pas un
 * apprenant, et trois choses ne doivent jamais lui etre ouvertes :
 *
 *  - un appel a l'IA. La correction d'une redaction ou d'un oral coute, et une
 *    reponse fausse sans explication ecrite en declenche une aussi. On n'ouvre
 *    donc que des formats a reponse fermee, et seulement quand CHAQUE question
 *    porte son explication ;
 *  - la synthese vocale, reservee aux comptes et payante. Une ecoute n'est
 *    ouverte que si son son est deja fabrique ;
 *  - un contenu qui ne nous appartient pas a montrer : epreuve d'un centre,
 *    exercice d'une lecon, sujet d'epreuve blanche. On ne prend que les
 *    contenus ecrits a la main par nous.
 *
 * La liste des formats est une LISTE D'AUTORISATION : un format nouveau reste
 * ferme tant que personne n'a verifie qu'il se corrige sans IA.
 */
class ExercicesDEssai
{
    /** Les composants qui se corrigent par comparaison, sans IA. */
    public const FORMATS_FERMES = [
        'mcq',
        'true-false-ng',
        'matching',
        'picture-mcq',
        'gap-fill',
        'open-cloze',
        'word-formation',
        'gapped-text',
        'multiple-matching',
        'form-completion',
        'summary-completion',
        'note-completion',
        'sentence-completion',
    ];

    /** Les deux bibliotheques ecrites a la main. */
    public const SOURCES = [
        'epreuve-verifiee' => true,   // au format officiel de l'epreuve
        'starter-library' => false,   // entrainement general, hors examen
    ];

    /** Ce qui trahirait la reponse si on l'envoyait au navigateur avant qu'il ait repondu. */
    private const CLES_DE_REPONSE = ['correct_answer', 'correct_answers', 'correct', 'accepted_answers', 'explanation'];

    /** Combien d'exercices on propose par examen : assez pour choisir, pas un catalogue. */
    private const PAR_EXAMEN = 4;

    public function estEssayable(Exercise $exercise): bool
    {
        $exercise->loadMissing('exerciseType');

        if ($exercise->is_ai_generated
            || $exercise->mock_exam_id !== null
            || $exercise->center_id !== null
            || $exercise->lesson_id !== null
            || $exercise->node_id !== null) {
            return false;
        }

        if (! array_key_exists($exercise->content['source'] ?? '', self::SOURCES)) {
            return false;
        }

        $type = $exercise->exerciseType;
        if (! $type || ! in_array($type->component_key, self::FORMATS_FERMES, true)) {
            return false;
        }

        $questions = $exercise->answerableQuestions();
        if ($questions === []) {
            return false;
        }

        // Une reponse fausse sans explication ecrite part a l'IA pour en obtenir
        // une : on ne laisse pas un visiteur anonyme la declencher.
        foreach ($questions as $question) {
            if (trim((string) ($question['explanation'] ?? '')) === '') {
                return false;
            }
        }

        if ($type->skill_type === 'listening' && ! $this->sonFabrique($exercise, $questions)) {
            return false;
        }

        return true;
    }

    /**
     * Ce qu'on propose, par langue puis par examen : les epreuves au format
     * officiel d'abord, puis l'entrainement, en variant les competences.
     *
     * @return list<array<string, mixed>>
     */
    public function catalogue(): array
    {
        $essayables = $this->candidats()->filter(fn (Exercise $e) => $this->estEssayable($e));

        return $essayables
            ->groupBy(fn (Exercise $e) => $e->exam->language->slug ?? 'autre')
            ->map(function (Collection $parLangue, string $langue) {
                return [
                    'slug' => $langue,
                    'nom' => $parLangue->first()->exam->language->name ?? $langue,
                    'examens' => $parLangue
                        ->groupBy(fn (Exercise $e) => $e->exam->slug)
                        ->map(fn (Collection $parExamen) => [
                            'slug' => $parExamen->first()->exam->slug,
                            'nom' => $parExamen->first()->exam->name,
                            'exercices' => $this->choisir($parExamen)
                                ->map(fn (Exercise $e) => $this->carte($e))
                                ->values()
                                ->all(),
                        ])
                        ->sortBy('nom')
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy(fn (array $langue) => array_search($langue['slug'], ['english', 'french', 'german'], true) ?? 99)
            ->values()
            ->all();
    }

    /**
     * L'exercice tel qu'on l'envoie au lecteur : SANS les reponses ni les
     * explications. Les composants ne s'en servent qu'en relecture ; le
     * visiteur les decouvre apres avoir repondu, comme un apprenant.
     */
    public function pourLeLecteur(Exercise $exercise): Exercise
    {
        $copie = $exercise->replicate();
        $copie->id = $exercise->id;
        $copie->exists = true;

        $copie->questions = array_map(
            fn (array $question) => array_diff_key($question, array_flip(self::CLES_DE_REPONSE)),
            $exercise->answerableQuestions(),
        );

        $copie->setRelation('exerciseType', $exercise->exerciseType);
        $copie->setRelation('exam', $exercise->exam);

        return $copie;
    }

    /** @return Collection<int, Exercise> */
    private function candidats(): Collection
    {
        return Exercise::query()
            ->with(['exerciseType.section', 'exam.language'])
            ->where('is_ai_generated', false)
            ->whereNull('mock_exam_id')
            ->whereNull('center_id')
            ->whereNull('lesson_id')
            ->whereNull('node_id')
            ->whereHas('exerciseType', fn ($q) => $q->whereIn('component_key', self::FORMATS_FERMES))
            ->orderBy('difficulty')
            ->orderBy('id')
            ->get();
    }

    /**
     * Quelques exercices par examen : l'officiel d'abord, une competence chacun
     * tant qu'on peut, du niveau le plus accessible au plus exigeant.
     *
     * @param  Collection<int, Exercise>  $exercices
     * @return Collection<int, Exercise>
     */
    private function choisir(Collection $exercices): Collection
    {
        $ordonnes = $exercices->sortBy([
            fn (Exercise $a, Exercise $b) => (int) $this->officiel($b) <=> (int) $this->officiel($a),
            fn (Exercise $a, Exercise $b) => strcmp((string) $a->difficulty, (string) $b->difficulty),
        ])->values();

        $retenus = collect();
        $competences = [];

        foreach ($ordonnes as $exercice) {
            $competence = $exercice->exerciseType->skill_type ?? '';
            if (! in_array($competence, $competences, true)) {
                $retenus->push($exercice);
                $competences[] = $competence;
            }
            if ($retenus->count() >= self::PAR_EXAMEN) {
                return $retenus;
            }
        }

        foreach ($ordonnes as $exercice) {
            if ($retenus->count() >= self::PAR_EXAMEN) {
                break;
            }
            if (! $retenus->contains('id', $exercice->id)) {
                $retenus->push($exercice);
            }
        }

        return $retenus;
    }

    /** @return array<string, mixed> */
    private function carte(Exercise $exercise): array
    {
        return [
            'id' => $exercise->id,
            'titre' => $exercise->content['title'] ?? $exercise->exerciseType->name,
            'partie' => $exercise->content['exam_part_name'] ?? null,
            'niveau' => $exercise->difficulty,
            'competence' => $exercise->exerciseType->skill_type,
            'questions' => count($exercise->answerableQuestions()),
            'officiel' => $this->officiel($exercise),
        ];
    }

    private function officiel(Exercise $exercise): bool
    {
        return self::SOURCES[$exercise->content['source'] ?? ''] ?? false;
    }

    /** @param  list<array<string, mixed>>  $questions */
    private function sonFabrique(Exercise $exercise, array $questions): bool
    {
        if (trim((string) ($exercise->content['audio_url'] ?? '')) !== '') {
            return true;
        }

        foreach ($questions as $question) {
            if (trim((string) ($question['audio_url'] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }
}
