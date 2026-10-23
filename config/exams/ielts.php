<?php

/**
 * IELTS - International English Language Testing System
 * Source: ielts.org
 *
 * Two variants: Academic and General Training
 * Scoring: Band 0-9 (half-band increments)
 * Overall band = average of 4 section bands, rounded to nearest half/whole
 */

return [
    'slug' => 'ielts',
    'name' => 'IELTS',
    'language' => 'english',

    'scoring' => [
        'type' => 'band',
        'min' => 0,
        'max' => 9,
        'increment' => 0.5,
        'overall_calculation' => 'average_rounded_half',
    ],

    'variants' => ['academic', 'general'],

    'sections' => [
        // ─── LISTENING (identical for Academic & General) ───
        [
            'slug' => 'listening',
            'name' => 'Listening',
            'skill_type' => 'listening',
        // 4 parties de 10 questions, 40 au total (source : ielts.org).
        'task_count' => 4,
            'time_limit' => 30, // +10min transfer time on paper
            'question_count' => 40,
            'scoring_weight' => 25,
            'parts' => [
                ['part' => 1, 'name' => 'Listening, Teil 1', 'name_fr' => 'Conversation du quotidien',
                    "task" => "Une conversation entre deux personnes dans une situation courante. 10 questions.",
                    'type' => 'form-completion', 'items' => 10, 'item_range' => '1-10', 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Listening, Teil 2', 'name_fr' => 'Monologue du quotidien',
                    "task" => "Une seule personne parle d'un sujet pratique, par exemple des equipements locaux. 10 questions.",
                    'type' => 'plan-map-diagram-labeling', 'items' => 10, 'item_range' => '11-20', 'source' => 'officiel'],
                ['part' => 3, 'name' => 'Listening, Teil 3', 'name_fr' => 'Conversation en contexte educatif',
                    "task" => "Un echange entre deux locuteurs principaux, souvent des etudiants et un enseignant. 10 questions.",
                    'type' => 'mcq', 'items' => 10, 'item_range' => '21-30', 'source' => 'officiel'],
                ['part' => 4, 'name' => 'Listening, Teil 4', 'name_fr' => 'Expose universitaire',
                    "task" => "Une personne expose un sujet universitaire. 10 questions.",
                    'type' => 'note-completion', 'items' => 10, 'item_range' => '31-40', 'source' => 'officiel'],
            ],
            'exercise_types' => [
                'mcq',
                'matching',
                'plan-map-diagram-labeling',
                'form-completion',
                'note-completion',
                'table-completion',
                'flow-chart-completion',
                'summary-completion',
                'sentence-completion',
            ],
        ],

        // ─── READING (differs Academic vs General) ───
        [
            'slug' => 'reading',
            // Structure officielle : 3 textes.
            'task_count' => 3,
            'name' => 'Reading',
            'skill_type' => 'reading',
            'time_limit' => 60,
            'question_count' => 40,
            'scoring_weight' => 25,
            'variant_config' => [
                'academic' => [
                    'description' => '3 long passages from books, journals, magazines, newspapers. Academic topics.',
                    'passages' => 3,
                ],
                'general' => [
                    'description' => '3 sections with texts from notices, newspapers, instruction manuals, books. Everyday English.',
                    'passages' => 3,
                ],
            ],
            'parts' => [
                ['part' => 1, 'name' => 'Reading, Teil 1', 'name_fr' => 'Premier texte',
                    "task" => "Academic : un texte long pour public non specialiste. General Training : deux ou trois textes courts du quotidien (avis, annonces, horaires).",
                    'type' => 'matching-headings', 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Reading, Teil 2', 'name_fr' => 'Deuxieme texte',
                    "task" => "Academic : un deuxieme texte long. General Training : deux textes lies au travail (fiches de poste, contrats, formation).",
                    'type' => 'true-false-not-given', 'source' => 'officiel'],
                ['part' => 3, 'name' => 'Reading, Teil 3', 'name_fr' => 'Troisieme texte',
                    "task" => "Academic : un troisieme texte long, dont un au moins developpe une argumentation suivie. General Training : un texte long d'interet general. 40 questions en tout, 1 point chacune, 60 minutes.",
                    'type' => 'mcq', 'items' => 40, 'source' => 'officiel'],
            ],
            'exercise_types' => [
                'mcq',
                'true-false-not-given',
                'yes-no-not-given',
                'matching-headings',
                'matching-information',
                'matching-features',
                'matching-sentence-endings',
                'summary-completion',
                'note-completion',
                'table-completion',
                'flow-chart-completion',
                'diagram-labeling',
                'sentence-completion',
                'short-answer',
            ],
        ],

        // ─── WRITING (differs Academic vs General) ───
        [
            'slug' => 'writing',
            // Structure officielle : 2 taches.
            'task_count' => 2,
            'name' => 'Writing',
            'skill_type' => 'writing',
        // Sans formats declares, ce module ne pouvait rien produire. Task 1 de
        // l'Academic decrit une information visuelle (courbe, tableau, schema),
        // Task 2 est un essai argumente — source : ielts.org, format Writing.
            'parts' => [
                ['part' => 1, 'name' => 'Writing, Teil 1', 'name_fr' => 'Tache 1',
                    "task" => "Academic : decrire un graphique, un tableau ou un schema. General Training : repondre a une situation par une lettre, en traitant les trois points donnes. 150 mots au minimum, environ 20 minutes.",
                    'type' => 'graph-description', 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Writing, Teil 2', 'name_fr' => 'Tache 2',
                    "task" => "Discuter un point de vue, un argument ou un probleme. 250 mots au minimum, environ 40 minutes. Compte double de la tache 1.",
                    'type' => 'essay', 'source' => 'officiel'],
            ],
        'exercise_types' => ['graph-description', 'essay'],
            'time_limit' => 60,
            'question_count' => 2,
            'scoring_weight' => 25,
            'variant_config' => [
                'academic' => [
                    'tasks' => [
                        [
                            'task' => 1,
                            'name' => 'Writing Task 1 (Academic)',
                            'description' => 'Describe, summarise or explain information from a graph, table, chart or diagram.',
                            'min_words' => 150,
                            'time_suggestion' => 20,
                            'exercise_type' => 'graph-description',
                        ],
                        [
                            'task' => 2,
                            'name' => 'Writing Task 2',
                            'description' => 'Write an essay in response to a point of view, argument or problem.',
                            'min_words' => 250,
                            'time_suggestion' => 40,
                            'exercise_type' => 'essay',
                        ],
                    ],
                ],
                'general' => [
                    'tasks' => [
                        [
                            'task' => 1,
                            'name' => 'Writing Task 1 (General)',
                            'description' => 'Write a letter (formal, semi-formal, or informal) requesting information or explaining a situation.',
                            'min_words' => 150,
                            'time_suggestion' => 20,
                            'exercise_type' => 'letter-writing',
                        ],
                        [
                            'task' => 2,
                            'name' => 'Writing Task 2',
                            'description' => 'Write an essay in response to a point of view, argument or problem.',
                            'min_words' => 250,
                            'time_suggestion' => 40,
                            'exercise_type' => 'essay',
                        ],
                    ],
                ],
            ],
            'rubric' => [
                'criteria' => [
                    ['name' => 'Task Achievement', 'slug' => 'task-achievement', 'max' => 9],
                    ['name' => 'Coherence and Cohesion', 'slug' => 'coherence-cohesion', 'max' => 9],
                    ['name' => 'Lexical Resource', 'slug' => 'lexical-resource', 'max' => 9],
                    ['name' => 'Grammatical Range and Accuracy', 'slug' => 'grammar-accuracy', 'max' => 9],
                ],
                'overall_calculation' => 'average_rounded_half',
            ],
        ],

        // ─── SPEAKING (identical for Academic & General) ───
        [
            'slug' => 'speaking',
            'name' => 'Speaking',
            'skill_type' => 'speaking',
        // Meme panne. Les trois parties de l'oral : entretien, prise de parole
        // longue sur une fiche, puis discussion — source : ielts.org.
            'parts' => [
                ['part' => 1, 'name' => 'Speaking, Teil 1', 'name_fr' => 'Introduction et entretien',
                    "task" => "L'examinateur pose des questions generales sur des sujets familiers. 4 a 5 minutes.",
                    'type' => 'speaking-response', 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Speaking, Teil 2', 'name_fr' => 'Expose',
                    "task" => "Une fiche-sujet, une minute de preparation, puis environ deux minutes de parole. 3 a 4 minutes en tout.",
                    'type' => 'speaking-long-turn', 'source' => 'officiel'],
                ['part' => 3, 'name' => 'Speaking, Teil 3', 'name_fr' => 'Discussion',
                    "task" => "Discussion plus abstraite autour du theme de la partie 2. 4 a 5 minutes.",
                    'type' => 'speaking-discussion', 'source' => 'officiel'],
            ],
        'exercise_types' => ['speaking-response', 'speaking-long-turn', 'speaking-discussion'],
        'task_count' => 3,
            'time_limit' => 14,
            'question_count' => 3, // 3 parts
            'scoring_weight' => 25,
            'rubric' => [
                'criteria' => [
                    ['name' => 'Fluency and Coherence', 'slug' => 'fluency-coherence', 'max' => 9],
                    ['name' => 'Lexical Resource', 'slug' => 'lexical-resource', 'max' => 9],
                    ['name' => 'Grammatical Range and Accuracy', 'slug' => 'grammar-accuracy', 'max' => 9],
                    ['name' => 'Pronunciation', 'slug' => 'pronunciation', 'max' => 9],
                ],
                'overall_calculation' => 'average_rounded_half',
            ],
        ],
    ],

    // Band score conversion (raw score → band)
    'band_conversion' => [
        'listening' => [
            39 => 9.0, 37 => 8.5, 35 => 8.0, 33 => 7.5, 30 => 7.0,
            27 => 6.5, 23 => 6.0, 20 => 5.5, 16 => 5.0, 13 => 4.5,
            10 => 4.0, 6 => 3.5, 4 => 3.0,
        ],
        'reading_academic' => [
            39 => 9.0, 37 => 8.5, 35 => 8.0, 33 => 7.5, 30 => 7.0,
            27 => 6.5, 23 => 6.0, 20 => 5.5, 16 => 5.0, 13 => 4.5,
            10 => 4.0, 6 => 3.5, 4 => 3.0,
        ],
        'reading_general' => [
            40 => 9.0, 39 => 8.5, 37 => 8.0, 36 => 7.5, 34 => 7.0,
            32 => 6.5, 30 => 6.0, 27 => 5.5, 23 => 5.0, 19 => 4.5,
            15 => 4.0, 12 => 3.5, 8 => 3.0,
        ],
    ],
];
