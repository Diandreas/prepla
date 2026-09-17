<?php

namespace App\Services\Content;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseType;

/** Original general-language practice; no remote generation or official exam claims. */
class StarterPracticeLibrary
{
    private array $catalogues = [];

    public function template(Exam $exam, ExerciseType $type, string $level): ?array
    {
        $exam->loadMissing('language');
        $type->loadMissing('section');
        // Never substitute a silent reading exercise for an audio/oral task.
        if ($type->section?->exam_id !== $exam->id || $type->skill_type !== 'reading'
            || !in_array($type->slug, ['mcq', 'gap-fill', 'matching'], true)
            || $type->slug !== $type->component_key) {
            return null;
        }

        $language = $exam->language?->slug;
        if (!in_array($language, ['english', 'french', 'german'], true)) {
            return null;
        }
        $this->catalogues[$language] ??= require resource_path("content/practice-starters/{$language}.php");

        return $this->catalogues[$language][$level][$type->component_key] ?? null;
    }

    public function ensure(Exam $exam, ExerciseType $type, string $level): ?Exercise
    {
        $template = $this->template($exam, $type, $level);
        if (!$template) {
            return null;
        }
        [$valid, $error, $questions] = ExerciseSchemaRegistry::validateQuestions($type->component_key, $template['questions']);
        if (!$valid) {
            throw new \LogicException('Invalid starter catalogue: ' . $error);
        }
        // Question IDs remain unique when exercises are combined in a session.
        $questions = array_map(fn (array $question) => array_merge($question, [
            'id' => "starter-{$exam->id}-{$type->id}-{$level}-{$question['id']}",
        ]), $questions);

        // Unique key also prevents duplicate rows when two learners start together.
        return Exercise::firstOrCreate([
            'catalog_key' => "starter-v1:{$exam->id}:{$type->id}:{$level}",
        ], [
            'exam_id' => $exam->id,
            'exercise_type_id' => $type->id,
            'exam_section_id' => $type->section_id,
            'difficulty' => $level,
            'is_ai_generated' => false,
            'xp_reward' => 10,
            'content' => array_merge($template['content'], [
                'title' => $template['title'],
                'description' => $template['description'],
                'source' => 'starter-library',
                'source_label' => 'Entraînement général · sans génération IA',
            ]),
            'questions' => $questions,
        ]);
    }
}
