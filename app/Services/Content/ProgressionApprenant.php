<?php

namespace App\Services\Content;

use App\Models\MockExam;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;
use App\Models\UserLearningProgress;
use Illuminate\Support\Carbon;

/**
 * Le portrait d'un apprenant : où il en est, ce qu'il réussit, ce qui résiste,
 * et ce qu'il a de mieux à faire maintenant.
 *
 * Le tuteur savait seulement le niveau déclaré et l'examen visé. Il répondait
 * donc aux questions de langue, et à rien d'autre : « où j'en suis ? » ou
 * « je fais quoi maintenant ? » ne trouvaient personne en face. Un apprenant
 * seul devant une liste d'exercices abandonne, et c'est l'accompagnement qui
 * manquait, pas le contenu.
 *
 * Tout ce qui est renvoyé ici est MESURÉ. Quand une donnée manque — pas encore
 * d'exercice fait, pas de date d'examen — le champ est nul, et celui qui s'en
 * sert doit le dire plutôt que de combler le vide.
 */
class ProgressionApprenant
{
    /** En deçà, on ne tire aucune conclusion sur une compétence. */
    private const TENTATIVES_MINIMUM = 3;

    /** Une réussite sous ce seuil désigne une compétence à retravailler. */
    private const SEUIL_FRAGILE = 60;

    /** @return array<string, mixed> */
    public function portrait(User $user): array
    {
        $profil = $user->profile?->loadMissing('targetExam.language');

        return [
            'prenom' => trim(explode(' ', trim($user->name ?? ''))[0] ?? '') ?: null,
            'niveau' => $profil?->current_level,
            'examen_vise' => $profil?->targetExam?->name,
            'langue_etudiee' => $profil?->targetExam?->language?->name,
            'jours_avant_examen' => $this->joursAvantExamen($profil?->exam_date),
            'serie_en_cours' => (int) ($profil?->serieEffective() ?? 0),
            'xp_total' => (int) ($profil?->xp_total ?? 0),
            'activite' => $this->activite($user),
            'competences' => $this->competences($user),
            'erreurs_qui_resistent' => $this->erreursQuiResistent($user),
            'prochaine_etape' => $this->prochaineEtape($user, $profil),
        ];
    }

    private function joursAvantExamen($date): ?int
    {
        if (! $date) {
            return null;
        }
        $jours = (int) Carbon::parse($date)->startOfDay()->diffInDays(now()->startOfDay(), false);

        // Une date passée n'est plus une echeance : mieux vaut ne rien dire que
        // d'annoncer « dans -12 jours ».
        return $jours <= 0 ? abs($jours) : null;
    }

    /** @return array<string, mixed> */
    private function activite(User $user): array
    {
        $recentes = UserExerciseAttempt::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(7))->get();
        $toutes = UserExerciseAttempt::where('user_id', $user->id)->count();

