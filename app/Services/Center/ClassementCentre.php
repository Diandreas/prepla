<?php

namespace App\Services\Center;

use App\Http\Controllers\Center\DashboardController;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ranger les classes et les eleves d'un centre : par langue, par niveau, par
 * salle, par statut.
 *
 * Un responsable ne voyait qu'une liste a plat, sans salle ni langue, et la
 * page des eleves comptait les exercices un eleve apres l'autre (une requete
 * par eleve). Tout est calcule ici, au meme endroit, pour que la page Eleves et
 * la repartition du tableau de bord rangent un eleve selon les memes regles.
 *
 * Le classement lui-meme (filtres, regroupements, export) se fait dans le
 * navigateur : resources/js/lib/classement-centre.js.
 */
class ClassementCentre
{
    /** La valeur « aucune » d'un critere (sans salle, sans niveau...). Meme cle cote navigateur. */
    public const SANS = '-';

    /** L'ordre des niveaux du CECRL : A1 avant A2, jamais l'ordre alphabetique seul. */
    public const ORDRE_NIVEAUX = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    /**
     * Les noms des langues en francais. La table languages porte des noms
     * melanges (« English », « Français », « Allemand ») : l'espace centre est
     * en francais, il les affiche tous en francais.
     */
    private const NOMS_DES_LANGUES = [
        'english' => 'Anglais',
        'french' => 'Français',
        'german' => 'Allemand',
    ];

    /** Les classes du centre que l'acteur peut voir : toutes pour le responsable, les siennes pour un enseignant. */
    public function classesVisibles(LanguageCenter $center, User $actor)
    {
        return Classroom::where('center_id', $center->id)
            ->whereNull('archived_at')
            ->when(
                ! $actor->centerRoleIs('center_admin'),
                fn ($q) => $q->whereHas('teachers', fn ($t) => $t->whereKey($actor->id))
            );
    }

    /** La langue d'une classe : celle de son examen, a defaut celle de l'examen par defaut du centre. */
    public function langueDeLaClasse(Classroom $classroom, ?Exam $examenParDefaut): ?array
    {
        return $this->langue(($classroom->exam ?? $examenParDefaut)?->language);
    }

    public function langue(?Language $language): ?array
    {
        if (! $language) {
            return null;
        }

        return [
            'cle' => $language->slug,
            'nom' => self::NOMS_DES_LANGUES[$language->slug] ?? $language->name,
            'drapeau' => $language->flag,
        ];
    }

    /** « a2 » et « A2 » sont le meme niveau ; un niveau vide n'en est pas un. */
    public static function niveau(?string $niveau): ?string
    {
        $niveau = strtoupper(trim((string) $niveau));

        return $niveau === '' ? null : $niveau;
    }

    /**
     * Actif, a relancer, jamais venu : le meme seuil que la carte « A relancer »
     * du tableau de bord. Exactement sept jours sans exercice : encore actif.
     */
    public static function statut(?string $derniereActivite): string
    {
        if ($derniereActivite === null) {
            return 'jamais';
        }

        return Carbon::parse($derniereActivite, 'UTC')->lt(now()->subDays(DashboardController::JOURS_AVANT_RELANCE))
            ? 'a_relancer'
            : 'actif';
    }

