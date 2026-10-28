<?php

/**
 * Goethe-Zertifikat A1 (Start Deutsch 1) — module Hoeren, les trois parties.
 *
 * Ecrit a la main d'apres le Modellsatz Erwachsene, 8e edition, fevrier 2024 :
 *  - Teil 1 : six courts enregistrements du quotidien, une seule ecoute,
 *             trois options illustrees chacun ;
 *  - Teil 2 : quatre annonces publiques, une seule ecoute, Richtig/Falsch ;
 *  - Teil 3 : cinq messages telephoniques ou annonces, deux ecoutes, trois options.
 *
 * Les textes sont originaux. Ce sont les FORMATS, les longueurs et les nombres
 * d'items qui reprennent l'epreuve, pas les contenus de l'organisme.
 *
 * Trois choix a garder si ce fichier est recopie :
 *  - le texte de l'enregistrement vit dans `audio_text`, question par question,
 *    JAMAIS dans `content.passage` : la, il serait affiche, et l'exercice
 *    d'ecoute se lirait au lieu de s'ecouter. La piste est fabriquee ensuite
 *    par `prepla:fabriquer-medias` ;
 *  - `answer_options` limite le vrai-faux a DEUX boutons. Sans cette cle, le
 *    lecteur en affiche trois, dont un « Non mentionne » qui n'existe pas au A1 ;
 *  - l'explication est en francais et dit POURQUOI, en citant le mot allemand
 *    qui tranche. Un apprenant A1 ne tire rien d'un « faux » seul.
 *
 * Le nombre d'ecoutes est annonce dans `content.instructions` de chaque partie :
 * une fois aux Teile 1 et 2, deux fois au Teil 3, comme a l'examen.
 */

