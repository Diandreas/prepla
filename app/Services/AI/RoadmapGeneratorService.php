<?php

namespace App\Services\AI;

use App\Models\LearningPathNode;
use App\Models\UserLearningProgress;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RoadmapGeneratorService
{
    /**
     * Planifie le parcours d'apprentissage pour un utilisateur spécifique.
     * Algorithme déterministe basé sur le temps restant jusqu'au Jour J.
     */
    public function generateForUser($user, $exam, $currentLevel, $examDate = null)
    {
        // 1. Récupérer tous les nœuds statiques pour cet examen
        $nodes = LearningPathNode::where('exam_id', $exam->id)
            ->orderBy('chapter_order')
            ->orderBy('sort_order')
            ->get();

        if ($nodes->isEmpty()) {
            return collect();
        }

        // 2. Filtrer les nœuds selon le niveau de l'utilisateur
        $filteredNodes = $this->filterNodesByLevel($nodes, $currentLevel);

        // 3. Appliquer la rampe pédagogique progressive
        $filteredNodes = $this->applyProgressiveRamp($filteredNodes, $currentLevel);

        // 4. Calculer la planification temporelle
        $startDate = Carbon::today();
        $targetDate = $examDate ? Carbon::parse($examDate) : Carbon::today()->addMonths(3);
        $totalDays = max(1, $startDate->diffInDays($targetDate));

        return $this->scheduleNodes($user->id, $filteredNodes, $startDate, $totalDays);
    }

    private function filterNodesByLevel(Collection $nodes, string $currentLevel): Collection
    {
        $levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $currentIndex = array_search($currentLevel, $levels);

        if ($currentIndex === false) $currentIndex = 0;

        return $nodes->filter(function ($node) use ($levels, $currentIndex) {
            $nodeIndex = array_search($node->level, $levels);
            // On garde le niveau actuel et les niveaux supérieurs
            return $nodeIndex >= $currentIndex;
        });
    }

    /**
     * Applique une rampe pédagogique progressive :
     * - Pour les débutants (A0/A1) : exclure les simulations d'examen du début du parcours
     *   et remettre les exercices de production (speaking, writing) après une phase d'acquisition.
     * - Pour tous : trier les nœuds du même niveau pour présenter les types
     *   d'entrée (vocabulary, reading) avant les types de production (speaking, writing).
     */
    private function applyProgressiveRamp(Collection $nodes, string $currentLevel): Collection
    {
        $isBeginnerLevel = in_array($currentLevel, ['A0', 'A1']);

        // Poids de skill_type pour l'ordre pédagogique :
        // Acquisition avant production
        $skillOrder = [
            'vocabulary' => 1,
            'reading'    => 2,
            'listening'  => 3,
            'grammar'    => 4,
            'writing'    => 5,
            'speaking'   => 6,
        ];

        // Pour les débutants, on sépare les nœuds en 3 phases :
        // Phase 1 (25%) : acquisition seulement (vocab, reading, listening, grammar)
        //   → exclure speaking/writing et les simulations d'examen
        // Phase 2 (50%) : tous les types sauf simulation
        // Phase 3 (25%) : tout (y compris simulations)

        if ($isBeginnerLevel) {
            $productionTypes = ['speaking', 'writing', 'production'];
            $simulationTypes = ['exam-simulation', 'mock-exam', 'full-exam'];

            $acquisition = $nodes->filter(function ($node) use ($productionTypes, $simulationTypes) {
                return !in_array($node->skill_type, $productionTypes)
                    && !in_array($node->node_type, $simulationTypes);
            });

            $production = $nodes->filter(function ($node) use ($productionTypes, $simulationTypes) {
                return in_array($node->skill_type, $productionTypes)
                    && !in_array($node->node_type, $simulationTypes);
            });

            $simulation = $nodes->filter(function ($node) use ($simulationTypes) {
                return in_array($node->node_type, $simulationTypes);
            });

            // Trier chaque groupe par skill_type dans l'ordre pédagogique
            $sortFn = function ($node) use ($skillOrder) {
                return $skillOrder[$node->skill_type] ?? 9;
            };

            $nodes = $acquisition->sortBy($sortFn)
                ->concat($production->sortBy($sortFn))
                ->concat($simulation);
        } else {
            // Pour les niveaux intermédiaires+, seulement trier par skill_type
            // au sein de chaque chapitre (conserver l'ordre des chapitres)
            $nodes = $nodes->sortBy(function ($node) use ($skillOrder) {
                // Tri primaire : chapter_order, secondaire : skill_order
                $skillWeight = $skillOrder[$node->skill_type] ?? 9;
                return $node->chapter_order * 100 + $skillWeight;
            });
        }

        return $nodes->values();
    }

    private function scheduleNodes($userId, Collection $nodes, Carbon $startDate, int $totalDays): Collection
    {
        $nodeCount = $nodes->count();
        if ($nodeCount === 0) return collect();

        // Calculer combien de nœuds par jour (vitesse)
        $nodesPerDay = $nodeCount / $totalDays;

        $progressEntries = [];

        // Supprimer l'ancienne progression pour repartir de zéro
        UserLearningProgress::where('user_id', $userId)->delete();

        $nodes = $nodes->values(); // Réindexer à partir de 0
        foreach ($nodes as $index => $node) {
            // Distribution linéaire sur les jours disponibles
            $dayOffset = (int) floor($index / max(1, $nodesPerDay));
            $scheduledDate = $startDate->copy()->addDays($dayOffset);

            $progressEntries[] = [
                'user_id' => $userId,
                'node_id' => $node->id,
                'status' => $index === 0 ? 'available' : 'locked',
                'scheduled_for' => $scheduledDate->format('Y-m-d'),
                'exercises_required' => 3, // Règle de 3 exercices par session
                'exercises_done' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insertion en masse pour la performance
        foreach (array_chunk($progressEntries, 50) as $chunk) {
            UserLearningProgress::insert($chunk);
        }

        return collect($progressEntries);
    }
}
