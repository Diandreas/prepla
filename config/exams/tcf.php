<?php

/**
 * TCF - Test de Connaissance du Français
 * Source: france-education-international.fr
 *
 * Test adaptatif par ordinateur pour les épreuves obligatoires
 * Scoring: 100-699 points, correspondance CEFR
 * 3 épreuves obligatoires + 2 épreuves complémentaires optionnelles
 */

return [
    'slug' => 'tcf',
    'name' => 'TCF',
    'language' => 'french',

    'scoring' => [
        'type' => 'points',
        'min' => 100,
        'max' => 699,
        'overall_calculation' => 'average_mandatory',
    ],

    'variants' => [
        'tcf-tout-public' => ['name' => 'TCF Tout Public'],
        'tcf-canada' => ['name' => 'TCF Canada', 'note' => 'Inclut les 4 épreuves complémentaires obligatoirement'],
        'tcf-quebec' => ['name' => 'TCF Québec'],
        'tcf-irn' => ['name' => 'TCF IRN (Intégration, Résidence, Nationalité)'],
    ],

    // Correspondance score → niveau CEFR
    'score_levels' => [
        ['min' => 100, 'max' => 199, 'level' => 'A1'],
        ['min' => 200, 'max' => 299, 'level' => 'A2'],
        ['min' => 300, 'max' => 399, 'level' => 'B1'],
        ['min' => 400, 'max' => 499, 'level' => 'B2'],
        ['min' => 500, 'max' => 599, 'level' => 'C1'],
        ['min' => 600, 'max' => 699, 'level' => 'C2'],
    ],

    // ─── Épreuves obligatoires ───
    'mandatory_sections' => [
        [
            'slug' => 'comprehension-orale',
            'name' => 'Compréhension orale',
            'skill_type' => 'listening',
            'time_limit' => 25,
            'question_count' => 29,
            'scoring_weight' => 33,
            'description' => '29 questions QCM (3 choix). Comprendre des mots familiers, des phrases, des échanges, des extraits de conférences/cours.',
            'difficulty_range' => 'A1-C2',
            'format' => 'QCM à 3 choix, difficulté croissante',
            'exercise_types' => ['mcq'],
        ],
        [
            'slug' => 'maitrise-structures',
            'name' => 'Maîtrise des structures de la langue',
            'skill_type' => 'grammar', // Section unique au TCF
            'time_limit' => 15,
            'question_count' => 18,
            'scoring_weight' => 33,
            'description' => '18 questions QCM (4 choix). Grammaire et lexique : choisir le mot/la forme correcte.',
            'difficulty_range' => 'A1-C2',
            'format' => 'QCM à 4 choix, difficulté croissante',
            'exercise_types' => ['mcq', 'grammar-mcq'],
        ],
        [
            'slug' => 'comprehension-ecrite',
            'name' => 'Compréhension écrite',
            'skill_type' => 'reading',
            'time_limit' => 45,
            'question_count' => 29,
            'scoring_weight' => 34,
            'description' => '29 questions QCM (4 choix). Comprendre des textes variés : panneaux, annonces, articles, textes argumentatifs.',
            'difficulty_range' => 'A1-C2',
            'format' => 'QCM à 4 choix, difficulté croissante',
            'exercise_types' => ['mcq'],
        ],
    ],

    // ─── Épreuves complémentaires (optionnelles sauf TCF Canada) ───
    'optional_sections' => [
        [
            'slug' => 'expression-ecrite',
            'name' => 'Expression écrite',
            'skill_type' => 'writing',
            'time_limit' => 60,
            'question_count' => 3,
            'scoring_weight' => null, // Noté séparément
            'description' => '3 tâches de difficulté croissante.',
            'tasks' => [
                [
                    'task' => 1,
                    'level' => 'A1-A2',
                    'description' => 'Rédiger un message court (40-60 mots) : description d\'événement, demande d\'information.',
                    'min_words' => 40,
                    'max_words' => 60,
                    'exercise_type' => 'short-writing',
                ],
                [
                    'task' => 2,
                    'level' => 'B1-B2',
                    'description' => 'Rédiger un article, un courrier, une note (~120 mots) : raconter, décrire, expliquer.',
                    'min_words' => 100,
                    'max_words' => 120,
                    'exercise_type' => 'letter-email-writing',
                ],
                [
                    'task' => 3,
                    'level' => 'C1-C2',
                    'description' => 'Rédiger un texte argumentatif (~180 mots) : comparer, prendre position, argumenter.',
                    'min_words' => 160,
                    'max_words' => 180,
                    'exercise_type' => 'essay',
                ],
            ],
            'rubric' => [
                ['name' => 'Respect de la consigne', 'slug' => 'respect-consigne'],
                ['name' => 'Cohérence et cohésion', 'slug' => 'coherence'],
                ['name' => 'Compétence lexicale', 'slug' => 'lexique'],
                ['name' => 'Compétence grammaticale', 'slug' => 'grammaire'],
            ],
        ],
        [
            'slug' => 'expression-orale',
            'name' => 'Expression orale',
            'skill_type' => 'speaking',
            'time_limit' => 12,
            'question_count' => 3,
            'scoring_weight' => null,
            'description' => '3 tâches de difficulté croissante, face à face avec un examinateur.',
            'tasks' => [
                [
                    'task' => 1,
                    'level' => 'A1-A2',
                    'duration' => '2 min',
                    'description' => 'Entretien dirigé sans préparation. Questions personnelles simples.',
                    'exercise_type' => 'speaking-response',
                ],
                [
                    'task' => 2,
                    'level' => 'B1-B2',
                    'duration' => '5 min 30',
                    'prep_time' => '2 min',
                    'description' => 'Exercice en interaction. Jeu de rôle avec l\'examinateur à partir d\'un document déclencheur.',
                    'exercise_type' => 'role-play',
                ],
                [
                    'task' => 3,
                    'level' => 'C1-C2',
                    'duration' => '4 min 30',
                    'prep_time' => '3 min',
                    'description' => 'Expression d\'un point de vue. Prendre position sur un sujet à partir d\'un document.',
                    'exercise_type' => 'speaking-discussion',
                ],
            ],
        ],
    ],
];
