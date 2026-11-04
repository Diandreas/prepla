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

    // Le document qui atteste les parties marquees « officiel ». Au A1, ce sont
    // les quatre exercices de comprehension des ecrits ; le reste du niveau
    // reste « tache » tant qu'aucun document ne fixe ses chiffres.
    'verifications' => [
        'A1' => ['document' => 'DELF A1 tout public, compréhension des écrits : manuel du candidat (france-education-international.fr/document/manuel-candidat-delf-a1) et corrigés des deux sujets d\'exemple (delf-dalf-a1-tp-correcteur-sujet-demo, delfa1toutpubliccorrecteurexemple2), lus dans leurs copies archivées', 'date' => '2026-11-04'],
    ],

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
                    'description' => '4 exercices de 5 questions : un message court, une affiche ou des indications pour s\'orienter dans l\'espace, des instructions ou une publicité pour s\'orienter dans le temps, un article court.',
                    // VERIFIE le 2026-11-04 sur les documents de France Education
                    // international (copies archivees : le site oppose un controle
                    // anti-robot) :
                    //  - le manuel du candidat DELF A1, § 3.4 : « Cette epreuve dure
                    //    30 minutes. Il y a 4 exercices », et l'objet de chacun ;
                    //    § 3.2 : « Je choisis la bonne reponse entre trois choix »
                    //    https://www.france-education-international.fr/document/manuel-candidat-delf-a1
                    //    (https://web.archive.org/web/20251221140604/https://www.france-education-international.fr/document/manuel-candidat-delf-a1) ;
                    //  - les corriges des DEUX sujets d'exemple publies — les deux
                    //    formats en circulation — qui comptent tous deux 5 questions
                    //    par exercice (6, 6, 6 et 7 points) :
                    //    https://www.france-education-international.fr/document/delf-dalf-a1-tp-correcteur-sujet-demo
                    //    https://www.france-education-international.fr/document/delfa1toutpubliccorrecteurexemple2
                    // Le format actualise (exemple 2) est entierement a choix
                    // multiple ; l'exercice 2 n'est donc pas un appariement.
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension des écrits, exercice 1', 'name_fr' => 'Message court',
                                "task" => "Comprendre une instruction simple dans un message court (courriel, carte d'invitation) : 5 questions.",
                                'type' => 'mcq', 'items' => 5, 'points' => 6, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Compréhension des écrits, exercice 2', 'name_fr' => 'S\'orienter dans l\'espace',
                                "task" => "Comprendre une affiche ou des indications dans un lieu public pour s'orienter : 5 questions.",
                                'type' => 'mcq', 'items' => 5, 'points' => 6, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Compréhension des écrits, exercice 3', 'name_fr' => 'S\'orienter dans le temps',
                                "task" => "Comprendre des instructions ou une publicité pour s'orienter dans le temps : 5 questions.",
                                'type' => 'mcq', 'items' => 5, 'points' => 6, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Compréhension des écrits, exercice 4', 'name_fr' => 'Article court',
                                "task" => "Comprendre un article court pour s'informer : 5 questions.",
                                'type' => 'mcq', 'items' => 5, 'points' => 7, 'source' => 'officiel'],
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
                    'description' => '4 exercices : des annonces pour chercher une information, un courriel ou une lettre, des instructions, un article.',
                    // Lu le 2026-11-04 : le manuel du candidat DELF A2, § 3.4, donne
                    // 4 exercices et leur objet, dans cet ordre, et « trois choix »
                    // par question a choix multiple
                    // (https://www.france-education-international.fr/document/manuel-candidat-delf-a2,
                    // copie https://web.archive.org/web/20260422050840/https://www.france-education-international.fr/document/manuel-candidat-delf-a2).
                    // PAS DE NOMBRE D'ITEMS : les deux sujets d'exemple officiels, un
                    // par format en circulation, n'en comptent pas autant — 6, 6, 6
                    // et 5 questions dans l'un, 5, 5, 5 et 6 dans l'autre
                    // (documents delf-a2-tp-correcteur-coll-exemple1 et -exemple2).
                    // Dans les deux, l'exercice 4 est surtout a choix multiple : le
                    // vrai-faux n'y est qu'une question parmi d'autres.
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension des écrits, exercice 1', 'name_fr' => 'Annonces',
                                "task" => "Comprendre des affiches, des menus ou des annonces pour chercher une information : associer chaque document à la personne ou à la situation qui lui correspond.",
                                'type' => 'matching', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Compréhension des écrits, exercice 2', 'name_fr' => 'Courriel ou lettre',
                                "task" => "Comprendre un courrier électronique ou une lettre, puis des questions à choix multiple.",
                                'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Compréhension des écrits, exercice 3', 'name_fr' => 'Instructions',
                                "task" => "Comprendre des instructions (règlement intérieur, recette), puis des questions à choix multiple.",
                                'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 4, 'name' => 'Compréhension des écrits, exercice 4', 'name_fr' => 'Article',
                                "task" => "Comprendre un article de journal, de magazine ou de guide touristique, puis des questions à choix multiple.",
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
                    'description' => '2 exercices (3 dans le format actualisé) : repérer des informations dans plusieurs annonces pour faire un choix, puis comprendre un article d\'intérêt général.',
                    // Lu le 2026-11-04 : le manuel du candidat DELF B1, § 3.4 : « Il y a
                    // 2 ou 3 exercices selon le format proposé » ; exercice 1,
                    // « repérer des informations dans une brochure, une annonce, un
                    // menu pour réaliser une tâche donnée » en mettant « une croix pour
                    // chaque critère et pour chaque annonce » ; exercice 2 (et 3),
                    // « comprendre un article d'intérêt général »
                    // (https://www.france-education-international.fr/document/manuel-candidat-delf-b1,
                    // copie https://web.archive.org/web/20251217101149/https://www.france-education-international.fr/document/manuel-candidat-delf-b1).
                    // La page du DELF B1 annonce 2 exercices et 45 minutes
                    // (https://www.france-education-international.fr/diplome/delf-tout-public/niveau-b1).
                    // L'exercice 1 est une grille oui / non : d'ou le vrai-faux a deux
                    // reponses. PAS DE NOMBRE D'ITEMS : 20 cases et 11 questions dans
                    // un sujet d'exemple, 16 cases puis deux articles de 7 questions
                    // dans l'autre (delf-b1-tp-correcteur-coll-exemple1 et -exemple2).
                    'parts' => [
                        ['part' => 1, 'name' => 'Compréhension des écrits, exercice 1', 'name_fr' => 'Choisir parmi des annonces',
                                "task" => "Plusieurs annonces : pour chacune, dire si elle répond ou non à chaque critère (oui / non), pour faire un choix.",
                                'type' => 'true-false', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Compréhension des écrits, exercice 2', 'name_fr' => 'Article d\'intérêt général',
                                "task" => "Un article d'intérêt général, puis des questions à choix multiple.",
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
                    // Lu le 2026-11-04 : la page du DELF B2 annonce 2 exercices et
                    // 1 heure (france-education-international.fr/diplome/delf-tout-public/niveau-b2).
                    // PAS DE NOMBRE D'ITEMS : les deux sujets d'exemple ne suivent pas
                    // le meme format — deux textes de 10 questions chacun dans l'un,
                    // trois textes de 7, 7 et 6 dans l'autre
                    // (delf-b2-tp-correcteur-coll-exemple1 et -exemple2).
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
