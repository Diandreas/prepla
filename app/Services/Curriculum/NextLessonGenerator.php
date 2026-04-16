<?php

namespace App\Services\Curriculum;

use App\Models\CurriculumSkeleton;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\LearningPathNode;
use App\Models\User;
use App\Models\UserError;
use App\Services\AI\MistralService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

/**
 * Pilier 9: Just-In-Time lesson generator.
 *
 * Generates the next lesson based on:
 * - The current macro objective from the skeleton
 * - Recent categorized errors (Pillar 4)
 * - Previous lessons (continuity)
 * - User's current level
 */
class NextLessonGenerator
{
    public function __construct(
        protected MistralService $mistral,
        protected CurriculumPlannerService $planner
    ) {}

    /**
     * Generate the next lesson for a user.
     * This is called JIT when the user clicks "Next Lesson".
     */
    public function generate(User $user): ?Lesson
    {
        $profile = $user->profile?->load('targetExam.language');
        if (!$profile || !$profile->targetExam) {
            return null;
        }

        $skeleton = CurriculumSkeleton::where('user_id', $user->id)->first();
        if (!$skeleton) {
            return null;
        }

        $currentObjective = $skeleton->currentObjective();
        if (!$currentObjective) {
            return null; // All objectives completed
        }

        // Check if there's already an unconsumed lesson for this objective
        $existingLesson = Lesson::where('user_id', $user->id)
            ->where('skeleton_objective_index', $skeleton->current_objective_index)
            ->first();

        if ($existingLesson) {
            return $existingLesson;
        }

        // Gather context for the prompt
        $context = $this->gatherContext($user, $skeleton);

        // Determine if this should be a consolidation lesson
        $isConsolidation = $skeleton->consecutive_failures >= 2;
        $status = $isConsolidation ? 'consolidation' : 'published';

        // Generate via Mistral
        $lessonData = $this->generateWithMistral($user, $context, $currentObjective, $isConsolidation);

        if (!$lessonData) {
            return null;
        }

        // Create a LearningPathNode dynamically for this lesson
        $node = LearningPathNode::create([
            'exam_id' => $profile->target_exam_id,
            'chapter_name' => $currentObjective['title'] ?? 'Lesson',
            'chapter_order' => $skeleton->current_objective_index,
            'sort_order' => 0,
            'title' => $lessonData['title'] ?? $currentObjective['title'],
            'description' => $lessonData['concept'] ?? '',
            'icon' => $this->getIconForConcept($currentObjective['concept'] ?? ''),
            'skill_type' => $this->getSkillTypeFromConcept($currentObjective['concept'] ?? ''),
            'level' => $profile->current_level ?? 'A1',
            'xp_reward' => 30,
            'node_type' => 'lesson',
            'exercises_count' => 3,
        ]);

        // Create the lesson
        $lesson = Lesson::create([
            'user_id' => $user->id,
            'node_id' => $node->id,
            'skeleton_objective_index' => $skeleton->current_objective_index,
            'title' => $lessonData['title'] ?? $currentObjective['title'],
            'concept' => $currentObjective['concept'] ?? null,
            'theory_markdown' => $lessonData['theory_markdown'] ?? '',
            'key_takeaways' => $lessonData['key_takeaways'] ?? [],
            'common_mistakes' => $lessonData['common_mistakes'] ?? [],
            'comprehension_quiz' => $lessonData['comprehension_quiz'] ?? [],
            'based_on_errors' => $context['recent_errors'] ?? [],
            'status' => $status,
            'generated_at' => now(),
        ]);

        return $lesson;
    }

    /**
     * Gather all context for the prompt.
     */
    private function gatherContext(User $user, CurriculumSkeleton $skeleton): array
    {
        // Recent categorized errors
        $recentErrors = UserError::where('user_id', $user->id)
            ->where('mastered', false)
            ->whereNotNull('error_category')
            ->where('error_category', '!=', 'session_mistake')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($e) => [
                'category' => $e->error_category,
                'subcategory' => $e->subcategory,
                'question' => $e->question_text,
                'user_answer' => $e->user_answer,
                'correct_answer' => $e->correct_answer,
            ])
            ->toArray();

        // Previous lesson titles (continuity)
        $previousLessons = Lesson::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->pluck('title')
            ->toArray();

        // Completed objectives
        $completedObjectives = collect($skeleton->objectives)
            ->filter(fn ($o) => ($o['status'] ?? '') === 'done')
            ->pluck('title')
            ->toArray();

