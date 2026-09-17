<?php

return [
    'A1' => [
        'mcq' => [
            'title' => 'Allemand A1 · La journée de Mila',
            'description' => 'Entraînement général à la lecture de phrases du quotidien, indépendant de tout examen officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis choisis la réponse qui correspond à chaque information.',
                'text' => 'Ich heiße Mila und wohne in Bonn. Ich arbeite in einem Supermarkt. Meine Arbeit beginnt um neun Uhr. Ich fahre mit dem Bus zur Arbeit. In der Mittagspause esse ich ein Brot und einen Apfel. Am Abend lerne ich mit meinem Freund Deutsch.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'Wo wohnt Mila?',
                    'options' => ['In Bonn', 'In Berlin', 'In Hamburg', 'In Köln'],
                    'correct_answer' => 'A',
                    'explanation' => 'Mila écrit « wohne in Bonn ». Le verbe « wohnen » signifie habiter.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Wo arbeitet Mila?',
                    'options' => ['In einer Schule', 'In einem Hotel', 'In einem Supermarkt', 'In einem Café'],
                    'correct_answer' => 'C',
                    'explanation' => '« Ich arbeite in einem Supermarkt » indique que Mila travaille dans un supermarché.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'Wann beginnt ihre Arbeit?',
                    'options' => ['Um sieben Uhr', 'Um neun Uhr', 'Um acht Uhr', 'Um zehn Uhr'],
                    'correct_answer' => 'B',
                    'explanation' => '« Beginnt um neun Uhr » signifie commence à neuf heures. On utilise « um » pour une heure précise.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'Wie fährt Mila zur Arbeit?',
                    'options' => ['Mit dem Auto', 'Mit dem Fahrrad', 'Mit dem Zug', 'Mit dem Bus'],
                    'correct_answer' => 'D',
                    'explanation' => 'Le texte dit « mit dem Bus zur Arbeit » : elle va au travail en bus.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'Was macht Mila am Abend?',
                    'options' => ['Sie spielt Tennis.', 'Sie lernt Deutsch.', 'Sie arbeitet im Café.', 'Sie kauft einen Apfel.'],
                    'correct_answer' => 'B',
                    'explanation' => '« Am Abend lerne ich ... Deutsch » décrit son activité du soir : apprendre l’allemand.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Allemand A1 · Se présenter au présent',
            'description' => 'Entraînement général à la conjugaison dans de courtes phrases écrites, sans génération IA.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot : le verbe entre parenthèses conjugué au présent.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Ich ___ zwanzig Jahre alt. (sein)', 'correct_answer' => 'bin',
                    'explanation' => 'Avec « ich », « sein » devient « bin ». En allemand, on utilise « sein » pour dire son âge.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'Du ___ eine Schwester. (haben)', 'correct_answer' => 'hast',
                    'explanation' => 'La forme de « haben » avec « du » est « hast ». Le verbe perd le -b dans cette forme.',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'Wir ___ in Berlin. (wohnen)', 'correct_answer' => 'wohnen',
                    'explanation' => 'Avec « wir », le verbe régulier prend -en, comme l’infinitif : « wir wohnen ».',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Er ___ jeden Tag Deutsch. (lernen)', 'correct_answer' => 'lernt',
                    'explanation' => 'Avec « er », un verbe régulier prend -t au présent : lern- + -t donne « lernt ».',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'Ihr ___ heute im Park. (spielen)', 'correct_answer' => 'spielt',
                    'explanation' => 'Avec « ihr », le verbe régulier « spielen » prend la terminaison -t : « ihr spielt ».',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Allemand A1 · Trouver le bon lieu',
            'description' => 'Entraînement général pour associer une situation simple au lieu correspondant.',
            'content' => [
                'instructions' => 'Lis chaque situation et choisis le lieu qui convient. Une seule réponse est attendue par question.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Hier kaufe ich Brot und Brötchen.',
                    'options' => ['Die Schule', 'Die Bäckerei', 'Der Bahnhof', 'Das Kino'],
                    'correct_answer' => 'B',
                    'explanation' => '« Brot und Brötchen » désigne le pain et les petits pains. On les achète à « die Bäckerei », la boulangerie.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'Hier nehme ich den Zug.',
                    'options' => ['Die Bibliothek', 'Das Schwimmbad', 'Die Bäckerei', 'Der Bahnhof'],
                    'correct_answer' => 'D',
                    'explanation' => '« Den Zug nehmen » signifie prendre le train. Le lieu correspondant est « der Bahnhof », la gare.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'Hier sehe ich einen Film auf einer großen Leinwand.',
                    'options' => ['Das Kino', 'Die Schule', 'Die Apotheke', 'Der Bahnhof'],
                    'correct_answer' => 'A',
                    'explanation' => 'Un film sur un grand écran (« auf einer großen Leinwand ») se regarde ici au cinéma : « das Kino ».',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Hier schwimme ich im Wasser.',
                    'options' => ['Die Bäckerei', 'Die Bibliothek', 'Das Schwimmbad', 'Das Kino'],
                    'correct_answer' => 'C',
                    'explanation' => '« Schwimmen » signifie nager. « Das Schwimmbad » est la piscine.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'Hier leihe ich Bücher aus.',
                    'options' => ['Der Bahnhof', 'Die Bibliothek', 'Die Bäckerei', 'Das Schwimmbad'],
                    'correct_answer' => 'B',
                    'explanation' => '« Bücher ausleihen » signifie emprunter des livres. On le fait à la bibliothèque : « die Bibliothek ».',
                ],
            ],
        ],
    ],
    'A2' => [
        'mcq' => [
            'title' => 'Allemand A2 · Une sortie reportée',
            'description' => 'Entraînement général à la lecture d’un message d’organisation, sans lien avec un sujet officiel.',
            'content' => [
                'instructions' => 'Lis le message, puis repère le nouveau programme et les consignes.',
                'text' => 'Hallo Tim, unser Ausflug findet nicht am Samstag, sondern am Sonntag statt, weil es am Samstag stark regnen soll. Wir treffen uns um 10 Uhr vor dem Bahnhof. Bitte bring eine Jacke und etwas zu trinken mit. Wir fahren mit dem Bus zum See und gehen dort spazieren. Gegen 16 Uhr sind wir wieder am Bahnhof. Schreib mir bitte bis Freitag, ob du mitkommst. Viele Grüße, Anna',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'An welchem Tag findet der Ausflug statt?',
                    'options' => ['Am Freitag', 'Am Samstag', 'Am Sonntag', 'Am Montag'],
                    'correct_answer' => 'C',
                    'explanation' => 'La structure « nicht ... sondern ... » corrige une information : pas samedi, mais dimanche.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Warum hat Anna den Tag geändert?',
                    'options' => ['Am Samstag soll es stark regnen.', 'Der Bus fährt am Sonntag nicht.', 'Tim arbeitet am Sonntag.', 'Der Bahnhof ist geschlossen.'],
                    'correct_answer' => 'A',
                    'explanation' => '« Weil » introduit la raison : de fortes pluies sont prévues le samedi (« stark regnen soll »).',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'Wo trifft sich die Gruppe?',
                    'options' => ['Vor Annas Haus', 'Direkt am See', 'Im Café', 'Vor dem Bahnhof'],
                    'correct_answer' => 'D',
                    'explanation' => '« Wir treffen uns ... vor dem Bahnhof » donne le point de rendez-vous : devant la gare.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'Wie fährt die Gruppe zum See?',
                    'options' => ['Mit dem Zug', 'Mit dem Bus', 'Mit dem Auto', 'Mit dem Fahrrad'],
                    'correct_answer' => 'B',
                    'explanation' => 'Le message précise « Wir fahren mit dem Bus zum See ». Le rendez-vous à la gare ne signifie pas que le trajet se fait en train.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'Was soll Tim bis Freitag machen?',
                    'options' => ['Eine Fahrkarte kaufen', 'Eine Jacke für Anna holen', 'Anna schreiben, ob er mitkommt', 'Am Bahnhof warten'],
                    'correct_answer' => 'C',
                    'explanation' => '« Schreib mir bitte bis Freitag, ob du mitkommst » demande à Tim de confirmer par écrit, au plus tard vendredi, s’il participe.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Allemand A2 · Raconter au passé',
            'description' => 'Entraînement général au parfait allemand avec l’auxiliaire déjà donné.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot : le participe passé (Partizip II) du verbe entre parenthèses. L’auxiliaire est déjà écrit.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Gestern habe ich Deutsch ___. (lernen)', 'correct_answer' => 'gelernt',
                    'explanation' => 'Pour le verbe régulier « lernen », le participe passé est ge- + lern- + -t : « gelernt ». Il se place ici en fin de proposition.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'Wir sind am Samstag nach Hause ___. (gehen)', 'correct_answer' => 'gegangen',
                    'explanation' => '« Gehen » a pour participe passé « gegangen ». Avec ce déplacement, le parfait se construit avec « sein », déjà conjugué en « sind ».',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'Sie hat einen Apfel ___. (essen)', 'correct_answer' => 'gegessen',
                    'explanation' => 'Le participe passé irrégulier de « essen » est « gegessen » : « sie hat ... gegessen ».',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Ich habe gestern meine Oma ___. (besuchen)', 'correct_answer' => 'besucht',
                    'explanation' => 'Avec le préfixe inséparable be-, on n’ajoute pas ge-. Le participe passé de « besuchen » est « besucht ».',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'Ihr habt einen Brief ___. (schreiben)', 'correct_answer' => 'geschrieben',
                    'explanation' => '« Schreiben » est irrégulier : son participe passé est « geschrieben », avec -ie- et la terminaison -en.',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Allemand A2 · Les petites annonces utiles',
            'description' => 'Entraînement général pour comprendre le sens pratique de courtes annonces.',
            'content' => [
                'instructions' => 'Lis chaque annonce et choisis la phrase qui en reformule le sens.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Der Aufzug ist kaputt. Bitte benutzen Sie die Treppe.',
                    'options' => ['Der Aufzug funktioniert nicht.', 'Die Treppe ist geschlossen.', 'Der Aufzug ist nur für Kinder.', 'Man muss im Aufzug warten.'],
                    'correct_answer' => 'A',
                    'explanation' => '« Kaputt » signifie cassé ou en panne. La consigne demande donc de prendre les escaliers (« die Treppe »).',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'Bitte geben Sie die Bücher bis Montag zurück.',
                    'options' => ['Man soll am Montag neue Bücher kaufen.', 'Man darf die Bücher behalten.', 'Man soll die Bücher erst nächsten Monat lesen.', 'Man muss die Bücher spätestens am Montag zurückbringen.'],
                    'correct_answer' => 'D',
                    'explanation' => '« Bis Montag » signifie jusqu’à lundi, lundi étant la limite. « Zurückgeben » et « zurückbringen » expriment ici le retour des livres.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'Für diesen Kurs müssen Sie sich vorher anmelden.',
                    'options' => ['Der Kurs ist abgesagt.', 'Man muss sich vor dem Kurs anmelden.', 'Man kann ohne Anmeldung kommen.', 'Der Kurs findet jeden Tag statt.'],
                    'correct_answer' => 'B',
                    'explanation' => '« Sich anmelden » signifie s’inscrire. « Vorher » précise que cette inscription doit se faire avant le cours.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Heute nur Barzahlung möglich.',
                    'options' => ['Heute ist alles kostenlos.', 'Heute kann man nur mit Karte bezahlen.', 'Heute muss man mit Bargeld bezahlen.', 'Heute ist der Laden geschlossen.'],
                    'correct_answer' => 'C',
                    'explanation' => '« Barzahlung » désigne le paiement en espèces. « Nur » signifie seulement : le paiement par carte n’est donc pas proposé aujourd’hui.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'Wegen der Reparatur bleibt das Schwimmbad heute geschlossen.',
                    'options' => ['Das Schwimmbad ist heute länger geöffnet.', 'Man kann heute wegen der Reparatur nicht ins Schwimmbad.', 'Die Reparatur beginnt erst nächste Woche.', 'Heute darf man kostenlos schwimmen.'],
                    'correct_answer' => 'B',
                    'explanation' => '« Wegen der Reparatur » donne la cause : des réparations. « Bleibt ... geschlossen » indique que la piscine reste fermée aujourd’hui.',
                ],
            ],
        ],
    ],
];
