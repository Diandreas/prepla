<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\MockExam;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MockExamSeeder extends Seeder
{
    public function run(): void
    {
        $baseDir = database_path('data/content/mock_exams');
        if (!File::exists($baseDir)) {
            return;
        }

        // Scan all exam folders (tef, tcf, etc.)
        $directories = File::directories($baseDir);

        foreach ($directories as $dir) {
            $examSlug = basename($dir);
            $exam = Exam::where('slug', $examSlug)->first();
            if (!$exam) continue;

            $jsonFiles = File::files($dir);

            // Fetch the blueprint (assuming the main one without levels, or adapt based on json)
            // For now, let's grab the first blueprint for this exam.
            $blueprint = ExamBlueprint::where('exam_id', $exam->id)->first();
            if (!$blueprint) continue;

            foreach ($jsonFiles as $file) {
                if ($file->getExtension() !== 'json') continue;

                $data = json_decode(File::get($file), true);
                if (!$data) continue;

                $mockExam = MockExam::updateOrCreate(
                    ['blueprint_id' => $blueprint->id, 'title' => $data['mock_exam_name'] ?? ('Simulation ' . $file->getFilenameWithoutExtension())],
                    ['description' => 'Test statique pré-généré : ' . $file->getFilename(), 'is_published' => true]
                );

                // Assuming structure: { sections: [ { slug: 'comprehension-ecrite', parts: [ { exercises: [ ... ] } ] } ] }
                foreach ($data['sections'] ?? [] as $sectionData) {
                    $section = ExamSection::where('exam_id', $exam->id)
                        ->where('slug', $sectionData['slug'])
                        ->first();
                    
                    if (!$section) continue;

                    foreach ($sectionData['parts'] ?? [] as $part) {
                        foreach ($part['exercises'] ?? [] as $exData) {
                            // Normalize JSON type to component_key
                            $rawType = $exData['type'] ?? 'mcq';
                            $componentKey = match ($rawType) {
                                'essay', 'letter-writing' => 'essay-editor',
                                'true-false' => 'true-false-ng',
                                'true-false-not-given' => 'true-false-ng',
                                'matching-headings' => 'matching',
                                'gap-fill', 'open-cloze' => 'gap-fill',
                                'word-formation' => 'word-formation',
                                'key-word-transformation' => 'key-word-transformation',
                                'sentence-completion' => 'sentence-completion',
                                'speaking' => 'speaking-recorder',
                                default => $rawType,
                            };

                            // Find or auto-create the ExerciseType for this section
                            $type = ExerciseType::where('section_id', $section->id)
                                ->where('component_key', $componentKey)
                                ->first();

                            if (!$type) {
                                // Try globally (any section of this exam)
                                $type = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id))
                                    ->where('component_key', $componentKey)
                                    ->first();
                            }

                            if (!$type) {
                                // Auto-create the exercise type for this section
                                $skillType = match (true) {
                                    str_contains($section->slug, 'writ') || str_contains($section->slug, 'schreib') || str_contains($section->slug, 'production-ecrit') || str_contains($section->slug, 'expression-ecrit') => 'writing',
                                    str_contains($section->slug, 'speak') || str_contains($section->slug, 'sprech') || str_contains($section->slug, 'production-oral') || str_contains($section->slug, 'expression-oral') => 'speaking',
                                    str_contains($section->slug, 'listen') || str_contains($section->slug, 'hoer') || str_contains($section->slug, 'comprehension-oral') => 'listening',
                                    default => 'reading',
                                };
                                $type = ExerciseType::create([
                                    'section_id' => $section->id,
                                    'slug' => $componentKey . '-' . $section->id,
                                    'component_key' => $componentKey,
                                    'name' => ucfirst(str_replace('-', ' ', $componentKey)),
                                    'skill_type' => $skillType,
                                ]);
                            }
                                

                            // Use the first question's ID as a stable unique key
                            $firstQuestionId = $exData['questions'][0]['id'] ?? null;
                            $uniqueKey = $firstQuestionId 
                                ? md5($mockExam->id . '_' . $firstQuestionId)
                                : md5($mockExam->id . '_' . $section->id . '_' . json_encode($exData['content']));

                            Exercise::updateOrCreate(
                                [
                                    'mock_exam_id' => $mockExam->id,
                                    'exam_section_id' => $section->id,
                                    'content->_uid' => $uniqueKey,
                                ],
                                [
                                    'exercise_type_id' => $type->id,
                                    'exam_id' => $exam->id,
                                    'content' => array_merge($exData['content'], ['_uid' => $uniqueKey]),
                                    'questions' => $exData['questions'],
                                    'difficulty' => $exData['difficulty'] ?? 'B1',
                                    'xp_reward' => 20,
                                    'is_ai_generated' => false,
                                ]
                            );
                        }
                    }
                }
            }
        }
    }
}
