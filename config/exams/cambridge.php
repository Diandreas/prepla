<?php

/**
 * Cambridge English Qualifications
 * Source: cambridgeenglish.org
 *
 * Three main exams: B2 First (FCE), C1 Advanced (CAE), C2 Proficiency (CPE)
 * Scoring: Cambridge English Scale (unique per exam)
 * Unique feature: "Reading and Use of English" combined paper
 */

return [
    'slug' => 'cambridge',
    'name' => 'Cambridge',
    'language' => 'english',

    'scoring' => [
        'type' => 'level',
        'scale' => 'cambridge_english_scale',
        'overall_calculation' => 'average_sections',
    ],

    'variants' => [
        'b2-first' => [
            'name' => 'B2 First (FCE)',
            'scale_min' => 140,
            'scale_max' => 190,
            'pass_grade_c' => 160,  // Grade C = B2
            'pass_grade_b' => 173,  // Grade B
            'pass_grade_a' => 180,  // Grade A = C1
        ],
        'c1-advanced' => [
            'name' => 'C1 Advanced (CAE)',
            'scale_min' => 160,
            'scale_max' => 210,
            'pass_grade_c' => 180,  // Grade C = C1
            'pass_grade_b' => 193,  // Grade B
            'pass_grade_a' => 200,  // Grade A = C2
        ],
        'c2-proficiency' => [
            'name' => 'C2 Proficiency (CPE)',
            'scale_min' => 180,
            'scale_max' => 230,
            'pass_grade_c' => 200,  // Grade C = C2
            'pass_grade_b' => 213,  // Grade B
            'pass_grade_a' => 220,  // Grade A
        ],
    ],

    'sections' => [
        // ─── READING AND USE OF ENGLISH ───
        [
            'slug' => 'reading-use-of-english',
            'name' => 'Reading and Use of English',
            'skill_type' => 'reading',
            'time_limit' => 90, // B2: 75min, C1: 90min, C2: 90min
            'question_count' => 52, // varies slightly by level
            'scoring_weight' => 40, // 40% of total (Reading 20% + Use of English 20%)
            'parts' => [
                // ── Use of English parts (Parts 1-4) ──
                [
                    'part' => 1,
                    'name' => 'Multiple-choice cloze',
                    'question_count' => 8,
                    'description' => 'Text with 8 gaps. Choose from 4 options (A-D) per gap. Tests vocabulary, collocations, idioms.',
                    'exercise_type' => 'mcq-cloze',
                ],
                [
                    'part' => 2,
                    'name' => 'Open cloze',
                    'question_count' => 8,
                    'description' => 'Text with 8 gaps. Write one word per gap. Tests grammar and vocabulary.',
                    'exercise_type' => 'open-cloze',
                ],
                [
                    'part' => 3,
                    'name' => 'Word formation',
                    'question_count' => 8,
                    'description' => 'Text with 8 gaps. Given a root word, form the correct word for each gap.',
                    'exercise_type' => 'word-formation',
                ],
                [
                    'part' => 4,
                    'name' => 'Key word transformation',
                    'question_count' => 6,
                    'description' => 'Complete a sentence using a given word so it means the same as the first sentence. 2-5 words including the key word.',
                    'exercise_type' => 'key-word-transformation',
                ],
                // ── Reading parts (Parts 5-7 for B2/C1, Parts 5-8 for C2) ──
                [
                    'part' => 5,
                    'name' => 'Multiple choice (reading)',
                    'question_count' => 6,
                    'description' => 'Read a long text and answer 6 four-option multiple choice questions.',
                    'exercise_type' => 'mcq',
                ],
                [
                    'part' => 6,
                    'name' => 'Gapped text',
                    'question_count' => 6,
                    'description' => 'Text with 6 removed paragraphs/sentences. Put them back in the right place.',
                    'exercise_type' => 'gapped-text',
                ],
                [
                    'part' => 7,
                    'name' => 'Multiple matching',
                    'question_count' => 10,
                    'description' => 'Match 10 statements/questions to sections of a long text or several short texts.',
                    'exercise_type' => 'multiple-matching',
                ],
            ],
            'variant_parts' => [
                'c2-proficiency' => [
                    // C2 has an extra Part 8
                    [
                        'part' => 8,
                        'name' => 'Summary writing',
                        'question_count' => 1,
                        'description' => 'Write a summary of two texts in your own words (220-260 words).',
                        'exercise_type' => 'summary-writing',
                    ],
                ],
            ],
        ],

        // ─── WRITING ───
        [
            'slug' => 'writing',
            'name' => 'Writing',
            'skill_type' => 'writing',
            'time_limit' => 80, // B2: 80min, C1: 90min, C2: 90min
            'question_count' => 2,
            'scoring_weight' => 20,
            'tasks' => [
                [
                    'task' => 1,
                    'name' => 'Compulsory task',
                    'description' => 'Essay based on input material. Must address given points.',
                    'min_words' => 140, // B2: 140-190, C1: 220-260, C2: 240-280
                    'max_words' => 190,
                    'exercise_type' => 'essay',
                ],
                [
                    'task' => 2,
                    'name' => 'Choice task',
                    'description' => 'Choose one from: article, email/letter, report, review, essay, story (varies by level).',
                    'min_words' => 140,
                    'max_words' => 190,
                    'exercise_types' => [
                        'article-writing',
                        'letter-email-writing',
                        'report-writing',
                        'review-writing',
                    ],
                ],
            ],
            'rubric' => [
                'criteria' => [
                    ['name' => 'Content', 'slug' => 'content', 'max' => 5],
                    ['name' => 'Communicative Achievement', 'slug' => 'communicative-achievement', 'max' => 5],
                    ['name' => 'Organisation', 'slug' => 'organisation', 'max' => 5],
                    ['name' => 'Language', 'slug' => 'language', 'max' => 5],
                ],
            ],
        ],

        // ─── LISTENING ───
        [
            'slug' => 'listening',
            'name' => 'Listening',
            'skill_type' => 'listening',
            'time_limit' => 40,
            'question_count' => 30,
            'scoring_weight' => 20,
            'parts' => [
                [
                    'part' => 1,
                    'name' => 'Multiple choice (short extracts)',
                    'question_count' => 8,
                    'description' => '8 short unrelated recordings. One three-option MCQ per recording.',
                    'exercise_type' => 'mcq',
                ],
                [
                    'part' => 2,
                    'name' => 'Sentence completion',
                    'question_count' => 10,
                    'description' => 'One long monologue. Complete 10 sentences with information from the recording.',
                    'exercise_type' => 'sentence-completion',
                ],
                [
                    'part' => 3,
                    'name' => 'Multiple matching',
                    'question_count' => 5,
                    'description' => 'Conversation with interacting speakers. Match 5 statements to speakers.',
                    'exercise_type' => 'multiple-matching',
                ],
                [
                    'part' => 4,
                    'name' => 'Multiple choice (long)',
                    'question_count' => 7,
                    'description' => 'Long recording (interview, discussion). 7 three-option MCQ questions.',
                    'exercise_type' => 'mcq',
                ],
            ],
        ],

        // ─── SPEAKING ───
        [
            'slug' => 'speaking',
            'name' => 'Speaking',
            'skill_type' => 'speaking',
            'time_limit' => 14,
            'question_count' => 4, // 4 parts
            'scoring_weight' => 20,
            'note' => 'Done in pairs with another candidate',
            'parts' => [
                [
                    'part' => 1,
                    'name' => 'Interview',
                    'duration' => '2 min',
                    'description' => 'Examiner asks each candidate questions about themselves.',
                    'exercise_type' => 'speaking-response',
                ],
                [
                    'part' => 2,
                    'name' => 'Individual long turn',
                    'duration' => '4 min',
                    'description' => 'Each candidate speaks for 1 minute about a pair of photographs, then briefly comments on partner\'s photos.',
                    'exercise_type' => 'speaking-long-turn',
                ],
                [
                    'part' => 3,
                    'name' => 'Collaborative task',
                    'duration' => '4 min',
                    'description' => 'Candidates discuss visual/written prompts together and reach a decision.',
                    'exercise_type' => 'speaking-discussion',
                ],
                [
                    'part' => 4,
                    'name' => 'Discussion',
                    'duration' => '4 min',
                    'description' => 'Examiner-led discussion on topics related to Part 3.',
                    'exercise_type' => 'speaking-discussion',
                ],
            ],
            'rubric' => [
                'criteria' => [
                    ['name' => 'Grammar and Vocabulary', 'slug' => 'grammar-vocabulary', 'max' => 5],
                    ['name' => 'Discourse Management', 'slug' => 'discourse-management', 'max' => 5],
                    ['name' => 'Pronunciation', 'slug' => 'pronunciation', 'max' => 5],
                    ['name' => 'Interactive Communication', 'slug' => 'interactive-communication', 'max' => 5],
                ],
            ],
        ],
    ],
];
