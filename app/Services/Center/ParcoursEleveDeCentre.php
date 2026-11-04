<?php

namespace App\Services\Center;

use App\Models\Assignment;
use App\Models\CenterUser;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Le parcours d'un eleve inscrit dans un centre.
 *
 * Ce n'est pas lui qui construit son parcours : c'est son centre, par les
 * devoirs que donnent ses enseignants. On ne lui demande donc jamais de
 * « choisir son objectif » : sa prochaine mission est son prochain devoir, et
 * son examen vise vient de sa classe. Selon le reglage du centre
 * (AContenuEleves), il suit en plus le parcours PrePla (par defaut), ou le
 * parcours que le centre a construit pour sa classe (ParcoursDeClasse).
 */
class ParcoursEleveDeCentre
{
    /** Les niveaux qu'on sait reprendre d'une classe vers le profil. */
    private const NIVEAUX = ['A0', 'A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    /**
     * Son appartenance d'eleve dans le centre courant, si ce centre est actif.
     * Null pour le personnel, l'apprenant libre, ou un centre ferme.
     */
    public function appartenance(User $user): ?CenterUser
    {
        $appartenance = $user->appartenanceCourante();

        return $appartenance?->role === 'student' && $appartenance->center?->is_active
            ? $appartenance
            : null;
    }

    public function centre(User $user): ?LanguageCenter
    {
        return $this->appartenance($user)?->center;
    }

    /** L'eleve ne voit-il que le travail donne par son centre ? */
    public function contenuReserveAuCentre(User $user): bool
    {
        return (bool) $this->centre($user)?->reserveSesElevesASonContenu();
    }

    /**
     * Ses classes d'eleve dans ce centre, la plus recemment rejointe d'abord.
     *
     * @return Collection<int, Classroom>
     */
    public function classes(User $user, LanguageCenter $centre): Collection
    {
        return Classroom::query()
            ->select('classrooms.*')
            ->join('classroom_user', 'classroom_user.classroom_id', '=', 'classrooms.id')
            ->where('classroom_user.user_id', $user->id)
            ->where('classroom_user.role_in_class', 'student')
            ->where('classrooms.center_id', $centre->id)
            ->whereNull('classrooms.archived_at')
            ->orderByDesc('classroom_user.created_at')
            ->orderByDesc('classrooms.id')
            ->with('exam:id,name')
            ->get();
    }

    /** Sa classe actuelle dans ce centre : la plus recemment rejointe. */
    public function classeActuelle(User $user, ?LanguageCenter $centre = null): ?Classroom
    {
        $centre ??= $this->centre($user);

        return $centre ? $this->classes($user, $centre)->first() : null;
    }

    /**
     * L'examen ou il s'entraine : celui de sa classe, sinon celui du centre par
     * defaut, seulement s'il est retenu par le centre. Jamais celui du profil :
     * si l'enseignant change la classe, l'entrainement suit.
     */
    public function examenDEntrainement(User $user, LanguageCenter $centre, ?Classroom $classe = null): ?Exam
    {
        $classe ??= $this->classeActuelle($user, $centre);
        foreach (array_filter([$classe?->exam_id, $centre->default_exam_id]) as $id) {
            if ($examen = Exam::proposables($centre)->whereKey($id)->first()) {
                return $examen;
            }
        }

        return null;
    }

    /** Son niveau d'entrainement : celui de sa classe, relu a chaque fois. */
    public function niveauDEntrainement(?Classroom $classe): ?string
    {
        return $this->niveau($classe?->level);
    }

    /**
     * Remplit l'examen vise et le niveau de son profil depuis sa classe, quand
     * il n'en a pas. Ne remplace jamais ce que l'eleve a deja choisi.
     *
     * Une classe sans examen prend celui du centre par defaut. Plusieurs
     * classes : la plus recemment rejointe.
     */
    public function completerObjectif(User $user, ?Classroom $classe = null): void
    {
        $centre = $classe?->center ?? $this->centre($user);
        if (! $centre) {
            return;
        }

        $classe ??= $this->classes($user, $centre)->first();

        $examen = $classe?->exam_id ?? $centre->default_exam_id;
        $niveau = $this->niveau($classe?->level);

        $profil = $user->profile;
        $valeurs = [];
        if (! $profil?->target_exam_id && $examen) {
            $valeurs['target_exam_id'] = $examen;
        }
        if (! $profil?->current_level && $niveau) {
            $valeurs['current_level'] = $niveau;
            $valeurs['level_source'] = 'classe';
        }
        if ($valeurs === []) {
            return;
        }

        if ($profil) {
            $profil->update($valeurs);
        } else {
            $user->setRelation('profile', $user->profile()->create($valeurs));
        }
    }

    /** « B1 », « b1 », « B1.2 » ou « B1 intensif » donnent B1 ; le reste, rien. */
    private function niveau(?string $niveau): ?string
    {
        $niveau = strtoupper(trim((string) $niveau));
        if (preg_match('/^(A0|A1|A2|B1|B2|C1|C2)/', $niveau, $m)) {
            return in_array($m[1], self::NIVEAUX, true) ? $m[1] : null;
        }

        return null;
    }

    /**
     * Ses devoirs publies dans ce centre, chacun avec sa progression.
     * Ordre : l'echeance la plus proche (donc les retards) d'abord, sans
     * echeance a la fin.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function devoirs(User $user, ?LanguageCenter $centre = null): Collection
    {
        $centre ??= $this->centre($user);
        if (! $centre) {
            return collect();
        }

        $classes = $this->classes($user, $centre)->keyBy('id');
        if ($classes->isEmpty()) {
            return collect();
        }

        $devoirs = Assignment::whereIn('classroom_id', $classes->keys())
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('items')
            ->orderByRaw('due_at is null, due_at asc')
            ->orderBy('published_at')
            ->orderBy('id')
            ->get();

        // Une seule requete pour toutes ses tentatives sur ces exercices.
        $tousLesExercices = $devoirs->flatMap(fn (Assignment $a) => $this->exercices($a))->unique()->values();
        $tentatives = $tousLesExercices->isEmpty() ? collect() : UserExerciseAttempt::where('user_id', $user->id)
            ->whereIn('exercise_id', $tousLesExercices)
            ->get(['exercise_id', 'created_at']);

        return $devoirs->map(function (Assignment $a) use ($classes, $tentatives) {
            $exercices = $this->exercices($a);
            // Fait = tente APRES la publication du devoir, comme le voit l'enseignant
            // (AssignmentProgressService).
            $faits = $tentatives
                ->filter(fn ($t) => $t->created_at >= $a->published_at)
                ->pluck('exercise_id')->map(fn ($id) => (int) $id)->unique();
            $faitsIci = $exercices->filter(fn ($id) => $faits->contains($id));
            $total = $exercices->count();
            $termine = $total > 0 && $faitsIci->count() >= $total;

            return [
                'id' => $a->id,
                'title' => $a->title,
                'instructions' => $a->instructions,
                'classe' => $classes->get($a->classroom_id)?->name,
                'due_at' => $a->due_at?->toIso8601String(),
                'echeance' => $this->echeance($a->due_at, $termine),
                // Un devoir rendu n'est plus en retard, meme apres son echeance.
                'overdue' => ! $termine && $a->isOverdue(),
                'total' => $total,
                'done' => $faitsIci->count(),
                'termine' => $termine,
                'next_exercise_id' => $exercices->first(fn ($id) => ! $faits->contains($id)),
            ];
        })->values();
    }

    /** @return Collection<int, int> les exercices du devoir, dans leur ordre. */
    private function exercices(Assignment $devoir): Collection
    {
        return $devoir->items
            ->where('itemable_type', Exercise::class)
            ->pluck('itemable_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /** Ses devoirs a faire : publies, avec au moins un exercice, pas termines. */
    public function devoirsEnAttente(User $user, ?LanguageCenter $centre = null): Collection
    {
        return $this->devoirs($user, $centre)
            ->filter(fn (array $d) => $d['total'] > 0 && ! $d['termine'])
            ->values();
    }

    /**
     * L'echeance dite comme on la dit : « pour lundi 10 novembre »,
     * « pour demain », « en retard de 2 jours ». Un devoir termine n'est jamais
     * dit en retard : on rappelle seulement sa date.
     *
     * Une echeance a minuit pile (champ date de l'enseignant) vaut pour toute la
     * journee (Assignment::depassee).
     */
    public function echeance(?CarbonInterface $echeance, bool $termine = false): ?string
    {
        if (! $echeance) {
            return null;
        }

        $jour = $echeance->copy()->startOfDay();
        $aujourdhui = now()->startOfDay();

        if ($termine) {
            return 'à rendre le '.$echeance->copy()->locale('fr')->isoFormat('dddd D MMMM');
        }

        if (Assignment::depassee($echeance)) {
            $jours = (int) $jour->diffInDays($aujourdhui);

            return match (true) {
                $jours <= 0 => 'en retard depuis aujourd’hui',
                $jours === 1 => 'en retard d’un jour',
                default => "en retard de {$jours} jours",
            };
        }

        if ($jour->equalTo($aujourdhui)) {
            return 'pour aujourd’hui';
        }
        if ($jour->equalTo($aujourdhui->copy()->addDay())) {
            return 'pour demain';
        }

        return 'pour '.$echeance->copy()->locale('fr')->isoFormat('dddd D MMMM');
    }

    /**
     * Peut-il ouvrir cet exercice ? Oui s'il fait partie d'un de ses devoirs
     * publies, ou du parcours construit par le centre pour l'une de ses classes.
     */
    public function exerciceDansSesDevoirs(User $user, int $exerciceId): bool
    {
        $centre = $this->centre($user);
        if (! $centre) {
            return false;
        }

        $classes = $this->classes($user, $centre)->pluck('id');

        if (app(ParcoursDeClasse::class)->exerciceDansLeParcours($classes, $exerciceId)) {
            return true;
        }

        return $classes->isNotEmpty() && Assignment::whereIn('classroom_id', $classes)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('items', fn ($q) => $q->where('itemable_type', Exercise::class)->where('itemable_id', $exerciceId))
            ->exists();
    }

    /**
     * Sa prochaine mission, pour l'accueil (meme forme que LearningJourneyService).
     * Null quand l'entrainement PrePla prend le relais (centre en 'libre', aucun
     * devoir en attente, et un parcours personnel deja en cours).
     *
     * @return array<string, mixed>|null
     */
    public function prochaineMission(User $user, bool $aUnParcours): ?array
    {
        $centre = $this->centre($user);
        if (! $centre) {
            return null;
        }

        $devoirs = $this->devoirs($user, $centre);
        $devoir = $devoirs->first(fn (array $d) => $d['total'] > 0 && ! $d['termine']);
        if ($devoir) {
            return [
                'kind' => 'devoir',
                'title' => $devoir['title'],
                'description' => $devoir['instructions'] ?: 'Le travail donné par ton enseignant.',
                'url' => route('devoirs.show', $devoir['id']),
                'devoir' => [
                    'classe' => $devoir['classe'],
                    'echeance' => $devoir['echeance'],
                    'en_retard' => $devoir['overdue'],
                    'fait' => $devoir['done'],
                    'total' => $devoir['total'],
                ],
            ];
        }

        // Pas encore de classe : rien ne peut lui etre donne, il lui faut son code.
        $classe = $this->classes($user, $centre)->first();
        if (! $classe) {
            return [
                'kind' => 'sans_classe',
                'title' => 'Rejoins ta classe',
                'description' => 'Ton enseignant te donnera le code de ta classe. Avec lui, tu recevras tes devoirs ici.',
                'url' => route('center.join'),
            ];
        }

        // « Tous faits » seulement s'il en a deja recu : sinon on le felicitait
        // a tort des son arrivee dans le centre.
        $aDejaEuDesDevoirs = $devoirs->contains(fn (array $d) => $d['total'] > 0);

        if ($centre->reserveSesElevesASonContenu()) {
            return $this->etapeDuParcours($user, $centre, $aDejaEuDesDevoirs);
        }

        // Centre en 'libre', plus rien a rendre : l'entrainement PrePla. Avec un
        // parcours deja en cours, c'est lui qui continue.
        if ($aUnParcours) {
            return null;
        }

        // Comme /practice : l'examen et le niveau de SA CLASSE, pas ceux du
        // profil, qui ne suit pas un changement de la classe.
        $examen = $this->examenDEntrainement($user, $centre, $classe);
        $niveau = $this->niveauDEntrainement($classe) ?? $user->profile?->current_level;

        $precisions = array_filter([
            $niveau ? "niveau {$niveau}" : null,
            "comme ta classe « {$classe->name} »",
        ]);

        return [
            'kind' => 'entrainement',
            'title' => $examen ? "M’entraîner pour le {$examen->name}" : 'M’entraîner',
            'description' => ($aDejaEuDesDevoirs
                ? 'Tu as fait tous tes devoirs. En attendant les prochains, entraîne-toi'
                : 'Ton enseignant ne t’a pas encore donné de devoir. En attendant, entraîne-toi')
                .($precisions ? ', '.implode(', ', $precisions) : '').'.',
            'url' => $examen ? route('practice.exam', $examen->id) : route('practice.index'),
        ];
    }

    /**
     * « Parcours créé par le centre », plus de devoir a rendre : la prochaine
     * etape non faite du parcours de sa classe, ou la fin du parcours.
     *
     * @return array<string, mixed>
     */
    private function etapeDuParcours(User $user, LanguageCenter $centre, bool $aDejaEuDesDevoirs = true): array
    {
        $classe = $this->classeActuelle($user, $centre);
        $parcours = $classe ? app(ParcoursDeClasse::class)->pourLEleve($user, $classe) : null;

        if (! $parcours || $parcours['total'] === 0) {
            return [
                'kind' => 'devoirs_finis',
                'title' => $aDejaEuDesDevoirs ? 'Tu as fait tous tes devoirs.' : 'Pas encore de devoir.',
                'description' => 'Ton centre n’a pas encore préparé le parcours de ta classe. Ton enseignant te donnera bientôt du travail.',
                'url' => route('results.index'),
            ];
        }

        if ($parcours['termine']) {
            return [
                'kind' => 'parcours_fini',
                'title' => 'Tu as terminé ton parcours.',
                'description' => 'Bravo ! Tu as fait toutes les étapes préparées par ton centre. Ton enseignant t’en préparera la suite.',
                'url' => route('parcours.index'),
            ];
        }

        $etape = $parcours['actuelle'];

        return [
            'kind' => 'etape',
            'title' => $etape['titre'],
            'description' => $etape['consigne'] ?: 'La prochaine étape du parcours préparé par ton centre.',
            'url' => route('exercise.show', $etape['exercise_id']),
            'etape' => [
                'classe' => $classe->name,
                'numero' => $etape['numero'],
                'total' => $etape['total'],
                'competence' => $etape['competence'],
                // « Étape 3 sur 8 · Compréhension orale »
                'libelle' => "Étape {$etape['numero']} sur {$etape['total']}".($etape['competence'] ? " · {$etape['competence']}" : ''),
            ],
        ];
    }
}
