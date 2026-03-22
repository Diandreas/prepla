<?php

/**
 * Goethe-Zertifikat - Goethe-Institut
 * Source: goethe.de
 *
 * Level-based: A1 (Start Deutsch 1) through C2 (Großes Deutsches Sprachdiplom)
 * Scoring: Points-based per module, pass mark 60% per module
 * CEFR: Direct mapping A1–C2
 */

return [
    'slug' => 'goethe',
    'name' => 'Goethe-Zertifikat',
    'language' => 'german',

    'scoring' => [
        'type' => 'level',
        'total' => 100,
        'pass_mark' => 60,
        'section_max' => 25,
        'overall_calculation' => 'sum',
    ],

    'variants' => null,

    // ─── Structure par niveau ───
    'levels' => [
        // ━━━━━━━━━━ A1: Start Deutsch 1 ━━━━━━━━━━
        'A1' => [
            'name' => 'Goethe-Zertifikat A1: Start Deutsch 1',
            'total_duration' => 65,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Reading)',
                    'skill_type' => 'reading',
                    'time_limit' => 25,
                    'scoring_weight' => 25,
                    'description' => 'Lire et comprendre des textes très courts : annonces, panneaux, formulaires simples.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hören (Listening)',
                    'skill_type' => 'listening',
                    'time_limit' => 20,
                    'scoring_weight' => 25,
                    'description' => 'Comprendre des dialogues et annonces courtes de la vie quotidienne.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Writing)',
                    'skill_type' => 'writing',
                    'time_limit' => 20,
                    'scoring_weight' => 25,
                    'description' => 'Remplir un formulaire et écrire un court message personnel.',
                    'exercise_types' => ['form-completion', 'short-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Speaking)',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'scoring_weight' => 25,
                    'description' => 'Se présenter, poser des questions simples et y répondre.',
                    'parts' => [
                        ['name' => 'Sich vorstellen', 'description' => 'Se présenter (nom, âge, pays, langue, métier)'],
                        ['name' => 'Fragen stellen/beantworten', 'description' => 'Poser et répondre à des questions sur des thèmes quotidiens'],
                        ['name' => 'Bitten formulieren', 'description' => 'Formuler et répondre à des demandes'],
                    ],
                    'exercise_types' => ['speaking-response', 'role-play'],
                ],
            ],
        ],

        // ━━━━━━━━━━ A2 ━━━━━━━━━━
        'A2' => [
            'name' => 'Goethe-Zertifikat A2',
            'total_duration' => 70,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Reading)',
                    'skill_type' => 'reading',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'description' => 'Comprendre des textes courts : annonces, emails, instructions simples.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'gap-fill'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hören (Listening)',
                    'skill_type' => 'listening',
                    'time_limit' => 20,
                    'scoring_weight' => 25,
                    'description' => 'Comprendre des annonces, conversations téléphoniques et dialogues courants.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Writing)',
                    'skill_type' => 'writing',
                    'time_limit' => 20,
                    'scoring_weight' => 25,
                    'description' => 'Écrire un message court (SMS, email, note) en réponse à une situation.',
                    'exercise_types' => ['short-writing', 'essay'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Speaking)',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'scoring_weight' => 25,
                    'description' => 'Se présenter, parler de sa vie quotidienne et planifier quelque chose ensemble.',
                    'parts' => [
                        ['name' => 'Sich vorstellen', 'description' => 'Se présenter et poser des questions'],
                        ['name' => 'Über Alltag sprechen', 'description' => 'Parler de son quotidien à partir d\'images'],
                        ['name' => 'Gemeinsam etwas planen', 'description' => 'Planifier une activité ensemble'],
                    ],
                    'exercise_types' => ['speaking-response', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ B1 ━━━━━━━━━━
        'B1' => [
            'name' => 'Goethe-Zertifikat B1',
            'total_duration' => 115,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Reading)',
                    'skill_type' => 'reading',
                    'time_limit' => 65,
                    'scoring_weight' => 25,
                    'description' => '5 parties : blog, article de presse, petites annonces, commentaires de lecteurs, règlement.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'matching-headings', 'gap-fill'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hören (Listening)',
                    'skill_type' => 'listening',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'description' => '4 parties : annonces, conversations, discussion radio, dialogue quotidien.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'note-completion'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Writing)',
                    'skill_type' => 'writing',
                    'time_limit' => 60,
                    'scoring_weight' => 25,
                    'description' => '3 tâches : email/lettre semi-formelle, prise de position, email personnel.',
                    'exercise_types' => ['essay', 'letter-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Speaking)',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'scoring_weight' => 25,
                    'description' => '3 parties : planifier ensemble, présenter un thème, discuter et négocier.',
                    'parts' => [
                        ['name' => 'Gemeinsam etwas planen', 'description' => 'Planifier quelque chose ensemble'],
                        ['name' => 'Ein Thema präsentieren', 'description' => 'Présenter un thème (expériences personnelles)'],
                        ['name' => 'Über ein Thema sprechen', 'description' => 'Réagir à la présentation du partenaire et poser des questions'],
                    ],
                    'exercise_types' => ['speaking-response', 'speaking-long-turn', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ B2 ━━━━━━━━━━
        'B2' => [
            'name' => 'Goethe-Zertifikat B2',
            'total_duration' => 170,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Reading)',
                    'skill_type' => 'reading',
                    'time_limit' => 65,
                    'scoring_weight' => 25,
                    'description' => '5 parties : article informatif, texte d\'opinion, annonces d\'emploi, commentaires, instructions.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'matching-headings', 'gap-fill', 'sentence-completion'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hören (Listening)',
                    'skill_type' => 'listening',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'description' => '4 parties : émission radio, interview, conversation informelle, conférence.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'note-completion'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Writing)',
                    'skill_type' => 'writing',
                    'time_limit' => 75,
                    'scoring_weight' => 25,
                    'description' => '2 tâches : prise de position argumentée sur un forum + message formel/semi-formel.',
                    'exercise_types' => ['essay', 'letter-writing'],
                    'rubric' => [
                        'criteria' => [
                            ['name' => 'Inhalt (Content)', 'slug' => 'content', 'max' => 25],
                            ['name' => 'Kommunikative Gestaltung', 'slug' => 'communicative-design', 'max' => 25],
                            ['name' => 'Formale Richtigkeit', 'slug' => 'formal-accuracy', 'max' => 25],
                        ],
                    ],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Speaking)',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'scoring_weight' => 25,
                    'description' => '2 parties : présentation structurée + discussion et négociation.',
                    'parts' => [
                        ['name' => 'Vortrag halten', 'description' => 'Faire une présentation structurée sur un thème donné'],
                        ['name' => 'Diskussion führen', 'description' => 'Discuter d\'un sujet en donnant des arguments pour et contre'],
                    ],
                    'exercise_types' => ['speaking-long-turn', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ C1 ━━━━━━━━━━
        'C1' => [
            'name' => 'Goethe-Zertifikat C1',
            'total_duration' => 190,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Reading)',
                    'skill_type' => 'reading',
                    'time_limit' => 70,
                    'scoring_weight' => 25,
                    'description' => 'Textes complexes : articles scientifiques, commentaires, textes littéraires, résumés.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'matching-headings', 'gap-fill', 'sentence-completion', 'short-answer'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hören (Listening)',
                    'skill_type' => 'listening',
                    'time_limit' => 40,
                    'scoring_weight' => 25,
                    'description' => 'Conférences, débats, interviews radio et conversations complexes.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'note-completion', 'sentence-completion'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Writing)',
                    'skill_type' => 'writing',
                    'time_limit' => 80,
                    'scoring_weight' => 25,
                    'description' => '2 tâches : rédaction structurée (essai argumentatif) + lettre formelle.',
                    'exercise_types' => ['essay', 'letter-writing'],
                    'rubric' => [
                        'criteria' => [
                            ['name' => 'Inhalt (Content)', 'slug' => 'content', 'max' => 25],
                            ['name' => 'Textaufbau (Structure)', 'slug' => 'text-structure', 'max' => 25],
                            ['name' => 'Ausdruck (Expression)', 'slug' => 'expression', 'max' => 25],
                            ['name' => 'Korrektheit (Accuracy)', 'slug' => 'accuracy', 'max' => 25],
                        ],
                    ],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Speaking)',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'scoring_weight' => 25,
                    'description' => '2 parties : présentation argumentée + discussion.',
                    'parts' => [
                        ['name' => 'Vortrag', 'description' => 'Présentation monologique argumentée sur un thème'],
                        ['name' => 'Gespräch', 'description' => 'Discussion : réagir, argumenter, négocier avec le partenaire'],
                    ],
                    'exercise_types' => ['speaking-long-turn', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ C2: Großes Deutsches Sprachdiplom ━━━━━━━━━━
        'C2' => [
            'name' => 'Goethe-Zertifikat C2: GDS',
            'total_duration' => 195,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'name' => 'Lesen (Reading)',
                    'skill_type' => 'reading',
                    'time_limit' => 80,
                    'scoring_weight' => 25,
                    'description' => 'Textes longs et complexes : littérature, science, philosophie. Analyse fine et inférence.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'matching-headings', 'gap-fill', 'sentence-completion', 'short-answer'],
                ],
                [
                    'slug' => 'hoeren',
                    'name' => 'Hören (Listening)',
                    'skill_type' => 'listening',
                    'time_limit' => 35,
                    'scoring_weight' => 25,
                    'description' => 'Discours académiques, débats radiophoniques, communications authentiques complexes.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'note-completion', 'short-answer'],
                ],
                [
                    'slug' => 'schreiben',
                    'name' => 'Schreiben (Writing)',
                    'skill_type' => 'writing',
                    'time_limit' => 80,
                    'scoring_weight' => 25,
                    'description' => '2 tâches : texte argumentatif complexe + réécriture/correction de texte.',
                    'exercise_types' => ['essay', 'summary-writing'],
                    'rubric' => [
                        'criteria' => [
                            ['name' => 'Inhalt (Content)', 'slug' => 'content', 'max' => 25],
                            ['name' => 'Textaufbau (Structure)', 'slug' => 'text-structure', 'max' => 25],
                            ['name' => 'Ausdruck (Expression)', 'slug' => 'expression', 'max' => 25],
                            ['name' => 'Korrektheit (Accuracy)', 'slug' => 'accuracy', 'max' => 25],
                        ],
                    ],
                ],
                [
                    'slug' => 'sprechen',
                    'name' => 'Sprechen (Speaking)',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'scoring_weight' => 25,
                    'description' => '2 parties : présentation élaborée + débat et négociation.',
                    'parts' => [
                        ['name' => 'Vortrag', 'description' => 'Présentation approfondie sur un sujet complexe avec analyse'],
                        ['name' => 'Gespräch', 'description' => 'Débat : défendre une position, nuancer, convaincre'],
                    ],
                    'exercise_types' => ['speaking-long-turn', 'speaking-discussion'],
                ],
            ],
        ],
    ],
];