        return [
            'recent_errors' => $recentErrors,
            'previous_lessons' => $previousLessons,
            'completed_objectives' => $completedObjectives,
            'level' => $user->profile->current_level ?? 'A1',
            'native_language' => $user->profile->native_language ?? 'Français',
            'language' => $user->profile->targetExam->language->name ?? 'English',
            'exam_name' => $user->profile->targetExam->name ?? 'Language Exam',
        ];
    }

    /**
     * Generate lesson content via Mistral.
     */
    private function generateWithMistral(User $user, array $context, array $objective, bool $isConsolidation): ?array
    {
        $langKey = strtolower($context['language']);
        $level = strtolower($context['level']);
        $concept = str_replace('.', '_', $objective['concept'] ?? 'general');
        $hasErrors = !empty($context['recent_errors']);

        // HYBRID CACHING LOGIC
        // If it's a standard lesson (no major errors, no consolidation), check the static library
        if (!$isConsolidation && !$hasErrors) {
            $cachePath = storage_path("app/lessons/{$langKey}/{$level}/{$concept}.json");
            
            if (File::exists($cachePath)) {
                $cachedJson = File::get($cachePath);
                $decodedCache = json_decode($cachedJson, true);
                if ($decodedCache && isset($decodedCache['theory_markdown'])) {
                    return $decodedCache;
                }
            }
        }

        $errorsText = '';
        if (!empty($context['recent_errors'])) {
            $errorsText = "The student's 5 most recent errors:\n";
            foreach (array_slice($context['recent_errors'], 0, 5) as $e) {
                $errorsText .= "- [{$e['category']}] Question: {$e['question']} | Student said: {$e['user_answer']} | Correct: {$e['correct_answer']}\n";
            }
        }

        $previousText = !empty($context['previous_lessons'])
            ? "Previous lessons: " . implode(', ', $context['previous_lessons'])
            : "This is the student's first lesson.";

        $consolidationInstructions = $isConsolidation
            ? "\n\nIMPORTANT: This is a CONSOLIDATION lesson. The student has failed exercises on this concept 2+ times. You must:\n- Explain the concept differently than before (use more examples, simpler language)\n- Focus on the specific errors listed above\n- Add extra practice on the weak points\n- Be more encouraging and provide more scaffolding"
            : '';

        $prompt = <<<PROMPT
You are a {$context['language']} teacher for a {$context['level']} student (native: {$context['native_language']}).
The student is preparing for: {$context['exam_name']}.

Current learning objective: {$objective['title']}
Concept: {$objective['concept']}

{$errorsText}
{$previousText}
{$consolidationInstructions}

Generate a complete lesson in JSON format:
{
  "title": "Lesson title in {$context['language']}",
  "concept": "Brief concept description",
  "theory_markdown": "Full lesson content in Markdown (500-800 words). Include:\n  - Clear explanation of the rule/concept\n  - 5+ practical examples with translations to {$context['native_language']}\n  - Color-coding hints using **bold** for key terms\n  - A section 'Common Mistakes' with 3 typical traps\n  - Tables for grammar rules or vocabulary when appropriate. IMPORTANT: Tables must NOT have blank lines between rows.\n  Write the lesson primarily in {$context['language']} with {$context['native_language']} translations for key terms.",
  "key_takeaways": ["takeaway 1", "takeaway 2", "takeaway 3"],
  "common_mistakes": [
    {"mistake": "description", "correction": "correct form", "tip": "how to remember"}
  ],
  "comprehension_quiz": [
    {
      "question": "Question text",
      "options": ["A", "B", "C", "D"],
      "correct_answer": "B",
      "explanation": "Why B is correct"
    }
  ]
}

Generate exactly 3 comprehension quiz questions that test understanding of the lesson content.
The theory_markdown should be detailed, engaging, and use Markdown formatting (headers, bold, lists).
PROMPT;

        $messages = [
            [
                'role' => 'system',
                'content' => "You are an expert language teacher who creates engaging, clear, and pedagogically sound lessons. Always respond in valid JSON format ONLY."
            ],
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $this->mistral->chat($messages);
        if (!$response) {
            Log::warning('NextLessonGenerator: Mistral returned null for lesson generation');
            return $this->getDefaultLesson($objective, $context);
        }

        $decoded = json_decode($response, true);
        if (!$decoded || !isset($decoded['theory_markdown'])) {
            Log::warning('NextLessonGenerator: Invalid JSON from Mistral', ['response' => $response]);
            return $this->getDefaultLesson($objective, $context);
        }

        // SAVE CACHE: If this was a standard lesson generation, save it to the static library
        if (!$isConsolidation && !$hasErrors) {
            $cacheDir = storage_path("app/lessons/{$langKey}/{$level}");
            if (!File::exists($cacheDir)) {
                File::makeDirectory($cacheDir, 0755, true);
            }
            $cachePath = "{$cacheDir}/{$concept}.json";
            File::put($cachePath, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return $decoded;
    }

    /**
     * Fallback default lesson if Mistral fails.
     */
    private function getDefaultLesson(array $objective, array $context): array
    {
        return [
            'title' => $objective['title'] ?? 'Lesson',
            'concept' => $objective['concept'] ?? 'general',
            'theory_markdown' => "# {$objective['title']}\n\nThis lesson covers **{$objective['concept']}**.\n\n*Content is being generated. Please try again in a moment.*",
            'key_takeaways' => ['Practice makes perfect', 'Review the concept regularly', 'Focus on the key rules'],
            'common_mistakes' => [],
            'comprehension_quiz' => [],
        ];
    }

    /**
     * Get a skill icon based on the concept taxonomy.
     */
    private function getIconForConcept(string $concept): string
    {
        if (str_starts_with($concept, 'grammar')) return '📝';
        if (str_starts_with($concept, 'vocabulary')) return '📚';
        if (str_starts_with($concept, 'reading')) return '📖';
        if (str_starts_with($concept, 'writing')) return '✍️';
        if (str_starts_with($concept, 'listening')) return '👂';
        if (str_starts_with($concept, 'speaking')) return '🗣️';
        return '📘';
    }

    /**
     * Map concept taxonomy to skill_type.
     */
    private function getSkillTypeFromConcept(string $concept): string
    {
        $prefix = explode('.', $concept)[0] ?? 'grammar';
        $mapping = [
            'grammar' => 'grammar',
            'vocabulary' => 'vocabulary',
            'reading' => 'reading',
            'writing' => 'writing',
            'listening' => 'listening',
            'speaking' => 'speaking',
            'exam' => 'reading',
        ];
        return $mapping[$prefix] ?? 'grammar';
    }
}
