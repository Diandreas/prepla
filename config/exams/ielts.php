<?php

/**
 * IELTS - International English Language Testing System
 * Source: ielts.org
 *
 * Two variants: Academic and General Training
 * Scoring: Band 0-9 (half-band increments)
 * Overall band = average of 4 section bands, rounded to nearest half/whole
 */

return [
    'slug' => 'ielts',
    'name' => 'IELTS',
    'language' => 'english',

    'scoring' => [
        'type' => 'band',
        'min' => 0,
        'max' => 9,
        'increment' => 0.5,
        'overall_calculation' => 'average_rounded_half',
    ],

    'variants' => ['academic', 'general'],

    'sections' => [
        // ─── LISTENING (identical for Academic & General) ───
        [
            'slug' => 'listening',
            'name' => 'Listening',
            'skill_type' => 'listening',
            'time_limit' => 30, // +10min transfer time on paper
            'question_count' => 40,
            'scoring_weight' => 25,
            'parts' => [
                [
                    'part' => 1,
                    'description' => 'Conversation between two people in an everyday social context',
                    'question_count' => 10,
                ],
                [
                    'part' => 2,
                    'description' => 'Monologue in an everyday social context (e.g. speech about local facilities)',
                    'question_count' => 10,
                ],
                [
                    'part' => 3,
                    'description' => 'Conversation between up to four people in an educational/training context',
                    'question_count' => 10,
                ],
                [
                    'part' => 4,
                    'description' => 'Academic monologue (e.g. university lecture)',
                    'question_count' => 10,
                ],
            ],
            'exercise_types' => [
                'mcq',
                'matching',
                'plan-map-diagram-labeling',
                'form-completion',
                'note-completion',
                'table-completion',
                'flow-chart-completion',
                'summary-completion',
                'sentence-completion',
            ],
        ],

        // ─── READING (differs Academic vs General) ───
        [
            'slug' => 'reading',
            'name' => 'Reading',
            'skill_type' => 'reading',
            'time_limit' => 60,
            'question_count' => 40,
            'scoring_weight' => 25,
            'variant_config' => [
                'academic' => [
                    'description' => '3 long passages from books, journals, magazines, newspapers. Academic topics.',
                    'passages' => 3,
                ],
                'general' => [
                    'description' => '3 sections with texts from notices, newspapers, instruction manuals, books. Everyday English.',
                    'passages' => 3,
                ],
            ],
            'exercise_types' => [
                'mcq',
                'true-false-not-given',
                'yes-no-not-given',
                'matching-headings',
                'matching-information',
                'matching-features',
                'matching-sentence-endings',
                'summary-completion',
                'note-completion',
                'table-completion',
                'flow-chart-completion',
                'diagram-labeling',
                'sentence-completion',
                'short-answer',
            ],
        ],

        // ─── WRITING (differs Academic vs General) ───
        [
            'slug' => 'writing',
            'name' => 'Writing',
            'skill_type' => 'writing',
            'time_limit' => 60,
            'question_count' => 2,
            'scoring_weight' => 25,
            'variant_config' => [
                'academic' => [
                    'tasks' => [
                        [
                            'task' => 1,
                            'name' => 'Writing Task 1 (Academic)',
                            'description' => 'Describe, summarise or explain information from a graph, table, chart or diagram.',
                            'min_words' => 150,
                            'time_suggestion' => 20,
                            'exercise_type' => 'graph-description',
                        ],
                        [
                            'task' => 2,
                            'name' => 'Writing Task 2',
                            'description' => 'Write an essay in response to a point of view, argument or problem.',
                            'min_words' => 250,
                            'time_suggestion' => 40,
                            'exercise_type' => 'essay',
                        ],
                    ],
                ],
                'general' => [
                    'tasks' => [
                        [
                            'task' => 1,
                            'name' => 'Writing Task 1 (General)',
                            'description' => 'Write a letter (formal, semi-formal, or informal) requesting information or explaining a situation.',
                            'min_words' => 150,
                            'time_suggestion' => 20,
                            'exercise_type' => 'letter-writing',
                        ],
                        [
                            'task' => 2,
                            'name' => 'Writing Task 2',
                            'description' => 'Write an essay in response to a point of view, argument or problem.',
                            'min_words' => 250,
                            'time_suggestion' => 40,
                            'exercise_type' => 'essay',
                        ],
                    ],
                ],
            ],
            'rubric' => [
                'criteria' => [
                    ['name' => 'Task Achievement', 'slug' => 'task-achievement', 'max' => 9],
                    ['name' => 'Coherence and Cohesion', 'slug' => 'coherence-cohesion', 'max' => 9],
                    ['name' => 'Lexical Resource', 'slug' => 'lexical-resource', 'max' => 9],
                    ['name' => 'Grammatical Range and Accuracy', 'slug' => 'grammar-accuracy', 'max' => 9],
                ],
                'overall_calculation' => 'average_rounded_half',
            ],
        ],

        // ─── SPEAKING (identical for Academic & General) ───
        [
            'slug' => 'speaking',
            'name' => 'Speaking',
            'skill_type' => 'speaking',
            'time_limit' => 14,
            'question_count' => 3, // 3 parts
            'scoring_weight' => 25,
            'parts' => [
                [
                    'part' => 1,
                    'name' => 'Introduction and Interview',
                    'duration' => '4-5 min',
                    'description' => 'Examiner asks general questions about familiar topics (home, family, work, studies, interests).',
                    'exercise_type' => 'speaking-response',
                ],
                [
                    'part' => 2,
                    'name' => 'Individual Long Turn',
                    'duration' => '3-4 min',
                    'description' => 'Candidate receives a cue card with a topic. 1 min preparation, then speak for 1-2 minutes.',
                    'exercise_type' => 'speaking-long-turn',
                ],
                [
                    'part' => 3,
                    'name' => 'Two-way Discussion',
                    'duration' => '4-5 min',
                    'description' => 'Discussion of more abstract issues linked to Part 2 topic.',
                    'exercise_type' => 'speaking-discussion',
                ],
            ],
            'rubric' => [
                'criteria' => [
                    ['name' => 'Fluency and Coherence', 'slug' => 'fluency-coherence', 'max' => 9],
                    ['name' => 'Lexical Resource', 'slug' => 'lexical-resource', 'max' => 9],
                    ['name' => 'Grammatical Range and Accuracy', 'slug' => 'grammar-accuracy', 'max' => 9],
                    ['name' => 'Pronunciation', 'slug' => 'pronunciation', 'max' => 9],
                ],
                'overall_calculation' => 'average_rounded_half',
            ],
        ],
    ],

    // Band score conversion (raw score → band)
    'band_conversion' => [
        'listening' => [
            39 => 9.0, 37 => 8.5, 35 => 8.0, 33 => 7.5, 30 => 7.0,
            27 => 6.5, 23 => 6.0, 20 => 5.5, 16 => 5.0, 13 => 4.5,
            10 => 4.0, 6 => 3.5, 4 => 3.0,
        ],
        'reading_academic' => [
            39 => 9.0, 37 => 8.5, 35 => 8.0, 33 => 7.5, 30 => 7.0,
            27 => 6.5, 23 => 6.0, 20 => 5.5, 16 => 5.0, 13 => 4.5,
            10 => 4.0, 6 => 3.5, 4 => 3.0,
        ],
        'reading_general' => [
            40 => 9.0, 39 => 8.5, 37 => 8.0, 36 => 7.5, 34 => 7.0,
            32 => 6.5, 30 => 6.0, 27 => 5.5, 23 => 5.0, 19 => 4.5,
            15 => 4.0, 12 => 3.5, 8 => 3.0,
        ],
    ],
];
