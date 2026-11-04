<?php

/**
 * TCF - Test de Connaissance du Français
 * Source: france-education-international.fr
 *
 * Test adaptatif par ordinateur pour les épreuves obligatoires
 * Scoring: 100-699 points, correspondance CEFR
 * 3 épreuves obligatoires + 2 épreuves complémentaires optionnelles
 *
 * Chiffres relus le 2026-11-04 sur la page « TCF tout public »
 * (https://www.france-education-international.fr/test/tcf-tout-public), dans
 * sa copie archivee du 2026-07-11 : le site est desormais derriere un controle
 * anti-robot. Quatre choix (A a D) aux trois epreuves a QCM ; expression
 * ecrite 60-120, 120-150 et 120-180 mots ; expression orale 12 minutes dont
 * 2 de preparation, toutes deux pour la tache 2.
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

    // Le TCF n'a pas de paliers : une seule entree atteste tout l'examen.
    'verifications' => [
        'TCF' => ['document' => 'TCF tout public, page officielle de France Éducation international (france-education-international.fr/test/tcf-tout-public), lue dans sa copie archivée du 2026-07-11', 'date' => '2026-11-04'],
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
            'description' => '29 questions QCM (4 choix). Comprendre des mots familiers, des phrases, des échanges, des extraits de conférences/cours.',
            'difficulty_range' => 'A1-C2',
            'format' => 'QCM à 4 choix, difficulté croissante',
            'parts' => [
                ['part' => 1, 'name' => 'Compréhension orale, partie 1', 'name_fr' => 'Questionnaire à choix multiple',
                    "task" => "29 questions à choix multiple, quatre propositions, une seule bonne réponse. 25 minutes.",
                    'type' => 'mcq', 'items' => 29, 'source' => 'officiel'],
            ],
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
            'parts' => [
                ['part' => 1, 'name' => 'Maîtrise des structures de la langue, partie 1', 'name_fr' => 'Questionnaire à choix multiple',
                    "task" => "18 questions à choix multiple, quatre propositions, une seule bonne réponse. 15 minutes.",
                    'type' => 'grammar-mcq', 'items' => 18, 'source' => 'officiel'],
            ],
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
            'parts' => [
                ['part' => 1, 'name' => 'Compréhension écrite, partie 1', 'name_fr' => 'Questionnaire à choix multiple',
                    "task" => "29 questions à choix multiple, quatre propositions, une seule bonne réponse. 45 minutes.",
                    'type' => 'mcq', 'items' => 29, 'source' => 'officiel'],
            ],
            'exercise_types' => ['mcq'],
        ],
    ],

    // ─── Épreuves complémentaires (optionnelles sauf TCF Canada) ───
    'optional_sections' => [
        [
            'slug' => 'expression-ecrite',
            // Sans formats declares, ce module ne produisait rien. Trois taches :
            // un message, un article ou un compte rendu, puis un texte argumente
            // confrontant deux points de vue (source : france-education-international.fr).
            'task_count' => 3,
            'parts' => [
                ['part' => 1, 'name' => 'Expression écrite, tâche 1', 'name_fr' => 'Exercice 1',
                    "task" => "Épreuve complémentaire : 3 exercices en 60 minutes. Premier exercice.",
                    'type' => 'short-writing', 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Expression écrite, tâche 2', 'name_fr' => 'Exercice 2',
                    "task" => "Deuxième des trois exercices de l'épreuve d'expression écrite.",
                    'type' => 'letter-email-writing', 'source' => 'officiel'],
                ['part' => 3, 'name' => 'Expression écrite, tâche 3', 'name_fr' => 'Exercice 3',
                    "task" => "Troisième des trois exercices de l'épreuve d'expression écrite.",
                    'type' => 'essay', 'source' => 'officiel'],
            ],
            'exercise_types' => ['short-writing', 'letter-email-writing', 'essay'],
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
                    'description' => 'Rédiger un message pour décrire, raconter ou expliquer, à un ou plusieurs destinataires (60 à 120 mots).',
                    'min_words' => 60,
                    'max_words' => 120,
                    'exercise_type' => 'short-writing',
                ],
                [
                    'task' => 2,
                    'level' => 'B1-B2',
                    'description' => 'Rédiger un article, un courrier, une note : compte rendu d\'expérience ou récit, avec commentaires ou arguments (120 à 150 mots).',
                    'min_words' => 120,
                    'max_words' => 150,
                    'exercise_type' => 'letter-email-writing',
                ],
                [
                    'task' => 3,
                    'level' => 'C1-C2',
                    'description' => 'Comparer deux points de vue sur un fait de société, exprimés dans deux documents, et donner son avis (120 à 180 mots).',
                    'min_words' => 120,
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
            // Meme panne. Trois taches : entretien dirige, exercice en interaction,
            // puis expression d'un point de vue.
            'task_count' => 3,
            'parts' => [
                ['part' => 1, 'name' => 'Expression orale, tâche 1', 'name_fr' => 'Exercice 1',
                    "task" => "Épreuve complémentaire : 3 exercices en 12 minutes, dont 2 de préparation. Premier exercice.",
                    'type' => 'speaking-response', 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Expression orale, tâche 2', 'name_fr' => 'Exercice 2',
                    "task" => "Deuxième des trois exercices de l'épreuve d'expression orale.",
                    'type' => 'role-play', 'source' => 'officiel'],
                ['part' => 3, 'name' => 'Expression orale, tâche 3', 'name_fr' => 'Exercice 3',
                    "task" => "Troisième des trois exercices de l'épreuve d'expression orale.",
                    'type' => 'speaking-long-turn', 'source' => 'officiel'],
            ],
            'exercise_types' => ['speaking-response', 'role-play', 'speaking-long-turn'],
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
                    'description' => 'Exercice en interaction avec préparation. Jeu de rôle avec l\'examinateur à partir d\'un document déclencheur.',
                    'exercise_type' => 'role-play',
                ],
                [
                    'task' => 3,
                    'level' => 'C1-C2',
                    'duration' => '4 min 30',
                    // Sans preparation : la page officielle le dit en toutes lettres.
                    // Les 2 minutes de preparation de l'epreuve sont celles de la tache 2.
                    'description' => 'Expression d\'un point de vue sans préparation. Répondre de manière spontanée, continue et convaincante à une question choisie par l\'examinateur.',
                    'exercise_type' => 'speaking-discussion',
                ],
            ],
        ],
    ],
];
