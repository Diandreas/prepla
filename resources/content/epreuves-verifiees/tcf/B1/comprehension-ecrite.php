<?php

/**
 * TCF tout public — épreuve « Compréhension écrite ».
 *
 * Écrit à la main d'après la page officielle du TCF tout public
 * (https://www.france-education-international.fr/test/tcf-tout-public,
 * copie lue le 2026-11-04 sur
 * https://web.archive.org/web/20260711113141/https://www.france-education-international.fr/test/tcf-tout-public) :
 *  - 29 questions à choix multiple, 4 choix de réponse (A, B, C, D), une seule
 *    réponse correcte, 45 minutes ;
 *  - « principe de difficulté progressive » : panneaux, messages et petites
 *    annonces au début, puis lettres, articles, textes argumentatifs, et pour
 *    finir des textes abstraits ou littéraires (C1-C2).
 *
 * Les documents sont originaux. Ce sont le FORMAT, le nombre d'items, le nombre
 * de choix et la progression qui reprennent l'épreuve, pas ses contenus.
 *
 * À l'épreuve, chaque document s'affiche avec sa question. Notre lecteur n'a
 * qu'un texte par exercice : les documents sont donc numérotés dans le passage,
 * et chaque question dit à quel document elle renvoie. Du document 22 au 25,
 * deux questions portent sur le même texte, comme pour les textes longs du
 * C1-C2.
 *
 * POURQUOI LE DOSSIER « B1 » : voir maitrise-structures.php. Le TCF n'a qu'une
 * épreuve, que l'application range au niveau B1 ; les documents vont du A1 au C2.
 */

