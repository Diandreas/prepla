<?php

/**
 * ÖSD — Österreichisches Sprachdiplom Deutsch
 * Source : osd.at (page officielle ÖSD Zertifikat B2, consultée le 2026-10-09)
 *
 * Les six niveaux, A1 à C2, sont décrits et vérifiés sur les pages officielles
 * et les Durchführungsbestimmungen de l'ÖSD. Chaque niveau porte sa source et sa
 * date de vérification au-dessus de son bloc. Tous décrivent la variante
 * GÉNÉRALE : ni les variantes Jugendliche (/J), ni PMB, ni l'ÖSD KID destiné aux
 * enfants, ni le ZC2 / Wirtschaftssprache Deutsch, qui sont d'autres examens.
 *
 * Le module Sprechen compte TROIS tâches, et c'est la deuxième — la Bildbesprechung —
 * qui demande de décrire et commenter une image. C'est une tâche de B2 : elle n'a pas
 * à être proposée à un débutant (voir App\Services\Content\ExerciseTypeSuitability).
 *
 * Scoring : le barème ci-dessous, 100 points et 60 % de seuil, est celui du B1.
 * Il ne vaut pas aux autres niveaux, et le fichier ne permet pas de le surcharger
 * par niveau. Les vrais barèmes sont portés par les `max_score` de chaque section
 * et rappelés dans les commentaires de niveau :
 *  - A1 : Lesen 30, Hören 30, Schreiben 15, oral 25 ; seuils 38/75 et 12/25 ;
 *  - A2 : Lesen 25, Hören 30, Schreiben 15, oral 20 ; seuils 35/70 et 10/20 ;
 *  - B1 : 100 points par module, 60 % chacun ;
 *  - B2 et C1 : Lesen 20, Hören 20, Schreiben 30, Sprechen 30 ; seuils 42/70 à
 *    l'écrit et 18/30 à l'oral ;
 *  - C2 : 40 points par module, 24/40 chacun.
 *
 * La modularité non plus n'est pas uniforme : seuls le B1 et le C2 se passent
 * vraiment module par module. Du A1 au C1, l'examen n'a que DEUX modules, un
 * écrit qui réunit Lesen, Hören et Schreiben, et un oral. Les quatre sections
 * servent ici à s'entraîner compétence par compétence — on ne sait pas
 * s'entraîner à un module triple — mais elles ne se valident pas séparément.
 */

