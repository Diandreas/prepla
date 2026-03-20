<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\LearningPathNode;
use Illuminate\Database\Seeder;

class SyllabusSeeder extends Seeder
{
    public function run(): void
    {
        $exams = Exam::all();

        foreach ($exams as $exam) {
            $this->seedIeltsSyllabus($exam);
        }
    }

    private function seedIeltsSyllabus($exam)
    {
        $chapters = [
            [
                'name' => 'Fondamentaux & Stratégies',
                'nodes' => [
                    ['title' => 'Maîtriser les QCM', 'skill' => 'reading', 'level' => 'A1'],
                    ['title' => 'Stratégies d\'écoute', 'skill' => 'listening', 'level' => 'A1'],
                    ['title' => 'Introduction au Writing Task 1', 'skill' => 'writing', 'level' => 'A2'],
                ]
            ],
            [
                'name' => 'Vocabulaire & Grammaire',
                'nodes' => [
                    ['title' => 'Connecteurs logiques', 'skill' => 'writing', 'level' => 'B1'],
                    ['title' => 'Phrasal Verbs essentiels', 'skill' => 'speaking', 'level' => 'B1'],
                    ['title' => 'Temps du passé avancés', 'skill' => 'writing', 'level' => 'B2'],
                ]
            ],
            [
                'name' => 'Maîtrise de la Compréhension',
                'nodes' => [
                    ['title' => 'Identifier les opinions', 'skill' => 'reading', 'level' => 'B2'],
                    ['title' => 'Suivre des discussions complexes', 'skill' => 'listening', 'level' => 'C1'],
                    ['title' => 'Vocabulaire académique C1', 'skill' => 'reading', 'level' => 'C1'],
                ]
            ],
            [
                'name' => 'Expression Intensive',
                'nodes' => [
                    ['title' => 'Argumentation structurée', 'skill' => 'writing', 'level' => 'C1'],
                    ['title' => 'Fluidité et Prononciation', 'skill' => 'speaking', 'level' => 'C2'],
                    ['title' => 'Essais critiques', 'skill' => 'writing', 'level' => 'C2'],
                ]
            ],
            [
                'name' => 'Simulations Finales',
                'nodes' => [
                    ['title' => 'Examen Blanc : Reading', 'skill' => 'reading', 'level' => 'C2'],
                    ['title' => 'Examen Blanc : Listening', 'skill' => 'listening', 'level' => 'C2'],
                    ['title' => 'Le Grand Boss : Full Mock Exam', 'skill' => 'mixed', 'level' => 'C2', 'type' => 'boss'],
                ]
            ],
        ];

        foreach ($chapters as $cIndex => $chapter) {
            foreach ($chapter['nodes'] as $nIndex => $node) {
                LearningPathNode::updateOrCreate(
                    [
                        'exam_id' => $exam->id,
                        'title' => $node['title']
                    ],
                    [
                        'chapter_name' => $chapter['name'],
                        'chapter_order' => $cIndex + 1,
                        'sort_order' => $nIndex + 1,
                        'description' => "Objectif : {$node['title']}",
                        'icon' => $this->getIcon($node['skill']),
                        'skill_type' => $node['skill'],
                        'level' => $node['level'],
                        'node_type' => $node['type'] ?? 'lesson',
                        'xp_reward' => isset($node['type']) && $node['type'] === 'boss' ? 200 : 50,
                    ]
                );
            }
        }
    }

    private function getIcon($skill)
    {
        return match($skill) {
            'reading' => 'book',
            'listening' => 'headphones',
            'writing' => 'pen',
            'speaking' => 'mic',
            default => 'zap',
        };
    }
}
