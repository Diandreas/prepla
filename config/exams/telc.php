<?php

/**
 * telc Deutsch — The European Language Certificates
 * Source : telc.net, pages officielles de chaque examen, consultées le 2026-10-10.
 *
 * telc publie VINGT-QUATRE examens d'allemand. Ce fichier décrit l'échelle
 * GÉNÉRALE, celle que presque tout le monde passe : telc Deutsch A1 à C2, un
 * examen par palier. Les épreuves spécialisées — Beruf, Pflege, Medizin,
 * Hochschule, Schule, für Zuwanderer, Junior — ont chacune une structure
 * différente (le A1 für Zuwanderer, par exemple, a des Sprachbausteine que le
 * Start Deutsch 1 n'a pas). Elles seront ajoutées une par une, vérifiées de la
 * même façon, plutôt que devinées à partir de celles-ci.
 *
 * CE QUI DISTINGUE telc DES AUTRES : les SPRACHBAUSTEINE, un module de langue
 * (grammaire puis lexique) qui n'existe ni au Goethe ni à l'ÖSD. C'est la
 * signature de l'examen, et la partie que les candidats ratent le plus souvent.
 *
 * DEUX AMÉNAGEMENTS, assumés ici et signalés sur place :
 *
 * 1. telc annonce UNE durée commune pour Leseverstehen + Sprachbausteine
 *    (90 minutes du B1 au C1, le candidat répartit lui-même). L'application a
 *    besoin d'une durée par module : la répartition ci-dessous est donc un
 *    découpage de notre fait, pas un chiffre de telc. Le total, lui, est exact.
 *
 * 2. Au A1, « Lesen und Schreiben » est un bloc unique de 45 minutes ; au C2,
 *    « Hören und Schreiben » en est un de 60. Ils sont présentés ici comme deux
 *    modules — on ne sait pas s'entraîner à une compétence double — en gardant
 *    le nombre de parties officiel de chacun.
 *
 * Scoring : 300 points au total (225 à l'écrit, 75 à l'oral), réussite à 60 %,
 * barème publié par telc pour les examens généraux.
 */

