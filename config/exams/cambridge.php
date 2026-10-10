<?php

/**
 * Cambridge English Qualifications
 * Source : cambridgeenglish.org — pages « exam format » et handbooks for teachers
 * de chaque qualification, consultés le 2026-10-11.
 *
 * CINQ qualifications générales adultes, une par palier : A2 Key, B1 Preliminary,
 * B2 First, C1 Advanced, C2 Proficiency. Les versions « for Schools » sont
 * d'autres examens et ne sont pas décrites ici.
 *
 * POURQUOI CE FICHIER A ÉTÉ REFAIT. Il décrivait UNE seule structure de quatre
 * modules, partagée par les trois qualifications qu'il connaissait, avec
 * 90 minutes de Reading and Use of English pour tout le monde. Trois choses y
 * étaient fausses, vérifiées une à une :
 *   — le Reading and Use of English du B2 First dure 75 minutes, pas 90 ;
 *   — celui du C1 Advanced compte HUIT parties, pas sept ;
 *   — le Speaking du C2 Proficiency n'en compte que TROIS, là où le B2 et le C1
 *     en ont quatre.
 * Et les deux qualifications les plus passées manquaient entièrement.
 *
 * DEUX AMÉNAGEMENTS, signalés sur place :
 *   1. Au A2 Key, « Reading and Writing » est UN seul module de 7 parties et
 *      60 minutes. Il est présenté ici en deux — lecture (parties 1 à 5) et
 *      écriture (parties 6 et 7) — parce qu'on ne s'entraîne pas à une
 *      compétence double. Le nombre de parties officiel de chacune est conservé.
 *   2. Le « Use of English » n'est pas une compétence du moteur : il est décrit
 *      avec la lecture, comme sur la copie du candidat.
 *
 * Les niveaux sont indexés par leur CODE CECR — c'est ce que le moteur cherche
 * pour retrouver le plan d'un palier — et le nom de la qualification vit dans
 * « name ». Indexer par le nom laissait le moteur sans plan et lui faisait
 * fabriquer des sujets génériques à la place.
 *
 * Résultat : chaque qualification délivre PLUSIEURS niveaux CECR selon le score
 * (A2 Key certifie A1, A2 ou B1 ; B1 Preliminary certifie A2, B1 ou B2). Les
 * bornes figurent dans « variants ».
 */

