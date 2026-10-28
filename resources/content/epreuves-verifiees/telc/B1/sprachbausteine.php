<?php

/**
 * telc Deutsch B1 — module Sprachbausteine, les deux parties.
 *
 * Ecrit a la main d'apres le format des Uebungstests officiels :
 *  - Teil 1 : un courriel suivi, dix trous, trois propositions par trou,
 *    GRAMMAIRE (prepositions, declinaisons, conjonctions, temps, relatifs) ;
 *  - Teil 2 : un article suivi, dix trous, un seul mot a prendre dans une
 *    liste de quinze (cinq intrus), LEXIQUE.
 *
 * Les textes sont originaux. Ce sont les FORMATS, les longueurs et les nombres
 * d'items qui reprennent l'epreuve, pas les contenus de l'organisme.
 *
 * Trois choix a garder si ce fichier est recopie :
 *  - c'est le module que les candidats ratent le plus : chaque explication dit
 *    POURQUOI la bonne forme gagne ET pourquoi l'intrus le plus tentant perd ;
 *  - le texte entier reste dans `content.passage`, et chaque question ne porte
 *    que son fragment : l'apprenant lit d'abord, puis tranche trou par trou ;
 *  - Teil 2 est de la famille multi-champs : UNE question par trou, chacune
 *    avec son `word_list` complet et `correct_answers` indexe depuis 0, comme
 *    le composant le renvoie. Dix questions, parce que la config annonce dix
 *    items — un seul bloc de dix reponses ferait mentir le compte.
 */

