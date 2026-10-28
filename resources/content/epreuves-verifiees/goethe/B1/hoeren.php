<?php

/**
 * Goethe-Zertifikat B1 — module Hoeren, les quatre parties.
 *
 * Ecrit a la main d'apres le format du Modellsatz Erwachsene :
 *  - Teil 1 : cinq courts messages, DEUX items chacun, ecoutes une fois ;
 *  - Teil 2 : une presentation suivie, cinq questions a trois options, une ecoute ;
 *  - Teil 3 : une conversation du quotidien, sept affirmations, une ecoute ;
 *  - Teil 4 : une discussion a trois voix, huit affirmations a attribuer, DEUX ecoutes.
 *
 * Les textes sont originaux. Ce sont les FORMATS, les longueurs et les nombres
 * d'items qui reprennent l'epreuve, pas les contenus de l'organisme.
 *
 * Trois choix a garder si ce fichier est recopie :
 *  - aucun texte d'enregistrement ne figure dans `content.passage`. Il vit dans
 *    `audio_text`, question par question : c'est `prepla:fabriquer-medias` qui
 *    en tire une piste. Mis dans le passage, il serait AFFICHE, et l'exercice
 *    d'ecoute se lirait au lieu de s'ecouter ;
 *  - `answer_options` limite le vrai-faux a DEUX boutons. Sans cette cle, le
 *    lecteur en affiche trois, dont un « Non mentionne » qui n'existe pas ici ;
 *  - l'explication est en francais et dit POURQUOI, en citant le mot allemand
 *    qui tranche. Un « faux » seul n'apprend rien.
 *
 * Reserve sur le Teil 1 : a l'examen, l'item alterne vrai-faux et choix
 * multiple. Notre nomenclature n'a pas de type mixte ; les dix items sont donc
 * ecrits en Richtig/Falsch, et la consigne le dit a l'apprenant.
 */

$teil1Message1 = "Guten Tag, hier ist die Praxis Dr. Walter. Ich rufe wegen Ihres Termins am Donnerstag an. "
    ."Leider ist Frau Dr. Walter am Donnerstag krank, deshalb müssen wir den Termin verschieben. "
    ."Wir haben am Freitag um halb elf noch einen Platz frei. Wenn das nicht passt, rufen Sie uns "
    ."bitte bis morgen Mittag zurück, dann suchen wir einen Termin in der nächsten Woche. "
    ."Unsere Nummer kennen Sie ja. Auf Wiederhören.";

$teil1Message2 = "Hallo Markus, ich bin's, Jana. Ich stehe hier am Hauptbahnhof und mein Zug fällt aus. "
    ."Der nächste fährt erst in einer Stunde, also bin ich nicht um sieben, sondern erst um acht bei dir. "
    ."Fang bitte schon ohne mich an zu kochen, ich habe im Zug etwas gegessen. Und bring den Hund nicht "
    ."zum Bahnhof, der wartet so ungern. Bis später!";

$teil1Message3 = "Liebe Fahrgäste, wir fahren in wenigen Minuten in Mannheim ein. "
    ."Wegen einer Baustelle halten wir heute nicht am Hauptbahnhof, sondern am Bahnhof Mannheim-Friedrichsfeld. "
    ."Von dort fahren Busse im Zehn-Minuten-Takt in die Innenstadt. Reisende nach Heidelberg bleiben bitte "
    ."sitzen, dieser Zug fährt weiter bis Heidelberg. Wir bitten Sie um Verständnis.";

$teil1Message4 = "Guten Abend und willkommen beim Stadttheater. Die Vorstellung heute Abend beginnt "
    ."eine halbe Stunde später als geplant, also um zwanzig Uhr. Der Grund ist ein technisches Problem auf "
    ."der Bühne, das gerade behoben wird. Bis dahin ist das Café im Erdgeschoss für Sie geöffnet. "
    ."Getränke dürfen Sie nicht in den Saal mitnehmen. Vielen Dank für Ihre Geduld.";

