<?php

namespace App\Services\Curriculum;

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\User;
use App\Models\UserError;
use App\Services\AI\MistralService;
use App\Services\LevelAdvancementService;
use Illuminate\Support\Facades\Log;

/**
 * Pilier 9: Curriculum Planner — generates and maintains the adaptive syllabus skeleton.
 *
 * Instead of pre-generating a full roadmap, creates a skeleton of ~30 macro objectives
 * that evolves based on the learner's performance.
 */
class CurriculumPlannerService
{
    public function __construct(
        protected MistralService $mistral
    ) {
    }

    /**
     * Build the initial skeleton for a new user.
     * Called during onboarding instead of RoadmapGeneratorService::generateForUser().
     */
    public function buildSkeleton(User $user, Exam $exam, string $currentLevel): CurriculumSkeleton
    {
        $language = $exam->language->name ?? 'English';
        $examName = $exam->name ?? 'Language Exam';
        $nativeLanguage = $user->profile->native_language ?? 'Français';

        $prompt = $this->buildSkeletonPrompt($language, $examName, $currentLevel, $nativeLanguage);

        $messages = [
            [
                'role' => 'system',
                'content' => "You are an expert language curriculum designer specializing in $examName preparation. You design learning paths that go from the student's current level to their target. You respond ONLY in valid JSON format."
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ]
        ];

        $response = $this->mistral->chat($messages);
        $objectives = $this->parseSkeletonResponse($response, $currentLevel, $language);

        // Mark the first objective as current
        if (!empty($objectives)) {
            $objectives[0]['status'] = 'current';
        }

        $skeleton = CurriculumSkeleton::updateOrCreate(
            ['user_id' => $user->id, 'exam_id' => $exam->id],
            [
                'objectives' => $objectives,
                'current_objective_index' => 0,
                'consecutive_successes' => 0,
                'consecutive_failures' => 0,
            ]
        );

        // Chaque palier se termine par son examen, pour tout le monde et des le depart.
        $skeleton->ensureLevelExams();

        return $skeleton->fresh();
    }

    /**
     * Reassess the skeleton after each lesson.
     * Can insert, remove, or reorder objectives based on performance.
     */
    /**
     * Prolonge un parcours termine par une etape au niveau suivant.
     *
     * Jusqu'ici, finir son parcours menait a une impasse : plus aucun objectif
     * courant, donc plus de lecon a ouvrir, et un niveau de profil inchange depuis
     * le test d'entree. Un apprenant qui allait au bout se retrouvait devant un
     * ecran « parcours termine » sans rien a faire, toujours marque debutant.
     *
     * L'examen valide d'abord le niveau ; on écrit ensuite une étape au niveau atteint.
     * Sans IA disponible, on reprend le programme de reference de ce niveau plutot
     * que de laisser l'impasse.
     *
     * @return bool true si de nouveaux objectifs ont ete ajoutes
     */
    public function extendForNextLevel(User $user, LevelAdvancementService $levels): bool
    {
        $skeleton = CurriculumSkeleton::where('user_id', $user->id)->first();
        if (!$skeleton) {
            return false;
        }

        $skeleton->ensureLevelExams();
        $objectives = $skeleton->objectives ?? [];
        if ($objectives === []) {
            return false;
        }

        // On ne prolonge que ce qui est reellement fini.
        foreach ($objectives as $objective) {
            if (($objective['status'] ?? 'pending') !== 'done') {
                return false;
            }
        }

        // Seul l'examen valide un niveau, jamais la simple fin des pratiques.

        $profile = $user->profile()->first();
        $exam = $profile?->targetExam;
        if (!$exam) {
            return false;
        }

        // La nouvelle etape se joue au niveau REELLEMENT valide, celui du profil, et
        // non au niveau affiche par les objectifs deja parcourus. Les deux ont
        // diverge : un apprenant a traverse des objectifs etiquetes B1 en ne faisant
        // que des exercices A1, et le suivre aurait prolonge le malentendu. S'il vient
        // d'etre promu, l'etape se joue donc au cran gagne ; sinon au sien, pour
        // consolider ce qu'il n'a pas encore tenu a 70 %.
        $ladder = CurriculumSkeleton::CEFR_LEVELS;
        $targetLevel = in_array($profile->current_level, $ladder, true) ? $profile->current_level : 'A1';

        $language = $exam->language->name ?? 'English';
        $fresh = $this->parseSkeletonResponse(
            $this->mistral->chat([
                [
                    'role' => 'system',
                    'content' => "You are an expert language curriculum designer. You respond ONLY in valid JSON format.",
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildSkeletonPrompt($language, $exam->name ?? 'Language Exam', $targetLevel, $profile->native_language ?? 'Français'),
                ],
            ]),
            $targetLevel,
            $language
        );

        if ($fresh === []) {
            return false;
        }

        // Dix objectifs suffisent pour une etape : au-dela, le parcours redevient un mur.
        $fresh = array_slice($fresh, 0, 10);
        $start = count($objectives);

        foreach ($fresh as $index => $objective) {
            $objective['order'] = $start + $index;
            $objective['level'] = $objective['level'] ?? $targetLevel;
            $objective['status'] = $index === 0 ? 'current' : 'pending';
            $objectives[] = $objective;
        }

        $skeleton->objectives = $objectives;
        $skeleton->current_objective_index = $start;
        $skeleton->consecutive_failures = 0;
        $skeleton->save();

        // La nouvelle etape se termine elle aussi par son examen.
        $skeleton->ensureLevelExams();

        Log::info('Parcours prolonge', [
            'user_id' => $user->id,
            'niveau' => $targetLevel,
            'objectifs_ajoutes' => count($fresh),
        ]);

        return true;
    }

