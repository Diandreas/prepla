<?php

namespace App\Services\Center;

use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * Ce qu'un centre sait du travail d'un apprenant : quoi, quand, a quelle heure,
 * combien de temps, avec quel resultat, et ce qu'il a repondu.
 *
 * Tout etait deja enregistre a chaque tentative — la date, le temps passe, les
 * reponses, la correction question par question. La fiche n'en montrait qu'un
 * type d'exercice et un pourcentage, sur les vingt dernieres tentatives : la
 * date partait meme vers la page sans y etre affichee. Un centre ne pouvait pas
 * dire a un parent quand son enfant avait travaille.
 *
 * L'HEURE. Les dates sont stockees en UTC. La page les envoie telles quelles et
 * le navigateur les affiche a l'heure de celui qui regarde. L'export CSV, lui,
 * est ecrit cote serveur : il prend le fuseau du centre (settings.fuseau), et le
 * nomme dans son en-tete pour qu'on ne puisse pas se tromper d'heure.
 */
class SuiviApprenant
{
    /** Le public vise : les candidats camerounais et africains. Un centre ailleurs regle le sien. */
    public const FUSEAU_PAR_DEFAUT = 'Africa/Douala';

    private const PAR_PAGE = 50;

    /** L'export se lit dans un tableur, par un centre francophone : pas de « reading ». */
    private const COMPETENCES = [
        'reading' => 'Lecture',
        'listening' => 'Écoute',
        'writing' => 'Écrit',
        'speaking' => 'Oral',
        'grammar' => 'Grammaire',
        'vocabulary' => 'Vocabulaire',
    ];

    public function fuseau(LanguageCenter $center): string
    {
        $fuseau = (string) ($center->settings['fuseau'] ?? '');

        return in_array($fuseau, \DateTimeZone::listIdentifiers(), true) ? $fuseau : self::FUSEAU_PAR_DEFAUT;
    }

    /**
     * Le resume en tete de fiche.
     *
     * @return array<string, mixed>
     */
    public function resume(User $eleve, string $fuseau): array
    {
        $tentatives = UserExerciseAttempt::where('user_id', $eleve->id);

        $derniere = (clone $tentatives)->max('created_at');
        $depuis = now($fuseau)->startOfDay()->subDays(29);

        // Les jours se comptent a l'heure du centre : une seance a 0 h 30 a Douala
        // tombe la veille en UTC, et ne doit pas etre rangee dans le mauvais jour.
        $parJour = (clone $tentatives)
            ->where('created_at', '>=', $depuis->copy()->utc())
            ->get(['created_at', 'time_spent'])
            ->groupBy(fn (UserExerciseAttempt $a) => $a->created_at->copy()->setTimezone($fuseau)->toDateString());

        $jours = [];
        for ($jour = $depuis->copy(); $jour->lte(now($fuseau)); $jour->addDay()) {
            $date = $jour->toDateString();
            $duJour = $parJour->get($date, collect());
            $jours[] = [
                'date' => $date,
                'exercices' => $duJour->count(),
                'minutes' => (int) round($duJour->sum('time_spent') / 60),
            ];
        }

        // Sans aucune tentative notee, il n'y a pas de moyenne : afficher « 0 % »
        // ferait croire a un eleve qui rate tout.
        $moyenne = (clone $tentatives)->avg('accuracy_percent');

        return [
            'derniere_activite' => $derniere ? Carbon::parse($derniere, 'UTC')->toIso8601String() : null,
            'tentatives' => (clone $tentatives)->count(),
            'precision_moyenne' => $moyenne !== null ? round((float) $moyenne, 1) : null,
            'temps_total_secondes' => (int) (clone $tentatives)->sum('time_spent'),
            'jours_actifs_30' => collect($jours)->where('exercices', '>', 0)->count(),
            'activite_30_jours' => $jours,
        ];
    }

    /** L'historique complet, le plus recent d'abord. */
    public function historique(User $eleve): LengthAwarePaginator
    {
        return UserExerciseAttempt::where('user_id', $eleve->id)
            ->with(['exercise.exerciseType:id,name,skill_type', 'exercise.exam:id,name'])
            ->latest()
            ->latest('id')
            ->paginate(self::PAR_PAGE)
            ->through(fn (UserExerciseAttempt $a) => $this->ligne($a));
    }

