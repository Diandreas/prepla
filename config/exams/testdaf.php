<?php

/**
 * TestDaF - Test Deutsch als Fremdsprache
 * Source: testdaf.de
 *
 * LE FORMAT DIGITAL, et lui seul. testdaf.de propose aujourd'hui DEUX formats
 * cote a cote — « Der digitale TestDaF » et « Der papierbasierte TestDaF » —
 * dont les structures different nettement : le format papier compte 3 taches de
 * Leseverstehen la ou le digital en compte 7, et une seule tache d'ecrit contre
 * deux. La refonte a AJOUTE le digital, elle n'a pas supprime le papier. Seul le
 * digital est decrit ici ; le papier le sera quand sa structure aura ete
 * verifiee de la meme facon, plutot que deduite de celle-ci.
 *
 * PAS DE NIVEAUX. Le TestDaF ne se passe pas « en B2 » : un seul test, dont le
 * resultat se situe sous TDN 3, ou a TDN 3, TDN 4 ou TDN 5 (B2 a C1). Ne jamais
 * creer de « TestDaF A2 » ni de « TestDaF C2 ».
 *
 * Nombres de taches et durees verifies sur testdaf.de le 2026-10-10. Le site
 * precise que « die Bearbeitungszeit und die Anzahl der Aufgaben koennen in
 * jedem Pruefungsteil variieren » : ces comptes sont ceux d'une session type.
 *
 * Scoring: TDN 3, TDN 4, TDN 5 per section (Unter TDN 3 = fail)
 * University admission typically requires TDN 4 in all sections
 */