$teil1Message5 = "Hier ist die Mailbox der Fahrradwerkstatt Rollrad. Ihr Rad ist fertig, Sie können es "
    ."ab heute abholen. Die Reparatur war günstiger als gedacht: wir mussten nur den Schlauch wechseln, "
    ."nicht den ganzen Reifen, deshalb kostet es achtundzwanzig Euro. Bitte bringen Sie Bargeld mit, "
    ."Karten nehmen wir leider nicht. Wir haben bis achtzehn Uhr offen, am Samstag bis dreizehn Uhr.";

$teil2Vortrag = "Herzlich willkommen im Stadtmuseum Bergheim. Mein Name ist Carla Seibold und ich führe "
    ."Sie heute durch das Haus. Bevor wir starten, einige Hinweise zum Ablauf. Die Führung dauert etwa "
    ."neunzig Minuten, und wir beginnen nicht, wie sonst, im Erdgeschoss, sondern oben im dritten Stock. "
    ."Dort wird nämlich gerade die neue Abteilung aufgebaut, und wir wollen sie sehen, solange das Licht "
    ."noch gut ist. Danach gehen wir Stockwerk für Stockwerk nach unten.\n\n"
    ."Das Haus selbst ist älter als die Sammlung. Es war über hundert Jahre lang eine Textilfabrik, und "
    ."erst seit 1998 ist hier das Museum. Viele Besucher denken, das war früher ein altes Rathaus, aber die "
    ."großen Fenster verraten die Fabrik: die Arbeiterinnen brauchten Tageslicht für ihre Webstühle.\n\n"
    ."Fotografieren ist erlaubt, allerdings ohne Blitz, weil die alten Stoffe im zweiten Stock "
    ."lichtempfindlich sind. Taschen und Rucksäcke lassen Sie bitte unten in den Schränken; einen "
    ."Schlüssel bekommen Sie am Eingang, er kostet nichts, Sie brauchen nur ein Pfand von einem Euro, "
    ."das Sie zurückerhalten.\n\n"
    ."Noch ein Wort zum Café: Es liegt im Innenhof und hat heute bis siebzehn Uhr geöffnet. Wenn Sie nach "
    ."der Führung dort etwas trinken möchten, sagen Sie an der Kasse, dass Sie bei mir in der Gruppe waren, "
    ."dann ist der Kaffee für Sie kostenlos. Und wer noch Fragen hat: stellen Sie sie ruhig unterwegs, "
    ."nicht erst am Ende, denn am Ende stehen wir schon wieder am Ausgang. So, dann folgen Sie mir bitte "
    ."zum Aufzug.";

$teil3Gespraech = "FRAU: Hallo Tobias! Dich habe ich hier ewig nicht gesehen.\n"
    ."MANN: Hallo Nadine. Ja, ich war drei Monate in Leipzig, bei meiner Schwester. Ich habe dort in "
    ."einem Büro gearbeitet, nur für die Zeit.\n"
    ."FRAU: Und jetzt bist du wieder zurück? Für immer?\n"
    ."MANN: Vorläufig ja. Die Stelle war befristet, und verlängern wollte ich nicht. Die Arbeit war in "
    ."Ordnung, aber der Weg dorthin hat jeden Tag fast eine Stunde gedauert. Das war mir am Ende zu viel.\n"
    ."FRAU: Das kenne ich. Ich bin letztes Jahr deshalb umgezogen, näher an die Schule. Jetzt gehe ich "
    ."zu Fuß, zwölf Minuten.\n"
    ."MANN: Beneidenswert. Ich suche gerade etwas hier in der Stadt, am liebsten halbtags, weil ich "
    ."nebenbei weiterstudieren will. Informatik, im Abendstudium.\n"
    ."FRAU: Dann schau mal bei uns an der Schule. Wir brauchen jemanden für die Computerräume, allerdings "
    ."erst ab Januar. Ich kann dir die Ausschreibung schicken, wenn du willst.\n"
    ."MANN: Gern, schick sie mir. Januar wäre sogar gut, vorher habe ich noch Prüfungen.\n"
    ."FRAU: Mach ich heute Abend. Hast du noch dieselbe Nummer?\n"
    ."MANN: Die Nummer ja, aber die Adresse nicht mehr: ich wohne jetzt bei einem Freund in der Lindenstraße.\n"
    ."FRAU: Okay. Und am Samstag? Wir treffen uns alle im Park, Lena hat Geburtstag. Komm doch dazu.\n"
    ."MANN: Samstag kann ich leider nicht, ich arbeite bis sechs im Café. Aber grüß sie von mir, und "
    ."am Sonntag hätte ich Zeit.\n"
    ."FRAU: Dann sagen wir Sonntag. Ich rufe dich an.";

