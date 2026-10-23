<?php

/**
 * TOEFL iBT - Test of English as a Foreign Language (Internet-Based Test)
 * Source: ets.org
 *
 * Format REFONDU pour la réforme ETS du 21 janvier 2026 : test plus court (<2h),
 * adaptatif, nouvelles tâches (Complete the Words, Build a Sentence, Listen and
 * Choose a Response, Listen and Repeat, Read in Daily Life). Score 0-120 + bande 1-6.
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
        // ─── READING (2026: adaptatif, ~27 min) ───
        [
            'slug' => 'reading',
            // Blueprint officiel ETS du format « 2026 Update » : 50 items.
            'task_count' => 3,
            'name' => 'Reading',
            'skill_type' => 'reading',
            'time_limit' => 30,
            'question_count' => 40,
            'scoring_weight' => 25,
            'parts' => [
                ['part' => 1, 'name' => 'Reading, Teil 1', 'name_fr' => 'Complete the Words',
                    "task" => "Completer des mots dans un texte, pour montrer qu'on maitrise lexique et syntaxe. 30 items.",
                    'type' => 'complete-the-words', 'items' => 30, 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Reading, Teil 2', 'name_fr' => 'Read in Daily Life',
                    "task" => "Comprendre de courts textes non universitaires du quotidien. 5 a 15 items.",
                    'type' => 'read-daily-life', 'source' => 'officiel'],
                ['part' => 3, 'name' => 'Reading, Teil 3', 'name_fr' => 'Read an Academic Passage',
                    "task" => "Un texte universitaire : idees principales, details, implicite, structure. 5 a 15 items.",
                    'type' => 'mcq', 'source' => 'officiel'],
            ],
            'exercise_types' => [
                'mcq',                  // Factual, inference, rhetorical purpose, vocabulary
                'read-daily-life',      // 2026: textes pratiques courts (rendu mcq)
                'complete-the-words',   // 2026: lettres manquantes
                'insert-text',          // Insert a sentence
                'prose-summary',        // Select 3 of 6 sentences
            ],
        ],

        // ─── LISTENING (2026: tâches dynamiques) ───
        [
            'slug' => 'listening',
            // Blueprint officiel ETS du format « 2026 Update » : 47 items.
            'task_count' => 4,
            'name' => 'Listening',
            'skill_type' => 'listening',
            'time_limit' => 29,
            'question_count' => 28,
            'scoring_weight' => 25,
            'parts' => [
                ['part' => 1, 'name' => 'Listening, Teil 1', 'name_fr' => 'Listen and Choose a Response',
                    "task" => "Un bref echange entre deux personnes : choisir la reponse qui convient. 15 a 19 items.",
                    'type' => 'listen-choose-response', 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Listening, Teil 2', 'name_fr' => 'Listen to a Conversation',
                    "task" => "Une courte conversation entre deux personnes. 10 items.",
                    'type' => 'mcq', 'items' => 10, 'source' => 'officiel'],
                ['part' => 3, 'name' => 'Listening, Teil 3', 'name_fr' => 'Listen to an Announcement',
                    "task" => "Une annonce en contexte universitaire.",
                    'type' => 'mcq', 'source' => 'officiel'],
                ['part' => 4, 'name' => 'Listening, Teil 4', 'name_fr' => 'Listen to an Academic Talk',
                    "task" => "Un expose universitaire suivi.",
                    'type' => 'note-completion', 'source' => 'officiel'],
            ],
            'exercise_types' => [
                'listen-choose-response', // 2026: choisir la réponse à un énoncé
                'mcq',                    // Conversations, annonces, lectures,
                'note-completion',
            ],
        ],

        // ─── SPEAKING ───
        [
            'slug' => 'speaking',
            // Blueprint officiel ETS du format « 2026 Update » : 11 items.
            'task_count' => 2,
            'name' => 'Speaking',
            'skill_type' => 'speaking',
            'time_limit' => 8,
            'question_count' => 4,
            'scoring_weight' => 25,
            // Types practisables (le seeder lit 'exercise_types' ; 'tasks' = métadonnée examen).
                    // « integrated-task » est le COMPOSANT ; le format s'appelle integrated-speaking.
                    // Mal nomme, il etait ignore en silence et l'oral n'avait plus que deux formats
                    // pour cinq taches — d'ou la meme tache repetee quatre fois.
            'parts' => [
                ['part' => 1, 'name' => 'Speaking, Teil 1', 'name_fr' => 'Listen and Repeat',
                    "task" => "Ecouter puis repeter, pour evaluer la prononciation et le rythme.",
                    'type' => 'listen-repeat', 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Speaking, Teil 2', 'name_fr' => 'Take an Interview',
                    "task" => "Repondre a une serie de questions, sous forme d'entretien.",
                    'type' => 'speaking-response', 'source' => 'officiel'],
            ],
                    'exercise_types' => ['speaking-response', 'integrated-speaking', 'listen-repeat'],
            'tasks' => [
                [
                    'task' => 0,
                    'name' => 'Listen and Repeat',
                    'description' => '2026 : écouter et répéter des phrases (campus/vie quotidienne), noté sur la prononciation.',
                    'prep_time' => 0,
                    'response_time' => 30,
                    'exercise_type' => 'listen-repeat',
                ],
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
            // Blueprint officiel ETS du format « 2026 Update » : 12 items.
            'task_count' => 3,
            'name' => 'Writing',
            'skill_type' => 'writing',
            'time_limit' => 23,
            'question_count' => 2,
            'scoring_weight' => 25,
            'parts' => [
                ['part' => 1, 'name' => 'Writing, Teil 1', 'name_fr' => 'Build a Sentence',
                    "task" => "Reconstruire des phrases avec la grammaire qui convient. 10 items.",
                    'type' => 'build-a-sentence', 'items' => 10, 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Writing, Teil 2', 'name_fr' => 'Write an Email',
                    "task" => "Rediger un courriel adapte a une situation universitaire.",
                    'type' => 'letter-email-writing', 'items' => 1, 'source' => 'officiel'],
                ['part' => 3, 'name' => 'Writing, Teil 3', 'name_fr' => 'Write for an Academic Discussion',
                    "task" => "Contribuer par ecrit a une discussion universitaire en ligne.",
                    'type' => 'academic-discussion', 'items' => 1, 'source' => 'officiel'],
            ],
            'exercise_types' => ['build-a-sentence', 'integrated-writing', 'academic-discussion',
                'letter-email-writing',
            ],
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
