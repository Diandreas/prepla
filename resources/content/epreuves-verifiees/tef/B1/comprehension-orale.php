<?php

/**
 * TEF (TEF Canada, TEF Études) — épreuve « Compréhension orale ».
 *
 * Écrit à la main d'après les documents officiels du Français des affaires
 * (CCI Paris Île-de-France), lus le 2026-11-04 :
 *  - https://www.lefrancaisdesaffaires.fr/candidat/test-evaluation-francais/tef-canada/presentation/
 *    et .../tef-etudes/presentation/ : « Contenu : 40 questions », « Durée :
 *    40 minutes », « Questionnaire à choix multiples (QCM) » ;
 *  - https://www.lefrancaisdesaffaires.fr/wp-content/uploads/2024/10/tef-exemples-epreuves-co.pdf
 *    (exemples officiels) : quatre choix A, B, C, D ; « chaque audio est
 *    diffusé une fois » ; et quatre sections dans cet ordre, reprises ici —
 *      questions 1 à 4   : conversations, à rapprocher d'un dessin ;
 *      questions 5 à 20  : annonces publiques, messages sur répondeur,
 *                          micro-trottoir (« totalement pour », « pour à
 *                          certaines conditions », « totalement contre »,
 *                          « ne se prononce pas ») ;
 *      questions 21 à 30 : chroniques radio, interviews et reportage — deux
 *                          questions par interview et par reportage ;
 *      questions 31 à 40 : documents audio divers, une question chacun.
 *
 * Les enregistrements sont originaux. Ce sont le FORMAT, le nombre d'items, le
 * nombre de choix et l'ordre des sections qui reprennent l'épreuve.
 *
 * Deux choix à garder si ce fichier est recopié :
 *  - le texte entendu vit dans `audio_text`, jamais dans `content.passage` :
 *    affiché, il transformerait l'écoute en lecture. Les deux questions d'une
 *    même interview portent le même `audio_text` ;
 *  - aux questions 1 à 4, l'épreuve montre quatre dessins. Notre format n'en
 *    affiche pas : chaque dessin est décrit en une ligne, et la consigne le dit.
 *
 * POURQUOI LE DOSSIER « B1 » : voir comprehension-ecrite.php du même dossier.
 */

$microTrottoir = "JOURNALISTE : Faut-il interdire les trottinettes électriques en libre-service dans notre ville ? Nous avons posé la question à des passants.";

$interviewLibraire = "JOURNALISTE : Vous avez ouvert une librairie dans un village de quatre cents habitants. Beaucoup vous ont dit que c'était de la folie…\n"
    ."LIBRAIRE : Oui, et ils n'avaient pas tout à fait tort ! Je ne vis pas de la vente de livres seule. Le matin, je fais aussi dépôt de pain, l'après-midi, point relais pour les colis. Ce sont ces services qui font venir les gens ; et une fois entrés, ils repartent souvent avec un livre.\n"
    ."JOURNALISTE : Et l'avenir ?\n"
    ."LIBRAIRE : Je voudrais organiser des lectures pour les enfants de l'école, le mercredi. Il ne me manque qu'une salle : la mairie doit me répondre en septembre.";

$interviewMedecin = "JOURNALISTE : Docteur, on parle beaucoup de la fatigue des étudiants. Qu'observez-vous ?\n"
    ."MÉDECIN : Ce qui me frappe, ce n'est pas tant le manque de sommeil que son irrégularité. Beaucoup dorment sept heures, mais jamais aux mêmes heures : couchés à deux heures du matin en semaine, à minuit le week-end. Le corps ne s'y retrouve plus.\n"
    ."JOURNALISTE : Que leur conseillez-vous ?\n"
    ."MÉDECIN : Pas de dormir davantage, curieusement. De se lever chaque jour à la même heure, même le dimanche. C'est l'heure du lever qui règle tout le reste.";

$interviewChef = "JOURNALISTE : Votre restaurant a retiré la viande de bœuf de sa carte. Pourquoi ?\n"
    ."CHEFFE : On pense souvent que c'est une décision militante. En réalité, elle est d'abord économique : le prix du bœuf de qualité a doublé en trois ans, et je refuse de servir de la viande médiocre.\n"
    ."JOURNALISTE : Vos clients ont-ils protesté ?\n"
    ."CHEFFE : Quelques habitués, au début. Mais les plats de légumes se vendent si bien qu'aujourd'hui, ce sont eux qui font notre réputation.";

$reportageJardins = "JOURNALISTE : Sur le toit de ce centre commercial, au milieu des tours, poussent désormais des tomates, des salades et des fraises. Mille deux cents mètres carrés cultivés par une association d'insertion, qui emploie douze personnes éloignées de l'emploi. Les légumes sont vendus directement aux habitants du quartier, deux fois par semaine.\n"
    ."RESPONSABLE : Notre but n'est pas de nourrir la ville, soyons honnêtes : on en serait bien incapables. Notre but, c'est de redonner un travail et un rythme à des personnes qui en avaient perdu l'habitude. Les légumes sont presque un prétexte.\n"
    ."JOURNALISTE : Le projet a coûté cher : renforcer le toit a représenté à lui seul la moitié du budget. Mais trois autres centres commerciaux de la région ont déjà demandé à suivre l'exemple.";

