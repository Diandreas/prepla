<?php

/**
 * TOEFL iBT - Test of English as a Foreign Language (Internet-Based Test)
 * Source: ets.org
 *
 * Format updated July 2023 (shorter test, "Academic Discussion" replaces Independent Essay)
 * Scoring: 0-30 per section, 0-120 total
 */

return [
    'slug' => 'toefl',
    'name' => 'TOEFL',
    'language' => 'english',

    'scoring' => [
        'type' => 'points',
        'min' => 0,
        'max' => 120,
        'section_max' => 30,
        'overall_calculation' => 'sum',
    ],

    'variants' => null, // Single variant

    'sections' => [
        // ─── READING ───
        [
            'slug' => 'reading',
            'name' => 'Reading',
            'skill_type' => 'reading',
            'time_limit' => 35,
            'question_count' => 20,
            'scoring_weight' => 25,
            'parts' => [
                [
                    'part' => 1,
                    'description' => '2 academic passages (approximately 700 words each), 10 questions per passage',
                    'passages' => 2,
                    'questions_per_passage' => 10,
                ],
            ],
            'exercise_types' => [
                'mcq',                  // Factual information, negative factual, inference, rhetorical purpose, vocabulary
                'insert-text',          // Insert a sentence into the passage
                'prose-summary',        // Select 3 of 6 sentences that summarize the passage
                'fill-table',           // Categorize information into a table
            ],
        ],

        // ─── LISTENING ───
        [
            'slug' => 'listening',
            'name' => 'Listening',
            'skill_type' => 'listening',
            'time_limit' => 36,
            'question_count' => 28,
            'scoring_weight' => 25,
            'parts' => [
                [
                    'part' => 1,
                    'description' => '3 academic lectures (3-5 minutes each), 6 questions per lecture',
                    'type' => 'lecture',
                    'count' => 3,
                    'questions_per_item' => 6,
                ],
                [
                    'part' => 2,
                    'description' => '2 conversations (3 minutes each), 5 questions per conversation',
                    'type' => 'conversation',
                    'count' => 2,
                    'questions_per_item' => 5,
                ],
            ],
            'exercise_types' => [
                'mcq',                  // Gist-content, gist-purpose, detail, inference, attitude
                'ordering',             // Put events/steps in order
                'matching',             // Match items (e.g. categorize statements)
            ],
        ],

        // ─── SPEAKING ───
        [
            'slug' => 'speaking',
            'name' => 'Speaking',
            'skill_type' => 'speaking',
            'time_limit' => 16,
            'question_count' => 4,
            'scoring_weight' => 25,
            'tasks' => [
                [
                    'task' => 1,
                    'name' => 'Independent Speaking',
                    'description' => 'Express and support an opinion on a familiar topic.',
                    'prep_time' => 15,
                    'response_time' => 45,
                    'exercise_type' => 'speaking-response',
                ],
                [
                    'task' => 2,
                    'name' => 'Integrated: Read + Listen + Speak (Campus)',
                    'description' => 'Read a short passage, listen to a conversation about it, speak about how the speakers opinion relates to the reading.',
                    'prep_time' => 30,
                    'response_time' => 60,
                    'exercise_type' => 'integrated-speaking',
                ],
                [
                    'task' => 3,
                    'name' => 'Integrated: Read + Listen + Speak (Academic)',
                    'description' => 'Read a short academic passage, listen to a lecture excerpt, explain how the lecture relates to the reading.',
                    'prep_time' => 30,
                    'response_time' => 60,
                    'exercise_type' => 'integrated-speaking',
                ],
                [
                    'task' => 4,
                    'name' => 'Integrated: Listen + Speak (Academic)',
                    'description' => 'Listen to a lecture excerpt, summarize and explain the key points.',
                    'prep_time' => 20,
                    'response_time' => 60,
                    'exercise_type' => 'integrated-speaking',
                ],
            ],
            'rubric' => [
                'criteria' => [
                    ['name' => 'Delivery', 'slug' => 'delivery', 'max' => 4],
                    ['name' => 'Language Use', 'slug' => 'language-use', 'max' => 4],
                    ['name' => 'Topic Development', 'slug' => 'topic-development', 'max' => 4],
                ],
                'scale' => '0-4, converted to 0-30',
            ],
        ],

        // ─── WRITING ───
        [
            'slug' => 'writing',
            'name' => 'Writing',
            'skill_type' => 'writing',
            'time_limit' => 29,
            'question_count' => 2,
            'scoring_weight' => 25,
            'tasks' => [
                [
                    'task' => 1,
                    'name' => 'Integrated Writing',
                    'description' => 'Read a passage (3 min), listen to a lecture, write a response explaining how the lecture challenges/supports the reading.',
                    'time_limit' => 20,
                    'min_words' => 150,
                    'max_words' => 225,
                    'exercise_type' => 'integrated-writing',
                ],
                [
                    'task' => 2,
                    'name' => 'Academic Discussion',
                    'description' => 'Read a professor\'s question and two student responses, then contribute your own opinion to the academic discussion.',
                    'time_limit' => 10,
                    'min_words' => 100,
                    'exercise_type' => 'academic-discussion',
                ],
            ],
            'rubric' => [
                'integrated' => [
                    ['name' => 'Content Accuracy', 'slug' => 'content-accuracy', 'max' => 5],
                    ['name' => 'Organization', 'slug' => 'organization', 'max' => 5],
                    ['name' => 'Language Use', 'slug' => 'language-use', 'max' => 5],
                ],
                'academic_discussion' => [
                    ['name' => 'Relevance & Contribution', 'slug' => 'relevance', 'max' => 5],
                    ['name' => 'Language Facility', 'slug' => 'language-facility', 'max' => 5],
                ],
                'scale' => '0-5, converted to 0-30',
            ],
        ],
    ],

    // Score level descriptions
    'score_levels' => [
        ['min' => 0,  'max' => 31,  'level' => 'Below A2'],
        ['min' => 32, 'max' => 34,  'level' => 'A2'],
        ['min' => 35, 'max' => 45,  'level' => 'Low B1'],
        ['min' => 46, 'max' => 59,  'level' => 'High B1'],
        ['min' => 60, 'max' => 78,  'level' => 'Low B2'],
        ['min' => 79, 'max' => 93,  'level' => 'High B2'],
        ['min' => 94, 'max' => 101, 'level' => 'C1'],
        ['min' => 102, 'max' => 109, 'level' => 'High C1'],
        ['min' => 110, 'max' => 120, 'level' => 'C2'],
    ],
];
