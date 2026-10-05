<?php

namespace App\Services\Content;

use App\Models\{Exam, ExamSection, Exercise, ExerciseType};

class OralStarter
{
    public function ensure(Exam $exam, string $level): void
    {
        $language = $exam->language->slug;
        if (!in_array($level, ['A0', 'A1', 'A2'], true) || !in_array($language, ['english', 'french', 'german'], true)) {
            return;
        }
        $section = ExamSection::where('exam_id', $exam->id)->where('skill_type', 'speaking')->first()
            ?? ExamSection::create(['exam_id' => $exam->id, 'slug' => 'guided-speaking', 'name' => 'Expression orale', 'skill_type' => 'speaking']);
        $type = ExerciseType::firstOrCreate(['section_id' => $section->id, 'slug' => 'guided-introduction'], [
            'component_key' => 'speaking-recorder', 'skill_type' => 'speaking', 'name' => 'Me présenter à voix haute',
        ]);
        $languageLabel = ['english' => 'anglais', 'french' => 'français', 'german' => 'allemand'][$language];
        $prompts = [
            "Présente-toi en {$languageLabel} avec deux phrases : ton prénom et le pays d’où tu viens.",
            "En {$languageLabel}, dis où tu habites et si tu travailles ou étudies. Utilise deux phrases courtes.",
            "En {$languageLabel}, dis une chose que tu aimes et une chose que tu n’aimes pas. Tu peux parler d’aliments ou de loisirs.",
        ];
        Exercise::firstOrCreate(['catalog_key' => "oral-introduction-v1:{$exam->id}:{$level}"], [
            'exam_id' => $exam->id, 'exam_section_id' => $section->id, 'exercise_type_id' => $type->id,
            'difficulty' => $level, 'is_ai_generated' => false, 'xp_reward' => 10,
            'content' => ['title' => 'Me présenter à voix haute', 'instructions' => 'Prépare tes idées, puis enregistre des phrases courtes. La correction orale nécessite une connexion et le service de correction disponible.'],
            'questions' => collect($prompts)->map(fn ($prompt, $index) => ['id' => "oral-{$index}", 'type' => 'speaking-recorder', 'text' => $prompt, 'prep_time' => 30, 'speak_time' => 30, 'correct_answer' => null])->all(),
        ]);
    }
}
