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
        // ━━━━━━━━━━ A1 : ÖSD Zertifikat A1 (ZA1) ━━━━━━━━━━
        // Source : osd.at, page ZA1 et Durchführungsbestimmungen ZA1 (10/2023),
        // consultées le 2026-10-10. Version générale adulte, à partir de 14 ans —
        // à ne pas confondre avec l'ÖSD KID A1, destiné aux enfants, dont la
        // structure est différente.
        'A1' => [
            'name' => 'ÖSD Zertifikat A1',
            // 25 + 10 + 20 (écrit) + 10 (oral)
            'total_duration' => 65,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'task_count' => 3,
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'time_limit' => 25,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '3 tâches sur des textes courts authentiques d\'Autriche, d\'Allemagne et de Suisse : associations et questions à choix.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Aufgabe 1', 'name_fr' => 'Textes courts',
                            "task" => "De courts messages du quotidien : juger des affirmations.",
                            'type' => 'true-false', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Lesen, Aufgabe 2', 'name_fr' => 'Petites annonces',
                            "task" => "Associer chaque situation a l'annonce qui y repond.",
                            'type' => 'matching', 'source' => 'deduit'],
                        ['part' => 3, 'name' => 'Lesen, Aufgabe 3', 'name_fr' => 'Panneaux',
                            "task" => "Des panneaux et consignes : choisir la bonne reponse.",
                            'type' => 'mcq', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['matching', 'mcq', 'true-false'],
                ],
                [
                    'slug' => 'hoeren',
                    'task_count' => 3,
                    'name' => 'Hören (Compréhension orale)',
                    'skill_type' => 'listening',
                    'time_limit' => 10,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '3 tâches : compréhension globale, détaillée et sélective. Allemand standard, avec les accents d\'Autriche, d\'Allemagne et de Suisse.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Horen, Aufgabe 1', 'name_fr' => 'Echanges courts',
                            "task" => "De courts dialogues : choisir la bonne reponse.",
                            'type' => 'mcq', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Horen, Aufgabe 2', 'name_fr' => 'Annonces',
                            "task" => "Des annonces entendues : juger des affirmations.",
                            'type' => 'true-false', 'source' => 'deduit'],
                        ['part' => 3, 'name' => 'Horen, Aufgabe 3', 'name_fr' => 'Messages',
                            "task" => "Des messages telephoniques : choisir la bonne reponse.",
                            'type' => 'mcq', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['mcq', 'matching', 'true-false'],
                ],
                [
                    'slug' => 'schreiben',
                    'task_count' => 2,
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'time_limit' => 20,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '2 tâches : remplir un formulaire (5 points), puis rédiger un texte simple (10 points).',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Aufgabe 1', 'name_fr' => 'Formulaire',
                            "task" => "Completer un formulaire simple.",
                            'type' => 'form-completion', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Schreiben, Aufgabe 2', 'name_fr' => 'Message court',
                            "task" => "Rediger un message tres court.",
                            'type' => 'short-writing', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['form-completion', 'short-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    'task_count' => 3,
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    'time_limit' => 10,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '3 tâches : se présenter sur 4 thèmes choisis parmi 6, décrire la situation d\'une photo à partir de quatre questions, puis une tâche d\'interaction. Épreuve individuelle à ce niveau.',
                    // La description d'image reste ici une question guidée très simple :
                    // ce n'est pas la Bildbesprechung du B2, qui demande de commenter
                    // une scène et n'est servie qu'à partir de ce niveau.
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Aufgabe 1', 'name_fr' => 'Se presenter',
                            "task" => "Se presenter a partir de mots-cles.",
                            'type' => 'speaking-response', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Sprechen, Aufgabe 2', 'name_fr' => 'Demander une information',
                            "task" => "Poser des questions simples et y repondre.",
                            'type' => 'speaking-elicitation', 'source' => 'deduit'],
                        ['part' => 3, 'name' => 'Sprechen, Aufgabe 3', 'name_fr' => 'Formuler une demande',
                            "task" => "Formuler une demande et reagir a celle du partenaire.",
                            'type' => 'role-play', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['speaking-response', 'role-play',
                        'speaking-elicitation',
                    ],
                ],
            ],
        ],

        // ━━━━━━━━━━ A2 : ÖSD Zertifikat A2 (ZA2) ━━━━━━━━━━
        // Source : osd.at, page ZA2 et Durchführungsbestimmungen ZA2 (10/2023).
        // Version générale adulte, à partir de 14 ans.
        'A2' => [
            'name' => 'ÖSD Zertifikat A2',
            // 30 + 15 + 30 (écrit) + 10 (oral)
            'total_duration' => 85,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'task_count' => 2,
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'time_limit' => 30,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '2 tâches : une association de 5 items (15 points), puis un texte suivi de 5 questions à trois choix (10 points).',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Aufgabe 1', 'name_fr' => 'Textes du quotidien',
                            "task" => "Des textes courts : choisir la bonne reponse.",
                            'type' => 'mcq', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Lesen, Aufgabe 2', 'name_fr' => 'Annonces',
                            "task" => "Associer chaque situation a l'annonce qui y repond.",
                            'type' => 'matching', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['matching', 'mcq'],
                ],
                [
                    'slug' => 'hoeren',
                    'task_count' => 3,
                    'name' => 'Hören (Compréhension orale)',
                    'skill_type' => 'listening',
                    'time_limit' => 15,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '3 tâches, en allemand standard avec les accents d\'Autriche, d\'Allemagne et de Suisse.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Horen, Aufgabe 1', 'name_fr' => 'Conversations',
                            "task" => "Des echanges du quotidien : juger des affirmations.",
                            'type' => 'true-false', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Horen, Aufgabe 2', 'name_fr' => 'Annonces',
                            "task" => "Des annonces entendues : choisir la bonne reponse.",
                            'type' => 'mcq', 'source' => 'deduit'],
                        ['part' => 3, 'name' => 'Horen, Aufgabe 3', 'name_fr' => 'Messages',
                            "task" => "Des messages courts : associer chacun a sa situation.",
                            'type' => 'matching', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['mcq', 'matching', 'true-false'],
                ],
                [
                    'slug' => 'schreiben',
                    'task_count' => 1,
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'time_limit' => 30,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '1 tâche : rédiger un courriel personnel en réponse à un texte déclencheur.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Aufgabe 1', 'name_fr' => 'Message ou courriel',
                            "task" => "Rediger un message court en traitant les points imposes.",
                            'type' => 'short-writing', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['letter-email-writing', 'short-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    'task_count' => 2,
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    'time_limit' => 10,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '2 tâches : se présenter sur 5 thèmes choisis parmi 6, puis planifier quelque chose ensemble. En binôme à l\'examen, individuelle selon le centre ; ici tu t\'entraînes seul, au micro.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Aufgabe 1', 'name_fr' => 'Se presenter',
                            "task" => "Se presenter et repondre aux questions.",
                            'type' => 'speaking-response', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Sprechen, Aufgabe 2', 'name_fr' => 'Se mettre d accord',
                            "task" => "Organiser quelque chose avec son partenaire.",
                            'type' => 'negotiation', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['speaking-response', 'negotiation'],
                ],
            ],
        ],

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
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Aufgabe 1', 'name_fr' => 'Texte informatif',
                            "task" => "Un texte du quotidien, puis des questions a choix multiple.",
                            'type' => 'mcq', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Lesen, Aufgabe 2', 'name_fr' => 'Annonces',
                            "task" => "Associer chaque situation a l'annonce qui y repond.",
                            'type' => 'matching', 'source' => 'deduit'],
                        ['part' => 3, 'name' => 'Lesen, Aufgabe 3', 'name_fr' => 'Opinions',
                            "task" => "Associer des avis aux textes qui les portent.",
                            'type' => 'matching', 'source' => 'deduit'],
                    ],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Horen, Aufgabe 1', 'name_fr' => 'Conversation',
                            "task" => "Une conversation : juger des affirmations.",
                            'type' => 'true-false', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Horen, Aufgabe 2', 'name_fr' => 'Expose',
                            "task" => "Une presentation suivie, puis des questions a choix multiple.",
                            'type' => 'mcq', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'note-completion',
                        'true-false',
                    ],
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
                        ['part' => 1, 'name' => 'Schreiben, Aufgabe 1', 'name_fr' => 'Courriel personnel',
                            "task" => "Rediger un courriel en traitant les points imposes.",
                            'type' => 'letter-email-writing', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Schreiben, Aufgabe 2', 'name_fr' => 'Prise de position',
                            "task" => "Ecrire une courte contribution argumentee.",
                            'type' => 'short-writing', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['essay', 'letter-writing', 'short-writing',
                        'letter-email-writing',
                    ],
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
                        ['part' => 1, 'name' => 'Sprechen, Aufgabe 1', 'name_fr' => 'Entretien',
                            "task" => "Parler de soi et repondre aux questions.",
                            'type' => 'speaking-response', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Sprechen, Aufgabe 2', 'name_fr' => 'Expose',
                            "task" => "Presenter un theme prepare.",
                            'type' => 'speaking-long-turn', 'source' => 'deduit'],
                        ['part' => 3, 'name' => 'Sprechen, Aufgabe 3', 'name_fr' => 'Planifier ensemble',
                            "task" => "Se mettre d'accord avec son partenaire.",
                            'type' => 'negotiation', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['speaking-response', 'speaking-long-turn', 'speaking-discussion',
                        'negotiation',
                    ],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Aufgabe 1', 'name_fr' => 'Article de fond',
                            "task" => "Un article, puis des questions a choix multiple.",
                            'type' => 'mcq', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Lesen, Aufgabe 2', 'name_fr' => 'Opinions croisees',
                            "task" => "Associer des affirmations aux textes qui les portent.",
                            'type' => 'matching', 'source' => 'deduit'],
                        ['part' => 3, 'name' => 'Lesen, Aufgabe 3', 'name_fr' => 'Texte lacunaire',
                            "task" => "Replacer les passages retires d'un texte.",
                            'type' => 'gapped-text', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'matching-headings', 'sentence-completion', 'short-answer',
                        'gapped-text',
                    ],
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
                    'parts' => [
                        ['part' => 1, 'name' => 'Horen, Aufgabe 1', 'name_fr' => 'Entretien',
                            "task" => "Un entretien radio, puis des questions a choix multiple.",
                            'type' => 'mcq', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Horen, Aufgabe 2', 'name_fr' => 'Debat',
                            "task" => "Une discussion a plusieurs voix : dire qui exprime quoi.",
                            'type' => 'matching', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'note-completion', 'multiple-matching',
                        'matching',
                    ],
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
                        ['part' => 1, 'name' => 'Schreiben, Aufgabe 1', 'name_fr' => 'Texte argumente',
                            "task" => "Ecrire une prise de position argumentee.",
                            'type' => 'essay', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Schreiben, Aufgabe 2', 'name_fr' => 'Message formel',
                            "task" => "Rediger un message formel adapte a la situation.",
                            'type' => 'letter-email-writing', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['letter-writing', 'essay',
                        'letter-email-writing',
                    ],
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
                        ['part' => 1, 'name' => 'Sprechen, Aufgabe 1', 'name_fr' => 'Expose',
                            "task" => "Presenter un theme de maniere structuree.",
                            'type' => 'speaking-long-turn', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Sprechen, Aufgabe 2', 'name_fr' => 'Discussion',
                            "task" => "Discuter du theme et defendre son point de vue.",
                            'type' => 'speaking-discussion', 'source' => 'deduit'],
                        ['part' => 3, 'name' => 'Sprechen, Aufgabe 3', 'name_fr' => 'Planifier ensemble',
                            "task" => "Aboutir a un accord avec son partenaire.",
                            'type' => 'negotiation', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['speaking-response', 'picture-description', 'speaking-discussion',
                        'speaking-long-turn', 'negotiation',
                    ],
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

        // ━━━━━━━━━━ C1 : ÖSD Zertifikat C1 (ZC1) ━━━━━━━━━━
        // Source : osd.at, page ZC1 et Durchführungsbestimmungen ZC1 (10/2023).
        // Version générale adulte, à partir de 16 ans. La variante ZC1/Jugendliche
        // partage ces chiffres mais tire ses thèmes de la vie des jeunes.
        'C1' => [
            'name' => 'ÖSD Zertifikat C1',
            // 90 + 40 + 90 (écrit) + 20 (oral)
            'total_duration' => 240,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'task_count' => 4,
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'time_limit' => 90,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '4 tâches : les trois premières valent 5 items chacune — un QCM à trois choix et deux associations — la quatrième regroupe le reste.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Aufgabe 1', 'name_fr' => 'Texte long',
                            "task" => "Un texte exigeant, puis des questions a choix multiple.",
                            'type' => 'mcq', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Lesen, Aufgabe 2', 'name_fr' => 'Opinions croisees',
                            "task" => "Associer des affirmations aux textes qui les portent.",
                            'type' => 'matching', 'source' => 'deduit'],
                        ['part' => 3, 'name' => 'Lesen, Aufgabe 3', 'name_fr' => 'Texte lacunaire',
                            "task" => "Replacer les passages retires d'un texte.",
                            'type' => 'gapped-text', 'source' => 'deduit'],
                        ['part' => 4, 'name' => 'Lesen, Aufgabe 4', 'name_fr' => 'Texte a completer',
                            "task" => "Completer un texte sans proposition : un mot par trou.",
                            'type' => 'open-cloze', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['mcq', 'matching', 'matching-headings', 'gapped-text',
                        'open-cloze',
                    ],
                ],
                [
                    'slug' => 'hoeren',
                    'task_count' => 2,
                    'name' => 'Hören (Compréhension orale)',
                    'skill_type' => 'listening',
                    'time_limit' => 40,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '2 tâches, sur des documents longs.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Horen, Aufgabe 1', 'name_fr' => 'Entretien',
                            "task" => "Un entretien long : completer des notes.",
                            'type' => 'note-completion', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Horen, Aufgabe 2', 'name_fr' => 'Expose',
                            "task" => "Un expose suivi, puis des questions a choix multiple.",
                            'type' => 'mcq', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['mcq', 'note-completion', 'matching'],
                ],
                [
                    'slug' => 'schreiben',
                    'task_count' => 2,
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'time_limit' => 90,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '2 tâches : un courriel formel, puis un exposé écrit, une prise de position ou un commentaire, au choix parmi trois thèmes.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Aufgabe 1', 'name_fr' => 'Texte argumente',
                            "task" => "Rediger un texte structure a partir d'un document source.",
                            'type' => 'integrated-writing', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Schreiben, Aufgabe 2', 'name_fr' => 'Sujet au choix',
                            "task" => "Trois themes au choix : rediger un texte developpe.",
                            'type' => 'essay', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['letter-email-writing', 'essay',
                        'integrated-writing',
                    ],
                ],
                [
                    'slug' => 'sprechen',
                    'task_count' => 3,
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    'time_limit' => 20,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '3 tâches : un appel téléphonique simulé (environ 5 minutes, dos à dos), une discussion, puis un court exposé. Épreuve individuelle.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Aufgabe 1', 'name_fr' => 'Expose',
                            "task" => "Presenter un sujet de maniere structuree.",
                            'type' => 'speaking-long-turn', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Sprechen, Aufgabe 2', 'name_fr' => 'Discussion',
                            "task" => "Discuter du sujet avec l'examinateur.",
                            'type' => 'speaking-discussion', 'source' => 'deduit'],
                        ['part' => 3, 'name' => 'Sprechen, Aufgabe 3', 'name_fr' => 'Debat',
                            "task" => "Defendre un point de vue.",
                            'type' => 'oral-debate', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['role-play', 'speaking-discussion', 'speaking-long-turn',
                        'oral-debate',
                    ],
                ],
            ],
        ],

        // ━━━━━━━━━━ C2 : ÖSD Zertifikat C2 (ZC2) ━━━━━━━━━━
        // Source : osd.at, page ZC2 et Durchführungsbestimmungen ZC2 (08/2026).
        // Quatre modules INDÉPENDANTS, qui se passent et se valident séparément.
        // La variante ZC2/Wirtschaftssprache Deutsch est un autre examen.
        'C2' => [
            'name' => 'ÖSD Zertifikat C2',
            // 90 + 40 + 90 (écrit) + 20 (oral)
            'total_duration' => 240,
            'sections' => [
                [
                    'slug' => 'lesen',
                    'task_count' => 4,
                    'name' => 'Lesen (Compréhension écrite)',
                    'skill_type' => 'reading',
                    'time_limit' => 90,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '4 tâches sur des textes exigeants.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Aufgabe 1', 'name_fr' => 'Texte exigeant',
                            "task" => "Un texte long et nuance, puis des questions a choix multiple.",
                            'type' => 'mcq', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Lesen, Aufgabe 2', 'name_fr' => 'Opinions croisees',
                            "task" => "Associer des affirmations aux textes qui les portent.",
                            'type' => 'matching', 'source' => 'deduit'],
                        ['part' => 3, 'name' => 'Lesen, Aufgabe 3', 'name_fr' => 'Texte lacunaire',
                            "task" => "Replacer les passages retires d'un texte.",
                            'type' => 'gapped-text', 'source' => 'deduit'],
                        ['part' => 4, 'name' => 'Lesen, Aufgabe 4', 'name_fr' => 'Texte a completer',
                            "task" => "Completer un texte sans proposition : un mot par trou.",
                            'type' => 'open-cloze', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['mcq', 'matching', 'matching-headings', 'gapped-text',
                        'open-cloze',
                    ],
                ],
                [
                    'slug' => 'hoeren',
                    'task_count' => 3,
                    'name' => 'Hören (Compréhension orale)',
                    'skill_type' => 'listening',
                    'time_limit' => 40,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '3 tâches sur des documents longs et denses.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Horen, Aufgabe 1', 'name_fr' => 'Entretien',
                            "task" => "Un entretien exigeant : completer des notes.",
                            'type' => 'note-completion', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Horen, Aufgabe 2', 'name_fr' => 'Expose',
                            "task" => "Un expose long, puis des questions a choix multiple.",
                            'type' => 'mcq', 'source' => 'deduit'],
                        ['part' => 3, 'name' => 'Horen, Aufgabe 3', 'name_fr' => 'Debat',
                            "task" => "Une discussion a plusieurs voix : dire qui exprime quoi.",
                            'type' => 'matching', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['mcq', 'note-completion', 'matching'],
                ],
                [
                    'slug' => 'schreiben',
                    'task_count' => 2,
                    'name' => 'Schreiben (Expression écrite)',
                    'skill_type' => 'writing',
                    'time_limit' => 90,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '2 tâches : un texte rédigé sur la feuille de travail (18 points), puis un texte au choix parmi deux thèmes (22 points).',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Aufgabe 1', 'name_fr' => 'Texte argumente',
                            "task" => "Rediger un texte long et nuance a partir d'un document source.",
                            'type' => 'integrated-writing', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Schreiben, Aufgabe 2', 'name_fr' => 'Sujet au choix',
                            "task" => "Deux themes au choix : rediger un texte developpe.",
                            'type' => 'essay', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['essay', 'report-writing',
                        'integrated-writing',
                    ],
                ],
                [
                    'slug' => 'sprechen',
                    'task_count' => 3,
                    'name' => 'Sprechen (Expression orale)',
                    'skill_type' => 'speaking',
                    'time_limit' => 20,
                    'scoring_weight' => 100,
                    'max_score' => 100,
                    'description' => '3 tâches : un appel téléphonique simulé, une présentation, puis un échange. Épreuve individuelle.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Aufgabe 1', 'name_fr' => 'Expose',
                            "task" => "Presenter un sujet exigeant de maniere structuree.",
                            'type' => 'speaking-long-turn', 'source' => 'deduit'],
                        ['part' => 2, 'name' => 'Sprechen, Aufgabe 2', 'name_fr' => 'Sujet au choix',
                            "task" => "Deux themes au choix : en parler de maniere nuancee.",
                            'type' => 'speaking-discussion', 'source' => 'deduit'],
                        ['part' => 3, 'name' => 'Sprechen, Aufgabe 3', 'name_fr' => 'Debat',
                            "task" => "Defendre un point de vue face a son interlocuteur.",
                            'type' => 'oral-debate', 'source' => 'deduit'],
                    ],
                    'exercise_types' => ['role-play', 'speaking-long-turn', 'speaking-discussion',
                        'oral-debate',
                    ],
                ],
            ],
        ],
    ],
];
