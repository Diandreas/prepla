<?php

/**
 * Goethe-Zertifikat B1 — module Lesen, les cinq parties.
 *
 * Formats reproduits d'apres le Modellsatz Erwachsene :
 *  - Teil 1 : un texte personnel, six affirmations Richtig/Falsch ;
 *  - Teil 2 : un article de presse d'environ 300 mots, six questions a trois options ;
 *  - Teil 3 : sept situations a rapprocher de huit annonces ;
 *  - Teil 4 : sept courriers de lecteurs, pour ou contre (Ja/Nein) ;
 *  - Teil 5 : un reglement, quatre questions a trois options.
 *
 * Les textes sont originaux. Ce sont les FORMATS, les longueurs et les nombres
 * d'items qui reprennent l'epreuve, pas les contenus de l'organisme.
 *
 * Deux choix a garder si ce fichier est recopie :
 *  - `answer_options` limite le vrai-faux a DEUX boutons. Sans cette cle, le
 *    lecteur en affiche trois, dont un « Non mentionne » qui n'existe pas ici.
 *    Au Teil 4 ce sont Ja/Nein, parce qu'on juge une POSITION, pas un fait ;
 *  - l'explication est en francais et dit POURQUOI, en citant le mot allemand
 *    qui tranche. Un « c'est faux » seul n'apprend rien.
 */

