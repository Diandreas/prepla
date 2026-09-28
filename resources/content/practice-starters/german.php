<?php

return [
    'A1' => [
        'mcq' => [
            'title' => 'Allemand A1 · La journée de Mila',
            'description' => 'Entraînement général à la lecture de phrases du quotidien, indépendant de tout examen officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis choisis la réponse qui correspond à chaque information.',
                'passage' => 'Ich heiße Mila und wohne in Bonn. Ich arbeite in einem Supermarkt. Meine Arbeit beginnt um neun Uhr. Ich fahre mit dem Bus zur Arbeit. In der Mittagspause esse ich ein Brot und einen Apfel. Am Abend lerne ich mit meinem Freund Deutsch.',
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
                'passage' => 'Hallo Tim, unser Ausflug findet nicht am Samstag, sondern am Sonntag statt, weil es am Samstag stark regnen soll. Wir treffen uns um 10 Uhr vor dem Bahnhof. Bitte bring eine Jacke und etwas zu trinken mit. Wir fahren mit dem Bus zum See und gehen dort spazieren. Gegen 16 Uhr sind wir wieder am Bahnhof. Schreib mir bitte bis Freitag, ob du mitkommst. Viele Grüße, Anna',
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
    'B1' => [
        'mcq' => [
            'title' => 'Allemand B1 · Le télétravail en question',
            'description' => 'Entraînement général à la lecture d’un texte d’opinion, indépendant de tout examen officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis choisis la réponse qui correspond à ce qui est dit — pas à ce que tu penses toi-même.',
                'passage' => 'Seit einigen Jahren arbeiten viele Angestellte teilweise von zu Hause aus. Eine Umfrage unter 800 Beschäftigten zeigt ein gemischtes Bild. Rund zwei Drittel geben an, sich zu Hause besser konzentrieren zu können, weil die ständigen Unterbrechungen im Büro wegfallen. Gleichzeitig berichtet fast die Hälfte, dass ihnen der spontane Austausch mit Kolleginnen und Kollegen fehlt. Besonders jüngere Beschäftigte, die erst seit Kurzem im Betrieb sind, fühlen sich schlechter eingearbeitet. Die Geschäftsführung plant deshalb kein vollständiges Homeoffice, sondern ein festes Modell: zwei Tage zu Hause, drei Tage im Büro. Wer diese Regelung nicht einhalten kann, soll das frühzeitig mit der Teamleitung besprechen.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'Was ist das Hauptergebnis der Umfrage?',
                    'options' => [
                        'Die Beschäftigten lehnen das Homeoffice ab.',
                        'Die Beschäftigten sehen Vor- und Nachteile.',
                        'Alle Beschäftigten wollen nur noch zu Hause arbeiten.',
                        'Die Umfrage brachte kein verwertbares Ergebnis.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'Le texte annonce « ein gemischtes Bild » : deux tiers se concentrent mieux, mais près de la moitié regrette les échanges. Les deux faces sont rapportées, donc ni rejet ni adhésion totale.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Warum können sich viele zu Hause besser konzentrieren?',
                    'options' => [
                        'Weil sie dort weniger Aufgaben haben.',
                        'Weil die Teamleitung sie nicht kontrolliert.',
                        'Weil die ständigen Unterbrechungen im Büro wegfallen.',
                        'Weil sie zu Hause längere Pausen machen.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'La proposition en « weil » donne la cause exacte : « weil die ständigen Unterbrechungen im Büro wegfallen ». Les autres explications ne figurent pas dans le texte.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'Welche Gruppe fühlt sich schlechter eingearbeitet?',
                    'options' => [
                        'Die Teamleitungen',
                        'Die Beschäftigten mit Kindern',
                        'Die ältesten Angestellten',
                        'Die neuen, jüngeren Beschäftigten',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => 'Le texte précise « besonders jüngere Beschäftigte, die erst seit Kurzem im Betrieb sind ». Ce sont bien les nouveaux venus, pas les plus âgés.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'Was plant die Geschäftsführung?',
                    'options' => [
                        'Ein festes Modell mit zwei Tagen zu Hause',
                        'Vollständiges Homeoffice für alle',
                        'Die Rückkehr aller ins Büro',
                        'Eine zweite Umfrage im nächsten Jahr',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'La tournure « kein ... , sondern ... » écarte le télétravail complet et pose la règle : deux jours à la maison, trois au bureau.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'Was soll jemand tun, der die Regelung nicht einhalten kann?',
                    'options' => [
                        'Eine schriftliche Beschwerde einreichen',
                        'Frühzeitig mit der Teamleitung sprechen',
                        'Die Umfrage noch einmal ausfüllen',
                        'Ohne Rückmeldung zu Hause bleiben',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'La dernière phrase le dit : « soll das frühzeitig mit der Teamleitung besprechen ». « Frühzeitig » veut dire sans attendre.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Allemand B1 · Subordonnées et rejet du verbe',
            'description' => 'Entraînement général aux conjonctions qui envoient le verbe à la fin.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot : la conjonction ou le verbe demandé. Attention à la place du verbe.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Ich bleibe heute zu Hause, ___ ich krank bin.',
                    'correct_answer' => 'weil',
                    'explanation' => 'La cause s’introduit par « weil », qui rejette le verbe conjugué à la fin : « ... weil ich krank bin ».',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'Sie fragt mich, ___ ich am Wochenende Zeit habe.',
                    'correct_answer' => 'ob',
                    'explanation' => 'Une question indirecte sans mot interrogatif s’introduit par « ob » (si). Le verbe « habe » passe en fin de proposition.',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'Als ich klein war, ___ ich jeden Sommer ans Meer. (fahren)',
                    'correct_answer' => 'fuhr',
                    'explanation' => 'Après la subordonnée en « als », la principale commence par le verbe conjugué. Au prétérit, « fahren » donne « fuhr ».',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Wenn ich mehr Zeit ___, würde ich ein Instrument lernen. (haben)',
                    'correct_answer' => 'hätte',
                    'explanation' => 'L’irréel du présent demande le Konjunktiv II. « Haben » donne « hätte », en écho à « würde » dans la principale.',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'Obwohl es stark geregnet ___, sind wir spazieren gegangen. (haben)',
                    'correct_answer' => 'hat',
                    'explanation' => 'Dans une subordonnée en « obwohl », l’auxiliaire se place tout à la fin, après le participe : « ... geregnet hat ».',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Allemand B1 · Reformuler une consigne',
            'description' => 'Entraînement général à saisir l’intention d’un message professionnel ou administratif.',
            'content' => [
                'instructions' => 'Lis chaque message et choisis la phrase qui en rend le mieux le sens.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Die Anmeldung ist nur bis zum 15. März möglich. Spätere Anträge werden nicht berücksichtigt.',
                    'options' => [
                        'Nach dem 15. März kann man sich nicht mehr anmelden.',
                        'Die Anmeldung beginnt am 15. März.',
                        'Späte Anträge kosten mehr Geld.',
                        'Der Termin kann verschoben werden.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Nur bis zum » fixe une limite, et « werden nicht berücksichtigt » signifie que les demandes tardives ne sont pas examinées du tout.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'Bei Fragen wenden Sie sich bitte direkt an Ihre Sachbearbeiterin, nicht an die Zentrale.',
                    'options' => [
                        'Fragen werden nur schriftlich beantwortet.',
                        'Die Zentrale ist derzeit geschlossen.',
                        'Man soll seine Fragen der zuständigen Person stellen.',
                        'Es gibt keine Ansprechpartner mehr.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« Sich wenden an » veut dire s’adresser à. Le message désigne la personne compétente et écarte le standard.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'Der Kurs findet statt, sofern sich mindestens acht Personen anmelden.',
                    'options' => [
                        'Der Kurs ist bereits ausgebucht.',
                        'Der Kurs findet nur bei genügend Anmeldungen statt.',
                        'Acht Personen haben sich schon angemeldet.',
                        'Der Kurs wurde abgesagt.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Sofern » pose une condition : le cours n’a lieu que si le seuil de huit inscrits est atteint. Rien ne dit qu’il l’est déjà.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Wir bitten Sie, den Raum nach der Nutzung so zu hinterlassen, wie Sie ihn vorgefunden haben.',
                    'options' => [
                        'Der Raum muss renoviert werden.',
                        'Der Raum darf nicht genutzt werden.',
                        'Man soll den Raum aufgeräumt zurücklassen.',
                        'Die Nutzung ist kostenpflichtig.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« So ... wie Sie ihn vorgefunden haben » demande de rendre la salle dans l’état où on l’a trouvée : donc rangée.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'Die Lieferung verzögert sich voraussichtlich um eine Woche. Wir bedauern die Unannehmlichkeiten.',
                    'options' => [
                        'Die Lieferung kommt wahrscheinlich später als geplant.',
                        'Die Bestellung wurde storniert.',
                        'Die Lieferung ist bereits angekommen.',
                        'Der Kunde muss eine Woche warten, bevor er bestellt.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Sich verzögern » signifie prendre du retard, et « voraussichtlich » marque une prévision, pas une certitude.',
                ],
            ],
        ],
    ],
    'B2' => [
        'mcq' => [
            'title' => 'Allemand B2 · Ce que mesure vraiment une note',
            'description' => 'Entraînement général à la lecture argumentative, indépendant de tout examen officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis distingue ce que l’auteur affirme de ce qu’il rapporte ou nuance.',
                'passage' => 'Noten gelten als objektives Maß für Leistung. Diese Annahme hält einer genaueren Prüfung jedoch kaum stand. Mehrere Studien haben Lehrkräften identische Aufsätze vorgelegt und lediglich den Namen der Verfasserin oder des Verfassers verändert. Die Bewertungen fielen messbar unterschiedlich aus. Daraus zu schließen, Noten seien wertlos, wäre allerdings voreilig: Sie erfüllen eine Ordnungsfunktion, auf die weder Schulen noch Hochschulen bislang verzichten können. Problematisch wird es erst dort, wo eine einzelne Zahl als abschließendes Urteil über eine Person gelesen wird. Wer Leistung fair beurteilen will, kommt deshalb um mehrere, voneinander unabhängige Rückmeldungen nicht herum — auch wenn dieser Weg ungleich aufwendiger ist.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'Welche Annahme stellt der Text infrage?',
                    'options' => [
                        'Dass Lehrkräfte zu streng bewerten',
                        'Dass Noten ein objektives Maß für Leistung sind',
                        'Dass Aufsätze schwer zu korrigieren sind',
                        'Dass Hochschulen zu viele Prüfungen verlangen',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'La première phrase pose l’idée reçue, la deuxième la met en cause : « Diese Annahme hält ... kaum stand ». C’est bien l’objectivité des notes qui est visée.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Was zeigen die erwähnten Studien?',
                    'options' => [
                        'Der Name der verfassenden Person beeinflusst die Bewertung.',
                        'Längere Aufsätze werden besser bewertet.',
                        'Lehrkräfte bewerten untereinander sehr einheitlich.',
                        'Anonyme Korrekturen dauern deutlich länger.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'Le protocole décrit ne fait varier qu’une seule chose — le nom — et les notes diffèrent malgré des copies identiques. La longueur ou la durée ne sont pas évoquées.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'Wie bewertet der Text die Schlussfolgerung, Noten seien wertlos?',
                    'options' => [
                        'Er teilt sie ausdrücklich.',
                        'Er hält sie für voreilig.',
                        'Er hält sie für bewiesen.',
                        'Er geht darauf nicht ein.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Wäre allerdings voreilig » signale un contre-argument : l’auteur refuse d’aller jusque-là. « Allerdings » marque la restriction.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'Wann werden Noten laut Text wirklich problematisch?',
                    'options' => [
                        'Wenn sie zu selten vergeben werden',
                        'Wenn sie von mehreren Personen vergeben werden',
                        'Wenn eine einzelne Zahl als endgültiges Urteil gilt',
                        'Wenn sie erst am Ende des Jahres mitgeteilt werden',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« Problematisch wird es erst dort, wo ... » délimite précisément le cas : une note unique lue comme un verdict définitif sur la personne.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'Welche Haltung nimmt der Text zu mehreren unabhängigen Rückmeldungen ein?',
                    'options' => [
                        'Er empfiehlt sie, räumt aber den höheren Aufwand ein.',
                        'Er lehnt sie als unrealistisch ab.',
                        'Er hält sie für billiger als Noten.',
                        'Er erwähnt sie nur als Randbemerkung.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Kommt ... nicht herum » exprime une nécessité, et la concession « auch wenn dieser Weg ungleich aufwendiger ist » reconnaît le coût. Recommandation assortie d’une réserve.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Allemand B2 · Connecteurs et voix passive',
            'description' => 'Entraînement général aux articulations logiques et aux constructions de l’écrit soutenu.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot. Cherche d’abord quel lien logique ou quelle construction la phrase exige.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Der Antrag wurde abgelehnt, ___ er zu spät eingereicht worden war.',
                    'correct_answer' => 'weil',
                    'explanation' => 'La subordonnée explique le rejet : « weil » introduit la cause et renvoie le verbe conjugué « war » en fin de proposition.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'Das Gebäude ___ im letzten Jahr vollständig renoviert. (werden)',
                    'correct_answer' => 'wurde',
                    'explanation' => 'Passif au prétérit : auxiliaire « werden » conjugué — « wurde » — plus le participe « renoviert ». Le bâtiment subit l’action.',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'Je gründlicher man sich vorbereitet, ___ ruhiger bleibt man in der Prüfung.',
                    'correct_answer' => 'desto',
                    'explanation' => 'La corrélation proportionnelle se construit « je + comparatif ..., desto + comparatif ... ». « Desto » ouvre la principale avec inversion.',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Die Ergebnisse sind eindeutig; ___ bleibt die Kritik bestehen.',
                    'correct_answer' => 'dennoch',
                    'explanation' => '« Dennoch » marque la concession — malgré des résultats clairs, la critique demeure — et occupe la première place, d’où l’inversion « bleibt die Kritik ».',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'Er tut so, als ___ er alles schon verstanden.',
                    'correct_answer' => 'hätte',
                    'explanation' => 'Après « als ob » ou « als » à valeur irréelle, on emploie le Konjunktiv II : « als hätte er ... », avec le verbe juste après « als ».',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Allemand B2 · Ce que le ton laisse entendre',
            'description' => 'Entraînement général à l’interprétation d’énoncés indirects ou prudents.',
            'content' => [
                'instructions' => 'Lis chaque énoncé et choisis ce qu’il veut réellement dire, au-delà des mots.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Ihr Vorschlag ist sicher gut gemeint, greift aber zu kurz.',
                    'options' => [
                        'Der Vorschlag wird als unzureichend abgelehnt.',
                        'Der Vorschlag wird vollständig übernommen.',
                        'Der Vorschlag war böswillig.',
                        'Der Vorschlag kam zu spät.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'La formule concède l’intention (« gut gemeint ») pour mieux rejeter le fond : « zu kurz greifen » veut dire ne pas aller assez loin.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'Wir sollten diesen Punkt zu einem späteren Zeitpunkt noch einmal aufgreifen.',
                    'options' => [
                        'Der Punkt ist endgültig entschieden.',
                        'Der Punkt wird jetzt nicht weiter behandelt.',
                        'Der Punkt war nie Teil der Tagesordnung.',
                        'Der Punkt muss sofort geklärt werden.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'Renvoyer à plus tard est une manière courtoise de clore la discussion pour l’instant, sans trancher le fond.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'Es dürfte schwierig werden, den Termin noch zu halten.',
                    'options' => [
                        'Der Termin ist bereits abgesagt.',
                        'Der Termin wird sicher eingehalten.',
                        'Der Termin wird vermutlich nicht eingehalten.',
                        'Der Termin wurde vorverlegt.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« Dürfte » exprime une supposition prudente : ce n’est pas encore annulé, mais l’échéance est probablement compromise.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Man kann durchaus geteilter Meinung sein, ob dieser Aufwand gerechtfertigt war.',
                    'options' => [
                        'Alle sind sich über den Aufwand einig.',
                        'Der Aufwand war eindeutig gerechtfertigt.',
                        'Der Aufwand wurde nie erfasst.',
                        'Ob sich der Aufwand gelohnt hat, ist umstritten.',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => '« Geteilter Meinung sein » signifie être partagé. L’énoncé installe un désaccord au lieu de trancher.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'Für eine abschließende Bewertung ist es noch zu früh.',
                    'options' => [
                        'Eine endgültige Beurteilung ist noch nicht möglich.',
                        'Die Bewertung liegt bereits vor.',
                        'Eine Bewertung ist grundsätzlich unmöglich.',
                        'Die Bewertung fiel negativ aus.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Noch zu früh » diffère le jugement sans l’exclure : on manque d’éléments pour l’instant, rien de plus.',
                ],
            ],
        ],
    ],
    'C1' => [
        'mcq' => [
            'title' => 'Allemand C1 · L’oubli comme fonction',
            'description' => 'Entraînement général à la lecture d’un texte de vulgarisation savante, indépendant de tout examen officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis repère la thèse, les réserves et ce qui reste ouvert.',
                'passage' => 'Das Vergessen genießt einen denkbar schlechten Ruf. Es gilt als Ausfall, als Defizit eines Systems, das eigentlich speichern sollte. Neuere Arbeiten der Gedächtnisforschung legen indes einen anderen Schluss nahe: Vergessen ist keine Panne, sondern eine Leistung. Ein Gedächtnis, das alles bewahrte, wäre nicht etwa vollkommen, sondern unbrauchbar — es könnte Wesentliches nicht mehr von Beiläufigem trennen. Entscheidend ist demnach nicht, wie viel gespeichert wird, sondern wie zuverlässig Unwichtiges abgeräumt wird. Diese Sichtweise ist allerdings nicht unwidersprochen geblieben. Kritiker wenden ein, dass sich aus der Nützlichkeit eines Vorgangs nicht ohne Weiteres auf seine Funktion schließen lässt; manches Vergessen bleibt schlicht Verlust. Unstrittig ist immerhin, dass sich die Frage nicht mehr sinnvoll stellen lässt, ohne das Vergessen als eigenständigen Vorgang ernst zu nehmen.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'Welche These vertreten die neueren Arbeiten?',
                    'options' => [
                        'Vergessen ist ein Defekt des Gedächtnisses.',
                        'Vergessen ist eine eigenständige Leistung.',
                        'Vergessen lässt sich durch Training abstellen.',
                        'Vergessen betrifft nur beiläufige Inhalte.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Keine Panne, sondern eine Leistung » oppose explicitement les deux lectures. L’adverbe « indes » signale le renversement par rapport à la réputation initiale.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Warum wäre ein Gedächtnis, das alles bewahrt, laut Text unbrauchbar?',
                    'options' => [
                        'Weil es zu viel Energie verbrauchen würde',
                        'Weil es Erinnerungen verfälschen würde',
                        'Weil es Wesentliches nicht mehr von Beiläufigem trennen könnte',
                        'Weil es nur kurzfristig funktionieren würde',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'La raison est donnée mot pour mot après le tiret. Le problème n’est pas la capacité mais la perte de hiérarchie entre les contenus.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'Worauf kommt es dieser Sichtweise zufolge an?',
                    'options' => [
                        'Auf die Menge des Gespeicherten',
                        'Auf die Geschwindigkeit des Abrufs',
                        'Auf die Zuverlässigkeit, mit der Unwichtiges abgeräumt wird',
                        'Auf die Länge der Wiederholungsintervalle',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'La structure « nicht ..., sondern ... » écarte la quantité au profit du tri. C’est le critère que le texte retient.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'Welchen Einwand führen die Kritiker an?',
                    'options' => [
                        'Aus der Nützlichkeit eines Vorgangs folgt nicht schon seine Funktion.',
                        'Die Studien wurden an zu wenigen Personen durchgeführt.',
                        'Vergessen lässt sich messtechnisch nicht erfassen.',
                        'Die Forschung ignoriert ältere Erkenntnisse.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'L’objection est d’ordre logique : un glissement de l’utile au fonctionnel. Aucune critique de méthode ou d’échantillon n’est formulée.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'Was gilt am Ende als unstrittig?',
                    'options' => [
                        'Dass Vergessen immer ein Verlust ist',
                        'Dass die Kritiker widerlegt sind',
                        'Dass Vergessen als eigenständiger Vorgang ernst zu nehmen ist',
                        'Dass die Frage abschließend geklärt ist',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« Unstrittig ist immerhin » ne concède qu’un point minimal : le sujet mérite d’être traité pour lui-même. Le débat, lui, reste ouvert.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Allemand C1 · Registre écrit et nuance',
            'description' => 'Entraînement général aux tournures de l’allemand écrit soigné.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot, en respectant le registre écrit et la nuance attendue.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Die Ergebnisse sind ___ der erwarteten Genauigkeit noch vorläufig.',
                    'correct_answer' => 'trotz',
                    'explanation' => '« Trotz » introduit la concession et régit le génitif (« trotz der ... Genauigkeit ») : malgré la précision attendue, les résultats restent provisoires.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'Es handelt sich ___ um eine vorläufige Einschätzung, nicht um ein Urteil.',
                    'correct_answer' => 'lediglich',
                    'explanation' => '« Lediglich » restreint la portée — rien de plus qu’une appréciation — et appartient au registre écrit, là où l’oral dirait « nur ».',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'Der Verfasser setzt voraus, dass die Methode bekannt ___. (sein)',
                    'correct_answer' => 'sei',
                    'explanation' => 'Le discours rapporté au style indirect demande le Konjunktiv I : « sein » donne « sei ». L’auteur rapporte sans se porter garant.',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Die Untersuchung wurde durchgeführt, ___ die Rahmenbedingungen zu klären.',
                    'correct_answer' => 'um',
                    'explanation' => 'La finale infinitive se construit « um ... zu + infinitif ». « Um » ouvre le groupe, « zu klären » le ferme.',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'Die Daten lassen sich, ___ mit Vorbehalt, auf andere Regionen übertragen.',
                    'correct_answer' => 'wenngleich',
                    'explanation' => '« Wenngleich » est une conjonction concessive du registre écrit : transférables, quoique avec réserve. En incise, elle nuance sans rompre la phrase.',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Allemand C1 · Distinguer des formulations proches',
            'description' => 'Entraînement général à la discrimination fine entre énoncés voisins.',
            'content' => [
                'instructions' => 'Choisis la reformulation qui conserve exactement la portée de l’énoncé, ni plus ni moins.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Ein Zusammenhang ist nicht auszuschließen.',
                    'options' => [
                        'Ein Zusammenhang ist nachgewiesen.',
                        'Ein Zusammenhang ist denkbar, aber unbelegt.',
                        'Ein Zusammenhang ist widerlegt.',
                        'Ein Zusammenhang ist wahrscheinlich.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'La double négation « nicht auszuschließen » ouvre une simple possibilité. Elle n’avance ni preuve ni probabilité.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'Die Maßnahme hat sich nicht als wirksam erwiesen.',
                    'options' => [
                        'Die Maßnahme war nachweislich schädlich.',
                        'Die Maßnahme wurde nie umgesetzt.',
                        'Die Maßnahme hat ihre Wirksamkeit nicht belegt.',
                        'Die Maßnahme wirkt nur langfristig.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'Absence de preuve d’efficacité n’équivaut pas à preuve de nocivité. La formulation reste au niveau du constat.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'Diese Lesart ist zumindest vertretbar.',
                    'options' => [
                        'Diese Lesart ist die einzig mögliche.',
                        'Diese Lesart ist offenkundig falsch.',
                        'Diese Lesart wird allgemein geteilt.',
                        'Diese Lesart lässt sich begründen, ohne zwingend zu sein.',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => '« Zumindest vertretbar » accorde le minimum : défendable, sans prétendre à l’exclusivité ni au consensus.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Der Autor verzichtet darauf, eigene Belege anzuführen.',
                    'options' => [
                        'Der Autor führt bewusst keine eigenen Belege an.',
                        'Der Autor konnte keine Belege finden.',
                        'Der Autor bestreitet fremde Belege.',
                        'Der Autor zitiert ausschließlich sich selbst.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Verzichten auf » dit un choix, non une impossibilité. La nuance sépare la décision de l’échec.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'Die Kritik trifft den Kern der Sache nur bedingt.',
                    'options' => [
                        'Die Kritik ist vollständig unbegründet.',
                        'Die Kritik trifft teilweise zu.',
                        'Die Kritik trifft genau ins Schwarze.',
                        'Die Kritik wurde zurückgezogen.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Nur bedingt » pose une validité partielle : la critique porte en partie, sans atteindre le cœur du sujet.',
                ],
            ],
        ],
    ],
    'C2' => [
        'mcq' => [
            'title' => 'Allemand C2 · Ce qu’une statistique ne dit pas',
            'description' => 'Entraînement général à la lecture critique d’une argumentation dense, indépendant de tout examen officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis distingue l’argument de l’illustration, et la portée réelle de chaque affirmation.',
                'passage' => 'Kaum ein rhetorisches Mittel wirkt so unangreifbar wie die Zahl. Wer sie anführt, scheint sich aus dem Streit der Meinungen zu verabschieden und dem Sachverhalt selbst das Wort zu erteilen. Genau darin liegt die Schwierigkeit. Eine Statistik antwortet nämlich stets auf eine Frage, die jemand zuvor gestellt hat — und diese Frage wird mit dem Ergebnis selten mitgeliefert. Erhebungszeitraum, Vergleichsgruppe, Operationalisierung: an jeder dieser Stellen sind Entscheidungen gefallen, die das Resultat mitformen, ohne im Resultat noch sichtbar zu sein. Daraus folgt kein Freibrief für Beliebigkeit. Wer aus der Standortgebundenheit jeder Messung schließt, es sei ohnehin alles gleich gültig, verwechselt die Kritik an einem Anspruch mit dessen Preisgabe. Die angemessene Konsequenz ist unbequemer: Zahlen bleiben unverzichtbar, verlangen aber, dass man ihre Entstehungsbedingungen mitliest — eine Zumutung, die sich weder delegieren noch abkürzen lässt.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'Worin besteht laut Text die Schwierigkeit bei Zahlen?',
                    'options' => [
                        'Sie sind für Laien zu schwer zu berechnen.',
                        'Sie beruhen auf einer Frage, die im Ergebnis nicht mehr erscheint.',
                        'Sie werden von Fachleuten absichtlich gefälscht.',
                        'Sie veralten schneller als andere Belege.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'Le « nämlich » explicite le point : toute statistique répond à une question posée en amont, rarement livrée avec le résultat. Ni fraude ni obsolescence ne sont en cause.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Welche Rolle spielt die Aufzählung « Erhebungszeitraum, Vergleichsgruppe, Operationalisierung »?',
                    'options' => [
                        'Sie belegt die Genauigkeit statistischer Verfahren.',
                        'Sie nennt Fehler, die man vermeiden sollte.',
                        'Sie führt aus, wo unsichtbare Entscheidungen das Ergebnis formen.',
                        'Sie zählt die Kapitel einer Studie auf.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'L’énumération illustre la thèse : ce sont autant de points de décision. La phrase le dit — « Entscheidungen ... , ohne im Resultat noch sichtbar zu sein ».',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'Welchen Fehlschluss weist der Text ausdrücklich zurück?',
                    'options' => [
                        'Dass jede Messung standortgebunden sei',
                        'Dass Zahlen überhaupt verwendet werden dürfen',
                        'Dass Statistiken selten veröffentlicht würden',
                        'Dass aus der Standortgebundenheit die Gleichgültigkeit aller Aussagen folge',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => 'Le texte nomme la confusion : critiquer une prétention n’est pas y renoncer. Il admet la situativité des mesures, mais refuse le relativisme qu’on en tire.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'Was bedeutet « eine Zumutung, die sich weder delegieren noch abkürzen lässt »?',
                    'options' => [
                        'Die Arbeit des Mitlesens kann niemand anderes übernehmen.',
                        'Die Arbeit ist Fachleuten vorbehalten.',
                        'Die Arbeit lohnt den Aufwand nicht.',
                        'Die Arbeit wird durch Software überflüssig.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Delegieren » et « abkürzen » sont tous deux niés : ni transfert à un tiers, ni raccourci. L’effort incombe au lecteur lui-même.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'Welche Haltung nimmt der Text insgesamt ein?',
                    'options' => [
                        'Er verwirft Statistiken als Erkenntnismittel.',
                        'Er hält Statistiken für unverzichtbar, aber erklärungsbedürftig.',
                        'Er hält den Streit der Meinungen für überwunden.',
                        'Er empfiehlt, Zahlen nur noch Fachleuten zu überlassen.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Zahlen bleiben unverzichtbar, verlangen aber ... » tient les deux bouts : maintien de l’outil, exigence de lecture. Ni rejet ni délégation.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Allemand C2 · Charnières de l’argumentation',
            'description' => 'Entraînement général aux articulations qui portent la nuance d’un raisonnement écrit.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot. La bonne réponse dépend du rapport logique exact entre les deux propositions.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Der Befund ist eindeutig; er erklärt ___ nicht, wie es dazu kam.',
                    'correct_answer' => 'allerdings',
                    'explanation' => '« Allerdings » restreint ce qui vient d’être accordé : le constat est net, sa portée explicative ne l’est pas. « Deshalb » ou « also » inverseraient le rapport.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'Die Belege sind dünn, ___ dass die These vollständig zusammenbräche.',
                    'correct_answer' => 'ohne',
                    'explanation' => 'La tournure « ohne dass » nie une conséquence attendue : preuves faibles, sans que la thèse s’effondre pour autant.',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'Die Ergebnisse sind ___ interessant als vielmehr methodisch aufschlussreich.',
                    'correct_answer' => 'weniger',
                    'explanation' => 'Le couple corrélatif « weniger ... als vielmehr ... » déplace l’accent : moins l’intérêt du résultat que sa portée de méthode.',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Es bleibt offen, ___ die Regel auch auf ältere Fälle anzuwenden ist.',
                    'correct_answer' => 'ob',
                    'explanation' => 'Une interrogation indirecte sans mot interrogatif prend « ob ». « Dass » affirmerait au lieu de laisser la question ouverte, ce que « bleibt offen » interdit.',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'Die Kritik wäre berechtigt, ___ sie sich auf den veröffentlichten Text bezöge.',
                    'correct_answer' => 'sofern',
                    'explanation' => '« Sofern » pose la condition restrictive : la critique ne vaudrait qu’à cette réserve près. Le Konjunktiv II des deux verbes marque l’irréel.',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Allemand C2 · Ce que la formule concède',
            'description' => 'Entraînement général à la lecture des atténuations et des sous-entendus de l’écrit savant.',
            'content' => [
                'instructions' => 'Choisis la reformulation qui restitue exactement ce que l’énoncé concède — et ce qu’il refuse.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Man wird der Studie kaum vorwerfen können, sie sei unsorgfältig gearbeitet.',
                    'options' => [
                        'Die Studie wird wegen Nachlässigkeit kritisiert.',
                        'Die Sorgfalt der Studie steht außer Frage — anderes womöglich nicht.',
                        'Die Studie ist in jeder Hinsicht überzeugend.',
                        'Die Studie wurde nie ernsthaft geprüft.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'La litote concède un point précis — le soin — et, par son insistance, laisse entendre que la critique porte ailleurs.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'Ob sich dieser Aufwand gelohnt hat, mag jeder für sich entscheiden.',
                    'options' => [
                        'Der Autor hält den Aufwand für gerechtfertigt.',
                        'Der Autor enthält sich eines Urteils, nicht ohne Skepsis.',
                        'Der Aufwand wurde nie beziffert.',
                        'Alle Beteiligten waren sich einig.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'Renvoyer chacun à son jugement est une abstention de façade : le ton laisse transparaître le doute sans l’assumer frontalement.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'Die Argumentation ist schlüssig, sofern man ihre Prämissen teilt.',
                    'options' => [
                        'Die Argumentation ist unabhängig von Voraussetzungen gültig.',
                        'Die Argumentation enthält einen formalen Fehler.',
                        'Die Argumentation steht und fällt mit ihren Voraussetzungen.',
                        'Die Prämissen wurden nie genannt.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'La cohérence n’est accordée que sous condition. « Sofern » déplace la charge de la preuve vers les prémisses, où se joue le désaccord.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Dass hier ein Zusammenhang besteht, wird man schwerlich bestreiten wollen.',
                    'options' => [
                        'Der Zusammenhang gilt als kaum bestreitbar.',
                        'Der Zusammenhang wurde widerlegt.',
                        'Der Zusammenhang ist reine Spekulation.',
                        'Über den Zusammenhang schweigt die Forschung.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Schwerlich bestreiten wollen » affirme par la négative : contester serait malvenu, donc le lien est tenu pour acquis.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'Der Beitrag leistet Verdienstvolles — auf einem Feld, das er selbst abgesteckt hat.',
                    'options' => [
                        'Der Beitrag wird uneingeschränkt gelobt.',
                        'Der Beitrag behandelt ein fremdes Fachgebiet.',
                        'Das Lob gilt, doch der Zuschnitt des Gegenstands wird angezweifelt.',
                        'Der Beitrag wurde zurückgezogen.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'Le tiret introduit la réserve : le mérite est réel, mais sur un terrain que l’auteur a lui-même délimité — donc la portée est mise en doute.',
                ],
            ],
        ],
    ],
];
