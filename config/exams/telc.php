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
                        ['name' => 'Formular', 'description' => 'Compléter un formulaire simple'],
                        ['name' => 'Kurze Mitteilung', 'description' => 'Écrire une note courte, avec une formule d\'appel et une salutation'],
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
                        ['name' => 'Sich vorstellen', 'description' => 'Se présenter : nom, âge, pays, lieu de résidence, langues'],
                        ['name' => 'Informationen erfragen', 'description' => 'Demander et donner une information à partir d\'une carte-mot'],
                        ['name' => 'Bitten formulieren', 'description' => 'Formuler une demande à partir d\'une carte-image, et réagir à celle du partenaire'],
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
                        ['name' => 'Teil 1 — Grammatik', 'description' => 'Texte à trous, trois propositions par trou'],
                        ['name' => 'Teil 2 — Wortschatz', 'description' => 'Texte à compléter avec les mots d\'une liste'],
                    ],
                    'exercise_types' => ['mcq-cloze', 'gap-fill', 'grammar-mcq'],
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
                        ['name' => 'Kontaktaufnahme', 'description' => 'Faire connaissance avec son partenaire'],
                        ['name' => 'Gespräch über ein Thema', 'description' => 'Échanger sur un thème à partir d\'un document'],
                        ['name' => 'Gemeinsam eine Aufgabe lösen', 'description' => 'Organiser quelque chose ensemble et se mettre d\'accord'],
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
                    'exercise_types' => ['mcq-cloze', 'gap-fill', 'grammar-mcq'],
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
                        ['name' => 'Erfahrungen', 'description' => 'Raconter une expérience personnelle liée au thème'],
                        ['name' => 'Diskussion', 'description' => 'Discuter d\'un sujet à partir d\'un document'],
                        ['name' => 'Gemeinsam planen', 'description' => 'Planifier quelque chose avec son partenaire'],
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
                        ['name' => 'Vortrag', 'description' => 'Exposé structuré sur un sujet exigeant'],
                        ['name' => 'Diskussion', 'description' => 'Échange argumenté avec l\'examinateur ou le partenaire'],
                    ],
                    'exercise_types' => ['speaking-long-turn', 'speaking-discussion', 'oral-debate'],
                ],
            ],
        ],
    ],
];