    /** @return array<string, mixed> */
    public function ligne(UserExerciseAttempt $tentative): array
    {
        $exercice = $tentative->exercise;
        [$justes, $total] = $this->compte($tentative);

        return [
            'id' => $tentative->id,
            // ISO avec le decalage : le navigateur l'affiche a l'heure locale.
            'date' => $tentative->created_at?->toIso8601String(),
            'exercice' => $exercice
                ? ($exercice->content['title'] ?? $exercice->exerciseType?->name ?? 'Exercice')
                : 'Exercice retiré',
            'examen' => $exercice?->exam?->name,
            'niveau' => $exercice?->difficulty,
            'partie' => $exercice?->content['exam_part_name'] ?? null,
            'competence' => $exercice?->exerciseType?->skill_type,
            'format' => $exercice?->exerciseType?->name,
            'justes' => $justes,
            'total' => $total,
            'precision' => $tentative->accuracy_percent !== null ? round((float) $tentative->accuracy_percent, 1) : null,
            'temps_secondes' => (int) $tentative->time_spent,
        ];
    }

    /**
     * Le detail d'une tentative : chaque question, ce que l'apprenant a repondu,
     * la bonne reponse et l'explication qu'il a recue.
     *
     * @return array<string, mixed>
     */
    public function detail(UserExerciseAttempt $tentative): array
    {
        $tentative->loadMissing(['exercise.exerciseType', 'exercise.exam']);
        $exercice = $tentative->exercise;
        $reponses = (array) ($tentative->answers ?? []);
        $retours = collect((array) ($tentative->feedback ?? []))->keyBy('question_id');

        $questions = collect($exercice?->questions ?? [])
            ->map(function (array $question) use ($reponses, $retours) {
                $id = (string) ($question['id'] ?? '');
                $retour = (array) ($retours->get($id) ?? []);

                return [
                    'id' => $id,
                    'texte' => $question['text'] ?? $question['prompt'] ?? null,
                    'options' => array_values((array) ($question['options'] ?? $question['answer_options'] ?? [])),
                    'reponse' => $reponses[$id] ?? null,
                    'bonne_reponse' => $retour['correct_answer'] ?? $question['correct_answer'] ?? $question['correct_answers'] ?? null,
                    'juste' => isset($retour['correct']) ? (bool) $retour['correct'] : null,
                    'precision' => $retour['accuracy'] ?? null,
                    'explication' => $retour['explanation'] ?? $question['explanation'] ?? null,
                    'repondue' => array_key_exists($id, $reponses),
                ];
            })
            // Une question retiree depuis, ou jamais presentee, n'a rien a montrer.
            ->filter(fn (array $q) => $q['repondue'] || $q['juste'] !== null)
            ->values()
            ->all();

        return $this->ligne($tentative) + [
            // Deux cas a ne pas confondre a l'ecran : l'exercice a ete retire, ou
            // il existe mais la tentative n'a enregistre aucune reponse.
            'exercice_disponible' => $exercice !== null,
            'consigne' => $exercice?->content['instructions'] ?? null,
            'texte' => $exercice?->content['passage'] ?? null,
            'questions' => $questions,
        ];
    }

    /**
     * L'historique complet en CSV, a l'heure du centre.
     *
     * @return \Generator<int, list<string|int|float|null>>
     */
    public function lignesCsv(User $eleve, string $fuseau): \Generator
    {
        yield ['Date', "Heure ({$fuseau})", 'Exercice', 'Examen', 'Niveau', 'Partie', 'Compétence', 'Bonnes réponses', 'Questions', 'Précision (%)', 'Temps passé (min)'];

        $requete = UserExerciseAttempt::where('user_id', $eleve->id)
            ->with(['exercise.exerciseType:id,name,skill_type', 'exercise.exam:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        foreach ($requete->lazy(200) as $tentative) {
            $l = $this->ligne($tentative);
            $quand = $tentative->created_at?->copy()->setTimezone($fuseau);

            yield [
                $quand?->format('Y-m-d'),
                $quand?->format('H:i'),
                $l['exercice'],
                $l['examen'],
                $l['niveau'],
                $l['partie'],
                self::COMPETENCES[$l['competence'] ?? ''] ?? $l['competence'],
                $l['justes'],
                $l['total'],
                $l['precision'],
                round($l['temps_secondes'] / 60, 1),
            ];
        }
    }

    /**
     * Bonnes reponses et nombre de questions, d'apres la correction enregistree.
     * Une question que l'apprenant n'a pas pu faire (panne de notre cote) sort
     * du compte, comme dans son propre resultat.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function compte(UserExerciseAttempt $tentative): array
    {
        $retours = collect((array) ($tentative->feedback ?? []))
            ->reject(fn ($r) => ! empty($r['technical_failure']));

        if ($retours->isEmpty()) {
            return [null, null];
        }

        return [$retours->where('correct', true)->count(), $retours->count()];
    }
}