    /**
     * Apres un examen de palier manque : des reprises sur ce qui n'a pas ete compris,
     * posees AVANT l'epreuve.
     *
     * Reproposer le meme examen a quelqu'un qui vient d'echouer ne lui apprend rien :
     * il le repasserait avec les memes lacunes. Chaque reprise est un objectif a part
     * entiere, donc avec sa lecon et sa pratique, ecrites sur le concept rate. Et comme
     * l'examen n'ouvre que lorsque tout son palier est termine, il se referme de
     * lui-meme le temps de la remediation.
     *
     * @param  string[]  $categories  categories d'erreur relevees pendant l'examen
     * @return int  nombre de reprises posees
     */
    public function insertRemedialBeforeExam(CurriculumSkeleton $skeleton, string $level, array $categories): int
    {
        $examIndex = null;
        foreach ($skeleton->objectives ?? [] as $index => $objective) {
            if (($objective['is_level_exam'] ?? false) === true
                && ($objective['level'] ?? null) === $level
                && ($objective['status'] ?? 'pending') !== 'done') {
                $examIndex = $index;
                break;
            }
        }

        if ($examIndex === null) {
            return 0;
        }

        // Deux reprises au plus : au-dela, la remediation devient un mur.
        $frequencies = array_count_values(array_filter($categories));
        arsort($frequencies);
        $categories = array_slice(array_keys($frequencies), 0, 2);
        if ($categories === []) {
            // Aucune categorie identifiee : on revise le palier dans son ensemble.
            $categories = ['revision.' . strtolower($level)];
        }

        $inserted = 0;
        $premier = null;

        foreach ($categories as $category) {
            // Une reprise deja en attente sur ce concept ne s'empile pas.
            $deja = collect($skeleton->objectives)->contains(
                fn ($objective) => ($objective['is_remedial'] ?? false) === true
                    && ($objective['concept'] ?? null) === $category
                    && ($objective['status'] ?? 'pending') !== 'done'
            );

            if ($deja) {
                continue;
            }

            $position = $examIndex + $inserted - 1; // insertObjective() insere APRES
            $skeleton->insertObjective([
                'title' => 'Reprise : ' . $this->categoryToTitle($category),
                'concept' => $category,
                'level' => $level,
                'status' => 'pending',
                'priority' => 'high',
                'is_remedial' => true,
            ], $position);

            $premier ??= $position + 1;
            $inserted++;
        }

        if ($inserted === 0) {
            return 0;
        }

        // L'apprenant reprend la, pas sur l'examen qu'il vient de manquer.
        $objectives = $skeleton->objectives;
        foreach ($objectives as &$objective) {
            if (($objective['status'] ?? '') === 'current') {
                $objective['status'] = 'pending';
            }
        }
        unset($objective);
        $objectives[$premier]['status'] = 'current';
        $skeleton->objectives = $objectives;
        $skeleton->current_objective_index = $premier;
        $skeleton->save();

        Log::info('Remediation posee apres un examen manque', [
            'user_id' => $skeleton->user_id,
            'niveau' => $level,
            'reprises' => $inserted,
        ]);

        return $inserted;
    }

