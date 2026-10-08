<?php

/**
 * ÖSD — Österreichisches Sprachdiplom Deutsch
 * Source : osd.at (page officielle ÖSD Zertifikat B2, consultée le 2026-10-09)
 *
 * Seul le niveau B2 est décrit ici, parce que c'est le seul dont la structure a été
 * vérifiée sur la source officielle. Les autres niveaux ÖSD (A1, A2, B1, C1, C2) ont
 * chacun leur propre format : ils seront ajoutés quand leur structure aura été
 * vérifiée de la même façon, plutôt que devinée à partir du B2.
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
        // ━━━━━━━━━━ B2 : ÖSD Zertifikat B2 (ZB2) ━━━━━━━━━━
        'B2' => [
            'name' => 'ÖSD Zertifikat B2',
            // 90 (Lesen) + 30 (Hören) + 90 (Schreiben) + 20 (Sprechen)
            'total_duration' => 230,
            'sections' => [
                [
                    'slug' => 'lesen',
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
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    // 15-20 min en individuel, 20-25 min en binôme : on retient 20.
                    'time_limit' => 20,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '3 tâches : un entretien d\'information, la description et le commentaire d\'une image, puis une discussion argumentée. 15 à 20 minutes seul, 20 à 25 minutes en binôme.',
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
