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
 *
 * C'EST FAIT le 2026-11-04. Documents lus sur lefrancaisdesaffaires.fr :
 *  - page « TEF Canada, presentation »
 *    (https://www.lefrancaisdesaffaires.fr/candidat/test-evaluation-francais/tef-canada/presentation/) :
 *    comprehension ecrite 40 questions en 1 heure, comprehension orale
 *    40 questions en 40 minutes, QCM a 1 point ; expression ecrite 1 heure en
 *    deux sections (A : suite d'un article, 80 mots minimum, 25 min ;
 *    B : point de vue justifie, 200 mots minimum, 35 min) ; expression orale
 *    15 minutes (A : obtenir des renseignements, 5 min ; B : argumenter pour
 *    convaincre, 10 min) ;
 *  - page « TEF Etudes, presentation » (.../tef-etudes/presentation/) :
 *    lexique et structure 40 questions en 30 minutes ;
 *  - livrets d'exemples (wp-content/uploads/2024/10/tef-exemples-epreuves-ce.pdf
 *    et -co.pdf) : quatre choix, A a D.
 * Les parties passent donc a « officiel ». Les baremes (max_score) n'ont pas
 * ete relus : ils restent tels quels.
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

    // Le TEF n'a pas de paliers : une seule entree atteste tout l'examen.
    'verifications' => [
        'TEF' => ['document' => 'TEF Canada et TEF Études, pages « présentation » de lefrancaisdesaffaires.fr, et livrets d\'exemples de compréhension écrite et orale (tef-exemples-epreuves-ce.pdf, -co.pdf)', 'date' => '2026-11-04'],
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
            'question_count' => 40,
            'max_score' => 300,
            'scoring_weight' => 20,
            'description' => '40 questions QCM (4 choix) en 1 heure : documents de la vie quotidienne, articles, textes administratifs et argumentatifs.',
            'parts' => [
                ['part' => 1, 'name' => 'Compréhension écrite, partie 1', 'name_fr' => 'Questionnaire à choix multiple',
                    "task" => "40 questions à choix multiple en 1 heure. Bonne réponse : 1 point.",
                    'type' => 'mcq', 'items' => 40, 'source' => 'officiel'],
            ],
            'exercise_types' => ['mcq'],
        ],

        // ─── COMPRÉHENSION ORALE ───
        [
            'slug' => 'comprehension-orale',
            'name' => 'Compréhension orale',
            'skill_type' => 'listening',
            'time_limit' => 40,
            'question_count' => 40,
            'max_score' => 360,
            'scoring_weight' => 20,
            'description' => '40 questions QCM (4 choix) en 40 minutes : messages courts, dialogues, documents radiophoniques.',
            'parts' => [
                ['part' => 1, 'name' => 'Compréhension orale, partie 1', 'name_fr' => 'Questionnaire à choix multiple',
                    "task" => "40 questions à choix multiple en 40 minutes. Bonne réponse : 1 point.",
                    'type' => 'mcq', 'items' => 40, 'source' => 'officiel'],
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
            'description' => '40 questions QCM en 30 minutes (TEF Études). Vocabulaire et grammaire : choisir la forme correcte.',
            'parts' => [
                ['part' => 1, 'name' => 'Lexique et structure, partie 1', 'name_fr' => 'Questionnaire à choix multiple',
                    "task" => "40 questions à choix multiple en 30 minutes. Bonne réponse : 1 point.",
                    'type' => 'grammar-mcq', 'items' => 40, 'source' => 'officiel'],
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
                ['part' => 1, 'name' => 'Expression écrite, partie 1', 'name_fr' => 'Section A',
                    "task" => "Écrire la suite d'un article, 80 mots au minimum. 25 minutes.",
                    'type' => 'text-continuation', 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Expression écrite, partie 2', 'name_fr' => 'Section B',
                    "task" => "Exprimer son point de vue et le justifier, 200 mots au minimum. 35 minutes.",
                    'type' => 'essay', 'source' => 'officiel'],
            ],
            'exercise_types' => ['text-continuation', 'essay'],
            'tasks' => [
                [
                    'task' => 1,
                    'name' => 'Section A — Écrire la suite d\'un article',
                    'description' => 'Écrire la suite d\'un article dont on lit le début (80 mots minimum, 25 minutes).',
                    'min_words' => 80,
                    'exercise_type' => 'text-continuation',
                ],
                [
                    'task' => 2,
                    'name' => 'Section B — Exprimer son point de vue',
                    'description' => 'Exprimer son point de vue sur une affirmation et le justifier (200 mots minimum, 35 minutes).',
                    'min_words' => 200,
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
                ['part' => 1, 'name' => 'Expression orale, partie 1', 'name_fr' => 'Section A',
                    "task" => "Obtenir des renseignements. 5 minutes.",
                    'type' => 'speaking-elicitation', 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Expression orale, partie 2', 'name_fr' => 'Section B',
                    "task" => "Argumenter pour convaincre. 10 minutes.",
                    'type' => 'oral-debate', 'source' => 'officiel'],
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
                    'name' => 'Section A — Obtenir des renseignements',
                    'description' => 'Jeu de rôle : obtenir des renseignements en posant des questions (5 minutes).',
                    'exercise_type' => 'speaking-elicitation',
                ],
                [
                    'task' => 2,
                    'name' => 'Section B — Argumenter pour convaincre',
                    'description' => 'Argumenter pour convaincre un interlocuteur (10 minutes).',
                    'exercise_type' => 'oral-debate',
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
