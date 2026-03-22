<?php

/**
 * TestDaF - Test Deutsch als Fremdsprache
 * Source: testdaf.de
 *
 * Single exam, no levels/variants
 * Digital TestDaF (since 2020): 4 sections
 * Scoring: TDN 3, TDN 4, TDN 5 per section (Unter TDN 3 = fail)
 * University admission typically requires TDN 4 in all sections
 * CEFR: B2–C1
 */

return [
    'slug' => 'testdaf',
    'name' => 'TestDaF',
    'language' => 'german',

    'scoring' => [
        'type' => 'level',
        'levels' => ['Unter TDN 3', 'TDN 3', 'TDN 4', 'TDN 5'],
        'per_section' => true,
        'university_requirement' => 'TDN 4 in all sections',
    ],

    'cefr_mapping' => [
        'TDN 3' => 'B2',
        'TDN 4' => 'B2-C1',
        'TDN 5' => 'C1',
    ],

    'sections' => [
        // ─── LESEN (Reading) ───
        [
            'slug' => 'lesen',
            'name' => 'Lesen (Reading)',
            'skill_type' => 'reading',
            'time_limit' => 55,
            'question_count' => 30,
            'scoring_weight' => 25,
            'parts' => [
                [
                    'part' => 1,
                    'name' => 'Lesetext 1',
                    'description' => 'Short texts (university life): match texts to headings/descriptions. 10 items.',
                    'question_count' => 10,
                ],
                [
                    'part' => 2,
                    'name' => 'Lesetext 2',
                    'description' => 'Journalistic text: MCQ with 3 options per question. 10 items.',
                    'question_count' => 10,
                ],
                [
                    'part' => 3,
                    'name' => 'Lesetext 3',
                    'description' => 'Academic/scientific text: True/False/Not Given. 10 items.',
                    'question_count' => 10,
                ],
            ],
            'exercise_types' => [
                'mcq',
                'true-false-not-given',
                'matching',
                'matching-headings',
                'sentence-completion',
                'gap-fill',
            ],
        ],

        // ─── HÖREN (Listening) ───
        [
            'slug' => 'hoeren',
            'name' => 'Hören (Listening)',
            'skill_type' => 'listening',
            'time_limit' => 40,
            'question_count' => 25,
            'scoring_weight' => 25,
            'parts' => [
                [
                    'part' => 1,
                    'name' => 'Hörtext 1',
                    'description' => 'Dialogue in university setting: note completion. 8 items.',
                    'question_count' => 8,
                ],
                [
                    'part' => 2,
                    'name' => 'Hörtext 2',
                    'description' => 'Interview/discussion: MCQ and matching. 10 items.',
                    'question_count' => 10,
                ],
                [
                    'part' => 3,
                    'name' => 'Hörtext 3',
                    'description' => 'Academic lecture: detailed comprehension. 7 items.',
                    'question_count' => 7,
                ],
            ],
            'exercise_types' => [
                'mcq',
                'true-false-not-given',
                'note-completion',
                'matching',
                'short-answer',
                'sentence-completion',
            ],
        ],

        // ─── SCHREIBEN (Writing) ───
        [
            'slug' => 'schreiben',
            'name' => 'Schreiben (Writing)',
            'skill_type' => 'writing',
            'time_limit' => 60,
            'question_count' => 1,
            'scoring_weight' => 25,
            'tasks' => [
                [
                    'task' => 1,
                    'name' => 'Schriftlicher Ausdruck',
                    'description' => 'Write a structured argumentative text on an academic topic based on a chart/graph. Include: introduction, describe data, present arguments for/against, state own position. 350-400 words.',
                    'min_words' => 350,
                    'time_suggestion' => 60,
                    'exercise_type' => 'essay',
                ],
            ],
            'rubric' => [
                'criteria' => [
                    ['name' => 'Gesamteindruck (Overall impression)', 'slug' => 'overall-impression', 'max' => 5],
                    ['name' => 'Behandlung der Aufgabe (Task treatment)', 'slug' => 'task-treatment', 'max' => 5],
                    ['name' => 'Argumentation (Argumentation)', 'slug' => 'argumentation', 'max' => 5],
                    ['name' => 'Sprachliche Realisierung (Linguistic realization)', 'slug' => 'linguistic-realization', 'max' => 5],
                ],
            ],
        ],

        // ─── SPRECHEN (Speaking) ───
        [
            'slug' => 'sprechen',
            'name' => 'Sprechen (Speaking)',
            'skill_type' => 'speaking',
            'time_limit' => 30,
            'question_count' => 7,
            'scoring_weight' => 25,
            'parts' => [
                [
                    'part' => 1,
                    'name' => 'Aufgabe 1',
                    'description' => 'Get information: ask questions in a university context.',
                    'duration' => '2 min',
                    'exercise_type' => 'speaking-response',
                ],
                [
                    'part' => 2,
                    'name' => 'Aufgabe 2',
                    'description' => 'Give information: describe a chart or diagram.',
                    'duration' => '3 min',
                    'exercise_type' => 'speaking-long-turn',
                ],
                [
                    'part' => 3,
                    'name' => 'Aufgabe 3',
                    'description' => 'Express and justify opinion: discuss a topic.',
                    'duration' => '3 min',
                    'exercise_type' => 'speaking-discussion',
                ],
                [
                    'part' => 4,
                    'name' => 'Aufgabe 4',
                    'description' => 'Develop proposals and weigh alternatives.',
                    'duration' => '3 min',
                    'exercise_type' => 'speaking-discussion',
                ],
                [
                    'part' => 5,
                    'name' => 'Aufgabe 5',
                    'description' => 'Hypothesize and express pros and cons.',
                    'duration' => '3 min',
                    'exercise_type' => 'speaking-discussion',
                ],
                [
                    'part' => 6,
                    'name' => 'Aufgabe 6',
                    'description' => 'Summarize and comment on an academic text.',
                    'duration' => '3 min',
                    'exercise_type' => 'speaking-long-turn',
                ],
                [
                    'part' => 7,
                    'name' => 'Aufgabe 7',
                    'description' => 'Take part in a discussion and present a counter-argument.',
                    'duration' => '3 min',
                    'exercise_type' => 'speaking-discussion',
                ],
            ],
            'rubric' => [
                'criteria' => [
                    ['name' => 'Gesamteindruck (Overall impression)', 'slug' => 'overall-impression', 'max' => 5],
                    ['name' => 'Behandlung der Aufgabe (Task treatment)', 'slug' => 'task-treatment', 'max' => 5],
                    ['name' => 'Sprachliche Realisierung (Linguistic realization)', 'slug' => 'linguistic-realization', 'max' => 5],
                ],
            ],
        ],
    ],
];
