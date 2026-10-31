<?php

namespace App\Services\Center;

use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserError;
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

    /**
     * Les faiblesses de l'apprenant, AVEC LEURS PREUVES.
     *
     * Le centre ne voyait que des comptes — « Grammar · Tense : 2 erreurs » —,
     * en anglais, sans rien pour juger. Un enseignant a besoin de voir la faute :
     * pour chaque faiblesse, les derniers extraits fautifs, leur correction et
     * l'explication donnee, avec la competence ou ils sont apparus.
     *
     * @return list<array<string, mixed>>
     */
    public function faiblesses(User $eleve): array
    {
        $erreurs = UserError::where('user_id', $eleve->id)
            ->where('mastered', false)
            ->whereNotNull('error_category')
            ->where('error_category', '!=', 'session_mistake')
            ->orderByDesc('updated_at')
            ->get(['error_category', 'subcategory', 'skill_type', 'question_text', 'user_answer', 'correct_answer', 'explanation', 'updated_at']);

        return $erreurs
            ->groupBy(fn (UserError $e) => $e->error_category.'|'.($e->subcategory ?? ''))
            ->map(fn ($groupe) => [
                'libelle' => self::libelleCategorie($groupe->first()->error_category, $groupe->first()->subcategory),
                'nombre' => $groupe->count(),
                'competences' => $groupe->pluck('skill_type')->filter()->unique()->values()->all(),
                'derniere' => $groupe->first()->updated_at?->toIso8601String(),
                'exemples' => $groupe->take(3)->map(fn (UserError $e) => [
                    'contexte' => $e->question_text,
                    'reponse' => $this->court($e->user_answer),
                    'correction' => $this->court($e->correct_answer),
                    'explication' => $e->explanation,
                    'competence' => $e->skill_type,
                ])->values()->all(),
            ])
            ->sortByDesc('nombre')
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * Le profil tire des productions recentes — redactions et prises de parole :
     * les tendances de fond, preuve a l'appui, qu'aucune faute isolee ne montre.
     *
     * @return list<array<string, mixed>>
     */
    public function profil(User $eleve): array
    {
        $observations = [];

        $tentatives = UserExerciseAttempt::where('user_id', $eleve->id)
            ->with(['exercise.exerciseType:id,name,skill_type'])
            ->latest()
            ->latest('id')
            ->take(40)
            ->get();

        foreach ($tentatives as $tentative) {
            foreach ((array) ($tentative->feedback ?? []) as $retour) {
                foreach ((array) ($retour['diagnostic'] ?? []) as $d) {
                    if (! is_array($d) || empty($d['observation'])) {
                        continue;
                    }
                    $observations[] = [
                        'dimension' => self::DIMENSIONS[$d['dimension'] ?? ''] ?? ($d['dimension'] ?? ''),
                        'observation' => (string) $d['observation'],
                        'gravite' => $d['severity'] ?? 'minor',
                        'competence' => $tentative->exercise?->exerciseType?->skill_type,
                        'exercice' => $tentative->exercise?->content['title'] ?? $tentative->exercise?->exerciseType?->name,
                        'tentative' => $tentative->id,
                        'date' => $tentative->created_at?->toIso8601String(),
                    ];
                }
            }

            if (count($observations) >= 12) {
                break;
            }
        }

        return array_slice($observations, 0, 12);
    }

    private function court(?string $texte): ?string
    {
        if ($texte === null) {
            return null;
        }

        return mb_strlen($texte) > 160 ? mb_substr($texte, 0, 157).'…' : $texte;
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
                    'explication' => $this->texteDExplication($retour['explanation'] ?? $question['explanation'] ?? null),
                    'repondue' => array_key_exists($id, $reponses),
                    'production' => $this->production($retour),
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
     * La correction complete d'une production — redaction ou prise de parole :
     * ce qu'un enseignant lit pour comprendre OU l'apprenant bute, pas seulement
     * s'il a reussi.
     *
     * @param  array<string, mixed>  $retour
     * @return array<string, mixed>|null  null pour une question a reponse fermee
     */
    private function production(array $retour): ?array
    {
        $corrections = is_array($retour['corrections'] ?? null) ? $retour['corrections'] : [];
        $diagnostic = is_array($retour['diagnostic'] ?? null) ? $retour['diagnostic'] : [];
        $criteres = is_array($retour['sub_scores'] ?? null) ? $retour['sub_scores'] : [];
        $transcription = is_string($retour['transcription'] ?? null) ? $retour['transcription'] : null;

        if ($corrections === [] && $diagnostic === [] && $criteres === [] && $transcription === null
            && empty($retour['covered_points']) && empty($retour['missing_points'])) {
            return null;
        }

        return [
            'transcription' => $transcription,
            'note' => isset($retour['band_score']) && is_numeric($retour['band_score']) ? (float) $retour['band_score'] : null,
            'criteres' => collect($criteres)
                ->filter(fn ($v) => is_numeric($v))
                ->map(fn ($v, $cle) => ['critere' => self::CRITERES[$cle] ?? $cle, 'note' => (float) $v])
                ->values()
                ->all(),
            'diagnostic' => collect($diagnostic)
                ->filter(fn ($d) => is_array($d) && ! empty($d['observation']))
                ->map(fn (array $d) => [
                    'dimension' => self::DIMENSIONS[$d['dimension'] ?? ''] ?? ($d['dimension'] ?? ''),
                    'observation' => (string) $d['observation'],
                    'gravite' => $d['severity'] ?? 'minor',
                ])
                ->values()
                ->all(),
            'corrections' => collect($corrections)
                ->filter(fn ($c) => is_array($c) && ! empty($c['original']))
                ->map(fn (array $c) => [
                    'avant' => (string) $c['original'],
                    'apres' => (string) ($c['corrected'] ?? ''),
                    'categorie' => self::libelleCategorie((string) ($c['category'] ?? '')),
                    'explication' => (string) ($c['explanation'] ?? ''),
                ])
                ->values()
                ->all(),
            'points_couverts' => array_values(array_filter((array) ($retour['covered_points'] ?? []), 'is_string')),
            'points_manquants' => array_values(array_filter((array) ($retour['missing_points'] ?? []), 'is_string')),
        ];
    }

    /**
     * L'explication en texte. A l'oral, l'IA rend un objet — concept, exemple,
     * conseil et leur traduction : on le lit en francais, sans quoi la page
     * affichait l'objet brut.
     */
    private function texteDExplication(mixed $explication): ?string
    {
        if (is_string($explication)) {
            return trim($explication) ?: null;
        }

        if (is_array($explication)) {
            $fr = is_array($explication['french_translation'] ?? null) ? $explication['french_translation'] : [];
            $parties = array_filter([
                $fr['concept'] ?? $explication['concept'] ?? null,
                $fr['hint'] ?? $explication['hint'] ?? null,
            ], fn ($p) => is_string($p) && trim($p) !== '');

            return $parties ? implode(' ', $parties) : null;
        }

        return null;
    }

    /** Les quatre dimensions de la note d'une redaction, en francais. */
    private const CRITERES = [
        'task_achievement' => 'Réalisation de la tâche',
        'coherence_cohesion' => 'Cohérence et cohésion',
        'lexical_resource' => 'Vocabulaire',
        'grammar_accuracy' => 'Correction grammaticale',
    ];

    /** Les dimensions du profil d'une production, en francais. */
    public const DIMENSIONS = [
        'lexical_range' => 'Étendue du vocabulaire',
        'lexical_precision' => 'Précision du vocabulaire',
        'syntactic_complexity' => 'Complexité des phrases',
        'grammatical_control' => 'Maîtrise grammaticale',
        'coherence' => 'Cohérence',
        'register' => 'Registre',
        'fluency' => 'Aisance',
        'task_fulfilment' => 'Réponse à la consigne',
    ];

    /** Les familles et sous-categories de faiblesse, en francais : « Grammar · Tense » ne parle a personne ici. */
    public const CATEGORIES = [
        'grammar' => 'Grammaire',
        'vocabulary' => 'Vocabulaire',
        'spelling' => 'Orthographe',
        'punctuation' => 'Ponctuation',
        'coherence' => 'Cohérence',
        'writing' => 'Écrit',
        'speaking' => 'Oral',
        'reading' => 'Compréhension écrite',
        'listening' => 'Compréhension orale',
    ];

    public const SOUS_CATEGORIES = [
        'tense' => 'temps',
        'agreement' => 'accords',
        'word-order' => 'ordre des mots',
        'article' => 'articles',
        'preposition' => 'prépositions',
        'modal' => 'verbes de modalité',
        'pronoun' => 'pronoms',
        'case' => 'déclinaison (cas)',
        'verb-form' => 'forme du verbe',
        'conjugation' => 'conjugaison',
        'negation' => 'négation',
        'subordination' => 'subordonnées',
        'lexical' => 'choix des mots',
        'register' => 'registre',
        'collocation' => 'collocations',
        'range' => 'étendue (répétitions)',
        'word-formation' => 'formation des mots',
        'false-friend' => 'faux amis',
        'accent' => 'accents',
        'capitalisation' => 'majuscules',
        'typo' => 'fautes de frappe',
        'connectors' => 'connecteurs',
        'structure' => 'structure',
        'reference' => 'reprises',
        'relevance' => 'pertinence',
        'fluency' => 'aisance',
        'completeness' => 'réponses incomplètes',
        'interaction' => 'interaction',
    ];

    public static function libelleCategorie(string $categorie, ?string $sous = null): string
    {
        [$famille, $depuisLaCle] = array_pad(explode('.', mb_strtolower(trim($categorie)), 2), 2, null);
        $sous = $sous ?? $depuisLaCle;

        $nom = self::CATEGORIES[$famille] ?? ($famille ? ucfirst($famille) : 'Autre');

        return $sous ? $nom.' · '.(self::SOUS_CATEGORIES[$sous] ?? str_replace('-', ' ', $sous)) : $nom;
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
