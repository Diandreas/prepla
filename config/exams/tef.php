<?php

/**
 * TEF - Test d'Évaluation de Français
 * Source: francais.cci-paris-idf.fr (Chambre de Commerce et d'Industrie de Paris)
 *
 * 5 épreuves (pas 4!) incluant Lexique et Structure
 * Scoring: 0-900 total
 * Variantes: TEF, TEF Canada, TEF Québec, TEF IRN
 *
 * CE QUI N'EST PAS ATTESTE NE SE DIT PAS OFFICIEL.
 *
 * Les parties de ce fichier annoncaient « source => officiel », c'est-a-dire
 * « lu dans le document de l'organisme, avec ses chiffres ». Rien ici ne dit
 * QUEL document, ni QUAND il a ete lu : la seule provenance inscrite est un nom
 * de site. La revendication est donc retiree, et les parties passent en
 * « architecture » — la structure telle que nous la comprenons, affichee a
 * l'apprenant comme reconstituee.
 *
 * Ce n'est pas un jugement sur l'exactitude de ces chiffres : ils sont
 * peut-etre justes. C'est qu'on ne peut pas le verifier sur parole, et que
 * l'application retire du contenu sur la foi de ce champ. Pour le rendre a
 * « officiel » : lire le document de l'organisme, noter sa reference et sa
 * date dans la cle `verifications`, et corriger ce qui s'en ecarte.
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
                ['part' => 1, 'name' => 'Comprehension ecrite, Teil 1', 'name_fr' => 'Questionnaire a choix multiple',
                    "task" => "40 questions a choix multiple en 1 heure. Bonne reponse : 1 point.",
                    'type' => 'mcq', 'items' => 40, 'source' => 'architecture'],
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
                ['part' => 1, 'name' => 'Comprehension orale, Teil 1', 'name_fr' => 'Questionnaire a choix multiple',
                    "task" => "40 questions a choix multiple en 40 minutes. Bonne reponse : 1 point.",
                    'type' => 'mcq', 'items' => 40, 'source' => 'architecture'],
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
                ['part' => 1, 'name' => 'Lexique et structure, Teil 1', 'name_fr' => 'Questionnaire a choix multiple',
                    "task" => "40 questions a choix multiple en 30 minutes. Bonne reponse : 1 point.",
                    'type' => 'grammar-mcq', 'items' => 40, 'source' => 'architecture'],
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
            'parts' => [
                ['part' => 1, 'name' => 'Expression ecrite, Teil 1', 'name_fr' => 'Section A',
                    "task" => "Ecrire la suite d'un article, 80 mots au minimum. 25 minutes.",
                    'type' => 'text-continuation', 'source' => 'architecture'],
                ['part' => 2, 'name' => 'Expression ecrite, Teil 2', 'name_fr' => 'Section B',
                    "task" => "Exprimer son point de vue et le justifier, 200 mots au minimum. 35 minutes.",
                    'type' => 'essay', 'source' => 'architecture'],
            ],
            'exercise_types' => ['text-continuation', 'essay'],
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
            // Formats repris des taches decrites ci-dessous : sans eux, ce module
            // ne declarait AUCUN format et ne pouvait rien produire — le sujet
            // sortait ampute de cette epreuve.
            'parts' => [
                ['part' => 1, 'name' => 'Expression orale, Teil 1', 'name_fr' => 'Section A',
                    "task" => "Obtenir des renseignements. 5 minutes.",
                    'type' => 'speaking-elicitation', 'source' => 'architecture'],
                ['part' => 2, 'name' => 'Expression orale, Teil 2', 'name_fr' => 'Section B',
                    "task" => "Argumenter pour convaincre. 10 minutes.",
                    'type' => 'oral-debate', 'source' => 'architecture'],
            ],
            'exercise_types' => ['role-play', 'negotiation', 'speaking-response',
                'speaking-elicitation', 'oral-debate',
            ],
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