return [
    'exam' => 'tef',
    'level' => 'B1',
    'section' => 'comprehension-orale',
    'parts' => [
        [
            'part' => 1,
            'name' => 'Compréhension orale, partie 1',
            'type' => 'mcq',
            'title' => 'TEF · Compréhension orale — Série complète de 40 questions',
            'description' => "Les quatre sections de l'épreuve, dans l'ordre officiel : conversations, annonces et messages, radio, documents divers. Une seule écoute, quatre choix par question.",
            'content' => [
                'instructions' => "Écoutez chaque enregistrement UNE SEULE FOIS, comme au TEF, puis choisissez la seule bonne réponse : A, B, C ou D. Pour une interview ou un reportage, deux questions portent sur le même enregistrement. À l'épreuve, l'ensemble dure 40 minutes.\n\nÀ noter : aux questions 1 à 4, l'épreuve montre quatre dessins ; ici, chaque dessin est décrit en une ligne.",
            ],
            'questions' => [
                // ── Questions 1 à 4 : conversations et dessins ──
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Conversation 1 — Quel dessin correspond à la conversation ?",
                    'audio_text' => "FEMME : Je voudrais ces roses rouges, s'il vous plaît. C'est pour un anniversaire.\nHOMME : Je vous fais un bouquet ? J'ajoute un peu de verdure ?\nFEMME : Oui, merci, c'est parfait.",
                    'options' => [
                        "Dessin A — Une femme achète des fleurs chez un fleuriste.",
                        "Dessin B — Une femme arrose les fleurs de son jardin.",
                        "Dessin C — Une femme offre un gâteau d'anniversaire.",
                        "Dessin D — Une femme achète des légumes au marché.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. La cliente demande « ces roses rouges » et le vendeur propose « un bouquet » : c'est une scène chez le fleuriste. L'anniversaire est la raison de l'achat, pas la scène (C).",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Conversation 2 — Quel dessin correspond à la conversation ?",
                    'audio_text' => "HOMME : Bonjour, j'ai mal à la gorge depuis trois jours et un peu de fièvre.\nFEMME : Ouvrez la bouche… Dites « a ». Bon, je vais vous prescrire un sirop.",
                    'options' => [
                        "Dessin A — Un homme achète du sirop au supermarché.",
                        "Dessin B — Un homme est examiné par une médecin.",
                        "Dessin C — Un homme chante devant un public.",
                        "Dessin D — Un homme est au lit chez lui.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Ouvrez la bouche… Dites “a” » et « je vais vous prescrire » : une médecin examine son patient. On ne prescrit pas au supermarché (A).",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Conversation 3 — Quel dessin correspond à la conversation ?",
                    'audio_text' => "FEMME : Pardon monsieur, pour aller à la gare, c'est loin ?\nHOMME : Non, c'est tout près : vous prenez la deuxième rue à gauche, et c'est au bout, en face de la poste.",
                    'options' => [
                        "Dessin A — Une femme achète un billet de train au guichet.",
                        "Dessin B — Une femme attend un train sur le quai.",
                        "Dessin C — Une femme demande son chemin à un passant dans la rue.",
                        "Dessin D — Une femme poste une lettre.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. Elle demande « pour aller à la gare, c'est loin ? » et l'homme indique le chemin : la scène se passe dans la rue, avant d'arriver à la gare. La poste n'est qu'un repère (D).",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Conversation 4 — Quel dessin correspond à la conversation ?",
                    'audio_text' => "HOMME : On met le canapé contre ce mur ?\nFEMME : Attends, d'abord les cartons de livres. Pose-les près de la fenêtre, on les videra demain.\nHOMME : D'accord, mais je fais une pause après : on porte des cartons depuis ce matin !",
                    'options' => [
                        "Dessin A — Un couple fait un déménagement.",
                        "Dessin B — Un couple lit sur un canapé.",
                        "Dessin C — Un couple achète un canapé dans un magasin.",
                        "Dessin D — Un couple range une bibliothèque municipale.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Ils placent le canapé, posent « les cartons de livres » et en portent « depuis ce matin » : c'est un déménagement. Les livres sont dans des cartons, pas en lecture (B).",
                ],
                // ── Questions 5 à 20 : annonces, messages, micro-trottoir ──
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Annonce 1 — Ce message est diffusé pour :",
                    'audio_text' => "Votre attention, s'il vous plaît. Le train TER numéro 857 432 à destination de Nantes, prévu à 14 h 12, partira avec un retard d'environ vingt minutes en raison d'un incident technique. Nous vous prions de nous excuser pour la gêne occasionnée.",
                    'options' => [
                        "annoncer la suppression d'un train.",
                        "annoncer un changement de quai.",
                        "annoncer un retard.",
                        "demander de présenter son billet.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. Le train « partira avec un retard d'environ vingt minutes ». Il part bien (contre A), et aucun quai n'est mentionné.",
                ],
                [
                    'id' => 'q6', 'type' => 'mcq',
                    'text' => "Annonce 2 — Ce message annonce :",
                    'audio_text' => "Chers clients, profitez dès maintenant de notre offre spéciale au rayon fruits et légumes : pour deux kilos de pommes achetés, le troisième est offert. Offre valable aujourd'hui seulement, dans la limite des stocks disponibles.",
                    'options' => [
                        "une promotion valable un seul jour.",
                        "la fermeture du rayon fruits et légumes.",
                        "une dégustation gratuite de pommes.",
                        "l'arrivée de nouveaux produits.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « pour deux kilos de pommes achetés, le troisième est offert », « valable aujourd'hui seulement » : c'est une promotion d'un jour. Le kilo offert s'obtient en achetant, ce n'est pas une dégustation (C).",
                ],
                [
                    'id' => 'q7', 'type' => 'mcq',
                    'text' => "Annonce 3 — Que doivent faire les passagers du vol pour Montréal ?",
                    'audio_text' => "Les passagers du vol AF 346 à destination de Montréal sont invités à se présenter immédiatement à la porte d'embarquement numéro 32. L'embarquement va se terminer. Dernier appel pour le vol AF 346.",
                    'options' => [
                        "Attendre une nouvelle annonce.",
                        "Se présenter au comptoir d'enregistrement.",
                        "Changer de vol.",
                        "Aller tout de suite à la porte 32.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. Ils doivent « se présenter immédiatement à la porte d'embarquement numéro 32 », car c'est le « dernier appel ». « Immédiatement » interdit d'attendre (A).",
                ],
                [
                    'id' => 'q8', 'type' => 'mcq',
                    'text' => "Annonce 4 — Que dit cette annonce aux visiteurs ?",
                    'audio_text' => "Mesdames et messieurs, le musée fermera ses portes dans quinze minutes. Nous invitons nos visiteurs à se diriger vers la sortie. La boutique reste ouverte jusqu'à dix-huit heures trente. Merci de votre visite et à bientôt.",
                    'options' => [
                        "Le musée est fermé pour travaux.",
                        "Le musée va fermer bientôt.",
                        "La boutique ferme dans quinze minutes.",
                        "Une visite guidée va commencer.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « le musée fermera ses portes dans quinze minutes ». La boutique, elle, « reste ouverte jusqu'à dix-huit heures trente » : c'est le piège de C.",
                ],
                [
                    'id' => 'q9', 'type' => 'mcq',
                    'text' => "Annonce 5 — Pourquoi la piscine diffuse-t-elle ce message ?",
                    'audio_text' => "Information aux nageurs : en raison d'un orage annoncé, le bassin extérieur va être évacué dans quelques minutes. Le bassin intérieur reste ouvert jusqu'à la fermeture habituelle. Merci de suivre les indications des maîtres-nageurs.",
                    'options' => [
                        "Pour annoncer la fermeture de toute la piscine.",
                        "Pour annoncer un cours de natation.",
                        "Pour signaler un enfant perdu.",
                        "Pour faire sortir les nageurs du bassin extérieur.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « le bassin extérieur va être évacué » à cause de l'orage. Le bassin intérieur « reste ouvert » : la piscine ne ferme pas entièrement (A).",
                ],
                [
                    'id' => 'q10', 'type' => 'mcq',
                    'text' => "Annonce 6 — Que conseille-t-on aux voyageurs du métro ?",
                    'audio_text' => "En raison d'un mouvement social, le trafic est fortement perturbé sur la ligne 4 : un train sur trois circule. Nous conseillons aux voyageurs d'emprunter la ligne 12 ou les bus de substitution mis en place entre les stations Gare du Nord et Châtelet.",
                    'options' => [
                        "Attendre la reprise normale du trafic sur la ligne 4.",
                        "Rentrer chez eux à pied.",
                        "Prendre la ligne 12 ou les bus de remplacement.",
                        "Acheter un billet spécial.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Nous conseillons aux voyageurs d'emprunter la ligne 12 ou les bus de substitution ». « De substitution » veut dire de remplacement. La ligne 4 roule encore, mais mal : « un train sur trois ».",
                ],
                // MESSAGES SUR RÉPONDEUR
                [
                    'id' => 'q11', 'type' => 'mcq',
                    'text' => "Message 1 — La personne appelle pour :",
                    'audio_text' => "Bonjour, c'est Sylvie Marchand, de l'agence immobilière. Je vous appelle au sujet de l'appartement de la rue Victor-Hugo : le propriétaire accepte votre dossier. Il faudrait signer le bail avant vendredi. Rappelez-moi pour fixer un rendez-vous. Bonne journée !",
                    'options' => [
                        "annoncer une bonne nouvelle et fixer un rendez-vous.",
                        "refuser un dossier.",
                        "proposer un autre appartement.",
                        "demander un document manquant.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « le propriétaire accepte votre dossier » puis « Rappelez-moi pour fixer un rendez-vous » pour signer le bail. Le dossier est accepté, pas refusé (B), et il ne manque aucun document.",
                ],
                [
                    'id' => 'q12', 'type' => 'mcq',
                    'text' => "Message 2 — La personne appelle pour :",
                    'audio_text' => "Salut Karim, c'est Lucie. Dis donc, pour samedi, finalement, on ne fait plus le pique-nique au parc : la météo annonce de la pluie toute la journée. On se retrouve chez moi à la même heure, midi. Apporte juste ta salade, comme prévu. Bisous !",
                    'options' => [
                        "annuler la rencontre de samedi.",
                        "changer le lieu du rendez-vous.",
                        "changer l'heure du rendez-vous.",
                        "demander à Karim d'apporter un dessert.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Le pique-nique n'a plus lieu au parc mais « chez moi à la même heure » : seul le lieu change. L'heure reste midi (contre C), et Karim apporte « juste [sa] salade, comme prévu ».",
                ],
                [
                    'id' => 'q13', 'type' => 'mcq',
                    'text' => "Message 3 — Que doit faire Monsieur Okafor ?",
                    'audio_text' => "Bonjour monsieur Okafor, ici le garage Dupuis. Votre voiture est prête. En revanche, en changeant les freins, nous avons remarqué que les pneus avant sont usés. Nous ne les avons pas remplacés sans votre accord : dites-nous ce que vous décidez quand vous passerez la chercher.",
                    'options' => [
                        "Venir chercher sa voiture et décider pour les pneus.",
                        "Payer le changement des pneus.",
                        "Laisser sa voiture au garage une semaine de plus.",
                        "Acheter des pneus dans un autre garage.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. La voiture « est prête », et le garage attend sa décision : « dites-nous ce que vous décidez quand vous passerez la chercher ». Les pneus n'ont pas été changés (« sans votre accord ») : il n'y a rien à payer pour eux (B).",
                ],
                [
                    'id' => 'q14', 'type' => 'mcq',
                    'text' => "Message 4 — Pourquoi l'école appelle-t-elle ?",
                    'audio_text' => "Bonjour, ici l'école primaire Jean-Moulin. Votre fille Inès a un peu de fièvre depuis la récréation. Rien de grave, elle est à l'infirmerie et elle se repose, mais il serait préférable que quelqu'un vienne la chercher avant la fin de la journée.",
                    'options' => [
                        "Inès s'est blessée pendant la récréation.",
                        "Inès a oublié ses affaires.",
                        "Il faut prendre rendez-vous avec la maîtresse.",
                        "Inès est malade et il faut venir la chercher.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. Inès « a un peu de fièvre » et « il serait préférable que quelqu'un vienne la chercher ». La récréation indique seulement quand la fièvre a commencé : elle ne s'est pas blessée (A).",
                ],
                [
                    'id' => 'q15', 'type' => 'mcq',
                    'text' => "Message 5 — Que propose la personne ?",
                    'audio_text' => "Bonjour madame Ferreira, ici le service clients de votre fournisseur d'électricité. Votre contrat arrive à échéance le mois prochain. Nous pouvons vous proposer une offre avec un prix fixe pendant deux ans. Si cela vous intéresse, rappelez-nous au numéro gratuit indiqué sur votre facture.",
                    'options' => [
                        "Une réduction immédiate sur la prochaine facture.",
                        "Un contrat à prix fixe pendant deux ans.",
                        "La résiliation du contrat.",
                        "Un rendez-vous avec un technicien.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « une offre avec un prix fixe pendant deux ans ». Le contrat « arrive à échéance », c'est-à-dire à son terme : il ne s'agit pas de le résilier (C), mais d'en proposer un nouveau.",
                ],
                // MICRO-TROTTOIR : « Faut-il interdire les trottinettes en libre-service ? »
                [
                    'id' => 'q16', 'type' => 'mcq',
                    'text' => "Micro-trottoir, personne 1 — Sur l'interdiction des trottinettes en libre-service, cette personne :",
                    'audio_text' => $microTrottoir."\nPERSONNE 1 : Ah oui, et tout de suite ! Elles traînent partout sur les trottoirs, j'ai failli tomber trois fois avec ma poussette. Qu'on les supprime, point final.",
                    'options' => ["est totalement pour.", "est pour, à certaines conditions.", "est totalement contre.", "ne se prononce pas."],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Ah oui, et tout de suite ! » et « Qu'on les supprime, point final » : aucune condition, une adhésion complète à l'interdiction. « point final » ferme la discussion.",
                ],
                [
                    'id' => 'q17', 'type' => 'mcq',
                    'text' => "Micro-trottoir, personne 2 — Sur l'interdiction des trottinettes en libre-service, cette personne :",
                    'audio_text' => $microTrottoir."\nPERSONNE 2 : Les interdire ? Sûrement pas ! Je les prends tous les jours pour aller au travail, c'est rapide et ça ne pollue pas. Ce serait un retour en arrière.",
                    'options' => ["est totalement pour.", "est pour, à certaines conditions.", "est totalement contre.", "ne se prononce pas."],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Sûrement pas ! » et « Ce serait un retour en arrière » : elle refuse l'interdiction sans réserve, parce qu'elle utilise ces trottinettes tous les jours.",
                ],
                [
                    'id' => 'q18', 'type' => 'mcq',
                    'text' => "Micro-trottoir, personne 3 — Sur l'interdiction des trottinettes en libre-service, cette personne :",
                    'audio_text' => $microTrottoir."\nPERSONNE 3 : Je serais d'accord pour les interdire, oui… mais seulement si la ville propose autre chose, des vélos en libre-service par exemple. Sinon, les gens vont reprendre leur voiture.",
                    'options' => ["est totalement pour.", "est pour, à certaines conditions.", "est totalement contre.", "ne se prononce pas."],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Je serais d'accord pour les interdire […] mais seulement si la ville propose autre chose » : son accord dépend d'une condition, annoncée par « seulement si ».",
                ],
                [
                    'id' => 'q19', 'type' => 'mcq',
                    'text' => "Micro-trottoir, personne 4 — Sur l'interdiction des trottinettes en libre-service, cette personne :",
                    'audio_text' => $microTrottoir."\nPERSONNE 4 : Franchement, je ne sais pas. Je ne les utilise jamais et elles ne me gênent pas non plus. Je n'ai pas vraiment d'avis là-dessus.",
                    'options' => ["est totalement pour.", "est pour, à certaines conditions.", "est totalement contre.", "ne se prononce pas."],
                    'correct_answer' => 'D',
                    'explanation' => "D. « je ne sais pas » et « Je n'ai pas vraiment d'avis » : elle ne prend pas position. Ne pas être gênée ne veut pas dire être contre l'interdiction.",
                ],
                [
                    'id' => 'q20', 'type' => 'mcq',
                    'text' => "Micro-trottoir, personne 5 — Sur l'interdiction des trottinettes en libre-service, cette personne :",
                    'audio_text' => $microTrottoir."\nPERSONNE 5 : Interdire, c'est la solution de facilité. Il suffirait d'obliger les entreprises à créer des places de stationnement. Mais tant que rien n'est fait, alors oui, je préfère qu'on les interdise.",
                    'options' => ["est totalement pour.", "est pour, à certaines conditions.", "est totalement contre.", "ne se prononce pas."],
                    'correct_answer' => 'B',
                    'explanation' => "B. Elle préfère une autre solution, mais accepte l'interdiction « tant que rien n'est fait » : son « oui » est conditionnel. C'est le piège de C — elle commence par critiquer l'interdiction, sans pour autant la rejeter.",
                ],
                // ── Questions 21 à 30 : radio — chroniques, interviews, reportage ──
                [
                    'id' => 'q21', 'type' => 'mcq',
                    'text' => "Chronique 1 — Selon cette chronique, le « prêt d'outils entre voisins » :",
                    'audio_text' => "Une perceuse sert en moyenne douze minutes dans toute sa vie. Douze minutes ! Partant de ce constat, des habitants de plusieurs immeubles ont créé une « outilthèque » : on y emprunte une perceuse, une échelle ou une ponceuse comme on emprunte un livre à la bibliothèque. Au-delà des économies, les initiateurs racontent surtout autre chose : on y croise des voisins à qui l'on n'avait jamais parlé. L'outil devient un prétexte à la rencontre.",
                    'options' => [
                        "permet surtout de vendre des outils d'occasion.",
                        "a été lancé par une bibliothèque municipale.",
                        "fait faire des économies, mais crée surtout des liens entre voisins.",
                        "concerne seulement les professionnels du bâtiment.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Au-delà des économies, les initiateurs racontent surtout autre chose : on y croise des voisins » ; « L'outil devient un prétexte à la rencontre ». La bibliothèque n'est qu'une comparaison (« comme on emprunte un livre »), pas l'organisatrice (B).",
                ],
                [
                    'id' => 'q22', 'type' => 'mcq',
                    'text' => "Chronique 2 — Que pense le chroniqueur des applications qui comptent les pas ?",
                    'audio_text' => "Dix mille pas par jour : l'objectif s'affiche sur des millions de téléphones. Savez-vous d'où vient ce chiffre ? D'une campagne publicitaire japonaise des années soixante, pour vendre un podomètre. Aucune étude ne l'avait fixé. Les recherches récentes montrent d'ailleurs que les bénéfices pour la santé apparaissent bien avant, dès six ou sept mille pas. Marcher reste une excellente idée ; c'est le chiffre magique qu'il faut regarder avec un peu d'ironie.",
                    'options' => [
                        "Il pense qu'il faut marcher au moins dix mille pas par jour.",
                        "Il déconseille de marcher tous les jours.",
                        "Il estime que le chiffre de dix mille pas n'a pas de base scientifique.",
                        "Il recommande d'acheter un podomètre.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. Le chiffre vient « d'une campagne publicitaire » et « Aucune étude ne l'avait fixé ». Il ne critique pas la marche (« Marcher reste une excellente idée ») mais le « chiffre magique ».",
                ],
                [
                    'id' => 'q23', 'type' => 'mcq',
                    'text' => "Interview 1 — Comment la libraire fait-elle vivre son commerce ?",
                    'audio_text' => $interviewLibraire,
                    'options' => [
                        "En proposant aussi d'autres services, comme le pain et les colis.",
                        "Grâce à la seule vente de livres.",
                        "Grâce à une aide de la mairie.",
                        "En vendant ses livres sur Internet.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Je ne vis pas de la vente de livres seule » : elle fait aussi « dépôt de pain » et « point relais pour les colis ». Ce sont ces services « qui font venir les gens ».",
                ],
                [
                    'id' => 'q24', 'type' => 'mcq',
                    'text' => "Interview 1 — Quel est le projet de la libraire ?",
                    'audio_text' => $interviewLibraire,
                    'options' => [
                        "Ouvrir une deuxième librairie.",
                        "Vendre son commerce à la mairie.",
                        "Ouvrir le mercredi toute la journée.",
                        "Organiser des lectures pour les enfants de l'école.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « Je voudrais organiser des lectures pour les enfants de l'école, le mercredi ». La mairie doit seulement lui répondre pour une salle : elle ne lui vend rien (B).",
                ],
                [
                    'id' => 'q25', 'type' => 'mcq',
                    'text' => "Interview 2 — Selon le médecin, quel est le principal problème de sommeil des étudiants ?",
                    'audio_text' => $interviewMedecin,
                    'options' => [
                        "Ils dorment beaucoup trop peu.",
                        "Leurs heures de sommeil changent tout le temps.",
                        "Ils font trop de sport le soir.",
                        "Ils dorment trop le week-end.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Ce qui me frappe, ce n'est pas tant le manque de sommeil que son irrégularité » : ils dorment « sept heures, mais jamais aux mêmes heures ». « ne… pas tant… que » écarte justement la réponse A.",
                ],
                [
                    'id' => 'q26', 'type' => 'mcq',
                    'text' => "Interview 2 — Que conseille le médecin ?",
                    'audio_text' => $interviewMedecin,
                    'options' => [
                        "De dormir davantage.",
                        "De se coucher avant minuit.",
                        "De faire la sieste l'après-midi.",
                        "De se lever tous les jours à la même heure.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « De se lever chaque jour à la même heure, même le dimanche ». Il précise : « Pas de dormir davantage, curieusement » — c'est le piège de A.",
                ],
                [
                    'id' => 'q27', 'type' => 'mcq',
                    'text' => "Interview 3 — Pourquoi la cheffe a-t-elle retiré le bœuf de sa carte ?",
                    'audio_text' => $interviewChef,
                    'options' => [
                        "Pour des raisons militantes.",
                        "Parce que ses clients le lui ont demandé.",
                        "Parce que le bœuf de qualité est devenu trop cher.",
                        "Parce qu'elle ne sait pas le cuisiner.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. La décision « est d'abord économique : le prix du bœuf de qualité a doublé en trois ans ». Elle écarte l'idée reçue : « On pense souvent que c'est une décision militante ».",
                ],
                [
                    'id' => 'q28', 'type' => 'mcq',
                    'text' => "Interview 3 — Comment les clients ont-ils réagi ?",
                    'audio_text' => $interviewChef,
                    'options' => [
                        "Quelques habitués ont protesté, mais les plats de légumes ont du succès.",
                        "Ils ont tous quitté le restaurant.",
                        "Ils n'ont rien remarqué.",
                        "Ils ont demandé le retour du bœuf.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Quelques habitués, au début », puis « les plats de légumes se vendent si bien qu'aujourd'hui, ce sont eux qui font notre réputation ». Les protestations sont limitées et passées.",
                ],
                [
                    'id' => 'q29', 'type' => 'mcq',
                    'text' => "Reportage — Quel est le but principal du jardin sur le toit, selon la responsable ?",
                    'audio_text' => $reportageJardins,
                    'options' => [
                        "Nourrir tous les habitants de la ville.",
                        "Aider des personnes à retrouver un travail et un rythme.",
                        "Vendre des légumes moins chers qu'au supermarché.",
                        "Rendre le centre commercial plus attractif.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Notre but, c'est de redonner un travail et un rythme à des personnes qui en avaient perdu l'habitude ». Elle écarte A elle-même : « Notre but n'est pas de nourrir la ville ».",
                ],
                [
                    'id' => 'q30', 'type' => 'mcq',
                    'text' => "Reportage — Qu'apprend-on sur le coût et l'avenir du projet ?",
                    'audio_text' => $reportageJardins,
                    'options' => [
                        "Le renforcement du toit a coûté cher, mais d'autres centres veulent l'imiter.",
                        "Le projet n'a presque rien coûté.",
                        "Le projet va s'arrêter faute d'argent.",
                        "La moitié du budget a servi à acheter des graines.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « renforcer le toit a représenté à lui seul la moitié du budget », et « trois autres centres commerciaux […] ont déjà demandé à suivre l'exemple ». La moitié du budget concerne le toit, pas les graines (D).",
                ],
                // ── Questions 31 à 40 : documents audio divers ──
                [
                    'id' => 'q31', 'type' => 'mcq',
                    'text' => "Document 1 — La présentatrice annonce :",
                    'audio_text' => "Bulletin économique : le prix du cacao a atteint cette semaine son plus haut niveau depuis quarante ans. En cause, deux mauvaises récoltes successives en Afrique de l'Ouest, liées à la sécheresse. Les fabricants de chocolat préviennent : les prix en magasin devraient augmenter d'ici Noël.",
                    'options' => [
                        "une baisse du prix du chocolat.",
                        "une récolte exceptionnelle de cacao.",
                        "la fermeture d'usines de chocolat.",
                        "une forte hausse du prix du cacao.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. Le prix du cacao a atteint « son plus haut niveau depuis quarante ans », à cause de « deux mauvaises récoltes ». La hausse en magasin est annoncée, pas une baisse (A).",
                ],
                [
                    'id' => 'q32', 'type' => 'mcq',
                    'text' => "Document 2 — Dans ce document, les infirmiers :",
                    'audio_text' => "Devant l'hôpital, une centaine d'infirmiers se sont rassemblés ce matin. Ils ne réclament pas d'augmentation de salaire, précisent-ils, mais l'embauche de personnel supplémentaire : dans certains services, une infirmière s'occupe la nuit de vingt-cinq patients. La direction a promis de les recevoir jeudi.",
                    'options' => [
                        "demandent une augmentation de salaire.",
                        "réclament des embauches supplémentaires.",
                        "protestent contre la fermeture de l'hôpital.",
                        "refusent de rencontrer la direction.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Ils réclament « l'embauche de personnel supplémentaire ». Ils précisent eux-mêmes ne pas demander d'augmentation (« Ils ne réclament pas d'augmentation de salaire ») : c'est le piège de A.",
                ],
                [
                    'id' => 'q33', 'type' => 'mcq',
                    'text' => "Document 3 — Que propose ce message publicitaire ?",
                    'audio_text' => "Vous partez en vacances et vous ne savez pas qui va s'occuper de votre chat ? Avec Voisins-Câlins, un voisin vérifié vient chez vous deux fois par jour nourrir votre animal et lui tenir compagnie. Inscription gratuite, première visite offerte !",
                    'options' => [
                        "Une pension pour chats pendant les vacances.",
                        "Une assurance pour les animaux.",
                        "Un service de garde d'animaux à domicile par un voisin.",
                        "Des voyages adaptés aux animaux.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « un voisin vérifié vient chez vous deux fois par jour nourrir votre animal » : l'animal reste à la maison. Ce n'est donc pas une pension, où l'on dépose l'animal (A).",
                ],
                [
                    'id' => 'q34', 'type' => 'mcq',
                    'text' => "Document 4 — Selon ce bulletin météo, demain :",
                    'audio_text' => "Demain, la matinée sera fraîche et brumeuse sur l'ensemble de la région. Le soleil s'imposera en début d'après-midi, avec des températures agréables, jusqu'à vingt-deux degrés. Attention toutefois en soirée : des orages sont possibles sur le relief.",
                    'options' => [
                        "il pleuvra toute la journée.",
                        "il fera très froid l'après-midi.",
                        "le temps sera ensoleillé l'après-midi, avec un risque d'orage le soir.",
                        "le brouillard durera toute la journée.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Le soleil s'imposera en début d'après-midi » et « des orages sont possibles » en soirée. La brume ne concerne que la matinée (contre D), et l'après-midi est « agréable » (contre B).",
                ],
                [
                    'id' => 'q35', 'type' => 'mcq',
                    'text' => "Document 5 — Qu'est-ce qui a changé dans cette bibliothèque ?",
                    'audio_text' => "Depuis septembre, la bibliothèque universitaire reste ouverte jusqu'à minuit, du lundi au jeudi. Une demande des étudiants, qui manquaient de places pour travailler le soir. Pour financer ces horaires, la bibliothèque fermera en revanche le samedi après-midi.",
                    'options' => [
                        "Elle reste ouverte tard le soir en semaine, mais ferme le samedi après-midi.",
                        "Elle ouvre désormais le dimanche.",
                        "Elle est réservée aux enseignants le soir.",
                        "Elle a doublé le nombre de ses places.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Elle ouvre « jusqu'à minuit, du lundi au jeudi », et en contrepartie « fermera en revanche le samedi après-midi ». « En revanche » annonce cette compensation.",
                ],
                [
                    'id' => 'q36', 'type' => 'mcq',
                    'text' => "Document 6 — Quel est le point de vue de l'auteure de ce billet ?",
                    'audio_text' => "On nous répète qu'il faut « sortir de sa zone de confort ». Comme si le confort était une faute ! Je ne dis pas qu'il ne faut jamais rien tenter. Je dis qu'on apprend aussi beaucoup en approfondissant ce que l'on fait déjà bien. La nouveauté permanente n'est pas une vertu en soi.",
                    'options' => [
                        "Il faut toujours chercher des expériences nouvelles.",
                        "Il ne faut jamais rien essayer de nouveau.",
                        "Le confort empêche d'apprendre.",
                        "Approfondir ce que l'on sait faire est aussi une façon d'apprendre.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « on apprend aussi beaucoup en approfondissant ce que l'on fait déjà bien ». Elle refuse l'excès inverse : « Je ne dis pas qu'il ne faut jamais rien tenter » (contre B).",
                ],
                [
                    'id' => 'q37', 'type' => 'mcq',
                    'text' => "Document 7 — Ce message de la mairie annonce :",
                    'audio_text' => "La mairie informe les habitants que la collecte des déchets verts change de jour : à partir du 1er avril, elle aura lieu le mercredi, et non plus le lundi. Les sacs devront être sortis la veille au soir, après dix-neuf heures.",
                    'options' => [
                        "la fin de la collecte des déchets verts.",
                        "un nouveau jour pour la collecte des déchets verts.",
                        "une collecte supplémentaire le lundi.",
                        "l'obligation d'acheter de nouveaux sacs.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. La collecte « aura lieu le mercredi, et non plus le lundi ». Elle continue (contre A) ; seuls le jour et l'heure de sortie des sacs sont précisés.",
                ],
                [
                    'id' => 'q38', 'type' => 'mcq',
                    'text' => "Document 8 — Selon ce spécialiste, pourquoi oublie-t-on ses rêves ?",
                    'audio_text' => "Si nous oublions nos rêves si vite, ce n'est pas qu'ils soient sans importance. Pendant le sommeil, le cerveau produit très peu d'une substance qui sert à fixer les souvenirs. Les rêves ne sont donc pas effacés : ils ne sont jamais vraiment enregistrés. Ceux dont on se souvient sont souvent ceux qui précèdent de peu le réveil.",
                    'options' => [
                        "Parce qu'ils n'ont aucune importance.",
                        "Parce que le réveil les efface.",
                        "Parce qu'on rêve très rarement.",
                        "Parce que le cerveau les enregistre très mal pendant le sommeil.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. Le cerveau « produit très peu d'une substance qui sert à fixer les souvenirs » : les rêves « ne sont jamais vraiment enregistrés ». Il écarte A dès la première phrase, et dit que les rêves ne sont « pas effacés » (contre B).",
                ],
                [
                    'id' => 'q39', 'type' => 'mcq',
                    'text' => "Document 9 — Que regrette l'auteur de cette chronique ?",
                    'audio_text' => "Nos villes se couvrent d'écrans publicitaires lumineux, allumés jour et nuit. On parle de leur consommation d'électricité, et c'est légitime. Mais on oublie l'essentiel : personne n'a choisi de les regarder. Un livre, on le ferme ; une émission, on l'éteint. L'écran dans la rue, lui, s'impose à tous.",
                    'options' => [
                        "Que les écrans consomment trop d'électricité.",
                        "Qu'il n'y ait pas assez de publicités dans la rue.",
                        "Que ces écrans s'imposent à tout le monde, sans choix possible.",
                        "Que les écrans soient éteints la nuit.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « on oublie l'essentiel : personne n'a choisi de les regarder » ; « L'écran dans la rue, lui, s'impose à tous ». La consommation est un argument qu'il juge « légitime » mais secondaire (A).",
                ],
                [
                    'id' => 'q40', 'type' => 'mcq',
                    'text' => "Document 10 — Que retient le conférencier de l'histoire de l'imprimerie ?",
                    'audio_text' => "On imagine l'imprimerie comme une révolution immédiate. Pourtant, cinquante ans après son invention, la plupart des livres imprimés imitaient encore les manuscrits : mêmes caractères, mêmes décorations. Il a fallu des décennies pour que l'on comprenne ce que cette technique permettait vraiment. Les nouvelles technologies changent d'abord nos outils ; elles ne changent nos habitudes que bien plus tard.",
                    'options' => [
                        "Les usages d'une technique nouvelle mettent longtemps à changer.",
                        "L'imprimerie a tout changé du jour au lendemain.",
                        "Les manuscrits étaient plus beaux que les livres imprimés.",
                        "L'imprimerie a été inventée il y a cinquante ans.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Les premiers livres imprimés « imitaient encore les manuscrits », et la technologie ne change nos habitudes « que bien plus tard ». B est l'idée reçue que le conférencier corrige (« On imagine… Pourtant »).",
                ],
            ],
        ],
    ],
];
