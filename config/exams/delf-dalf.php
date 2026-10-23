<?php

/**
 * DELF / DALF - Diplôme d'Études en Langue Française / Diplôme Approfondi de Langue Française
 * Source: france-education-international.fr
 *
 * DELF: A1, A2, B1, B2
 * DALF: C1, C2
 * Scoring: /100 total (4 épreuves x /25), seuil de réussite = 50/100, minimum 5/25 par épreuve
 *
 * IMPORTANT: Chaque niveau a sa propre structure (durées, types de tâches)
 * DALF C2 a une structure radicalement différente (épreuves combinées)
 */

return [
    'slug' => 'delf-dalf',
    'name' => 'DELF/DALF',
    'language' => 'french',

    'scoring' => [
        'type' => 'level',
        'total' => 100,
        'pass_mark' => 50,
        'section_min' => 5,   // Minimum 5/25 par épreuve
        'section_max' => 25,
        'overall_calculation' => 'sum',
    ],

    'variants' => null, // Chaque niveau est un blueprint distinct

    // ─── Structure par niveau ───
    'levels' => [
        // ━━━━━━━━━━ DELF A1 ━━━━━━━━━━
        'A1' => [
            'name' => 'DELF A1',
            'total_duration' => 80, // 1h20
            'sections' => [
                [
                    'slug' => 'comprehension-orale',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 4,
                    'name' => 'Compréhension de l\'oral',
                    'skill_type' => 'listening',
                    'time_limit' => 20,
                    'scoring_weight' => 25,
                    'description' => '3-4 exercices d\'écoute courts (dialogues de la vie quotidienne). Écoute 2 fois.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension de l\'oral, exercice 1', 'name_fr' => 'Annonces',
                                "task" => "Des annonces courtes : choisir la bonne réponse.",
                                'type' => 'mcq', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Compréhension de l\'oral, exercice 2', 'name_fr' => 'Messages',
                                "task" => "Des messages sur répondeur : choisir la bonne réponse.",
                                'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Compréhension de l\'oral, exercice 3', 'name_fr' => 'Échanges du quotidien',
                                "task" => "De courts échanges : choisir la bonne réponse.",
                                'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 4, 'name' => 'Compréhension de l\'oral, exercice 4', 'name_fr' => 'Situations et images',
                                "task" => "Associer chaque situation entendue à la bonne image.",
                                'type' => 'picture-mcq', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'true-false', 'short-answer', 'matching', 'picture-mcq'],
                ],
                [
                    'slug' => 'comprehension-ecrite',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 4,
                    'name' => 'Compréhension des écrits',
                    'skill_type' => 'reading',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'description' => '3-4 exercices de lecture (petites annonces, menus, horaires, courriels simples).',
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension des écrits, exercice 1', 'name_fr' => 'Consignes et panneaux',
                                "task" => "De courts documents du quotidien : choisir la bonne réponse.",
                                'type' => 'mcq', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Compréhension des écrits, exercice 2', 'name_fr' => 'Petites annonces',
                                "task" => "Associer chaque situation à l'annonce qui y répond.",
                                'type' => 'matching', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Compréhension des écrits, exercice 3', 'name_fr' => 'Documents courts',
                                "task" => "Plusieurs documents brefs : choisir la bonne réponse.",
                                'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 4, 'name' => 'Compréhension des écrits, exercice 4', 'name_fr' => 'Message personnel',
                                "task" => "Un message simple, puis des questions à choix multiple.",
                                'type' => 'mcq', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'true-false', 'matching', 'short-answer'],
                ],
                [
                    'slug' => 'production-ecrite',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 2,
                    'name' => 'Production écrite',
                    'skill_type' => 'writing',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'description' => '2 exercices : remplir un formulaire + rédiger des phrases simples (carte postale, message).',
                    'parts' => [
                        ['part' => 1, 'name' => 'Production écrite, exercice 1', 'name_fr' => 'Remplir un formulaire',
                                "task" => "Compléter un formulaire avec ses informations personnelles.",
                                'type' => 'form-completion', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Production écrite, exercice 2', 'name_fr' => 'Message de 40 mots',
                                "task" => "Écrire un message simple, 40 mots au minimum.",
                                'type' => 'short-writing', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['form-completion', 'short-writing'],
                ],
                [
                    'slug' => 'production-orale',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 3,
                    'name' => 'Production orale',
                    'skill_type' => 'speaking',
                    'time_limit' => 7, // 5-7 min
                    'prep_time' => 10,
                    'scoring_weight' => 25,
                    'parts' => [
                        ['part' => 1, 'name' => 'Production orale, exercice 1', 'name_fr' => 'Entretien dirigé',
                                "task" => "Répondre aux questions de l'examinateur sur soi.",
                                'type' => 'speaking-response', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Production orale, exercice 2', 'name_fr' => 'Échange d\'informations',
                                "task" => "Poser des questions simples à partir de mots-clés.",
                                'type' => 'speaking-elicitation', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Production orale, exercice 3', 'name_fr' => 'Dialogue simulé',
                                "task" => "Jouer une situation courante, par exemple acheter quelque chose.",
                                'type' => 'role-play', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['speaking-response', 'speaking-elicitation', 'role-play'],
                ],
            ],
        ],

        // ━━━━━━━━━━ DELF A2 ━━━━━━━━━━
        'A2' => [
            'name' => 'DELF A2',
            'total_duration' => 100, // 1h40
            'sections' => [
                [
                    'slug' => 'comprehension-orale',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 4,
                    'name' => 'Compréhension de l\'oral',
                    'skill_type' => 'listening',
                    'time_limit' => 25,
                    'scoring_weight' => 25,
                    'description' => '3-4 exercices courts. Comprendre des annonces, instructions, conversations courtes.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension de l\'oral, exercice 1', 'name_fr' => 'Annonces publiques',
                                "task" => "Des annonces entendues : choisir la bonne réponse.",
                                'type' => 'mcq', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Compréhension de l\'oral, exercice 2', 'name_fr' => 'Messages',
                                "task" => "Des messages courts : choisir la bonne réponse.",
                                'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Compréhension de l\'oral, exercice 3', 'name_fr' => 'Conversation',
                                "task" => "Un échange du quotidien : choisir la bonne réponse.",
                                'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 4, 'name' => 'Compréhension de l\'oral, exercice 4', 'name_fr' => 'Actes de parole',
                                "task" => "Associer chaque extrait entendu à l'intention qu'il exprime.",
                                'type' => 'matching', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'true-false', 'matching', 'short-answer'],
                ],
                [
                    'slug' => 'comprehension-ecrite',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 4,
                    'name' => 'Compréhension des écrits',
                    'skill_type' => 'reading',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'description' => '3-4 exercices. Comprendre des textes courts de la vie quotidienne (correspondance, publicités, etc.).',
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension des écrits, exercice 1', 'name_fr' => 'Situations et annonces',
                                "task" => "Associer chaque situation au document qui y répond.",
                                'type' => 'matching', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Compréhension des écrits, exercice 2', 'name_fr' => 'Document informatif',
                                "task" => "Un document pratique, puis des questions à choix multiple.",
                                'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Compréhension des écrits, exercice 3', 'name_fr' => 'Message personnel',
                                "task" => "Une lettre ou un courriel, puis des questions à choix multiple.",
                                'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 4, 'name' => 'Compréhension des écrits, exercice 4', 'name_fr' => 'Article court',
                                "task" => "Un article bref : juger des affirmations vraies ou fausses.",
                                'type' => 'true-false', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'true-false', 'matching', 'short-answer'],
                ],
                [
                    'slug' => 'production-ecrite',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 2,
                    'name' => 'Production écrite',
                    'skill_type' => 'writing',
                    'time_limit' => 45,
                    'scoring_weight' => 25,
                    'description' => '2 exercices : décrire un événement ou des expériences personnelles + écrire un message (invitation, remerciement, excuse).',
                    'min_words' => 60,
                    'parts' => [
                        ['part' => 1, 'name' => 'Production écrite, exercice 1', 'name_fr' => 'Raconter un événement',
                                "task" => "Décrire un événement ou une expérience, 60 mots au minimum.",
                                'type' => 'short-writing', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Production écrite, exercice 2', 'name_fr' => 'Répondre à une invitation',
                                "task" => "Répondre à un message : inviter, remercier ou s'excuser. 60 mots au minimum.",
                                'type' => 'letter-email-writing', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['short-writing', 'letter-email-writing'],
                ],
                [
                    'slug' => 'production-orale',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 3,
                    'name' => 'Production orale',
                    'skill_type' => 'speaking',
                    'time_limit' => 8, // 6-8 min
                    'prep_time' => 10,
                    'scoring_weight' => 25,
                    'parts' => [
                        ['part' => 1, 'name' => 'Production orale, exercice 1', 'name_fr' => 'Entretien dirigé',
                                "task" => "Se présenter et répondre aux questions de l'examinateur.",
                                'type' => 'speaking-response', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Production orale, exercice 2', 'name_fr' => 'Monologue suivi',
                                "task" => "Parler de soi et de son quotidien à partir d'un sujet tiré au sort.",
                                'type' => 'speaking-long-turn', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Production orale, exercice 3', 'name_fr' => 'Exercice en interaction',
                                "task" => "Jouer une situation de la vie courante avec l'examinateur.",
                                'type' => 'role-play', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['speaking-response', 'speaking-long-turn', 'role-play'],
                ],
            ],
        ],

        // ━━━━━━━━━━ DELF B1 ━━━━━━━━━━
        'B1' => [
            'name' => 'DELF B1',
            'total_duration' => 115, // 1h55
            'sections' => [
                [
                    'slug' => 'comprehension-orale',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 3,
                    'name' => 'Compréhension de l\'oral',
                    'skill_type' => 'listening',
                    'time_limit' => 25,
                    'scoring_weight' => 25,
                    'description' => '3 exercices. Comprendre des informations sur la vie quotidienne, émissions de radio, discussions.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension de l\'oral, exercice 1', 'name_fr' => 'Document long',
                                "task" => "Un document entendu deux fois, puis des questions à choix multiple.",
                                'type' => 'mcq', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Compréhension de l\'oral, exercice 2', 'name_fr' => 'Document court',
                                "task" => "Un document entendu une fois, puis des questions à choix multiple.",
                                'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Compréhension de l\'oral, exercice 3', 'name_fr' => 'Émission',
                                "task" => "Une émission ou un entretien, puis des questions à choix multiple.",
                                'type' => 'mcq', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'true-false', 'short-answer'],
                ],
                [
                    'slug' => 'comprehension-ecrite',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 2,
                    'name' => 'Compréhension des écrits',
                    'skill_type' => 'reading',
                    'time_limit' => 45,
                    'scoring_weight' => 25,
                    'description' => '2 exercices. Comprendre des textes informatifs et reconnaître des positions.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension des écrits, exercice 1', 'name_fr' => 'Dégager une information',
                                "task" => "Plusieurs documents : trouver celui qui répond à chaque besoin.",
                                'type' => 'matching', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Compréhension des écrits, exercice 2', 'name_fr' => 'Analyser un texte',
                                "task" => "Un article, puis des questions à choix multiple.",
                                'type' => 'mcq', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'true-false', 'matching', 'short-answer'],
                ],
                [
                    'slug' => 'production-ecrite',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 1,
                    'name' => 'Production écrite',
                    'skill_type' => 'writing',
                    'time_limit' => 45,
                    'scoring_weight' => 25,
                    'description' => 'Exprimer une attitude personnelle sur un thème général (essai, courrier, article). ~160 mots minimum.',
                    'min_words' => 160,
                    'parts' => [
                        ['part' => 1, 'name' => 'Production écrite, exercice 1', 'name_fr' => 'Essai argumenté',
                                "task" => "Donner son avis et le justifier, 160 mots au minimum.",
                                'type' => 'essay', 'points' => 25, 'source' => 'tache'],
                    ],
                    'exercise_types' => ['essay', 'letter-email-writing'],
                ],
                [
                    'slug' => 'production-orale',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 3,
                    'name' => 'Production orale',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'prep_time' => 10,
                    'scoring_weight' => 25,
                    'parts' => [
                        ['part' => 1, 'name' => 'Production orale, exercice 1', 'name_fr' => 'Entretien dirigé',
                                "task" => "Parler de soi et répondre aux questions de l'examinateur.",
                                'type' => 'speaking-response', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Production orale, exercice 2', 'name_fr' => 'Exercice en interaction',
                                "task" => "Résoudre une situation en jouant un rôle avec l'examinateur.",
                                'type' => 'role-play', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Production orale, exercice 3', 'name_fr' => 'Expression d\'un point de vue',
                                "task" => "Dégager le thème d'un document court et exprimer son opinion.",
                                'type' => 'speaking-long-turn', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['speaking-response', 'role-play', 'speaking-discussion', 'speaking-long-turn'],
                ],
            ],
        ],

        // ━━━━━━━━━━ DELF B2 ━━━━━━━━━━
        'B2' => [
            'name' => 'DELF B2',
            'total_duration' => 150, // 2h30
            'sections' => [
                [
                    'slug' => 'comprehension-orale',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 2,
                    'name' => 'Compréhension de l\'oral',
                    'skill_type' => 'listening',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'description' => '2 exercices. Comprendre des interviews, journaux télévisés, exposés. Écoute unique pour certains documents.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension de l\'oral, exercice 1', 'name_fr' => 'Interview ou bulletin',
                                "task" => "Un document entendu une fois, puis des questions à choix multiple.",
                                'type' => 'mcq', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Compréhension de l\'oral, exercice 2', 'name_fr' => 'Exposé ou débat',
                                "task" => "Un document entendu deux fois, puis des questions à choix multiple.",
                                'type' => 'mcq', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'true-false', 'short-answer'],
                ],
                [
                    'slug' => 'comprehension-ecrite',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 2,
                    'name' => 'Compréhension des écrits',
                    'skill_type' => 'reading',
                    'time_limit' => 60,
                    'scoring_weight' => 25,
                    'description' => '2 exercices. Textes informatifs et argumentatifs (articles de presse, essais).',
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension des écrits, exercice 1', 'name_fr' => 'Texte informatif',
                                "task" => "Un texte à portée générale, puis des questions à choix multiple.",
                                'type' => 'mcq', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Compréhension des écrits, exercice 2', 'name_fr' => 'Texte argumentatif',
                                "task" => "Un texte d'opinion, puis des questions à choix multiple.",
                                'type' => 'mcq', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'true-false', 'short-answer', 'matching'],
                ],
                [
                    'slug' => 'production-ecrite',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 1,
                    'name' => 'Production écrite',
                    'skill_type' => 'writing',
                    'time_limit' => 60,
                    'scoring_weight' => 25,
                    'description' => 'Prise de position argumentée (lettre formelle, contribution à un débat, article critique). 250 mots minimum.',
                    'min_words' => 250,
                    'parts' => [
                        ['part' => 1, 'name' => 'Production écrite, exercice 1', 'name_fr' => 'Prise de position',
                                "task" => "Écrire une contribution argumentée : courrier, article ou rapport. 250 mots au minimum.",
                                'type' => 'essay', 'points' => 25, 'source' => 'tache'],
                    ],
                    'exercise_types' => ['essay', 'letter-email-writing', 'article-writing'],
                ],
                [
                    'slug' => 'production-orale',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 2,
                    'name' => 'Production orale',
                    'skill_type' => 'speaking',
                    'time_limit' => 20,
                    'prep_time' => 30,
                    'scoring_weight' => 25,
                    'description' => 'Présenter et défendre un point de vue à partir d\'un document déclencheur, puis débat avec l\'examinateur.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Production orale, exercice 1', 'name_fr' => 'Monologue suivi',
                                "task" => "Présenter son point de vue à partir d'un document déclencheur.",
                                'type' => 'speaking-long-turn', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Production orale, exercice 2', 'name_fr' => 'Exercice en interaction',
                                "task" => "Défendre son point de vue dans un débat avec l'examinateur.",
                                'type' => 'oral-debate', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['speaking-long-turn', 'oral-debate', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ DALF C1 ━━━━━━━━━━
        'C1' => [
            'name' => 'DALF C1',
            'total_duration' => 250, // ~4h
            'sections' => [
                [
                    'slug' => 'comprehension-orale',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 2,
                    'name' => 'Compréhension de l\'oral',
                    'skill_type' => 'listening',
                    'time_limit' => 40,
                    'scoring_weight' => 25,
                    'description' => '2 exercices. Document long (~6 min, écoute x2) + plusieurs courts (écoute x1). Émissions radio, conférences.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension de l\'oral, exercice 1', 'name_fr' => 'Document long',
                                "task" => "Un entretien ou un cours entendu deux fois, puis des questions.",
                                'type' => 'mcq', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Compréhension de l\'oral, exercice 2', 'name_fr' => 'Documents brefs',
                                "task" => "Plusieurs documents courts entendus une fois, puis des questions.",
                                'type' => 'mcq', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'short-answer'],
                ],
                [
                    'slug' => 'comprehension-ecrite',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 1,
                    'name' => 'Compréhension des écrits',
                    'skill_type' => 'reading',
                    'time_limit' => 50,
                    'scoring_weight' => 25,
                    'description' => 'Texte long (1500-2000 mots) : littéraire ou journalistique. Questions détaillées.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension des écrits, exercice 1', 'name_fr' => 'Texte long',
                                "task" => "Un texte exigeant de 1 500 à 2 000 mots, puis des questions.",
                                'type' => 'mcq', 'points' => 25, 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'short-answer', 'true-false'],
                ],
                [
                    'slug' => 'production-ecrite',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 2,
                    'name' => 'Production écrite',
                    'skill_type' => 'writing',
                    // Sans formats declares, ce module ne pouvait rien fabriquer.
                    // Ils sont repris de ses deux taches, decrites plus bas.
                    'parts' => [
                        ['part' => 1, 'name' => 'Production écrite, exercice 1', 'name_fr' => 'Synthèse de documents',
                                "task" => "Faire la synthèse de plusieurs documents, 220 à 240 mots.",
                                'type' => 'synthesis', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Production écrite, exercice 2', 'name_fr' => 'Essai argumenté',
                                "task" => "Écrire un texte argumenté à partir du dossier, 250 mots au minimum.",
                                'type' => 'essay', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['synthesis', 'essay'],
                    'time_limit' => 150, // 2h30 pour synthèse + essai
                    'scoring_weight' => 25,
                    'description' => '2 exercices : (1) Synthèse de documents (~220 mots) + (2) Essai argumenté (~250 mots). Domaine au choix : Lettres et sciences humaines OU Sciences.',
                    'tasks' => [
                        [
                            'name' => 'Synthèse de documents',
                            'description' => 'Synthétiser plusieurs documents (~1000 mots au total) en ~220 mots. Reformuler sans donner son avis.',
                            'min_words' => 200,
                            'max_words' => 240,
                            'exercise_type' => 'synthesis',
                        ],
                        [
                            'name' => 'Essai argumenté',
                            'description' => 'Écrire un essai argumenté en relation avec le thème des documents. ~250 mots.',
                            'min_words' => 250,
                            'exercise_type' => 'essay',
                        ],
                    ],
                ],
                [
                    'slug' => 'production-orale',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 2,
                    'name' => 'Production orale',
                    'skill_type' => 'speaking',
                    'time_limit' => 30,
                    'prep_time' => 60,
                    'scoring_weight' => 25,
                    'description' => 'Exposé à partir de documents + discussion avec le jury. Domaine au choix.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Production orale, exercice 1', 'name_fr' => 'Monologue suivi',
                                "task" => "Exposer un point de vue construit à partir d'un dossier de documents.",
                                'type' => 'speaking-long-turn', 'points' => 25, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Production orale, exercice 2', 'name_fr' => 'Exercice en interaction',
                                "task" => "Débattre avec le jury à partir de son exposé.",
                                'type' => 'oral-debate', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['speaking-long-turn', 'oral-debate', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ DALF C2 ━━━━━━━━━━
        'C2' => [
            'name' => 'DALF C2',
            'total_duration' => 210, // ~3h30
            'note' => 'Structure radicalement différente : épreuves combinées',
            'sections' => [
                [
                    'slug' => 'comprehension-production-orales',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 3,
                    'name' => 'Compréhension et production orales',
                    'skill_type' => 'speaking', // Combined listening+speaking
                    'time_limit' => 30,
                    'prep_time' => 0, // Pas de préparation séparée
                    'scoring_weight' => 50, // /50 (2 épreuves combinées)
                    'description' => 'Écouter 2 documents audio (~14 min total), puis présenter son point de vue et débattre avec le jury.',
                    'tasks' => [
                        ['name' => 'Écoute', 'description' => '2 écoutes de documents longs (radio, conférence)'],
                        ['name' => 'Monologue suivi', 'description' => 'Compte rendu + prise de position (~10 min)'],
                        ['name' => 'Débat', 'description' => 'Échange avec le jury sur les thèmes entendus (~10 min)'],
                    ],
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension et production orales, exercice 1', 'name_fr' => 'Compte rendu',
                                "task" => "Rendre compte du contenu d'un document sonore, entendu deux fois.",
                                'type' => 'speaking-long-turn', 'points' => 50, 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Compréhension et production orales, exercice 2', 'name_fr' => 'Développement personnel',
                                "task" => "Développer son point de vue à partir de la problématique du document.",
                                'type' => 'speaking-discussion', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Compréhension et production orales, exercice 3', 'name_fr' => 'Débat avec le jury',
                                "task" => "Défendre sa position face au jury.",
                                'type' => 'oral-debate', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['integrated-speaking', 'speaking-long-turn', 'speaking-discussion', 'oral-debate'],
                ],
                [
                    'slug' => 'comprehension-production-ecrites',
                    // Nombre d'exercices a l'examen reel (source : france-education-international.fr).
                    'task_count' => 1,
                    'name' => 'Compréhension et production écrites',
                    'skill_type' => 'writing', // Combined reading+writing
                    'time_limit' => 210, // 3h30
                    'scoring_weight' => 50, // /50 (2 épreuves combinées)
                    'description' => 'Lire un dossier documentaire (~2000 mots), puis produire un texte structuré (~700 mots).',
                    'tasks' => [
                        [
                            'name' => 'Production d\'un texte structuré',
                            'description' => 'À partir du dossier : article, éditorial, rapport, discours... selon la consigne. Domaine au choix.',
                            'min_words' => 700,
                            'exercise_type' => 'synthesis-essay',
                        ],
                    ],
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension et production des écrits, exercice 1', 'name_fr' => 'Texte structuré',
                                "task" => "Produire un texte structuré — article, éditorial, rapport, discours — à partir d'un dossier d'environ 2 000 mots.",
                                'type' => 'integrated-writing', 'points' => 50, 'source' => 'tache'],
                    ],
                    'exercise_types' => ['synthesis-essay', 'integrated-writing'],
                ],
            ],
        ],
    ],

    'rubric' => [
        'production_ecrite' => [
            ['name' => 'Respect de la consigne', 'slug' => 'respect-consigne', 'max' => 5],
            ['name' => 'Capacité à argumenter', 'slug' => 'argumentation', 'max' => 5],
            ['name' => 'Cohérence et cohésion', 'slug' => 'coherence-cohesion', 'max' => 5],
            ['name' => 'Compétence lexicale', 'slug' => 'lexique', 'max' => 5],
            ['name' => 'Compétence grammaticale', 'slug' => 'grammaire', 'max' => 5],
        ],
        'production_orale' => [
            ['name' => 'Respect de la consigne', 'slug' => 'respect-consigne', 'max' => 5],
            ['name' => 'Capacité à interagir', 'slug' => 'interaction', 'max' => 5],
            ['name' => 'Aisance et fluidité', 'slug' => 'fluidite', 'max' => 5],
            ['name' => 'Compétence lexicale', 'slug' => 'lexique', 'max' => 5],
            ['name' => 'Compétence grammaticale', 'slug' => 'grammaire', 'max' => 5],
        ],
    ],
];
