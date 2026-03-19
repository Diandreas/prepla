<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class PlacementTestService
{
    public function __construct(protected MistralService $mistral)
    {
    }

    /**
     * Generate 10 language-specific placement questions using Mistral.
     * Falls back to hardcoded questions if AI fails.
     */
    public function generateQuestions(string $language, string $languageNative, string $examName): array
    {
        $prompt = <<<PROMPT
You are a language testing expert. Generate a placement test for {$examName} ({$language}).

Create exactly 10 multiple-choice questions to assess language proficiency levels A1 through C1.
- 2 questions at A1 level (very basic)
- 2 questions at A2 level (elementary)
- 2 questions at B1 level (intermediate)
- 2 questions at B2 level (upper-intermediate)
- 2 questions at C1 level (advanced)

The questions MUST test {$language} ({$languageNative}) language skills.
The question TEXT can be in French (interface language).
The sentences to evaluate MUST be in {$language}.

Return a valid JSON object with this exact structure:
{
  "questions": [
    {
      "id": "q1",
      "level": "A1",
      "text": "Question instruction in French",
      "sentence": "The {$language} sentence to evaluate (leave empty string if not applicable)",
      "options": ["Option A", "Option B", "Option C", "Option D"],
      "correct": 0
    }
  ]
}

The "correct" field is the 0-based index of the correct answer.
Make questions progressively harder. Only return the JSON object.
PROMPT;

        $content = $this->mistral->chat([
            ['role' => 'user', 'content' => $prompt],
        ]);

        if ($content) {
            $decoded = json_decode($content, true);
            if (isset($decoded['questions']) && is_array($decoded['questions']) && count($decoded['questions']) >= 5) {
                // Normalize IDs
                foreach ($decoded['questions'] as $i => &$q) {
                    $q['id'] = 'q' . ($i + 1);
                }
                return $decoded['questions'];
            }
        }

        return $this->getFallbackQuestions($language);
    }

    /**
     * Evaluate answers and compute CEFR level.
     */
    public function evaluateLevel(array $questions, array $answers): string
    {
        $levelScores = ['A1' => 0, 'A2' => 0, 'B1' => 0, 'B2' => 0, 'C1' => 0];
        $levelCounts = ['A1' => 0, 'A2' => 0, 'B1' => 0, 'B2' => 0, 'C1' => 0];

        foreach ($questions as $q) {
            $level = $q['level'] ?? 'B1';
            $levelCounts[$level] = ($levelCounts[$level] ?? 0) + 1;
            $userAnswer = $answers[$q['id']] ?? -1;
            if ((int)$userAnswer === (int)$q['correct']) {
                $levelScores[$level] = ($levelScores[$level] ?? 0) + 1;
            }
        }

        // Find highest level where user got at least 50%
        $levels = ['C1', 'B2', 'B1', 'A2', 'A1'];
        foreach ($levels as $level) {
            $count = $levelCounts[$level] ?? 0;
            $score = $levelScores[$level] ?? 0;
            if ($count > 0 && ($score / $count) >= 0.5) {
                return $level;
            }
        }

        return 'A1';
    }

    /**
     * Generate personalized study program using Mistral.
     */
    public function generateProgram(
        string $language,
        string $examName,
        string $estimatedLevel,
        ?int $targetScore,
        ?string $examDate,
        string $userName
    ): array {
        $daysUntilExam = $examDate ? max(7, now()->diffInDays($examDate, true)) : 90;
        $weeksUntilExam = max(1, (int)round($daysUntilExam / 7));
        $scoreInfo = $targetScore ? "Score cible : {$targetScore}" : '';

        $prompt = <<<PROMPT
Tu es un expert en préparation aux examens de langue. Crée un programme d'étude personnalisé.

Profil de l'étudiant :
- Prénom : {$userName}
- Langue cible : {$language}
- Examen : {$examName}
- Niveau actuel estimé : {$estimatedLevel}
- Semaines avant l'examen : {$weeksUntilExam}
{$scoreInfo}

Génère un programme de préparation réaliste et motivant.
Retourne un objet JSON avec cette structure exacte :
{
  "summary": "Message personnalisé d'encouragement pour {$userName} (3-4 phrases, en français, mentionne son niveau {$estimatedLevel} et l'examen {$examName})",
  "strengths": ["point fort 1", "point fort 2", "point fort 3"],
  "focus_areas": [
    {"skill": "reading", "priority": "haute", "description": "conseil spécifique"},
    {"skill": "listening", "priority": "moyenne", "description": "conseil spécifique"},
    {"skill": "writing", "priority": "haute", "description": "conseil spécifique"},
    {"skill": "speaking", "priority": "basse", "description": "conseil spécifique"}
  ],
  "weekly_plan": {
    "reading": 3,
    "listening": 3,
    "writing": 2,
    "speaking": 2
  },
  "milestones": [
    {"week": 2, "goal": "objectif concret semaine 2"},
    {"week": 4, "goal": "objectif concret semaine 4"},
    {"week": ${weeksUntilExam}, "goal": "objectif final pour l'examen"}
  ],
  "daily_tip": "Conseil pratique quotidien spécifique à {$language} et {$examName}",
  "next_level": "Niveau CECRL suivant après {$estimatedLevel}"
}

Sois précis, encourageant et adapté au niveau {$estimatedLevel}. Réponds uniquement en JSON.
PROMPT;

        $content = $this->mistral->chat([
            ['role' => 'user', 'content' => $prompt],
        ]);

        if ($content) {
            $decoded = json_decode($content, true);
            if (is_array($decoded) && isset($decoded['summary'])) {
                return $decoded;
            }
        }

        return $this->getFallbackProgram($language, $examName, $estimatedLevel, $userName);
    }

    private function getFallbackQuestions(string $language): array
    {
        // Generic grammar questions that work across languages
        return [
            ['id' => 'q1', 'level' => 'A1', 'text' => 'Choisissez la bonne réponse :', 'sentence' => 'I ___ a student.', 'options' => ['am', 'is', 'are', 'be'], 'correct' => 0],
            ['id' => 'q2', 'level' => 'A1', 'text' => 'Complétez la phrase :', 'sentence' => 'She ___ to school every day.', 'options' => ['go', 'goes', 'going', 'gone'], 'correct' => 1],
            ['id' => 'q3', 'level' => 'A2', 'text' => 'Choisissez la bonne forme :', 'sentence' => 'They ___ dinner when I called.', 'options' => ['have', 'had', 'were having', 'has'], 'correct' => 2],
            ['id' => 'q4', 'level' => 'A2', 'text' => 'Sélectionnez la réponse correcte :', 'sentence' => 'I have lived here ___ 5 years.', 'options' => ['since', 'for', 'during', 'while'], 'correct' => 1],
            ['id' => 'q5', 'level' => 'B1', 'text' => 'Complétez avec la bonne forme :', 'sentence' => 'If I ___ more money, I would travel more.', 'options' => ['have', 'had', 'has', 'having'], 'correct' => 1],
            ['id' => 'q6', 'level' => 'B1', 'text' => 'Choisissez le bon mot :', 'sentence' => 'The meeting was ___ due to the storm.', 'options' => ['cancelled', 'cancelling', 'cancel', 'cancels'], 'correct' => 0],
            ['id' => 'q7', 'level' => 'B2', 'text' => 'Sélectionnez la structure correcte :', 'sentence' => 'Not only ___ the test, but she also won a prize.', 'options' => ['she passed', 'did she pass', 'she did pass', 'passed she'], 'correct' => 1],
            ['id' => 'q8', 'level' => 'B2', 'text' => 'Choisissez le meilleur synonyme pour "significant" :', 'sentence' => '', 'options' => ['small', 'considerable', 'ordinary', 'simple'], 'correct' => 1],
            ['id' => 'q9', 'level' => 'C1', 'text' => 'Choisissez la forme correcte :', 'sentence' => 'Had I known about the meeting, I ___ attended.', 'options' => ['would have', 'will have', 'would', 'should'], 'correct' => 0],
            ['id' => 'q10', 'level' => 'C1', 'text' => 'Quel mot complète le mieux cette phrase ?', 'sentence' => 'The new policy has been met with ___ opposition.', 'options' => ['vehement', 'quiet', 'gentle', 'timid'], 'correct' => 0],
        ];
    }

    private function getFallbackProgram(string $language, string $examName, string $level, string $userName): array
    {
        return [
            'summary' => "Félicitations {$userName} ! Votre niveau estimé est {$level} en {$language}. Sur la base de votre test, nous avons préparé un programme personnalisé pour vous aider à réussir le {$examName}. Chaque jour de pratique vous rapproche de votre objectif !",
            'strengths' => ['Bonne compréhension des structures de base', 'Motivation élevée', 'Capacité d\'apprentissage'],
            'focus_areas' => [
                ['skill' => 'reading', 'priority' => 'haute', 'description' => 'Lisez des textes authentiques chaque jour'],
                ['skill' => 'listening', 'priority' => 'haute', 'description' => 'Écoutez des podcasts et vidéos en ' . $language],
                ['skill' => 'writing', 'priority' => 'moyenne', 'description' => 'Pratiquez l\'écriture de courts paragraphes'],
                ['skill' => 'speaking', 'priority' => 'moyenne', 'description' => 'Entraînez-vous à la prononciation quotidiennement'],
            ],
            'weekly_plan' => ['reading' => 3, 'listening' => 3, 'writing' => 2, 'speaking' => 2],
            'milestones' => [
                ['week' => 2, 'goal' => 'Maîtriser le vocabulaire essentiel du niveau ' . $level],
                ['week' => 4, 'goal' => 'Compléter 20 exercices et améliorer la précision à 75%'],
                ['week' => 8, 'goal' => 'Simuler des conditions d\'examen et atteindre le niveau cible'],
            ],
            'daily_tip' => 'Pratiquez 20-30 minutes chaque jour plutôt que de longues sessions irrégulières.',
            'next_level' => $this->getNextLevel($level),
        ];
    }

    /**
     * Generate a Duolingo-style learning roadmap using Mistral.
     */
    public function generateRoadmap(
        string $language,
        string $examName,
        string $currentLevel,
        ?int $targetScore,
        ?string $examDate
    ): array {
        $nextLevel = $this->getNextLevel($currentLevel);
        $daysUntilExam = $examDate ? max(7, now()->diffInDays($examDate, true)) : 90;

        $prompt = <<<PROMPT
Tu es un expert en création de parcours d'apprentissage de langues. Crée une feuille de route (roadmap) de type Duolingo pour préparer l'examen {$examName} en {$language}.

Niveau actuel : {$currentLevel}
Niveau cible : {$nextLevel}
Jours avant examen : {$daysUntilExam}

Crée exactement 15 étapes de progression, organisées en 5 sections thématiques de 3 étapes chacune.
Chaque section a un thème (vocabulaire, grammaire, compréhension, expression, examen).

Retourne un JSON avec cette structure exacte :
{
  "nodes": [
    {
      "sort_order": 1,
      "title": "Titre court de l'étape",
      "description": "Description en 1 phrase de ce qu'on apprend",
      "icon": "book|headphones|pen|mic|trophy|star|zap|target|brain|globe",
      "skill_type": "reading|listening|writing|speaking|mixed",
      "level": "{$currentLevel}",
      "node_type": "lesson|practice|boss",
      "xp_reward": 50
    }
  ]
}

Règles :
- Les 3 premières étapes sont "lesson" (introduction, niveau {$currentLevel})
- Les étapes 4-6 sont "practice" (exercices de renforcement)
- Les étapes 7-9 mélangent compétences (niveau transition)
- Les étapes 10-12 sont plus avancées (début niveau {$nextLevel})
- Les étapes 13-14 sont "practice" intensif
- L'étape 15 est un "boss" (test simulé de l'examen)
- xp_reward : 50 pour lesson, 75 pour practice, 150 pour boss
- Varie les skill_types entre reading, listening, writing, speaking et mixed
- Les titres doivent être en français, courts et motivants
- Les descriptions en français, adaptées à {$language} et {$examName}

Retourne uniquement le JSON.
PROMPT;

        $content = $this->mistral->chat([
            ['role' => 'user', 'content' => $prompt],
        ]);

        if ($content) {
            $decoded = json_decode($content, true);
            if (isset($decoded['nodes']) && is_array($decoded['nodes']) && count($decoded['nodes']) >= 5) {
                return $decoded['nodes'];
            }
        }

        return $this->getFallbackRoadmap($language, $examName, $currentLevel, $nextLevel);
    }

    private function getFallbackRoadmap(string $language, string $examName, string $level, string $nextLevel): array
    {
        return [
            ['sort_order' => 1, 'title' => 'Bases essentielles', 'description' => "Révisez les fondamentaux du {$language} niveau {$level}", 'icon' => 'book', 'skill_type' => 'reading', 'level' => $level, 'node_type' => 'lesson', 'xp_reward' => 50],
            ['sort_order' => 2, 'title' => 'Vocabulaire courant', 'description' => "Maîtrisez le vocabulaire essentiel pour le {$examName}", 'icon' => 'brain', 'skill_type' => 'reading', 'level' => $level, 'node_type' => 'lesson', 'xp_reward' => 50],
            ['sort_order' => 3, 'title' => 'Écoute active', 'description' => "Entraînez votre compréhension orale en {$language}", 'icon' => 'headphones', 'skill_type' => 'listening', 'level' => $level, 'node_type' => 'lesson', 'xp_reward' => 50],
            ['sort_order' => 4, 'title' => 'Grammaire en contexte', 'description' => 'Pratiquez les structures grammaticales clés', 'icon' => 'pen', 'skill_type' => 'writing', 'level' => $level, 'node_type' => 'practice', 'xp_reward' => 75],
            ['sort_order' => 5, 'title' => 'Compréhension écrite', 'description' => "Analysez des textes authentiques en {$language}", 'icon' => 'book', 'skill_type' => 'reading', 'level' => $level, 'node_type' => 'practice', 'xp_reward' => 75],
            ['sort_order' => 6, 'title' => 'Expression orale', 'description' => 'Pratiquez la prononciation et la fluidité', 'icon' => 'mic', 'skill_type' => 'speaking', 'level' => $level, 'node_type' => 'practice', 'xp_reward' => 75],
            ['sort_order' => 7, 'title' => 'Compétences mixtes', 'description' => 'Combinez lecture et écoute pour progresser', 'icon' => 'zap', 'skill_type' => 'mixed', 'level' => $level, 'node_type' => 'lesson', 'xp_reward' => 50],
            ['sort_order' => 8, 'title' => 'Rédaction guidée', 'description' => "Écrivez des textes structurés en {$language}", 'icon' => 'pen', 'skill_type' => 'writing', 'level' => $level, 'node_type' => 'practice', 'xp_reward' => 75],
            ['sort_order' => 9, 'title' => 'Dialogue interactif', 'description' => 'Simulez des conversations réalistes', 'icon' => 'mic', 'skill_type' => 'speaking', 'level' => $level, 'node_type' => 'practice', 'xp_reward' => 75],
            ['sort_order' => 10, 'title' => "Niveau {$nextLevel} : Introduction", 'description' => "Découvrez les exigences du niveau {$nextLevel}", 'icon' => 'star', 'skill_type' => 'mixed', 'level' => $nextLevel, 'node_type' => 'lesson', 'xp_reward' => 50],
            ['sort_order' => 11, 'title' => 'Textes avancés', 'description' => "Abordez des textes complexes niveau {$nextLevel}", 'icon' => 'book', 'skill_type' => 'reading', 'level' => $nextLevel, 'node_type' => 'practice', 'xp_reward' => 75],
            ['sort_order' => 12, 'title' => 'Écoute avancée', 'description' => 'Comprenez des enregistrements rapides et naturels', 'icon' => 'headphones', 'skill_type' => 'listening', 'level' => $nextLevel, 'node_type' => 'practice', 'xp_reward' => 75],
            ['sort_order' => 13, 'title' => 'Révision intensive', 'description' => 'Consolidez toutes vos compétences acquises', 'icon' => 'target', 'skill_type' => 'mixed', 'level' => $nextLevel, 'node_type' => 'practice', 'xp_reward' => 75],
            ['sort_order' => 14, 'title' => "Simulation {$examName}", 'description' => "Entraînez-vous dans les conditions réelles de l'examen", 'icon' => 'zap', 'skill_type' => 'mixed', 'level' => $nextLevel, 'node_type' => 'practice', 'xp_reward' => 75],
            ['sort_order' => 15, 'title' => 'Examen final', 'description' => "Test complet simulant le {$examName} - Prouvez votre niveau !", 'icon' => 'trophy', 'skill_type' => 'mixed', 'level' => $nextLevel, 'node_type' => 'boss', 'xp_reward' => 150],
        ];
    }

    private function getNextLevel(string $level): string
    {
        $levels = ['A1' => 'A2', 'A2' => 'B1', 'B1' => 'B2', 'B2' => 'C1', 'C1' => 'C2', 'C2' => 'C2'];
        return $levels[$level] ?? 'B1';
    }
}