return [
    'slug' => 'testdaf',
    'name' => 'TestDaF',
    'language' => 'german',

    'scoring' => [
        'type' => 'level',
        'levels' => ['Unter TDN 3', 'TDN 3', 'TDN 4', 'TDN 5'],
        'per_section' => true,
        'university_requirement' => 'TDN 4 in all sections',
    ],

    'cefr_mapping' => [
        'TDN 3' => 'B2',
        'TDN 4' => 'B2-C1',
        'TDN 5' => 'C1',
    ],

    /*
     * QUI A VERIFIE QUOI, ET QUAND.
     *
     * « source => officiel » sur une partie signifie : lu dans le document de
     * l'organisme, avec ses chiffres. C'etait une affirmation que rien ne
     * soutenait — n'importe qui pouvait l'ecrire. Un palier qui la revendique
     * doit desormais nommer ici le document et la date de consultation, et un
     * test refuse la revendication sans la preuve.
     */
    'verifications' => [
        'TDN' => ['document' => 'TestDaF digital, page officielle « Aufbau des digitalen TestDaF », testdaf.de', 'date' => '2026-10-10'],
    ],

    'sections' => [
        // ─── LESEN (Reading) ───
        [
            'slug' => 'lesen',
            'task_count' => 7,
            'name' => 'Lesen (Reading)',
            'skill_type' => 'reading',
            'time_limit' => 55,
            'question_count' => 30,
            'scoring_weight' => 25,
            'parts' => [
                ['part' => 1, 'name' => 'Lesen, Teil 1', 'name_fr' => 'Completer un texte a trous',
                    "task" => "Luckentext erganzen. 5 items.",
                    'type' => 'open-cloze', 'items' => 5, 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Lesen, Teil 2', 'name_fr' => 'Remettre les paragraphes en ordre',
                    "task" => "Textabschnitte ordnen. 4 items.",
                    'type' => 'ordering', 'items' => 4, 'source' => 'officiel'],
                ['part' => 3, 'name' => 'Lesen, Teil 3', 'name_fr' => 'Questions a choix multiple',
                    "task" => "Multiple-Choice. 7 items.",
                    'type' => 'mcq', 'items' => 7, 'source' => 'officiel'],
                ['part' => 4, 'name' => 'Lesen, Teil 4', 'name_fr' => 'Associer les actes de parole',
                    "task" => "Sprachhandlungen zuordnen. 4 items.",
                    'type' => 'matching', 'items' => 4, 'source' => 'officiel'],
                ['part' => 5, 'name' => 'Lesen, Teil 5', 'name_fr' => 'Classer des affirmations',
                    "task" => "Aussagen Kategorien zuordnen. 7 items.",
                    'type' => 'matching-features', 'items' => 7, 'source' => 'officiel'],
                ['part' => 6, 'name' => 'Lesen, Teil 6', 'name_fr' => 'Affirmations et couples de notions',
                    "task" => "Aussagen einem Begriffspaar zuordnen. 4 items.",
                    'type' => 'matching', 'items' => 4, 'source' => 'officiel'],
                ['part' => 7, 'name' => 'Lesen, Teil 7', 'name_fr' => 'Reperer les erreurs du resume',
                    "task" => "Fehler in Zusammenfassung erkennen. 3 items.",
                    'type' => 'summary-completion', 'items' => 3, 'source' => 'officiel'],
            ],
            'exercise_types' => [
                'mcq',
                'true-false-not-given',
                'matching',
                'matching-headings',
                'sentence-completion',
                'gap-fill',
                'open-cloze', 'ordering', 'matching-features', 'summary-completion',
            ],
        ],

        // ─── HÖREN (Listening) ───
        [
            'slug' => 'hoeren',
            'task_count' => 7,
            'name' => 'Hören (Listening)',
            'skill_type' => 'listening',
            'time_limit' => 40,
            'question_count' => 25,
            'scoring_weight' => 25,
            'parts' => [
                ['part' => 1, 'name' => 'Horen, Teil 1', 'name_fr' => 'Completer un tableau',
                    "task" => "Kurzantwort : Ubersicht erganzen. 5 items.",
                    'type' => 'table-completion', 'items' => 5, 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Horen, Teil 2', 'name_fr' => 'Noter les passages utiles',
                    "task" => "Kurzantwort : Textstellen zu Begriffspaar notieren. 4 items.",
                    'type' => 'short-answer', 'items' => 4, 'source' => 'officiel'],
                ['part' => 3, 'name' => 'Horen, Teil 3', 'name_fr' => 'Reperer les erreurs du resume',
                    "task" => "Fehler in Zusammenfassung erkennen. 2 items.",
                    'type' => 'summary-completion', 'items' => 2, 'source' => 'officiel'],
                ['part' => 4, 'name' => 'Horen, Teil 4', 'name_fr' => 'Attribuer les affirmations',
                    "task" => "Aussagen Personen zuordnen, a partir d'une video. 6 items.",
                    'type' => 'matching', 'items' => 6, 'source' => 'officiel'],
                ['part' => 5, 'name' => 'Horen, Teil 5', 'name_fr' => 'Completer le plan d\'un expose',
                    "task" => "Gliederungspunkte zu Vortrag erganzen, a partir d'une video. 4 items.",
                    'type' => 'note-completion', 'items' => 4, 'source' => 'officiel'],
                ['part' => 6, 'name' => 'Horen, Teil 6', 'name_fr' => 'Questions a choix multiple',
                    "task" => "Multiple-Choice. 5 items.",
                    'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                ['part' => 7, 'name' => 'Horen, Teil 7', 'name_fr' => 'Son et orthographe',
                    "task" => "Laut- und Schriftbild abgleichen. 4 items.",
                    'type' => 'dictation', 'items' => 4, 'source' => 'officiel'],
            ],
            'exercise_types' => [
                'mcq',
                'true-false-not-given',
                'note-completion',
                'matching',
                'short-answer',
                'sentence-completion',
                'table-completion', 'summary-completion', 'dictation',
            ],
        ],

        // ─── SCHREIBEN (Writing) ───
        [
            'slug' => 'schreiben',
            'task_count' => 2,
            // Formats repris des taches decrites ci-dessous : sans eux, ce module
            // ne declarait AUCUN format et ne pouvait rien produire — le sujet
            // sortait ampute de cette epreuve.
            'parts' => [
                ['part' => 1, 'name' => 'Schreiben, Teil 1', 'name_fr' => 'Texte argumente',
                    "task" => "Argumentativen Text schreiben. 200 mots au minimum.",
                    'type' => 'essay', 'items' => 1, 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Schreiben, Teil 2', 'name_fr' => 'Resume texte et graphique',
                    "task" => "Resumer les informations d'un texte et d'un graphique. 100 a 150 mots.",
                    'type' => 'integrated-writing', 'items' => 1, 'source' => 'officiel'],
            ],
            'exercise_types' => ['graph-description', 'essay',
                'integrated-writing',
            ],
            'name' => 'Schreiben (Writing)',
            'skill_type' => 'writing',
            'time_limit' => 60,
            'question_count' => 1,
            'scoring_weight' => 25,
            'tasks' => [
                [
                    'task' => 1,
                    'name' => 'Schriftlicher Ausdruck',
                    'description' => 'Write a structured argumentative text on an academic topic based on a chart/graph. Include: introduction, describe data, present arguments for/against, state own position. 350-400 words.',
                    'min_words' => 350,
                    'time_suggestion' => 60,
                    'exercise_type' => 'essay',
                ],
            ],
            'rubric' => [
                'criteria' => [
                    ['name' => 'Gesamteindruck (Overall impression)', 'slug' => 'overall-impression', 'max' => 5],
                    ['name' => 'Behandlung der Aufgabe (Task treatment)', 'slug' => 'task-treatment', 'max' => 5],
                    ['name' => 'Argumentation (Argumentation)', 'slug' => 'argumentation', 'max' => 5],
                    ['name' => 'Sprachliche Realisierung (Linguistic realization)', 'slug' => 'linguistic-realization', 'max' => 5],
                ],
            ],
        ],

        // ─── SPRECHEN (Speaking) ───
        [
            'slug' => 'sprechen',
            'task_count' => 7,
            // Formats repris des taches decrites ci-dessous : sans eux, ce module
            // ne declarait AUCUN format et ne pouvait rien produire — le sujet
            // sortait ampute de cette epreuve.
            'parts' => [
                ['part' => 1, 'name' => 'Sprechen, Teil 1', 'name_fr' => 'Donner un conseil',
                    "task" => "Rat geben. 45 secondes de parole.",
                    'type' => 'speaking-response', 'source' => 'officiel'],
                ['part' => 2, 'name' => 'Sprechen, Teil 2', 'name_fr' => 'Peser des options',
                    "task" => "Optionen abwagen. 1 min 30 de parole.",
                    'type' => 'speaking-discussion', 'source' => 'officiel'],
                ['part' => 3, 'name' => 'Sprechen, Teil 3', 'name_fr' => 'Resumer un texte',
                    "task" => "Text zusammenfassen. 2 minutes de parole.",
                    'type' => 'speaking-long-turn', 'source' => 'officiel'],
                ['part' => 4, 'name' => 'Sprechen, Teil 4', 'name_fr' => 'Comparer et prendre position',
                    "task" => "Informationen abgleichen, Stellung nehmen. 1 min 30 de parole.",
                    'type' => 'speaking-discussion', 'source' => 'officiel'],
                ['part' => 5, 'name' => 'Sprechen, Teil 5', 'name_fr' => 'Presenter un sujet',
                    "task" => "Thema prasentieren. 2 min 30 de parole.",
                    'type' => 'speaking-long-turn', 'source' => 'officiel'],
                ['part' => 6, 'name' => 'Sprechen, Teil 6', 'name_fr' => 'Restituer des arguments',
                    "task" => "Argumente wiedergeben, Stellung nehmen. 2 minutes de parole.",
                    'type' => 'speaking-discussion', 'source' => 'officiel'],
                ['part' => 7, 'name' => 'Sprechen, Teil 7', 'name_fr' => 'Critiquer des mesures',
                    "task" => "Massnahmen kritisieren. 1 min 30 de parole.",
                    'type' => 'oral-debate', 'source' => 'officiel'],
            ],
            'exercise_types' => ['speaking-response', 'speaking-long-turn', 'speaking-discussion', 'negotiation', 'oral-debate', 'role-play', 'speaking-elicitation'],
            'name' => 'Sprechen (Speaking)',
            'skill_type' => 'speaking',
            'time_limit' => 35,
            'question_count' => 7,
            'scoring_weight' => 25,
            'rubric' => [
                'criteria' => [
                    ['name' => 'Gesamteindruck (Overall impression)', 'slug' => 'overall-impression', 'max' => 5],
                    ['name' => 'Behandlung der Aufgabe (Task treatment)', 'slug' => 'task-treatment', 'max' => 5],
                    ['name' => 'Sprachliche Realisierung (Linguistic realization)', 'slug' => 'linguistic-realization', 'max' => 5],
                ],
            ],
        ],
    ],
];
