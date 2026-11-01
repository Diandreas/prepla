<?php

namespace App\Support\Demo;

use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;
use App\Services\Content\ErreursDeRedaction;
use App\Services\ErrorSpacedRepetitionService;

/**
 * Applique le contenu de demonstration a des exercices et des tentatives qui
 * existent deja : partage par le generateur de la demo et par sa reparation,
 * pour que les deux ne divergent jamais.
 *
 * Il ne SUPPRIME rien. Il remplace le contenu d'un exercice, les reponses et la
 * correction d'une tentative, et retient les erreurs comme l'application le fait
 * pour un vrai apprenant : une par question ratee, une par faute de redaction.
 */
final class RemplisseurDeDemo
{
    public function __construct(
        private readonly ErreursDeRedaction $redactions,
        private readonly ErrorSpacedRepetitionService $sm2,
    ) {}

    /** Donne a l'exercice son vrai contenu, selon sa competence et son niveau. */
    public function exercice(Exercise $exercice): void
    {
        $competence = $this->competence($exercice);
        $modele = ContenuDeDemo::exercice($competence, (string) $exercice->difficulty);

        $exercice->update([
            'content' => array_merge($modele['content'], ['title' => $modele['title']]),
            'questions' => $modele['questions'],
            'exercise_type_id' => $this->typeQcm($exercice, $competence) ?? $exercice->exercise_type_id,
        ]);
        $exercice->unsetRelation('exerciseType');
    }

    /**
     * Une lecture ou une ecoute de demo est un QCM sur un texte. Rangee sous le
     * type « appariement », elle s'affichait « Associez ce terme » et le centre
     * lisait « Matching » sur la copie : elle prend le QCM de sa competence,
     * dans le meme examen, quand il existe.
     */
    private function typeQcm(Exercise $exercice, string $competence): ?int
    {
        if ($competence === 'writing' || $exercice->exerciseType?->component_key === 'mcq') {
            return null;
        }

        return ExerciseType::where('component_key', 'mcq')
            ->where('skill_type', $competence)
            ->whereHas('section', fn ($q) => $q->where('exam_id', $exercice->exam_id))
            ->orderBy('id')
            ->value('id');
    }

    /**
     * Donne a la tentative des reponses coherentes avec son score, la correction
     * que l'application aurait enregistree, et les erreurs qu'elle laisse.
     */
    public function tentative(UserExerciseAttempt $tentative, Exercise $exercice): void
    {
        $competence = $this->competence($exercice);
        $copie = ContenuDeDemo::tentative(
            $competence,
            (string) $exercice->difficulty,
            $exercice->questions ?? [],
            (float) $tentative->accuracy_percent,
            (int) $tentative->id,
        );

        $justes = collect($copie['feedback'])->where('correct', true)->count();
        $total = count($copie['feedback']);

        $tentative->update([
            'answers' => $copie['answers'],
            'feedback' => $copie['feedback'],
            'score' => $justes,
            // A une epreuve fermee, la precision est le rapport des bonnes reponses :
            // « 73 % » sur quatre questions ne pouvait correspondre a aucune copie.
            'accuracy_percent' => $competence === 'writing' || $total === 0
                ? $tentative->accuracy_percent
                : round($justes / $total * 100, 1),
        ]);

        $eleve = $tentative->user ?? User::find($tentative->user_id);
        if (! $eleve) {
            return;
        }

        if ($competence === 'writing') {
            // Comme pour un vrai apprenant : chaque faute de la copie devient une
            // faiblesse retenue, avec sa sous-categorie.
            $this->redactions->memoriser($eleve, $copie['corrections'], $copie['texte'], 'writing');

            return;
        }

        foreach ($copie['erreurs'] as $e) {
            $erreur = UserError::updateOrCreate(
                ['user_id' => $eleve->id, 'exercise_id' => $exercice->id, 'question_id' => $e['question_id']],
                [
                    'question_text' => $e['question_text'],
                    'user_answer' => $e['user_answer'],
                    'correct_answer' => $e['correct_answer'],
                    'explanation' => $e['explanation'],
                    'skill_type' => $competence,
                    'exercise_type_slug' => $exercice->exerciseType?->slug ?? 'mcq',
                    // Une erreur de comprehension porte sur un texte, pas sur une
                    // regle : elle se range sous sa competence, comme dans l'appli.
                    'error_category' => $competence,
                    'subcategory' => null,
                    'mastered' => false,
                ],
            );

            if ($erreur->wasRecentlyCreated) {
                $this->sm2->initializeForNewError($erreur);
                $erreur->forceFill(['created_at' => $tentative->created_at, 'updated_at' => $tentative->created_at])->saveQuietly();
            }
        }
    }

    /**
     * Une erreur fabriquee par l'ancienne demo — reponse « B », explication
     * passe-partout — reecrite en place d'apres le vrai contenu de son exercice.
     * Rien n'est supprime.
     */
    public function erreurFactice(UserError $erreur, Exercise $exercice): void
    {
        $competence = $this->competence($exercice);

        if ($competence === 'writing') {
            $fautes = ContenuDeDemo::fautesDeRedaction((string) $exercice->difficulty);
            if ($fautes === []) {
                return;
            }
            $faute = $fautes[crc32('erreur-'.$erreur->id) % count($fautes)];
            [$famille, $sous] = array_pad(explode('.', $faute['category'], 2), 2, null);

            $erreur->update([
                'question_text' => 'Dans ta rédaction : « '.$faute['original'].' »',
                'user_answer' => $faute['original'],
                'correct_answer' => $faute['corrected'],
                'explanation' => $faute['explanation'],
                'skill_type' => 'writing',
                'error_category' => $famille,
                'subcategory' => $sous,
            ]);

            return;
        }

        $q = collect($exercice->questions ?? [])->firstWhere('id', $erreur->question_id)
            ?? ($exercice->questions[0] ?? null);
        if (! $q) {
            return;
        }

        $lettres = array_map(fn ($k) => chr(65 + $k), array_keys($q['options']));
        $fausses = array_values(array_diff($lettres, [$q['correct_answer']]));

        $erreur->update([
            'question_text' => $q['text'],
            'user_answer' => $fausses[crc32('erreur-'.$erreur->id) % count($fausses)],
            'correct_answer' => $q['correct_answer'].') '.$q['options'][ord($q['correct_answer']) - 65],
            'explanation' => $q['explanation'],
            'skill_type' => $competence,
            'error_category' => $competence,
            'subcategory' => null,
        ]);
    }

    private function competence(Exercise $exercice): string
    {
        $competence = $exercice->exerciseType?->skill_type ?? 'reading';

        return in_array($competence, ['reading', 'listening', 'writing'], true) ? $competence : 'reading';
    }
}