$teil4Diskussion = "MODERATOR: Guten Abend bei „Stadtgespräch“. Unser Thema heute: Soll die Innenstadt "
    ."autofrei werden? Bei mir sitzen Frau Dr. Petra Ahrens, Verkehrsforscherin an der Hochschule, und "
    ."Herr Nils Brandt, Inhaber eines Schuhgeschäfts in der Marktstraße. Frau Ahrens, fangen wir bei "
    ."Ihnen an.\n"
    ."AHRENS: Gern. Die Zahlen sind eindeutig: wo Autos aus engen Straßen verschwinden, kommen die "
    ."Menschen zu Fuß oder mit dem Rad, und sie bleiben länger. In sechs von sieben Städten, die wir "
    ."untersucht haben, ist der Umsatz der Geschäfte nach zwei Jahren gestiegen, nicht gefallen.\n"
    ."BRANDT: Ihre Zahlen bestreite ich nicht, aber sie passen nicht auf jede Stadt. Meine Kundschaft ist "
    ."älter, viele kommen aus den Dörfern ringsum, und mit zwei schweren Taschen nimmt niemand den Bus. "
    ."Ich habe seit der Sperrung im Frühjahr deutlich weniger Kunden.\n"
    ."MODERATOR: Dann wäre die Frage, ob nicht der Bus das Problem ist und nicht die Sperrung.\n"
    ."BRANDT: Der Bus ist ein Teil davon, aber nicht alles. Meine Kunden wollen vor der Tür parken.\n"
    ."AHRENS: Eine Sperrung ohne besseren Nahverkehr ist halbe Arbeit, und halbe "
    ."Arbeit schadet den Händlern tatsächlich. Nur: die Reihenfolge lässt sich kaum umdrehen. Die Busse "
    ."werden erst bezahlt, wenn sichtbar ist, dass die Leute sie brauchen.\n"
    ."MODERATOR: Ich muss gestehen, dass ich selbst jeden Morgen mit dem Auto in die Redaktion fahre, "
    ."und ich finde keinen Parkplatz mehr. Insofern verstehe ich beide Seiten.\n"
    ."BRANDT: Sehen Sie. Und niemand fragt uns Händler vorher. Die Stadt hat die Marktstraße gesperrt "
    ."und uns zwei Wochen vorher einen Brief geschickt. Das ist keine Beteiligung.\n"
    ."AHRENS: Das kritisiere ich auch. In Freiburg hat man zwei Jahre lang mit den Anliegern geplant, und "
    ."am Ende war fast niemand mehr dagegen. Zeit ist hier wichtiger als Geld.\n"
    ."MODERATOR: Ein Vorschlag zum Schluss, in einem Satz?\n"
    ."BRANDT: Erst die Busse, dann die Poller. In dieser Reihenfolge mache ich mit.\n"
    ."AHRENS: Und einen Versuch über einen ganzen Sommer, mit Messungen, statt eine Entscheidung für "
    ."die Ewigkeit.\n"
    ."MODERATOR: Vielen Dank Ihnen beiden.";

