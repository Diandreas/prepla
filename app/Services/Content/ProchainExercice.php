<?php

namespace App\Services\Content;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;

/**
 * Ce que l'apprenant a de mieux à faire maintenant, et POURQUOI.
 *
 * La pratique libre tirait au hasard : elle ouvrait un exercice du type demandé,
 * sans jamais regarder ce que l'apprenant venait de rater. On pouvait manquer
 * dix fois le même accord, le onzième exercice n'en savait rien. C'est pourtant
 * le mode qu'utilise celui qui travaille SEUL — exactement celui qui n'a
 * personne pour lui dire quoi reprendre.
 *
 * Le parcours, lui, savait déjà s'adapter. Ce service apporte la même chose à
 * la pratique, et y ajoute ce qui manquait partout : une raison, dite à
 * l'apprenant. « Un exercice » ne donne envie à personne ; « on reprend les
 * accords, tu les as ratés quatre fois » est une étape dans une histoire.
 */
class ProchainExercice
{
    /** En deçà, une compétence n'a pas assez d'historique pour être jugée faible. */
    private const TENTATIVES_MINIMUM = 3;

    private const SEUIL_FRAGILE = 60;

    public function __construct(private ExerciseTypeSuitability $pertinence) {}

    /**
     * Le prochain exercice, avec sa raison d'être.
     *
     * @return array{type: ?ExerciseType, pourquoi: string, categorie: ?string, exercice: ?Exercise}
     */
    public function pour(User $user, Exam $exam, ?string $niveau = null): array
    {
        $niveau = $niveau ?: ($user->profile?->current_level ?: 'A1');
        $types = $this->typesOuvrables($exam, $niveau);

        if ($types->isEmpty()) {
            return $this->rien('Aucun format n’est encore disponible pour ton niveau.');
        }

        return $this->surUneErreurARevoir($user, $exam, $niveau, $types)
            ?? $this->surLaCompetenceLaPlusFaible($user, $exam, $niveau, $types)
            ?? $this->surUnFormatJamaisTente($user, $exam, $niveau, $types)
            ?? $this->auHasard($user, $exam, $niveau, $types);
    }

    /**
     * 1. Une erreur que la révision espacée réclame aujourd'hui.
     *
     * C'est le cas le plus utile : la mémoire est sur le point de lâcher, et
     * c'est le moment où reprendre rapporte le plus.
     */
    private function surUneErreurARevoir(User $user, Exam $exam, string $niveau, $types): ?array
    {
        $erreur = UserError::conceptDue($user->id)->orderBy('next_review_at')->first()
            ?? UserError::concept($user->id)->orderByDesc('created_at')->first();

        if (! $erreur || ! $erreur->error_category) {
            return null;
        }

        $type = $this->typePourLaCategorie($types, $erreur->error_category, $erreur->skill_type);
        if (! $type) {
            return null;
        }

        $quand = $erreur->created_at?->diffForHumans();

        return $this->avec($type, $user, $exam, $niveau, $erreur->error_category,
            'On reprend '.$this->enClair($erreur->error_category)
            .($quand ? ' — tu as buté dessus '.$quand.'.' : '.'));
    }

    /** 2. La compétence où la réussite est la plus basse. */
    private function surLaCompetenceLaPlusFaible(User $user, Exam $exam, string $niveau, $types): ?array
    {
        $faible = UserExerciseAttempt::where('user_exercise_attempts.user_id', $user->id)
            ->join('exercises', 'exercises.id', '=', 'user_exercise_attempts.exercise_id')
            ->join('exercise_types', 'exercise_types.id', '=', 'exercises.exercise_type_id')
            ->where('exercises.exam_id', $exam->id)
            ->selectRaw('exercise_types.skill_type as competence, COUNT(*) as n, AVG(accuracy_percent) as moyenne')
            ->groupBy('exercise_types.skill_type')
            ->havingRaw('COUNT(*) >= ?', [self::TENTATIVES_MINIMUM])
            ->orderBy('moyenne')
            ->first();

        if (! $faible || (float) $faible->moyenne >= self::SEUIL_FRAGILE) {
            return null;
        }

        $type = $types->firstWhere('skill_type', $faible->competence);
        if (! $type) {
            return null;
        }

        return $this->avec($type, $user, $exam, $niveau, null,
            'On travaille '.$this->enClair($faible->competence)
            .' — c’est là que tu réussis le moins ('.(int) round((float) $faible->moyenne).' %).');
    }

