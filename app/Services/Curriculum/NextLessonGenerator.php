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
        $nativeKey = strtolower(preg_replace('/[^a-z]/i', '', $context['native_language']));
        $level = strtolower($context['level']);
        $concept = str_replace('.', '_', $objective['concept'] ?? 'general');
        $hasErrors = !empty($context['recent_errors']);

        // HYBRID CACHING LOGIC
        // Cache key includes native_language so a French speaker doesn't get an English-explained lesson
        if (!$isConsolidation && !$hasErrors) {
            $cachePath = storage_path("app/lessons/{$langKey}_via_{$nativeKey}/{$level}/{$concept}.json");
            
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

        // CEFR-tiered language policy for explanations:
        //   A0/A1/A2 → explanations 100% in NATIVE language (target language only in examples)
        //   B1/B2    → explanations 70% native, 30% target
        //   C1/C2    → explanations primarily in target language
        $upperLevel = strtoupper($context['level']);
        $isBeginner = in_array($upperLevel, ['A0', 'A1', 'A2'], true);
        $isIntermediate = in_array($upperLevel, ['B1', 'B2'], true);

        if ($isBeginner) {
            $languagePolicy = "CRITICAL: The student is a beginner ({$upperLevel}) and DOES NOT SPEAK {$context['language']} fluently. " .
                "Write ALL explanations, instructions, headings, key points, takeaways, common mistakes, AND quiz questions ENTIRELY in {$context['native_language']}. " .
                "Only the {$context['language']} example words/phrases themselves should be in {$context['language']}, and they MUST be followed by their {$context['native_language']} translation. " .
                "Example format for vocabulary: \"**hello** (en {$context['language']}) = bonjour (en {$context['native_language']})\". " .
                "DO NOT write paragraphs in {$context['language']}. A beginner cannot read them.";
            $titleLang = $context['native_language'];
            $quizLang = $context['native_language'];
        } elseif ($isIntermediate) {
            $languagePolicy = "The student is intermediate ({$upperLevel}). Write explanations 70% in {$context['native_language']} and 30% in {$context['language']}. " .
                "Provide translations for key terms. Quiz questions in {$context['language']} with native-language instructions.";
            $titleLang = $context['language'];
            $quizLang = $context['language'];
        } else {
            $languagePolicy = "The student is advanced ({$upperLevel}). Write the lesson primarily in {$context['language']}, with {$context['native_language']} translations only for rare/technical terms.";
            $titleLang = $context['language'];
            $quizLang = $context['language'];
        }

        $prompt = <<<PROMPT
You are a {$context['language']} teacher for a {$context['level']} student whose native language is {$context['native_language']}.
The student is preparing for: {$context['exam_name']}.

Current learning objective: {$objective['title']}
Concept: {$objective['concept']}

{$languagePolicy}

{$errorsText}
{$previousText}
{$consolidationInstructions}

Generate a complete lesson in JSON format:
{
  "title": "Lesson title in {$titleLang}",
  "concept": "Brief concept description",
  "theory_markdown": "Full lesson content in Markdown (500-800 words). Follow the language policy above. Structure:\n  - 3 to 5 H2 sections separated by '## Section Title' — each section short enough to fit on one mobile screen (≈150 words max per section)\n  - Clear pedagogical explanation of the rule/concept\n  - 5+ practical examples in {$context['language']} with {$context['native_language']} translations\n  - Use **bold** for key terms\n  - Tables for grammar rules or vocabulary when appropriate. IMPORTANT: no blank lines between table rows.\n  - End with a short 'Récap' / 'Summary' section",
  "key_takeaways": ["3 takeaways following the language policy"],
  "common_mistakes": [
    {"mistake": "description (following language policy)", "correction": "correct form", "tip": "how to remember (in {$context['native_language']} for beginners)"}
  ],
  "comprehension_quiz": [
    {
      "question": "Question text in {$quizLang}",
      "options": ["Full text of option 1", "Full text of option 2", "Full text of option 3", "Full text of option 4"],
      "correct_answer": "The full text of the correct option — must match ONE of the options above EXACTLY, character for character (not a letter like 'B')",
      "explanation": "Why that option is correct (in {$context['native_language']} for beginners)"
    }
  ]
}

Generate exactly 3 comprehension quiz questions that test understanding of the lesson content.
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
            $cacheDir = storage_path("app/lessons/{$langKey}_via_{$nativeKey}/{$level}");
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