    public function reassess(User $user): void
    {
        $skeleton = CurriculumSkeleton::where('user_id', $user->id)->first();
        if (!$skeleton)
            return;

        // Get recent categorized errors
        $recentErrors = UserError::where('user_id', $user->id)
            ->where('mastered', false)
            ->whereNotNull('error_category')
            ->where('error_category', '!=', 'session_mistake')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // Group errors by category to find recurring weaknesses
        $errorGroups = $recentErrors->groupBy('error_category')
            ->map(fn($group) => $group->count())
            ->sortDesc();

        $objectives = $skeleton->objectives;

        // Check if there's a weakness not covered by remaining objectives
        foreach ($errorGroups as $category => $count) {
            if ($count >= 3) {
                // Check if this category already has a pending objective
                $alreadyCovered = collect($objectives)->contains(function ($obj) use ($category) {
                    return str_contains(strtolower($obj['concept'] ?? ''), strtolower($category))
                        && in_array($obj['status'] ?? 'pending', ['pending', 'current']);
                });

                if (!$alreadyCovered) {
                    // Insert a remedial objective after the current one
                    $newObjective = [
                        'order' => $skeleton->current_objective_index + 1,
                        'title' => "Consolidation : " . $this->categoryToTitle($category),
                        'concept' => $category,
                        'status' => 'pending',
                        'priority' => 'high',
                    ];
                    $skeleton->insertObjective($newObjective, $skeleton->current_objective_index);
                }
            }
        }

        // Remove objectives that are already mastered (based on category stats)
        $masteredCategories = UserError::where('user_id', $user->id)
            ->where('mastered', true)
            ->whereNotNull('error_category')
            ->pluck('error_category')
            ->unique();

        // Don't remove — just note that we skip them via the normal advanceToNextObjective flow
        // Actual skipping happens when consecutive successes >= 3

        $skeleton->save();
    }

    /**
     * Record a lesson outcome and decide what happens next.
     */
    public function recordLessonOutcome(User $user, ?float $accuracyPercent, ?bool $passed = null): string
    {
        $skeleton = CurriculumSkeleton::where('user_id', $user->id)->first();
        if (!$skeleton)
            return 'no_skeleton';

        // Leçon arrivée sans questions : rien n'a été mesuré. Elle comptait pour 100 %
        // et alimentait la série de réussites — trois leçons sans quiz d'affilée
        // faisaient sauter la suivante, sur un savoir jamais vérifié. Le parcours
        // avance quand même (rester bloqué était le défaut d'avant), mais sans
        // compter comme une réussite.
        if ($accuracyPercent === null) {
            $skeleton->advanceToPractice();

            return 'not_assessed';
        }

        // "Passed" is decided by the lesson quiz (2/3 threshold, see
        // Lesson::isComprehensionPassed). The UI shows the "Practice this concept"
        // CTA for any passing score, so the skeleton MUST move the objective into
        // its practice phase for any passing score too — otherwise the practice
        // session has no 'current_practice' objective to complete and the journey
        // gets stuck. Fall back to the 60% band only when the caller didn't pass
        // an explicit verdict (legacy callers). 60% aligns with the practice
        // mastery threshold (was 80%, too punishing).
        $isPass = $passed ?? ($accuracyPercent >= 60);

        if ($isPass) {
            // Success
            $skeleton->consecutive_successes++;
            $skeleton->consecutive_failures = 0;
            $skeleton->save();

            // Always open the practice phase for the just-learned concept.
            $skeleton->advanceToPractice();

            // A strong streak of high scores (≥80%) lets us skip the *next* lesson.
            if ($accuracyPercent >= 80 && $skeleton->consecutive_successes >= 3
                && !($skeleton->currentObjective()['is_remedial'] ?? false)) {
                return 'skip_ahead';
            }

            return 'advance';
        } else {
            // Failure
            $skeleton->consecutive_failures++;
            $skeleton->consecutive_successes = 0;
            $skeleton->save();

            // Beyond 5 consecutive failures, consolidation (reworded AI content)
            // alone hasn't unblocked the learner — there was previously NO ceiling
            // here, so a learner who never clears the mastery threshold on a
            // concept could stay on it forever. Force-complete the stuck practice
            // objective so the journey can move on; the concept still gets
            // flagged for spaced-repetition review (UserError) rather than lost.
            $STUCK_THRESHOLD = 5;
            if ($skeleton->consecutive_failures >= $STUCK_THRESHOLD) {
                $practiceIndex = $skeleton->practiceObjectiveIndex();
                if ($practiceIndex !== null) {
                    $skeleton->forceCompleteStuckPractice($practiceIndex);
                    return 'unblocked_after_struggle';
                }
            }

            // If 2+ failures, don't advance — next lesson is consolidation
            if ($skeleton->consecutive_failures >= 2) {
                return 'consolidation';
            }

            // First failure: still advance but note it
            return 'retry_concept';
        }
    }

