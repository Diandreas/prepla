<?php

/**
 * Goethe-Zertifikat A2 — module Lesen, les quatre parties.
 *
 * Ecrit a la main d'apres le format du Modellsatz Erwachsene :
 *  - Teil 1 : un courriel long, cinq questions a trois options ;
 *  - Teil 2 : cinq situations resolues sur des tableaux d'information ;
 *  - Teil 3 : correspondance de la vie courante, cinq questions a trois options ;
 *  - Teil 4 : cinq personnes, huit petites annonces de A a H, association.
 *
 * Les textes sont originaux. Ce sont les FORMATS, les longueurs et les nombres
 * d'items qui reprennent l'epreuve, pas les contenus de l'organisme.
 *
 * Deux choix a garder si ce fichier est recopie :
 *  - un 'mcq' porte 'options' dans l'ordre et 'correct_answer' en LETTRE ;
 *  - l'explication est en francais et dit POURQUOI, en citant le mot allemand
 *    qui tranche. Un apprenant A2 ne tire rien d'un « faux » seul.
 */

return [
    'exam' => 'goethe',
    'level' => 'A2',
    'section' => 'lesen',
    'parts' => [

        // ━━━━━━━━━━ Teil 1 : un courriel, 5 items a trois options ━━━━━━━━━━
        [
            'part' => 1,
            'name' => 'Lesen, Teil 1',
            'type' => 'mcq',
            'title' => 'Goethe A2 · Lesen, Teil 1 — Un courriel de travail',
            'description' => "Un courriel et cinq questions a trois options. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Lis le courriel, puis choisis pour chaque question la bonne reponse : A, B ou C.",
                'passage' => "E-Mail von Markus Pfeiffer an alle Kolleginnen und Kollegen\n\n"
                    ."Betreff: Betriebsausflug am 12. Juni\n\n"
                    ."Liebe Kolleginnen und Kollegen,\n\n"
                    ."am Freitag, 12. Juni, machen wir unseren Betriebsausflug. Wir fahren mit dem Bus nach Bamberg. "
                    ."Der Bus fährt um 8.30 Uhr vom Parkplatz hinter dem Büro ab. Bitte seid zehn Minuten früher da, "
                    ."denn wir wollen pünktlich losfahren.\n\n"
                    ."Am Morgen besuchen wir das Museum in der Altstadt. Danach essen wir zusammen in einem Restaurant "
                    ."am Fluss. Das Mittagessen bezahlt die Firma, die Getränke müsst ihr selbst bezahlen.\n\n"
                    ."Nach dem Essen hat jeder drei Stunden Zeit für die Stadt. Wer lieber Rad fahren möchte, kann am "
                    ."Bahnhof ein Fahrrad leihen; das kostet acht Euro. Um 18 Uhr fahren wir zurück.\n\n"
                    ."Bitte schreibt mir bis Montag eine E-Mail, wenn ihr mitkommt. Wer ein vegetarisches Essen möchte, "
                    ."schreibt das bitte dazu.\n\n"
                    ."Viele Grüße\n"
                    ."Markus Pfeiffer",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Wo fährt der Bus ab?",
                    'options' => [
                        "Am Bahnhof.",
                        "Auf dem Parkplatz hinter dem Büro.",
                        "Vor dem Museum.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Le courriel dit « Der Bus fährt um 8.30 Uhr vom Parkplatz hinter dem Büro ab ». Le Bahnhof n'apparaît que pour louer un vélo, et le musée est une visite du matin.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Was bezahlt die Firma?",
                    'options' => [
                        "Das Mittagessen.",
                        "Die Getränke.",
                        "Die Fahrräder.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Das Mittagessen bezahlt die Firma, die Getränke müsst ihr selbst bezahlen. » Le verbe « selbst bezahlen » (payer soi-même) écarte les boissons, et le vélo coûte huit euros.",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Was passiert nach dem Mittagessen?",
                    'options' => [
                        "Alle fahren sofort zurück.",
                        "Alle besuchen zusammen das Museum.",
                        "Jeder hat drei Stunden frei.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Nach dem Essen hat jeder drei Stunden Zeit für die Stadt. » Le mot « jeder » dit que chacun s'organise seul ; le musée se visite « am Morgen », et le retour est à 18 h.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Was muss man tun, wenn man mitkommen will?",
                    'options' => [
                        "Nichts, alle kommen mit.",
                        "Bis Montag eine E-Mail schreiben.",
                        "Am Freitag im Büro anrufen.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Bitte schreibt mir bis Montag eine E-Mail, wenn ihr mitkommt. » « bis Montag » fixe le délai ; le vendredi 12 juin est le jour de la sortie, pas celui de l'inscription.",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Wo kann man ein Fahrrad leihen?",
                    'options' => [
                        "Am Bahnhof.",
                        "Im Restaurant am Fluss.",
                        "Im Museum.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « kann am Bahnhof ein Fahrrad leihen ». Le verbe « leihen » signifie emprunter ou louer, et le lieu indiqué est la gare.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 2 : tableaux d'information, 5 items ━━━━━━━━━━
        // Les trois tableaux sont dans content.passage, en texte aligne : c'est
        // la lecture selective qui est testee, pas la comprehension d'un recit.
        [
            'part' => 2,
            'name' => 'Lesen, Teil 2',
            'type' => 'mcq',
            'title' => 'Goethe A2 · Lesen, Teil 2 — Horaires, cinema, centre commercial',
            'description' => "Cinq situations resolues a l'aide de trois tableaux d'information. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Lis les trois tableaux, puis choisis pour chaque situation la bonne reponse : A, B ou C.",
                'passage' => "TABELLE 1 — Zugverbindungen Nürnberg → München (Montag bis Freitag)\n\n"
                    ."ab 6.12  an 7.28   umsteigen in Ingolstadt\n"
                    ."ab 7.12  an 8.15   direkt\n"
                    ."ab 8.12  an 9.40   umsteigen in Treuchtlingen\n"
                    ."ab 9.12  an 10.15  direkt\n\n"
                    ."TABELLE 2 — Kinoprogramm « Lichtspiele », Woche vom 3. bis 9. März\n\n"
                    ."Der blaue Koffer (Komödie) — Mo, Mi, Fr 17.00 — auf Deutsch\n"
                    ."Sommer in Bregenz (Drama) — Di, Do 20.15 — auf Deutsch\n"
                    ."Die Insel der Vögel (Dokumentarfilm) — Sa, So 15.30 — Deutsch mit Untertiteln\n"
                    ."Nachts in Lissabon (Krimi) — Fr, Sa 22.00 — Originalfassung, ab 16 Jahren\n\n"
                    ."TABELLE 3 — Einkaufszentrum Hansapark, Wegweiser\n\n"
                    ."Untergeschoss: Parkhaus, Toiletten, Fahrradwerkstatt\n"
                    ."Erdgeschoss: Supermarkt, Apotheke, Blumen\n"
                    ."1. Stock: Kleidung, Schuhe, Sportartikel\n"
                    ."2. Stock: Bücher, Elektro, Spielwaren\n"
                    ."3. Stock: Café, Friseur, Reisebüro",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Sie müssen am Mittwoch um 9 Uhr in München sein und möchten nicht umsteigen. Welchen Zug nehmen Sie?",
                    'options' => [
                        "Den Zug um 6.12 Uhr.",
                        "Den Zug um 7.12 Uhr.",
                        "Den Zug um 8.12 Uhr.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Le train de 7.12 est marqué « direkt » et arrive à 8.15, donc avant 9 h. Celui de 6.12 porte « umsteigen in Ingolstadt » : il faut changer de train. Celui de 8.12 arrive à 9.40, trop tard.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Sie möchten am Sonntag ins Kino gehen. Welcher Film läuft?",
                    'options' => [
                        "Der blaue Koffer.",
                        "Die Insel der Vögel.",
                        "Nachts in Lissabon.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Seul « Die Insel der Vögel » porte « Sa, So » : le dimanche, c'est « So ». La comédie passe « Mo, Mi, Fr » et le polar « Fr, Sa ».",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Ihre Tochter ist 14 Jahre alt und möchte am Samstag den Krimi sehen. Was ist richtig?",
                    'options' => [
                        "Sie kann den Krimi sehen, er läuft am Samstag.",
                        "Der Krimi läuft am Samstag nicht.",
                        "Sie ist zu jung: der Film ist erst ab 16 Jahren.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. Le polar porte « ab 16 Jahren » : à partir de 16 ans. « ab » marque un âge minimum, donc une personne de 14 ans ne peut pas entrer, même si le film passe bien « Fr, Sa ».",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Sie brauchen ein Medikament. In welches Geschoss gehen Sie?",
                    'options' => [
                        "Ins Erdgeschoss.",
                        "In den 1. Stock.",
                        "In den 3. Stock.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Apotheke » est la pharmacie, et elle est au « Erdgeschoss », le rez-de-chaussée. Le premier étage vend des vêtements, le troisième abrite un café et un coiffeur.",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Ihr Fahrrad ist kaputt. Wohin gehen Sie?",
                    'options' => [
                        "In den 2. Stock.",
                        "Ins Untergeschoss.",
                        "In den 3. Stock.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Fahrradwerkstatt » — l'atelier de réparation de vélos — figure au « Untergeschoss », le sous-sol. « Werkstatt » veut dire atelier, c'est le mot qui tranche.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 3 : correspondance du quotidien, 5 items ━━━━━━━━━━
        [
            'part' => 3,
            'name' => 'Lesen, Teil 3',
            'type' => 'mcq',
            'title' => 'Goethe A2 · Lesen, Teil 3 — Carte postale, message, courriel',
            'description' => "Trois textes de la vie courante et cinq questions a trois options. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Lis les trois textes, puis choisis pour chaque question la bonne reponse : A, B ou C.",
                'passage' => "TEXT A — Postkarte von Yusuf\n\n"
                    ."Liebe Frau Brandt,\n"
                    ."viele Grüße aus Rostock! Das Wetter ist leider nicht gut, es regnet jeden Tag. "
                    ."Trotzdem gefällt mir die Stadt sehr gut. Gestern war ich im Hafen und habe ein großes Schiff gesehen. "
                    ."Morgen fahre ich mit dem Zug nach Warnemünde, dort soll der Strand sehr schön sein. "
                    ."Am Sonntag bin ich wieder zu Hause.\n"
                    ."Ihr Yusuf\n\n"
                    ."TEXT B — Nachricht von Claudia an ihre Nachbarin\n\n"
                    ."Hallo Bettina,\n"
                    ."ich bin ab Donnerstag eine Woche in Leipzig bei meiner Schwester. "
                    ."Kannst du bitte meine Pflanzen gießen? Zweimal reicht. "
                    ."Der Schlüssel liegt wie immer bei Herrn Özdemir im zweiten Stock. "
                    ."Die Post musst du nicht holen, das macht mein Sohn.\n"
                    ."Danke dir! Claudia\n\n"
                    ."TEXT C — E-Mail der Hausverwaltung\n\n"
                    ."Guten Tag,\n"
                    ."am Dienstag, 14. Oktober, kommt zwischen 8 und 14 Uhr ein Techniker und prüft die Heizungen in "
                    ."allen Wohnungen. Bitte bleiben Sie zu Hause oder geben Sie Ihren Schlüssel bei einer Nachbarin ab. "
                    ."Wenn Sie an diesem Tag arbeiten müssen, rufen Sie uns bis Freitag an; dann kommt der Techniker in "
                    ."der nächsten Woche. Das Wasser bleibt an, nur die Heizung ist für zwei Stunden aus.\n"
                    ."Hausverwaltung Lindner",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Text A: Wie findet Yusuf Rostock?",
                    'options' => [
                        "Die Stadt gefällt ihm sehr gut.",
                        "Er findet die Stadt langweilig.",
                        "Ihm gefällt nur das Wetter.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Trotzdem gefällt mir die Stadt sehr gut. » Le mot « trotzdem » (pourtant) oppose la pluie à son plaisir : la pluie ne gâche pas son avis sur la ville.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Text A: Was macht Yusuf morgen?",
                    'options' => [
                        "Er geht in den Hafen.",
                        "Er fährt nach Warnemünde.",
                        "Er fährt nach Hause.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Morgen fahre ich mit dem Zug nach Warnemünde. » Le port, c'était « gestern » (hier), et le retour à la maison « am Sonntag ».",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Text B: Was soll Bettina machen?",
                    'options' => [
                        "Die Post holen.",
                        "Nach Leipzig fahren.",
                        "Die Pflanzen gießen.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Kannst du bitte meine Pflanzen gießen? » — « gießen » veut dire arroser. Pour le courrier, Claudia écrit « Die Post musst du nicht holen » : « nicht müssen » signifie que ce n'est pas nécessaire.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Text C: Was sollen die Bewohner am 14. Oktober tun?",
                    'options' => [
                        "Zu Hause bleiben oder den Schlüssel bei einer Nachbarin abgeben.",
                        "Die Heizung selbst ausmachen.",
                        "Das Wasser abstellen.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Bitte bleiben Sie zu Hause oder geben Sie Ihren Schlüssel bei einer Nachbarin ab. » Le « oder » laisse le choix entre deux solutions. L'eau, elle, « bleibt an » : elle reste ouverte.",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Text C: Was kann man tun, wenn man am Dienstag arbeiten muss?",
                    'options' => [
                        "Man bekommt keinen anderen Termin.",
                        "Man muss Urlaub nehmen.",
                        "Man ruft bis Freitag an und bekommt einen Termin in der nächsten Woche.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « rufen Sie uns bis Freitag an; dann kommt der Techniker in der nächsten Woche. » Le « dann » annonce la conséquence : un autre rendez-vous la semaine suivante.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 4 : cinq personnes, huit annonces, 5 items ━━━━━━━━━━
        // Trois annonces (C, D, H) ne conviennent a personne : c'est voulu, et
        // c'est ce qui force a lire les huit avant de choisir.
        [
            'part' => 4,
            'name' => 'Lesen, Teil 4',
            'type' => 'matching',
            'title' => 'Goethe A2 · Lesen, Teil 4 — Cinq personnes, huit annonces',
            'description' => "Cinq personnes cherchent quelque chose : a chacune son annonce, de A a H. Toutes les annonces ne servent pas. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Lis les huit petites annonces, puis choisis pour chaque personne l'annonce qui convient. Attention : certaines annonces ne conviennent a personne.",
                'passage' => "KLEINANZEIGEN\n\n"
                    ."A — Deutschkurs am Abend: zweimal pro Woche, dienstags und donnerstags von 18 bis 20 Uhr, "
                    ."kleine Gruppen, auch für Anfänger. Volkshochschule Nord.\n\n"
                    ."B — Zimmer in einer Wohngemeinschaft frei: 18 Quadratmeter, günstige Miete, ab 1. Mai, "
                    ."nur für Studentinnen und Studenten.\n\n"
                    ."C — Hundebetreuung: Ich gehe nachmittags mit Ihrem Hund spazieren, 10 Euro pro Stunde. "
                    ."Bitte abends anrufen.\n\n"
                    ."D — Gebrauchtes Fahrrad zu verkaufen: 7 Gänge, gut gepflegt, mit Licht und Schloss, 80 Euro.\n\n"
                    ."E — Nachhilfe in Mathematik für Schülerinnen und Schüler der Klassen 5 bis 10, bei mir zu Hause, "
                    ."eine Stunde pro Woche.\n\n"
                    ."F — Wir suchen eine Aushilfe für unser Café: jedes Wochenende, samstags und sonntags von 9 bis 15 Uhr. "
                    ."Erfahrung ist nicht nötig.\n\n"
                    ."G — Klavierunterricht für Erwachsene, auch ohne Vorkenntnisse, am Dienstag- und Donnerstagabend.\n\n"
                    ."H — Umzugshilfe gesucht: nur am Samstag, 20. April, vier Stunden Möbel tragen, 15 Euro pro Stunde.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => "Frau Kowalski arbeitet den ganzen Tag im Büro. Sie möchte am Abend Deutsch lernen.",
                    'options' => [
                        "A — Deutschkurs am Abend, Volkshochschule Nord",
                        "B — Zimmer in einer Wohngemeinschaft",
                        "C — Hundebetreuung",
                        "D — Gebrauchtes Fahrrad",
                        "E — Nachhilfe in Mathematik",
                        "F — Aushilfe für ein Café",
                        "G — Klavierunterricht für Erwachsene",
                        "H — Umzugshilfe gesucht",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. L'annonce dit « Deutschkurs am Abend … von 18 bis 20 Uhr » : un cours d'allemand le soir, donc après le bureau. L'annonce G est aussi le soir, mais c'est du « Klavierunterricht », du piano.",
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => "Herr Nowak studiert in Freiburg und sucht ein günstiges Zimmer.",
                    'options' => [
                        "A — Deutschkurs am Abend, Volkshochschule Nord",
                        "B — Zimmer in einer Wohngemeinschaft",
                        "C — Hundebetreuung",
                        "D — Gebrauchtes Fahrrad",
                        "E — Nachhilfe in Mathematik",
                        "F — Aushilfe für ein Café",
                        "G — Klavierunterricht für Erwachsene",
                        "H — Umzugshilfe gesucht",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Zimmer … günstige Miete … nur für Studentinnen und Studenten » : une chambre pas chère, réservée aux étudiants. « günstig » veut dire avantageux, bon marché, et Herr Nowak est bien étudiant.",
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => "Tomas ist 13 Jahre alt und geht in die 7. Klasse. In Mathematik schreibt er schlechte Noten.",
                    'options' => [
                        "A — Deutschkurs am Abend, Volkshochschule Nord",
                        "B — Zimmer in einer Wohngemeinschaft",
                        "C — Hundebetreuung",
                        "D — Gebrauchtes Fahrrad",
                        "E — Nachhilfe in Mathematik",
                        "F — Aushilfe für ein Café",
                        "G — Klavierunterricht für Erwachsene",
                        "H — Umzugshilfe gesucht",
                    ],
                    'correct_answer' => 'E',
                    'explanation' => "E. « Nachhilfe in Mathematik … der Klassen 5 bis 10 » : du soutien scolaire en maths, pour les classes 5 à 10. La 7e classe de Tomas est dans cet intervalle ; « Nachhilfe » est le mot pour les cours de rattrapage.",
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => "Frau Lindner hat am Wochenende frei und möchte regelmäßig etwas Geld dazuverdienen.",
                    'options' => [
                        "A — Deutschkurs am Abend, Volkshochschule Nord",
                        "B — Zimmer in einer Wohngemeinschaft",
                        "C — Hundebetreuung",
                        "D — Gebrauchtes Fahrrad",
                        "E — Nachhilfe in Mathematik",
                        "F — Aushilfe für ein Café",
                        "G — Klavierunterricht für Erwachsene",
                        "H — Umzugshilfe gesucht",
                    ],
                    'correct_answer' => 'F',
                    'explanation' => "F. « Aushilfe … jedes Wochenende, samstags und sonntags » : un petit travail chaque week-end. « jedes Wochenende » répond à « regelmäßig » (régulièrement) ; l'annonce H ne vaut que pour un seul samedi, le 20 avril.",
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => "Herr Baumann ist 60 Jahre alt und möchte endlich ein Instrument lernen. Er hat noch nie Musikunterricht gehabt.",
                    'options' => [
                        "A — Deutschkurs am Abend, Volkshochschule Nord",
                        "B — Zimmer in einer Wohngemeinschaft",
                        "C — Hundebetreuung",
                        "D — Gebrauchtes Fahrrad",
                        "E — Nachhilfe in Mathematik",
                        "F — Aushilfe für ein Café",
                        "G — Klavierunterricht für Erwachsene",
                        "H — Umzugshilfe gesucht",
                    ],
                    'correct_answer' => 'G',
                    'explanation' => "G. « Klavierunterricht für Erwachsene, auch ohne Vorkenntnisse » : des cours de piano pour adultes, même sans connaissances préalables. « ohne Vorkenntnisse » correspond exactement à « noch nie Musikunterricht gehabt ».",
                ],
            ],
        ],
    ],
];
