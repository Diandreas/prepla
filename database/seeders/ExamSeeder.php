<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\ExerciseType;
use App\Models\Language;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    /**
     * Map of all exercise type slugs → component_key + display name.
     * Central registry so we don't duplicate definitions.
     */
    private const EXERCISE_TYPE_REGISTRY = [
        // ── Existing components ──
        'mcq'                     => ['name' => 'Multiple Choice',              'component' => 'mcq'],
        'true-false-not-given'    => ['name' => 'True/False/Not Given',         'component' => 'true-false-ng'],
        'yes-no-not-given'        => ['name' => 'Yes/No/Not Given',             'component' => 'true-false-ng'],
        'true-false'              => ['name' => 'Vrai/Faux',                    'component' => 'true-false-ng'],
        'matching'                => ['name' => 'Matching',                     'component' => 'matching'],
        'gap-fill'                => ['name' => 'Gap Fill',                     'component' => 'gap-fill'],
        'essay'                   => ['name' => 'Essay Writing',                'component' => 'essay-editor'],
        'vocabulary-card'         => ['name' => 'Vocabulary Card',              'component' => 'vocabulary-card'],

        // ── Priority 1: Needed by many exams ──
        'sentence-completion'     => ['name' => 'Sentence Completion',          'component' => 'sentence-completion'],
        'short-answer'            => ['name' => 'Short Answer',                 'component' => 'short-answer'],
        'note-completion'         => ['name' => 'Note Completion',              'component' => 'note-completion'],
        'summary-completion'      => ['name' => 'Summary Completion',           'component' => 'summary-completion'],
        'ordering'                => ['name' => 'Ordering / Sequencing',        'component' => 'ordering'],
        'dictation'               => ['name' => 'Dictation',                    'component' => 'dictation'],
        'form-completion'         => ['name' => 'Form Completion',              'component' => 'form-completion'],
        'table-completion'        => ['name' => 'Table Completion',             'component' => 'table-completion'],
        'flow-chart-completion'   => ['name' => 'Flow Chart Completion',        'component' => 'flow-chart-completion'],
        'short-writing'           => ['name' => 'Short Writing',                'component' => 'short-writing'],
        'grammar-mcq'             => ['name' => 'Grammar MCQ',                  'component' => 'mcq'],

        // ── IELTS specific ──
        'matching-headings'       => ['name' => 'Matching Headings',            'component' => 'matching'],
        'matching-information'    => ['name' => 'Matching Information',          'component' => 'matching'],
        'matching-features'       => ['name' => 'Matching Features',            'component' => 'matching'],
        'matching-sentence-endings' => ['name' => 'Matching Sentence Endings',  'component' => 'matching'],
        'plan-map-diagram-labeling' => ['name' => 'Plan/Map/Diagram Labeling', 'component' => 'diagram-labeling'],
        'diagram-labeling'        => ['name' => 'Diagram Labeling',             'component' => 'diagram-labeling'],
        'graph-description'       => ['name' => 'Graph/Chart Description',      'component' => 'graph-description'],
        'letter-writing'          => ['name' => 'Letter Writing',               'component' => 'essay-editor'],

        // ── TOEFL specific ──
        'insert-text'             => ['name' => 'Insert Text',                  'component' => 'insert-text'],
        'prose-summary'           => ['name' => 'Prose Summary',                'component' => 'summary-completion'],
        'fill-table'              => ['name' => 'Fill a Table',                 'component' => 'table-completion'],
        'integrated-writing'      => ['name' => 'Integrated Writing',           'component' => 'integrated-task'],
        'academic-discussion'     => ['name' => 'Academic Discussion',          'component' => 'academic-discussion'],
        'integrated-speaking'     => ['name' => 'Integrated Speaking',          'component' => 'integrated-task'],

        // ── Cambridge specific ──
        'mcq-cloze'               => ['name' => 'Multiple-choice Cloze',        'component' => 'gap-fill'],
        'open-cloze'              => ['name' => 'Open Cloze',                   'component' => 'open-cloze'],
        'word-formation'          => ['name' => 'Word Formation',               'component' => 'word-formation'],
        'key-word-transformation' => ['name' => 'Key Word Transformation',      'component' => 'key-word-transformation'],
        'gapped-text'             => ['name' => 'Gapped Text',                  'component' => 'gapped-text'],
        'multiple-matching'       => ['name' => 'Multiple Matching',            'component' => 'multiple-matching'],
        'summary-writing'         => ['name' => 'Summary Writing',              'component' => 'essay-editor'],
        'article-writing'         => ['name' => 'Article Writing',              'component' => 'essay-editor'],
        'letter-email-writing'    => ['name' => 'Letter/Email Writing',         'component' => 'essay-editor'],
        'report-writing'          => ['name' => 'Report Writing',               'component' => 'essay-editor'],
        'review-writing'          => ['name' => 'Review Writing',               'component' => 'essay-editor'],

        // ── DELF/DALF specific ──
        'synthesis'               => ['name' => 'Synthèse de documents',        'component' => 'synthesis'],
        'synthesis-essay'         => ['name' => 'Synthèse + Essai',             'component' => 'synthesis'],

        // ── Speaking types ──
        'speaking-response'       => ['name' => 'Speaking Response',            'component' => 'speaking-recorder'],
        'speaking-long-turn'      => ['name' => 'Speaking Long Turn',           'component' => 'speaking-recorder'],
        'speaking-discussion'     => ['name' => 'Speaking Discussion',          'component' => 'speaking-recorder'],
        'role-play'               => ['name' => 'Role Play',                    'component' => 'role-play'],
        // ── Interactive speaking (live per-turn correction, render via role-play) ──
        'oral-debate'             => ['name' => 'Débat argumenté',              'component' => 'role-play'],
        'negotiation'             => ['name' => 'Négociation / se mettre d\'accord', 'component' => 'role-play'],
        'speaking-elicitation'    => ['name' => 'Poser des questions',          'component' => 'role-play'],
        'listen-repeat'           => ['name' => 'Écouter et répéter',           'component' => 'listen-repeat'],
        'picture-mcq'             => ['name' => 'Choisir la bonne image',        'component' => 'picture-mcq'],
        // ── TOEFL 2026 ──
        'complete-the-words'      => ['name' => 'Compléter les mots',            'component' => 'complete-the-words'],
        'build-a-sentence'        => ['name' => 'Construire la phrase',          'component' => 'build-a-sentence'],
        'listen-choose-response'  => ['name' => 'Écouter et répondre',           'component' => 'listen-choose-response'],
        'read-daily-life'         => ['name' => 'Lecture du quotidien',          'component' => 'mcq'],
        // ── Genres d'écrit (écriture guidée, AI-évaluée) ──
        'guided-rewrite'          => ['name' => 'Réécriture / résumé guidé',     'component' => 'guided-writing'],
        'text-continuation'       => ['name' => 'Continuer le texte',            'component' => 'guided-writing'],
    ];

    public function run(): void
    {
        $languages = config('languages.languages');

        foreach ($languages as $langData) {
            $language = Language::where('slug', $langData['slug'])->first();
            if (! $language) continue;

            foreach ($langData['exams'] as $examData) {
                $exam = Exam::updateOrCreate(
                    ['slug' => $examData['slug']],
                    [
                        'language_id' => $language->id,
                        'name'        => $examData['name'],
                        'levels'      => $examData['levels'] ?? null,
                        'scoring_type' => $this->getScoringType($examData['slug']),
                        'max_score'   => $this->getMaxScore($examData['slug']),
                    ]
                );

                // Try to load exam-specific config
                $configPath = config_path('exams/' . $examData['slug'] . '.php');
                $config = null;

                if (file_exists($configPath)) {
                    $config = require $configPath;
                }

                if ($config) {
                    $this->seedFromConfig($exam, $config);
                } else {
                    // Fallback: create generic sections for exams not yet configured
                    $this->createGenericSections($exam);
                }
            }
        }
    }

    /**
     * Seed exam sections and exercise types from config/exams/{slug}.php
     */
    private function seedFromConfig(Exam $exam, array $config): void
    {
        // Handle exams with level-specific structures (DELF/DALF)
        if (isset($config['levels'])) {
            foreach ($config['levels'] as $level => $levelConfig) {
                $blueprint = ExamBlueprint::updateOrCreate(
                    ['exam_id' => $exam->id, 'level' => $level, 'variant' => null],
                    [
                        'name'                   => $levelConfig['name'],
                        'total_duration_minutes'  => $levelConfig['total_duration'],
                        'scoring_config'          => $config['scoring'],
                        'sections_config'         => $levelConfig['sections'],
                    ]
                );

                $this->createSectionsFromConfig($exam, $blueprint, $levelConfig['sections']);
            }
            return;
        }

        // Handle exams with variant structures (Cambridge, IELTS)
        if (isset($config['variants']) && is_array($config['variants'])) {
            // Normalize: ['academic', 'general'] → ['academic' => [...], 'general' => [...]]
            $variants = $config['variants'];
            $normalized = [];
            foreach ($variants as $key => $value) {
                if (is_string($value)) {
                    $normalized[$value] = ['name' => $config['name'] . ' ' . ucfirst($value)];
                } else {
                    $normalized[$key] = $value;
                }
            }

            $sections = $config['sections'] ?? $config['mandatory_sections'] ?? [];

            foreach ($normalized as $variantSlug => $variantData) {
                $blueprint = ExamBlueprint::updateOrCreate(
                    ['exam_id' => $exam->id, 'level' => null, 'variant' => $variantSlug],
                    [
                        'name'                   => $variantData['name'],
                        'total_duration_minutes'  => $this->calcTotalDuration($sections),
                        'scoring_config'          => array_merge($config['scoring'], $variantData),
                        'sections_config'         => $sections,
                    ]
                );

                $this->createSectionsFromConfig($exam, $blueprint, $sections);
            }
            return;
        }

        // Handle exams with mandatory + optional sections (TCF, TEF)
        $allSections = $config['sections']
            ?? array_merge($config['mandatory_sections'] ?? [], $config['optional_sections'] ?? []);

        $blueprint = ExamBlueprint::updateOrCreate(
            ['exam_id' => $exam->id, 'level' => null, 'variant' => null],
            [
                'name'                   => $config['name'],
                'total_duration_minutes'  => $this->calcTotalDuration($allSections),
                'scoring_config'          => $config['scoring'],
                'sections_config'         => $allSections,
            ]
        );

        $this->createSectionsFromConfig($exam, $blueprint, $allSections);
    }

    /**
     * Create exam sections and their exercise types from a config array.
     */
    private function createSectionsFromConfig(Exam $exam, ExamBlueprint $blueprint, array $sections): void
    {
        foreach ($sections as $sortOrder => $sectionConfig) {
            $section = ExamSection::updateOrCreate(
                [
                    'exam_id' => $exam->id,
                    'slug'    => $sectionConfig['slug'],
                ],
                [
                    'blueprint_id'   => $blueprint->id,
                    'name'           => $sectionConfig['name'],
                    'skill_type'     => $sectionConfig['skill_type'],
                    'time_limit'     => $sectionConfig['time_limit'] ?? null,
                    'sort_order'     => $sortOrder + 1,
                    'question_count' => $sectionConfig['question_count'] ?? null,
                    'scoring_weight' => $sectionConfig['scoring_weight'] ?? null,
                    'max_score'      => $sectionConfig['max_score'] ?? null,
                    'parts_config'   => $sectionConfig['parts'] ?? $sectionConfig['tasks'] ?? null,
                    'rubric'         => $sectionConfig['rubric'] ?? null,
                    'description'    => $sectionConfig['description'] ?? null,
                ]
            );

            // Create exercise types from config
            $exerciseTypes = $sectionConfig['exercise_types'] ?? [];
            foreach ($exerciseTypes as $typeSlug) {
                $registry = self::EXERCISE_TYPE_REGISTRY[$typeSlug] ?? null;
                if (! $registry) continue;

                ExerciseType::updateOrCreate(
                    ['section_id' => $section->id, 'slug' => $typeSlug],
                    [
                        'name'          => $registry['name'],
                        'skill_type'    => $sectionConfig['skill_type'],
                        'component_key' => $registry['component'],
                    ]
                );
            }
        }
    }

    /**
     * Fallback: generic 4-section structure for exams not yet configured.
     */
    private function createGenericSections(Exam $exam): void
    {
        $sections = [
            ['slug' => 'reading',   'name' => 'Reading',   'skill_type' => 'reading',   'time_limit' => 60, 'sort_order' => 1],
            ['slug' => 'listening', 'name' => 'Listening', 'skill_type' => 'listening', 'time_limit' => 40, 'sort_order' => 2],
            ['slug' => 'writing',   'name' => 'Writing',   'skill_type' => 'writing',   'time_limit' => 60, 'sort_order' => 3],
            ['slug' => 'speaking',  'name' => 'Speaking',  'skill_type' => 'speaking',  'time_limit' => 15, 'sort_order' => 4],
        ];

        foreach ($sections as $sectionData) {
            $section = ExamSection::updateOrCreate(
                ['exam_id' => $exam->id, 'slug' => $sectionData['slug']],
                $sectionData
            );

            $types = match ($section->skill_type) {
                'reading'   => ['mcq', 'true-false-not-given', 'matching', 'gap-fill'],
                'listening' => ['mcq', 'note-completion', 'matching'],
                'writing'   => ['essay'],
                'speaking'  => ['speaking-response'],
                default     => [],
            };

            foreach ($types as $typeSlug) {
                $registry = self::EXERCISE_TYPE_REGISTRY[$typeSlug] ?? null;
                if (! $registry) continue;

                ExerciseType::updateOrCreate(
                    ['section_id' => $section->id, 'slug' => $typeSlug],
                    [
                        'name'          => $registry['name'],
                        'skill_type'    => $section->skill_type,
                        'component_key' => $registry['component'],
                    ]
                );
            }
        }
    }

    private function getScoringType(string $slug): string
    {
        return match ($slug) {
            'ielts'     => 'band',
            'cambridge' => 'level',
            'delf-dalf' => 'level',
            'dele'      => 'level',
            'goethe'    => 'level',
            'hsk'       => 'level',
            'jlpt'      => 'level',
            'topik'     => 'level',
            'torfl'     => 'level',
            default     => 'points',
        };
    }

    private function getMaxScore(string $slug): ?int
    {
        return match ($slug) {
            'ielts' => 9,
            'toefl' => 120,
            'tcf'   => 699,
            'tef'   => 900,
            'siele' => 1000,
            default => null,
        };
    }

    private function calcTotalDuration(array $sections): int
    {
        return collect($sections)->sum(fn ($s) => $s['time_limit'] ?? 0);
    }
}