return [
    'exam' => 'tcf',
    'level' => 'B1',
    'section' => 'comprehension-ecrite',
    'parts' => [
        [
            'part' => 1,
            'name' => 'Compréhension écrite, partie 1',
            'type' => 'mcq',
            'title' => 'TCF · Compréhension écrite — Série complète, du A1 au C2',
            'description' => "25 documents et 29 questions à quatre choix, de difficulté croissante. Format officiel du TCF tout public.",
            'content' => [
                'instructions' => "Lisez chaque document, puis répondez à la question qui le concerne : une seule réponse est correcte (A, B, C ou D). Les documents deviennent de plus en plus difficiles, comme au TCF. À l'épreuve, vous avez 45 minutes pour les 29 questions.",
                'passage' => "DOCUMENT 1 — Sur une porte\n"
                    ."PHARMACIE DU CENTRE\nOuverte du lundi au samedi, de 9 h à 19 h.\nFermée le dimanche.\n\n"

                    ."DOCUMENT 2 — Un SMS\n"
                    ."Salut Léa ! Je suis au café en face de la gare. Je t'attends. Bisous, Tom\n\n"

                    ."DOCUMENT 3 — Au restaurant\n"
                    ."MENU DU JOUR — 14 €\nSalade de tomates\nPoulet et frites\nGlace à la vanille\n(Boisson non comprise)\n\n"

                    ."DOCUMENT 4 — Une affiche\n"
                    ."COURS DE YOGA\nTous les mardis à 18 h 30, salle 2.\nApportez votre tapis !\nLe premier cours est gratuit.\n\n"

                    ."DOCUMENT 5 — Dans le hall d'un immeuble\n"
                    ."Chers voisins,\nL'ascenseur sera en réparation du 3 au 5 mars. Merci d'utiliser l'escalier pendant ces trois jours. "
                    ."Si vous avez besoin d'aide pour porter vos courses, adressez-vous à la gardienne, au rez-de-chaussée.\nLe syndic\n\n"

                    ."DOCUMENT 6 — Une petite annonce\n"
                    ."Vends vélo de ville, très bon état, acheté il y a un an. 120 € (prix neuf : 300 €). Casque offert. "
                    ."Visible le soir après 19 h. Tél. : 06 12 34 56 78.\n\n"

                    ."DOCUMENT 7 — Un courriel\n"
                    ."Bonjour Madame Diallo,\nJe vous confirme votre inscription à l'atelier de cuisine du samedi 12 octobre. "
                    ."L'atelier commence à 10 h et se termine vers 13 h : vous mangerez ensemble les plats préparés. "
                    ."Pensez à apporter une boîte pour emporter les restes. Le paiement se fait sur place, le jour de l'atelier.\n"
                    ."Cordialement,\nL'équipe de l'Atelier Saveurs\n\n"

                    ."DOCUMENT 8 — Règlement de la médiathèque\n"
                    ."Avec votre carte, vous pouvez emprunter 5 livres et 2 DVD pendant 3 semaines. "
                    ."En cas de retard, aucune amende n'est demandée, mais chaque semaine de retard entraîne une semaine sans emprunt.\n\n"

                    ."DOCUMENT 9 — Article d'un journal local\n"
                    ."Depuis le printemps, la ville de Vernay prête gratuitement des vélos électriques à ses habitants pendant un mois. "
                    ."« Nous ne voulons pas remplacer le bus, explique l'adjointe au maire. Nous voulons convaincre ceux qui prennent "
                    ."leur voiture pour faire deux kilomètres. » Le pari semble réussi : après l'essai, quatre participants sur dix ont "
                    ."acheté leur propre vélo, et la plupart disent laisser leur voiture au garage pour les petits trajets.\n\n"

                    ."DOCUMENT 10 — Un témoignage publié sur un blog\n"
                    ."J'ai longtemps cru que le télétravail me rendrait libre. En réalité, sans trajet pour marquer le début et la fin "
                    ."de la journée, mes journées n'avaient plus de limites : je répondais à des courriels à 22 heures, en pyjama, "
                    ."sur le canapé. Depuis septembre, je retourne au bureau deux jours par semaine, et je garde les trois autres à la maison. "
                    ."C'est cet équilibre qui m'a sauvée.\n\n"

                    ."DOCUMENT 11 — Affiché à l'entrée de la piscine municipale\n"
                    ."Le bonnet de bain est obligatoire. Les shorts de bain sont interdits : seuls les maillots ajustés sont acceptés. "
                    ."Les enfants de moins de 10 ans doivent être accompagnés d'un adulte en tenue de bain, présent dans l'eau ou au bord du bassin.\n\n"

                    ."DOCUMENT 12 — Une lettre envoyée à un magasin\n"
                    ."Madame, Monsieur,\nLe 4 mai, j'ai acheté dans votre magasin un lave-linge avec livraison et installation, facturées 49 euros. "
                    ."Le livreur est venu avec deux jours de retard et il est reparti sans brancher l'appareil, faute de temps. "
                    ."Un voisin l'a installé pour moi : la machine fonctionne bien et je souhaite la garder. En revanche, je vous demande de me "
                    ."rembourser les 49 euros, puisque le service n'a pas été rendu.\nVeuillez agréer, Madame, Monsieur, mes salutations distinguées.\nPaul Mercier\n\n"

                    ."DOCUMENT 13 — Une offre d'emploi\n"
                    ."La Brasserie du Port recherche un serveur ou une serveuse pour le samedi et le dimanche uniquement. "
                    ."Débutants acceptés : nous assurons la formation. Bonne présentation et sens du contact indispensables. "
                    ."Envoyez votre CV et quelques lignes de motivation à recrutement@brasserieduport.fr.\n\n"

                    ."DOCUMENT 14 — Sur le site d'un festival\n"
                    ."En cas de pluie, les concerts prévus dans le parc auront lieu au gymnase municipal, aux mêmes horaires. "
                    ."Les billets restent valables. Aucun remboursement ne sera effectué.\n\n"

                    ."DOCUMENT 15 — Extrait d'un article de magazine\n"
                    ."Le « tourisme lent » a le vent en poupe : on ne prend plus l'avion pour un week-end, on traverse l'Europe en train, "
                    ."on s'arrête, on prend son temps. Il serait pourtant naïf d'y voir une révolution. Partir trois semaines en train "
                    ."suppose du temps et de l'argent dont la plupart des salariés ne disposent pas. Le mouvement annonce moins la fin du "
                    ."tourisme de masse que l'apparition d'une nouvelle manière, pour quelques-uns, de se distinguer.\n\n"

                    ."DOCUMENT 16 — Extrait d'un article économique\n"
                    ."Pendant six mois, 40 entreprises ont testé la semaine de quatre jours sans baisse de salaire. Résultat : la production "
                    ."est restée au même niveau, et les arrêts maladie ont reculé d'un tiers. Les cadres, eux, sont plus partagés : "
                    ."organiser les réunions quand une partie de l'équipe est absente le lundi et l'autre le vendredi demande, disent-ils, "
                    ."« un vrai casse-tête ».\n\n"

                    ."DOCUMENT 17 — Courrier d'un lecteur\n"
                    ."Fermer le centre-ville aux voitures ? Je n'y suis pas opposé, bien au contraire. Mais pas dans ces conditions. "
                    ."Tant que le dernier bus partira à 20 h 30 et que les parkings relais resteront payants, interdire la voiture reviendra "
                    ."à interdire le centre à tous ceux qui habitent loin. Qu'on améliore d'abord les transports, et je serai le premier "
                    ."à applaudir.\n\n"

                    ."DOCUMENT 18 — Extrait d'un article scientifique de vulgarisation\n"
                    ."On sait depuis longtemps qu'un comprimé sans principe actif peut soulager un patient qui croit recevoir un vrai "
                    ."médicament : c'est l'effet placebo. Plus étonnant encore : dans plusieurs essais récents, des patients à qui l'on avait "
                    ."clairement annoncé qu'ils recevaient un faux médicament ont tout de même déclaré souffrir moins. Pour les chercheurs, "
                    ."le rituel du soin compterait donc autant que la croyance.\n\n"

                    ."DOCUMENT 19 — Conditions générales d'un abonnement\n"
                    ."L'abonnement est conclu pour un an et se renouvelle automatiquement à sa date anniversaire. Après la première année, "
                    ."il peut être résilié à tout moment et sans frais, par lettre recommandée ou depuis l'espace client, moyennant un préavis "
                    ."d'un mois.\n\n"

                    ."DOCUMENT 20 — Critique littéraire\n"
                    ."Le premier roman d'Inès Barral se lit d'une traite : des personnages qu'on n'oublie pas, une langue nerveuse, un humour "
                    ."qui ne tombe jamais dans la facilité. On regrette d'autant plus la dernière partie, expédiée en vingt pages, comme si "
                    ."l'autrice avait été pressée d'en finir. Un livre à lire, malgré tout.\n\n"

                    ."DOCUMENT 21 — Extrait d'un essai sur l'éducation\n"
                    ."On a longtemps opposé la mémoire à l'intelligence, comme si apprendre par cœur empêchait de réfléchir. Les sciences "
                    ."cognitives invitent à nuancer : on ne raisonne jamais à vide, et sans connaissances solidement installées en mémoire, "
                    ."il n'y a ni comparaison, ni déduction, ni esprit critique. Ce n'est donc pas le par-cœur qu'il faut bannir de l'école, "
                    ."mais le par-cœur sans compréhension.\n\n"

                    ."DOCUMENT 22 — Extrait d'un article de fond (questions 22 et 23)\n"
                    ."Planter des arbres, ouvrir des parcs, transformer d'anciennes voies ferrées en promenades : rien ne semble plus "
                    ."consensuel que le verdissement des villes. Les études menées dans plusieurs métropoles révèlent pourtant un effet "
                    ."pervers. Dans les quartiers populaires, l'arrivée d'un grand parc fait grimper les loyers, attire de nouveaux habitants "
                    ."plus aisés et pousse les anciens vers la périphérie, loin de ces espaces verts qui leur étaient d'abord destinés. "
                    ."Les urbanistes parlent de « gentrification verte ». Faut-il pour autant renoncer à la nature en ville ? Certains plaident "
                    ."pour des aménagements « juste assez verts » : de petites interventions dispersées, couplées à un encadrement des loyers "
                    ."et à la construction de logements sociaux, afin que ceux qui vivaient là profitent eux aussi des arbres qu'on plante.\n\n"

                    ."DOCUMENT 23 — Extrait d'une tribune (questions 24 et 25)\n"
                    ."Les logiciels de traduction progressent si vite qu'on annonce régulièrement la disparition du métier de traducteur. "
                    ."C'est confondre la traduction avec le passage d'une langue à une autre. Une machine rend « il pleut des cordes » par "
                    ."une expression équivalente ; elle hésite davantage lorsqu'un roman joue précisément sur le double sens d'une formule, "
                    ."ou lorsqu'un contrat repose sur une virgule. Le traducteur n'est pas menacé : il est déplacé. Hier artisan de chaque "
                    ."phrase, il devient l'arbitre de ce que la machine ne peut pas trancher — et cette part-là, la moins visible, est "
                    ."aussi la plus exigeante.\n\n"

                    ."DOCUMENT 24 — Extrait d'un récit (questions 26 et 27)\n"
                    ."Mon oncle Armand se flattait de n'avoir jamais lu un roman. « La vie m'en a assez appris », disait-il, en fermant les "
                    ."yeux d'un air pénétré, comme on se recueille devant une vérité trop haute pour les autres. Chaque dimanche, il nous "
                    ."racontait pourtant, avec un luxe de détails, la vie de personnages qu'il prétendait avoir connus à l'armée et qui "
                    ."ressemblaient étrangement à ceux des feuilletons que ma tante écoutait à la radio. Personne, à table, n'aurait osé le "
                    ."lui faire remarquer : on ne contredit pas un homme qui a tout appris de la vie.\n\n"

                    ."DOCUMENT 25 — Extrait d'un essai philosophique (questions 28 et 29)\n"
                    ."Nous parlons du progrès comme d'une flèche : il irait d'un point à un autre, toujours dans le même sens, et chaque "
                    ."génération n'aurait qu'à poursuivre le mouvement. Mais le progrès d'un domaine n'entraîne pas celui des autres. "
                    ."Le siècle qui a vaincu la variole a aussi inventé les bombardements de masse. Il faudrait donc parler de progrès au "
                    ."pluriel, et accepter qu'ils se contredisent parfois. Ce n'est pas renoncer à l'idée de progrès ; c'est refuser d'en "
                    ."faire une loi de l'histoire, qui nous dispenserait de choisir lesquels nous voulons.",
            ],
            'questions' => [
                // ── A1 ──
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Document 1 — Le dimanche, la pharmacie :",
                    'options' => ["ouvre à 9 h.", "ferme à 19 h.", "est fermée.", "ouvre seulement l'après-midi."],
                    'correct_answer' => 'C',
                    'explanation' => "C. La dernière ligne dit « Fermée le dimanche ». Les horaires de 9 h à 19 h valent « du lundi au samedi » : ils ne concernent pas le dimanche.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Document 2 — Où est Tom ?",
                    'options' => ["À la gare.", "Dans un café.", "Chez Léa.", "À son travail."],
                    'correct_answer' => 'B',
                    'explanation' => "B. Tom écrit « Je suis au café en face de la gare ». La gare est seulement un repère pour trouver le café : « en face de » veut dire de l'autre côté de la rue, pas dedans.",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Document 3 — Avec le menu à 14 € :",
                    'options' => ["la boisson est gratuite.", "il y a un dessert.", "on mange du poisson.", "il n'y a pas d'entrée."],
                    'correct_answer' => 'B',
                    'explanation' => "B. La « glace à la vanille » est un dessert. La boisson n'est pas gratuite : « non comprise » veut dire qu'elle est en plus. Le plat est du poulet, et la salade de tomates est une entrée.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Document 4 — Pour le cours de yoga, il faut :",
                    'options' => ["payer le premier cours.", "venir le mardi matin.", "apporter un tapis.", "aller dans la salle 3."],
                    'correct_answer' => 'C',
                    'explanation' => "C. L'affiche dit « Apportez votre tapis ! ». Le premier cours est gratuit, il a lieu le mardi à 18 h 30 (le soir, pas le matin), dans la salle 2.",
                ],
                // ── A2 ──
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Document 5 — Du 3 au 5 mars, les habitants :",
                    'options' => ["ne peuvent pas sortir de l'immeuble.", "doivent prendre l'escalier.", "doivent téléphoner au syndic.", "peuvent prendre l'ascenseur le matin."],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Merci d'utiliser l'escalier pendant ces trois jours » : l'ascenseur est en réparation, à aucun moment de la journée. Pour de l'aide, il faut voir la gardienne, pas appeler le syndic.",
                ],
                [
                    'id' => 'q6', 'type' => 'mcq',
                    'text' => "Document 6 — Qu'est-ce que le vendeur propose en plus du vélo ?",
                    'options' => ["La livraison.", "Un casque.", "Une réparation gratuite.", "Un deuxième vélo."],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Casque offert » : le casque est donné avec le vélo. L'annonce ne parle ni de livraison ni de réparation ; au contraire, il faut venir voir le vélo le soir.",
                ],
                [
                    'id' => 'q7', 'type' => 'mcq',
                    'text' => "Document 7 — Que doit faire Madame Diallo ?",
                    'options' => ["Payer l'atelier avant le 12 octobre.", "Apporter une boîte.", "Arriver à 13 h.", "Préparer un plat chez elle."],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Pensez à apporter une boîte pour emporter les restes ». Le paiement se fait « sur place, le jour de l'atelier », donc pas avant. L'atelier commence à 10 h : 13 h, c'est la fin.",
                ],
                [
                    'id' => 'q8', 'type' => 'mcq',
                    'text' => "Document 8 — Vous rendez un livre avec une semaine de retard. Que se passe-t-il ?",
                    'options' => ["Vous payez une amende.", "Vous ne pouvez pas emprunter pendant une semaine.", "Vous perdez votre carte.", "Vous pouvez garder le livre trois semaines de plus."],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Chaque semaine de retard entraîne une semaine sans emprunt ». L'amende est le piège : le règlement précise justement qu'« aucune amende n'est demandée ».",
                ],
                // ── B1 ──
                [
                    'id' => 'q9', 'type' => 'mcq',
                    'text' => "Document 9 — Quel est le but principal de l'opération ?",
                    'options' => [
                        "Remplacer les bus par des vélos.",
                        "Faire moins utiliser la voiture pour les petits trajets.",
                        "Vendre des vélos électriques aux habitants.",
                        "Attirer des touristes à Vernay.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. L'adjointe le dit : « convaincre ceux qui prennent leur voiture pour faire deux kilomètres ». Elle écarte expressément l'option A (« Nous ne voulons pas remplacer le bus »). Les achats de vélos sont un résultat, pas le but : la ville prête, elle ne vend pas.",
                ],
                [
                    'id' => 'q10', 'type' => 'mcq',
                    'text' => "Document 10 — Que pense l'auteure du télétravail ?",
                    'options' => [
                        "Il lui a apporté la liberté qu'elle espérait.",
                        "Il a effacé la limite entre son travail et sa vie privée.",
                        "Elle a décidé de ne plus jamais travailler chez elle.",
                        "Il lui a fait perdre son emploi.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « mes journées n'avaient plus de limites : je répondais à des courriels à 22 heures » — le travail débordait sur la soirée. A est ce qu'elle « croyait », le contraire de ce qu'elle a vécu. C est faux : elle « garde les trois autres [jours] à la maison ».",
                ],
                [
                    'id' => 'q11', 'type' => 'mcq',
                    'text' => "Document 11 — Un père veut emmener sa fille de 8 ans à la piscine. Que doit-il faire ?",
                    'options' => [
                        "Attendre sa fille à la cafétéria.",
                        "Être lui-même en tenue de bain.",
                        "Laisser sa fille entrer seule avec un bonnet.",
                        "Présenter un certificat médical.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Les enfants de moins de 10 ans « doivent être accompagnés d'un adulte en tenue de bain ». Avec 8 ans, la fille est concernée : elle ne peut pas entrer seule, et son père doit être en maillot, dans l'eau ou au bord du bassin.",
                ],
                [
                    'id' => 'q12', 'type' => 'mcq',
                    'text' => "Document 12 — Que demande Paul Mercier ?",
                    'options' => [
                        "Un nouveau lave-linge.",
                        "Le remboursement des frais de livraison et d'installation.",
                        "L'annulation de sa commande.",
                        "La visite gratuite d'un technicien.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « je vous demande de me rembourser les 49 euros », c'est-à-dire la livraison et l'installation, « puisque le service n'a pas été rendu ». Il précise que la machine fonctionne et qu'il « souhaite la garder » : il ne veut ni l'échanger ni annuler.",
                ],
                [
                    'id' => 'q13', 'type' => 'mcq',
                    'text' => "Document 13 — Pour ce poste :",
                    'options' => [
                        "il faut déjà avoir travaillé dans un restaurant.",
                        "on travaille seulement le week-end.",
                        "le candidat doit payer sa formation.",
                        "il faut se présenter directement au restaurant.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « pour le samedi et le dimanche uniquement ». « Débutants acceptés » exclut A, et « nous assurons la formation » veut dire que la brasserie s'en charge. La candidature se fait par courriel.",
                ],
                [
                    'id' => 'q14', 'type' => 'mcq',
                    'text' => "Document 14 — S'il pleut, les spectateurs :",
                    'options' => [
                        "sont remboursés.",
                        "doivent acheter un nouveau billet.",
                        "vont au gymnase avec le même billet.",
                        "peuvent revenir le lendemain.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. Les concerts « auront lieu au gymnase municipal, aux mêmes horaires » et « les billets restent valables ». Le texte exclut le remboursement (« Aucun remboursement ») et ne parle d'aucun report au lendemain.",
                ],
                // ── B2 ──
                [
                    'id' => 'q15', 'type' => 'mcq',
                    'text' => "Document 15 — Selon l'auteur, le « tourisme lent » :",
                    'options' => [
                        "va bientôt remplacer le tourisme de masse.",
                        "reste accessible surtout à une minorité.",
                        "coûte moins cher que les voyages en avion.",
                        "est encouragé par les employeurs.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Il « suppose du temps et de l'argent dont la plupart des salariés ne disposent pas », et devient une manière, « pour quelques-uns, de se distinguer ». A est la thèse que l'auteur réfute : « Il serait pourtant naïf d'y voir une révolution ».",
                ],
                [
                    'id' => 'q16', 'type' => 'mcq',
                    'text' => "Document 16 — Qu'a montré l'expérience de la semaine de quatre jours ?",
                    'options' => [
                        "Une forte hausse de la production.",
                        "Une production stable et moins d'arrêts maladie.",
                        "Un échec, à cause de l'opposition des cadres.",
                        "Une baisse des salaires dans les entreprises testées.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « la production est restée au même niveau, et les arrêts maladie ont reculé d'un tiers ». La production n'a pas augmenté, les salaires n'ont pas baissé (« sans baisse de salaire »), et les cadres sont « partagés », pas opposés au point de faire échouer l'essai.",
                ],
                [
                    'id' => 'q17', 'type' => 'mcq',
                    'text' => "Document 17 — Quelle est la position de l'auteur sur la fermeture du centre-ville aux voitures ?",
                    'options' => [
                        "Il y est totalement opposé.",
                        "Il y est favorable, à condition d'améliorer d'abord les transports.",
                        "Elle lui est indifférente.",
                        "Il la soutient sans aucune réserve.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Je n'y suis pas opposé, bien au contraire. Mais pas dans ces conditions » puis « Qu'on améliore d'abord les transports, et je serai le premier à applaudir ». Il est pour l'idée, avec une condition : ce n'est ni un refus (A) ni un soutien sans réserve (D).",
                ],
                [
                    'id' => 'q18', 'type' => 'mcq',
                    'text' => "Document 18 — Qu'est-ce qui étonne les chercheurs ?",
                    'options' => [
                        "Qu'un faux médicament puisse soulager un patient qui croit en prendre un vrai.",
                        "Qu'un faux médicament agisse même quand le patient sait qu'il est faux.",
                        "Que les vrais médicaments agissent moins bien que les faux.",
                        "Que l'effet placebo disparaisse lorsque le patient est informé.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Le texte annonce « Plus étonnant encore » : des patients informés qu'ils recevaient « un faux médicament » ont tout de même déclaré souffrir moins. A décrit l'effet placebo classique, que l'on « sait depuis longtemps » : ce n'est pas ce qui étonne. D dit le contraire du texte.",
                ],
                [
                    'id' => 'q19', 'type' => 'mcq',
                    'text' => "Document 19 — Un client abonné depuis 18 mois veut arrêter son abonnement. Il peut :",
                    'options' => [
                        "le faire seulement à la date anniversaire.",
                        "le faire sans frais, en respectant un préavis d'un mois.",
                        "le faire à condition de payer des frais de résiliation.",
                        "le faire uniquement en se rendant en agence.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Après la première année, il peut être résilié à tout moment et sans frais […] moyennant un préavis d'un mois ». 18 mois, c'est plus d'un an : la résiliation est libre, par lettre recommandée ou en ligne. La « date anniversaire » ne concerne que le renouvellement automatique.",
                ],
                [
                    'id' => 'q20', 'type' => 'mcq',
                    'text' => "Document 20 — Que pense le critique du roman ?",
                    'options' => [
                        "Il le trouve parfait du début à la fin.",
                        "Il l'apprécie, mais regrette une fin trop rapide.",
                        "Il déconseille de le lire.",
                        "Il le trouve trop long.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Les éloges (« se lit d'une traite », « une langue nerveuse ») sont suivis d'un regret : la dernière partie, « expédiée en vingt pages ». La conclusion, « Un livre à lire, malgré tout », exclut C. Le reproche porte sur une fin trop courte, pas sur un livre trop long.",
                ],
                // ── C1 ──
                [
                    'id' => 'q21', 'type' => 'mcq',
                    'text' => "Document 21 — Quelle est la thèse de l'auteur ?",
                    'options' => [
                        "La mémorisation est devenue inutile à l'école.",
                        "La mémorisation est indispensable, pourvu qu'elle s'accompagne de compréhension.",
                        "Les sciences cognitives condamnent l'apprentissage par cœur.",
                        "L'intelligence ne dépend pas des connaissances.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. La dernière phrase donne la thèse : « Ce n'est donc pas le par-cœur qu'il faut bannir […], mais le par-cœur sans compréhension ». Les sciences cognitives montrent au contraire qu'« on ne raisonne jamais à vide » : C et D inversent l'argument, et A est l'idée reçue que le texte combat.",
                ],
                [
                    'id' => 'q22', 'type' => 'mcq',
                    'text' => "Document 22 — Quel paradoxe l'article met-il en évidence ?",
                    'options' => [
                        "Les villes plantent des arbres mais réduisent le nombre de parcs.",
                        "Des espaces verts destinés aux habitants modestes contribuent à les faire partir.",
                        "Les habitants des quartiers populaires refusent les nouveaux parcs.",
                        "Les parcs coûtent plus cher aux villes qu'ils ne leur rapportent.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Le parc « fait grimper les loyers » et « pousse les anciens vers la périphérie, loin de ces espaces verts qui leur étaient d'abord destinés » : la mesure prévue pour eux finit par les chasser. C'est ce que l'article nomme « gentrification verte ». Aucune hostilité des habitants (C) ni aucun bilan financier (D) n'est évoqué.",
                ],
                [
                    'id' => 'q23', 'type' => 'mcq',
                    'text' => "Document 22 — Quelle réponse certains urbanistes proposent-ils ?",
                    'options' => [
                        "Renoncer à créer des espaces verts dans les quartiers populaires.",
                        "Réserver les nouveaux parcs aux habitants du quartier.",
                        "Multiplier de petits aménagements verts, accompagnés de mesures sur le logement.",
                        "Construire les grands parcs en périphérie plutôt qu'en ville.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. Ils plaident pour des aménagements « juste assez verts » : « de petites interventions dispersées, couplées à un encadrement des loyers et à la construction de logements sociaux ». La question rhétorique « Faut-il pour autant renoncer à la nature en ville ? » écarte A.",
                ],
                [
                    'id' => 'q24', 'type' => 'mcq',
                    'text' => "Document 23 — Selon l'auteur, ceux qui annoncent la disparition des traducteurs :",
                    'options' => [
                        "surestiment la difficulté des contrats.",
                        "réduisent la traduction au simple passage d'une langue à une autre.",
                        "ignorent que les logiciels progressent rapidement.",
                        "pensent que les machines ne savent pas traduire les expressions.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « C'est confondre la traduction avec le passage d'une langue à une autre » : l'erreur qu'il dénonce est une définition trop étroite du métier. Il reconnaît au contraire que les logiciels « progressent si vite » (C) et qu'ils savent rendre une expression figée comme « il pleut des cordes » (D).",
                ],
                [
                    'id' => 'q25', 'type' => 'mcq',
                    'text' => "Document 23 — Que signifie « Le traducteur n'est pas menacé : il est déplacé » ?",
                    'options' => [
                        "Le traducteur doit changer de pays pour trouver du travail.",
                        "Son rôle change : il tranche ce que la machine ne sait pas décider.",
                        "Il sera remplacé par la machine, mais plus tard que prévu.",
                        "Il doit abandonner la traduction littéraire pour la traduction juridique.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « déplacé » est pris au sens figuré : la place du traducteur dans le travail change. La phrase suivante l'explique : « Hier artisan de chaque phrase, il devient l'arbitre de ce que la machine ne peut pas trancher ». Le sens géographique (A) est le contresens tendu par le mot.",
                ],
                // ── C2 ──
                [
                    'id' => 'q26', 'type' => 'mcq',
                    'text' => "Document 24 — Quel est le ton du narrateur à l'égard de l'oncle Armand ?",
                    'options' => ["Admiratif.", "Ironique.", "Indigné.", "Nostalgique."],
                    'correct_answer' => 'B',
                    'explanation' => "B. Le narrateur feint de reprendre les grands airs de l'oncle (« une vérité trop haute pour les autres ») tout en montrant qu'il emprunte ses histoires aux feuilletons de la radio. La dernière phrase répète sa formule, « un homme qui a tout appris de la vie », pour mieux s'en moquer : c'est de l'ironie, pas de l'admiration.",
                ],
                [
                    'id' => 'q27', 'type' => 'mcq',
                    'text' => "Document 24 — Que laisse entendre le narrateur ?",
                    'options' => [
                        "L'oncle avait réellement connu ces personnages à l'armée.",
                        "L'oncle tirait ses récits de fictions, tout en méprisant les romans.",
                        "La tante inventait les histoires que racontait l'oncle.",
                        "La famille admirait sincèrement la sagesse de l'oncle.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Ses personnages « ressemblaient étrangement à ceux des feuilletons » : l'adverbe « étrangement » suggère, sans le dire, que l'oncle puise dans la fiction qu'il prétend mépriser. Le silence de la table ne vient pas de l'admiration (D) mais de la crainte de le contredire : « Personne […] n'aurait osé ».",
                ],
                [
                    'id' => 'q28', 'type' => 'mcq',
                    'text' => "Document 25 — Quelle conception du progrès l'auteur critique-t-il ?",
                    'options' => [
                        "Celle d'un mouvement continu, dans une seule direction, valable pour tous les domaines.",
                        "Celle d'un progrès réservé aux sciences et à la médecine.",
                        "Celle d'un progrès qui dépend de choix politiques.",
                        "Celle d'un progrès qui se mesure au pluriel.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. L'image de la « flèche » qui va « toujours dans le même sens » est ce qu'il conteste, puisque « le progrès d'un domaine n'entraîne pas celui des autres ». D est sa propre proposition (« parler de progrès au pluriel »), et C rejoint sa conclusion — choisir « lesquels nous voulons ».",
                ],
                [
                    'id' => 'q29', 'type' => 'mcq',
                    'text' => "Document 25 — Quel rôle joue l'exemple de la variole et des bombardements de masse ?",
                    'options' => [
                        "Il prouve que la médecine a progressé plus vite que l'armement.",
                        "Il montre que des progrès de nature différente peuvent coexister et s'opposer.",
                        "Il illustre la supériorité du XXe siècle sur les siècles précédents.",
                        "Il sert à condamner toute idée de progrès.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Un même siècle a produit une victoire médicale et une catastrophe militaire : l'exemple appuie l'idée que les progrès sont pluriels et « se contredisent parfois ». D est le piège : l'auteur précise « Ce n'est pas renoncer à l'idée de progrès ».",
                ],
            ],
        ],
    ],
];
