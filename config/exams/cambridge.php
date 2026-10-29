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

    /*
     * QUI A VERIFIE QUOI, ET QUAND.
     *
     * « source => officiel » sur une partie signifie : lu dans le document de
     * l'organisme, avec ses chiffres. La provenance ci-dessous reprend celle
     * que l'en-tete de ce fichier declare — pages « exam format » et handbooks
     * for teachers de chaque qualification — avec sa date de consultation.
     */
    'verifications' => [
        'A2' => ['document' => 'Cambridge English, page « exam format » et Handbook for teachers de la qualification du palier A2, cambridgeenglish.org', 'date' => '2026-10-11'],
        'B1' => ['document' => 'Cambridge English, page « exam format » et Handbook for teachers de la qualification du palier B1, cambridgeenglish.org', 'date' => '2026-10-11'],
        'B2' => ['document' => 'Cambridge English, page « exam format » et Handbook for teachers de la qualification du palier B2, cambridgeenglish.org', 'date' => '2026-10-11'],
        'C1' => ['document' => 'Cambridge English, page « exam format » et Handbook for teachers de la qualification du palier C1, cambridgeenglish.org', 'date' => '2026-10-11'],
        'C2' => ['document' => 'Cambridge English, page « exam format » et Handbook for teachers de la qualification du palier C2, cambridgeenglish.org', 'date' => '2026-10-11'],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Reading and Writing, Part 1', 'name_fr' => 'Multiple choice',
                            "task" => "Six textes courts du quotidien : en saisir le message principal.",
                            'type' => 'mcq', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Reading and Writing, Part 2', 'name_fr' => 'Multiple matching',
                            "task" => "Associer sept questions a trois textes courts sur un meme theme.",
                            'type' => 'matching', 'items' => 7, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Reading and Writing, Part 3', 'name_fr' => 'Multiple choice',
                            "task" => "Un texte long : detail et idees principales.",
                            'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Reading and Writing, Part 4', 'name_fr' => 'Multiple-choice cloze',
                            "task" => "Choisir le mot qui convient pour chaque trou d'un texte factuel.",
                            'type' => 'mcq-cloze', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 5, 'name' => 'Reading and Writing, Part 5', 'name_fr' => 'Open cloze',
                            "task" => "Completer les trous d'un courriel, un mot par trou.",
                            'type' => 'open-cloze', 'items' => 6, 'source' => 'officiel'],
                    ],
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
                        ['part' => 6, 'name' => 'Reading and Writing, Part 6', 'name_fr' => 'Guided writing',
                            "task" => "Ecrire un courriel ou une note de 25 mots au minimum. 15 points.",
                            'type' => 'short-writing', 'items' => 1, 'source' => 'officiel'],
                        ['part' => 7, 'name' => 'Reading and Writing, Part 7', 'name_fr' => 'Picture story',
                            "task" => "Ecrire une histoire de 35 mots au minimum a partir de trois images. 15 points.",
                            'type' => 'short-writing', 'items' => 1, 'source' => 'officiel'],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Listening, Part 1', 'name_fr' => 'Multiple choice',
                            "task" => "Cinq courts dialogues : choisir la bonne image.",
                            'type' => 'picture-mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Listening, Part 2', 'name_fr' => 'Gap fill',
                            "task" => "Completer des notes a partir d'un monologue.",
                            'type' => 'note-completion', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Listening, Part 3', 'name_fr' => 'Multiple choice',
                            "task" => "Cinq questions a trois options sur un dialogue.",
                            'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Listening, Part 4', 'name_fr' => 'Multiple choice',
                            "task" => "Cinq extraits courts : en saisir l'idee principale.",
                            'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 5, 'name' => 'Listening, Part 5', 'name_fr' => 'Matching',
                            "task" => "Associer cinq elements a un dialogue.",
                            'type' => 'matching', 'items' => 5, 'source' => 'officiel'],
                    ],
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
                        ['part' => 1, 'name' => 'Speaking, Part 1', 'name_fr' => 'Interview',
                            "task" => "Repondre aux questions de l'examinateur. 3 a 4 minutes.",
                            'type' => 'speaking-response', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Speaking, Part 2', 'name_fr' => 'Discussion',
                            "task" => "Echanger avec l'autre candidat a partir d'un support. 5 a 6 minutes.",
                            'type' => 'speaking-discussion', 'source' => 'officiel'],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Reading, Part 1', 'name_fr' => 'Multiple choice',
                            "task" => "Cinq avis ou messages reels : en saisir le message principal.",
                            'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Reading, Part 2', 'name_fr' => 'Matching',
                            "task" => "Associer cinq descriptions de personnes a huit textes courts.",
                            'type' => 'matching', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Reading, Part 3', 'name_fr' => 'Multiple choice',
                            "task" => "Un texte long : detail, sens general, implicite, attitude.",
                            'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Reading, Part 4', 'name_fr' => 'Gapped text',
                            "task" => "Replacer cinq phrases retirees d'un texte long.",
                            'type' => 'gapped-text', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 5, 'name' => 'Reading, Part 5', 'name_fr' => 'Multiple-choice cloze',
                            "task" => "Choisir le mot qui convient pour chaque trou d'un texte court.",
                            'type' => 'mcq-cloze', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 6, 'name' => 'Reading, Part 6', 'name_fr' => 'Open cloze',
                            "task" => "Completer six trous, un mot par trou.",
                            'type' => 'open-cloze', 'items' => 6, 'source' => 'officiel'],
                    ],
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
                        ['part' => 1, 'name' => 'Writing, Part 1', 'name_fr' => 'Courriel',
                            "task" => "Repondre a un courriel et a des notes, environ 100 mots. 20 points.",
                            'type' => 'letter-email-writing', 'items' => 1, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Writing, Part 2', 'name_fr' => 'Article ou recit',
                            "task" => "Au choix : un article ou une histoire, environ 100 mots. 20 points.",
                            'type' => 'article-writing', 'items' => 1, 'source' => 'officiel'],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Listening, Part 1', 'name_fr' => 'Multiple choice',
                            "task" => "Sept extraits courts : choisir la bonne image.",
                            'type' => 'picture-mcq', 'items' => 7, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Listening, Part 2', 'name_fr' => 'Multiple choice',
                            "task" => "Six dialogues courts : en saisir le sens general.",
                            'type' => 'mcq', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Listening, Part 3', 'name_fr' => 'Gap fill',
                            "task" => "Completer six trous a partir d'un monologue.",
                            'type' => 'note-completion', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Listening, Part 4', 'name_fr' => 'Multiple choice',
                            "task" => "Six questions sur un entretien : attitude et opinion.",
                            'type' => 'mcq', 'items' => 6, 'source' => 'officiel'],
                    ],
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
                        ['part' => 1, 'name' => 'Speaking, Part 1', 'name_fr' => 'Interview',
                            "task" => "Repondre aux questions de l'examinateur. 2 minutes.",
                            'type' => 'speaking-response', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Speaking, Part 2', 'name_fr' => 'Extended turn',
                            "task" => "Parler environ une minute d'une photographie. 3 minutes.",
                            'type' => 'speaking-long-turn', 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Speaking, Part 3', 'name_fr' => 'Discussion',
                            "task" => "Discuter avec l'autre candidat a partir d'un support. 4 minutes.",
                            'type' => 'speaking-discussion', 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Speaking, Part 4', 'name_fr' => 'General conversation',
                            "task" => "Conversation generale avec l'autre candidat. 3 minutes.",
                            'type' => 'speaking-discussion', 'source' => 'officiel'],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Reading and Use of English, Part 1', 'name_fr' => 'Multiple-choice cloze',
                            "task" => "Choisir entre A, B, C et D pour chaque trou. 1 point par question.",
                            'type' => 'mcq-cloze', 'items' => 8, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Reading and Use of English, Part 2', 'name_fr' => 'Open cloze',
                            "task" => "Trouver le mot qui manque dans chaque trou.",
                            'type' => 'open-cloze', 'items' => 8, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Reading and Use of English, Part 3', 'name_fr' => 'Word formation',
                            "task" => "Transformer un mot donne pour completer chaque trou.",
                            'type' => 'word-formation', 'items' => 8, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Reading and Use of English, Part 4', 'name_fr' => 'Key word transformations',
                            "task" => "Reecrire une phrase avec un mot impose. Jusqu'a 2 points par question.",
                            'type' => 'key-word-transformation', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 5, 'name' => 'Reading and Use of English, Part 5', 'name_fr' => 'Multiple choice',
                            "task" => "Questions A-D sur le detail, l'opinion et l'attitude. 2 points chacune.",
                            'type' => 'mcq', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 6, 'name' => 'Reading and Use of English, Part 6', 'name_fr' => 'Gapped text',
                            "task" => "Replacer des phrases dans les trous d'un texte. 2 points chacune.",
                            'type' => 'gapped-text', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 7, 'name' => 'Reading and Use of English, Part 7', 'name_fr' => 'Multiple matching',
                            "task" => "Associer des questions aux passages d'un ou plusieurs textes.",
                            'type' => 'multiple-matching', 'items' => 10, 'source' => 'officiel'],
                    ],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Writing, Part 1', 'name_fr' => 'Essai obligatoire',
                            "task" => "Donner son opinion sur un sujet impose, en utilisant deux idees fournies et une personnelle. 140 a 190 mots.",
                            'type' => 'essay', 'items' => 1, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Writing, Part 2', 'name_fr' => 'Au choix',
                            "task" => "Au choix : article, courriel ou lettre, critique, rapport. 140 a 190 mots.",
                            'type' => 'article-writing', 'items' => 1, 'source' => 'officiel'],
                    ],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Listening, Part 1', 'name_fr' => 'Multiple choice',
                            "task" => "Huit enregistrements d'environ 30 secondes, une question A/B/C chacun.",
                            'type' => 'mcq', 'items' => 8, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Listening, Part 2', 'name_fr' => 'Sentence completion',
                            "task" => "Completer des phrases a partir d'un monologue de 3 a 4 minutes.",
                            'type' => 'sentence-completion', 'items' => 10, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Listening, Part 3', 'name_fr' => 'Multiple matching',
                            "task" => "Associer cinq monologues courts a huit affirmations.",
                            'type' => 'multiple-matching', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Listening, Part 4', 'name_fr' => 'Multiple choice',
                            "task" => "Sept questions A/B/C sur un entretien de 3 a 4 minutes.",
                            'type' => 'mcq', 'items' => 7, 'source' => 'officiel'],
                    ],
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
                        ['part' => 1, 'name' => 'Speaking, Part 1', 'name_fr' => 'Interview',
                            "task" => "Echange avec l'examinateur. 2 minutes par candidat.",
                            'type' => 'speaking-response', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Speaking, Part 2', 'name_fr' => 'Long turn',
                            "task" => "Parler seul une minute, puis reaction du partenaire pendant 30 secondes.",
                            'type' => 'speaking-long-turn', 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Speaking, Part 3', 'name_fr' => 'Collaborative task',
                            "task" => "Discussion de 2 minutes avec l'autre candidat, puis 1 minute pour decider.",
                            'type' => 'negotiation', 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Speaking, Part 4', 'name_fr' => 'Discussion',
                            "task" => "Discussion avec l'autre candidat. 4 minutes.",
                            'type' => 'speaking-discussion', 'source' => 'officiel'],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Reading and Use of English, Part 1', 'name_fr' => 'Multiple-choice cloze',
                            "task" => "Choisir entre A, B, C et D pour chaque trou.",
                            'type' => 'mcq-cloze', 'items' => 8, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Reading and Use of English, Part 2', 'name_fr' => 'Open cloze',
                            "task" => "Trouver le seul mot qui manque dans chaque trou.",
                            'type' => 'open-cloze', 'items' => 8, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Reading and Use of English, Part 3', 'name_fr' => 'Word formation',
                            "task" => "Transformer un mot donne pour completer chaque trou.",
                            'type' => 'word-formation', 'items' => 8, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Reading and Use of English, Part 4', 'name_fr' => 'Key word transformations',
                            "task" => "Reecrire en 3 a 6 mots avec un mot impose. Jusqu'a 2 points par question.",
                            'type' => 'key-word-transformation', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 5, 'name' => 'Reading and Use of English, Part 5', 'name_fr' => 'Multiple choice',
                            "task" => "Questions A-D : detail, opinion, ton, attitude. 2 points chacune.",
                            'type' => 'mcq', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 6, 'name' => 'Reading and Use of English, Part 6', 'name_fr' => 'Cross-text multiple matching',
                            "task" => "Croiser quatre textes courts. 2 points par question.",
                            'type' => 'multiple-matching', 'items' => 4, 'source' => 'officiel'],
                        ['part' => 7, 'name' => 'Reading and Use of English, Part 7', 'name_fr' => 'Gapped text',
                            "task" => "Replacer des paragraphes dans les trous. 2 points chacune.",
                            'type' => 'gapped-text', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 8, 'name' => 'Reading and Use of English, Part 8', 'name_fr' => 'Multiple matching',
                            "task" => "Associer des questions aux passages d'un ou plusieurs textes.",
                            'type' => 'multiple-matching', 'items' => 10, 'source' => 'officiel'],
                    ],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Writing, Part 1', 'name_fr' => 'Essai obligatoire',
                            "task" => "Lire un texte, puis ecrire un essai sur deux points en disant lequel compte le plus. 220 a 260 mots.",
                            'type' => 'essay', 'items' => 1, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Writing, Part 2', 'name_fr' => 'Au choix',
                            "task" => "Au choix parmi trois : lettre ou courriel, proposition, rapport, critique. 220 a 260 mots.",
                            'type' => 'report-writing', 'items' => 1, 'source' => 'officiel'],
                    ],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Listening, Part 1', 'name_fr' => 'Multiple choice',
                            "task" => "Trois extraits de conversation, deux questions A/B/C chacun.",
                            'type' => 'mcq', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Listening, Part 2', 'name_fr' => 'Sentence completion',
                            "task" => "Completer des phrases a partir d'un monologue d'environ 3 minutes.",
                            'type' => 'sentence-completion', 'items' => 8, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Listening, Part 3', 'name_fr' => 'Multiple choice',
                            "task" => "Questions A-D sur une conversation d'environ 4 minutes.",
                            'type' => 'mcq', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Listening, Part 4', 'name_fr' => 'Multiple matching',
                            "task" => "Associer cinq monologues a huit options, pour deux taches.",
                            'type' => 'multiple-matching', 'items' => 10, 'source' => 'officiel'],
                    ],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Speaking, Part 1', 'name_fr' => 'Interview',
                            "task" => "Echange avec l'examinateur. 2 minutes.",
                            'type' => 'speaking-response', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Speaking, Part 2', 'name_fr' => 'Long turn',
                            "task" => "Parler seul une minute, puis commentaire du partenaire pendant 30 secondes.",
                            'type' => 'speaking-long-turn', 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Speaking, Part 3', 'name_fr' => 'Collaborative task',
                            "task" => "3 minutes : 2 de discussion, puis 1 pour decider ensemble.",
                            'type' => 'negotiation', 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Speaking, Part 4', 'name_fr' => 'Discussion',
                            "task" => "Discussion avec l'autre candidat. 5 minutes.",
                            'type' => 'speaking-discussion', 'source' => 'officiel'],
                    ],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Reading and Use of English, Part 1', 'name_fr' => 'Multiple-choice cloze',
                            "task" => "Choisir parmi quatre mots ou expressions pour chacun des huit trous.",
                            'type' => 'mcq-cloze', 'items' => 8, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Reading and Use of English, Part 2', 'name_fr' => 'Open cloze',
                            "task" => "Trouver le mot qui convient pour chacun des huit trous.",
                            'type' => 'open-cloze', 'items' => 8, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Reading and Use of English, Part 3', 'name_fr' => 'Word formation',
                            "task" => "Former le mot manquant a partir d'un radical, pour chacun des huit trous.",
                            'type' => 'word-formation', 'items' => 8, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Reading and Use of English, Part 4', 'name_fr' => 'Key word transformations',
                            "task" => "Reecrire en 3 a 8 mots avec un mot impose. Jusqu'a 2 points par question.",
                            'type' => 'key-word-transformation', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 5, 'name' => 'Reading and Use of English, Part 5', 'name_fr' => 'Multiple choice',
                            "task" => "Questions A-D sur un texte long. 2 points chacune.",
                            'type' => 'mcq', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 6, 'name' => 'Reading and Use of English, Part 6', 'name_fr' => 'Gapped text',
                            "task" => "Decider ou replacer les paragraphes retires. 2 points chacune.",
                            'type' => 'gapped-text', 'items' => 7, 'source' => 'officiel'],
                        ['part' => 7, 'name' => 'Reading and Use of English, Part 7', 'name_fr' => 'Multiple matching',
                            "task" => "Associer des questions aux passages d'un ou plusieurs textes.",
                            'type' => 'multiple-matching', 'items' => 10, 'source' => 'officiel'],
                    ],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Writing, Part 1', 'name_fr' => 'Essai obligatoire',
                            "task" => "Resumer et evaluer deux textes d'environ 100 mots, puis ecrire un essai. 240 a 280 mots.",
                            'type' => 'essay', 'items' => 1, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Writing, Part 2', 'name_fr' => 'Au choix',
                            "task" => "Au choix parmi trois : article, lettre, rapport, critique. 280 a 320 mots.",
                            'type' => 'article-writing', 'items' => 1, 'source' => 'officiel'],
                    ],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Listening, Part 1', 'name_fr' => 'Multiple choice',
                            "task" => "Trois enregistrements d'environ une minute, deux questions A/B/C chacun.",
                            'type' => 'mcq', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Listening, Part 2', 'name_fr' => 'Sentence completion',
                            "task" => "Completer des trous avec un a trois mots, a partir d'un monologue de 3 a 4 minutes.",
                            'type' => 'sentence-completion', 'items' => 9, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Listening, Part 3', 'name_fr' => 'Multiple choice',
                            "task" => "Questions A-D sur un echange de 3 a 4 minutes.",
                            'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Listening, Part 4', 'name_fr' => 'Multiple matching',
                            "task" => "Associer cinq monologues courts a huit options, pour deux taches.",
                            'type' => 'multiple-matching', 'items' => 10, 'source' => 'officiel'],
                    ],
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
                        ['part' => 1, 'name' => 'Speaking, Part 1', 'name_fr' => 'Interview',
                            "task" => "Chaque candidat a son tour avec l'examinateur. 2 minutes.",
                            'type' => 'speaking-response', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Speaking, Part 2', 'name_fr' => 'Collaborative task',
                            "task" => "4 minutes : une minute sur une image, puis une tache de decision commune.",
                            'type' => 'negotiation', 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Speaking, Part 3', 'name_fr' => 'Long turn and discussion',
                            "task" => "Environ 2 minutes d'expose chacun, puis 6 minutes de discussion. 10 minutes.",
                            'type' => 'speaking-long-turn', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['speaking-response', 'negotiation', 'speaking-long-turn', 'speaking-discussion'],
                ],
            ],
        ],
    ],
];
