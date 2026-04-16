<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\AI\MistralService;

class GenerateAllLessons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prepla:generate-lessons {--lang=all : Specific language to generate (e.g. english, french)} {--level=all : Specific level to generate (e.g. A1)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-generates and caches all static markdown lessons from the JSON curriculums.';

    protected MistralService $mistral;

    public function __construct(MistralService $mistral)
    {
        parent::__construct();
        $this->mistral = $mistral;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting the Prepla Lesson Auto-Generator...");
        $this->info("This command will read the curriculum JSON files and generate static Markdown files for each objective.");
        $this->info("If stopped, it will resume where it left off because it skips existing files.");
        $this->line("");

        $langOption = $this->option('lang');
        $levelOption = strtolower($this->option('level'));

        $languages = $langOption === 'all' 
            ? ['english', 'german', 'french', 'spanish'] 
            : [$langOption];

        $totalGenerated = 0;
        $totalSkipped = 0;

        foreach ($languages as $lang) {
            $jsonPath = base_path("database/data/curriculums/{$lang}.json");
            
            if (!File::exists($jsonPath)) {
                $this->warn("Curriculum file not found for {$lang}: {$jsonPath}");
                continue;
            }

            $this->info("Processing language: " . strtoupper($lang));
            $curriculum = json_decode(File::get($jsonPath), true);

            foreach ($curriculum as $level => $objectives) {
                if ($levelOption !== 'all' && strtolower($level) !== $levelOption) {
                    continue; // Skip this level if user specified a specific one
                }

                $this->line("  -> Level: {$level} (" . count($objectives) . " objectives)");

                $cacheDir = storage_path("app/lessons/{$lang}/" . strtolower($level));
                if (!File::exists($cacheDir)) {
                    File::makeDirectory($cacheDir, 0755, true);
                }

                $bar = $this->output->createProgressBar(count($objectives));
                $bar->start();

                foreach ($objectives as $objective) {
                    $concept = str_replace('.', '_', $objective['concept']);
                    $cachePath = "{$cacheDir}/{$concept}.json";

                    if (File::exists($cachePath)) {
                        // Already generated, skip
                        $totalSkipped++;
                        $bar->advance();
                        continue;
                    }

                    // Generate with Mistral
                    $success = $this->generateAndSaveLesson($lang, $level, $objective, $cachePath);
                    if ($success) {
                        $totalGenerated++;
                    } else {
                        $this->error("\nFailed to generate lesson for {$concept}");
                    }

                    $bar->advance();
                    // Sleep slightly to avoid hitting strict rate limits on Mistral if any
                    usleep(500000); // 0.5 seconds
                }

                $bar->finish();
                $this->line("\n");
            }
        }

        $this->info("Generation complete!");
        $this->info("- Successfully generated: {$totalGenerated} lessons/node sets");
        $this->info("- Skipped (already existed): {$totalSkipped}");
    }

    /**
     * Finds or creates a LearningPathNode for the concept.
     */
    private function ensureNode(string $lang, string $level, array $objective) {
        $exam = \App\Models\Exam::whereHas('language', fn($q) => $q->where('name', $lang))->first();
        if (!$exam) return null;

        return \App\Models\LearningPathNode::firstOrCreate(
            ['exam_id' => $exam->id, 'level' => $level, 'title' => $objective['title']],
            [
                'chapter_name' => $objective['title'],
                'description' => $objective['concept'],
                'node_type' => 'lesson',
                'skill_type' => 'grammar', // Fallback
                'xp_reward' => 30
            ]
        );
    }

    private function generateAndSaveLesson(string $lang, string $level, array $objective, string $cachePath): bool
    {
        $nativeLanguage = 'Français'; // Assuming the base user base is French, or it could be generic.
        if ($lang === 'french') {
            $nativeLanguage = 'English'; // If learning French, assume English native for translation examples
        }

        $prompt = <<<PROMPT
You are a {$lang} teacher for a {$level} student (native: {$nativeLanguage}).
Current learning objective: {$objective['title']}
Concept: {$objective['concept']}

Generate a complete, foundational lesson in JSON format:
{
  "title": "Lesson title in {$lang}",
  "concept": "Brief concept description",
  "theory_markdown": "Full lesson content in Markdown (500-800 words). Include:\n  - Clear explanation of the rule/concept\n  - 5+ practical examples with translations to {$nativeLanguage}\n  - Color-coding hints using **bold** for key terms\n  - A section 'Common Mistakes' with 3 typical traps\n  Write the lesson primarily in {$lang} with {$nativeLanguage} translations for key terms.",
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
The theory_markdown should be detailed, engaging, and use Markdown formatting.
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
            return false;
        }

        $decoded = json_decode($response, true);
        if (!$decoded || !isset($decoded['theory_markdown'])) {
            return false;
        }

        File::put($cachePath, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Generate targeted exercises for this node
        $this->generateExercisesForNode($lang, $level, $objective);

        return true;
    }

    private function generateExercisesForNode(string $lang, string $level, array $objective)
    {
        $node = $this->ensureNode($lang, $level, $objective);
        if (!$node) return;

        // Skip if exercises already exist for this node
        if (\App\Models\Exercise::where('node_id', $node->id)->exists()) {
            return;
        }

        $prompt = "Generate 3 diverse exercises (Multiple Choice, Fill in the blanks) for level {$level} {$lang} on the specific concept: '{$objective['title']} ({$objective['concept']})'. Output JSON array of exercises.";
        // Note: In a real production scenario, we'd use a dedicated prompt for Mistral here.
        // For now, let's assume we use the falling logic in PracticeController which picks random exercises if needed,
        // but adding this hook ensures we can scale the content library.
    }
}
