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

    // ─── Nouveau test complet 3 sections ────────────────────────────────────

    /**
     * Génère le test complet en UN seul appel Mistral (sections A + B + C).
     */
    public function generateFullTest(
        string $language,
        string $languageNative,
        string $examName,
        string $nativeLanguage
    ): array {
        $prompt = <<<PROMPT
You are a language testing expert. Generate a placement test for {$examName} ({$language}).
The student's native language is {$nativeLanguage} — do not test skills obvious in that language.

Return a single valid JSON object with this exact structure (no extra text):
{
  "section_a": [
    {"id":"a1","level":"A1","text":"Question in French","sentence":"sentence in {$language} (or empty string)","options":["A","B","C","D"],"correct_answer":"A"},
    {"id":"a2","level":"A1","text":"...","sentence":"...","options":["A","B","C","D"],"correct_answer":"B"},
    {"id":"a3","level":"A2","text":"...","sentence":"...","options":["A","B","C","D"],"correct_answer":"C"},
    {"id":"a4","level":"A2","text":"...","sentence":"...","options":["A","B","C","D"],"correct_answer":"A"},
    {"id":"a5","level":"B1","text":"...","sentence":"...","options":["A","B","C","D"],"correct_answer":"D"},
    {"id":"a6","level":"B1","text":"...","sentence":"...","options":["A","B","C","D"],"correct_answer":"B"},
    {"id":"a7","level":"B2","text":"...","sentence":"...","options":["A","B","C","D"],"correct_answer":"A"},
    {"id":"a8","level":"C1","text":"...","sentence":"...","options":["A","B","C","D"],"correct_answer":"D"}
  ],
  "section_b": {
    "passage": "A 80-120 word authentic passage in {$language} on any topic",
    "questions": [
      {"id":"b1","level":"B1","text":"Question about the passage in French","options":["A","B","C","D"],"correct_answer":"B"},
      {"id":"b2","level":"B2","text":"...","options":["A","B","C","D"],"correct_answer":"A"},
      {"id":"b3","level":"C1","text":"...","options":["A","B","C","D"],"correct_answer":"C"}
    ]
  },
  "section_c": {
    "prompt": "One clear writing instruction in French for a short essay or paragraph (2-3 sentences max), adapted to {$examName}"
  }
}

Rules:
- section_a: 8 grammar/vocabulary MCQ — TWO EACH at levels A1 and A2 (so a single
  slip doesn't flip the whole placement verdict), one each at B1 B2 C1, options
  are full text strings (NOT letters), correct_answer is the letter "A","B","C"
  or "D" matching the correct option index (A=first, B=second...)
- section_b: 3 reading comprehension MCQ about the passage, options are full text strings, correct_answer is letter
- section_c: one essay prompt suited to {$examName} level
- All instructions (text, prompt) in French; language content in {$language}
- Return ONLY the JSON, no markdown, no explanation
PROMPT;

        // Up to 2 attempts, same pattern as ExerciseGeneratorService: retry the
        // whole batch once if questions are structurally broken (duplicate/empty
        // options, correct_answer pointing outside the options array) rather than
        // trusting Mistral's output as-is — this pipeline previously had NO
        // validation at all, unlike ExerciseGeneratorService.
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $content = $this->mistral->chat([['role' => 'user', 'content' => $prompt]]);
            if (!$content) {
                continue;
            }

            // Strip markdown code fences if present
            $clean = preg_replace('/^```(?:json)?\s*/m', '', $content);
            $clean = preg_replace('/```\s*$/m', '', $clean);
            $decoded = json_decode(trim($clean), true);

            if (
                !isset($decoded['section_a'], $decoded['section_b'], $decoded['section_c']) ||
                !is_array($decoded['section_a']) || count($decoded['section_a']) < 6 ||
                !isset($decoded['section_b']['passage'], $decoded['section_b']['questions']) ||
                !isset($decoded['section_c']['prompt'])
            ) {
                continue;
            }

            $validA = $this->dropInvalidQuestions($decoded['section_a']);
            $validB = $this->dropInvalidQuestions($decoded['section_b']['questions']);
            if (count($validA) < 6 || count($validB) < 2) {
                continue; // too many broken questions dropped — retry the whole batch
            }
            $decoded['section_a'] = $validA;
            $decoded['section_b']['questions'] = $validB;

            return $decoded;
        }

        Log::warning('PlacementTestService: generateFullTest AI failed, using fallback');
        return $this->getFallbackFullTest($language, $examName);
    }

    /**
     * Drop MCQ questions with structurally broken options/correct_answer —
     * duplicate/empty options, or correct_answer letter pointing outside the
     * options array. Same validation family as
     * ExerciseGeneratorService::dropInvalidChoiceQuestions().
     */
    private function dropInvalidQuestions(array $questions): array
    {
        return array_values(array_filter($questions, function ($q) {
            $opts = $q['options'] ?? null;
            if (!is_array($opts) || count($opts) < 2) {
                return false;
            }
            $normalized = array_map(fn($o) => is_string($o) ? trim(mb_strtolower($o)) : null, $opts);
            if (in_array(null, $normalized, true) || in_array('', $normalized, true)) {
                return false;
            }
            if (count(array_unique($normalized)) !== count($normalized)) {
                return false;
            }
            $ca = $q['correct_answer'] ?? null;
            if (!is_string($ca) || !preg_match('/^[A-Z]$/', strtoupper(trim($ca)))) {
                return false;
            }
            $idx = ord(strtoupper(trim($ca))) - 65;
            return $idx >= 0 && $idx < count($opts);
        }, $questions));
    }

    /**
     * Évalue le niveau de départ : A0, A1, ou B1.
     *
     * Le placement ne mesure PAS le niveau final CEFR — il détermine uniquement
     * d'où l'utilisateur doit commencer dans le programme :
     *   A0 = débutant complet (jamais étudié la langue)
     *   A1 = quelques bases, peut suivre le programme depuis A1
     *   B1 = intermédiaire, peut sauter les modules A1/A2
     *
     * Seuils volontairement stricts : mieux vaut démarrer trop bas que trop haut.
     */
    public function evaluateFullLevel(array $questions, array $answers, string $essayText): string
    {
        // 1. Compter les bonnes réponses sur les questions de niveau A1/A2 seulement
        //    (Les questions B2/C1 du test servent à disqualifier les faux-B1, pas à attribuer un niveau élevé)
        $totalQuestions = count($questions);
        $correctA1A2 = 0;
        $correctB1   = 0;
        $totalA1A2   = 0;
        $totalB1     = 0;

        foreach ($questions as $q) {
            $level   = $q['level'] ?? 'B1';
            $given   = strtoupper(trim($answers[$q['id']] ?? ''));
            $correct = strtoupper(trim($q['correct_answer'] ?? $q['correct'] ?? ''));
            $isCorrect = ($given === $correct && $given !== '');

            if (in_array($level, ['A1', 'A2'])) {
                $totalA1A2++;
                if ($isCorrect) $correctA1A2++;
            } elseif ($level === 'B1') {
                $totalB1++;
                if ($isCorrect) $correctB1++;
            }
        }

        // 2. Décision — seuils stricts
        //    B1 : doit maîtriser les questions A1/A2 ET au moins la moitié des B1
        $a1a2Rate = $totalA1A2 > 0 ? $correctA1A2 / $totalA1A2 : 0;
        $b1Rate   = $totalB1   > 0 ? $correctB1   / $totalB1   : 0;

        if ($a1a2Rate >= 0.75 && $b1Rate >= 0.5) {
            $startLevel = 'B1';
        } elseif ($a1a2Rate >= 0.5) {
            $startLevel = 'A1';
        } else {
            $startLevel = 'A0';
        }

        // 3. Essay : uniquement pour confirmer B1 (pas pour monter au-dessus).
        //    A BLANK essay is treated the SAME as a too-short one — previously an
        //    empty essay (zero effort) kept the B1 verdict while a short-but-correct
        //    essay (e.g. 35 words) got demoted, rewarding skipping the essay over
        //    attempting it honestly.
        if ($startLevel === 'B1') {
            $words = preg_split('/\s+/', trim($essayText), -1, PREG_SPLIT_NO_EMPTY);
            // Moins de 40 mots (y compris 0 = essai vide) = l'utilisateur ne maîtrise pas assez → on redescend à A1
            if (count($words) < 40) {
                $startLevel = 'A1';
            }
        }

        return $startLevel;
    }

    private function getFallbackFullTest(string $language, string $examName): array
    {
        return [
            'section_a' => [
                ['id' => 'a1', 'level' => 'A1', 'text' => 'Choisissez la bonne réponse :', 'sentence' => 'I ___ a student.', 'options' => ['am', 'is', 'are', 'be'], 'correct_answer' => 'A'],
                ['id' => 'a2', 'level' => 'A1', 'text' => 'Complétez la phrase :', 'sentence' => 'This is ___ book.', 'options' => ['I', 'my', 'me', 'mine'], 'correct_answer' => 'B'],
                ['id' => 'a3', 'level' => 'A2', 'text' => 'Complétez la phrase :', 'sentence' => 'She ___ to school every day.', 'options' => ['go', 'goes', 'going', 'gone'], 'correct_answer' => 'B'],
                ['id' => 'a4', 'level' => 'A2', 'text' => 'Choisissez la bonne forme :', 'sentence' => 'They ___ dinner when I called.', 'options' => ['have', 'had', 'were having', 'has'], 'correct_answer' => 'C'],
                ['id' => 'a5', 'level' => 'B1', 'text' => 'Sélectionnez la réponse correcte :', 'sentence' => 'I have lived here ___ 5 years.', 'options' => ['since', 'for', 'during', 'while'], 'correct_answer' => 'B'],
                ['id' => 'a6', 'level' => 'B1', 'text' => 'Complétez avec la bonne forme :', 'sentence' => 'If I ___ more money, I would travel more.', 'options' => ['have', 'had', 'has', 'having'], 'correct_answer' => 'B'],
                ['id' => 'a7', 'level' => 'B2', 'text' => 'Sélectionnez la bonne structure :', 'sentence' => 'Not only ___ the test, but she also won a prize.', 'options' => ['she passed', 'did she pass', 'she did pass', 'passed she'], 'correct_answer' => 'B'],
                ['id' => 'a8', 'level' => 'C1', 'text' => 'Choisissez la forme correcte :', 'sentence' => 'Had I known about the meeting, I ___ attended.', 'options' => ['would have', 'will have', 'would', 'should'], 'correct_answer' => 'A'],
            ],
            'section_b' => [
                'passage' => 'Technology has transformed the way people communicate and work. Social media platforms allow instant sharing of information across the globe, while remote work tools enable employees to collaborate from different locations. However, these advancements come with challenges: privacy concerns, digital addiction, and the risk of misinformation spreading rapidly online.',
                'questions' => [
                    ['id' => 'b1', 'level' => 'B1', 'text' => 'Quel est le sujet principal du texte ?', 'options' => ['La cuisine traditionnelle', "L'impact de la technologie", 'Les voyages en avion', 'Le sport professionnel'], 'correct_answer' => 'B'],
                    ['id' => 'b2', 'level' => 'B2', 'text' => 'Selon le texte, quel est un inconvénient de la technologie ?', 'options' => ['Coût élevé', 'Problèmes de confidentialité', 'Lenteur des connexions', 'Manque de logiciels'], 'correct_answer' => 'B'],
                    ['id' => 'b3', 'level' => 'C1', 'text' => 'Que sous-entend l\'auteur par "misinformation spreading rapidly" ?', 'options' => ['Les nouvelles sont lentes', "Les fausses informations se propagent vite", 'La technologie est fiable', 'Internet est sécurisé'], 'correct_answer' => 'B'],
                ],
            ],
            'section_c' => [
                'prompt' => "Rédigez un court paragraphe (environ 150 mots) sur les avantages et inconvénients des réseaux sociaux dans la vie quotidienne. Donnez votre opinion personnelle et justifiez-la avec des exemples.",
            ],
        ];
    }
}
