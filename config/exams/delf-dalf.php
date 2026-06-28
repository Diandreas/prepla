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
                    'name' => 'Compréhension de l\'oral',
                    'skill_type' => 'listening',
                    'time_limit' => 20,
                    'scoring_weight' => 25,
                    'description' => '3-4 exercices d\'écoute courts (dialogues de la vie quotidienne). Écoute 2 fois.',
                    'exercise_types' => ['mcq', 'true-false', 'short-answer', 'matching'],
                ],
                [
                    'slug' => 'comprehension-ecrite',
                    'name' => 'Compréhension des écrits',
                    'skill_type' => 'reading',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'description' => '3-4 exercices de lecture (petites annonces, menus, horaires, courriels simples).',
                    'exercise_types' => ['mcq', 'true-false', 'matching', 'short-answer'],
                ],
                [
                    'slug' => 'production-ecrite',
                    'name' => 'Production écrite',
                    'skill_type' => 'writing',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'description' => '2 exercices : remplir un formulaire + rédiger des phrases simples (carte postale, message).',
                    'exercise_types' => ['form-completion', 'short-writing'],
                ],
                [
                    'slug' => 'production-orale',
                    'name' => 'Production orale',
                    'skill_type' => 'speaking',
                    'time_limit' => 7, // 5-7 min
                    'prep_time' => 10,
                    'scoring_weight' => 25,
                    'parts' => [
                        ['name' => 'Entretien dirigé', 'description' => 'Se présenter, parler de soi (1 min)'],
                        ['name' => 'Échange d\'informations', 'description' => 'Poser des questions à partir de mots-clés (2 min)'],
                        ['name' => 'Dialogue simulé', 'description' => 'Jeu de rôle : achat, réservation, etc. (2 min)'],
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
                    'name' => 'Compréhension de l\'oral',
                    'skill_type' => 'listening',
                    'time_limit' => 25,
                    'scoring_weight' => 25,
                    'description' => '3-4 exercices courts. Comprendre des annonces, instructions, conversations courtes.',
                    'exercise_types' => ['mcq', 'true-false', 'matching', 'short-answer'],
                ],
                [
                    'slug' => 'comprehension-ecrite',
                    'name' => 'Compréhension des écrits',
                    'skill_type' => 'reading',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'description' => '3-4 exercices. Comprendre des textes courts de la vie quotidienne (correspondance, publicités, etc.).',
                    'exercise_types' => ['mcq', 'true-false', 'matching', 'short-answer'],
                ],
                [
                    'slug' => 'production-ecrite',
                    'name' => 'Production écrite',
                    'skill_type' => 'writing',
                    'time_limit' => 45,
                    'scoring_weight' => 25,
                    'description' => '2 exercices : décrire un événement ou des expériences personnelles + écrire un message (invitation, remerciement, excuse).',
                    'min_words' => 60,
                    'exercise_types' => ['short-writing', 'letter-email-writing'],
                ],
                [
                    'slug' => 'production-orale',
                    'name' => 'Production orale',
                    'skill_type' => 'speaking',
                    'time_limit' => 8, // 6-8 min
                    'prep_time' => 10,
                    'scoring_weight' => 25,
                    'parts' => [
                        ['name' => 'Entretien dirigé', 'description' => 'Parler de soi, de ses activités (1-2 min)'],
                        ['name' => 'Monologue suivi', 'description' => 'Présenter un thème à partir d\'un document déclencheur (2 min)'],
                        ['name' => 'Exercice en interaction', 'description' => 'Jeu de rôle ou résolution de problème (3-4 min)'],
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
                    'name' => 'Compréhension de l\'oral',
                    'skill_type' => 'listening',
                    'time_limit' => 25,
                    'scoring_weight' => 25,
                    'description' => '3 exercices. Comprendre des informations sur la vie quotidienne, émissions de radio, discussions.',
                    'exercise_types' => ['mcq', 'true-false', 'short-answer'],
                ],
                [
                    'slug' => 'comprehension-ecrite',
                    'name' => 'Compréhension des écrits',
                    'skill_type' => 'reading',
                    'time_limit' => 35,
                    'scoring_weight' => 25,
                    'description' => '2 exercices. Comprendre des textes informatifs et reconnaître des positions.',
                    'exercise_types' => ['mcq', 'true-false', 'matching', 'short-answer'],
                ],
                [
                    'slug' => 'production-ecrite',
                    'name' => 'Production écrite',
                    'skill_type' => 'writing',
                    'time_limit' => 45,
                    'scoring_weight' => 25,
                    'description' => 'Exprimer une attitude personnelle sur un thème général (essai, courrier, article). ~160 mots minimum.',
                    'min_words' => 160,
                    'exercise_types' => ['essay', 'letter-email-writing'],
                ],
                [
                    'slug' => 'production-orale',
                    'name' => 'Production orale',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'prep_time' => 10,
                    'scoring_weight' => 25,
                    'parts' => [
                        ['name' => 'Entretien dirigé', 'description' => 'Parler de soi, projets, habitudes (2-3 min)'],
                        ['name' => 'Exercice en interaction', 'description' => 'Jeu de rôle : résoudre un problème, négocier (3-4 min)'],
                        ['name' => 'Expression d\'un point de vue', 'description' => 'Dégager le thème d\'un document et donner son opinion (5-7 min)'],
                    ],
                    'exercise_types' => ['speaking-response', 'role-play', 'speaking-discussion'],
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
                    'name' => 'Compréhension de l\'oral',
                    'skill_type' => 'listening',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'description' => '2 exercices. Comprendre des interviews, journaux télévisés, exposés. Écoute unique pour certains documents.',
                    'exercise_types' => ['mcq', 'true-false', 'short-answer'],
                ],
                [
                    'slug' => 'comprehension-ecrite',
                    'name' => 'Compréhension des écrits',
                    'skill_type' => 'reading',
                    'time_limit' => 60,
                    'scoring_weight' => 25,
                    'description' => '2 exercices. Textes informatifs et argumentatifs (articles de presse, essais).',
                    'exercise_types' => ['mcq', 'true-false', 'short-answer', 'matching'],
                ],
                [
                    'slug' => 'production-ecrite',
                    'name' => 'Production écrite',
                    'skill_type' => 'writing',
                    'time_limit' => 60,
                    'scoring_weight' => 25,
                    'description' => 'Prise de position argumentée (lettre formelle, contribution à un débat, article critique). 250 mots minimum.',
                    'min_words' => 250,
                    'exercise_types' => ['essay', 'letter-email-writing', 'article-writing'],
                ],
                [
                    'slug' => 'production-orale',
                    'name' => 'Production orale',
                    'skill_type' => 'speaking',
                    'time_limit' => 20,
                    'prep_time' => 30,
                    'scoring_weight' => 25,
                    'description' => 'Présenter et défendre un point de vue à partir d\'un document déclencheur, puis débat avec l\'examinateur.',
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
                    'name' => 'Compréhension de l\'oral',
                    'skill_type' => 'listening',
                    'time_limit' => 40,
                    'scoring_weight' => 25,
                    'description' => '2 exercices. Document long (~6 min, écoute x2) + plusieurs courts (écoute x1). Émissions radio, conférences.',
                    'exercise_types' => ['mcq', 'short-answer'],
                ],
                [
                    'slug' => 'comprehension-ecrite',
                    'name' => 'Compréhension des écrits',
                    'skill_type' => 'reading',
                    'time_limit' => 50,
                    'scoring_weight' => 25,
                    'description' => 'Texte long (1500-2000 mots) : littéraire ou journalistique. Questions détaillées.',
                    'exercise_types' => ['mcq', 'short-answer', 'true-false'],
                ],
                [
                    'slug' => 'production-ecrite',
                    'name' => 'Production écrite',
                    'skill_type' => 'writing',
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
                    'name' => 'Production orale',
                    'skill_type' => 'speaking',
                    'time_limit' => 30,
                    'prep_time' => 60,
                    'scoring_weight' => 25,
                    'description' => 'Exposé à partir de documents + discussion avec le jury. Domaine au choix.',
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
                    'exercise_types' => ['integrated-speaking'],
                ],
                [
                    'slug' => 'comprehension-production-ecrites',
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
                    'exercise_types' => ['synthesis-essay'],
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