    private function buildSkeletonPrompt(string $language, string $examName, string $level, string $nativeLanguage): string
    {
        return <<<PROMPT
Design a comprehensive learning curriculum skeleton for a student preparing for $examName.

Student profile:
- Current level: $level
- Native language: $nativeLanguage  
- Target: Pass $examName

Create a list of 25-30 macro learning objectives, ordered from most fundamental to most advanced, appropriate for going from $level to the exam's target level.

Each objective should cover a specific concept or skill area. Include a mix of:
- Grammar concepts (tenses, structures, agreements)
- Vocabulary themes (work, education, travel, science etc.)
- Reading skills (skimming, scanning, inference)
- Writing skills (essay structure, coherence, argument)
- Listening skills (detail, inference, main idea)
- Speaking skills (fluency, accuracy, complexity)

Respond in this exact JSON format:
{
  "objectives": [
    {
      "order": 0,
      "title": "Objective title in $language",
      "concept": "grammar.tense.present_simple",
      "level": "$level",
      "status": "pending",
      "priority": "normal"
    }
  ]
}

IMPORTANT:
- The concept field should use dot-notation taxonomy (e.g., grammar.tense.past_simple, vocabulary.theme.work, reading.skill.skimming)
- Titles should be clear and in $language
- Order from foundational to advanced
- For level $level, start with the basics appropriate for that level
- The level field is the CEFR level this objective is practised at (A1, A2, B1, B2, C1 or C2).
  Start at $level and climb gradually: the first objectives stay at $level, the last ones
  reach the level the exam demands. Never go back down.
PROMPT;
    }

    private function parseSkeletonResponse(?string $response, string $level, string $language): array
    {
        if (!$response) {
            return $this->getDefaultSkeleton($level, $language);
        }

        $decoded = json_decode($response, true);
        $objectives = $decoded['objectives'] ?? [];

        if (empty($objectives)) {
            return $this->getDefaultSkeleton($level, $language);
        }

        // Ensure proper structure
        $total = count($objectives);

        return collect($objectives)->map(function ($obj, $index) use ($level, $total) {
            $objectiveLevel = $obj['level'] ?? null;
            if (!is_string($objectiveLevel) || !in_array($objectiveLevel, CurriculumSkeleton::CEFR_LEVELS, true)) {
                $objectiveLevel = CurriculumSkeleton::levelForPosition($level, $index, $total);
            }

            return [
                'order' => $obj['order'] ?? $index,
                'title' => $obj['title'] ?? "Objective $index",
                'concept' => $obj['concept'] ?? 'general',
                // Sans ce niveau, toute la pratique était générée en A1, du premier au
                // dernier objectif — y compris ceux qui visent la fluidité avancée.
                'level' => $objectiveLevel,
                'status' => 'pending',
                'priority' => $obj['priority'] ?? 'normal',
            ];
        })->toArray();
    }

    /**
     * Default skeleton if Mistral fails, adapted by language.
     * Loads robust curriculum from JSON data files.
     */
    private function getDefaultSkeleton(string $level, string $language): array
    {
        $langKey = strtolower($language);
        $fileName = 'english.json'; // fallback

        if (str_contains($langKey, 'german') || str_contains($langKey, 'allemand')) {
            $fileName = 'german.json';
        } elseif (str_contains($langKey, 'french') || str_contains($langKey, 'français')) {
            $fileName = 'french.json';
        } elseif (str_contains($langKey, 'spanish') || str_contains($langKey, 'espagnol')) {
            $fileName = 'spanish.json';
        }

        $path = base_path("database/data/curriculums/{$fileName}");

        if (file_exists($path)) {
            $json = file_get_contents($path);
            $data = json_decode($json, true);
            if (isset($data[$level])) {
                return $data[$level];
            }
        }

        // Ultimate safety fallback if file is missing
        return [
            ['order' => 0, 'title' => 'Basic Fundamentals', 'concept' => 'grammar.basic', 'status' => 'pending', 'priority' => 'normal'],
            ['order' => 1, 'title' => 'Essential Vocabulary', 'concept' => 'vocabulary.basic', 'status' => 'pending', 'priority' => 'normal']
        ];
    }

    /**
     * Convert error category to a readable title.
     */
    private function categoryToTitle(string $category): string
    {
        $titles = [
            'grammar.tense' => 'Les temps verbaux',
            'grammar.agreement' => 'Les accords grammaticaux',
            'grammar.word-order' => "L'ordre des mots",
            'grammar.article' => 'Les articles',
            'vocabulary.lexical' => 'Le lexique',
            'vocabulary.register' => 'Le registre de langue',
            'vocabulary.collocation' => 'Les collocations',
            'spelling' => "L'orthographe",
            'punctuation' => 'La ponctuation',
            'coherence' => 'La cohérence textuelle',
            'writing.structure' => 'La structure rédactionnelle',
            'writing.cohesion' => 'La cohésion du texte',
        ];

        // Try exact match first, then partial match
        if (isset($titles[$category]))
            return $titles[$category];

        foreach ($titles as $key => $title) {
            if (str_starts_with($category, $key))
                return $title;
        }

        return ucfirst(str_replace('.', ' — ', $category));
    }
}