return [
    'slug' => 'telc',
    'name' => 'telc Deutsch',
    'language' => 'german',

    'scoring' => [
        'type' => 'level',
        'total' => 300,
        'pass_mark' => 60,
        'section_max' => 100,
        'overall_calculation' => 'weighted',
    ],

    'variants' => null,

    'levels' => [

        // ━━━━━━━━━━ A1 : Start Deutsch 1 / telc Deutsch A1 ━━━━━━━━━━
        // Source : telc.net, page « Start German1 / telc German A1 ».
        // Hören 3 parties, env. 20 min · Lesen und Schreiben 3 + 2 parties, 45 min
        // · Sprechen 3 parties, env. 15 min, en groupe de 4, sans préparation.
        'A1' => [
            'name' => 'Start Deutsch 1 / telc Deutsch A1',
            'total_duration' => 80,
            'sections' => [
                [
                    'slug' => 'hoeren',
                    'name' => 'Hören (Compréhension orale)',
                    'skill_type' => 'listening',
                    'task_count' => 3,
                    'time_limit' => 20,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '3 parties : annonces courtes, messages sur répondeur, conversations du quotidien.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören, Teil 1', 'name_fr' => 'Conversations du quotidien',
                            'task' => "De courts échanges entendus : juger chaque affirmation vraie ou fausse.",
                            'type' => 'true-false', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Hören, Teil 2', 'name_fr' => 'Annonces publiques',
                            'task' => "Des annonces courtes : choisir la bonne réponse.",
                            'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Hören, Teil 3', 'name_fr' => 'Messages sur répondeur',
                            'task' => "Des messages téléphoniques : associer chaque message à sa situation.",
                            'type' => 'matching', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['true-false', 'mcq', 'matching'],
                ],
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'task_count' => 3,
                    // Part du bloc « Lesen und Schreiben » de 45 minutes. Découpage de notre fait.
                    'time_limit' => 25,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '3 parties : courts messages, petites annonces, panneaux et consignes.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Teil 1', 'name_fr' => 'Textes courts',
                            'task' => "De courts messages écrits : juger chaque affirmation vraie ou fausse.",
                            'type' => 'true-false', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Lesen, Teil 2', 'name_fr' => 'Petites annonces',
                            'task' => "Associer chaque situation à la petite annonce qui y répond.",
                            'type' => 'matching', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Lesen, Teil 3', 'name_fr' => 'Panneaux et consignes',
                            'task' => "Des panneaux et consignes du quotidien : choisir la bonne interprétation.",
                            'type' => 'mcq', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['true-false', 'matching', 'mcq'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'task_count' => 2,
                    // Part du même bloc de 45 minutes. Découpage de notre fait.
                    'time_limit' => 20,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '2 parties : remplir un formulaire, puis écrire une courte note d\'une trentaine de mots.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen und Schreiben, Teil 1', 'name_fr' => 'Remplir un formulaire',
                            'task' => "Compléter un formulaire simple avec les informations demandées.",
                            'type' => 'form-completion', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Lesen und Schreiben, Teil 2', 'name_fr' => 'Message court',
                            'task' => "Rédiger un message très court en traitant les points imposés.",
                            'type' => 'short-writing', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['form-completion', 'short-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    'task_count' => 3,
                    'time_limit' => 15,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '3 parties : se présenter, demander et donner une information, formuler une demande et y réagir. À l\'examen, en groupe de quatre candidats, sans temps de préparation ; ici tu t\'entraînes seul, au micro.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Teil 1', 'name_fr' => 'Se présenter',
                            'task' => "Se présenter : nom, âge, pays, langues, profession.",
                            'type' => 'speaking-response', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Sprechen, Teil 2', 'name_fr' => 'Demander une information',
                            'task' => "Poser des questions simples à partir de mots-clés.",
                            'type' => 'role-play', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Sprechen, Teil 3', 'name_fr' => 'Demander et réagir',
                            'task' => "Formuler une demande et répondre à celle de son partenaire.",
                            'type' => 'role-play', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['speaking-response', 'role-play'],
                ],
            ],
        ],

        // ━━━━━━━━━━ A2 : Start Deutsch 2 / telc Deutsch A2 ━━━━━━━━━━
        // Source : telc.net, page « Start German 2 / telc German A2 ».
        // Hören 3 parties, env. 20 min · Lesen 3 parties, 50 min · Schreiben
        // 2 parties (durée non publiée) · Sprechen 3 parties, env. 15 min, en binôme.
        'A2' => [
            'name' => 'Start Deutsch 2 / telc Deutsch A2',
            'total_duration' => 115,
            'sections' => [
                [
                    'slug' => 'hoeren',
                    'name' => 'Hören (Compréhension orale)',
                    'skill_type' => 'listening',
                    'task_count' => 3,
                    'time_limit' => 20,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '3 parties : compréhension globale, compréhension détaillée, compréhension sélective.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören, Teil 1', 'name_fr' => 'Conversations du quotidien',
                            'task' => "De courts échanges entendus : juger chaque affirmation vraie ou fausse.",
                            'type' => 'true-false', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Hören, Teil 2', 'name_fr' => 'Annonces et informations',
                            'task' => "Des annonces ou informations entendues : choisir la bonne réponse.",
                            'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Hören, Teil 3', 'name_fr' => 'Messages',
                            'task' => "Des messages courts : associer chaque message à sa situation.",
                            'type' => 'matching', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['true-false', 'mcq', 'matching'],
                ],
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'task_count' => 3,
                    'time_limit' => 50,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '3 parties : courriels et messages, petites annonces à associer à des situations, textes informatifs courts.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Teil 1', 'name_fr' => 'Compréhension globale',
                            'task' => "Associer à chaque texte le titre qui lui correspond.",
                            'type' => 'matching-headings', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Lesen, Teil 2', 'name_fr' => 'Compréhension sélective',
                            'task' => "Associer chaque situation à l'annonce qui y répond.",
                            'type' => 'matching', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Lesen, Teil 3', 'name_fr' => 'Compréhension détaillée',
                            'task' => "Un texte, puis des questions à choix multiple.",
                            'type' => 'mcq', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['matching-headings', 'matching', 'mcq', 'true-false'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'task_count' => 2,
                    // telc ne publie pas de durée pour ce module au A2 ; 30 minutes
                    // est notre estimation, alignée sur le B1.
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '2 parties : un formulaire, puis un message personnel court.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Teil 1', 'name_fr' => 'Remplir un formulaire',
                            'task' => "Compléter un formulaire avec les informations demandées.",
                            'type' => 'form-completion', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Schreiben, Teil 2', 'name_fr' => 'Message ou courriel court',
                            'task' => "Rédiger un message court en traitant les points imposés.",
                            'type' => 'short-writing', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['form-completion', 'short-writing', 'letter-email-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    'task_count' => 3,
                    'time_limit' => 15,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '3 parties : se présenter, parler d\'un sujet du quotidien, organiser quelque chose ensemble. En binôme à l\'examen, sans temps de préparation.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Teil 1', 'name_fr' => 'Se présenter',
                            'task' => "Se présenter et répondre aux questions de l'examinateur.",
                            'type' => 'speaking-response', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Sprechen, Teil 2', 'name_fr' => 'Parler d\'un theme',
                            'task' => "Échanger sur un thème du quotidien à partir d'un support.",
                            'type' => 'speaking-discussion', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Sprechen, Teil 3', 'name_fr' => 'Se mettre d\'accord',
                            'task' => "Organiser quelque chose avec son partenaire et se mettre d'accord.",
                            'type' => 'negotiation', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['speaking-response', 'speaking-discussion', 'negotiation'],
                ],
            ],
        ],

        // ━━━━━━━━━━ B1 : Zertifikat Deutsch / telc Deutsch B1 ━━━━━━━━━━
        // Source : telc.net, page « Certificate German / telc German B1 ».
        // Leseverstehen 3 parties + Sprachbausteine 2 parties, 90 min ensemble ·
        // Hörverstehen 3 parties, env. 30 min · Schriftlicher Ausdruck 1 partie,
        // 30 min · Mündlicher Ausdruck 3 parties, env. 15 min, en binôme, avec
        // 20 minutes de préparation.
        'B1' => [
            'name' => 'Zertifikat Deutsch / telc Deutsch B1',
            'total_duration' => 150,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Leseverstehen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'task_count' => 3,
                    // 60 + 30 = les 90 minutes communes avec les Sprachbausteine.
                    'time_limit' => 60,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '3 parties : compréhension globale (associer des titres à des textes), compréhension détaillée (QCM), compréhension sélective (associer des situations à des annonces).',
                    'parts' => [
                        ['part' => 1, 'name' => 'Leseverstehen, Teil 1', 'name_fr' => 'Compréhension globale',
                            'task' => "Associer à chacun des textes le bon titre, parmi une liste de titres.",
                            'type' => 'matching-headings', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Leseverstehen, Teil 2', 'name_fr' => 'Compréhension détaillée',
                            'task' => "Un texte long, puis des questions à choix multiple.",
                            'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Leseverstehen, Teil 3', 'name_fr' => 'Compréhension sélective',
                            'task' => "Associer chaque situation à l'annonce qui y répond.",
                            'type' => 'matching', 'items' => 10, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['matching-headings', 'mcq', 'matching'],
                ],
                [
                    'slug' => 'sprachbausteine',
                    'name' => 'Sprachbausteine (Structures de la langue)',
                    'skill_type' => 'grammar',
                    'task_count' => 2,
                    'time_limit' => 30,
                    'scoring_weight' => 15,
                    'max_score' => 100,
                    'description' => '2 parties : un texte à trous avec trois choix par trou (grammaire), puis un texte à compléter à partir d\'une liste de mots (lexique). C\'est le module propre à telc.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprachbausteine, Teil 1', 'name_fr' => 'Grammaire',
                            'task' => "Une lettre ou un courriel à trous : choisir le bon mot parmi trois, pour chaque trou.",
                            'type' => 'mcq-cloze', 'items' => 10, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Sprachbausteine, Teil 2', 'name_fr' => 'Lexique',
                            'task' => "Un texte à trous : replacer les mots en les choisissant dans une liste.",
                            'type' => 'summary-completion', 'items' => 10, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['mcq-cloze', 'gap-fill', 'grammar-mcq', 'summary-completion'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hörverstehen (Compréhension orale)',
                    'skill_type' => 'listening',
                    'task_count' => 3,
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '3 parties : compréhension globale, compréhension détaillée, compréhension sélective — en vrai ou faux.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hörverstehen, Teil 1', 'name_fr' => 'Compréhension globale',
                            'task' => "De courts textes entendus : juger chaque affirmation vraie ou fausse.",
                            'type' => 'true-false', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Hörverstehen, Teil 2', 'name_fr' => 'Compréhension détaillée',
                            'task' => "Un entretien ou une conversation : juger chaque affirmation vraie ou fausse.",
                            'type' => 'true-false', 'items' => 10, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Hörverstehen, Teil 3', 'name_fr' => 'Compréhension sélective',
                            'task' => "Des annonces ou messages courts : juger chaque affirmation vraie ou fausse.",
                            'type' => 'true-false', 'items' => 5, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['true-false', 'mcq', 'matching'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schriftlicher Ausdruck (Expression écrite)',
                    'skill_type' => 'writing',
                    'task_count' => 1,
                    'time_limit' => 30,
                    'scoring_weight' => 15,
                    'max_score' => 100,
                    'description' => '1 tâche : une lettre ou un courriel semi-formel, en traitant les quatre points imposés.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schriftlicher Ausdruck', 'name_fr' => 'Lettre ou courriel',
                            'task' => "Rédiger une lettre ou un courriel semi-formel en traitant les quatre points imposés.",
                            'type' => 'letter-email-writing', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['letter-email-writing', 'letter-writing', 'short-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Mündlicher Ausdruck (Expression orale)',
                    'skill_type' => 'speaking',
                    'task_count' => 3,
                    'time_limit' => 15,
                    'scoring_weight' => 20,
                    'max_score' => 100,
                    'description' => '3 parties : prise de contact, conversation sur un thème, résoudre une tâche ensemble. En binôme à l\'examen, avec 20 minutes de préparation ; ici tu t\'entraînes seul, au micro.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Teil 1', 'name_fr' => 'Kontaktaufnahme',
                            'task' => "Faire connaissance avec son partenaire.",
                            'type' => 'speaking-response', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Sprechen, Teil 2', 'name_fr' => 'Gespräch über ein Thema',
                            'task' => "Échanger sur un thème à partir d'un document.",
                            'type' => 'speaking-discussion', 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Sprechen, Teil 3', 'name_fr' => 'Gemeinsam eine Aufgabe lösen',
                            'task' => "Organiser quelque chose ensemble et se mettre d'accord.",
                            'type' => 'negotiation', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['speaking-response', 'speaking-discussion', 'negotiation'],
                ],
            ],
        ],

        // ━━━━━━━━━━ B2 : telc Deutsch B2 ━━━━━━━━━━
        // Source : telc.net, page « telc German B2 », et handbook telc Deutsch B2.
        // Leseverstehen 3 parties (5 associations, 5 QCM, 10 associations) +
        // Sprachbausteine 2 parties (10 QCM de grammaire, 10 associations de
        // lexique), 90 min ensemble · Hörverstehen 3 parties (5, 10, 5), env.
        // 20 min · Schriftlicher Ausdruck 1 partie, 30 min · Mündlicher Ausdruck
        // 3 parties, env. 15 min, en binôme, 20 minutes de préparation.
        'B2' => [
            'name' => 'telc Deutsch B2',
            'total_duration' => 155,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Leseverstehen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'task_count' => 3,
                    'time_limit' => 60,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '3 parties : 5 associations (compréhension globale), 5 QCM (compréhension détaillée), 10 associations (compréhension sélective).',
                    'parts' => [
                        ['part' => 1, 'name' => 'Leseverstehen, Teil 1', 'name_fr' => 'Compréhension globale',
                            'task' => 'Associer à chacun des 5 textes le bon titre, parmi 10 titres proposés.',
                            'type' => 'matching-headings', 'items' => 5, 'item_range' => '1-5', 'points' => 25, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Leseverstehen, Teil 2', 'name_fr' => 'Compréhension détaillée',
                            'task' => 'Un texte long, puis 5 questions à choix multiple avec trois options.',
                            'type' => 'mcq', 'items' => 5, 'item_range' => '6-10', 'points' => 25, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Leseverstehen, Teil 3', 'name_fr' => 'Compréhension sélective',
                            'task' => 'Associer 10 situations aux 12 annonces qui y répondent. Deux annonces ne servent pas.',
                            'type' => 'matching', 'items' => 10, 'item_range' => '11-20', 'points' => 25, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['matching-headings', 'mcq', 'matching'],
                ],
                [
                    'slug' => 'sprachbausteine',
                    'name' => 'Sprachbausteine (Structures de la langue)',
                    'skill_type' => 'grammar',
                    'task_count' => 2,
                    'time_limit' => 30,
                    'scoring_weight' => 15,
                    'max_score' => 100,
                    'description' => '2 parties : 10 trous de grammaire à trois choix, puis 10 mots à replacer depuis une liste. Le module propre à telc.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprachbausteine, Teil 1', 'name_fr' => 'Grammaire',
                            'task' => 'Une lettre ou un courriel à trous : choisir le bon mot parmi trois, pour chacun des 10 trous.',
                            'type' => 'mcq-cloze', 'items' => 10, 'item_range' => '21-30', 'points' => 15, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Sprachbausteine, Teil 2', 'name_fr' => 'Lexique',
                            'task' => 'Un article à trous : replacer 10 mots en les choisissant dans une liste de 15.',
                            'type' => 'summary-completion', 'items' => 10, 'item_range' => '31-40', 'points' => 15, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['mcq-cloze', 'gap-fill', 'grammar-mcq', 'summary-completion'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hörverstehen (Compréhension orale)',
                    'skill_type' => 'listening',
                    'task_count' => 3,
                    'time_limit' => 20,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '3 parties : 5, 10 puis 5 affirmations à juger vraies ou fausses.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hörverstehen, Teil 1', 'name_fr' => 'Compréhension globale',
                            'task' => 'Un journal radio de six nouvelles : juger 5 phrases vraies ou fausses.',
                            'type' => 'true-false', 'items' => 5, 'item_range' => '41-45', 'points' => 25, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Hörverstehen, Teil 2', 'name_fr' => 'Compréhension détaillée',
                            'task' => 'Une interview ou un entretien radio : juger 10 affirmations vraies ou fausses.',
                            'type' => 'true-false', 'items' => 10, 'item_range' => '46-55', 'points' => 25, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Hörverstehen, Teil 3', 'name_fr' => 'Compréhension sélective',
                            'task' => 'Cinq annonces ou messages vocaux courts : juger 5 affirmations vraies ou fausses.',
                            'type' => 'true-false', 'items' => 5, 'item_range' => '56-60', 'points' => 25, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['true-false', 'mcq', 'matching'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schriftlicher Ausdruck (Expression écrite)',
                    'skill_type' => 'writing',
                    'task_count' => 1,
                    'time_limit' => 30,
                    'scoring_weight' => 15,
                    'max_score' => 100,
                    'description' => '1 tâche : un courriel semi-formel, à partir d\'une situation et de points imposés.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schriftlicher Ausdruck', 'name_fr' => 'Courriel semi-formel',
                            'task' => "Répondre à une annonce par un courriel semi-formel, en traitant les quatre points imposés. 150 mots au minimum. Un sujet à choisir entre deux.",
                            'type' => 'letter-email-writing', 'items' => 1, 'points' => 45, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['letter-email-writing', 'essay', 'short-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Mündlicher Ausdruck (Expression orale)',
                    'skill_type' => 'speaking',
                    'task_count' => 3,
                    'time_limit' => 15,
                    'scoring_weight' => 20,
                    'max_score' => 100,
                    'description' => '3 parties : raconter une expérience, discuter d\'un sujet, planifier quelque chose ensemble. En binôme, avec 20 minutes de préparation.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Teil 1 - Über Erfahrungen sprechen', 'name_fr' => 'Parler de son expérience',
                            'task' => "Un thème choisi dans une liste de sept, préparé à l'avance : en parler, puis répondre aux questions. Environ 5 minutes.",
                            'type' => 'speaking-long-turn', 'items' => 1, 'points' => 25, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Sprechen, Teil 2 - Diskussion', 'name_fr' => 'Discussion',
                            'task' => "Restituer les idées principales d'un article de presse, puis en débattre. Environ 5 minutes.",
                            'type' => 'speaking-discussion', 'items' => 1, 'points' => 25, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Sprechen, Teil 3 - Gemeinsam etwas planen', 'name_fr' => 'Planifier ensemble',
                            'task' => "Faire des propositions, réagir à celles du partenaire et aboutir à un programme commun. Environ 5 minutes.",
                            'type' => 'negotiation', 'items' => 1, 'points' => 25, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['speaking-long-turn', 'speaking-discussion', 'negotiation'],
                ],
            ],
        ],

        // ━━━━━━━━━━ C1 : telc Deutsch C1 ━━━━━━━━━━
        // Source : telc.net, page « telc German C1 ».
        // Leseverstehen 3 parties, 90 min · Sprachbausteine 1 partie (durée non
        // publiée séparément) · Hörverstehen 3 parties, env. 40 min ·
        // Schriftlicher Ausdruck 1 partie, 70 min · Mündlicher Ausdruck
        // 3 parties, env. 16 min, 20 minutes de préparation.
        'C1' => [
            'name' => 'telc Deutsch C1',
            'total_duration' => 216,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Leseverstehen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'task_count' => 3,
                    // 70 + 20 = les 90 minutes communes avec les Sprachbausteine.
                    'time_limit' => 70,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '3 parties : textes longs et argumentatifs, QCM et associations.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Leseverstehen, Teil 1', 'name_fr' => 'Compréhension globale',
                            'task' => "Associer à chacun des textes le titre qui lui correspond.",
                            'type' => 'matching-headings', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Leseverstehen, Teil 2', 'name_fr' => 'Compréhension détaillée',
                            'task' => "Un texte long et exigeant, puis des questions à choix multiple.",
                            'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Leseverstehen, Teil 3', 'name_fr' => 'Texte lacunaire',
                            'task' => "Replacer dans un texte les paragraphes ou phrases qui en ont été retirés.",
                            'type' => 'gapped-text', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['matching-headings', 'mcq', 'matching', 'gapped-text'],
                ],
                [
                    'slug' => 'sprachbausteine',
                    'name' => 'Sprachbausteine (Structures de la langue)',
                    'skill_type' => 'grammar',
                    'task_count' => 1,
                    'time_limit' => 20,
                    'scoring_weight' => 15,
                    'max_score' => 100,
                    'description' => '1 partie : un texte à trous, QCM ou association, sur la grammaire et la lexique du niveau.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprachbausteine', 'name_fr' => 'Structures de la langue',
                            'task' => "Un texte à trous à compléter sans proposition : un mot par trou.",
                            'type' => 'open-cloze', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq-cloze', 'open-cloze', 'word-formation'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hörverstehen (Compréhension orale)',
                    'skill_type' => 'listening',
                    'task_count' => 3,
                    'time_limit' => 40,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '3 parties : exposés, entretiens et discussions, en QCM ou en associations.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hörverstehen, Teil 1', 'name_fr' => 'Compréhension globale',
                            'task' => "De courts textes entendus : juger chaque affirmation vraie ou fausse.",
                            'type' => 'true-false', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Hörverstehen, Teil 2', 'name_fr' => 'Compréhension détaillée',
                            'task' => "Un entretien long : questions à choix multiple.",
                            'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Hörverstehen, Teil 3', 'name_fr' => 'Prise de notes',
                            'task' => "Compléter des notes à partir d'un exposé entendu.",
                            'type' => 'note-completion', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'matching', 'true-false', 'note-completion'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schriftlicher Ausdruck (Expression écrite)',
                    'skill_type' => 'writing',
                    'task_count' => 1,
                    'time_limit' => 70,
                    'scoring_weight' => 15,
                    'max_score' => 100,
                    'description' => '1 tâche longue : réagir par écrit à un document, en développant une position argumentée.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schriftlicher Ausdruck', 'name_fr' => 'Texte argumenté',
                            'task' => "Rédiger un texte structuré et argumenté à partir d'un document source.",
                            'type' => 'integrated-writing', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['essay', 'integrated-writing', 'report-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Mündlicher Ausdruck (Expression orale)',
                    'skill_type' => 'speaking',
                    'task_count' => 3,
                    'time_limit' => 16,
                    'scoring_weight' => 20,
                    'max_score' => 100,
                    'description' => '3 parties : présentation, discussion, tâche commune. Généralement à deux candidats, avec 20 minutes de préparation.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Teil 1', 'name_fr' => 'Exposé',
                            'task' => "Présenter un thème préparé, puis répondre aux questions.",
                            'type' => 'speaking-long-turn', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Sprechen, Teil 2', 'name_fr' => 'Discussion',
                            'task' => "Discuter d'un sujet à partir d'un document.",
                            'type' => 'speaking-discussion', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Sprechen, Teil 3', 'name_fr' => 'Planifier ensemble',
                            'task' => "Aboutir à un accord avec son partenaire.",
                            'type' => 'negotiation', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['speaking-long-turn', 'speaking-discussion', 'negotiation'],
                ],
            ],
        ],

        // ━━━━━━━━━━ C2 : telc Deutsch C2 ━━━━━━━━━━
        // Source : telc.net, page « telc German C2 ».
        // Lesen 3 parties, 80 min · Hören und Schreiben 1 partie, 60 min ·
        // Schreiben 1 partie, 90 min · Sprechen 2 parties, env. 15 min,
        // 20 minutes de préparation.
        //
        // Le C2 n'a PAS de Sprachbausteine, et il a un module double « écouter
        // puis écrire » qu'aucun autre niveau ne comporte. C'est voulu : à ce
        // niveau, telc ne teste plus la langue isolément.
        'C2' => [
            'name' => 'telc Deutsch C2',
            'total_duration' => 245,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'task_count' => 3,
                    'time_limit' => 80,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '3 parties : textes exigeants, littéraires ou spécialisés, en QCM et en associations.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Leseverstehen, Teil 1', 'name_fr' => 'Compréhension détaillée',
                            'task' => "Un texte exigeant, puis des questions à choix multiple.",
                            'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Leseverstehen, Teil 2', 'name_fr' => 'Compréhension sélective',
                            'task' => "Associer des informations aux textes qui les contiennent.",
                            'type' => 'matching', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Leseverstehen, Teil 3', 'name_fr' => 'Texte lacunaire',
                            'task' => "Replacer dans un texte les passages qui en ont été retirés.",
                            'type' => 'gapped-text', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'matching', 'matching-headings', 'gapped-text'],
                ],
                [
                    'slug' => 'hoeren-schreiben',
                    'name' => 'Hören und Schreiben (Écouter puis écrire)',
                    'skill_type' => 'listening',
                    'task_count' => 1,
                    'time_limit' => 60,
                    'scoring_weight' => 25,
                    'max_score' => 100,
                    'description' => '1 tâche double, propre au C2 : écouter un document, puis en rendre compte par écrit.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören und Schreiben', 'name_fr' => 'Écouter puis rédiger',
                            'task' => "Rédiger un texte à partir d'un document entendu. À l'examen, ce module est un bloc unique ; ici on s'y entraîne séparément.",
                            'type' => 'integrated-writing', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['integrated-writing', 'note-completion'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'task_count' => 1,
                    'time_limit' => 90,
                    'scoring_weight' => 30,
                    'max_score' => 100,
                    'description' => '1 tâche longue : un texte construit et nuancé, à partir d\'un document de départ.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schriftlicher Ausdruck', 'name_fr' => 'Texte argumenté',
                            'task' => "Rédiger un texte long, structuré et nuancé, à partir d'un document source.",
                            'type' => 'integrated-writing', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['essay', 'integrated-writing', 'report-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    'task_count' => 2,
                    'time_limit' => 15,
                    'scoring_weight' => 20,
                    'max_score' => 100,
                    'description' => '2 parties seulement à ce niveau : un exposé construit, puis un échange approfondi. 20 minutes de préparation.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Teil 1', 'name_fr' => 'Exposé',
                            'task' => "Présenter un thème préparé de manière structurée.",
                            'type' => 'speaking-long-turn', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Sprechen, Teil 2', 'name_fr' => 'Débat',
                            'task' => "Défendre un point de vue face à son interlocuteur.",
                            'type' => 'oral-debate', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['speaking-long-turn', 'speaking-discussion', 'oral-debate'],
                ],
            ],
        ],
    ],
];