    /**
     * Les eleves que l'acteur peut voir, une ligne chacun, prets a ranger.
     *
     * Le nombre de requetes ne depend pas du nombre d'eleves : les classes, les
     * profils et les langues sont charges d'avance, et les exercices agreges en
     * une seule requete groupee par eleve.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function eleves(LanguageCenter $center, User $actor): Collection
    {
        $admin = $actor->centerRoleIs('center_admin');
        $center->loadMissing('defaultExam.language');
        $examenParDefaut = $center->defaultExam;

        $query = $center->students()->with([
            // Seulement les classes que l'acteur voit : un enseignant ne decouvre
            // pas, par un eleve commun, les classes de ses collegues.
            'classrooms' => fn ($q) => $q->where('center_id', $center->id)
                ->whereNull('archived_at')
                ->wherePivot('role_in_class', 'student')
                ->when(! $admin, fn ($q2) => $q2->whereHas('teachers', fn ($t) => $t->whereKey($actor->id)))
                ->orderBy('name')
                ->with('exam.language'),
            'profile.targetExam.language',
        ]);

        // A teacher only sees students of the classrooms they teach; a
        // center_admin sees every student of the center. Memes classes que
        // classesVisibles() : une classe archivee ne fait plus voir ses eleves,
        // et un collegue enseignant de la classe n'est pas un de ses eleves.
        if (! $admin) {
            $query->whereHas(
                'classrooms',
                fn ($q) => $q->where('center_id', $center->id)
                    ->whereNull('archived_at')
                    ->where('classroom_user.role_in_class', 'student')
                    ->whereHas('teachers', fn ($q2) => $q2->whereKey($actor->id))
            );
        }

        $eleves = $query->orderBy('name')->get();
        $ids = $eleves->pluck('id');

        // Une seule requete pour tous : avant, un count() par eleve. Elle porte
        // aussi le classement du centre : XP, exercices et precision de la
        // semaine et du mois (calendaires, comme /leaderboard), jours actifs sur
        // trente jours, et la precision des deux dernieres semaines face a
        // celle d'avant, pour la « meilleure progression ».
        $bornes = [
            'semaine' => now()->startOfWeek()->utc()->format('Y-m-d H:i:s'),
            'mois' => now()->startOfMonth()->utc()->format('Y-m-d H:i:s'),
            'trente' => now()->subDays(30)->utc()->format('Y-m-d H:i:s'),
            'quinze' => now()->subDays(14)->utc()->format('Y-m-d H:i:s'),
        ];
        $activite = $ids->isEmpty() ? collect() : UserExerciseAttempt::whereIn('user_id', $ids)
            ->selectRaw(
                'user_id, count(*) as exercices, avg(accuracy_percent) as precision_moyenne, max(created_at) as derniere,'
                .' sum(xp_earned) as xp_tout,'
                .' sum(case when created_at >= ? then xp_earned else 0 end) as xp_semaine,'
                .' sum(case when created_at >= ? then 1 else 0 end) as ex_semaine,'
                .' avg(case when created_at >= ? then accuracy_percent end) as prec_semaine,'
                .' sum(case when created_at >= ? then xp_earned else 0 end) as xp_mois,'
                .' sum(case when created_at >= ? then 1 else 0 end) as ex_mois,'
                .' avg(case when created_at >= ? then accuracy_percent end) as prec_mois,'
                .' count(distinct case when created_at >= ? then date(created_at) end) as jours_actifs,'
                .' avg(case when created_at >= ? then accuracy_percent end) as prec_recente,'
                .' avg(case when created_at < ? then accuracy_percent end) as prec_avant',
                [
                    $bornes['semaine'], $bornes['semaine'], $bornes['semaine'],
                    $bornes['mois'], $bornes['mois'], $bornes['mois'],
                    $bornes['trente'], $bornes['quinze'], $bornes['quinze'],
                ]
            )
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $arrondi = fn ($v) => $v !== null ? (int) round((float) $v) : null;

        return $eleves->map(function (User $u) use ($activite, $examenParDefaut, $arrondi) {
            $stats = $activite[$u->id] ?? null;
            $derniere = $stats?->derniere;

            $classes = $u->classrooms->map(fn (Classroom $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'room' => $c->room,
                'level' => self::niveau($c->level),
                'langue' => $this->langueDeLaClasse($c, $examenParDefaut),
            ])->values();

            // Le niveau de la classe ; a defaut, celui que l'eleve a declare.
            $niveaux = $classes->pluck('level')->filter()->unique()->values();
            $niveauDuProfil = $niveaux->isEmpty() ? self::niveau($u->profile?->current_level) : null;

            // La langue des classes ; a defaut, celle de l'examen qu'il vise.
            $langues = $classes->pluck('langue')->filter()->unique('cle')->values();
            if ($langues->isEmpty() && ($langue = $this->langue($u->profile?->targetExam?->language))) {
                $langues = collect([$langue]);
            }

            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'classes' => $classes,
                'niveaux' => $niveauDuProfil ? [$niveauDuProfil] : $niveaux->all(),
                'niveau_du_profil' => $niveauDuProfil !== null,
                'langues' => $langues->all(),
                'examen_vise' => $u->profile?->targetExam?->name,
                'exercices' => (int) ($stats?->exercices ?? 0),
                'score_moyen' => $stats?->precision_moyenne !== null ? (int) round((float) $stats->precision_moyenne) : null,
                'derniere_activite' => $derniere ? Carbon::parse($derniere, 'UTC')->toIso8601String() : null,
                'statut' => self::statut($derniere),
                // Le classement du centre. Depuis le debut : le total du profil,
                // qui compte aussi les lecons ; jamais moins que les exercices.
                'periodes' => [
                    'semaine' => ['xp' => (int) ($stats?->xp_semaine ?? 0), 'exercices' => (int) ($stats?->ex_semaine ?? 0), 'precision' => $arrondi($stats?->prec_semaine)],
                    'mois' => ['xp' => (int) ($stats?->xp_mois ?? 0), 'exercices' => (int) ($stats?->ex_mois ?? 0), 'precision' => $arrondi($stats?->prec_mois)],
                    'total' => [
                        'xp' => max((int) ($u->profile?->xp_total ?? 0), (int) ($stats?->xp_tout ?? 0)),
                        'exercices' => (int) ($stats?->exercices ?? 0),
                        'precision' => $arrondi($stats?->precision_moyenne),
                    ],
                ],
                'serie' => $u->profile?->serieEffective() ?? 0,
                'jours_actifs' => (int) ($stats?->jours_actifs ?? 0),
                // En points de precision : deux dernieres semaines moins avant.
                'progression' => $stats?->prec_recente !== null && $stats?->prec_avant !== null
                    ? (int) round((float) $stats->prec_recente - (float) $stats->prec_avant)
                    : null,
            ];
        })->values();
    }

    /**
     * La repartition du tableau de bord : combien de classes et d'eleves par
     * langue et par niveau. Un eleve compte une fois par langue (ou niveau),
     * meme s'il suit deux classes de cette langue.
     *
     * Elle part des classes deja chargees par le tableau de bord, et de leurs
     * eleves : les memes eleves que les cartes « Semaine » et « A relancer »,
     * donc les memes chiffres. Un eleve sans classe (ou seulement dans une
     * classe archivee) n'y est pas ; il reste sur la page Eleves.
     *
     * Meme regle que eleves() : la langue et le niveau des classes ; a defaut de
     * niveau de classe, celui que l'eleve a declare.
     *
     * @param  Collection<int, Classroom>  $classes  chargees avec exam.language et students.profile
     */
    public function repartition(Collection $classes, ?Exam $examenParDefaut): array
    {
        $langues = [];
        $niveaux = [];
        // Par eleve : les cles de langue et de niveau de ses classes visibles.
        $parEleve = [];

        foreach ($classes as $c) {
            $langue = $this->langueDeLaClasse($c, $examenParDefaut);
            $cle = $langue['cle'] ?? self::SANS;
            $langues[$cle] ??= ['cle' => $cle, 'libelle' => $langue['nom'] ?? 'Sans langue', 'drapeau' => $langue['drapeau'] ?? null, 'classes' => 0, 'eleves' => 0];
            $langues[$cle]['classes']++;

            $niveau = self::niveau($c->level);
            $cleNiveau = $niveau ?? self::SANS;
            $niveaux[$cleNiveau] ??= ['cle' => $cleNiveau, 'libelle' => $niveau ?? 'Sans niveau', 'classes' => 0, 'eleves' => 0];
            $niveaux[$cleNiveau]['classes']++;

            foreach ($c->students as $u) {
                $parEleve[$u->id] ??= ['eleve' => $u, 'langues' => [], 'niveaux' => []];
                $parEleve[$u->id]['langues'][$cle] = true;
                if ($niveau !== null) {
                    $parEleve[$u->id]['niveaux'][$niveau] = true;
                }
            }
        }

        foreach ($parEleve as $e) {
            foreach (array_keys($e['langues']) as $cle) {
                $langues[$cle]['eleves']++;
            }

            $sesNiveaux = array_keys($e['niveaux']) ?: array_filter([self::niveau($e['eleve']->profile?->current_level)]);
            foreach ($sesNiveaux ?: [self::SANS] as $niveau) {
                $niveaux[$niveau] ??= ['cle' => $niveau, 'libelle' => $niveau === self::SANS ? 'Sans niveau' : $niveau, 'classes' => 0, 'eleves' => 0];
                $niveaux[$niveau]['eleves']++;
            }
        }

        $langues = collect($langues)->sort(function ($a, $b) {
            if (($a['cle'] === self::SANS) !== ($b['cle'] === self::SANS)) {
                return $a['cle'] === self::SANS ? 1 : -1;
            }

            return strcoll($a['libelle'], $b['libelle']);
        })->values()->all();

        $niveaux = collect($niveaux)->sort(fn ($a, $b) => self::comparerNiveaux($a['cle'], $b['cle']))->values()->all();

        return ['langues' => $langues, 'niveaux' => $niveaux];
    }

    /** A1 .. C2 d'abord, puis les niveaux libres par ordre alphabetique, « sans niveau » a la fin. */
    public static function comparerNiveaux(string $a, string $b): int
    {
        $rang = function (string $n): array {
            if ($n === self::SANS) {
                return [2, ''];
            }
            $i = array_search($n, self::ORDRE_NIVEAUX, true);

            return $i === false ? [1, $n] : [0, str_pad((string) $i, 2, '0', STR_PAD_LEFT)];
        };

        return $rang($a) <=> $rang($b);
    }
}
