<?php

/**
 * Goethe-Zertifikat A1 (Start Deutsch 1) — module Lesen, les trois parties.
 *
 * Ecrit a la main d'apres le Modellsatz Erwachsene, 8e edition, fevrier 2024 :
 *  - Teil 1 : deux textes courts, cinq affirmations Richtig/Falsch ;
 *  - Teil 2 : cinq situations, deux annonces chacune, on coche a ou b ;
 *  - Teil 3 : cinq panneaux du quotidien, une affirmation chacun.
 *
 * Les textes sont originaux. Ce sont les FORMATS, les longueurs et les nombres
 * d'items qui reprennent l'epreuve, pas les contenus de l'organisme.
 *
 * Deux choix a garder si ce fichier est recopie :
 *  - `answer_options` limite le vrai-faux a DEUX boutons. Sans cette cle, le
 *    lecteur en affiche trois, dont un « Non mentionne » qui n'existe pas au A1 ;
 *  - l'explication est en francais et dit POURQUOI, en citant le mot allemand
 *    qui tranche. Un apprenant A1 ne tire rien d'un « faux » seul.
 */

return [
    'exam' => 'goethe',
    'level' => 'A1',
    'section' => 'lesen',
    'parts' => [

        // ━━━━━━━━━━ Teil 1 : Richtig / Falsch, 5 items ━━━━━━━━━━
        [
            'part' => 1,
            'name' => 'Lesen, Teil 1',
            'type' => 'true-false',
            'title' => 'Goethe A1 · Lesen, Teil 1 — Deux messages',
            'description' => "Deux textes courts et cinq affirmations : juste ou faux. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Lis les deux textes. Pour chaque affirmation, choisis Richtig (juste) ou Falsch (faux).",
                'passage' => "TEXTE 1 — Nachricht von Lena\n\n"
                    ."Hallo Tom,\n"
                    ."ich komme heute später. Mein Bus hat Verspätung. Ich bin um 18 Uhr da, nicht um 17 Uhr. "
                    ."Kannst du schon Brot und Milch kaufen? Der Supermarkt neben dem Bahnhof ist bis 20 Uhr offen. "
                    ."Danke und bis später!\n"
                    ."Lena\n\n"
                    ."TEXTE 2 — E-Mail von der Sprachschule\n\n"
                    ."Liebe Kursteilnehmerinnen und Kursteilnehmer,\n"
                    ."der Deutschkurs A1 beginnt am Montag, 14. September, um 9 Uhr in Raum 12. "
                    ."Bitte bringen Sie das Buch und ein Heft mit. Am ersten Tag dauert der Kurs nur zwei Stunden. "
                    ."Der Kurs findet von Montag bis Donnerstag statt.\n"
                    ."Mit freundlichen Grüßen\n"
                    ."Sabine Herbst, Sprachschule Mitte",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'true-false',
                    'text' => 'Lena kommt um 17 Uhr.',
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. Lena écrit « Ich bin um 18 Uhr da, nicht um 17 Uhr » : elle arrive à 18 h. Le mot « nicht » annule l'heure qui suit.",
                ],
                [
                    'id' => 'q2', 'type' => 'true-false',
                    'text' => 'Tom soll etwas im Supermarkt kaufen.',
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « Kannst du schon Brot und Milch kaufen? » est une demande : Tom doit acheter du pain et du lait. « sollen » exprime ici ce qu'on attend de lui.",
                ],
                [
                    'id' => 'q3', 'type' => 'true-false',
                    'text' => 'Der Deutschkurs beginnt am Dienstag.',
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. Le courriel dit « am Montag, 14. September » : le cours commence un lundi, pas un mardi. « Dienstag » est le mardi.",
                ],
                [
                    'id' => 'q4', 'type' => 'true-false',
                    'text' => 'Die Kursteilnehmer brauchen ein Buch und ein Heft.',
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « Bitte bringen Sie das Buch und ein Heft mit » : il faut apporter le livre et un cahier. « mitbringen » signifie apporter avec soi.",
                ],
                [
                    'id' => 'q5', 'type' => 'true-false',
                    'text' => 'Der Kurs ist am Freitag.',
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. « von Montag bis Donnerstag » : du lundi au jeudi. Le vendredi, « Freitag », n'est pas inclus.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 2 : a oder b, 5 items ━━━━━━━━━━
        // Mehrfachauswahl a DEUX options, pas une association : l'apprenant lit
        // une situation et choisit laquelle des deux annonces y repond.
        [
            'part' => 2,
            'name' => 'Lesen, Teil 2',
            'type' => 'mcq',
            'title' => 'Goethe A1 · Lesen, Teil 2 — a ou b ?',
            'description' => "Cinq situations, deux annonces chacune : laquelle convient, a ou b ? Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Lis chaque situation, puis choisis l'annonce qui convient : a ou b.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Sie möchten am Sonntag Brötchen kaufen. Wohin gehen Sie?",
                    'options' => [
                        "Bäckerei Krause — Montag bis Samstag 6–18 Uhr. Sonntag geschlossen.",
                        "Bäckerei am Markt — täglich 7–12 Uhr, auch am Sonntag.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "b. « täglich … auch am Sonntag » veut dire tous les jours, le dimanche compris. L'annonce a précise « Sonntag geschlossen », fermé le dimanche.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Ihr Kind ist sechs Jahre alt und möchte schwimmen lernen. Was passt?",
                    'options' => [
                        "Schwimmkurs für Kinder ab 5 Jahren — samstags 10 Uhr.",
                        "Aquafitness für Erwachsene ab 18 Jahren — mittwochs 19 Uhr.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "a. « für Kinder ab 5 Jahren » : pour les enfants à partir de 5 ans, donc un enfant de six ans peut venir. « ab » signifie « à partir de ».",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Sie suchen ein Zimmer in Köln für zwei Monate. Was passt?",
                    'options' => [
                        "Wohnung in Köln, 3 Zimmer, Mietvertrag mindestens 2 Jahre.",
                        "Zimmer in Köln frei, auch für kurze Zeit, ab 1 Monat möglich.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "b. « auch für kurze Zeit, ab 1 Monat » : aussi pour une courte durée, à partir d'un mois. L'annonce a demande un bail de deux ans au minimum (« mindestens 2 Jahre »).",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Sie haben Zahnschmerzen und brauchen heute einen Termin. Wen rufen Sie an?",
                    'options' => [
                        "Zahnarztpraxis Dr. Meier — heute Notfalltermine von 8 bis 11 Uhr.",
                        "Augenarztpraxis Dr. Sommer — Termine nach Vereinbarung.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "a. « Zahnarzt » est le dentiste, et « Notfalltermine » des rendez-vous d'urgence le jour même. « Augenarzt », dans b, est l'ophtalmologue.",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Sie möchten am Abend Deutsch lernen, nach der Arbeit. Was passt?",
                    'options' => [
                        "Deutschkurs A1 — Montag bis Freitag, 9–12 Uhr.",
                        "Deutschkurs A1 — Dienstag und Donnerstag, 18–20 Uhr.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "b. 18–20 h, c'est le soir (« am Abend »), après le travail. L'annonce a propose le matin, de 9 h à 12 h.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 3 : Richtig / Falsch sur des panneaux, 5 items ━━━━━━━━━━
        [
            'part' => 3,
            'name' => 'Lesen, Teil 3',
            'type' => 'true-false',
            'title' => 'Goethe A1 · Lesen, Teil 3 — Panneaux et écriteaux',
            'description' => "Cinq panneaux du quotidien et une affirmation chacun : juste ou faux. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Lis chaque panneau, puis dis si l'affirmation est juste (Richtig) ou fausse (Falsch).",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'true-false',
                    'text' => "Am Eingang der Bibliothek steht : « Bitte keine Getränke mitbringen. »\n\n"
                        ."Affirmation : In der Bibliothek darf man etwas trinken.",
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. « keine Getränke mitbringen » interdit d'apporter des boissons. « kein » est la négation devant un nom.",
                ],
                [
                    'id' => 'q2', 'type' => 'true-false',
                    'text' => "An der Tür der Apotheke steht : « Heute wegen Urlaub geschlossen. Ab 12. August wieder offen. »\n\n"
                        ."Affirmation : Die Apotheke ist heute zu.",
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « geschlossen » signifie fermé, et « zu » en est le synonyme courant à l'oral.",
                ],
                [
                    'id' => 'q3', 'type' => 'true-false',
                    'text' => "Im Zug steht : « Dieser Platz ist für Reisende mit Kinderwagen reserviert. »\n\n"
                        ."Affirmation : Der Platz ist für alle Reisenden frei.",
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. « reserviert für Reisende mit Kinderwagen » : la place est réservée aux voyageurs avec poussette, donc pas libre pour tout le monde.",
                ],
                [
                    'id' => 'q4', 'type' => 'true-false',
                    'text' => "Am Automaten steht : « Nur Münzen. Keine Scheine. »\n\n"
                        ."Affirmation : Man kann hier mit Münzen bezahlen.",
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « Nur Münzen » : seulement des pièces. Les billets, « Scheine », ne sont pas acceptés.",
                ],
                [
                    'id' => 'q5', 'type' => 'true-false',
                    'text' => "Im Park steht : « Hunde bitte an der Leine führen. »\n\n"
                        ."Affirmation : Hunde dürfen im Park frei laufen.",
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. « an der Leine führen » veut dire tenir en laisse : les chiens ne peuvent donc pas courir librement.",
                ],
            ],
        ],
    ],
];
