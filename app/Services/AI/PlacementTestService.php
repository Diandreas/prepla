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

    // ─── Test complet : grammaire, comprehension ecrite et orale, redaction ──

    /** Les paliers mesures, du debutant complet (A0, pre-A1) au C2. */
    public const PALIERS = ['A0', 'A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    /**
     * Genere le test complet en UN seul appel Mistral.
     *
     * Format rendu au front d'onboarding (memes cles qu'avant) :
     *  - section_a : QCM de grammaire et vocabulaire, du A0 au C2 ;
     *  - section_b : { passage, questions } ou les questions de comprehension
     *    ORALE portent un champ audio_text (lu a voix haute, comme dans les
     *    exercices d'ecoute) et skill = listening ; les autres sont de la
     *    comprehension ecrite sur le passage (skill = reading) ;
     *  - section_c : { prompt } le sujet de redaction.
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
    {"id":"a1","level":"A0","text":"Question in French","sentence":"sentence in {$language} (or empty string)","options":["A","B","C","D"],"correct_answer":"A"}
  ],
  "section_b": {
    "passage": "A 80-120 word authentic passage in {$language}",
    "questions": [
      {"id":"b1","level":"A2","text":"Question about the passage","options":["A","B","C","D"],"correct_answer":"B"}
    ]
  },
  "listening": {
    "audio_text": "A 50-90 word spoken dialogue or message in {$language}, to be read aloud by text-to-speech",
    "questions": [
      {"id":"l1","level":"A1","text":"Question about what is heard","options":["A","B","C","D"],"correct_answer":"C"}
    ]
  },
  "section_c": {
    "prompt": "One clear writing instruction for a short essay (2-3 sentences max), adapted to {$examName}"
  }
}

Rules:
- section_a: EXACTLY 10 grammar/vocabulary MCQ at these levels, in this order:
  A0, A1, A1, A2, A2, B1, B1, B2, C1, C2 (A0 = absolute beginner: a number,
  a greeting, a very common word).
- section_b: 3 reading comprehension MCQ about the passage, at levels A2, B1, B2.
- listening: 3 listening MCQ at levels A1, A2, B1. They must be answerable ONLY
  by listening to audio_text; never copy the answer into the question.
- options are full text strings (NOT letters); correct_answer is the letter
  "A","B","C" or "D" matching the correct option (A=first, B=second...).
  Vary the position of the correct answer.