return [
    'slug' => 'cambridge',
    'name' => 'Cambridge',
    'language' => 'english',

    'scoring' => [
        'type' => 'level',
        'scale' => 'cambridge_english_scale',
        // Les cinq composantes (lecture, Use of English, écriture, écoute, oral)
        // pèsent le même poids : la note globale est leur moyenne.
        'overall_calculation' => 'average_sections',
    ],

    // Bornes de l'échelle Cambridge English, publiées sur la page officielle des
    // résultats. Un score sous le seuil de la qualification délivre quand même un
    // certificat, au palier inférieur.
    'variants' => [
        'a2-key' => [
            'name' => 'A2 Key (KET)',
            'scale_min' => 100,
            'scale_max' => 150,
            'certifie_niveau_inferieur' => 'A1 de 100 à 119',
            'pass_grade_c' => 120,  // Grade C = A2
            'pass_grade_b' => 133,  // Grade B
            'pass_grade_a' => 140,  // Grade A = B1
        ],
        'b1-preliminary' => [
            'name' => 'B1 Preliminary (PET)',
            'scale_min' => 120,
            'scale_max' => 170,
            'certifie_niveau_inferieur' => 'A2 de 120 à 139',
            'pass_grade_c' => 140,  // Grade C = B1
            'pass_grade_b' => 153,  // Grade B
            'pass_grade_a' => 160,  // Grade A = B2
        ],
        'b2-first' => [
            'name' => 'B2 First (FCE)',
            'scale_min' => 140,
            'scale_max' => 190,
            'pass_grade_c' => 160,  // Grade C = B2
            'pass_grade_b' => 173,
            'pass_grade_a' => 180,  // Grade A = C1
        ],
        'c1-advanced' => [
            'name' => 'C1 Advanced (CAE)',
            'scale_min' => 160,
            'scale_max' => 210,
            'pass_grade_c' => 180,  // Grade C = C1
            'pass_grade_b' => 193,
            'pass_grade_a' => 200,  // Grade A = C2
        ],
        'c2-proficiency' => [
            'name' => 'C2 Proficiency (CPE)',
            'scale_min' => 180,
            'scale_max' => 230,
            'pass_grade_c' => 200,  // Grade C = C2
            'pass_grade_b' => 213,
            'pass_grade_a' => 220,
        ],
    ],

    'levels' => [

        // ━━━━━━━━━━ A2 Key (KET) ━━━━━━━━━━
        // Reading and Writing : 7 parties, 32 questions, 60 min, 50 % de la note.
        // Listening : 5 parties, 25 questions, 30 min (6 min de report inclus), 25 %.
        // Speaking : 2 parties, 8 à 10 min en binôme, 25 %.
        'A2' => [
            'name' => 'A2 Key (KET)',
            'total_duration' => 120,
            'sections' => [
                [
                    'slug' => 'reading',
                    'task_count' => 5,
                    'name' => 'Reading (parties 1 à 5 du module Reading and Writing)',
                    'skill_type' => 'reading',
                    // Part du module commun de 60 minutes. Découpage de notre fait.
                    'time_limit' => 40,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '5 parties : six messages courts en QCM, trois textes à apparier, un texte long en QCM, un texte à trous à trois choix, un texte à trous libre.',
                    'exercise_types' => ['mcq', 'matching', 'mcq-cloze', 'open-cloze'],
                ],
                [
                    'slug' => 'writing',
                    'task_count' => 2,
                    'name' => 'Writing (parties 6 et 7 du module Reading and Writing)',
                    'skill_type' => 'writing',
                    'time_limit' => 20,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '2 parties : un message guidé de 25 mots minimum, puis un récit d\'après trois images, de 35 mots minimum.',
                    'parts' => [
                        ['name' => 'Guided writing', 'description' => 'Écrire un message court et guidé, 25 mots minimum'],
                        ['name' => 'Picture story', 'description' => 'Raconter une histoire d\'après trois images, 35 mots minimum'],
                    ],
                    'exercise_types' => ['short-writing', 'letter-email-writing'],
                ],
                [
                    'slug' => 'listening',
                    'task_count' => 5,
                    'name' => 'Listening',
                    'skill_type' => 'listening',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '5 parties de 5 questions : QCM avec images, texte à trous, QCM sur texte long, appariement. Chaque enregistrement est entendu deux fois ; les 30 minutes incluent 6 minutes de report des réponses.',
                    'exercise_types' => ['picture-mcq', 'mcq', 'note-completion', 'matching'],
                ],
                [
                    'slug' => 'speaking',
                    'task_count' => 2,
                    'name' => 'Speaking',
                    'skill_type' => 'speaking',
                    'time_limit' => 10,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '2 parties : un entretien avec l\'examinateur, puis une tâche collaborative à partir de supports images. 8 à 10 minutes en binôme ; ici tu t\'entraînes seul, au micro.',
                    'parts' => [
                        ['name' => 'Interview', 'description' => 'Donner des informations personnelles et répondre aux questions de l\'examinateur'],
                        ['name' => 'Collaborative task', 'description' => 'Comparer, décrire et donner son avis à partir de supports images'],
                    ],
                    'exercise_types' => ['speaking-response', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ B1 Preliminary (PET) ━━━━━━━━━━
        // Quatre modules séparés depuis la réforme de 2020 : Reading et Writing
        // ne forment plus un seul papier. 25 % chacun.
        'B1' => [
            'name' => 'B1 Preliminary (PET)',
            'total_duration' => 140,
            'sections' => [
                [
                    'slug' => 'reading',
                    'task_count' => 6,
                    'name' => 'Reading',
                    'skill_type' => 'reading',
                    'time_limit' => 45,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '6 parties, 32 questions : cinq messages courts en QCM, cinq personnes à rattacher à huit textes, un texte long en QCM, un texte lacunaire à compléter par des phrases, un texte à trous à quatre choix, un texte à trous libre.',
                    'exercise_types' => ['mcq', 'matching', 'gapped-text', 'mcq-cloze', 'open-cloze'],
                ],
                [
                    'slug' => 'writing',
                    'task_count' => 2,
                    'name' => 'Writing',
                    'skill_type' => 'writing',
                    'time_limit' => 45,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '2 tâches d\'environ 100 mots, notées sur 20 chacune : un courriel guidé obligatoire, puis un article ou une histoire au choix.',
                    'parts' => [
                        ['name' => 'Email', 'description' => 'Répondre à un courriel en traitant les notes fournies, environ 100 mots'],
                        ['name' => 'Article or story', 'description' => 'Au choix : un article ou une histoire sur le sujet donné, environ 100 mots'],
                    ],
                    'exercise_types' => ['letter-email-writing', 'article-writing', 'short-writing'],
                ],
                [
                    'slug' => 'listening',
                    'task_count' => 4,
                    'name' => 'Listening',
                    'skill_type' => 'listening',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '4 parties, 25 questions : QCM avec images, QCM sur textes courts, texte à trous à six items, QCM sur un entretien long.',
                    'exercise_types' => ['picture-mcq', 'mcq', 'note-completion'],
                ],
                [
                    'slug' => 'speaking',
                    'task_count' => 4,
                    'name' => 'Speaking',
                    'skill_type' => 'speaking',
                    'time_limit' => 12,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '4 parties : entretien, exposé individuel sur une photographie, tâche collaborative à partir de supports images, puis discussion générale. 10 à 12 minutes en binôme.',
                    'parts' => [
                        ['name' => 'Interview', 'description' => 'Questions factuelles et personnelles, environ 2 minutes'],
                        ['name' => 'Individual long turn', 'description' => 'Décrire une photographie en couleur dans un tour de parole prolongé'],
                        ['name' => 'Collaborative task', 'description' => 'Discuter d\'une situation à partir de supports images'],
                        ['name' => 'Discussion', 'description' => 'Discussion générale sur le thème de la tâche précédente'],
                    ],
                    'exercise_types' => ['speaking-response', 'speaking-long-turn', 'speaking-discussion', 'negotiation'],
                ],
            ],
        ],

        // ━━━━━━━━━━ B2 First (FCE) ━━━━━━━━━━
        // Reading and Use of English : 7 parties, 52 questions, 75 MINUTES — et non
        // 90, ce que ce fichier annonçait pour les trois qualifications à la fois.
        'B2' => [
            'name' => 'B2 First (FCE)',
            'total_duration' => 209,
            'sections' => [
                [
                    'slug' => 'reading-use-of-english',
                    'task_count' => 7,
                    'name' => 'Reading and Use of English',
                    'skill_type' => 'reading',
                    'time_limit' => 75,
                    'scoring_weight' => 40,
                    'max_score' => 100,
                    'description' => '7 parties, 52 questions, 2 200 à 2 500 mots à lire : texte à trous à quatre choix, texte à trous libre, formation de mots, transformations avec mot imposé, QCM, texte lacunaire, appariement multiple.',
                    'exercise_types' => ['mcq-cloze', 'open-cloze', 'word-formation', 'key-word-transformation', 'mcq', 'gapped-text', 'multiple-matching'],
                ],
                [
                    'slug' => 'writing',
                    'task_count' => 2,
                    'name' => 'Writing',
                    'skill_type' => 'writing',
                    'time_limit' => 80,
                    'scoring_weight' => 20,
                    'max_score' => 100,
                    'description' => '2 tâches de 140 à 190 mots, notées sur 20 chacune : un essai obligatoire à partir de deux idées fournies, puis un article, un courriel, une critique ou un rapport au choix.',
                    'exercise_types' => ['essay', 'article-writing', 'letter-email-writing', 'review-writing', 'report-writing'],
                ],
                [
                    'slug' => 'listening',
                    'task_count' => 4,
                    'name' => 'Listening',
                    'skill_type' => 'listening',
                    'time_limit' => 40,
                    'scoring_weight' => 20,
                    'max_score' => 100,
                    'description' => '4 parties, 30 questions : huit enregistrements courts en QCM, un monologue à compléter, cinq monologues liés à apparier, un entretien long en QCM. Chaque enregistrement est entendu deux fois.',
                    'exercise_types' => ['mcq', 'sentence-completion', 'multiple-matching'],
                ],
                [
                    'slug' => 'speaking',
                    'task_count' => 4,
                    'name' => 'Speaking',
                    'skill_type' => 'speaking',
                    'time_limit' => 14,
                    'scoring_weight' => 20,
                    'max_score' => 100,
                    'description' => '4 parties : entretien, tour de parole d\'une minute sur deux photographies, tâche collaborative avec décision commune, puis discussion. 14 minutes en binôme.',
                    'parts' => [
                        ['name' => 'Interview', 'description' => 'Parler de soi, de son passé, de son présent et de ses projets — 2 minutes'],
                        ['name' => 'Long turn', 'description' => 'Parler seul une minute sur une paire de photographies'],
                        ['name' => 'Collaborative task', 'description' => 'Discuter à partir d\'amorces écrites, puis décider ensemble'],
                        ['name' => 'Discussion', 'description' => 'Discussion approfondie guidée par l\'examinateur — 4 minutes'],
                    ],
                    'exercise_types' => ['speaking-response', 'speaking-long-turn', 'negotiation', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ C1 Advanced (CAE) ━━━━━━━━━━
        // Reading and Use of English : HUIT parties, 56 questions, 90 minutes. La
        // huitième est un appariement croisé entre plusieurs textes, qui n'existe
        // ni au B2 ni au C2.
        'C1' => [
            'name' => 'C1 Advanced (CAE)',
            'total_duration' => 235,
            'sections' => [
                [
                    'slug' => 'reading-use-of-english',
                    'task_count' => 8,
                    'name' => 'Reading and Use of English',
                    'skill_type' => 'reading',
                    'time_limit' => 90,
                    'scoring_weight' => 40,
                    'max_score' => 100,
                    'description' => '8 parties, 56 questions, 3 000 à 3 500 mots à lire : les sept formats du B2, plus un appariement croisé entre plusieurs textes.',
                    'exercise_types' => ['mcq-cloze', 'open-cloze', 'word-formation', 'key-word-transformation', 'mcq', 'gapped-text', 'multiple-matching', 'matching-information'],
                ],
                [
                    'slug' => 'writing',
                    'task_count' => 2,
                    'name' => 'Writing',
                    'skill_type' => 'writing',
                    'time_limit' => 90,
                    'scoring_weight' => 20,
                    'max_score' => 100,
                    'description' => '2 tâches : un essai obligatoire, puis une lettre ou un courriel, une proposition, un rapport ou une critique au choix.',
                    'exercise_types' => ['essay', 'letter-email-writing', 'report-writing', 'review-writing'],
                ],
                [
                    'slug' => 'listening',
                    'task_count' => 4,
                    'name' => 'Listening',
                    'skill_type' => 'listening',
                    'time_limit' => 40,
                    'scoring_weight' => 20,
                    'max_score' => 100,
                    'description' => '4 parties, 30 questions : QCM, phrases à compléter, QCM, appariement multiple. Émissions, discours, conversations ; chaque enregistrement est entendu deux fois.',
                    'exercise_types' => ['mcq', 'sentence-completion', 'multiple-matching'],
                ],
                [
                    'slug' => 'speaking',
                    'task_count' => 4,
                    'name' => 'Speaking',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'scoring_weight' => 20,
                    'max_score' => 100,
                    'description' => '4 parties : entretien, tour de parole long, tâche collaborative, puis discussion. 15 minutes en binôme.',
                    'exercise_types' => ['speaking-response', 'speaking-long-turn', 'negotiation', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ C2 Proficiency (CPE) ━━━━━━━━━━
        // Le Speaking n'a que TROIS parties, contrairement au B2 First et au
        // C1 Advanced qui en ont quatre.
        'C2' => [
            'name' => 'C2 Proficiency (CPE)',
            'total_duration' => 236,
            'sections' => [
                [
                    'slug' => 'reading-use-of-english',
                    'task_count' => 7,
                    'name' => 'Reading and Use of English',
                    'skill_type' => 'reading',
                    'time_limit' => 90,
                    'scoring_weight' => 40,
                    'max_score' => 100,
                    'description' => '7 parties, 53 questions, environ 3 000 mots à lire, tirés de fiction et d\'essais : texte à trous à quatre choix, texte à trous libre, formation de mots, transformations avec mot imposé, QCM, texte lacunaire, appariement multiple.',
                    'exercise_types' => ['mcq-cloze', 'open-cloze', 'word-formation', 'key-word-transformation', 'mcq', 'gapped-text', 'multiple-matching'],
                ],
                [
                    'slug' => 'writing',
                    'task_count' => 2,
                    'name' => 'Writing',
                    'skill_type' => 'writing',
                    'time_limit' => 90,
                    'scoring_weight' => 20,
                    'max_score' => 100,
                    'description' => '2 tâches : un essai discursif obligatoire à partir de deux textes, puis un article, une lettre, un rapport ou une critique au choix.',
                    'exercise_types' => ['essay', 'synthesis-essay', 'article-writing', 'letter-writing', 'report-writing', 'review-writing'],
                ],
                [
                    'slug' => 'listening',
                    'task_count' => 4,
                    'name' => 'Listening',
                    'skill_type' => 'listening',
                    'time_limit' => 40,
                    'scoring_weight' => 20,
                    'max_score' => 100,
                    'description' => '4 parties, 30 questions : conversations, conférences et séminaires. Chaque enregistrement est entendu deux fois.',
                    'exercise_types' => ['mcq', 'sentence-completion', 'multiple-matching'],
                ],
                [
                    'slug' => 'speaking',
                    'task_count' => 3,
                    'name' => 'Speaking',
                    'skill_type' => 'speaking',
                    'time_limit' => 16,
                    'scoring_weight' => 20,
                    'max_score' => 100,
                    'description' => '3 parties seulement : entretien, tâche collaborative, puis tour de parole long suivi d\'une discussion. 16 minutes en binôme.',
                    'parts' => [
                        ['name' => 'Interview', 'description' => 'Échange initial avec l\'examinateur'],
                        ['name' => 'Collaborative task', 'description' => 'Discuter et décider avec l\'autre candidat'],
                        ['name' => 'Long turn and discussion', 'description' => 'Exposé individuel suivi d\'une discussion approfondie'],
                    ],
                    'exercise_types' => ['speaking-response', 'negotiation', 'speaking-long-turn', 'speaking-discussion'],
                ],
            ],
        ],
    ],
];
