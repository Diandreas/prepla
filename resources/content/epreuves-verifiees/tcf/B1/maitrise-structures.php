<?php

/**
 * TCF tout public — épreuve « Maîtrise des structures de la langue ».
 *
 * Écrit à la main d'après la page officielle du TCF tout public
 * (https://www.france-education-international.fr/test/tcf-tout-public,
 * copie lue le 2026-11-04 sur
 * https://web.archive.org/web/20260711113141/https://www.france-education-international.fr/test/tcf-tout-public) :
 *  - 18 questions à choix multiple, 4 choix de réponse (A, B, C, D), une seule
 *    réponse correcte, 15 minutes ;
 *  - « principe de difficulté progressive » : du A1 au C2 dans la même série ;
 *  - trois familles de questions, que l'on retrouve ici : repérer une erreur
 *    de lexique ou de registre, choisir l'équivalent d'une expression ou d'un
 *    terme grammatical, choisir la formulation correcte dans une situation.
 *
 * Les phrases sont originales. Ce sont le FORMAT, le nombre d'items, le nombre
 * de choix et la progression qui reprennent l'épreuve, pas ses contenus.
 *
 * POURQUOI LE DOSSIER « B1 ». Le TCF n'a pas de niveaux : une seule épreuve
 * situe le candidat du A1 au C2. Mais l'application range chaque exercice à un
 * niveau, et c'est par ce niveau qu'elle le sert. La série complète est donc
 * rangée au B1 — le niveau par défaut d'un apprenant, et celui du plan
 * « TCF — entraînement niveau B1 ». Les questions, elles, vont du A1 au C2.
 *
 * À garder si ce fichier est recopié : quatre options partout (l'épreuve n'en
 * a jamais trois), et une explication qui dit pourquoi la bonne forme gagne ET
 * pourquoi l'intrus le plus tentant perd.
 */