- Language content in {$language}; instructions may be in {$language} or French.
- Return ONLY the JSON, no markdown, no explanation
PROMPT;

        // Deux essais au plus : on refait tout le lot quand les questions sont
        // cassees (options vides ou en double, bonne reponse hors des options)
        // plutot que de croire l'IA sur parole.
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $content = $this->mistral->chat([['role' => 'user', 'content' => $prompt]]);
            if (!$content) {
                continue;
            }

            $clean = preg_replace('/^```(?:json)?\s*/m', '', $content);
            $clean = preg_replace('/```\s*$/m', '', $clean);
            $decoded = json_decode(trim($clean), true);

            if (
                !isset($decoded['section_a'], $decoded['section_b'], $decoded['section_c']) ||
                !is_array($decoded['section_a']) || count($decoded['section_a']) < 6 ||
                !isset($decoded['section_b']['passage'], $decoded['section_b']['questions']) ||
                // Les questions de la section B doivent etre une LISTE : rendues en
                // chaine ou en nombre, elles partaient dans array_map et le test de
                // placement tombait en 500 des son ouverture, pour un compte neuf.
                !is_array($decoded['section_b']['questions']) || count($decoded['section_b']['questions']) < 2 ||
                !isset($decoded['section_c']['prompt'])
            ) {
                continue;
            }

            $validA = $this->dropInvalidQuestions($decoded['section_a']);
            $validB = $this->dropInvalidQuestions($decoded['section_b']['questions']);
            if (count($validA) < 6 || count($validB) < 2) {
                continue; // trop de questions cassees : on refait tout le lot
            }

            $lecture = array_map(fn ($q) => array_merge($q, ['skill' => 'reading']), $validB);

            return [
                'section_a' => $this->normaliserNiveaux($validA),
                'section_b' => [
                    'passage' => $decoded['section_b']['passage'],
                    'questions' => array_merge(
                        $this->normaliserNiveaux($lecture),
                        $this->ecouteValideOuSecours($decoded['listening'] ?? null, $language),
                    ),
                ],
                'section_c' => $decoded['section_c'],
            ];
        }

        Log::warning('PlacementTestService: generateFullTest AI failed, using fallback', [
            'langue' => $language,
        ]);

        return $this->getFallbackFullTest($language, $examName);
    }

    /**
     * L'ecoute proposee par l'IA si elle tient debout, sinon celle de secours.
     *
     * Une ecoute sans texte a lire, ou trop courte pour porter des questions,
     * ne mesure rien : on garde quand meme l'oral dans le test, avec les items
     * fixes de la langue visee.
     */
    private function ecouteValideOuSecours(mixed $ecoute, string $language): array
    {
        $texte = is_array($ecoute) && is_string($ecoute['audio_text'] ?? null) ? trim($ecoute['audio_text']) : '';
        $questions = is_array($ecoute) && is_array($ecoute['questions'] ?? null)
            ? $this->dropInvalidQuestions($ecoute['questions'])
            : [];

        if (count(preg_split('/\s+/u', $texte, -1, PREG_SPLIT_NO_EMPTY)) < 20 || count($questions) < 2) {
            return $this->ecouteDeSecours($language);
        }

        $sortie = [];
        foreach ($questions as $i => $q) {
            $sortie[] = array_merge($q, [
                'id' => 'l'.($i + 1),
                'skill' => 'listening',
                'audio_text' => $texte,
            ]);
        }

        return $this->normaliserNiveaux($sortie);
    }

    /** Un niveau inconnu (« pre-A1 », vide...) est ramene a un palier connu. */
    private function normaliserNiveaux(array $questions): array
    {
        return array_map(function ($q) {
            $niveau = strtoupper(trim((string) ($q['level'] ?? '')));
            $q['level'] = match (true) {
                in_array($niveau, self::PALIERS, true) => $niveau,
                $niveau === '' || str_contains($niveau, 'PRE') => 'A0',
                default => 'B1',
            };

            return $q;
        }, $questions);
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
            if (!is_array($q)) {
                return false;
            }
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
        }));
    }

    /**
     * Evalue le niveau de depart, de A0 a C2.
     *
     * On monte palier par palier : un palier est acquis quand au moins la moitie
     * de ses questions (grammaire, lecture et ecoute confondues) sont justes, et
     * on s'arrete au premier palier rate. Un candidat qui devine juste au C1
     * mais rate le B1 reste donc au A2 : mieux vaut demarrer trop bas que trop
     * haut.
     *
     * La redaction confirme les niveaux hauts : une copie vide ou tres courte ne
     * permet pas de demarrer au B1 ou au-dessus, quel que soit le QCM.
     */
    public function evaluateFullLevel(array $questions, array $answers, string $essayText): string
    {
        $justes = array_fill_keys(self::PALIERS, 0);
        $totaux = array_fill_keys(self::PALIERS, 0);

        foreach ($questions as $q) {
            if (!is_array($q) || !isset($q['id'])) {
                continue;
            }
            $level = strtoupper((string) ($q['level'] ?? 'B1'));
            if (!isset($totaux[$level])) {
                $level = 'B1';
            }
            $given = strtoupper(trim((string) ($answers[$q['id']] ?? '')));
            $correct = strtoupper(trim((string) ($q['correct_answer'] ?? $q['correct'] ?? '')));

            $totaux[$level]++;
            if ($given !== '' && $given === $correct) {
                $justes[$level]++;
            }
        }

        $niveau = 'A0';
        foreach (self::PALIERS as $palier) {
            if ($totaux[$palier] === 0) {
                // Palier absent du test (ancien test sans A0 ni C2) : il ne
                // bloque pas la montee, mais il ne la donne pas non plus.
                continue;
            }
            if ($justes[$palier] / $totaux[$palier] < 0.5) {
                break;
            }
            // Reussir le A0 seul ne fait pas un A1 : on reste au depart.
            $niveau = $palier;
        }

        $mots = count(preg_split('/\s+/u', trim($essayText), -1, PREG_SPLIT_NO_EMPTY));
        $rang = array_search($niveau, self::PALIERS, true);

        // Moins de 40 mots (copie vide comprise) : pas plus haut que A2.
        if ($mots < 40 && $rang > array_search('A2', self::PALIERS, true)) {
            return 'A2';
        }
        // Moins de 80 mots : pas plus haut que B1.
        if ($mots < 80 && $rang > array_search('B1', self::PALIERS, true)) {
            return 'B1';
        }

        return $niveau;
    }

    /**
     * Le test de secours, dans LA LANGUE VISEE.
     *
     * Il n'y a pas de question « generique qui marche pour toutes les langues » :
     * une phrase a trous est forcement ecrite dans une langue. L'ancien repli
     * servait de l'anglais a tout le monde, et un candidat telc Deutsch
     * repartait avec un niveau d'allemand mesure sur sa grammaire anglaise.
     *
     * Dix items par langue, du A0 au C2. Pour toute autre langue, on ne devine
     * pas : voir estServable().
     */
    private const REPLIS = [
        'english' => [
            ['A0', 'three', ['2', '3', '4', '5'], 'B', 'Quel chiffre correspond à ce mot ?'],
            ['A1', 'I ___ a student.', ['am', 'is', 'are', 'be'], 'A'],
            ['A1', 'This is ___ book.', ['I', 'my', 'me', 'mine'], 'B'],
            ['A2', 'She ___ to school every day.', ['go', 'goes', 'going', 'gone'], 'B'],
            ['A2', 'I have lived here ___ five years.', ['since', 'for', 'during', 'while'], 'B'],
            ['B1', 'If I ___ more money, I would travel more.', ['have', 'had', 'has', 'having'], 'B'],
            ['B1', 'The meeting was ___ because of the storm.', ['cancelled', 'cancelling', 'cancel', 'cancels'], 'A'],
            ['B2', 'Not only ___ the test, but she also won a prize.', ['she passed', 'did she pass', 'she did pass', 'passed she'], 'B'],
            ['C1', 'Had I known about the meeting, I ___ attended.', ['would have', 'will have', 'would', 'should'], 'A'],
            ['C2', 'Scarcely ___ the house when it began to rain.', ['we had left', 'had we left', 'did we leave', 'we left'], 'B'],
        ],
        'french' => [
            ['A0', 'trois', ['2', '3', '4', '5'], 'B', 'Quel chiffre correspond à ce mot ?'],
            ['A1', 'Je ___ étudiant.', ['suis', 'es', 'est', 'être'], 'A'],
            ['A1', "C'est ___ livre.", ['je', 'mon', 'moi', 'le mien'], 'B'],
            ['A2', 'Elle ___ à l\'école tous les jours.', ['va', 'vas', 'aller', 'allé'], 'A'],
            ['A2', "J'habite ici ___ cinq ans.", ['depuis', 'pendant', 'pour', 'dans'], 'A'],
            ['B1', "Si j'___ plus d'argent, je voyagerais davantage.", ['ai', 'avais', 'aurai', 'aurais'], 'B'],
            ['B1', 'La réunion a été ___ à cause de la tempête.', ['annulée', 'annulant', 'annuler', 'annule'], 'A'],
            ['B2', "Il faut que tu ___ avant la fin de la semaine.", ['viens', 'viendras', 'viennes', 'venir'], 'C'],
            ['C1', "___ que je sois fatigué, je terminerai ce travail.", ['Bien', 'Malgré', 'Quoique', 'Pourtant'], 'C'],
            ['C2', "Il eût fallu qu'il ___ plus tôt.", ['vient', 'vînt', 'viendrait', 'venait'], 'B'],
        ],
        'german' => [
            ['A0', 'drei', ['2', '3', '4', '5'], 'B', 'Quel chiffre correspond à ce mot ?'],
            ['A1', 'Ich ___ Student.', ['bin', 'bist', 'ist', 'sein'], 'A'],
            ['A1', 'Das ist ___ Buch.', ['ich', 'mein', 'mir', 'meiner'], 'B'],
            ['A2', 'Sie ___ jeden Tag zur Schule.', ['geht', 'gehst', 'gehen', 'gegangen'], 'A'],
            ['A2', 'Ich wohne ___ fünf Jahren hier.', ['seit', 'für', 'während', 'in'], 'A'],
            ['B1', 'Wenn ich mehr Geld ___, würde ich mehr reisen.', ['habe', 'hätte', 'hatte', 'haben'], 'B'],
            ['B1', 'Das Treffen wurde wegen des Sturms ___.', ['abgesagt', 'absagen', 'absagend', 'abgesagen'], 'A'],
            ['B2', 'Nicht nur ___ sie die Prüfung, sondern sie gewann auch einen Preis.', ['sie bestand', 'bestand sie', 'sie hat bestanden', 'bestehen sie'], 'B'],
            ['C1', 'Hätte ich von dem Treffen gewusst, ___ ich teilgenommen.', ['hätte', 'würde', 'wäre', 'sollte'], 'A'],
            ['C2', 'Ungeachtet ___ Einwände wurde das Projekt genehmigt.', ['dem', 'den', 'der', 'die'], 'C'],
        ],
    ];

    /** Le texte de la comprehension ecrite et ses questions, dans la langue visee. */
    private const PASSAGES = [
        'english' => ['Technology has changed how people work. Remote tools let colleagues work together from different cities, but they also blur the line between work and private life. Many companies now set rules so that messages sent in the evening do not have to be answered before the next morning.', [
            ['A2', 'What is the text mainly about?', ['Cooking', 'How technology changes work', 'Air travel', 'Professional sport'], 'B'],
            ['B1', 'What do many companies do?', ['They ban remote tools', 'They close offices at night', 'They set rules for evening messages', 'They pay for overtime'], 'C'],
            ['B2', 'Which problem does the text mention?', ['The tools are too expensive', 'Work and private life are harder to separate', 'Colleagues live too far away', 'Messages get lost'], 'B'],
        ]],
        'french' => ['La technologie a changé la façon de travailler. Les outils à distance permettent à des collègues de travailler ensemble depuis des villes différentes, mais ils brouillent aussi la frontière entre le travail et la vie privée. Beaucoup d\'entreprises fixent désormais des règles pour qu\'un message envoyé le soir n\'ait pas à recevoir de réponse avant le lendemain.', [
            ['A2', 'Quel est le sujet principal du texte ?', ['La cuisine', 'La technologie et le travail', 'Les voyages en avion', 'Le sport professionnel'], 'B'],
            ['B1', 'Que font beaucoup d\'entreprises ?', ['Elles interdisent le travail à distance', 'Elles ferment les bureaux le soir', 'Elles fixent des règles pour les messages du soir', 'Elles paient les heures supplémentaires'], 'C'],
            ['B2', 'Quel problème le texte mentionne-t-il ?', ['Les outils coûtent trop cher', 'La frontière entre travail et vie privée devient floue', 'Les collègues habitent trop loin', 'Les messages se perdent'], 'B'],
        ]],
        'german' => ['Die Technik hat die Arbeit verändert. Mit digitalen Werkzeugen arbeiten Kolleginnen und Kollegen aus verschiedenen Städten zusammen, doch die Grenze zwischen Beruf und Privatleben verschwimmt dabei. Viele Firmen vereinbaren deshalb Regeln: Eine Nachricht am Abend muss erst am nächsten Morgen beantwortet werden.', [
            ['A2', 'Worum geht es im Text vor allem?', ['Ums Kochen', 'Um Technik und Arbeit', 'Um Flugreisen', 'Um Profisport'], 'B'],
            ['B1', 'Was machen viele Firmen?', ['Sie verbieten digitale Werkzeuge', 'Sie schließen abends die Büros', 'Sie vereinbaren Regeln für Nachrichten am Abend', 'Sie bezahlen Überstunden'], 'C'],
            ['B2', 'Welches Problem nennt der Text?', ['Die Werkzeuge sind zu teuer', 'Beruf und Privatleben sind schwerer zu trennen', 'Die Kollegen wohnen zu weit weg', 'Nachrichten gehen verloren'], 'B'],
        ]],
    ];

    /**
     * La comprehension orale de secours : un message lu a voix haute (champ
     * audio_text, le meme que celui des exercices d'ecoute) et trois questions.
     */
    private const ECOUTES = [
        'english' => ["Hi Tom, it's Sarah. I'm sorry, I can't come to the cinema tonight because I have to work late. Can we go on Saturday instead? The film starts at seven, so let's meet at half past six in front of the cinema.", [
            ['A1', 'Who is calling?', ['Tom', 'Sarah', 'The cinema', 'A colleague'], 'B'],
            ['A2', 'When does the film start?', ['At six', 'At half past six', 'At seven', 'At eight'], 'C'],
            ['B1', "Why can't Sarah come tonight?", ['She is ill', 'She has to work late', 'She has no money', 'She has already seen the film'], 'B'],
        ]],
        'french' => ["Salut Tom, c'est Sarah. Désolée, je ne peux pas venir au cinéma ce soir parce que je dois travailler tard. On peut y aller samedi à la place ? Le film commence à sept heures, alors retrouvons-nous à six heures et demie devant le cinéma.", [
            ['A1', 'Qui appelle ?', ['Tom', 'Sarah', 'Le cinéma', 'Une collègue'], 'B'],
            ['A2', 'À quelle heure commence le film ?', ['À six heures', 'À six heures et demie', 'À sept heures', 'À huit heures'], 'C'],
            ['B1', 'Pourquoi Sarah ne peut-elle pas venir ce soir ?', ['Elle est malade', 'Elle doit travailler tard', "Elle n'a pas d'argent", 'Elle a déjà vu le film'], 'B'],
        ]],
        'german' => ['Hallo Tom, hier ist Sarah. Tut mir leid, ich kann heute Abend nicht ins Kino kommen, weil ich lange arbeiten muss. Können wir stattdessen am Samstag gehen? Der Film beginnt um sieben Uhr, also treffen wir uns um halb sieben vor dem Kino.', [
            ['A1', 'Wer ruft an?', ['Tom', 'Sarah', 'Das Kino', 'Eine Kollegin'], 'B'],
            ['A2', 'Wann beginnt der Film?', ['Um sechs Uhr', 'Um halb sieben', 'Um sieben Uhr', 'Um acht Uhr'], 'C'],
            ['B1', 'Warum kann Sarah heute Abend nicht kommen?', ['Sie ist krank', 'Sie muss lange arbeiten', 'Sie hat kein Geld', 'Sie hat den Film schon gesehen'], 'B'],
        ]],
    ];

    /** Les consignes de redaction, dans la langue visee. */
    private const SUJETS = [
        'english' => 'Write about 150 words on the advantages and drawbacks of social media in everyday life. Give your own opinion and support it with examples.',
        'french' => "Rédigez environ 150 mots sur les avantages et les inconvénients des réseaux sociaux dans la vie quotidienne. Donnez votre opinion et justifiez-la par des exemples.",
        'german' => 'Schreiben Sie etwa 150 Wörter über die Vor- und Nachteile sozialer Netzwerke im Alltag. Äußern Sie Ihre eigene Meinung und begründen Sie sie mit Beispielen.',
    ];

    /**
     * Sait-on servir un test de secours dans cette langue ?
     *
     * Si non, l'appelant doit renvoyer l'apprenant vers la declaration de son
     * niveau plutot que de lui faire passer un test dans une autre langue.
     */
    public function estServable(string $language): bool
    {
        return isset(self::REPLIS[$this->cle($language)]);
    }

    private function cle(string $language): string
    {
        return match (mb_strtolower(trim($language))) {
            'english', 'anglais' => 'english',
            'french', 'français', 'francais' => 'french',
            'german', 'allemand', 'deutsch' => 'german',
            default => '',
        };
    }

    private function ecouteDeSecours(string $language): array
    {
        [$texte, $items] = self::ECOUTES[$this->cle($language) ?: 'english'];

        $questions = [];
        foreach ($items as $rang => [$niveau, $question, $choix, $bonne]) {
            $questions[] = [
                'id' => 'l'.($rang + 1),
                'level' => $niveau,
                'skill' => 'listening',
                'text' => $question,
                'audio_text' => $texte,
                'options' => $choix,
                'correct_answer' => $bonne,
            ];
        }

        return $questions;
    }

    private function getFallbackFullTest(string $language, string $examName): array
    {
        $cle = $this->cle($language) ?: 'english';
        [$passage, $itemsLecture] = self::PASSAGES[$cle];

        $sectionA = [];
        foreach (self::REPLIS[$cle] as $rang => $item) {
            [$niveau, $phrase, $choix, $bonne] = $item;
            $sectionA[] = [
                'id' => 'a'.($rang + 1),
                'level' => $niveau,
                'text' => $item[4] ?? 'Choisissez la bonne réponse :',
                'sentence' => $phrase,
                'options' => $choix,
                'correct_answer' => $bonne,
            ];
        }

        $lecture = [];
        foreach ($itemsLecture as $rang => [$niveau, $question, $choix, $bonne]) {
            $lecture[] = [
                'id' => 'b'.($rang + 1),
                'level' => $niveau,
                'skill' => 'reading',
                'text' => $question,
                'options' => $choix,
                'correct_answer' => $bonne,
            ];
        }

        return [
            'section_a' => $sectionA,
            'section_b' => [
                'passage' => $passage,
                'questions' => array_merge($lecture, $this->ecouteDeSecours($language)),
            ],
            'section_c' => ['prompt' => self::SUJETS[$cle]],
        ];
    }
}