return [
    'exam' => 'goethe',
    'level' => 'A1',
    'section' => 'hoeren',
    'parts' => [

        // ━━━━━━━━━━ Teil 1 : choisir la bonne image, 6 items, UNE ecoute ━━━━━━━━━━
        [
            'part' => 1,
            'name' => 'Hören, Teil 1',
            'type' => 'picture-mcq',
            'title' => 'Goethe A1 · Hören, Teil 1 — Courts échanges',
            'description' => "Six courts enregistrements du quotidien, une seule écoute : choisir la bonne réponse parmi trois images. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Tu entends chaque enregistrement UNE SEULE FOIS. Écoute, puis choisis l'image qui convient : a, b ou c.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'picture-mcq',
                    'text' => "Was kauft die Frau?",
                    'audio_text' => "Verkäufer: Guten Tag, was möchten Sie? — Frau: Ich nehme bitte ein Kilo Äpfel. — Verkäufer: Möchten Sie auch Bananen? Die sind heute billig. — Frau: Nein, danke. Nur die Äpfel.",
                    // Les termes cherches dans Wikimedia Commons. Une photographie reelle,
                    // sans filigrane et sans quota, plutot qu'une image fabriquee.
                    'image_queries' => [
                        'apples fruit',
                        'bananas fruit',
                        'wheat bread loaf',
                    ],
                    'options' => ['Äpfel', 'Bananen', 'Brot'],
                    'correct_answer' => 'A',
                    'explanation' => "a. Elle dit « Ich nehme bitte ein Kilo Äpfel » : elle prend des pommes. Aux bananes elle répond « Nein, danke », donc b est écarté, et le pain n'est jamais mentionné.",
                ],
                [
                    'id' => 'q2', 'type' => 'picture-mcq',
                    'text' => "Wie fährt der Mann zur Arbeit?",
                    'audio_text' => "Frau: Nimmst du heute das Auto? — Mann: Nein, mein Auto ist kaputt. Ich fahre mit dem Fahrrad. — Frau: Und der Bus? — Mann: Der Bus ist zu langsam.",
                    // Les termes cherches dans Wikimedia Commons. Une photographie reelle,
                    // sans filigrane et sans quota, plutot qu'une image fabriquee.
                    'image_queries' => [
                        'red automobile parked',
                        'bicycle leaning wall',
                        'blue city bus',
                    ],
                    'options' => ['mit dem Auto', 'mit dem Fahrrad', 'mit dem Bus'],
                    'correct_answer' => 'B',
                    'explanation' => "b. « Ich fahre mit dem Fahrrad » : il y va à vélo. La voiture est « kaputt » (en panne) et le bus « zu langsam » (trop lent) : les deux sont refusés à voix haute.",
                ],
                [
                    'id' => 'q3', 'type' => 'picture-mcq',
                    'text' => "Wann kommt der Zug?",
                    'audio_text' => "Mann: Entschuldigung, wann kommt der Zug nach Hamburg? — Frau: Um halb neun. — Mann: Also um acht Uhr? — Frau: Nein, um halb neun, also um acht Uhr dreißig.",
                    'options' => ['um 8.00 Uhr', 'um 8.30 Uhr', 'um 9.00 Uhr'],
                    'correct_answer' => 'B',
                    'explanation' => "b. « halb neun » ne veut pas dire neuf heures et demie : en allemand, c'est la demi-heure AVANT neuf, donc 8 h 30. La femme le répète elle-même : « um acht Uhr dreißig ».",
                ],
                [
                    'id' => 'q4', 'type' => 'picture-mcq',
                    'text' => "Wo ist die Apotheke?",
                    'audio_text' => "Frau: Entschuldigung, wo ist hier eine Apotheke? — Mann: Gehen Sie hier rechts. Die Apotheke ist neben der Post. — Frau: Neben dem Supermarkt? — Mann: Nein, neben der Post.",
                    'options' => ['neben der Post', 'neben dem Supermarkt', 'neben dem Bahnhof'],
                    'correct_answer' => 'A',
                    'explanation' => "a. « neben der Post » : à côté de la poste. L'homme corrige la femme avec « Nein, neben der Post », ce qui élimine le supermarché.",
                ],
                [
                    'id' => 'q5', 'type' => 'picture-mcq',
                    'text' => "Was macht das Kind am Samstag?",
                    'audio_text' => "Mutter: Spielst du am Samstag Fußball? — Kind: Nein, am Samstag gehe ich schwimmen. Fußball spiele ich am Sonntag. — Mutter: Gut, dann nimm dein Handtuch mit.",
                    // Les termes cherches dans Wikimedia Commons. Une photographie reelle,
                    // sans filigrane et sans quota, plutot qu'une image fabriquee.
                    'image_queries' => [
                        'child playing football',
                        'child swimming pool',
                        'boy bicycle riding',
                    ],
                    'options' => ['Fußball spielen', 'schwimmen gehen', 'Fahrrad fahren'],
                    'correct_answer' => 'B',
                    'explanation' => "b. « am Samstag gehe ich schwimmen » : samedi, il va nager. Le football, c'est « am Sonntag », dimanche. Le mot « Handtuch » (serviette) confirme la piscine.",
                ],
                [
                    'id' => 'q6', 'type' => 'picture-mcq',
                    'text' => "Wie ist das Wetter morgen?",
                    'audio_text' => "Radio: Und nun das Wetter. Heute ist es noch sonnig und warm. Morgen regnet es den ganzen Tag. Nehmen Sie bitte einen Regenschirm mit. Schnee gibt es erst im Dezember.",
                    // Les termes cherches dans Wikimedia Commons. Une photographie reelle,
                    // sans filigrane et sans quota, plutot qu'une image fabriquee.
                    'image_queries' => [
                        'sun clear sky',
                        'rain umbrella',
                        'snow covered street',
                    ],
                    'options' => ['sonnig', 'Regen', 'Schnee'],
                    'correct_answer' => 'B',
                    'explanation' => "b. « Morgen regnet es den ganzen Tag » : demain, il pleut. Le soleil, c'est pour « heute » (aujourd'hui), et la neige seulement « im Dezember ». La question porte sur demain.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 2 : Richtig / Falsch sur des annonces, 4 items, UNE ecoute ━━━━━━━━━━
        [
            'part' => 2,
            'name' => 'Hören, Teil 2',
            'type' => 'true-false',
            'title' => 'Goethe A1 · Hören, Teil 2 — Annonces publiques',
            'description' => "Quatre annonces publiques (gare, aéroport, magasin), une seule écoute : l'affirmation est-elle juste ou fausse ? Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Tu entends chaque annonce UNE SEULE FOIS. Écoute, puis dis si l'affirmation est juste (Richtig) ou fausse (Falsch).",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'true-false',
                    'text' => "Der Zug nach München fährt von Gleis 7 ab.",
                    'audio_text' => "Achtung, eine Information für die Reisenden nach München. Der Intercity um 14 Uhr 20 fährt heute nicht von Gleis 7, sondern von Gleis 9. Ich wiederhole: von Gleis 9.",
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. L'annonce dit « nicht von Gleis 7, sondern von Gleis 9 » : le train part de la voie 9. La construction « nicht … sondern » corrige l'information qui précède.",
                ],
                [
                    'id' => 'q2', 'type' => 'true-false',
                    'text' => "Das Flugzeug nach Wien hat Verspätung.",
                    'audio_text' => "Guten Abend, eine Durchsage für die Passagiere nach Wien. Der Flug LH 1208 startet nicht um 19 Uhr. Wir fliegen eine Stunde später, um 20 Uhr. Wir bitten um Entschuldigung.",
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « eine Stunde später, um 20 Uhr » : le vol part une heure plus tard que prévu. « später » (plus tard) est exactement ce que veut dire « Verspätung », le retard.",
                ],
                [
                    'id' => 'q3', 'type' => 'true-false',
                    'text' => "Der Supermarkt öffnet morgen um 8 Uhr.",
                    'audio_text' => "Liebe Kundinnen und Kunden, unser Markt schließt in zehn Minuten. Bitte kommen Sie jetzt zur Kasse. Morgen sind wir ab 8 Uhr wieder für Sie da. Vielen Dank für Ihren Einkauf.",
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Richtig',
                    'explanation' => "Juste. « Morgen sind wir ab 8 Uhr wieder für Sie da » : demain, le magasin est de nouveau là à partir de 8 heures, donc il ouvre à 8 heures. « ab 8 Uhr … wieder da » est la manière courante de dire l'heure d'ouverture, sans employer le verbe « öffnen ». Le piège est de retenir « schließt in zehn Minuten » et de répondre « Falsch » : cette phrase parle de la fermeture de ce soir, pas de l'ouverture de demain.",
                ],
                [
                    'id' => 'q4', 'type' => 'true-false',
                    'text' => "Das Schwimmbad ist heute geöffnet.",
                    'audio_text' => "Hier ist das Stadtbad Nordpark. Heute ist das Schwimmbad leider geschlossen. Wir haben ein Problem mit dem Wasser. Ab Donnerstag können Sie wieder schwimmen. Danke für Ihr Verständnis.",
                    'answer_options' => ['Richtig', 'Falsch'],
                    'correct_answer' => 'Falsch',
                    'explanation' => "Faux. « Heute ist das Schwimmbad leider geschlossen » : aujourd'hui la piscine est fermée. « geschlossen » est le contraire de « geöffnet » ; on rouvre « ab Donnerstag », à partir de jeudi.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 3 : a, b ou c, 5 items, DEUX ecoutes ━━━━━━━━━━
        [
            'part' => 3,
            'name' => 'Hören, Teil 3',
            'type' => 'mcq',
            'title' => 'Goethe A1 · Hören, Teil 3 — Messages téléphoniques',
            'description' => "Cinq messages téléphoniques ou annonces, écoutés deux fois : choisir la bonne réponse parmi trois. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Tu entends chaque message DEUX FOIS. Écoute, puis choisis la bonne réponse : a, b ou c.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Wann soll Frau Berger zum neuen Termin kommen?",
                    'audio_text' => "Guten Tag, Frau Berger, hier ist die Praxis Doktor Lang. Ihr Termin am Montag um 10 Uhr ist leider nicht möglich. Können Sie am Mittwoch um 10 Uhr kommen? Bitte rufen Sie uns zurück. Auf Wiederhören.",
                    'options' => ['am Montag', 'am Mittwoch', 'am Freitag'],
                    'correct_answer' => 'B',
                    'explanation' => "b. « Können Sie am Mittwoch um 10 Uhr kommen? » : le nouveau rendez-vous est proposé le mercredi. Le lundi « ist leider nicht möglich », n'est plus possible.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Was soll Jonas mitbringen?",
                    'audio_text' => "Hallo Jonas, hier ist Mira. Wir machen heute Abend ein Picknick im Park. Ich habe schon Brot und Käse. Kannst du bitte Wasser mitbringen? Kuchen brauchen wir nicht, Lukas backt einen. Bis später!",
                    'options' => ['Brot', 'Wasser', 'Kuchen'],
                    'correct_answer' => 'B',
                    'explanation' => "b. « Kannst du bitte Wasser mitbringen? » : on lui demande de l'eau. Le pain, Mira l'a déjà (« Ich habe schon Brot »), et pour le gâteau elle dit « brauchen wir nicht ».",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Wo treffen sich die Freunde?",
                    'audio_text' => "Hi Sofia, hier ist Ben. Wir treffen uns morgen nicht im Café, das ist zu klein. Wir treffen uns direkt vor dem Kino, um sieben. Zum Restaurant gehen wir vielleicht nach dem Film. Tschüss!",
                    'options' => ['im Café', 'vor dem Kino', 'im Restaurant'],
                    'correct_answer' => 'B',
                    'explanation' => "b. « Wir treffen uns direkt vor dem Kino » : devant le cinéma. Le café est écarté par « nicht im Café », et le restaurant n'est qu'une possibilité « nach dem Film », après le film.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Wie viel kostet die Fahrkarte für ein Kind von zehn Jahren?",
                    'audio_text' => "Willkommen bei der Stadtbahn Freiburg. Eine Fahrkarte für Erwachsene kostet drei Euro. Kinder bis zwölf Jahre zahlen einen Euro fünfzig. Kinder unter sechs Jahren fahren kostenlos.",
                    'options' => ['3,00 Euro', '1,50 Euro', 'nichts'],
                    'correct_answer' => 'B',
                    'explanation' => "b. « Kinder bis zwölf Jahre zahlen einen Euro fünfzig » : un enfant de dix ans entre dans « bis zwölf Jahre », donc il paie 1,50 euro. Les 3 euros sont le tarif « für Erwachsene ». L'intrus le plus tentant est « nichts », la gratuité : elle ne vaut que « unter sechs Jahren », pour les moins de six ans, et dix ans n'est pas en dessous de six.",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Warum ruft Herr Keller an?",
                    'audio_text' => "Guten Morgen, hier spricht Keller von der Firma Sonnblick. Ich kann heute nicht zur Arbeit kommen, ich bin krank. Ich gehe jetzt zum Arzt. Morgen bin ich hoffentlich wieder da. Danke.",
                    'options' => ['Er ist krank.', 'Er hat Urlaub.', 'Er kommt später.'],
                    'correct_answer' => 'A',
                    'explanation' => "a. « ich bin krank » : il est malade, et il ajoute « Ich gehe jetzt zum Arzt », il va chez le médecin. Il ne dit ni « Urlaub » (congé) ni qu'il arrivera plus tard : il ne vient pas du tout aujourd'hui.",
                ],
            ],
        ],
    ],
];