return [
    'exam' => 'goethe',
    'level' => 'B1',
    'section' => 'lesen',
    'parts' => [

        // ━━━━━━━━━━ Teil 1 : Richtig / Falsch sur un texte personnel, 6 items ━━━━━━━━━━
        [
            'part' => 1,
            'name' => 'Lesen, Teil 1',
            'type' => 'true-false',
            'title' => 'Goethe B1 · Lesen, Teil 1 — Un an à Leipzig',
            'description' => "Un billet de blog et six affirmations : juste ou faux. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Lis le billet de blog. Pour chaque affirmation, choisis Richtig (juste) ou Falsch (faux).",
                'passage' => "Mein Jahr in Leipzig — aus dem Blog von Marisol\n\n"
                    ."Vor zehn Monaten bin ich aus Valencia nach Leipzig gekommen. Eigentlich wollte ich nach "
                    ."Berlin, aber dort habe ich kein Zimmer gefunden. Heute bin ich froh darüber: Leipzig ist "
                    ."kleiner, und ich bin mit dem Fahrrad in zwanzig Minuten überall.\n\n"
                    ."Die ersten Wochen waren hart. Im Deutschkurs habe ich alles verstanden, aber auf dem Markt "
                    ."hat mich niemand verstanden — und ich niemanden. Meine Vermieterin, Frau Köhler, hat mir "
                    ."dann vorgeschlagen, zweimal pro Woche mit ihr Kaffee zu trinken und dabei nur Deutsch zu "
                    ."sprechen. Das hat mir mehr geholfen als jedes Buch.\n\n"
                    ."Seit Februar arbeite ich halbtags in einem Kindergarten. Bezahlt wird das nicht, es ist ein "
                    ."Praktikum, aber die Kinder korrigieren mich sofort, wenn ich einen Fehler mache. Am "
                    ."Nachmittag gehe ich in die Universität; ich schreibe gerade eine Hausarbeit über Stadtgärten.\n\n"
                    ."Was ich nicht mag, ist die Bürokratie. Für die Verlängerung meines Visums musste ich drei "
                    ."Monate auf einen Termin warten. Und den November möchte ich hier nicht noch einmal erleben.\n\n"
                    ."Im September fliege ich nach Hause. Bleiben könnte ich, Frau Köhler würde mir das Zimmer "
                    ."lassen, aber meine Schwester heiratet im Oktober, und ich habe versprochen, dabei zu sein. "
                    ."Vielleicht komme ich im nächsten Frühling zurück.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'true-false',
                    'text' => 'Marisol wollte von Anfang an nach Leipzig.',
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. Elle écrit « Eigentlich wollte ich nach Berlin » : au départ elle voulait aller à Berlin, et c'est le manque de chambre (« kein Zimmer gefunden ») qui l'a menée à Leipzig. « eigentlich » annonce justement ce qui était prévu avant.",
                ],
                [
                    'id' => 'q2', 'type' => 'true-false',
                    'text' => 'Die Idee, zusammen Kaffee zu trinken und Deutsch zu sprechen, kam von der Vermieterin.',
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « Frau Köhler hat mir dann vorgeschlagen » : c'est la logeuse qui propose. Le verbe « vorschlagen » désigne celui qui a l'idée, pas celui qui l'accepte.",
                ],
                [
                    'id' => 'q3', 'type' => 'true-false',
                    'text' => 'Für ihre Arbeit im Kindergarten bekommt Marisol Geld.',
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. « Bezahlt wird das nicht, es ist ein Praktikum » : ce n'est pas payé, c'est un stage. Le passif « bezahlt wird » avec « nicht » exclut toute rémunération.",
                ],
                [
                    'id' => 'q4', 'type' => 'true-false',
                    'text' => 'Marisol schreibt im Moment eine Hausarbeit.',
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « ich schreibe gerade eine Hausarbeit über Stadtgärten » : « gerade » signifie « en ce moment ». Le travail est donc en cours.",
                ],
                [
                    'id' => 'q5', 'type' => 'true-false',
                    'text' => 'Marisol hat ihr Visum ohne Wartezeit verlängern können.',
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. « musste ich drei Monate auf einen Termin warten » : il a fallu attendre trois mois un rendez-vous. « musste » marque l'obligation subie, donc bien une attente.",
                ],
                [
                    'id' => 'q6', 'type' => 'true-false',
                    'text' => 'Marisol hält eine Rückkehr nach Leipzig für möglich.',
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. La dernière phrase dit « Vielleicht komme ich im nächsten Frühling zurück » : « vielleicht » et « zurückkommen » expriment un retour possible, sans promesse.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 2 : article de presse, 6 mcq a trois options ━━━━━━━━━━
        [
            'part' => 2,
            'name' => 'Lesen, Teil 2',
            'type' => 'mcq',
            'title' => 'Goethe B1 · Lesen, Teil 2 — Le bus qui vient sur commande',
            'description' => "Un article d'environ 300 mots et six questions à trois options. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Lis l'article, puis choisis pour chaque question la bonne réponse : A, B ou C.",
                'passage' => "Der Bus, der auf Bestellung kommt\n\n"
                    ."Wer im Landkreis Hohenberg kein Auto hat, kannte das Problem: morgens fuhr ein Bus zur "
                    ."Schule, abends einer zurück, und dazwischen nichts. Seit zwei Jahren gibt es deshalb den "
                    ."Rufbus. Er hat keinen festen Fahrplan. Wer mitfahren will, meldet sich spätestens eine "
                    ."Stunde vorher über eine App oder telefonisch an und nennt die Haltestelle. Ein Rechner "
                    ."fügt die Wünsche zusammen und schlägt dem Fahrer eine Strecke vor.\n\n"
                    ."Die Idee kam nicht aus dem Rathaus, sondern von einer Gruppe Jugendlicher. Für eine "
                    ."Abschlussarbeit hatten sie gezählt, wie viele Sitze in den alten Bussen leer blieben. Das "
                    ."Ergebnis — im Schnitt siebzehn von zwanzig — überzeugte den Kreistag schneller als jede Rede.\n\n"
                    ."Heute fahren täglich sieben Kleinbusse. Eine Fahrt kostet drei Euro, für Schüler und für "
                    ."Menschen über 65 die Hälfte. Teurer als früher ist das System trotzdem nicht: weil kaum "
                    ."leere Kilometer gefahren werden, zahlt der Landkreis pro Fahrgast weniger als vorher.\n\n"
                    ."Nicht alle sind zufrieden. Besonders älteren Fahrgästen fällt die Anmeldung schwer. "
                    ."„Meine Mutter ist 84 und hat kein Smartphone“, sagt Bernd Faust aus Kleinried. Der "
                    ."Landkreis hat darauf reagiert und eine Telefonnummer eingerichtet, die von sieben bis "
                    ."neunzehn Uhr besetzt ist. Außerdem wünschen sich viele Fahrgäste Verbindungen am späten "
                    ."Abend; bisher fährt der letzte Bus um 21 Uhr.\n\n"
                    ."Die Verwaltung bleibt vorsichtig. „Wir haben nicht den Verkehr einer Großstadt, wir haben "
                    ."Dörfer mit vierzig Einwohnern“, sagt Verkehrsplanerin Ulrike Daum. „Was bei uns "
                    ."funktioniert, muss in einem anderen Kreis nicht funktionieren.“ Neun Landkreise haben "
                    ."trotzdem schon um Auskunft gebeten.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Was ist beim Rufbus anders als beim früheren Bus?",
                    'options' => [
                        "Er fährt nur, wenn sich vorher jemand angemeldet hat.",
                        "Er fährt öfter, aber immer auf derselben Strecke.",
                        "Er fährt nur morgens und abends.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Er hat keinen festen Fahrplan. Wer mitfahren will, meldet sich spätestens eine Stunde vorher an » : pas d'horaire fixe, il faut s'inscrire. C'est l'ancien bus qui ne roulait que « morgens » et « abends ».",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Von wem kam die Idee für den Rufbus?",
                    'options' => [
                        "Von der Verwaltung des Landkreises.",
                        "Von einer Gruppe Jugendlicher.",
                        "Von einem privaten Busunternehmen.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Die Idee kam nicht aus dem Rathaus, sondern von einer Gruppe Jugendlicher. » La construction « nicht … sondern » écarte la mairie et nomme les jeunes.",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Was hatten die Jugendlichen herausgefunden?",
                    'options' => [
                        "Die alten Busse waren fast immer voll.",
                        "Es gab zu wenige Haltestellen im Landkreis.",
                        "In den alten Bussen blieben die meisten Sitze leer.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « wie viele Sitze … leer blieben. Das Ergebnis — im Schnitt siebzehn von zwanzig » : dix-sept sièges vides sur vingt, donc la plupart. « im Schnitt » veut dire « en moyenne ».",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Was zahlt eine Schülerin für eine Fahrt?",
                    'options' => [
                        "Drei Euro.",
                        "Nichts.",
                        "Eineinhalb Euro.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Eine Fahrt kostet drei Euro, für Schüler … die Hälfte » : la moitié de trois euros, soit 1,50 €. « die Hälfte » signifie la moitié, pas la gratuité.",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Wie hat der Landkreis auf die Kritik älterer Fahrgäste reagiert?",
                    'options' => [
                        "Mit einer Telefonnummer, die tagsüber besetzt ist.",
                        "Mit Smartphones für alle Fahrgäste über 65.",
                        "Mit zusätzlichen Fahrten nach 21 Uhr.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « hat darauf reagiert und eine Telefonnummer eingerichtet, die von sieben bis neunzehn Uhr besetzt ist ». Les trajets tardifs restent un souhait : « wünschen sich », pas encore une réalité.",
                ],
                [
                    'id' => 'q6', 'type' => 'mcq',
                    'text' => "Was meint Verkehrsplanerin Ulrike Daum?",
                    'options' => [
                        "Jeder Landkreis sollte das System einführen.",
                        "Das System lässt sich nicht einfach auf andere Gegenden übertragen.",
                        "Das System ist für den Landkreis zu teuer geworden.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Was bei uns funktioniert, muss in einem anderen Kreis nicht funktionieren. » Le « muss … nicht » allemand veut dire « ce n'est pas forcément le cas », donc prudence sur la transposition.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 3 : sept situations, huit annonces, 7 items ━━━━━━━━━━
        [
            'part' => 3,
            'name' => 'Lesen, Teil 3',
            'type' => 'matching',
            'title' => 'Goethe B1 · Lesen, Teil 3 — Annonces de la ville',
            'description' => "Sept situations et huit annonces : laquelle convient à chaque personne ? Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Lis les huit annonces, puis choisis pour chaque situation l'annonce qui convient. Une annonce reste inutilisée.",
                'passage' => "A — Volkshochschule Nord: Nähen für Anfänger. Aus alten Kleidern Neues machen. "
                    ."Dienstag 18–20 Uhr, acht Abende, 60 Euro.\n\n"
                    ."B — Stadtbibliothek: Lesepaten gesucht. Wer einmal pro Woche eine Stunde Zeit hat, liest "
                    ."Grundschulkindern vor. Keine Vorkenntnisse nötig, kurze Einführung.\n\n"
                    ."C — Sportverein Eiche: Lauftreff für Wiedereinsteiger. Samstags 9 Uhr am Waldparkplatz. "
                    ."Langsames Tempo, kostenlos, ohne Anmeldung.\n\n"
                    ."D — Musikschule Kranz: Gitarrenunterricht für Erwachsene, einzeln oder zu zweit. Termine "
                    ."frei wählbar, 25 Euro pro Stunde.\n\n"
                    ."E — Repair-Café im Gemeindehaus: Jeden ersten Samstag reparieren wir gemeinsam Toaster, "
                    ."Lampen und Fahrräder. Bringen Sie Ihr Gerät mit.\n\n"
                    ."F — Kochschule Löffel: Vegetarisch für Berufstätige. Drei Gerichte in 30 Minuten. "
                    ."Freitag 19 Uhr, einmaliger Kurs, 35 Euro.\n\n"
                    ."G — Seniorentreff Lindenhof: Computerstunde. Fragen zu Handy, E-Mail und Internet, "
                    ."montags 15 Uhr, ohne Kosten. Enkel dürfen mitkommen.\n\n"
                    ."H — Kleingartenverein Sonnenhang: Freie Parzellen, 200 m², Wasser am Weg. Jahresmiete "
                    ."180 Euro, Mithilfe an zwei Arbeitstagen erwartet.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => "Ihr Wasserkocher ist kaputt. Wegwerfen möchten Sie ihn nicht, reparieren können Sie ihn allein nicht.",
                    'options' => [
                        'A — Nähen für Anfänger',
                        'B — Lesepaten gesucht',
                        'C — Lauftreff für Wiedereinsteiger',
                        'D — Gitarrenunterricht für Erwachsene',
                        'E — Repair-Café im Gemeindehaus',
                        'F — Vegetarisch für Berufstätige',
                        'G — Computerstunde im Seniorentreff',
                        'H — Freie Parzellen im Kleingartenverein',
                    ],
                    'correct_answer' => 'E',
                    'explanation' => "E. L'annonce dit « reparieren wir gemeinsam Toaster, Lampen und Fahrräder » : on répare ensemble de petits appareils électriques, et « Bringen Sie Ihr Gerät mit » invite à apporter le sien.",
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => "Nach Jahren ohne Sport wollen Sie wieder laufen. Sie möchten nichts bezahlen und sich nicht vorher anmelden.",
                    'options' => [
                        'A — Nähen für Anfänger',
                        'B — Lesepaten gesucht',
                        'C — Lauftreff für Wiedereinsteiger',
                        'D — Gitarrenunterricht für Erwachsene',
                        'E — Repair-Café im Gemeindehaus',
                        'F — Vegetarisch für Berufstätige',
                        'G — Computerstunde im Seniorentreff',
                        'H — Freie Parzellen im Kleingartenverein',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Lauftreff für Wiedereinsteiger … kostenlos, ohne Anmeldung » : « Wiedereinsteiger » désigne ceux qui reprennent après une pause, et les deux conditions, gratuit et sans inscription, sont écrites noir sur blanc.",
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => "Ihre Großmutter versteht ihr neues Handy nicht. Sie sucht Hilfe, die nichts kostet, und möchte Sie mitnehmen.",
                    'options' => [
                        'A — Nähen für Anfänger',
                        'B — Lesepaten gesucht',
                        'C — Lauftreff für Wiedereinsteiger',
                        'D — Gitarrenunterricht für Erwachsene',
                        'E — Repair-Café im Gemeindehaus',
                        'F — Vegetarisch für Berufstätige',
                        'G — Computerstunde im Seniorentreff',
                        'H — Freie Parzellen im Kleingartenverein',
                    ],
                    'correct_answer' => 'G',
                    'explanation' => "G. « Fragen zu Handy, E-Mail und Internet … ohne Kosten. Enkel dürfen mitkommen » : les questions sur le téléphone, la gratuité et la présence des petits-enfants, les trois sont dans l'annonce.",
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => "Sie arbeiten bis 18 Uhr und möchten lernen, schnell und ohne Fleisch zu kochen.",
                    'options' => [
                        'A — Nähen für Anfänger',
                        'B — Lesepaten gesucht',
                        'C — Lauftreff für Wiedereinsteiger',
                        'D — Gitarrenunterricht für Erwachsene',
                        'E — Repair-Café im Gemeindehaus',
                        'F — Vegetarisch für Berufstätige',
                        'G — Computerstunde im Seniorentreff',
                        'H — Freie Parzellen im Kleingartenverein',
                    ],
                    'correct_answer' => 'F',
                    'explanation' => "F. « Vegetarisch für Berufstätige. Drei Gerichte in 30 Minuten. Freitag 19 Uhr » : « vegetarisch » veut dire sans viande, « Berufstätige » désigne les personnes qui travaillent, et 19 h convient après 18 h.",
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => "Sie möchten ehrenamtlich etwas mit Kindern machen, haben aber nur eine Stunde pro Woche frei und keine Ausbildung dafür.",
                    'options' => [
                        'A — Nähen für Anfänger',
                        'B — Lesepaten gesucht',
                        'C — Lauftreff für Wiedereinsteiger',
                        'D — Gitarrenunterricht für Erwachsene',
                        'E — Repair-Café im Gemeindehaus',
                        'F — Vegetarisch für Berufstätige',
                        'G — Computerstunde im Seniorentreff',
                        'H — Freie Parzellen im Kleingartenverein',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Wer einmal pro Woche eine Stunde Zeit hat, liest Grundschulkindern vor. Keine Vorkenntnisse nötig » : une heure par semaine, avec des enfants, et sans formation préalable (« Vorkenntnisse » = connaissances préalables).",
                ],
                [
                    'id' => 'q6', 'type' => 'matching',
                    'text' => "Sie wohnen in einer Wohnung ohne Garten und möchten selbst Gemüse anbauen.",
                    'options' => [
                        'A — Nähen für Anfänger',
                        'B — Lesepaten gesucht',
                        'C — Lauftreff für Wiedereinsteiger',
                        'D — Gitarrenunterricht für Erwachsene',
                        'E — Repair-Café im Gemeindehaus',
                        'F — Vegetarisch für Berufstätige',
                        'G — Computerstunde im Seniorentreff',
                        'H — Freie Parzellen im Kleingartenverein',
                    ],
                    'correct_answer' => 'H',
                    'explanation' => "H. « Freie Parzellen, 200 m², Wasser am Weg » : une « Parzelle » est une parcelle de jardin à louer, et l'eau sur place sert justement à la culture.",
                ],
                [
                    'id' => 'q7', 'type' => 'matching',
                    'text' => "Sie möchten als Erwachsener ein Instrument lernen, können aber nur zu unregelmäßigen Zeiten kommen.",
                    'options' => [
                        'A — Nähen für Anfänger',
                        'B — Lesepaten gesucht',
                        'C — Lauftreff für Wiedereinsteiger',
                        'D — Gitarrenunterricht für Erwachsene',
                        'E — Repair-Café im Gemeindehaus',
                        'F — Vegetarisch für Berufstätige',
                        'G — Computerstunde im Seniorentreff',
                        'H — Freie Parzellen im Kleingartenverein',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « Gitarrenunterricht für Erwachsene … Termine frei wählbar » : pour adultes, et les horaires se choisissent librement, ce qui convient à un emploi du temps irrégulier. Les autres cours ont un jour fixe.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 4 : sept courriers de lecteurs, Ja / Nein, 7 items ━━━━━━━━━━
        // Ja/Nein et non Richtig/Falsch : on ne juge pas un fait, mais une POSITION.
        [
            'part' => 4,
            'name' => 'Lesen, Teil 4',
            'type' => 'true-false',
            'title' => 'Goethe B1 · Lesen, Teil 4 — Handys in der Schule verbieten?',
            'description' => "Sept courriers de lecteurs sur un même sujet : chaque personne est-elle pour l'interdiction ? Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Lis les sept courriers de lecteurs. Dis pour chaque personne si elle est POUR une interdiction du portable à l'école : Ja (oui) ou Nein (non).",
                'passage' => "Leserbriefe zur Frage: Sollen Handys in der Schule verboten werden?\n\n"
                    ."Person A — Kerstin Abel, Lehrerin:\n"
                    ."In meiner Klasse habe ich zwei Jahre lang gegen die Geräte gekämpft. Seit das Handy morgens "
                    ."in eine Box kommt, sprechen die Schüler in der Pause wieder miteinander. Ich würde das "
                    ."Verbot sofort auf alle Schulen ausweiten.\n\n"
                    ."Person B — Dilan Yildiz, Schülerin, 16:\n"
                    ."Ein Verbot löst gar nichts. Wir brauchen das Handy für Wörterbücher, für Fahrpläne und um "
                    ."unseren Eltern zu schreiben, wenn der Unterricht ausfällt. Man sollte uns beibringen, wann "
                    ."man es weglegt, statt es einfach wegzunehmen.\n\n"
                    ."Person C — Markus Reh, Vater von drei Kindern:\n"
                    ."Mein Sohn hat im Unterricht heimlich Videos geschaut und seine Noten sind abgestürzt. Zu "
                    ."Hause darf er das Gerät am Nachmittag benutzen, aber die Schulzeit sollte handyfrei sein. "
                    ."Das muss die Schule entscheiden, nicht jedes Kind für sich.\n\n"
                    ."Person D — Antje Sommer, Schulsozialarbeiterin:\n"
                    ."Ich sehe jeden Tag, welche Streitereien über das Handy laufen. Trotzdem halte ich ein "
                    ."allgemeines Verbot für falsch: die Konflikte gehen danach einfach woanders weiter. Lieber "
                    ."klare Regeln pro Klasse, die die Schüler mitbestimmen.\n\n"
                    ."Person E — Pawel Nowak, Informatiklehrer:\n"
                    ."Wer Jugendliche auf das digitale Leben vorbereiten will, kann ihnen nicht das wichtigste "
                    ."Gerät aus der Hand nehmen. In meinem Unterricht arbeiten wir damit. Ein Verbot wäre ein "
                    ."Rückschritt in die Zeit der Kreidetafel.\n\n"
                    ."Person F — Helene Brauer, Rentnerin und ehemalige Schulleiterin:\n"
                    ."Konzentration lernt man in Ruhe. Sechs Stunden ohne Bildschirm haben noch keinem Kind "
                    ."geschadet, und die Schulen, die es eingeführt haben, berichten von ruhigeren Pausen. Ich "
                    ."bin eindeutig dafür.\n\n"
                    ."Person G — Tobias Krenz, Vater einer Tochter in der fünften Klasse:\n"
                    ."Meine Tochter fährt eine Stunde mit dem Bus. Ohne Handy wüsste sie bei einer Verspätung "
                    ."nicht, wie sie mich erreicht. Im Unterricht hat das Gerät nichts zu suchen, in der Tasche "
                    ."auf dem Schulweg schon. Ein generelles Verbot kommt für mich nicht in Frage.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'true-false',
                    'text' => 'Person A ist für ein Handyverbot in der Schule?',
                    'answer_options' => ['Ja', 'Nein'],
                    'correct_answer' => 'Ja',
                    'explanation' => "Ja. Kerstin Abel écrit « Ich würde das Verbot sofort auf alle Schulen ausweiten » : elle étendrait l'interdiction à toutes les écoles. « ausweiten » veut dire élargir, donc elle est pour.",
                ],
                [
                    'id' => 'q2', 'type' => 'true-false',
                    'text' => 'Person B ist für ein Handyverbot in der Schule?',
                    'answer_options' => ['Ja', 'Nein'],
                    'correct_answer' => 'Nein',
                    'explanation' => "Nein. Dilan Yildiz commence par « Ein Verbot löst gar nichts » : l'interdiction ne résout rien. Elle préfère qu'on apprenne « wann man es weglegt », quand le ranger.",
                ],
                [
                    'id' => 'q3', 'type' => 'true-false',
                    'text' => 'Person C ist für ein Handyverbot in der Schule?',
                    'answer_options' => ['Ja', 'Nein'],
                    'correct_answer' => 'Ja',
                    'explanation' => "Ja. Markus Reh écrit « die Schulzeit sollte handyfrei sein » : le temps scolaire devrait être sans portable. L'adjectif « handyfrei » dit l'interdiction, et il ajoute que c'est à l'école de décider.",
                ],
                [
                    'id' => 'q4', 'type' => 'true-false',
                    'text' => 'Person D ist für ein Handyverbot in der Schule?',
                    'answer_options' => ['Ja', 'Nein'],
                    'correct_answer' => 'Nein',
                    'explanation' => "Nein. Antje Sommer voit bien les conflits, mais écrit « halte ich ein allgemeines Verbot für falsch » : elle juge l'interdiction générale mauvaise. Le « trotzdem » qui précède annonce justement ce retournement.",
                ],
                [
                    'id' => 'q5', 'type' => 'true-false',
                    'text' => 'Person E ist für ein Handyverbot in der Schule?',
                    'answer_options' => ['Ja', 'Nein'],
                    'correct_answer' => 'Nein',
                    'explanation' => "Nein. Pawel Nowak écrit « Ein Verbot wäre ein Rückschritt » : une interdiction serait un retour en arrière. Un « Rückschritt » est le contraire d'un progrès, donc il s'y oppose.",
                ],
                [
                    'id' => 'q6', 'type' => 'true-false',
                    'text' => 'Person F ist für ein Handyverbot in der Schule?',
                    'answer_options' => ['Ja', 'Nein'],
                    'correct_answer' => 'Ja',
                    'explanation' => "Ja. Helene Brauer conclut « Ich bin eindeutig dafür » : elle est clairement pour. « dafür sein » signifie être en faveur de quelque chose, « eindeutig » renforce encore.",
                ],
                [
                    'id' => 'q7', 'type' => 'true-false',
                    'text' => 'Person G ist für ein Handyverbot in der Schule?',
                    'answer_options' => ['Ja', 'Nein'],
                    'correct_answer' => 'Nein',
                    'explanation' => "Nein. Tobias Krenz accepte l'interdiction en classe, mais écrit « Ein generelles Verbot kommt für mich nicht in Frage » : une interdiction générale est hors de question, car sa fille a besoin du téléphone sur le trajet.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 5 : un reglement, 4 mcq a trois options ━━━━━━━━━━
        [
            'part' => 5,
            'name' => 'Lesen, Teil 5',
            'type' => 'mcq',
            'title' => 'Goethe B1 · Lesen, Teil 5 — Règlement de la résidence étudiante',
            'description' => "Un règlement intérieur et quatre questions à trois options. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Lis le règlement, puis choisis pour chaque question la bonne réponse : A, B ou C.",
                'passage' => "Hausordnung des Studentenwohnheims Am Mühlgraben (Auszug)\n\n"
                    ."1. Ruhezeiten: täglich von 22 bis 7 Uhr und sonntags den ganzen Tag. Musik und Besuch sind "
                    ."in dieser Zeit nur so laut erlaubt, dass sie im Nachbarzimmer nicht zu hören sind.\n\n"
                    ."2. Küchen: Jede Etage reinigt ihre Küche nach dem Plan am Kühlschrank. Geschirr, das länger "
                    ."als 24 Stunden stehen bleibt, wird in eine Kiste gestellt; die Kiste wird erst nach einer "
                    ."Woche geleert.\n\n"
                    ."3. Besuch: Gäste dürfen bis zu drei Nächte bleiben. Für längeren Besuch ist eine "
                    ."schriftliche Erlaubnis der Verwaltung nötig; in der Prüfungszeit (Februar und Juli) wird "
                    ."sie nicht erteilt.\n\n"
                    ."4. Waschkeller: Waschmaschinen werden über das Online-Formular gebucht. Wer seinen Termin "
                    ."nicht nutzt und nicht bis zwei Stunden vorher absagt, zahlt eine Gebühr von 3 Euro.\n\n"
                    ."5. Fahrräder gehören in den Fahrradkeller, nicht ins Treppenhaus. Räder im Treppenhaus "
                    ."werden nach einer Verwarnung kostenpflichtig entfernt.\n\n"
                    ."6. Schäden im Zimmer sind sofort der Verwaltung zu melden. Wer einen Schaden erst beim "
                    ."Auszug meldet, haftet für die Folgeschäden.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Ein Freund möchte im Juli eine Woche bei Ihnen schlafen. Was gilt?",
                    'options' => [
                        "Sie bräuchten eine schriftliche Erlaubnis, die im Juli nicht erteilt wird.",
                        "Das ist ohne Erlaubnis möglich, weil Gäste beliebig lange bleiben dürfen.",
                        "Gäste dürfen im Wohnheim überhaupt nicht übernachten.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Au-delà de trois nuits, il faut « eine schriftliche Erlaubnis der Verwaltung », et le règlement précise « in der Prüfungszeit (Februar und Juli) wird sie nicht erteilt » : en juillet elle n'est pas accordée. « erteilt » se dit d'une autorisation qu'on délivre.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Sie haben eine Waschmaschine gebucht, werden krank und sagen eine halbe Stunde vorher ab. Was passiert?",
                    'options' => [
                        "Nichts, denn Absagen sind immer kostenlos.",
                        "Sie zahlen 3 Euro, weil Sie zu spät abgesagt haben.",
                        "Sie dürfen vier Wochen keine Maschine mehr buchen.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Le règlement exige d'annuler « bis zwei Stunden vorher », au moins deux heures avant ; une demi-heure est trop tard, d'où la « Gebühr von 3 Euro », les frais de 3 euros.",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Was passiert mit Geschirr, das zwei Tage in der Küche steht?",
                    'options' => [
                        "Es wird sofort weggeworfen.",
                        "Die ganze Etage zahlt eine Gebühr.",
                        "Es kommt in eine Kiste, die erst nach einer Woche geleert wird.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Geschirr, das länger als 24 Stunden stehen bleibt, wird in eine Kiste gestellt; die Kiste wird erst nach einer Woche geleert. » Deux jours, c'est plus de 24 heures, et « erst nach einer Woche » signifie pas avant une semaine.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Seit dem Einzug tropft in Ihrem Zimmer der Wasserhahn. Was sollten Sie laut Hausordnung tun?",
                    'options' => [
                        "Den Schaden sofort der Verwaltung melden.",
                        "Damit bis zum Auszug warten.",
                        "Den Hahn selbst reparieren und die Rechnung einreichen.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Schäden im Zimmer sind sofort der Verwaltung zu melden. » La forme « sind … zu melden » exprime une obligation, et attendre coûte cher : on « haftet für die Folgeschäden », on répond des dégâts qui suivent.",
                ],
            ],
        ],
    ],
];
