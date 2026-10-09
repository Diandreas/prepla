<?php

/**
 * ÖSD — Österreichisches Sprachdiplom Deutsch
 * Source : osd.at (page officielle ÖSD Zertifikat B2, consultée le 2026-10-09)
 *
 * Deux niveaux sont décrits : B1 et B2, les seuls dont la structure a été vérifiée
 * sur la source officielle. Les autres (A1, A2, C1, C2) ont chacun leur propre
 * format : ils seront ajoutés quand il aura été vérifié de la même façon, plutôt que
 * deviné à partir de ceux-ci.
 *
 * Le module Sprechen compte TROIS tâches, et c'est la deuxième — la Bildbesprechung —
 * qui demande de décrire et commenter une image. C'est une tâche de B2 : elle n'a pas
 * à être proposée à un débutant (voir App\Services\Content\ExerciseTypeSuitability).
 *
 * Scoring : chaque module vaut 100 points, seuil de réussite 60 %. Les modules se
 * passent et se valident séparément.
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
        // ━━━━━━━━━━ B1 : ÖSD Zertifikat B1 (ZB1) ━━━━━━━━━━
        // Source : osd.at (page officielle ZB1, consultee le 2026-10-09). Cette
        // epreuve est un produit COMMUN a l'OSD, au Goethe-Institut et a
        // l'universite de Fribourg : sa structure est donc celle du
        // Goethe-Zertifikat B1, verifiee des deux cotes.
        'B1' => [
            'name' => 'ÖSD Zertifikat B1',
            'total_duration' => 180,
            'sections' => [
                [
                    'slug' => 'lesen',
                    // Nombre de taches a l'examen reel (source officielle).
                    'task_count' => 5,
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'time_limit' => 65,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '5 tâches : blog, courriel, article de presse, petites annonces, consignes écrites.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'matching-headings', 'gap-fill'],
                ],
                [
                    'slug' => 'hoeren',
                    // Nombre de taches a l'examen reel (source officielle).
                    'task_count' => 4,
                    'name' => 'Hören (Compréhension orale)',
                    'skill_type' => 'listening',
                    'time_limit' => 40,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '4 tâches : annonces, conversations, émissions courtes.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'note-completion'],
                ],
                [
                    'slug' => 'schreiben',
                    // Nombre de taches a l'examen reel (source officielle).
                    'task_count' => 3,
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'time_limit' => 60,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '3 tâches : un courriel personnel, une prise de position, une excuse formelle.',
                    'parts' => [
                        ['name' => 'Persönliche E-Mail', 'description' => 'Écrire un courriel personnel'],
                        ['name' => 'Meinung äußern', 'description' => 'Donner son opinion sur un sujet'],
                        ['name' => 'Formelle Entschuldigung', 'description' => 'Écrire une excuse formelle'],
                    ],
                    'exercise_types' => ['essay', 'letter-writing', 'short-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    // Nombre de taches a l'examen reel (source officielle).
                    'task_count' => 3,
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '3 tâches : planifier ensemble, présenter un thème, réagir à la présentation du partenaire. À l\'examen, en binôme avec un autre candidat ; ici tu t\'entraînes seul, au micro.',
                    'parts' => [
                        ['name' => 'Planungsgespräch', 'description' => 'Planifier quelque chose ensemble'],
                        ['name' => 'Kurzvortrag', 'description' => 'Présenter brièvement un thème familier'],
                        ['name' => 'Nachfragen', 'description' => 'Réagir à la présentation du partenaire et poser des questions'],
                    ],
                    'exercise_types' => ['speaking-response', 'speaking-long-turn', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ B2 : ÖSD Zertifikat B2 (ZB2) ━━━━━━━━━━
        'B2' => [
            'name' => 'ÖSD Zertifikat B2',
            // 90 (Lesen) + 30 (Hören) + 90 (Schreiben) + 20 (Sprechen)
            'total_duration' => 230,
            'sections' => [
                [
                    'slug' => 'lesen',
                    // Nombre de taches a l'examen reel (source officielle).
                    'task_count' => 4,
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'time_limit' => 90,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '4 tâches sur des textes authentiques : presse, textes informatifs et textes d\'opinion. Compréhension globale, détaillée et sélective.',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'matching-headings', 'sentence-completion', 'short-answer'],
                ],
                [
                    'slug' => 'hoeren',
                    // Nombre de taches a l'examen reel (source officielle).
                    'task_count' => 2,
                    'name' => 'Hören (Compréhension orale)',
                    'skill_type' => 'listening',
                    'time_limit' => 30,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '2 tâches : compréhension globale, détaillée et sélective d\'enregistrements authentiques (entretiens, émissions, conversations).',
                    'exercise_types' => ['mcq', 'true-false-not-given', 'note-completion', 'multiple-matching'],
                ],
                [
                    'slug' => 'schreiben',
                    // Nombre de taches a l'examen reel (source officielle).
                    'task_count' => 2,
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'time_limit' => 90,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '2 textes à rédiger : un courriel formel, puis une argumentation / prise de position.',
                    'parts' => [
                        ['name' => 'Formelle E-Mail', 'description' => 'Rédiger un courriel formel (demande, réclamation, candidature)'],
                        ['name' => 'Argumentation', 'description' => 'Prendre position sur un sujet et argumenter'],
                    ],
                    'exercise_types' => ['letter-writing', 'essay'],
                    'rubric' => [
                        'criteria' => [
                            ['name' => 'Inhalt (Contenu)', 'slug' => 'content', 'max' => 25],
                            ['name' => 'Textaufbau (Structure)', 'slug' => 'text-structure', 'max' => 25],
                            ['name' => 'Ausdruck (Expression)', 'slug' => 'expression', 'max' => 25],
                            ['name' => 'Korrektheit (Correction)', 'slug' => 'accuracy', 'max' => 25],
                        ],
                    ],
                ],
                [
                    'slug' => 'sprechen',
                    // Nombre de taches a l'examen reel (source officielle).
                    'task_count' => 3,
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    // 15-20 min en individuel, 20-25 min en binôme : on retient 20.
                    'time_limit' => 20,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '3 tâches : un entretien d\'information, la description et le commentaire d\'une image, puis une discussion argumentée. À l\'examen, 15 à 20 minutes seul ou 20 à 25 minutes en binôme avec un autre candidat ; ici tu t\'entraînes seul, au micro.',
                    'parts' => [
                        [
                            'name' => 'Informationsgespräch',
                            'description' => 'Entretien d\'information : poser et répondre à des questions pour obtenir ou donner des renseignements précis',
                        ],
                        [
                            'name' => 'Bildbesprechung',
                            'description' => 'Décrire une image puis la commenter : ce qu\'elle montre, ce qu\'elle suggère, votre point de vue',
                        ],
                        [
                            'name' => 'Diskussion',
                            'description' => 'Discussion : défendre une position, répondre aux arguments de l\'interlocuteur, nuancer',
                        ],
                    ],
                    'exercise_types' => ['speaking-response', 'picture-description', 'speaking-discussion'],
                    'rubric' => [
                        'criteria' => [
                            ['name' => 'Aufgabenerfüllung (Réalisation de la tâche)', 'slug' => 'task-fulfilment', 'max' => 25],
                            ['name' => 'Kohärenz und Flüssigkeit (Cohérence et fluidité)', 'slug' => 'coherence-fluency', 'max' => 25],
                            ['name' => 'Wortschatz (Lexique)', 'slug' => 'vocabulary', 'max' => 25],
                            ['name' => 'Strukturen (Correction grammaticale)', 'slug' => 'accuracy', 'max' => 25],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
