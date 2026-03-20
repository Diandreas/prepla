<?php

/**
 * TEF - Test d'Évaluation de Français
 * Source: francais.cci-paris-idf.fr (Chambre de Commerce et d'Industrie de Paris)
 *
 * 5 épreuves (pas 4!) incluant Lexique et Structure
 * Scoring: 0-900 total
 * Variantes: TEF, TEF Canada, TEF Québec, TEF IRN
 */

return [
    'slug' => 'tef',
    'name' => 'TEF',
    'language' => 'french',

    'scoring' => [
        'type' => 'points',
        'min' => 0,
        'max' => 900,
        'overall_calculation' => 'sum_mandatory',
    ],

    'variants' => [
        'tef' => ['name' => 'TEF'],
        'tef-canada' => ['name' => 'TEF Canada', 'note' => 'Requis pour immigration Canada. 4 épreuves obligatoires.'],
        'tef-quebec' => ['name' => 'TEF Québec'],
        'tef-irn' => ['name' => 'TEF IRN (Intégration, Résidence, Naturalisation)'],
    ],

    // Correspondance score → niveau CEFR (par section)
    'score_levels' => [
        'comprehension' => [ // CE et CO même échelle
            ['min' => 0,   'max' => 68,  'level' => 'A1'],
            ['min' => 69,  'max' => 150, 'level' => 'A2'],
            ['min' => 151, 'max' => 226, 'level' => 'B1'],
            ['min' => 227, 'max' => 315, 'level' => 'B2'],
            ['min' => 316, 'max' => 360, 'level' => 'C1'],
        ],
        'expression' => [ // EE et EO même échelle
            ['min' => 0,   'max' => 68,  'level' => 'A1'],
            ['min' => 69,  'max' => 150, 'level' => 'A2'],
            ['min' => 151, 'max' => 226, 'level' => 'B1'],
            ['min' => 227, 'max' => 315, 'level' => 'B2'],
            ['min' => 316, 'max' => 360, 'level' => 'C1'],
            ['min' => 361, 'max' => 450, 'level' => 'C2'],
        ],
    ],

    'sections' => [
        // ─── COMPRÉHENSION ÉCRITE ───
        [
            'slug' => 'comprehension-ecrite',
            'name' => 'Compréhension écrite',
            'skill_type' => 'reading',
            'time_limit' => 60,
            'question_count' => 50,
            'max_score' => 300,
            'scoring_weight' => 20,
            'description' => '50 questions QCM. Sections : documents courts, textes informatifs, textes argumentatifs.',
            'parts' => [
                [
                    'name' => 'Section A',
                    'description' => 'Comprendre des documents courts (panneaux, formulaires, annonces)',
                    'question_count' => 15,
                ],
                [
                    'name' => 'Section B',
                    'description' => 'Comprendre des textes informatifs de complexité moyenne',
                    'question_count' => 15,
                ],
                [
                    'name' => 'Section C',
                    'description' => 'Comprendre des textes argumentatifs complexes',
                    'question_count' => 20,
                ],
            ],
            'exercise_types' => ['mcq'],
        ],

        // ─── COMPRÉHENSION ORALE ───
        [
            'slug' => 'comprehension-orale',
            'name' => 'Compréhension orale',
            'skill_type' => 'listening',
            'time_limit' => 40,
            'question_count' => 60,
            'max_score' => 360,
            'scoring_weight' => 20,
            'description' => '60 questions QCM. Messages courts, dialogues, documents radiophoniques.',
            'parts' => [
                [
                    'name' => 'Section A',
                    'description' => 'Identifier des messages courts (annonces, instructions, messages téléphoniques)',
                    'question_count' => 20,
                ],
                [
                    'name' => 'Section B',
                    'description' => 'Comprendre des dialogues et conversations',
                    'question_count' => 20,
                ],
                [
                    'name' => 'Section C',
                    'description' => 'Comprendre des documents radiophoniques, conférences, exposés',
                    'question_count' => 20,
                ],
            ],
            'exercise_types' => ['mcq'],
        ],

        // ─── LEXIQUE ET STRUCTURE ───
        [
            'slug' => 'lexique-structure',
            'name' => 'Lexique et structure',
            'skill_type' => 'grammar',
            'time_limit' => 30,
            'question_count' => 40,
            'max_score' => 240,
            'scoring_weight' => 20,
            'description' => '40 questions QCM. Vocabulaire et grammaire : choisir la forme correcte, synonymes, antonymes.',
            'parts' => [
                [
                    'name' => 'Section A - Lexique',
                    'description' => 'Maîtrise du vocabulaire : synonymes, antonymes, définitions, contexte',
                    'question_count' => 20,
                ],
                [
                    'name' => 'Section B - Structure',
                    'description' => 'Maîtrise de la grammaire : conjugaisons, accords, syntaxe, prépositions',
                    'question_count' => 20,
                ],
            ],
            'exercise_types' => ['mcq', 'grammar-mcq'],
        ],

        // ─── EXPRESSION ÉCRITE ───
        [
            'slug' => 'expression-ecrite',
            'name' => 'Expression écrite',
            'skill_type' => 'writing',
            'time_limit' => 60,
            'question_count' => 2,
            'max_score' => 450,
            'scoring_weight' => 20,
            'tasks' => [
                [
                    'task' => 1,
                    'name' => 'Rédaction d\'un texte',
                    'description' => 'Rédiger un récit, une description ou une lettre à partir d\'un sujet imposé (~200 mots).',
                    'min_words' => 200,
                    'exercise_type' => 'essay',
                ],
                [
                    'task' => 2,
                    'name' => 'Argumentation',
                    'description' => 'Rédiger un texte argumentatif à partir d\'un sujet imposé (~300 mots). Prendre position et justifier.',
                    'min_words' => 300,
                    'exercise_type' => 'essay',
                ],
            ],
            'rubric' => [
                ['name' => 'Pertinence du contenu', 'slug' => 'pertinence'],
                ['name' => 'Cohérence et cohésion', 'slug' => 'coherence'],
                ['name' => 'Compétence lexicale', 'slug' => 'lexique'],
                ['name' => 'Compétence grammaticale', 'slug' => 'grammaire'],
                ['name' => 'Orthographe', 'slug' => 'orthographe'],
            ],
        ],

        // ─── EXPRESSION ORALE ───
        [
            'slug' => 'expression-orale',
            'name' => 'Expression orale',
            'skill_type' => 'speaking',
            'time_limit' => 15,
            'question_count' => 2,
            'max_score' => 450,
            'scoring_weight' => 20,
            'tasks' => [
                [
                    'task' => 1,
                    'name' => 'Recueillir des informations',
                    'description' => 'Jeu de rôle : obtenir des renseignements et poser des questions (~5 min).',
                    'exercise_type' => 'role-play',
                ],
                [
                    'task' => 2,
                    'name' => 'Convaincre',
                    'description' => 'Argumenter pour convaincre un interlocuteur, débattre d\'un sujet (~10 min).',
                    'exercise_type' => 'speaking-discussion',
                ],
            ],
            'rubric' => [
                ['name' => 'Capacité à communiquer', 'slug' => 'communication'],
                ['name' => 'Cohérence du discours', 'slug' => 'coherence'],
                ['name' => 'Compétence lexicale', 'slug' => 'lexique'],
                ['name' => 'Compétence grammaticale', 'slug' => 'grammaire'],
                ['name' => 'Phonétique', 'slug' => 'phonetique'],
            ],
        ],
    ],
];
