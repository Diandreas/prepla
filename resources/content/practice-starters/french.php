<?php

return [
    'A1' => [
        'mcq' => [
            'title' => 'Français A1 · Une nouvelle voisine',
            'description' => 'Entraînement général à la compréhension d’une présentation simple, indépendant de tout examen officiel.',
            'content' => [
                'instructions' => 'Lis la présentation de Nora, puis choisis la bonne réponse.',
                'passage' => 'Bonjour ! Je m’appelle Nora. J’ai vingt-quatre ans et j’habite à Lyon avec ma sœur. Je suis étudiante. Le matin, je vais à l’université à vélo. J’aime cuisiner et lire. Le samedi, je joue au tennis avec mon amie Léa.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'Quel âge a Nora ?',
                    'options' => ['Vingt ans', 'Vingt-quatre ans', 'Trente ans', 'Trente-quatre ans'],
                    'correct_answer' => 'B',
                    'explanation' => 'Nora dit « J’ai vingt-quatre ans ». En français, on utilise le verbe « avoir » pour donner son âge.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Avec qui habite Nora ?',
                    'options' => ['Son frère', 'Ses parents', 'Son amie Léa', 'Sa sœur'],
                    'correct_answer' => 'D',
                    'explanation' => '« J’habite à Lyon avec ma sœur » identifie la personne qui partage son logement.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'Que fait Nora ?',
                    'options' => ['Elle est étudiante.', 'Elle est médecin.', 'Elle est vendeuse.', 'Elle est cuisinière.'],
                    'correct_answer' => 'A',
                    'explanation' => 'Le texte indique « Je suis étudiante ». Aimer cuisiner ne signifie pas être cuisinière de métier.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'Comment va-t-elle à l’université ?',
                    'options' => ['En voiture', 'En bus', 'À vélo', 'À pied'],
                    'correct_answer' => 'C',
                    'explanation' => 'La phrase « je vais à l’université à vélo » précise son moyen de transport.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'Quand joue-t-elle au tennis ?',
                    'options' => ['Le lundi', 'Le samedi', 'Le mercredi', 'Le dimanche'],
                    'correct_answer' => 'B',
                    'explanation' => '« Le samedi » introduit une habitude : Nora joue au tennis ce jour de la semaine.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Français A1 · Les verbes du quotidien',
            'description' => 'Entraînement général au présent dans de courtes phrases écrites, sans génération IA.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot : le verbe entre parenthèses conjugué au présent de l’indicatif.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Je ___ étudiant. (être)', 'correct_answer' => 'suis',
                    'explanation' => 'Au présent, « être » se conjugue « je suis ». On utilise ce verbe pour présenter sa situation.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'Tu ___ un frère. (avoir)', 'correct_answer' => 'as',
                    'explanation' => 'Avec « tu », le présent du verbe « avoir » est « as », sans accent.',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'Nous ___ français à la maison. (parler)', 'correct_answer' => 'parlons',
                    'explanation' => 'Pour un verbe régulier en -er, la terminaison avec « nous » est -ons : parl- + -ons.',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Elle ___ au marché le samedi. (aller)', 'correct_answer' => 'va',
                    'explanation' => '« Aller » est irrégulier. Au présent, avec « elle », on écrit « va ».',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'Vous ___ un film. (regarder)', 'correct_answer' => 'regardez',
                    'explanation' => 'Avec « vous », les verbes réguliers en -er prennent -ez au présent : « vous regardez ».',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Français A1 · À chaque besoin, son lieu',
            'description' => 'Entraînement général pour comprendre des situations simples et reconnaître les lieux utiles.',
            'content' => [
                'instructions' => 'Pour chaque situation, choisis le lieu qui convient parmi les quatre propositions.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Je veux acheter du pain et des croissants.',
                    'options' => ['La piscine', 'La gare', 'La boulangerie', 'La bibliothèque'],
                    'correct_answer' => 'C',
                    'explanation' => 'Le pain et les croissants s’achètent à la boulangerie. Ce sont les indices utiles dans la phrase.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'Je veux emprunter un roman.',
                    'options' => ['La bibliothèque', 'La pharmacie', 'La piscine', 'La gare'],
                    'correct_answer' => 'A',
                    'explanation' => '« Emprunter » un livre signifie le prendre pour le rendre ensuite. La bibliothèque permet cet emprunt.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'Je dois prendre le train.',
                    'options' => ['La boulangerie', 'Le cinéma', 'Le parc', 'La gare'],
                    'correct_answer' => 'D',
                    'explanation' => 'La gare est le lieu de départ et d’arrivée des trains.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Je veux nager dans un bassin.',
                    'options' => ['La bibliothèque', 'La piscine', 'La pharmacie', 'La boulangerie'],
                    'correct_answer' => 'B',
                    'explanation' => 'Les mots « nager » et « bassin » permettent d’identifier la piscine.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'Je veux voir un film sur grand écran.',
                    'options' => ['La gare', 'La pharmacie', 'Le cinéma', 'La bibliothèque'],
                    'correct_answer' => 'C',
                    'explanation' => 'Voir un film « sur grand écran » correspond ici à une sortie au cinéma.',
                ],
            ],
        ],
    ],
    'A2' => [
        'mcq' => [
            'title' => 'Français A2 · Un atelier de cuisine',
            'description' => 'Entraînement général à la lecture d’une annonce pratique, sans lien avec un sujet officiel.',
            'content' => [
                'instructions' => 'Lis l’annonce, puis choisis la réponse correspondant à chaque détail.',
                'passage' => 'Le centre de quartier organise un atelier de cuisine le mercredi 18 juin, de 17 h à 19 h. L’atelier est ouvert aux adultes débutants. Le matériel et les ingrédients sont fournis, mais chaque personne doit apporter une boîte pour emporter son repas. La participation coûte 8 euros. Il faut s’inscrire à l’accueil avant le lundi 16 juin. Attention : l’atelier aura lieu dans la grande salle du premier étage, et non dans la cuisine du rez-de-chaussée.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'À qui s’adresse cet atelier ?',
                    'options' => ['Aux cuisiniers professionnels', 'Aux enfants de moins de dix ans', 'Aux adultes débutants', 'Aux élèves d’une seule école'],
                    'correct_answer' => 'C',
                    'explanation' => '« Ouvert aux adultes débutants » indique le public concerné : aucune expérience en cuisine n’est demandée.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Combien de temps dure l’atelier ?',
                    'options' => ['Deux heures', 'Une heure', 'Trois heures', 'Une demi-journée'],
                    'correct_answer' => 'A',
                    'explanation' => 'L’atelier commence à 17 h et finit à 19 h : sa durée est de deux heures.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'Que faut-il apporter ?',
                    'options' => ['Tous les ingrédients', 'Une casserole', 'Un livre de recettes', 'Une boîte pour le repas'],
                    'correct_answer' => 'D',
                    'explanation' => 'Les ingrédients et le matériel sont fournis. Le mot « mais » introduit ce qui reste à apporter : une boîte.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'Comment s’inscrire ?',
                    'options' => ['Par téléphone après le 18 juin', 'À l’accueil avant le 16 juin', 'Sur place après l’atelier', 'En envoyant sa recette par courrier'],
                    'correct_answer' => 'B',
                    'explanation' => 'L’annonce donne à la fois le lieu de l’inscription, « à l’accueil », et sa limite, « avant le lundi 16 juin ».',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'Où se déroule l’atelier ?',
                    'options' => ['Dans le jardin du centre', 'À l’accueil', 'Dans la grande salle du premier étage', 'Dans la cuisine du rez-de-chaussée'],
                    'correct_answer' => 'C',
                    'explanation' => 'La dernière phrase précise le premier étage et exclut la cuisine du rez-de-chaussée avec « et non ».',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Français A2 · Raconter une sortie',
            'description' => 'Entraînement général au passé composé avec l’auxiliaire déjà donné.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot : le participe passé du verbe entre parenthèses. L’auxiliaire est déjà écrit.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Hier, nous avons ___ le musée. (visiter)', 'correct_answer' => 'visité',
                    'explanation' => 'Le participe passé d’un verbe régulier en -er se termine par -é : « visiter » devient « visité ».',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'J’ai ___ le bus à huit heures. (prendre)', 'correct_answer' => 'pris',
                    'explanation' => 'Le participe passé de « prendre » est irrégulier : « pris ». Le passé composé est « j’ai pris ».',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'Elle a ___ une carte postale à sa sœur. (écrire)', 'correct_answer' => 'écrit',
                    'explanation' => '« Écrire » a pour participe passé « écrit ». Le complément « une carte postale » suit le verbe : on n’ajoute pas de -e.',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Nous avons ___ un film après le dîner. (voir)', 'correct_answer' => 'vu',
                    'explanation' => 'Le participe passé de « voir » est « vu » : « nous avons vu un film ».',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'Ils ont ___ une table pour le déjeuner. (réserver)', 'correct_answer' => 'réservé',
                    'explanation' => '« Réserver » est un verbe en -er : son participe passé est « réservé ». Avec « avoir », il ne s’accorde pas avec le sujet « ils ».',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Français A2 · Comprendre les consignes',
            'description' => 'Entraînement général pour reformuler des messages et annonces du quotidien.',
            'content' => [
                'instructions' => 'Choisis, pour chaque message, la phrase qui en exprime le sens.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Merci de rendre les clés avant midi.',
                    'options' => ['On peut garder les clés toute la journée.', 'Il faut rapporter les clés avant 12 h.', 'Il faut fabriquer de nouvelles clés.', 'Les clés sont disponibles seulement le soir.'],
                    'correct_answer' => 'B',
                    'explanation' => '« Rendre » signifie ici rapporter. « Avant midi » fixe une limite avant 12 h.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'Ascenseur en panne. Utilisez l’escalier.',
                    'options' => ['L’ascenseur est réservé au personnel.', 'L’escalier est fermé.', 'Il faut attendre dans l’ascenseur.', 'L’ascenseur ne fonctionne pas.'],
                    'correct_answer' => 'D',
                    'explanation' => '« En panne » signifie qu’un appareil ne fonctionne pas. L’escalier est proposé comme solution.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'Entrée gratuite pour les enfants de moins de six ans.',
                    'options' => ['Un enfant de cinq ans ne paie pas.', 'Tous les adultes entrent gratuitement.', 'Un enfant de huit ans entre gratuitement.', 'Les enfants ne peuvent pas entrer.'],
                    'correct_answer' => 'A',
                    'explanation' => 'Cinq ans est inférieur à six ans. La gratuité annoncée concerne cette tranche d’âge, pas les adultes ni les enfants de huit ans.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Réservation obligatoire pour le cours du samedi.',
                    'options' => ['Le cours du samedi est annulé.', 'Le cours a lieu tous les jours.', 'Il faut réserver sa place à l’avance.', 'On peut venir sans prévenir.'],
                    'correct_answer' => 'C',
                    'explanation' => 'Le mot « obligatoire » indique que réserver est nécessaire pour participer au cours.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'Magasin fermé exceptionnellement ce mardi.',
                    'options' => ['Le magasin ferme tous les mardis.', 'La fermeture de ce mardi n’est pas habituelle.', 'Le magasin ferme définitivement.', 'Le magasin ouvre uniquement le mardi.'],
                    'correct_answer' => 'B',
                    'explanation' => '« Exceptionnellement » signale une situation inhabituelle. L’annonce ne décrit pas une fermeture chaque mardi.',
                ],
            ],
        ],
    ],
    'B1' => [
        'mcq' => [
            'title' => 'Français B1 · Une rue rendue aux piétons',
            'description' => 'Entraînement général à la lecture d’un compte rendu municipal, sans lien avec un sujet officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis choisis la réponse qui correspond à ce qui est écrit, et non à ton opinion.',
                'passage' => 'Depuis janvier, la rue des Tilleuls est fermée aux voitures entre 8 h et 19 h. La mairie a présenté un premier bilan lors du conseil de quartier. Les commerçants, qui craignaient une baisse de fréquentation, constatent au contraire une hausse du nombre de clients le samedi ; en semaine, en revanche, les chiffres restent stables. Plusieurs riverains se plaignent du report de la circulation sur les rues voisines, plus étroites. La mairie reconnaît le problème et annonce une étude complémentaire avant toute extension du dispositif à d’autres rues. Aucune décision définitive ne sera prise avant le mois de septembre.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'Que craignaient les commerçants ?',
                    'options' => [
                        'Une baisse de fréquentation',
                        'Une hausse des loyers',
                        'La fermeture définitive de leurs boutiques',
                        'Le départ des riverains',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'La proposition relative « qui craignaient une baisse de fréquentation » donne exactement leur inquiétude, exprimée à l’imparfait car antérieure au bilan.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Qu’observe-t-on le samedi ?',
                    'options' => [
                        'Une baisse du nombre de clients',
                        'Une hausse du nombre de clients',
                        'Une fermeture des commerces',
                        'Aucun changement mesurable',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Au contraire » renverse la crainte annoncée juste avant : le samedi, la fréquentation augmente. La stabilité, elle, concerne la semaine.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'De quoi se plaignent plusieurs riverains ?',
                    'options' => [
                        'Du bruit des terrasses',
                        'Du manque de places de stationnement payant',
                        'Du report de la circulation sur les rues voisines',
                        'De la fermeture des commerces le samedi',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'Le texte nomme la gêne : les voitures se reportent sur des rues « plus étroites ». Le bruit et le stationnement ne sont pas évoqués.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'Quelle est la position de la mairie ?',
                    'options' => [
                        'Elle nie le problème signalé.',
                        'Elle étend le dispositif immédiatement.',
                        'Elle supprime le dispositif.',
                        'Elle reconnaît le problème et lance une étude.',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => 'Deux verbes se suivent : « reconnaît » puis « annonce une étude complémentaire ». Ni déni ni décision immédiate.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'Quand une décision définitive sera-t-elle prise ?',
                    'options' => [
                        'Pas avant septembre',
                        'Dès le mois de janvier',
                        'Au prochain conseil de quartier',
                        'Elle a déjà été prise.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Aucune décision définitive ne sera prise avant le mois de septembre » : la tournure négative fixe une limite, sans promettre de décision à cette date.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Français B1 · Relier ses idées',
            'description' => 'Entraînement général aux connecteurs logiques et aux temps du récit.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot. Cherche d’abord quel lien logique ou quel temps la phrase exige.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Il est resté chez lui ___ qu’il était malade.',
                    'correct_answer' => 'parce',
                    'explanation' => 'La cause s’exprime par la locution « parce que ». Le mot manquant est « parce », le « que » étant déjà écrit.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'Quand je suis arrivé, il ___ déjà parti. (être)',
                    'correct_answer' => 'était',
                    'explanation' => 'L’imparfait décrit l’état constaté au moment de l’arrivée : « il était déjà parti ». Le passé composé marquerait une action ponctuelle.',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'Je te préviendrai dès que je ___ la réponse. (avoir)',
                    'correct_answer' => 'aurai',
                    'explanation' => 'Après « dès que », le français emploie le futur, là où d’autres langues mettent le présent : « dès que j’aurai la réponse ».',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Il faut que tu ___ à l’heure demain. (être)',
                    'correct_answer' => 'sois',
                    'explanation' => '« Il faut que » impose le subjonctif. Le verbe « être » donne « que tu sois ».',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'C’est le livre ___ je t’ai parlé la semaine dernière.',
                    'correct_answer' => 'dont',
                    'explanation' => 'Le verbe « parler de » entraîne le pronom relatif « dont », qui remplace « de + nom ». « Que » supposerait un complément direct.',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Français B1 · Comprendre une consigne écrite',
            'description' => 'Entraînement général à saisir l’intention d’un message pratique ou administratif.',
            'content' => [
                'instructions' => 'Lis chaque message et choisis la phrase qui en rend le mieux le sens.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Les inscriptions sont closes le 15 mars. Aucune demande ne sera acceptée après cette date.',
                    'options' => [
                        'On ne peut plus s’inscrire après le 15 mars.',
                        'Les inscriptions commencent le 15 mars.',
                        'Une inscription tardive coûte plus cher.',
                        'La date peut être repoussée sur demande.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Closes » signifie fermées, et la seconde phrase exclut toute exception : rien n’est accepté au-delà.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'En cas de difficulté, adressez-vous directement à votre conseiller, et non à l’accueil.',
                    'options' => [
                        'L’accueil est définitivement supprimé.',
                        'Il faut s’adresser à la personne qui suit votre dossier.',
                        'Les demandes se font uniquement par écrit.',
                        'Il n’y a plus d’interlocuteur disponible.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Votre conseiller » désigne la personne chargée de votre dossier. Le message oriente vers elle plutôt que vers l’accueil.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'L’atelier aura lieu à condition qu’au moins huit personnes s’inscrivent.',
                    'options' => [
                        'L’atelier est déjà complet.',
                        'Huit personnes se sont déjà inscrites.',
                        'L’atelier n’aura lieu que si les inscriptions suffisent.',
                        'L’atelier a été annulé.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« À condition que » pose une condition non encore remplie : rien n’indique que le seuil soit atteint.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Merci de laisser la salle dans l’état où vous l’avez trouvée.',
                    'options' => [
                        'La salle doit être repeinte.',
                        'La salle est interdite d’accès.',
                        'Il faut ranger la salle avant de partir.',
                        'L’utilisation de la salle est payante.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« Dans l’état où vous l’avez trouvée » demande de remettre les lieux comme on les a trouvés : donc de ranger.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'La livraison devrait être retardée d’environ une semaine. Nous vous prions de nous en excuser.',
                    'options' => [
                        'La livraison arrivera probablement plus tard que prévu.',
                        'La commande a été annulée.',
                        'La livraison est déjà arrivée.',
                        'Il faut attendre une semaine avant de commander.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'Le conditionnel « devrait » et l’adverbe « environ » indiquent une prévision, pas une certitude. Rien n’est annulé.',
                ],
            ],
        ],
    ],
    'B2' => [
        'mcq' => [
            'title' => 'Français B2 · Le mythe du travail sans interruption',
            'description' => 'Entraînement général à la lecture argumentative, sans lien avec un sujet officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis distingue ce que l’auteur affirme de ce qu’il rapporte ou nuance.',
                'passage' => 'On répète volontiers qu’il faudrait travailler sans être interrompu. L’idée paraît de bon sens ; elle résiste pourtant mal à l’examen. Une équipe a suivi pendant six mois des salariés de deux services comparables : dans le premier, les messages étaient relevés trois fois par jour à heures fixes ; dans le second, chacun restait libre de son organisation. Contrairement à ce qu’espéraient les auteurs, la productivité mesurée n’a pas varié de façon significative. Ce que les entretiens révèlent, en revanche, est plus intéressant : les salariés du premier groupe déclarent une fatigue nettement moindre en fin de journée. Faut-il en conclure que la règle doit être généralisée ? Rien n’est moins sûr. Imposer un rythme uniforme à des métiers dont les contraintes diffèrent reviendrait à traiter comme une cause ce qui n’est peut-être qu’un effet du sentiment de maîtrise.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'Quel résultat contredit l’attente des auteurs ?',
                    'options' => [
                        'La fatigue a augmenté dans les deux groupes.',
                        'La productivité n’a pas varié de façon significative.',
                        'Les salariés ont refusé de participer.',
                        'Les deux services n’étaient pas comparables.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Contrairement à ce qu’espéraient les auteurs » annonce le démenti, qui porte précisément sur la productivité, restée stable.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Qu’apportent les entretiens ?',
                    'options' => [
                        'Une fatigue moindre déclarée dans le premier groupe',
                        'Une hausse de la productivité du second groupe',
                        'Une critique de la méthode employée',
                        'Un refus des salariés de changer d’organisation',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'Le tour « en revanche » oppose les deux sources : chiffres stables d’un côté, déclarations de fatigue moindre de l’autre.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'Que répond l’auteur à la question de la généralisation ?',
                    'options' => [
                        'Il la recommande sans réserve.',
                        'Il la juge déjà appliquée partout.',
                        'Il s’en méfie.',
                        'Il ne se prononce pas du tout.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« Rien n’est moins sûr » est une litote qui exprime un doute net. L’auteur se prononce, mais contre la généralisation.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'Quel risque l’auteur signale-t-il dans la dernière phrase ?',
                    'options' => [
                        'Confondre une cause avec un effet',
                        'Sous-estimer le coût de l’étude',
                        'Négliger les salariés les plus anciens',
                        'Multiplier les interruptions',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'La fin le formule explicitement : « traiter comme une cause ce qui n’est peut-être qu’un effet ». C’est une erreur de raisonnement, pas de méthode.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'Quel est le statut de l’idée exposée dans la première phrase ?',
                    'options' => [
                        'C’est la thèse défendue par l’auteur.',
                        'C’est une opinion courante que l’auteur discute.',
                        'C’est une citation d’un des salariés.',
                        'C’est la conclusion de l’étude.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« On répète volontiers » attribue l’idée à l’opinion commune, et « elle résiste pourtant mal à l’examen » annonce sa mise en question.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Français B2 · Subjonctif, concession et articulation',
            'description' => 'Entraînement général aux formes attendues à l’écrit soutenu.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot, en respectant le mode et le lien logique attendus.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Bien qu’il ___ tous les arguments, il n’a pas changé d’avis. (connaître)',
                    'correct_answer' => 'connaisse',
                    'explanation' => '« Bien que » impose le subjonctif : « connaître » donne « qu’il connaisse ». La concession n’annule pas la principale.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'Les résultats sont nets ; ils n’expliquent ___ pas l’origine du phénomène.',
                    'correct_answer' => 'cependant',
                    'explanation' => '« Cependant » restreint ce qui vient d’être accordé. « Donc » ou « ainsi » inverseraient le rapport logique.',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'Il a agi ___ que personne ne s’en aperçoive.',
                    'correct_answer' => 'sans',
                    'explanation' => 'La locution « sans que » nie une conséquence attendue et entraîne le subjonctif : « sans que personne ne s’en aperçoive ».',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Plus on avance dans le texte, ___ la thèse se précise.',
                    'correct_answer' => 'plus',
                    'explanation' => 'La corrélation proportionnelle se construit « plus ..., plus ... ». Le second terme reprend le premier sans « que ».',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'Ce sont des données ___ la fiabilité reste discutée.',
                    'correct_answer' => 'dont',
                    'explanation' => '« La fiabilité de ces données » : le complément du nom introduit par « de » impose le relatif « dont ».',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Français B2 · Ce que la formule laisse entendre',
            'description' => 'Entraînement général à l’interprétation d’énoncés prudents ou indirects.',
            'content' => [
                'instructions' => 'Lis chaque énoncé et choisis ce qu’il veut réellement dire, au-delà des mots.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Votre proposition part d’une bonne intention, mais elle reste insuffisante.',
                    'options' => [
                        'La proposition est rejetée comme incomplète.',
                        'La proposition est retenue telle quelle.',
                        'La proposition était malveillante.',
                        'La proposition est arrivée hors délai.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'La concession sur l’intention prépare le rejet du fond : « reste insuffisante » porte le vrai jugement.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'Nous reviendrons sur ce point ultérieurement.',
                    'options' => [
                        'Le point est définitivement tranché.',
                        'Le point n’est pas traité pour le moment.',
                        'Le point n’a jamais été à l’ordre du jour.',
                        'Le point doit être réglé sur-le-champ.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'Renvoyer à plus tard est une manière polie de clore la discussion pour l’instant, sans décider du fond.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'Il serait difficile de tenir ce délai.',
                    'options' => [
                        'Le délai est déjà dépassé.',
                        'Le délai sera tenu sans problème.',
                        'Le délai ne sera probablement pas tenu.',
                        'Le délai a été avancé.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'Le conditionnel « serait » atténue une annonce défavorable : l’échéance est compromise, sans être encore officiellement manquée.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'On peut légitimement s’interroger sur l’utilité de cette dépense.',
                    'options' => [
                        'La dépense est unanimement approuvée.',
                        'La dépense n’a jamais été chiffrée.',
                        'L’utilité de la dépense est mise en doute.',
                        'La dépense a été annulée.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« S’interroger légitimement » introduit une critique en la présentant comme raisonnable : le doute est réel, la forme est prudente.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'Il est encore trop tôt pour porter un jugement définitif.',
                    'options' => [
                        'Aucune conclusion ferme n’est possible pour l’instant.',
                        'Le jugement a déjà été rendu.',
                        'Aucun jugement ne sera jamais possible.',
                        'Le jugement rendu est défavorable.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Trop tôt » diffère la conclusion sans l’exclure : il manque des éléments, rien de plus n’est dit.',
                ],
            ],
        ],
    ],
    'C1' => [
        'mcq' => [
            'title' => 'Français C1 · L’oubli, défaut ou fonction ?',
            'description' => 'Entraînement général à la lecture d’un texte de vulgarisation savante, sans lien avec un sujet officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis repère la thèse, les réserves et ce qui demeure ouvert.',
                'passage' => 'L’oubli jouit d’une réputation exécrable. On y voit une défaillance, le raté d’un dispositif qui devrait, lui, conserver. Les travaux récents sur la mémoire invitent pourtant à renverser la perspective : oublier n’est pas une panne, c’est une opération. Une mémoire qui retiendrait tout ne serait pas parfaite, elle serait inutilisable, faute de pouvoir distinguer l’essentiel de l’accessoire. Ce qui importe n’est donc pas la quantité conservée, mais la sûreté avec laquelle le superflu est écarté. Cette lecture n’a pas fait l’unanimité. On lui objecte, non sans raison, qu’on ne saurait inférer la fonction d’un processus de sa seule utilité ; certains oublis demeurent des pertes sèches. Reste un acquis que nul ne conteste : la question ne peut plus être posée sans traiter l’oubli comme un phénomène à part entière.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'Quelle thèse les travaux récents soutiennent-ils ?',
                    'options' => [
                        'L’oubli est un défaut de la mémoire.',
                        'L’oubli est une opération à part entière.',
                        'L’oubli peut être supprimé par l’entraînement.',
                        'L’oubli ne touche que les détails.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Non une panne, c’est une opération » oppose les deux lectures. « Pourtant » signale le renversement par rapport à la réputation initiale.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Pourquoi une mémoire qui retiendrait tout serait-elle inutilisable ?',
                    'options' => [
                        'Parce qu’elle consommerait trop d’énergie',
                        'Parce qu’elle déformerait les souvenirs',
                        'Parce qu’elle ne distinguerait plus l’essentiel de l’accessoire',
                        'Parce qu’elle ne fonctionnerait qu’à court terme',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'La raison est donnée par « faute de pouvoir distinguer ». Le défaut n’est pas de capacité mais de hiérarchie.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'Selon cette lecture, qu’est-ce qui importe ?',
                    'options' => [
                        'La quantité d’informations conservées',
                        'La rapidité du rappel',
                        'La régularité des révisions',
                        'La sûreté avec laquelle le superflu est écarté',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => 'La structure « non pas ..., mais ... » écarte la quantité au profit du tri. C’est le critère retenu.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'Quelle objection est faite à cette lecture ?',
                    'options' => [
                        'On ne peut déduire la fonction d’un processus de sa seule utilité.',
                        'Les études portaient sur trop peu de personnes.',
                        'L’oubli ne se mesure pas.',
                        'Les travaux ignorent les recherches anciennes.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'L’objection est logique — un glissement de l’utile au fonctionnel — et non méthodologique. Aucun reproche d’échantillon n’est formulé.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'Que présente la dernière phrase ?',
                    'options' => [
                        'La victoire d’un camp sur l’autre',
                        'Un acquis minimal que personne ne conteste',
                        'La clôture définitive du débat',
                        'Un appel à abandonner la question',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Reste un acquis que nul ne conteste » n’accorde que le minimum : le sujet mérite d’être traité pour lui-même. Le débat demeure ouvert.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Français C1 · Registre écrit et nuance',
            'description' => 'Entraînement général aux tournures de l’écrit soigné.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot, en respectant le registre écrit et la nuance attendue.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Ces résultats demeurent provisoires, ___ la précision annoncée.',
                    'correct_answer' => 'malgré',
                    'explanation' => '« Malgré » introduit la concession devant un groupe nominal : la précision n’empêche pas le caractère provisoire.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'Il ne s’agit ___ que d’une appréciation, non d’un verdict.',
                    'correct_answer' => 'là',
                    'explanation' => 'La tournure « il ne s’agit là que de » appartient à l’écrit soutenu ; « là » y renforce la restriction portée par « ne ... que ».',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'L’enquête a été menée ___ de clarifier le cadre juridique.',
                    'correct_answer' => 'afin',
                    'explanation' => 'Le but se marque par « afin de + infinitif », plus soutenu que « pour » dans un texte de ce registre.',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Les données sont transposables, ___ avec prudence, à d’autres régions.',
                    'correct_answer' => 'quoique',
                    'explanation' => '« Quoique », en incise, concède une réserve sans rompre la phrase : transposables, mais avec précaution.',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'L’auteur tient pour acquis ___ la méthode soit connue du lecteur.',
                    'correct_answer' => 'que',
                    'explanation' => '« Tenir pour acquis que » introduit une complétive ; le subjonctif « soit » marque ici que l’auteur présuppose sans démontrer.',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Français C1 · Distinguer des formulations proches',
            'description' => 'Entraînement général à la discrimination fine entre énoncés voisins.',
            'content' => [
                'instructions' => 'Choisis la reformulation qui conserve exactement la portée de l’énoncé, ni plus ni moins.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Un lien n’est pas à exclure.',
                    'options' => [
                        'Le lien est démontré.',
                        'Le lien est envisageable mais non établi.',
                        'Le lien est réfuté.',
                        'Le lien est probable.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'La double négation ouvre une simple possibilité. Elle n’avance ni preuve ni estimation de probabilité.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'La mesure ne s’est pas révélée efficace.',
                    'options' => [
                        'La mesure s’est révélée nuisible.',
                        'La mesure n’a jamais été appliquée.',
                        'L’efficacité de la mesure n’est pas établie.',
                        'La mesure n’agit qu’à long terme.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'Absence de preuve d’efficacité ne vaut pas preuve de nocivité. L’énoncé reste au niveau du constat.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'Cette lecture est pour le moins défendable.',
                    'options' => [
                        'Cette lecture est la seule possible.',
                        'Cette lecture est manifestement fausse.',
                        'Cette lecture fait consensus.',
                        'Cette lecture peut se justifier sans s’imposer.',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => '« Pour le moins défendable » accorde le minimum : on peut la soutenir, sans exclusivité ni accord général.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'L’auteur se dispense de produire ses propres preuves.',
                    'options' => [
                        'L’auteur choisit de ne pas produire de preuves.',
                        'L’auteur n’a pas réussi à en trouver.',
                        'L’auteur conteste les preuves des autres.',
                        'L’auteur ne cite que lui-même.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Se dispenser de » exprime un choix, non une impossibilité. La nuance sépare la décision de l’échec.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'La critique ne porte qu’en partie.',
                    'options' => [
                        'La critique est entièrement infondée.',
                        'La critique est partiellement fondée.',
                        'La critique touche exactement sa cible.',
                        'La critique a été retirée.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Ne ... qu’en partie » établit une validité limitée : la critique vaut pour une part, pas pour le tout.',
                ],
            ],
        ],
    ],
    'C2' => [
        'mcq' => [
            'title' => 'Français C2 · Ce qu’un chiffre ne dit pas',
            'description' => 'Entraînement général à la lecture critique d’une argumentation dense, sans lien avec un sujet officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis distingue l’argument de l’illustration, et mesure la portée exacte de chaque affirmation.',
                'passage' => 'Peu de procédés rhétoriques paraissent aussi inattaquables que le chiffre. Qui l’avance semble se retirer du conflit des opinions et laisser parler la chose même. C’est précisément là que réside la difficulté. Une statistique répond toujours à une question que quelqu’un a posée — et cette question, le résultat ne la transporte presque jamais avec lui. Période d’observation, groupe de comparaison, choix des indicateurs : autant de points où des décisions ont été prises, qui façonnent le résultat sans y laisser de trace lisible. Il ne s’ensuit aucun blanc-seing pour l’arbitraire. Conclure de la situation de toute mesure que tout se vaut, c’est confondre la critique d’une prétention avec son abandon. La conséquence qui s’impose est plus inconfortable : les chiffres restent indispensables, mais exigent qu’on lise en même temps les conditions de leur fabrication — exigence qu’on ne peut ni déléguer ni abréger.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'En quoi consiste la difficulté signalée ?',
                    'options' => [
                        'Les chiffres sont trop complexes à calculer.',
                        'Ils reposent sur une question qui n’apparaît plus dans le résultat.',
                        'Ils sont sciemment falsifiés par les spécialistes.',
                        'Ils vieillissent plus vite que les autres preuves.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'Le texte l’énonce après le tiret : toute statistique répond à une question posée en amont, que le résultat ne transporte pas. Ni fraude ni obsolescence.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Quel rôle joue l’énumération « période d’observation, groupe de comparaison, choix des indicateurs » ?',
                    'options' => [
                        'Elle atteste la rigueur des procédés statistiques.',
                        'Elle énumère les chapitres d’une étude.',
                        'Elle détaille les points où des décisions invisibles façonnent le résultat.',
                        'Elle recense des erreurs à éviter.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'L’énumération illustre la thèse : ce sont autant de choix « sans y laisser de trace lisible ». Ce ne sont pas des fautes, mais des décisions.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'Quel raisonnement le texte rejette-t-il explicitement ?',
                    'options' => [
                        'Que toute mesure est située',
                        'Que les chiffres puissent être employés',
                        'Que les statistiques soient rarement publiées',
                        'Que de la situation de toute mesure découle l’équivalence de tout',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => 'Le texte nomme la confusion : critiquer une prétention n’est pas y renoncer. Il admet la situativité, refuse le relativisme qu’on en tire.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'Que signifie « exigence qu’on ne peut ni déléguer ni abréger » ?',
                    'options' => [
                        'Ce travail de lecture n’incombe à personne d’autre et ne se raccourcit pas.',
                        'Ce travail est réservé aux spécialistes.',
                        'Ce travail ne vaut pas l’effort consenti.',
                        'Ce travail sera bientôt automatisé.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'Les deux verbes sont niés ensemble : ni transfert à un tiers, ni raccourci. L’effort revient au lecteur.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'Quelle position d’ensemble le texte adopte-t-il ?',
                    'options' => [
                        'Il écarte les statistiques comme moyen de connaissance.',
                        'Il les tient pour indispensables mais à lire avec leurs conditions.',
                        'Il estime le conflit des opinions dépassé.',
                        'Il recommande de confier les chiffres aux seuls experts.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Restent indispensables, mais exigent ... » tient les deux bouts : maintien de l’outil, exigence de lecture. Ni rejet ni délégation.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Français C2 · Charnières de l’argumentation',
            'description' => 'Entraînement général aux articulations qui portent la nuance d’un raisonnement écrit.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot. La bonne réponse dépend du rapport logique exact entre les deux propositions.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Le constat est net ; il n’explique ___ pas comment on en est arrivé là.',
                    'correct_answer' => 'toutefois',
                    'explanation' => '« Toutefois » restreint ce qui vient d’être accordé. « Donc » ou « ainsi » inverseraient le rapport logique.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'Les preuves sont minces, ___ que la thèse s’effondre pour autant.',
                    'correct_answer' => 'sans',
                    'explanation' => '« Sans que » nie une conséquence attendue et entraîne le subjonctif : la faiblesse des preuves n’emporte pas la thèse.',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'Ces résultats sont ___ intéressants que révélateurs sur le plan de la méthode.',
                    'correct_answer' => 'moins',
                    'explanation' => 'Le couple « moins ... que ... » déplace l’accent : l’intérêt du résultat compte moins que sa portée méthodologique.',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Reste à savoir ___ la règle vaut aussi pour les cas antérieurs.',
                    'correct_answer' => 'si',
                    'explanation' => 'L’interrogation indirecte sans mot interrogatif prend « si ». « Que » affirmerait au lieu de laisser la question ouverte.',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'La critique serait fondée ___ elle visait le texte publié.',
                    'correct_answer' => 'si',
                    'explanation' => 'Le système hypothétique « si + imparfait ... conditionnel » marque l’irréel : la critique ne vaudrait qu’à cette condition, non remplie.',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Français C2 · Ce que la formule concède',
            'description' => 'Entraînement général à la lecture des atténuations et des sous-entendus de l’écrit savant.',
            'content' => [
                'instructions' => 'Choisis la reformulation qui restitue exactement ce que l’énoncé concède — et ce qu’il refuse.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'On ne saurait reprocher à cette étude d’avoir été menée sans soin.',
                    'options' => [
                        'L’étude est critiquée pour sa négligence.',
                        'Le soin de l’étude n’est pas en cause — le reste peut l’être.',
                        'L’étude emporte l’adhésion sur tous les points.',
                        'L’étude n’a jamais été examinée.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'La litote concède un point précis — le soin — et, par son insistance même, suggère que la critique porte ailleurs.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'Chacun jugera si cet effort valait d’être consenti.',
                    'options' => [
                        'L’auteur juge l’effort justifié.',
                        'L’auteur s’abstient de trancher, non sans réserve.',
                        'L’effort n’a jamais été chiffré.',
                        'Tous les participants étaient d’accord.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'Renvoyer chacun à son jugement est une abstention de façade : le tour laisse affleurer le doute sans l’assumer.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'Le raisonnement se tient, pour peu qu’on en accepte les prémisses.',
                    'options' => [
                        'Le raisonnement vaut indépendamment de toute prémisse.',
                        'Le raisonnement comporte une erreur formelle.',
                        'Le raisonnement tient ou tombe avec ses prémisses.',
                        'Les prémisses n’ont jamais été énoncées.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« Pour peu que » subordonne la validité à une condition. Le désaccord se déplace vers les prémisses.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Nul ne s’aviserait de contester qu’il y ait ici un lien.',
                    'options' => [
                        'Le lien est tenu pour à peine contestable.',
                        'Le lien a été réfuté.',
                        'Le lien relève de la pure spéculation.',
                        'La recherche est muette sur ce lien.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'La formule affirme par la négative : contester serait déplacé, donc le lien est traité comme acquis.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'La contribution a son mérite — sur un terrain qu’elle a elle-même délimité.',
                    'options' => [
                        'La contribution est louée sans réserve.',
                        'La contribution traite un domaine étranger à son auteur.',
                        'L’éloge tient, mais la délimitation de l’objet est mise en doute.',
                        'La contribution a été retirée.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'Le tiret introduit la réserve : le mérite est réel, mais sur un terrain choisi par l’auteur — d’où un doute sur la portée.',
                ],
            ],
        ],
    ],
];
