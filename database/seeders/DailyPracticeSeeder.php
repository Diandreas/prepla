<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\LearningPathNode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DailyPracticeSeeder extends Seeder
{
    public function run(): void
    {
        $baseDir = database_path('data/content/daily_practice');
        if (!File::exists($baseDir)) {
            return;
        }

        // e.g. daily_practice/french/...
        $languageDirs = File::directories($baseDir);

        foreach ($languageDirs as $langDir) {
            $jsonFiles = File::files($langDir);

            // We assume a generic "TEF" exam acts as the anchor for French, 
            // since LearningPathNode is currently bound to exam_id.
            $exam = Exam::where('slug', 'tef')->first();
            if (!$exam) continue;

            $grammarType = ExerciseType::where('component_key', 'mcq')->first();
            if (!$grammarType) continue;

            foreach ($jsonFiles as $file) {
                if ($file->getExtension() !== 'json') continue;

                $data = json_decode(File::get($file), true);
                if (!$data) continue;

                $level = $data['level'] ?? 'A1';
                $topic = $data['topic'] ?? 'Sujet non défini';
                
                $node = LearningPathNode::updateOrCreate(
                    ['exam_id' => $exam->id, 'level' => $level, 'chapter_name' => "Catégorie $topic"],
                    [
                        'chapter_order' => 1,
                        'sort_order' => 1,
                        'title' => $topic . " ($level)",
                        'description' => "Leçons validées basées sur notre modèle JSON.",
                        'skill_type' => 'grammar',
                        'node_type' => 'lesson',
                        'xp_reward' => 50,
                    ]
                );

                $exerciseIds = [];
                foreach ($data['exercises'] ?? [] as $exData) {
                    $ex = Exercise::create([
                        'exercise_type_id' => $grammarType->id, // Adapt according to $exData['type'] logic
                        'exam_id' => $exam->id,
                        'content' => $exData['content'],
                        'questions' => $exData['questions'],
                        'difficulty' => $exData['difficulty'] ?? $level,
                        'xp_reward' => 10,
                        'is_ai_generated' => false,
                    ]);
                    $exerciseIds[] = $ex->id;
                }

                $node->update(['exercise_ids' => $exerciseIds, 'exercises_count' => count($exerciseIds)]);
            }
        }
    }
}
