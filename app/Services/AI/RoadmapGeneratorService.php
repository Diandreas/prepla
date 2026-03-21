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
        // On garde les nœuds de niveau >= niveau actuel
        $filteredNodes = $this->filterNodesByLevel($nodes, $currentLevel);

        // 3. Calculer la planification temporelle
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

    private function scheduleNodes($userId, Collection $nodes, Carbon $startDate, int $totalDays): Collection
    {
        $nodeCount = $nodes->count();
        if ($nodeCount === 0) return collect();

        // Calculer combien de nœuds par jour (vitesse)
        $nodesPerDay = $nodeCount / $totalDays;
        
        $progressEntries = [];
        $currentNodeIndex = 0;

        // Supprimer l'ancienne progression pour repartir de zéro (ou archiver si nécessaire)
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