return [
    'exam' => 'goethe',
    'level' => 'B1',
    'section' => 'hoeren',
    'parts' => [

        // ━━━━━━━━━━ Teil 1 : Richtig / Falsch, 10 items, cinq messages ━━━━━━━━━━
        [
            'part' => 1,
            'name' => 'Hören, Teil 1',
            'type' => 'true-false',
            'title' => 'Goethe B1 · Hören, Teil 1 — Cinq messages',
            'description' => "Cinq courts messages, deux affirmations chacun : juste ou faux. Une seule écoute, comme à l'examen.",
            'content' => [
                'instructions' => "Tu entends cinq courts messages, chacun UNE SEULE fois. Deux affirmations portent sur chaque message : choisis Richtig (juste) ou Falsch (faux).\n\n"
                    ."À noter : le jour de l'examen, cette partie alterne des affirmations vrai-faux et des questions à choix multiple. Ici, les dix items sont tous en Richtig/Falsch — le travail d'écoute est le même, seule la forme de la réponse est uniforme.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'true-false',
                    'text' => 'Der Termin am Donnerstag findet statt.',
                    'audio_text' => $teil1Message1,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. Le cabinet annonce « müssen wir den Termin verschieben » : le rendez-vous doit être déplacé, parce que la docteure est malade ce jour-là (« am Donnerstag krank »). « verschieben » veut dire reporter.",
                ],
                [
                    'id' => 'q2', 'type' => 'true-false',
                    'text' => 'Man soll zurückrufen, wenn der Freitagstermin nicht passt.',
                    'audio_text' => $teil1Message1,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « Wenn das nicht passt, rufen Sie uns bitte bis morgen Mittag zurück » : si le créneau du vendredi ne convient pas, il faut rappeler. « passen » signifie ici convenir, aller.",
                ],
                [
                    'id' => 'q3', 'type' => 'true-false',
                    'text' => 'Jana kommt später als geplant.',
                    'audio_text' => $teil1Message2,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « nicht um sieben, sondern erst um acht » : pas à sept heures mais seulement à huit. Son train est supprimé (« mein Zug fällt aus »), d'où le retard.",
                ],
                [
                    'id' => 'q4', 'type' => 'true-false',
                    'text' => 'Jana hat Hunger und möchte mit Markus zusammen essen.',
                    'audio_text' => $teil1Message2,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. Elle dit « Fang bitte schon ohne mich an zu kochen, ich habe im Zug etwas gegessen » : elle a déjà mangé dans le train. Le Perfekt « habe gegessen » situe le repas avant l'appel.",
                ],
                [
                    'id' => 'q5', 'type' => 'true-false',
                    'text' => 'Der Zug hält heute nicht am Hauptbahnhof Mannheim.',
                    'audio_text' => $teil1Message3,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « halten wir heute nicht am Hauptbahnhof, sondern am Bahnhof Mannheim-Friedrichsfeld » : l'arrêt est déplacé à cause d'un chantier (« wegen einer Baustelle »). La structure « nicht … sondern » corrige l'information.",
                ],
                [
                    'id' => 'q6', 'type' => 'true-false',
                    'text' => 'Reisende nach Heidelberg müssen in einen Bus umsteigen.',
                    'audio_text' => $teil1Message3,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. « Reisende nach Heidelberg bleiben bitte sitzen, dieser Zug fährt weiter bis Heidelberg » : ils restent assis. Les bus (« Busse … in die Innenstadt ») sont pour ceux qui vont au centre de Mannheim.",
                ],
                [
                    'id' => 'q7', 'type' => 'true-false',
                    'text' => 'Die Vorstellung beginnt um 20 Uhr.',
                    'audio_text' => $teil1Message4,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « eine halbe Stunde später als geplant, also um zwanzig Uhr » : une demi-heure plus tard que prévu, donc à 20 h. Le mot « also » introduit la conclusion chiffrée.",
                ],
                [
                    'id' => 'q8', 'type' => 'true-false',
                    'text' => 'Getränke darf man in den Saal mitnehmen.',
                    'audio_text' => $teil1Message4,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. « Getränke dürfen Sie nicht in den Saal mitnehmen » : c'est justement interdit. Le « nicht » après « dürfen » exprime l'interdiction.",
                ],
                [
                    'id' => 'q9', 'type' => 'true-false',
                    'text' => 'Die Reparatur kostet weniger als erwartet.',
                    'audio_text' => $teil1Message5,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « Die Reparatur war günstiger als gedacht » : moins chère que prévu, car seule la chambre à air a été changée (« nur den Schlauch wechseln, nicht den ganzen Reifen »). « günstig » veut dire avantageux, pas cher.",
                ],
                [
                    'id' => 'q10', 'type' => 'true-false',
                    'text' => 'Man kann mit Karte bezahlen.',
                    'audio_text' => $teil1Message5,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. « Bitte bringen Sie Bargeld mit, Karten nehmen wir leider nicht » : il faut des espèces, la carte n'est pas acceptée. « Bargeld » est l'argent liquide.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 2 : mcq a trois options, 5 items, une presentation ━━━━━━━━━━
        [
            'part' => 2,
            'name' => 'Hören, Teil 2',
            'type' => 'mcq',
            'title' => 'Goethe B1 · Hören, Teil 2 — La visite guidée du musée',
            'description' => "Une présentation suivie, écoutée une fois, puis cinq questions à trois options. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Tu entends UNE SEULE FOIS une guide qui accueille un groupe au musée. Lis d'abord les questions, puis écoute et choisis a, b ou c.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Wo beginnt die Führung?",
                    'audio_text' => $teil2Vortrag,
                    'options' => [
                        "Im Erdgeschoss, wie immer.",
                        "Im dritten Stock.",
                        "Im Innenhof beim Café.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "b. « wir beginnen nicht, wie sonst, im Erdgeschoss, sondern oben im dritten Stock » : la guide corrige l'habitude. « nicht … sondern » écarte justement la réponse a, et le café est évoqué pour la fin.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Was war das Gebäude früher?",
                    'audio_text' => $teil2Vortrag,
                    'options' => [
                        "Ein Rathaus.",
                        "Eine Textilfabrik.",
                        "Eine Schule.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "b. « Es war über hundert Jahre lang eine Textilfabrik » : une usine textile. La guide signale que beaucoup croient à un ancien hôtel de ville (« Viele Besucher denken, das war früher ein altes Rathaus ») — c'est le piège de l'option a.",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Was gilt für das Fotografieren?",
                    'audio_text' => $teil2Vortrag,
                    'options' => [
                        "Es ist überall verboten.",
                        "Es ist erlaubt, aber ohne Blitz.",
                        "Es ist nur im zweiten Stock erlaubt.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "b. « Fotografieren ist erlaubt, allerdings ohne Blitz » : autorisé, mais sans flash, parce que les tissus du deuxième étage sont sensibles à la lumière (« lichtempfindlich »). « allerdings » introduit la restriction.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Was braucht man für einen Schrank?",
                    'audio_text' => $teil2Vortrag,
                    'options' => [
                        "Einen Euro als Pfand, den man zurückbekommt.",
                        "Einen Euro, den man bezahlen muss.",
                        "Nichts, der Schlüssel liegt im Schrank.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "a. « er kostet nichts, Sie brauchen nur ein Pfand von einem Euro, das Sie zurückerhalten » : l'euro est une caution rendue, pas un prix. « Pfand » est la consigne, et « zurückerhalten » dit qu'on la récupère.",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Wann sollen die Besucher Fragen stellen?",
                    'audio_text' => $teil2Vortrag,
                    'options' => [
                        "Erst am Ende der Führung.",
                        "Während der Führung.",
                        "Vorher an der Kasse.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "b. « stellen Sie sie ruhig unterwegs, nicht erst am Ende » : pendant le parcours, car à la fin le groupe est déjà à la sortie. « unterwegs » veut dire en chemin.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 3 : Richtig / Falsch, 7 items, une conversation ━━━━━━━━━━
        [
            'part' => 3,
            'name' => 'Hören, Teil 3',
            'type' => 'true-false',
            'title' => 'Goethe B1 · Hören, Teil 3 — Deux connaissances se revoient',
            'description' => "Une conversation du quotidien, écoutée une fois, puis sept affirmations : juste ou faux. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Tu entends UNE SEULE FOIS une conversation entre Nadine et Tobias, qui se croisent en ville. Pour chaque affirmation, choisis Richtig (juste) ou Falsch (faux).",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'true-false',
                    'text' => 'Tobias hat drei Monate in Leipzig gearbeitet.',
                    'audio_text' => $teil3Gespraech,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « ich war drei Monate in Leipzig … ich habe dort in einem Büro gearbeitet » : trois mois, dans un bureau. Le Perfekt « habe gearbeitet » dit que c'est fini.",
                ],
                [
                    'id' => 'q2', 'type' => 'true-false',
                    'text' => 'Tobias hat die Stelle in Leipzig verloren.',
                    'audio_text' => $teil3Gespraech,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. « Die Stelle war befristet, und verlängern wollte ich nicht » : le poste était à durée déterminée et c'est lui qui n'a pas voulu prolonger. « befristet » signifie limité dans le temps ; personne ne l'a renvoyé.",
                ],
                [
                    'id' => 'q3', 'type' => 'true-false',
                    'text' => 'Der lange Weg zur Arbeit hat Tobias gestört.',
                    'audio_text' => $teil3Gespraech,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « der Weg dorthin hat jeden Tag fast eine Stunde gedauert. Das war mir am Ende zu viel » : presque une heure par jour, et « zu viel » dit clairement que c'était trop pour lui.",
                ],
                [
                    'id' => 'q4', 'type' => 'true-false',
                    'text' => 'Nadine fährt mit dem Bus zur Schule.',
                    'audio_text' => $teil3Gespraech,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. « Jetzt gehe ich zu Fuß, zwölf Minuten » : elle y va à pied depuis son déménagement. « zu Fuß gehen » veut dire marcher.",
                ],
                [
                    'id' => 'q5', 'type' => 'true-false',
                    'text' => 'Tobias sucht eine Stelle für den ganzen Tag.',
                    'audio_text' => $teil3Gespraech,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. « am liebsten halbtags, weil ich nebenbei weiterstudieren will » : il préfère un mi-temps pour pouvoir continuer ses études. « halbtags » s'oppose à une journée entière.",
                ],
                [
                    'id' => 'q6', 'type' => 'true-false',
                    'text' => 'Die Stelle an der Schule ist erst ab Januar frei.',
                    'audio_text' => $teil3Gespraech,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « Wir brauchen jemanden für die Computerräume, allerdings erst ab Januar » : pas avant janvier. Le « erst ab » marque le début repoussé, et cela arrange Tobias à cause de ses examens.",
                ],
                [
                    'id' => 'q7', 'type' => 'true-false',
                    'text' => 'Tobias kommt am Samstag zum Geburtstag in den Park.',
                    'audio_text' => $teil3Gespraech,
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. « Samstag kann ich leider nicht, ich arbeite bis sechs im Café » : il travaille le samedi. Les deux se mettent d'accord sur le dimanche (« Dann sagen wir Sonntag »).",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 4 : matching, 8 items, qui dit quoi ━━━━━━━━━━
        [
            'part' => 4,
            'name' => 'Hören, Teil 4',
            'type' => 'matching',
            'title' => 'Goethe B1 · Hören, Teil 4 — Débat radio : la ville sans voitures',
            'description' => "Une discussion à trois voix, écoutée DEUX fois : pour chacune des huit affirmations, dire qui la prononce. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Tu entends DEUX FOIS un débat radiophonique entre l'animateur, Madame Ahrens (chercheuse) et Monsieur Brandt (commerçant). Pour chaque affirmation, dis qui l'exprime : l'animateur, Madame Ahrens ou Monsieur Brandt.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => "Die Umsätze der Geschäfte sind in den untersuchten Städten gestiegen.",
                    'audio_text' => $teil4Diskussion,
                    'options' => ['Der Moderator', 'Frau Ahrens', 'Herr Brandt'],
                    'correct_answer' => 'B',
                    'explanation' => "Frau Ahrens. Elle cite son étude : « In sechs von sieben Städten … ist der Umsatz der Geschäfte nach zwei Jahren gestiegen ». Le mot « untersucht » (étudié) renvoie à sa recherche, pas au commerçant.",
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => "Seit der Sperrung kommen weniger Kunden ins Geschäft.",
                    'audio_text' => $teil4Diskussion,
                    'options' => ['Der Moderator', 'Frau Ahrens', 'Herr Brandt'],
                    'correct_answer' => 'C',
                    'explanation' => "Herr Brandt. Il parle de son magasin : « Ich habe seit der Sperrung im Frühjahr deutlich weniger Kunden. » Le « ich » et le mot « Kundschaft » désignent le commerçant.",
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => "Ältere Kunden aus den Dörfern können schwere Taschen nicht im Bus tragen.",
                    'audio_text' => $teil4Diskussion,
                    'options' => ['Der Moderator', 'Frau Ahrens', 'Herr Brandt'],
                    'correct_answer' => 'C',
                    'explanation' => "Herr Brandt. « Meine Kundschaft ist älter, viele kommen aus den Dörfern ringsum, und mit zwei schweren Taschen nimmt niemand den Bus. » C'est son objection centrale.",
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => "Vielleicht ist der schlechte Busverkehr das eigentliche Problem.",
                    'audio_text' => $teil4Diskussion,
                    'options' => ['Der Moderator', 'Frau Ahrens', 'Herr Brandt'],
                    'correct_answer' => 'A',
                    'explanation' => "Der Moderator. C'est lui seul qui lance l'idée : « Dann wäre die Frage, ob nicht der Bus das Problem ist und nicht die Sperrung. » Brandt est le piège, mais il la relativise aussitôt (« Der Bus ist ein Teil davon, aber nicht alles ») : il ne fait donc pas du mauvais réseau de bus le vrai problème.",
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => "Die Reihenfolge — erst der Nahverkehr, dann die Sperrung — lässt sich kaum umdrehen.",
                    'audio_text' => $teil4Diskussion,
                    'options' => ['Der Moderator', 'Frau Ahrens', 'Herr Brandt'],
                    'correct_answer' => 'B',
                    'explanation' => "Frau Ahrens. Elle seule dit que l'ordre ne peut pas être inversé : « Nur: die Reihenfolge lässt sich kaum umdrehen. Die Busse werden erst bezahlt, wenn sichtbar ist, dass die Leute sie brauchen. » Herr Brandt est l'intrus tentant, car il parle aussi d'ordre (« Erst die Busse, dann die Poller »), mais c'est pour exiger cet ordre, pas pour dire qu'on ne peut pas le renverser.",
                ],
                [
                    'id' => 'q6', 'type' => 'matching',
                    'text' => "Die Parkplatzsuche in der Innenstadt ist schon heute schwierig.",
                    'audio_text' => $teil4Diskussion,
                    'options' => ['Der Moderator', 'Frau Ahrens', 'Herr Brandt'],
                    'correct_answer' => 'A',
                    'explanation' => "Der Moderator. « Ich muss gestehen, dass ich selbst jeden Morgen mit dem Auto in die Redaktion fahre, und ich finde keinen Parkplatz mehr. » Le mot « Redaktion » (la rédaction) montre qu'il s'agit du journaliste. Herr Brandt est l'intrus tentant, car il parle lui aussi de stationnement (« Meine Kunden wollen vor der Tür parken »), mais c'est un souhait de ses clients, pas le constat d'une place introuvable.",
                ],
                [
                    'id' => 'q7', 'type' => 'matching',
                    'text' => "Die Händler wurden vor der Entscheidung nicht gefragt.",
                    'audio_text' => $teil4Diskussion,
                    'options' => ['Der Moderator', 'Frau Ahrens', 'Herr Brandt'],
                    'correct_answer' => 'C',
                    'explanation' => "Herr Brandt. « niemand fragt uns Händler vorher … uns zwei Wochen vorher einen Brief geschickt. Das ist keine Beteiligung. » Le « uns Händler » le désigne lui et ses collègues.",
                ],
                [
                    'id' => 'q8', 'type' => 'matching',
                    'text' => "Man sollte es einen ganzen Sommer lang versuchen und dabei messen.",
                    'audio_text' => $teil4Diskussion,
                    'options' => ['Der Moderator', 'Frau Ahrens', 'Herr Brandt'],
                    'correct_answer' => 'B',
                    'explanation' => "Frau Ahrens. Sa proposition finale : « einen Versuch über einen ganzen Sommer, mit Messungen, statt eine Entscheidung für die Ewigkeit ». Brandt, lui, propose « Erst die Busse, dann die Poller ».",
                ],
            ],
        ],
    ],
];