return [
    'exam' => 'tcf',
    'level' => 'B1',
    'section' => 'maitrise-structures',
    'parts' => [
        [
            'part' => 1,
            'name' => 'Maîtrise des structures de la langue, partie 1',
            'type' => 'grammar-mcq',
            'title' => 'TCF · Maîtrise des structures — Série complète, du A1 au C2',
            'description' => "18 questions à quatre choix, de difficulté croissante, comme le jour du test. Format officiel du TCF tout public.",
            'content' => [
                'instructions' => "Choisissez pour chaque question la seule bonne réponse : A, B, C ou D. Les questions deviennent de plus en plus difficiles, comme au TCF. À l'épreuve, vous avez 15 minutes pour les 18 questions : environ 50 secondes chacune.",
            ],
            'questions' => [
                // ── A1 ──
                [
                    'id' => 'q1', 'type' => 'grammar-mcq',
                    'text' => "Nous ___ deux enfants : un garçon et une fille.",
                    'options' => [
                        'sommes',
                        'ont',
                        'avons',
                        'êtes',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. On « a » des enfants : c'est le verbe avoir, et avec « nous » il se conjugue « avons ». « sommes » est le verbe être (nous sommes contents), « ont » va avec « ils », et « êtes » avec « vous ».",
                ],
                [
                    'id' => 'q2', 'type' => 'grammar-mcq',
                    'text' => "Mon frère habite ___ Portugal depuis trois ans.",
                    'options' => [
                        'au',
                        'à',
                        'en',
                        'aux',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Le Portugal est un pays masculin qui commence par une consonne : on dit « au Portugal ». « en » sert pour les pays féminins (en France, en Italie), « à » pour les villes (à Lisbonne) et « aux » pour les pays au pluriel (aux États-Unis).",
                ],
                [
                    'id' => 'q3', 'type' => 'grammar-mcq',
                    'text' => "Le samedi, j'achète ___ pommes au marché.",
                    'options' => [
                        'du',
                        'de la',
                        'un',
                        'des',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « pommes » est au pluriel : l'article indéfini pluriel est « des ». « du » et « de la » s'emploient devant un nom au singulier qu'on ne compte pas (du pain, de la confiture), et « un » est singulier.",
                ],
                // ── A2 ──
                [
                    'id' => 'q4', 'type' => 'grammar-mcq',
                    'text' => "Hier soir, nous ___ au cinéma avec des amis.",
                    'options' => [
                        'allons',
                        'sommes allés',
                        'avons allé',
                        'irons',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Hier soir » demande un temps du passé : le passé composé. « aller » fait partie des verbes qui se conjuguent avec « être » : nous sommes allés. « avons allé » est le piège classique — l'auxiliaire est faux. « allons » est au présent et « irons » au futur.",
                ],
                [
                    'id' => 'q5', 'type' => 'grammar-mcq',
                    'text' => "Dakar, c'est la ville ___ je suis née.",
                    'options' => [
                        'que',
                        'qui',
                        'dont',
                        'où',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. On est né DANS une ville : le pronom relatif qui reprend un lieu est « où ». « que » remplace un complément direct (la ville que j'aime), « qui » un sujet (la ville qui me plaît), « dont » un complément avec « de » (la ville dont je parle).",
                ],
                [
                    'id' => 'q6', 'type' => 'grammar-mcq',
                    'text' => "— Tu as vu Paul aujourd'hui ? — Oui, je ___ ai vu ce matin.",
                    'options' => [
                        'lui',
                        'le',
                        "l'",
                        'y',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. On voit QUELQU'UN : « Paul » est complément direct, donc pronom « le », qui s'élide devant la voyelle de « ai » : je l'ai vu. « le ai » ne s'écrit jamais. « lui » remplace un complément avec « à » (je lui parle), « y » un lieu ou une chose introduite par « à ».",
                ],
                // ── B1 ──
                [
                    'id' => 'q7', 'type' => 'grammar-mcq',
                    'text' => "Il faut que tu ___ ce dossier avant midi.",
                    'options' => [
                        'finisses',
                        'finis',
                        'finiras',
                        'finirais',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Il faut que » exprime une obligation et commande toujours le subjonctif : que tu finisses. « finis » est l'indicatif présent — il se prononce presque pareil, c'est le piège. Le futur « finiras » et le conditionnel « finirais » ne suivent jamais « il faut que ».",
                ],
                [
                    'id' => 'q8', 'type' => 'grammar-mcq',
                    'text' => "Si j'avais plus de temps libre, je ___ davantage.",
                    'options' => [
                        'lis',
                        'lirais',
                        'lirai',
                        'lise',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Si » + imparfait (si j'avais) exprime une hypothèse ; la conséquence se met au conditionnel présent : je lirais. « lirai » (futur) répondrait à « si » + présent (si j'ai le temps, je lirai). Le subjonctif « lise » ne suit jamais une hypothèse en « si ».",
                ],
                [
                    'id' => 'q9', 'type' => 'grammar-mcq',
                    'text' => "Vous écrivez un courriel à une cliente que vous ne connaissez pas. Quelle formule convient pour terminer ?",
                    'options' => [
                        "Je vous prie d'agréer, Madame, mes salutations distinguées.",
                        "Bisous et à bientôt !",
                        "Salut, à plus !",
                        "Bonne journée, ma belle.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Avec une cliente inconnue, le registre est formel : vouvoiement et formule de politesse complète, « Je vous prie d'agréer… mes salutations distinguées ». Les trois autres appartiennent au registre familier — « Bisous », « à plus », « ma belle » — et seraient déplacés, voire vexants, dans un échange professionnel.",
                ],
                // ── B2 ──
                [
                    'id' => 'q10', 'type' => 'grammar-mcq',
                    'text' => "Bien qu'il ___ beaucoup travaillé, il n'a pas obtenu son diplôme.",
                    'options' => [
                        'a',
                        'avait',
                        'aura',
                        'ait',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « Bien que » introduit une concession et exige le subjonctif ; au passé, c'est le subjonctif passé : bien qu'il ait travaillé. « avait » est tentant parce que le travail précède l'échec, mais l'indicatif est impossible après « bien que » — il irait après « même s'il ».",
                ],
                [
                    'id' => 'q11', 'type' => 'grammar-mcq',
                    'text' => "Après trois échecs, Karim a jeté l'éponge. Cela signifie qu'il :",
                    'options' => [
                        "a fait le ménage pour se calmer.",
                        "a abandonné.",
                        "s'est mis en colère.",
                        "a enfin réussi.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Jeter l'éponge » vient de la boxe : l'entraîneur jette l'éponge sur le ring pour arrêter le combat. L'expression veut donc dire renoncer, abandonner. Le sens littéral (le ménage) est le piège, et la colère n'est pas dans l'image.",
                ],
                [
                    'id' => 'q12', 'type' => 'grammar-mcq',
                    'text' => "C'est le projet ___ je vous ai parlé lors de notre dernière réunion.",
                    'options' => [
                        'que',
                        'lequel',
                        'dont',
                        'auquel',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. On parle DE quelque chose : le relatif qui reprend un complément introduit par « de » est « dont ». « que » conviendrait sans préposition (le projet que je vous ai présenté) ; « auquel » suppose « à » (le projet auquel je pense), et « lequel » seul ne s'emploie pas ici.",
                ],
                // ── C1 ──
                [
                    'id' => 'q13', 'type' => 'grammar-mcq',
                    'text' => "Il a quitté la salle sans que personne ne s'en ___.",
                    'options' => [
                        'aperçoit',
                        'apercevra',
                        'aperçoive',
                        'apercevait',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Sans que » est toujours suivi du subjonctif : sans que personne ne s'en aperçoive. Le « ne » est ici explétif — il n'a pas de valeur négative et ne change pas le mode. Les trois autres formes sont à l'indicatif, impossible après cette conjonction.",
                ],
                [
                    'id' => 'q14', 'type' => 'grammar-mcq',
                    'text' => "Laquelle de ces phrases contient une erreur de vocabulaire ?",
                    'options' => [
                        "Après un long séjour à l'hôpital, il a recouvert la santé.",
                        "Ce traitement a eu des effets bénéfiques.",
                        "Le témoin a fait sa déposition au commissariat.",
                        "Interrogée sur ses projets, elle a éludé la question.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. On « recouvre » la santé (on la retrouve) avec le verbe recouvrer : il a recouvré la santé. « recouvert » est le participe de « recouvrir » (couvrir entièrement) — les deux verbes se ressemblent, c'est toute la difficulté. Les trois autres emplois sont justes : « éluder » signifie esquiver.",
                ],
                [
                    'id' => 'q15', 'type' => 'grammar-mcq',
                    'text' => "___ soit la solution retenue, nous l'appliquerons sans délai.",
                    'options' => [
                        'Quelque',
                        'Quel que',
                        'Quelle',
                        'Quelle que',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. Devant le verbe « être » au subjonctif, on écrit en deux mots « quel que », accordé avec le sujet : « la solution » est féminin singulier, donc « quelle que soit la solution ». « Quel que » oublie l'accord ; « quelque » en un mot se place devant un nom (quelque difficulté) et ne se construit pas avec « soit ».",
                ],
                // ── C2 ──
                [
                    'id' => 'q16', 'type' => 'grammar-mcq',
                    'text' => "Laquelle de ces phrases relève d'un registre soutenu ?",
                    'options' => [
                        "Faut qu'on cause, toi et moi.",
                        "Il conviendrait que nous nous entretenions.",
                        "On doit parler, là, sérieux.",
                        "Faudrait qu'on discute un peu.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Il conviendrait que » + subjonctif et le verbe « s'entretenir » marquent un registre soutenu. Les trois autres sont familiers : suppression du « il » dans « faut » et « faudrait », « causer » au sens de parler, « sérieux » employé comme adverbe.",
                ],
                [
                    'id' => 'q17', 'type' => 'grammar-mcq',
                    'text' => "Les efforts qu'il a ___ pendant toute l'année ont fini par payer.",
                    'options' => [
                        'fait',
                        'faite',
                        'faites',
                        'faits',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. Avec « avoir », le participe s'accorde avec le complément direct quand celui-ci est placé AVANT. Ici, « qu' » reprend « les efforts » (masculin pluriel), placé avant « a fait » : il faut « faits ». « fait » sans accord serait juste si le complément suivait : il a fait des efforts.",
                ],
                [
                    'id' => 'q18', 'type' => 'grammar-mcq',
                    'text' => "« Le directeur n'est pas sans savoir que le projet a pris du retard. » Cette phrase signifie que le directeur :",
                    'options' => [
                        "ignore que le projet a pris du retard.",
                        "doute que le projet ait pris du retard.",
                        "sait que le projet a pris du retard.",
                        "refuse d'apprendre que le projet a pris du retard.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Ne pas être sans savoir » est une double négation : « ne pas » annule « sans ». L'expression veut donc dire « savoir très bien ». Le contresens — comprendre « ignorer » — est si fréquent, même chez les francophones, que c'est l'intrus le plus tentant.",
                ],
            ],
        ],
    ],
];