return [
    'exam' => 'telc',
    'level' => 'B1',
    'section' => 'sprachbausteine',
    'parts' => [

        // ━━━━━━━━━━ Teil 1 : grammaire, dix trous a trois choix ━━━━━━━━━━
        [
            'part' => 1,
            'name' => 'Sprachbausteine, Teil 1',
            'type' => 'mcq-cloze',
            'title' => 'telc B1 · Sprachbausteine, Teil 1 — Un courriel à la Volkshochschule',
            'description' => "Dix trous de grammaire dans un courriel suivi : pour chacun, la bonne forme parmi trois. Format des Übungstests officiels.",
            'content' => [
                'instructions' => "Lis d'abord le courriel en entier, puis choisis pour chaque trou la proposition qui convient : A, B ou C. Un seul mot ou groupe de mots est correct.",
                'passage' => "Sehr geehrte Frau Baumgartner,\n\n"
                    ."vor zwei Wochen habe ich mich ___(1)___ den Abendkurs \"Fotografie für Anfänger\" angemeldet. "
                    ."Leider habe ich bis heute keine Bestätigung bekommen, ___(2)___ ich die Anmeldegebühr schon überwiesen habe.\n\n"
                    ."Am Telefon hat mir eine Kollegin gesagt, dass der Kurs ___(3)___ 15. Oktober beginnt. "
                    ."Auf Ihrer Internetseite steht aber ein anderes Datum. Können Sie mir bitte sagen, ___(4)___ Termin richtig ist?\n\n"
                    ."Außerdem brauche ich eine Auskunft zum Raum. Das Gebäude, ___(5)___ im Internet genannt wird, liegt sehr weit von der Haltestelle. "
                    ."Ich bin vor drei Jahren schon einmal dort ___(6)___ und habe den Eingang nicht gefunden.\n\n"
                    ."Wenn der Kurs wirklich am Donnerstag stattfindet, ___(7)___ leider nicht kommen, denn ich arbeite bis 19 Uhr. "
                    ."In diesem Fall ___(8)___ ich mich gern für den Samstagskurs anmelden, wenn dort noch ein Platz frei wäre.\n\n"
                    ."Bitte antworten Sie mir ___(9)___ Freitag, damit ich meine Woche planen kann. "
                    ."___(10)___ eine schnelle Antwort wäre ich Ihnen sehr dankbar.\n\n"
                    ."Mit freundlichen Grüßen\n"
                    ."Katrin Lohmeyer",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq-cloze',
                    'text' => "vor zwei Wochen habe ich mich ___ den Abendkurs \"Fotografie für Anfänger\" angemeldet.",
                    'options' => ['für', 'auf', 'nach'],
                    'correct_answer' => 'A',
                    'explanation' => "A. Le verbe est « sich anmelden für + accusatif » : on s'inscrit POUR un cours. « auf » appartient à « sich freuen auf », « warten auf » ; « nach » à « fragen nach ». La préposition se retient avec le verbe, jamais seule.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq-cloze',
                    'text' => "Leider habe ich bis heute keine Bestätigung bekommen, ___ ich die Anmeldegebühr schon überwiesen habe.",
                    'options' => ['weil', 'obwohl', 'damit'],
                    'correct_answer' => 'B',
                    'explanation' => "B. Les deux faits se contredisent : pas de confirmation, et pourtant les frais sont déjà payés. « obwohl » (bien que) marque cette opposition. « weil » donnerait une cause absurde — payer n'empêche pas de recevoir une confirmation — et « damit » annoncerait un but.",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq-cloze',
                    'text' => "Am Telefon hat mir eine Kollegin gesagt, dass der Kurs ___ 15. Oktober beginnt.",
                    'options' => ['im', 'um', 'am'],
                    'correct_answer' => 'C',
                    'explanation' => "C. Devant une DATE, l'allemand dit « am 15. Oktober ». « im » sert pour le mois ou l'année (im Oktober), « um » pour l'heure (um 15 Uhr). C'est le piège classique : le chiffre 15 fait penser à une heure.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq-cloze',
                    'text' => "Können Sie mir bitte sagen, ___ Termin richtig ist?",
                    'options' => ['welchen', 'welcher', 'welchem'],
                    'correct_answer' => 'B',
                    'explanation' => "B. Dans la question indirecte, « Termin » est le SUJET de « ist » : il faut le nominatif, donc « welcher Termin ». « welchen » serait l'accusatif (welchen Termin nehmen Sie?) et « welchem » le datif.",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq-cloze',
                    'text' => "Das Gebäude, ___ im Internet genannt wird, liegt sehr weit von der Haltestelle.",
                    'options' => ['dem', 'was', 'das'],
                    'correct_answer' => 'C',
                    'explanation' => "C. Le relatif reprend « das Gebäude » (neutre) et il est sujet de « genannt wird » : nominatif neutre, donc « das ». « dem » serait le datif, et « was » ne reprend jamais un nom précis — seulement « alles », « etwas », « nichts ».",
                ],
                [
                    'id' => 'q6', 'type' => 'mcq-cloze',
                    'text' => "Ich bin vor drei Jahren schon einmal dort ___ und habe den Eingang nicht gefunden.",
                    'options' => ['war', 'gewesen', 'geworden'],
                    'correct_answer' => 'B',
                    'explanation' => "B. L'auxiliaire « bin » est déjà là : il attend un participe, et celui de « sein » est « gewesen ». « war » est un prétérit, il ne se combine pas avec « bin ». « geworden » est le participe de « werden » (devenir), autre sens.",
                ],
                [
                    'id' => 'q7', 'type' => 'mcq-cloze',
                    'text' => "Wenn der Kurs wirklich am Donnerstag stattfindet, ___ leider nicht kommen, denn ich arbeite bis 19 Uhr.",
                    'options' => ['ich kann', 'kann ich', 'kann'],
                    'correct_answer' => 'B',
                    'explanation' => "B. La subordonnée en « Wenn … » occupe la première place ; la principale commence donc par le VERBE conjugué, puis le sujet : « kann ich ». « ich kann » laisserait le verbe en troisième position, et « kann » seul n'a pas de sujet.",
                ],
                [
                    'id' => 'q8', 'type' => 'mcq-cloze',
                    'text' => "In diesem Fall ___ ich mich gern für den Samstagskurs anmelden, wenn dort noch ein Platz frei wäre.",
                    'options' => ['würde', 'wurde', 'werde'],
                    'correct_answer' => 'A',
                    'explanation' => "A. La subordonnée « wenn dort noch ein Platz frei wäre » est au Konjunktiv II : une principale à l'irréel doit lui répondre, donc « würde … anmelden ». « werde » est le piège le plus tentant, car un futur serait de l'allemand courant ailleurs — mais il affirme un fait réel et jure avec « wäre ». « wurde » est un prétérit (sans tréma), impossible ici.",
                ],
                [
                    'id' => 'q9', 'type' => 'mcq-cloze',
                    'text' => "Bitte antworten Sie mir ___ Freitag, damit ich meine Woche planen kann.",
                    'options' => ['bis', 'seit', 'ab'],
                    'correct_answer' => 'A',
                    'explanation' => "A. Elle veut la réponse AVANT la fin de vendredi : « bis Freitag » donne la limite. « seit » regarde vers le passé (depuis vendredi) et « ab » vers le début d'une période (à partir de vendredi), ce qui autoriserait une réponse la semaine suivante.",
                ],
                [
                    'id' => 'q10', 'type' => 'mcq-cloze',
                    'text' => "___ eine schnelle Antwort wäre ich Ihnen sehr dankbar.",
                    'options' => ['Über', 'Für', 'Auf'],
                    'correct_answer' => 'B',
                    'explanation' => "B. L'adjectif commande : « dankbar für etwas ». « Über » irait avec « sich freuen über », et « Auf » avec « warten auf ». La formule toute faite « Über eine schnelle Antwort würde ich mich freuen » existe, mais pas avec « dankbar ».",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 2 : lexique, dix mots a prendre dans une liste de quinze ━━━━━━━━━━
        [
            'part' => 2,
            'name' => 'Sprachbausteine, Teil 2',
            'type' => 'summary-completion',
            'title' => 'telc B1 · Sprachbausteine, Teil 2 — Das Reparatur-Café',
            'description' => "Dix trous de vocabulaire dans un article : chaque mot se prend dans une liste de quinze, dont cinq intrus. Format des Übungstests officiels.",
            'content' => [
                'instructions' => "Lis l'article, puis place dans chaque trou un mot de la liste. Chaque mot ne sert qu'une fois, et cinq mots de la liste ne servent à rien.",
                'passage' => "ARTICLE — Das Reparatur-Café im Stadtteil Ostfeld\n\n"
                    ."Seit einem Jahr gibt es im Stadtteil Ostfeld ein Reparatur-Café. "
                    ."Jeden zweiten Samstag ___(1)___ sich dort etwa dreißig Menschen mit kaputten Geräten. "
                    ."Wer einen Toaster oder eine Lampe mitbringt, bekommt Hilfe von Freiwilligen, die ihre ___(2)___ kostenlos weitergeben.\n\n"
                    ."Das Ziel ist einfach: weniger Müll. Viele Geräte werden ___(3)___, obwohl nur ein Kabel gebrochen ist. "
                    ."Die Stadt ___(4)___ das Projekt mit einem kleinen Zuschuss für Werkzeug und Strom.\n\n"
                    ."Eine Anmeldung ist nicht ___(5)___; man kommt einfach vorbei und wartet, bis ein Tisch frei wird. "
                    ."Höchstens zwei Stunden pro Gerät sind ___(6)___, danach ist der nächste Gast dran. "
                    ."Nicht jede Reparatur ___(7)___: manchmal ist das Ersatzteil teurer als ein neues Gerät.\n\n"
                    ."Trotzdem ___(8)___ die Besucher, dass sie an diesen Samstagen etwas gelernt haben. "
                    ."Besonders ___(9)___ sind die älteren Gäste, die früher selbst Elektriker waren und heute den Jüngeren alles erklären. "
                    ."Im nächsten Jahr will das Café zusätzlich einen Kurs für Fahrradreparaturen ___(10)___.\n\n"
                    ."LISTE DER WÖRTER\n\n"
                    ."a) anbieten\n"
                    ."b) bestellen\n"
                    ."c) betonen\n"
                    ."d) gefragt\n"
                    ."e) gelingt\n"
                    ."f) gewöhnlich\n"
                    ."g) Kenntnisse\n"
                    ."h) Meinung\n"
                    ."i) nötig\n"
                    ."j) treffen\n"
                    ."k) unterstützt\n"
                    ."l) verboten\n"
                    ."m) verkauft\n"
                    ."n) vorgesehen\n"
                    ."o) weggeworfen",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'summary-completion',
                    'text' => "Jeden zweiten Samstag ___ sich dort etwa dreißig Menschen mit kaputten Geräten.",
                    'word_list' => ['anbieten', 'bestellen', 'betonen', 'gefragt', 'gelingt', 'gewöhnlich', 'Kenntnisse', 'Meinung', 'nötig', 'treffen', 'unterstützt', 'verboten', 'verkauft', 'vorgesehen', 'weggeworfen'],
                    'correct_answers' => ['0' => 'treffen'],
                    'explanation' => "j) treffen. Le pronom « sich » impose un verbe réfléchi : « sich treffen » veut dire se retrouver, et le sujet pluriel « dreißig Menschen » donne bien la forme « treffen ». L'intrus tentant est « bestellen » (commander) : « sich bestellen » réclame un objet (sich etwas bestellen = se commander quelque chose, « ich bestelle mir einen Kaffee ») et ne veut jamais dire « se retrouver ».",
                ],
                [
                    'id' => 'q2', 'type' => 'summary-completion',
                    'text' => "Wer einen Toaster oder eine Lampe mitbringt, bekommt Hilfe von Freiwilligen, die ihre ___ kostenlos weitergeben.",
                    'word_list' => ['anbieten', 'bestellen', 'betonen', 'gefragt', 'gelingt', 'gewöhnlich', 'Kenntnisse', 'Meinung', 'nötig', 'treffen', 'unterstützt', 'verboten', 'verkauft', 'vorgesehen', 'weggeworfen'],
                    'correct_answers' => ['0' => 'Kenntnisse'],
                    'explanation' => "g) Kenntnisse. Après « ihre » il faut un NOM, et ce que des bénévoles transmettent en réparant, ce sont leurs connaissances techniques. « Meinung » (l'opinion) est le piège : « ihre Meinung weitergeben » serait grammaticalement possible, mais ce que des bénévoles transmettent en réparant un grille-pain, ce sont des connaissances techniques, pas une opinion.",
                ],
                [
                    'id' => 'q3', 'type' => 'summary-completion',
                    'text' => "Viele Geräte werden ___, obwohl nur ein Kabel gebrochen ist.",
                    'word_list' => ['anbieten', 'bestellen', 'betonen', 'gefragt', 'gelingt', 'gewöhnlich', 'Kenntnisse', 'Meinung', 'nötig', 'treffen', 'unterstützt', 'verboten', 'verkauft', 'vorgesehen', 'weggeworfen'],
                    'correct_answers' => ['0' => 'weggeworfen'],
                    'explanation' => "o) weggeworfen. « werden » + participe forme un passif, et le thème annoncé juste avant est « weniger Müll » : les appareils sont JETÉS. « verkauft » (vendus) est grammaticalement possible mais contredit le texte : un appareil au câble cassé finit à la poubelle, pas à la vente.",
                ],
                [
                    'id' => 'q4', 'type' => 'summary-completion',
                    'text' => "Die Stadt ___ das Projekt mit einem kleinen Zuschuss für Werkzeug und Strom.",
                    'word_list' => ['anbieten', 'bestellen', 'betonen', 'gefragt', 'gelingt', 'gewöhnlich', 'Kenntnisse', 'Meinung', 'nötig', 'treffen', 'unterstützt', 'verboten', 'verkauft', 'vorgesehen', 'weggeworfen'],
                    'correct_answers' => ['0' => 'unterstützt'],
                    'explanation' => "k) unterstützt. Le sujet singulier « die Stadt » demande une forme en -t, et « einen Zuschuss » (une subvention) dit une aide : « unterstützen » soutenir. « verboten » serait l'inverse exact : on n'interdit pas un projet en lui versant de l'argent.",
                ],
                [
                    'id' => 'q5', 'type' => 'summary-completion',
                    'text' => "Eine Anmeldung ist nicht ___; man kommt einfach vorbei und wartet, bis ein Tisch frei wird.",
                    'word_list' => ['anbieten', 'bestellen', 'betonen', 'gefragt', 'gelingt', 'gewöhnlich', 'Kenntnisse', 'Meinung', 'nötig', 'treffen', 'unterstützt', 'verboten', 'verkauft', 'vorgesehen', 'weggeworfen'],
                    'correct_answers' => ['0' => 'nötig'],
                    'explanation' => "i) nötig. « ist nicht nötig » est la formule figée : ce n'est pas nécessaire — et la suite le confirme, « man kommt einfach vorbei ». « verboten » dirait que l'inscription est interdite, ce qui n'a pas de sens, et « gewöhnlich » (habituel) laisserait entendre qu'on s'inscrit parfois.",
                ],
                [
                    'id' => 'q6', 'type' => 'summary-completion',
                    'text' => "Höchstens zwei Stunden pro Gerät sind ___, danach ist der nächste Gast dran.",
                    'word_list' => ['anbieten', 'bestellen', 'betonen', 'gefragt', 'gelingt', 'gewöhnlich', 'Kenntnisse', 'Meinung', 'nötig', 'treffen', 'unterstützt', 'verboten', 'verkauft', 'vorgesehen', 'weggeworfen'],
                    'correct_answers' => ['0' => 'vorgesehen'],
                    'explanation' => "n) vorgesehen. « vorgesehen sein » signifie être prévu, et « höchstens zwei Stunden sind vorgesehen » dit exactement cela : deux heures au maximum sont planifiées, puis on laisse la place. « nötig » est le piège, mais « höchstens … nötig » n'a pas de sens : on ne fixe pas un plafond à ce qui est indispensable.",
                ],
                [
                    'id' => 'q7', 'type' => 'summary-completion',
                    'text' => "Nicht jede Reparatur ___: manchmal ist das Ersatzteil teurer als ein neues Gerät.",
                    'word_list' => ['anbieten', 'bestellen', 'betonen', 'gefragt', 'gelingt', 'gewöhnlich', 'Kenntnisse', 'Meinung', 'nötig', 'treffen', 'unterstützt', 'verboten', 'verkauft', 'vorgesehen', 'weggeworfen'],
                    'correct_answers' => ['0' => 'gelingt'],
                    'explanation' => "e) gelingt. Le sujet « jede Reparatur » est singulier, et « gelingen » veut dire réussir : toutes les réparations n'aboutissent pas, ce que la suite explique. « bestellen » ne se construit pas ainsi — il faudrait un complément, et c'est la pièce qu'on commande, pas la réparation.",
                ],
                [
                    'id' => 'q8', 'type' => 'summary-completion',
                    'text' => "Trotzdem ___ die Besucher, dass sie an diesen Samstagen etwas gelernt haben.",
                    'word_list' => ['anbieten', 'bestellen', 'betonen', 'gefragt', 'gelingt', 'gewöhnlich', 'Kenntnisse', 'Meinung', 'nötig', 'treffen', 'unterstützt', 'verboten', 'verkauft', 'vorgesehen', 'weggeworfen'],
                    'correct_answers' => ['0' => 'betonen'],
                    'explanation' => "c) betonen. Il faut un verbe de parole qui ouvre une subordonnée en « dass », au pluriel pour « die Besucher » : « betonen », souligner, insister. « Meinung » est le piège, parce qu'on pense à « ihre Meinung sagen » — mais c'est un nom, et la place est celle du verbe.",
                ],
                [
                    'id' => 'q9', 'type' => 'summary-completion',
                    'text' => "Besonders ___ sind die älteren Gäste, die früher selbst Elektriker waren und heute den Jüngeren alles erklären.",
                    'word_list' => ['anbieten', 'bestellen', 'betonen', 'gefragt', 'gelingt', 'gewöhnlich', 'Kenntnisse', 'Meinung', 'nötig', 'treffen', 'unterstützt', 'verboten', 'verkauft', 'vorgesehen', 'weggeworfen'],
                    'correct_answers' => ['0' => 'gefragt'],
                    'explanation' => "d) gefragt. « gefragt sein » signifie être très demandé, recherché : ces anciens électriciens expliquent tout aux plus jeunes, donc on se les dispute. « gewöhnlich » (ordinaire) va avec « besonders » sur le plan grammatical mais dit le contraire du texte, qui les met en avant.",
                ],
                [
                    'id' => 'q10', 'type' => 'summary-completion',
                    'text' => "Im nächsten Jahr will das Café zusätzlich einen Kurs für Fahrradreparaturen ___.",
                    'word_list' => ['anbieten', 'bestellen', 'betonen', 'gefragt', 'gelingt', 'gewöhnlich', 'Kenntnisse', 'Meinung', 'nötig', 'treffen', 'unterstützt', 'verboten', 'verkauft', 'vorgesehen', 'weggeworfen'],
                    'correct_answers' => ['0' => 'anbieten'],
                    'explanation' => "a) anbieten. Le modal « will » réclame un INFINITIF en fin de phrase, et « einen Kurs anbieten » est la collocation usuelle : proposer un cours. « bestellen » reste l'intrus le plus tentant parce qu'il est aussi à l'infinitif, mais on ne commande pas un cours qu'on organise soi-même.",
                ],
            ],
        ],
    ],
];