return [
    'slug' => 'osd',
    'name' => 'ÖSD Zertifikat',
    'language' => 'german',

    'scoring' => [
        'type' => 'level',
        'total' => 100,
        'pass_mark' => 60,
        'section_max' => 100,
        'overall_calculation' => 'per_module',
    ],

    'variants' => null,

    'levels' => [
        // ━━━━━━━━━━ A1 : ÖSD Zertifikat A1 ━━━━━━━━━━
        // Source : osd.at, Durchführungsbestimmungen ZA1 (10/2023), vérifiées le
        // 2026-10-10. Variante générale adulte, à partir de 14 ans — à ne pas
        // confondre avec l'ÖSD KID A1, destiné aux enfants.
        // Barème officiel : Lesen 30, Hören 30, Schreiben 15 (écrit 75, seuil
        // 38/75 avec un minimum de 6/30 en Lesen comme en Hören), oral 25
        // points, seuil 12/25.
        'A1' => [
            'name' => 'ÖSD Zertifikat A1',
            // 25 + 10 + 20 + 10 = 65 minutes
            'total_duration' => 65,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'task_count' => 3,
                    'time_limit' => 25,
                    'scoring_weight' => 100,
                    'max_score' => 30,
                    'description' => '3 tâches sur des textes courts authentiques d\'Autriche, d\'Allemagne et de Suisse : deux associations et un Ja/Nein.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Aufgabe 1', 'name_fr' => 'Associer des annonces',
                            'task' => "Cinq situations de A à E et six annonces : associer chacune à celle qui y répond.",
                            'type' => 'matching', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Lesen, Aufgabe 2', 'name_fr' => 'Ja ou Nein',
                            'task' => "Trois annonces, deux questions chacune : répondre par oui ou non.",
                            'type' => 'true-false', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Lesen, Aufgabe 3', 'name_fr' => 'Panneaux et images',
                            'task' => "Cinq textes courts de A à E et six images : associer chaque texte à son image.",
                            'type' => 'matching', 'items' => 5, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['matching', 'true-false'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hören (Compréhension orale)',
                    'skill_type' => 'listening',
                    'task_count' => 3,
                    'time_limit' => 10,
                    'scoring_weight' => 100,
                    'max_score' => 30,
                    'description' => '3 tâches : associer des documents entendus à des photos, compléter une prise de notes, remplir une grille.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören, Aufgabe 1', 'name_fr' => 'Associer des photos',
                            'task' => "Cinq documents entendus et six photos : associer chacun à sa photo.",
                            'type' => 'matching', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Hören, Aufgabe 2', 'name_fr' => 'Prise de notes',
                            'task' => "Compléter cinq informations manquantes dans une prise de notes. Le document est entendu deux fois.",
                            'type' => 'note-completion', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Hören, Aufgabe 3', 'name_fr' => 'Grille à cocher',
                            'task' => "Cinq personnes interrogées : cocher dans la grille la réponse de chacune, une seule par personne.",
                            'type' => 'matching', 'items' => 5, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['matching', 'note-completion'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'task_count' => 2,
                    'time_limit' => 20,
                    'scoring_weight' => 100,
                    'max_score' => 15,
                    'description' => '2 tâches : remplir un formulaire, puis écrire un message très court.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Aufgabe 1', 'name_fr' => 'Formulaire',
                            'task' => "Compléter un formulaire : dix informations à trouver, cinq points.",
                            'type' => 'form-completion', 'items' => 10, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Schreiben, Aufgabe 2', 'name_fr' => 'Message court',
                            'task' => "Écrire un message très court en traitant les points imposés.",
                            'type' => 'short-writing', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['form-completion', 'short-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    'task_count' => 3,
                    'time_limit' => 10,
                    'scoring_weight' => 100,
                    'max_score' => 25,
                    'description' => '3 tâches en épreuve individuelle : se présenter, décrire une image, jouer une situation.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Aufgabe 1', 'name_fr' => 'Se présenter',
                            'task' => "Parler de soi librement : nom, origine, famille, travail, loisirs.",
                            'type' => 'speaking-response', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Sprechen, Aufgabe 2', 'name_fr' => 'Décrire une image',
                            'task' => "Décrire une image en suivant les questions qui la guident.",
                            'type' => 'picture-description', 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Sprechen, Aufgabe 3', 'name_fr' => 'Jeu de rôle',
                            'task' => "Jouer une situation du quotidien avec l'examinateur : demander, répondre, réagir.",
                            'type' => 'role-play', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['speaking-response', 'picture-description', 'role-play'],
                ],
            ],
        ],

        // ━━━━━━━━━━ A2 : ÖSD Zertifikat A2 ━━━━━━━━━━
        // Source : osd.at, Durchführungsbestimmungen ZA2, vérifiées le 2026-10-10.
        // Variante générale. Barème officiel : Lesen 25, Hören 30, Schreiben 15
        // (écrit 70, seuil 35/70 avec un minimum de 5/25 en Lesen et 6/30 en
        // Hören), oral 20 points, seuil 10/20.
        'A2' => [
            'name' => 'ÖSD Zertifikat A2',
            // 30 + 15 + 30 + 10 = 85 minutes
            'total_duration' => 85,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'task_count' => 2,
                    'time_limit' => 30,
                    'scoring_weight' => 100,
                    'max_score' => 25,
                    'description' => '2 tâches : une association, puis un choix multiple.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Aufgabe 1', 'name_fr' => 'Associer',
                            'task' => "Cinq situations à associer aux textes ou annonces qui y répondent.",
                            'type' => 'matching', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Lesen, Aufgabe 2', 'name_fr' => 'Choix multiple',
                            'task' => "Un texte, puis cinq questions à choix multiple.",
                            'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['matching', 'mcq'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hören (Compréhension orale)',
                    'skill_type' => 'listening',
                    'task_count' => 3,
                    'time_limit' => 15,
                    'scoring_weight' => 100,
                    'max_score' => 30,
                    'description' => '3 tâches : une grille à plusieurs réponses, une prise de notes, une association.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören, Aufgabe 1', 'name_fr' => 'Grille',
                            'task' => "Une grille à cocher : plusieurs réponses justes sont possibles.",
                            'type' => 'multiple-matching', 'items' => 4, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Hören, Aufgabe 2', 'name_fr' => 'Prise de notes',
                            'task' => "Compléter cinq informations manquantes dans une prise de notes.",
                            'type' => 'note-completion', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Hören, Aufgabe 3', 'name_fr' => 'Associer',
                            'task' => "Associer cinq éléments entendus à leur contenu dans la grille.",
                            'type' => 'matching', 'items' => 5, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['multiple-matching', 'note-completion', 'matching'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'task_count' => 1,
                    'time_limit' => 30,
                    'scoring_weight' => 100,
                    'max_score' => 15,
                    'description' => '1 tâche : écrire un courriel en traitant les points imposés.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Aufgabe 1', 'name_fr' => 'Courriel',
                            'task' => "Écrire un courriel en traitant les points imposés par la consigne.",
                            'type' => 'letter-email-writing', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['letter-email-writing', 'short-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    'task_count' => 2,
                    'time_limit' => 10,
                    'scoring_weight' => 100,
                    'max_score' => 20,
                    'description' => '2 tâches, en épreuve individuelle ou à deux : parler de soi, puis organiser quelque chose ensemble.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Aufgabe 1', 'name_fr' => 'Parler de soi',
                            'task' => "Parler librement d'un thème personnel, puis répondre aux questions.",
                            'type' => 'speaking-response', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Sprechen, Aufgabe 2', 'name_fr' => 'Organiser ensemble',
                            'task' => "Organiser quelque chose avec son partenaire et se mettre d'accord.",
                            'type' => 'negotiation', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['speaking-response', 'negotiation'],
                ],
            ],
        ],

        // ━━━━━━━━━━ B1 : ÖSD Zertifikat B1 ━━━━━━━━━━
        // Source : osd.at, ÖSD Zertifikat B1 (ZB1) et ses Modellsätze, vérifiés le
        // 2026-10-10. Seul niveau, avec le C2, à être réellement modulaire :
        // « Ein Modul ist bestanden, wenn von 100 Punkten mindestens 60 Punkte
        // erreicht sind. » Les quatre modules se passent dans n'importe quelle
        // combinaison. Ne pas confondre avec l'ÖSD Zertifikat B1 / Jugendliche.
        'B1' => [
            'name' => 'ÖSD Zertifikat B1',
            // 65 + 40 + 60 + 15 = 180 minutes
            'total_duration' => 180,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'task_count' => 5,
                    'time_limit' => 65,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '5 tâches : vrai-faux, choix multiple, association, Ja/Nein, puis un dernier choix multiple.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Aufgabe 1', 'name_fr' => 'Vrai ou faux',
                            'task' => "Six affirmations sur un texte : juste ou faux.",
                            'type' => 'true-false', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Lesen, Aufgabe 2', 'name_fr' => 'Choix multiple',
                            'task' => "Six questions à choix multiple, trois options chacune.",
                            'type' => 'mcq', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Lesen, Aufgabe 3', 'name_fr' => 'Associer',
                            'task' => "Sept éléments à associer aux textes qui leur correspondent.",
                            'type' => 'matching', 'items' => 7, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Lesen, Aufgabe 4', 'name_fr' => 'Ja ou Nein',
                            'task' => "Sept questions : répondre par oui ou non.",
                            'type' => 'true-false', 'items' => 7, 'source' => 'officiel'],
                        ['part' => 5, 'name' => 'Lesen, Aufgabe 5', 'name_fr' => 'Choix multiple',
                            'task' => "Quatre dernières questions à choix multiple.",
                            'type' => 'mcq', 'items' => 4, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['true-false', 'mcq', 'matching'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hören (Compréhension orale)',
                    'skill_type' => 'listening',
                    'task_count' => 4,
                    'time_limit' => 40,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '4 tâches : vrai-faux et choix multiple en alternance, choix multiple, vrai-faux, puis « qui dit quoi ».',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören, Aufgabe 1', 'name_fr' => 'Vrai-faux et choix multiple',
                            'task' => "Dix items en alternance : une affirmation vrai-faux, puis une question à choix multiple.",
                            'type' => 'true-false', 'items' => 10, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Hören, Aufgabe 2', 'name_fr' => 'Choix multiple',
                            'task' => "Cinq questions à choix multiple, trois options chacune.",
                            'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Hören, Aufgabe 3', 'name_fr' => 'Vrai ou faux',
                            'task' => "Sept affirmations : juste ou faux.",
                            'type' => 'true-false', 'items' => 7, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Hören, Aufgabe 4', 'name_fr' => 'Qui dit quoi',
                            'task' => "Huit affirmations : dire pour chacune qui la prononce.",
                            'type' => 'matching', 'items' => 8, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['true-false', 'mcq', 'matching'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'task_count' => 3,
                    'time_limit' => 60,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '3 tâches : un message personnel, une prise de position d\'environ 80 mots, une excuse formelle.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Aufgabe 1', 'name_fr' => 'Message personnel',
                            'task' => "Écrire un message ou un courriel personnel en traitant les points imposés.",
                            'type' => 'letter-email-writing', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Schreiben, Aufgabe 2', 'name_fr' => 'Donner son avis',
                            'task' => "Donner son avis sur une question en environ 80 mots.",
                            'type' => 'essay', 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Schreiben, Aufgabe 3', 'name_fr' => 'Excuse formelle',
                            'task' => "Écrire une lettre d'excuse formelle.",
                            'type' => 'letter-writing', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['letter-email-writing', 'essay', 'letter-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    'task_count' => 3,
                    'time_limit' => 15,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '3 tâches : organiser ensemble, présenter, puis réagir à la présentation de l\'autre. Environ 15 minutes à deux, 10 en individuel.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Aufgabe 1', 'name_fr' => 'Organiser ensemble',
                            'task' => "Planifier quelque chose avec son partenaire et se mettre d'accord.",
                            'type' => 'negotiation', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Sprechen, Aufgabe 2', 'name_fr' => 'Présenter',
                            'task' => "Présenter un thème seul, de manière structurée.",
                            'type' => 'speaking-long-turn', 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Sprechen, Aufgabe 3', 'name_fr' => 'Réagir à l\'autre',
                            'task' => "Réagir à la présentation de son partenaire et lui poser des questions.",
                            'type' => 'speaking-discussion', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['negotiation', 'speaking-long-turn', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ B2 : ÖSD Zertifikat B2 ━━━━━━━━━━
        // Source : osd.at, page ÖSD Zertifikat B2 (ZB2) et Durchführungs-
        // bestimmungen, vérifiées le 2026-10-09. Variante générale : ni ZB2/J
        // ni ZB2/PMB. Barème officiel : Lesen 20, Hören 20, Schreiben 30,
        // Sprechen 30.
        // ATTENTION : à ce niveau l'ÖSD n'a que DEUX modules, la Schriftliche
        // Prüfung (Lesen + Hören + Schreiben, seuil 42/70) et la Mündliche
        // Prüfung (seuil 18/30). Les quatre sections ci-dessous servent à
        // s'entraîner compétence par compétence ; elles ne se valident pas
        // séparément à l'examen.
        'B2' => [
            'name' => 'ÖSD Zertifikat B2',
            // 90 + 30 + 90 + 20 = 230 minutes
            'total_duration' => 230,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'task_count' => 4,
                    'time_limit' => 90,
                    'scoring_weight' => 100,
                    'max_score' => 20,
                    'description' => '4 tâches : choix multiple, titres à associer, reconstruction de marge coupée, puis texte à trous.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Aufgabe 1', 'name_fr' => 'Choix multiple',
                            'task' => "Cinq questions à choix multiple : A, B ou C.",
                            'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Lesen, Aufgabe 2', 'name_fr' => 'Titres à associer',
                            'task' => "Dix titres pour cinq textes : associer le bon titre à chaque texte.",
                            'type' => 'matching-headings', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Lesen, Aufgabe 3', 'name_fr' => 'Marge coupée',
                            'task' => "La marge droite du texte a été coupée : rétablir les vingt fins de mots, trois lettres au maximum chacune.",
                            'type' => 'complete-the-words', 'items' => 20, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Lesen, Aufgabe 4', 'name_fr' => 'Texte à trous',
                            'task' => "Dix trous à combler, un mot par trou, sans proposition.",
                            'type' => 'open-cloze', 'items' => 10, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['mcq', 'matching-headings', 'complete-the-words', 'open-cloze'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hören (Compréhension orale)',
                    'skill_type' => 'listening',
                    'task_count' => 2,
                    'time_limit' => 30,
                    'scoring_weight' => 100,
                    'max_score' => 20,
                    'description' => '2 tâches : un vrai-faux, puis une grille d\'informations à remplir.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören, Aufgabe 1', 'name_fr' => 'Vrai ou faux',
                            'task' => "Dix affirmations : juste ou faux. Le document est entendu deux fois.",
                            'type' => 'true-false', 'items' => 10, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Hören, Aufgabe 2', 'name_fr' => 'Grille d\'informations',
                            'task' => "Compléter ou cocher trente informations dans une grille. Le document n'est entendu qu'une seule fois.",
                            'type' => 'table-completion', 'items' => 30, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['true-false', 'table-completion'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'task_count' => 2,
                    'time_limit' => 90,
                    'scoring_weight' => 100,
                    'max_score' => 30,
                    'description' => '2 tâches : un courriel de réclamation, puis une prise de position au choix entre deux sujets.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Aufgabe 1', 'name_fr' => 'Courriel de réclamation',
                            'task' => "Écrire un courriel de réclamation en registre formel.",
                            'type' => 'letter-email-writing', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Schreiben, Aufgabe 2', 'name_fr' => 'Prise de position',
                            'task' => "Prendre position sur l'un des deux sujets proposés.",
                            'type' => 'essay', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['letter-email-writing', 'essay'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    'task_count' => 3,
                    'time_limit' => 20,
                    'scoring_weight' => 100,
                    'max_score' => 30,
                    'description' => '3 tâches d\'environ cinq minutes : une situation à jouer, une image à commenter, puis une discussion.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Aufgabe 1', 'name_fr' => 'Situation à jouer',
                            'task' => "Mener une conversation dans une situation donnée, environ cinq minutes.",
                            'type' => 'role-play', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Sprechen, Aufgabe 2', 'name_fr' => 'Commenter une image',
                            'task' => "Choisir une image parmi trois, la décrire et la commenter.",
                            'type' => 'picture-description', 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Sprechen, Aufgabe 3', 'name_fr' => 'Discussion',
                            'task' => "Échanger des points de vue en défendant le rôle attribué, environ cinq minutes.",
                            'type' => 'speaking-discussion', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['role-play', 'picture-description', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ C1 : ÖSD Zertifikat C1 ━━━━━━━━━━
        // Source : osd.at, page ÖSD Zertifikat C1 (ZC1) et Durchführungs-
        // bestimmungen, vérifiées le 2026-10-10. Variante générale : ni ZC1/J
        // ni ZC1/PMB. Barème officiel : Lesen 20, Hören 20, Schreiben 30,
        // Sprechen 30. Comme au B2, l'examen n'a que deux modules (écrit, seuil
        // 42/70 ; oral, seuil 18/30) : les quatre sections servent à
        // s'entraîner, pas à valider séparément.
        'C1' => [
            'name' => 'ÖSD Zertifikat C1',
            // 90 + 40 + 90 + 20 = 240 minutes
            'total_duration' => 240,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'task_count' => 4,
                    'time_limit' => 90,
                    'scoring_weight' => 100,
                    'max_score' => 20,
                    'description' => '4 tâches : choix multiple, titres à associer, paragraphes à replacer, puis texte à trous.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Aufgabe 1', 'name_fr' => 'Choix multiple',
                            'task' => "Cinq questions à choix multiple : A, B ou C.",
                            'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Lesen, Aufgabe 2', 'name_fr' => 'Titres à associer',
                            'task' => "Onze titres de A à K pour cinq textes : associer le bon titre à chaque texte.",
                            'type' => 'matching-headings', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Lesen, Aufgabe 3', 'name_fr' => 'Paragraphes à replacer',
                            'task' => "Replacer dans le texte cinq paragraphes retirés, choisis parmi sept de A à G.",
                            'type' => 'gapped-text', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Lesen, Aufgabe 4', 'name_fr' => 'Texte à trous',
                            'task' => "Quinze trous à combler, un mot par trou, sans proposition.",
                            'type' => 'open-cloze', 'items' => 15, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['mcq', 'matching-headings', 'gapped-text', 'open-cloze'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hören (Compréhension orale)',
                    'skill_type' => 'listening',
                    'task_count' => 2,
                    'time_limit' => 40,
                    'scoring_weight' => 100,
                    'max_score' => 20,
                    'description' => '2 tâches : un vrai-faux sur une émission de radio, puis une prise de notes sur un exposé.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören, Aufgabe 1', 'name_fr' => 'Vrai ou faux',
                            'task' => "Dix affirmations sur une émission de radio : juste ou faux.",
                            'type' => 'true-false', 'items' => 10, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Hören, Aufgabe 2', 'name_fr' => 'Prise de notes',
                            'task' => "Compléter dix informations manquantes dans les notes d'un exposé.",
                            'type' => 'note-completion', 'items' => 10, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['true-false', 'note-completion'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'task_count' => 2,
                    'time_limit' => 90,
                    'scoring_weight' => 100,
                    'max_score' => 30,
                    'description' => '2 tâches : une réponse formelle appuyée sur des notes, puis une prise de position d\'au moins 250 mots.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Aufgabe 1', 'name_fr' => 'Réponse formelle',
                            'task' => "Répondre à un courriel en registre formel, en s'appuyant sur les notes fournies.",
                            'type' => 'letter-email-writing', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Schreiben, Aufgabe 2', 'name_fr' => 'Prise de position',
                            'task' => "Prendre position sur l'un des trois sujets proposés, en au moins 250 mots.",
                            'type' => 'essay', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['letter-email-writing', 'essay'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    'task_count' => 3,
                    'time_limit' => 20,
                    'scoring_weight' => 100,
                    'max_score' => 30,
                    'description' => '3 tâches : un appel téléphonique à mener, une argumentation, puis un exposé court suivi de questions.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Aufgabe 1', 'name_fr' => 'Appel téléphonique',
                            'task' => "Simuler un appel téléphonique et négocier une solution, environ cinq minutes.",
                            'type' => 'role-play', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Sprechen, Aufgabe 2', 'name_fr' => 'Argumenter',
                            'task' => "Discuter et défendre une position, environ cinq minutes.",
                            'type' => 'speaking-discussion', 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Sprechen, Aufgabe 3', 'name_fr' => 'Exposé court',
                            'task' => "Faire un exposé court seul, puis répondre aux questions, environ dix minutes.",
                            'type' => 'speaking-long-turn', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['role-play', 'speaking-discussion', 'speaking-long-turn'],
                ],
            ],
        ],

        // ━━━━━━━━━━ C2 : ÖSD Zertifikat C2 ━━━━━━━━━━
        // Source : osd.at, page ÖSD Zertifikat C2 (ZC2) et Durchführungs-
        // bestimmungen, vérifiées le 2026-10-10. Variante générale : PAS le
        // ZC2 / Wirtschaftssprache Deutsch, qui est un autre examen, à deux
        // modules et 160 points.
        // Le C2 est réellement modulaire : quatre modules de 40 points, chacun
        // réussi à partir de 24/40. Deux tâches n'ont pas d'équivalent exact
        // dans notre nomenclature : la correction de fautes dans un communiqué
        // de presse et la correction d'informations fausses à l'oral. Elles
        // sont encodées au plus proche et signalées dans leur consigne.
        'C2' => [
            'name' => 'ÖSD Zertifikat C2',
            // 90 + 40 + 75 + 20 = 225 minutes
            'total_duration' => 225,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'task_count' => 4,
                    'time_limit' => 90,
                    'scoring_weight' => 100,
                    'max_score' => 40,
                    'description' => '4 tâches : choix multiple, paragraphes à replacer, correction de fautes, reconstruction de marge coupée.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Aufgabe 1', 'name_fr' => 'Choix multiple',
                            'task' => "Cinq questions à choix multiple : A, B ou C.",
                            'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Lesen, Aufgabe 2', 'name_fr' => 'Paragraphes à replacer',
                            'task' => "Replacer dans le texte cinq paragraphes retirés, choisis parmi sept de A à G.",
                            'type' => 'gapped-text', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Lesen, Aufgabe 3', 'name_fr' => 'Correction de fautes',
                            'task' => "Un communiqué de presse à corriger : une faute au maximum par ligne, dix lignes fautives.",
                            'type' => 'guided-rewrite', 'items' => 10, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Lesen, Aufgabe 4', 'name_fr' => 'Marge coupée',
                            'task' => "La marge droite du texte a été coupée : rétablir les vingt fins de mots.",
                            'type' => 'complete-the-words', 'items' => 20, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['mcq', 'gapped-text', 'guided-rewrite', 'complete-the-words'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hören (Compréhension orale)',
                    'skill_type' => 'listening',
                    'task_count' => 3,
                    'time_limit' => 40,
                    'scoring_weight' => 100,
                    'max_score' => 40,
                    'description' => '3 tâches : corriger des informations fausses, un choix multiple, une prise de notes.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören, Aufgabe 1', 'name_fr' => 'Corriger des informations',
                            'task' => "Un message contient cinq informations fausses : les corriger.",
                            'type' => 'short-answer', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Hören, Aufgabe 2', 'name_fr' => 'Choix multiple',
                            'task' => "Dix questions à choix multiple sur un document en trois parties, entendu une seule fois.",
                            'type' => 'mcq', 'items' => 10, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Hören, Aufgabe 3', 'name_fr' => 'Prise de notes',
                            'task' => "Compléter dix informations dans les notes d'un exposé, entendu deux fois.",
                            'type' => 'note-completion', 'items' => 10, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['short-answer', 'mcq', 'note-completion'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'task_count' => 2,
                    'time_limit' => 75,
                    'scoring_weight' => 100,
                    'max_score' => 40,
                    'description' => '2 tâches : un texte formel à partir d\'un modèle (18 points), puis un texte au choix entre deux sujets (22 points).',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Aufgabe 1', 'name_fr' => 'Texte formel',
                            'task' => "Rédiger un texte formel ou un courriel à partir du modèle fourni. Dix-huit points.",
                            'type' => 'letter-email-writing', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Schreiben, Aufgabe 2', 'name_fr' => 'Texte au choix',
                            'task' => "Rédiger un texte du genre demandé sur l'un des deux sujets proposés. Vingt-deux points.",
                            'type' => 'essay', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['letter-email-writing', 'essay'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    'task_count' => 3,
                    'time_limit' => 20,
                    'scoring_weight' => 100,
                    'max_score' => 40,
                    'description' => '3 tâches : un entretien de conseil à mener, une présentation au choix, puis la défense de sa position.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Aufgabe 1', 'name_fr' => 'Entretien de conseil',
                            'task' => "Mener un entretien de conseil ou un appel téléphonique.",
                            'type' => 'role-play', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Sprechen, Aufgabe 2', 'name_fr' => 'Présentation',
                            'task' => "Faire une présentation sur l'un des deux thèmes proposés.",
                            'type' => 'speaking-long-turn', 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Sprechen, Aufgabe 3', 'name_fr' => 'Défendre sa position',
                            'task' => "Répondre aux questions et défendre sa propre position.",
                            'type' => 'speaking-discussion', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['role-play', 'speaking-long-turn', 'speaking-discussion'],
                ],
            ],
        ],
    ],
];