    /** 3. Un format jamais tenté : on ne laisse pas de trou avant l'examen. */
    private function surUnFormatJamaisTente(User $user, Exam $exam, string $niveau, $types): ?array
    {
        $tentes = UserExerciseAttempt::where('user_exercise_attempts.user_id', $user->id)
            ->join('exercises', 'exercises.id', '=', 'user_exercise_attempts.exercise_id')
            ->pluck('exercises.exercise_type_id')->unique();

        $type = $types->first(fn ($t) => ! $tentes->contains($t->id));
        if (! $type) {
            return null;
        }

        return $this->avec($type, $user, $exam, $niveau, null,
            'Tu n’as jamais fait ce format, et il tombe à l’examen.');
    }

    /** 4. Rien ne se distingue : on avance, simplement. */
    private function auHasard(User $user, Exam $exam, string $niveau, $types): array
    {
        return $this->avec($types->random(), $user, $exam, $niveau, null,
            'Rien ne résiste en ce moment : on continue d’avancer.');
    }

    /**
     * Les formats que l'examen propose ET qui conviennent au niveau.
     *
     * @return \Illuminate\Support\Collection<int, ExerciseType>
     */
    private function typesOuvrables(Exam $exam, string $niveau)
    {
        return ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id)
            ->where('slug', '!=', 'level-assessment'))
            ->get()
            ->filter(fn (ExerciseType $t) => $this->pertinence->convient($t, $niveau))
            ->values();
    }

    /** Le format qui travaille cette catégorie d'erreur, s'il en existe un. */
    private function typePourLaCategorie($types, string $categorie, ?string $competence): ?ExerciseType
    {
        $racine = explode('.', $categorie)[0];

        // La grammaire et le lexique ont leurs formats propres ; sinon on
        // retombe sur la compétence où la faute a été commise.
        $souhaites = match ($racine) {
            'grammar' => ['grammar-mcq', 'mcq-cloze', 'gap-fill', 'open-cloze', 'key-word-transformation', 'build-a-sentence'],
            'vocabulary' => ['word-formation', 'complete-the-words', 'summary-completion', 'gap-fill'],
            'spelling' => ['dictation', 'complete-the-words', 'gap-fill'],
            'punctuation', 'coherence' => ['gapped-text', 'ordering', 'guided-rewrite'],
            'writing' => ['short-writing', 'letter-email-writing', 'essay'],
            default => [],
        };

        foreach ($souhaites as $slug) {
            if ($trouve = $types->firstWhere('slug', $slug)) {
                return $trouve;
            }
        }

        return $competence ? $types->firstWhere('skill_type', $competence) : null;
    }

    /**
     * L'exercice lui-même, pris dans le vivier libre que l'apprenant n'a pas
     * encore fait. Null quand il n'y en a pas : l'appelant generera.
     */
    private function avec(ExerciseType $type, User $user, Exam $exam, string $niveau, ?string $categorie, string $pourquoi): array
    {
        $dejaFaits = UserExerciseAttempt::where('user_id', $user->id)->pluck('exercise_id');

        $exercice = Exercise::where('exam_id', $exam->id)
            ->where('exercise_type_id', $type->id)
            ->where('difficulty', $niveau)
            ->whereNull('center_id')->whereNull('lesson_id')
            ->whereNull('node_id')->whereNull('mock_exam_id')
            ->whereNotIn('id', $dejaFaits)
            ->inRandomOrder()
            ->first();

        return ['type' => $type, 'pourquoi' => $pourquoi, 'categorie' => $categorie, 'exercice' => $exercice];
    }

    /** @return array{type: null, pourquoi: string, categorie: null, exercice: null} */
    private function rien(string $pourquoi): array
    {
        return ['type' => null, 'pourquoi' => $pourquoi, 'categorie' => null, 'exercice' => null];
    }

    private function enClair(string $quoi): string
    {
        return match (explode('.', $quoi)[0]) {
            'grammar' => 'la grammaire',
            'vocabulary' => 'le vocabulaire',
            'spelling' => 'l’orthographe',
            'punctuation' => 'la ponctuation',
            'coherence' => 'l’enchaînement des idées',
            'writing' => 'l’expression écrite',
            'reading' => 'la compréhension écrite',
            'listening' => 'la compréhension orale',
            'speaking' => 'l’expression orale',
            default => $quoi,
        };
    }
}
