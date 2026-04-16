<?php

namespace App\Services\Curriculum;

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\User;
use App\Models\UserError;
use App\Services\AI\MistralService;
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

        return CurriculumSkeleton::updateOrCreate(
            ['user_id' => $user->id, 'exam_id' => $exam->id],
            [
                'objectives' => $objectives,
                'current_objective_index' => 0,
                'consecutive_successes' => 0,
                'consecutive_failures' => 0,
            ]
        );
    }

    /**
     * Reassess the skeleton after each lesson.
     * Can insert, remove, or reorder objectives based on performance.
     */
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
    public function recordLessonOutcome(User $user, float $accuracyPercent): string
    {
        $skeleton = CurriculumSkeleton::where('user_id', $user->id)->first();
        if (!$skeleton)
            return 'no_skeleton';

        if ($accuracyPercent >= 80) {
            // Success
            $skeleton->consecutive_successes++;
            $skeleton->consecutive_failures = 0;
            $skeleton->save();

            if ($skeleton->consecutive_successes >= 3) {
                // High performer: they still need to practice this objective, but the next one after could be skipped
                $skeleton->advanceToPractice();
                return 'skip_ahead';
            } else {
                // Normal progression goes to practice phase
                $skeleton->advanceToPractice();
                return 'advance';
            }
        } else {
            // Failure
            $skeleton->consecutive_failures++;
            $skeleton->consecutive_successes = 0;
            $skeleton->save();

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
        return collect($objectives)->map(function ($obj, $index) {
            return [
                'order' => $obj['order'] ?? $index,
                'title' => $obj['title'] ?? "Objective $index",
                'concept' => $obj['concept'] ?? 'general',
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
