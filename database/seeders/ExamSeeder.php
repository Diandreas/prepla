<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExerciseType;
use App\Models\Language;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $languages = config('languages.languages');

        foreach ($languages as $langData) {
            $language = Language::where('slug', $langData['slug'])->first();
            if (!$language) continue;

            foreach ($langData['exams'] as $examData) {
                $exam = Exam::updateOrCreate(
                    ['slug' => $examData['slug']],
                    [
                        'language_id' => $language->id,
                        'name' => $examData['name'],
                        'levels' => $examData['levels'] ?? null,
                        'scoring_type' => $this->getScoringType($examData['slug']),
                        'max_score' => $this->getMaxScore($examData['slug']),
                    ]
                );

                $this->createSections($exam);
            }
        }
    }

    private function getScoringType(string $slug): string
    {
        return match ($slug) {
            'ielts' => 'band',
            'cambridge' => 'level',
            'delf-dalf' => 'level',
            'dele' => 'level',
            'goethe' => 'level',
            'hsk' => 'level',
            'jlpt' => 'level',
            'topik' => 'level',
            'torfl' => 'level',
            default => 'points',
        };
    }

    private function getMaxScore(string $slug): ?int
    {
        return match ($slug) {
            'ielts' => 9,
            'toefl' => 120,
            'tcf' => 699,
            'tef' => 900,
            'siele' => 1000,
            default => null,
        };
    }

    private function createSections(Exam $exam): void
    {
        $sections = [
            ['slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading', 'time_limit' => 60, 'sort_order' => 1],
            ['slug' => 'listening', 'name' => 'Listening', 'skill_type' => 'listening', 'time_limit' => 40, 'sort_order' => 2],
            ['slug' => 'writing', 'name' => 'Writing', 'skill_type' => 'writing', 'time_limit' => 60, 'sort_order' => 3],
            ['slug' => 'speaking', 'name' => 'Speaking', 'skill_type' => 'speaking', 'time_limit' => 15, 'sort_order' => 4],
        ];

        foreach ($sections as $sectionData) {
            $section = ExamSection::updateOrCreate(
                ['exam_id' => $exam->id, 'slug' => $sectionData['slug']],
                $sectionData
            );

            $this->createExerciseTypes($section);
        }
    }

    private function createExerciseTypes(ExamSection $section): void
    {
        $types = match ($section->skill_type) {
            'reading' => [
                ['slug' => 'mcq', 'name' => 'Multiple Choice', 'component_key' => 'mcq'],
                ['slug' => 'true-false-ng', 'name' => 'True/False/Not Given', 'component_key' => 'true-false-ng'],
                ['slug' => 'matching', 'name' => 'Matching', 'component_key' => 'matching'],
                ['slug' => 'gap-fill', 'name' => 'Gap Fill', 'component_key' => 'gap-fill'],
            ],
            'listening' => [
                ['slug' => 'mcq', 'name' => 'Multiple Choice', 'component_key' => 'mcq'],
                ['slug' => 'note-completion', 'name' => 'Note Completion', 'component_key' => 'note-completion'],
                ['slug' => 'matching', 'name' => 'Matching', 'component_key' => 'matching'],
            ],
            'writing' => [
                ['slug' => 'essay', 'name' => 'Essay Writing', 'component_key' => 'essay-editor'],
                ['slug' => 'visual-task', 'name' => 'Visual Task', 'component_key' => 'visual-task-writing'],
            ],
            'speaking' => [
                ['slug' => 'speaking-response', 'name' => 'Speaking Response', 'component_key' => 'speaking-recorder'],
            ],
            default => [],
        };

        foreach ($types as $typeData) {
            ExerciseType::updateOrCreate(
                ['section_id' => $section->id, 'slug' => $typeData['slug']],
                array_merge($typeData, ['skill_type' => $section->skill_type])
            );
        }
    }
}