        return [
            'exercices_en_tout' => $toutes,
            'exercices_7_derniers_jours' => $recentes->count(),
            'reussite_moyenne_7j' => $recentes->isEmpty() ? null
                : (int) round($recentes->avg('accuracy_percent')),
            // value() rend la colonne BRUTE, pas une date : l'appeler comme un
            // objet levait une erreur 500 des que l'apprenant avait fait un
            // exercice.
            'dernier_exercice' => UserExerciseAttempt::where('user_id', $user->id)
                ->latest('created_at')->first()?->created_at?->diffForHumans(),
        ];
    }

    /**
     * La réussite par compétence, et seulement là où il y a de quoi juger.
     *
     * @return array<string, mixed>
     */
    private function competences(User $user): array
    {
        $parCompetence = UserExerciseAttempt::where('user_exercise_attempts.user_id', $user->id)
            ->join('exercises', 'exercises.id', '=', 'user_exercise_attempts.exercise_id')
            ->join('exercise_types', 'exercise_types.id', '=', 'exercises.exercise_type_id')
            ->selectRaw('exercise_types.skill_type as competence, COUNT(*) as n, AVG(accuracy_percent) as moyenne')
            ->groupBy('exercise_types.skill_type')
            ->get();

        $mesurees = [];
        $fragiles = [];
        $solides = [];

        foreach ($parCompetence as $ligne) {
            if (! $ligne->competence) {
                continue;
            }
            $moyenne = (int) round((float) $ligne->moyenne);
            $mesurees[$ligne->competence] = ['exercices' => (int) $ligne->n, 'reussite' => $moyenne];

            // Trois exercices ne font pas une tendance : en dessous, on ne
            // qualifie rien, pour ne pas decourager sur un coup de malchance.
            if ((int) $ligne->n < self::TENTATIVES_MINIMUM) {
                continue;
            }
            $moyenne < self::SEUIL_FRAGILE
                ? $fragiles[] = $ligne->competence
                : $solides[] = $ligne->competence;
        }

        return [
            'detail' => $mesurees,
            'a_retravailler' => $fragiles,
            'bien_maitrisees' => $solides,
            'jamais_travaillees' => array_values(array_diff(
                ['reading', 'listening', 'writing', 'speaking'], array_keys($mesurees)
            )),
        ];
    }

    /**
     * Les familles d'erreurs encore non maîtrisées, les plus fréquentes d'abord.
     *
     * @return list<array{categorie: string, competence: ?string, occurrences: int}>
     */
    private function erreursQuiResistent(User $user): array
    {
        return UserError::where('user_id', $user->id)
            ->where('mastered', false)
            ->whereNotNull('error_category')
            ->selectRaw('error_category, skill_type, COUNT(*) as n')
            ->groupBy('error_category', 'skill_type')
            ->orderByDesc('n')
            ->limit(4)
            ->get()
            ->map(fn ($e) => [
                'categorie' => (string) $e->error_category,
                'competence' => $e->skill_type,
                'occurrences' => (int) $e->n,
            ])->all();
    }

    /**
     * Ce qu'il a de mieux à faire maintenant, et POURQUOI.
     *
     * L'ordre répond à ce qui aide vraiment : reprendre ce qui résiste avant
     * d'en empiler du nouveau ; finir une étape commencée plutôt que d'en
     * ouvrir une autre ; ne proposer l'épreuve blanche que quand elle existe
     * à son niveau.
     *
     * @return array{quoi: string, pourquoi: string, ou: ?string}
     */
    private function prochaineEtape(User $user, $profil): array
    {
        $enCours = UserLearningProgress::where('user_id', $user->id)
            ->where('status', 'in_progress')->latest('updated_at')->first();

        if ($enCours) {
            return [
                'quoi' => 'finir l’étape commencée dans ton parcours',
                'pourquoi' => 'une étape laissée à mi-chemin ne compte pas, et celle-ci est déjà à moitié faite',
                'ou' => 'Mon parcours',
            ];
        }

        $erreurs = UserError::where('user_id', $user->id)->where('mastered', false)->count();
        if ($erreurs >= 5) {
            return [
                'quoi' => 'reprendre tes erreurs',
                'pourquoi' => $erreurs.' erreurs ne sont pas encore maîtrisées : les revoir rapporte plus que de nouveaux exercices',
                'ou' => 'Rebondir après une erreur',
            ];
        }

        $fragiles = $this->competences($user)['a_retravailler'];
        if ($fragiles !== []) {
            return [
                'quoi' => 'travailler '.$this->enFrancais($fragiles[0]),
                'pourquoi' => 'c’est la compétence où ta réussite est la plus basse',
                'ou' => 'Pratiquer',
            ];
        }

        $jamais = $this->competences($user)['jamais_travaillees'];
        if ($jamais !== []) {
            return [
                'quoi' => 'essayer '.$this->enFrancais($jamais[0]),
                'pourquoi' => 'tu n’y as jamais touché, et elle compte à l’examen',
                'ou' => 'Pratiquer',
            ];
        }

        $epreuve = $profil?->target_exam_id && MockExam::where('is_published', true)->whereHas('exercises')
            ->whereHas('blueprint', fn ($q) => $q->where('exam_id', $profil->target_exam_id)
                ->where('level', $profil->current_level))->exists();

        if ($epreuve) {
            return [
                'quoi' => 'passer une épreuve blanche complète',
                'pourquoi' => 'tes compétences tiennent : il reste à tenir la durée et le format',
                'ou' => 'Pratiquer, épreuve blanche complète',
            ];
        }

        return [
            'quoi' => 'faire un exercice au format de l’examen',
            'pourquoi' => 'rien ne résiste pour l’instant : autant avancer sur le format',
            'ou' => 'Pratiquer',
        ];
    }

    private function enFrancais(string $competence): string
    {
        return match ($competence) {
            'reading' => 'la compréhension écrite',
            'listening' => 'la compréhension orale',
            'writing' => 'l’expression écrite',
            'speaking' => 'l’expression orale',
            'grammar' => 'les structures de la langue',
            default => $competence,
        };
    }
}
