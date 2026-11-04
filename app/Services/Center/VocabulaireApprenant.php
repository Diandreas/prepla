<?php

namespace App\Services\Center;

use App\Models\Classroom;
use App\Models\User;
use App\Models\UserWordProgress;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Le vocabulaire d'un apprenant, vu par son centre.
 *
 * L'apprenant garde ses mots dans son lexique personnel (dictionnaire) : chaque
 * mot y passe de « decouvert » a « en cours » puis « maitrise », et revient a
 * date fixe pour etre revise. Tout etait enregistre, mais l'enseignant n'en
 * voyait rien : il ne pouvait pas savoir si un eleve apprenait ses mots, ni
 * lesquels resistaient a toute la classe.
 *
 * LECTURE SEULE. Le lexique de l'apprenant ne s'ouvre pas ici : rien n'est
 * importe ni recalcule (le dictionnaire, lui, importe les anciens mots a
 * l'ouverture — ce n'est pas au regard de l'enseignant de le declencher).
 */
class VocabulaireApprenant
{
    /** Les etapes du lexique, dans l'ordre ou un mot les traverse. */
    public const ETAPES = [
        'discovered' => 'Découvert',
        'learning' => 'En cours',
        'mastered' => 'Maîtrisé',
    ];

    /** Au-dela, un mot a reviser est considere comme laisse de cote. */
    private const JOURS_DE_RETARD = 7;

    /** Assez pour toute une annee d'apprentissage, sans charger un lexique sans fin. */
    private const MOTS_MAX = 1000;

    /**
     * Le bilan d'un eleve et la liste de ses mots.
     *
     * @return array{bilan: array<string, mixed>, mots: list<array<string, mixed>>}
     */
    public function pourEleve(User $eleve): array
    {
        $mots = $this->motsDe([$eleve->id]);

        return [
            'bilan' => $this->bilan($mots),
            'mots' => $mots
                ->sortBy(fn (UserWordProgress $m) => [
                    // D'abord ce qui demande une action : les mots a reviser,
                    // puis ceux en cours, enfin les acquis.
                    $this->aReviser($m) ? 0 : 1,
                    array_search($this->etape($m), ['learning', 'discovered', 'mastered'], true),
                    $m->dictionaryWord?->word,
                ])
                ->map(fn (UserWordProgress $m) => $this->ligne($m))
                ->values()
                ->all(),
        ];
    }

    /**
     * Le vocabulaire de toute une classe : un bilan par eleve, et les mots qui
     * resistent au plus grand nombre.
     *
     * @return array{eleves: list<array<string, mixed>>, mots_difficiles: list<array<string, mixed>>}
     */
    public function pourClasse(Classroom $classe): array
    {
        $eleves = $classe->students()->orderBy('name')->get(['users.id', 'users.name']);
        $mots = $this->motsDe($eleves->pluck('id')->all());
        $parEleve = $mots->groupBy('user_id');

        return [
            'eleves' => $eleves->map(fn (User $e) => [
                'id' => $e->id,
                'nom' => $e->name,
            ] + $this->bilan($parEleve->get($e->id, collect())))->values()->all(),
            'mots_difficiles' => $this->motsDifficiles($mots),
        ];
    }

    /**
     * @param  list<int>  $eleves
     * @return Collection<int, UserWordProgress>
     */
    private function motsDe(array $eleves): Collection
    {
        if ($eleves === []) {
            return collect();
        }

        return UserWordProgress::whereIn('user_id', $eleves)
            ->whereHas('dictionaryWord')
            ->with('dictionaryWord:id,word,language,translation,definition,skill_level')
            ->latest('updated_at')
            ->limit(self::MOTS_MAX * count($eleves))
            ->get();
    }

    /**
     * @param  Collection<int, UserWordProgress>  $mots
     * @return array<string, mixed>
     */
    private function bilan(Collection $mots): array
    {
        $parEtape = array_fill_keys(array_keys(self::ETAPES), 0);
        foreach ($mots as $m) {
            $parEtape[$this->etape($m)]++;
        }

        $parNiveau = $mots
            ->groupBy(fn (UserWordProgress $m) => $m->dictionaryWord?->skill_level ?: '—')
            ->map->count()
            ->sortKeys()
            ->all();

        $semaine = now()->subDays(7);
        $derniere = $mots->pluck('last_reviewed_at')->filter()->max();

        return [
            'total' => $mots->count(),
            'par_etape' => $parEtape,
            'par_niveau' => $parNiveau,
            'a_reviser' => $mots->filter(fn ($m) => $this->aReviser($m))->count(),
            'laisses_de_cote' => $mots->filter(fn ($m) => $this->laisseDeCote($m))->count(),
            'ajoutes_7_jours' => $mots->filter(fn ($m) => $m->created_at?->gte($semaine))->count(),
            'revises_7_jours' => $mots->filter(fn ($m) => $m->last_reviewed_at?->gte($semaine))->count(),
            'derniere_revision' => $derniere instanceof Carbon ? $derniere->toIso8601String() : null,
        ];
    }

    /** @return array<string, mixed> */
    private function ligne(UserWordProgress $m): array
    {
        $mot = $m->dictionaryWord;

        return [
            'id' => $m->id,
            'mot' => $mot?->word,
            'langue' => $mot?->language,
            'traduction' => $mot?->translation ?: null,
            'definition' => $mot?->definition ?: null,
            'niveau' => $mot?->skill_level ?: null,
            'etape' => $this->etape($m),
            'ajoute' => $m->created_at?->toIso8601String(),
            'derniere_revision' => $m->last_reviewed_at?->toIso8601String(),
            'prochaine_revision' => $m->next_review_at?->toIso8601String(),
            // Reconnaitre (choisir la definition) et retrouver (ecrire le mot) :
            // le dictionnaire exige deux rappels et une reconnaissance pour
            // tenir un mot pour maitrise.
            'reconnaissances' => (int) $m->recognition_count,
            'rappels' => (int) $m->recall_count,
            'a_reviser' => $this->aReviser($m),
            'laisse_de_cote' => $this->laisseDeCote($m),
        ];
    }

    /**
     * Les mots « en cours » chez le plus d'eleves, puis ceux a reviser : ce que
     * la classe n'arrive pas a fixer, a reprendre en cours.
     *
     * @param  Collection<int, UserWordProgress>  $mots
     * @return list<array<string, mixed>>
     */
    private function motsDifficiles(Collection $mots): array
    {
        return $mots
            ->filter(fn (UserWordProgress $m) => $this->etape($m) === 'learning')
            ->groupBy('dictionary_word_id')
            // Un mot qu'un seul eleve travaille n'est pas une difficulte de classe.
            ->filter(fn (Collection $g) => $g->pluck('user_id')->unique()->count() >= 2)
            ->map(fn (Collection $g) => [
                'mot' => $g->first()->dictionaryWord?->word,
                'traduction' => $g->first()->dictionaryWord?->translation ?: null,
                'niveau' => $g->first()->dictionaryWord?->skill_level ?: null,
                'eleves' => $g->pluck('user_id')->unique()->count(),
                'a_reviser' => $g->filter(fn ($m) => $this->aReviser($m))->count(),
            ])
            ->sortBy([['eleves', 'desc'], ['a_reviser', 'desc'], ['mot', 'asc']])
            ->take(15)
            ->values()
            ->all();
    }

    /** Un mot sans etape connue est un mot qu'on vient de decouvrir. */
    private function etape(UserWordProgress $m): string
    {
        return array_key_exists((string) $m->status, self::ETAPES) ? $m->status : 'discovered';
    }

    /**
     * Le mot attend sa revision — meme regle que la file de revision du
     * dictionnaire (UserWordProgress::dueForReview).
     */
    private function aReviser(UserWordProgress $m): bool
    {
        if ($m->next_review_at === null) {
            return $this->etape($m) !== 'mastered';
        }

        return $m->next_review_at->lte(now());
    }

    /** A reviser depuis plus d'une semaine : l'eleve ne revient plus dessus. */
    private function laisseDeCote(UserWordProgress $m): bool
    {
        if (! $this->aReviser($m)) {
            return false;
        }
        $depuis = $m->next_review_at ?? $m->last_reviewed_at ?? $m->created_at;

        return $depuis !== null && $depuis->lt(now()->subDays(self::JOURS_DE_RETARD));
    }
}
