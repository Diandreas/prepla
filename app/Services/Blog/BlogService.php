<?php

namespace App\Services\Blog;

/**
 * Static, code-defined blog content — no DB/admin needed for a first batch of
 * SEO articles. Add a new entry to POSTS() to publish a new post; nothing else
 * to wire (index, sitemap and RSS-less discovery all read from here).
 */
class BlogService
{
    /** @return array<int, array<string, string>> */
    public static function posts(): array
    {
        return [
            [
                'slug' => 'tcf-canada-cameroun-guide',
                'title' => 'TCF Canada depuis le Cameroun : le guide complet pour réussir du premier coup',
                'description' => "Comment se préparer au TCF Canada quand on est au Cameroun : sections de l'examen, niveau à viser, durée de préparation réaliste et erreurs à éviter.",
                'category' => 'Canada',
                'theme' => 'canada',
                'published_at' => '2026-07-11',
                'read_minutes' => 8,
                'body' => <<<'HTML'
<p>Le TCF Canada (Test de Connaissance du Français pour le Canada) est devenu, ces
dernières années, l'un des examens les plus recherchés au Cameroun — porte d'entrée
vers Entrée express, le Programme des travailleurs qualifiés du Québec (PSTQ) ou un
permis d'études. Voici comment s'y préparer sérieusement, sans perdre de mois sur
des méthodes qui ne collent pas au format réel de l'examen.</p>

<h2>Ce que le TCF Canada évalue réellement</h2>
<p>Le TCF Canada comporte 4 épreuves obligatoires : compréhension orale, compréhension
écrite, expression orale et expression écrite. Chaque épreuve est notée séparément et
convertie en <strong>niveau du CECR (A1 à C2)</strong>, puis en <strong>Niveaux de
compétence linguistique canadiens (NCLC)</strong> — c'est ce NCLC, pas la note brute,
qui compte pour ton dossier d'immigration.</p>

<p>Concrètement : viser un « bon score global » ne veut rien dire. Il faut viser un
<strong>NCLC précis par épreuve</strong>, selon le programme visé (Entrée express,
PSTQ, etc.). Les seuils exacts sont revus régulièrement par IRCC et le gouvernement du
Québec — vérifie toujours le seuil à jour sur le site officiel d'IRCC ou d'Immigration
Québec avant de fixer ton objectif, plutôt que de te fier à un chiffre qui circule sur
un groupe WhatsApp.</p>

<h2>Combien de temps pour se préparer, réaliste ment ?</h2>
<table>
<tr><th>Ton niveau de départ</th><th>Temps de préparation raisonnable</th></tr>
<tr><td>Tu te débrouilles déjà bien à l'oral et à l'écrit (B1-B2)</td><td>4 à 8 semaines, focalisées sur le format de l'examen</td></tr>
<tr><td>Niveau correct mais rouillé, fautes fréquentes</td><td>2 à 3 mois de pratique régulière</td></tr>
<tr><td>Tu pars de plus loin (A2 ou en dessous)</td><td>4 à 6 mois avant de viser le NCLC demandé</td></tr>
</table>
<p>Le piège classique : réviser la grammaire pendant des semaines sans jamais s'entraîner
au <em>format</em> exact de l'épreuve (temps limité, type de questions, façon de
répondre). Le TCF sanctionne autant la maîtrise du format que le niveau de langue.</p>

<h2>Les erreurs qui coûtent des points</h2>
<ul>
<li><strong>Expression écrite :</strong> vouloir « faire riche » avec du vocabulaire mal
maîtrisé plutôt que d'écrire juste et structuré. Un texte simple mais sans faute vaut
mieux qu'un texte ambitieux plein d'erreurs.</li>
<li><strong>Expression orale :</strong> réciter un discours appris par cœur — l'examinateur
s'en rend compte immédiatement et pose des questions de relance pour tester la
spontanéité réelle.</li>
<li><strong>Compréhension orale :</strong> paniquer si on ne comprend pas un mot et perdre
le fil de toute la question suivante. L'entraînement au format (pas juste à la langue)
règle ce problème.</li>
<li><strong>Gestion du temps :</strong> passer trop de temps sur une question difficile en
compréhension écrite et bâcler les dix suivantes.</li>
</ul>

<h2>Comment structurer ta préparation</h2>
<ol>
<li><strong>Fais un vrai test de niveau</strong> avant de commencer — savoir si tu pars de
A2, B1 ou B2 change complètement le plan de travail.</li>
<li><strong>Entraîne-toi sur le format exact</strong> : questions à choix multiples
chronométrées pour l'oral/écrit compréhension, tâches d'expression écrite type
« exprimer une opinion argumentée », simulation d'entretien pour l'oral.</li>
<li><strong>Fais-toi corriger</strong>, surtout à l'écrit et à l'oral — c'est là que les
points se perdent silencieusement, sans qu'on s'en rende compte seul.</li>
<li><strong>Répète en conditions réelles</strong> la semaine avant l'examen : mêmes durées,
mêmes types d'épreuves, pas de dictionnaire.</li>
</ol>

<p>PrePla propose un test de placement gratuit, un parcours d'exercices adapté au format
TCF/TEF et une correction par IA de tes productions écrites et orales — de quoi structurer
ta préparation sans tourner en rond. <a href="/register">Essaie gratuitement pendant 7 jours</a>.</p>
HTML,
            ],
            [
                'slug' => 'tef-vs-tcf-canada',
                'title' => 'TEF ou TCF pour immigrer au Canada : lequel choisir ?',
                'description' => "TEF Canada ou TCF Canada : différences, format, et comment décider lequel préparer pour ton dossier d'immigration ou d'études.",
                'category' => 'Canada',
                'theme' => 'canada',
                'published_at' => '2026-07-11',
                'read_minutes' => 6,
                'body' => <<<'HTML'
<p>Deux tests de français sont reconnus pour l'immigration canadienne : le <strong>TEF
Canada</strong> (Chambre de commerce et d'industrie de Paris Île-de-France) et le
<strong>TCF Canada</strong> (France Éducation international). Les deux sont valables
pour Entrée express et la plupart des programmes provinciaux — la question n'est donc
pas « lequel est reconnu » mais « lequel te convient le mieux ».</p>

<h2>Les vraies différences</h2>
<table>
<tr><th></th><th>TEF Canada</th><th>TCF Canada</th></tr>
<tr><td>Expression orale</td><td>Face à face ou enregistré selon le centre</td><td>Généralement enregistré</td></tr>
<tr><td>Compréhension écrite</td><td>Textes variés, questions à choix multiples</td><td>Questions à choix multiples, niveau progressif</td></tr>
<tr><td>Disponibilité au Cameroun</td><td>Selon les centres agréés</td><td>Selon les centres agréés</td></tr>
<tr><td>Validité des résultats</td><td>2 ans</td><td>2 ans</td></tr>
</table>

<p>Dans la pratique, l'écart de difficulté entre les deux est faible pour un même
niveau réel de français. Le vrai critère de choix, ce sont des questions très
concrètes : quel centre est disponible près de chez toi (Yaoundé, Douala…), à quelle
date, et à quel prix ? C'est souvent ça qui tranche, pas une différence de contenu.</p>

<h2>Comment décider</h2>
<ul>
<li><strong>Regarde les créneaux disponibles</strong> dans les centres agréés près de
chez toi — un test que tu peux passer dans 3 semaines vaut souvent mieux qu'un test
« légèrement plus facile » disponible dans 4 mois.</li>
<li><strong>Renseigne-toi sur le format oral</strong> : si tu es plus à l'aise pour
t'enregistrer seul que face à un examinateur (ou l'inverse), ça peut faire pencher la
balance.</li>
<li><strong>Vérifie que ton programme d'immigration accepte bien les deux</strong> —
c'est le cas pour la majorité, mais certains programmes provinciaux ou volets
spécifiques peuvent avoir une préférence. Un détail à confirmer sur le site officiel
avant de t'inscrire et de payer.</li>
</ul>

<h2>Se préparer, quel que soit le test choisi</h2>
<p>La bonne nouvelle : la préparation se recoupe à 90 %. Travailler la compréhension
orale/écrite en conditions chronométrées, structurer ses productions écrites autour
d'une opinion claire et argumentée, et s'entraîner à parler sans réciter par cœur —
ce socle sert quel que soit le nom exact de l'examen sur ton relevé de notes.</p>

<p>PrePla couvre les deux formats (TEF et TCF) avec des exercices adaptés au niveau
CECR visé et une correction IA immédiate. <a href="/register">Teste gratuitement pendant
7 jours</a> pour voir où tu en es.</p>
HTML,
            ],
            [
                'slug' => 'ielts-candidats-africains',
                'title' => "IELTS pour le Canada, le Royaume-Uni ou l'Australie : le guide pour candidats africains",
                'description' => "Comment préparer l'IELTS efficacement en partant d'Afrique francophone ou anglophone : bandes visées, pièges fréquents, plan de travail.",
                'category' => 'IELTS',
                'theme' => 'ielts',
                'published_at' => '2026-07-11',
                'read_minutes' => 7,
                'body' => <<<'HTML'
<p>L'IELTS reste le test d'anglais le plus demandé pour étudier ou immigrer vers le
Canada, le Royaume-Uni, l'Australie ou la Nouvelle-Zélande. Pour un candidat africain —
qu'il soit déjà très à l'aise en anglais ou qu'il progresse depuis le français —
quelques pièges reviennent systématiquement.</p>

<h2>Comprendre le système de bandes</h2>
<p>L'IELTS note chacune des 4 épreuves (Listening, Reading, Writing, Speaking) sur une
échelle de 1 à 9, puis calcule une moyenne. Ce qui compte pour un dossier
d'immigration ou d'admission, ce n'est presque jamais la moyenne seule : la plupart
des programmes exigent un <strong>score minimum sur CHAQUE épreuve</strong>. Un très
bon score en Listening ne compense pas un score trop faible en Writing.</p>
<p>Le seuil exact dépend entièrement du programme (université, employeur, volet
d'immigration) — vérifie-le sur la page officielle de ta destination plutôt que de te
fier à une moyenne générale entendue ailleurs.</p>

<h2>Les pièges les plus fréquents</h2>
<ul>
<li><strong>Writing Task 2 :</strong> répondre hors-sujet en récitant un plan « prêt à
l'emploi » appris par cœur — les correcteurs sont formés à repérer les réponses
génériques qui ne traitent pas vraiment la question posée.</li>
<li><strong>Speaking :</strong> répondre par des phrases trop courtes en Part 1, ou au
contraire monopoliser la parole en Part 2 sans respecter le temps imparti (2 minutes,
pas plus).</li>
<li><strong>Listening :</strong> se laisser déstabiliser par les accents (britannique,
australien) quand on s'est entraîné uniquement avec des vidéos en accent américain.</li>
<li><strong>Reading :</strong> mal gérer le temps — 60 minutes pour 3 textes denses,
avec un niveau de difficulté qui augmente. Beaucoup de candidats finissent le premier
texte trop lentement et bâclent le troisième.</li>
</ul>

<h2>Plan de travail sur 8 semaines (niveau B1-B2 de départ)</h2>
<table>
<tr><th>Semaines</th><th>Focus</th></tr>
<tr><td>1-2</td><td>Diagnostic complet des 4 épreuves + vocabulaire académique de base</td></tr>
<tr><td>3-5</td><td>Un focus par semaine (Listening, Reading, Writing) en conditions chronométrées, correction systématique</td></tr>
<tr><td>6-7</td><td>Speaking intensif : enregistrements, feedback, banque de réponses spontanées (pas apprises par cœur)</td></tr>
<tr><td>8</td><td>Simulations complètes en conditions réelles, dans le créneau horaire du vrai examen</td></tr>
</table>

<p>Si ton niveau de départ est plus bas (A2), compte plutôt 4 à 6 mois — le vrai
déterminant n'est pas le nombre de semaines mais le nombre d'heures de pratique
réelle en conditions d'examen, pas de révision passive de grammaire.</p>

<p>PrePla génère un parcours d'exercices IELTS adapté à ton niveau réel (mesuré par un
test de placement), avec correction immédiate de l'écrit et de l'oral.
<a href="/register">Commence gratuitement</a>.</p>
HTML,
            ],
            [
                'slug' => 'delf-dalf-sans-partir-en-france',
                'title' => 'DELF-DALF : réussir son diplôme de français sans quitter le Cameroun',
                'description' => "Comment préparer le DELF ou le DALF pour Campus France ou une admission universitaire, en restant au Cameroun ou en Afrique.",
                'category' => 'France',
                'theme' => 'france',
                'published_at' => '2026-07-11',
                'read_minutes' => 6,
                'body' => <<<'HTML'
<p>Le DELF (A1 à B2) et le DALF (C1-C2) sont souvent perçus comme réservés à ceux qui
étudient déjà en France — en réalité, la grande majorité des candidats les préparent
et les passent depuis leur pays d'origine, notamment pour un dossier Campus France ou
une admission universitaire directe.</p>

<h2>Quel niveau viser selon ton projet</h2>
<ul>
<li><strong>Licence en France (hors filières sélectives en langue) :</strong>
généralement B2 attendu, parfois B1 accepté selon l'établissement — à confirmer sur
la fiche de la formation visée.</li>
<li><strong>Master :</strong> B2 quasi systématiquement demandé, C1 parfois valorisé
pour certaines filières exigeantes (droit, lettres).</li>
<li><strong>Filières scientifiques/techniques :</strong> le niveau exigé est parfois
un peu plus bas qu'en lettres/sciences humaines, mais vérifie toujours au cas par
cas — ça varie beaucoup d'un établissement à l'autre.</li>
</ul>

<h2>Le format qui surprend le plus de candidats</h2>
<p>Contrairement à un examen de grammaire classique, le DELF/DALF évalue des
<strong>tâches communicatives concrètes</strong> : comprendre une annonce radio,
rédiger une lettre argumentée, défendre un point de vue à l'oral face à un jury. Une
excellente maîtrise théorique de la grammaire française ne suffit pas si tu n'as
jamais pratiqué ces formats précis.</p>

<h2>Où perdent des points les candidats déjà francophones</h2>
<p>Beaucoup de candidats camerounais francophones sous-estiment le DALF (C1-C2) en se
disant « je parle déjà français ». Le piège : le DALF exige une <strong>argumentation
structurée</strong> (thèse, antithèse, synthèse) et un vocabulaire précis sur des
sujets abstraits — une compétence académique spécifique, pas seulement la maîtrise de
la langue au quotidien.</p>

<h2>Plan de préparation</h2>
<ol>
<li><strong>Identifie ton niveau réel</strong> avec un test de placement, plutôt que
de deviner en fonction de ton ressenti.</li>
<li><strong>Entraîne-toi aux 4 épreuves séparément</strong> : compréhension de l'oral,
compréhension des écrits, production écrite, production orale — chacune a ses codes
propres.</li>
<li><strong>Travaille l'épreuve orale en conditions réelles</strong> : présentation
d'un document, débat avec un jury simulé, gestion du temps de préparation.</li>
<li><strong>Fais-toi corriger la production écrite</strong> régulièrement — c'est
l'épreuve où la progression est la plus lente sans retour extérieur.</li>
</ol>

<p>PrePla propose un parcours structuré par niveau CECR (A1 à C2), avec des exercices
calqués sur le format DELF/DALF et une correction IA de tes écrits.
<a href="/register">Essaie gratuitement 7 jours</a>.</p>
HTML,
            ],
            [
                'slug' => 'goethe-ausbildung-allemagne',
                'title' => "Goethe-Zertifikat et Ausbildung en Allemagne : préparer son allemand depuis le Cameroun",
                'description' => "Comment préparer le Goethe-Zertifikat (A1 à C1) pour un projet d'Ausbildung ou d'études en Allemagne depuis l'Afrique.",
                'category' => 'Allemagne',
                'theme' => 'germany',
                'published_at' => '2026-07-11',
                'read_minutes' => 6,
                'body' => <<<'HTML'
<p>L'Ausbildung (formation professionnelle en alternance) et les études en
Studienkolleg attirent un nombre croissant de candidats camerounais et africains vers
l'Allemagne. Dans les deux cas, le niveau d'allemand exigé au départ — généralement
autour de <strong>B1</strong>, parfois B2 selon le programme — est la première porte
à franchir, souvent avant même d'avoir un contrat ou une place confirmée.</p>

<h2>Pourquoi le niveau réellement exigé varie autant</h2>
<p>Contrairement à une idée reçue, il n'existe pas un seul « niveau standard » pour
partir en Allemagne : chaque employeur, chaque Studienkolleg et chaque Land peut avoir
ses propres exigences. Certains programmes d'Ausbildung acceptent un B1 avec
engagement à progresser sur place ; d'autres, notamment dans le secteur du soin
(Pflege), exigent un B2 solide dès le départ. Vérifie toujours l'exigence précise du
programme visé plutôt qu'un chiffre général.</p>

<h2>Ce qui rend l'allemand difficile à ce stade pour un francophone</h2>
<ul>
<li><strong>La déclinaison</strong> (nominatif/accusatif/datif/génitif) — le point qui
fait le plus perdre de points en production écrite et orale.</li>
<li><strong>L'ordre des mots</strong>, très différent du français, en particulier avec
les verbes à particule séparable et les subordonnées.</li>
<li><strong>Le vocabulaire du quotidien professionnel</strong> (spécifique à
l'Ausbildung visée) — souvent absent des manuels généralistes.</li>
</ul>

<h2>Plan de préparation réaliste</h2>
<table>
<tr><th>Niveau de départ</th><th>Temps pour atteindre B1 solide</th></tr>
<tr><td>Grand débutant (A0)</td><td>8 à 12 mois de pratique régulière</td></tr>
<tr><td>Notions de base (A1-A2)</td><td>4 à 6 mois</td></tr>
<tr><td>Déjà A2 solide / B1 rouillé</td><td>2 à 3 mois de remise à niveau ciblée</td></tr>
</table>

<h2>Comment structurer le travail</h2>
<ol>
<li><strong>Fais un test de placement</strong> pour situer précisément ton niveau
CECR — l'allemand est un cas typique où on surestime sa compréhension écrite et
sous-estime sa production orale.</li>
<li><strong>Priorise l'oral</strong> tôt dans la préparation : c'est souvent l'épreuve
la plus redoutée, et celle qui progresse le plus lentement sans pratique régulière.</li>
<li><strong>Simule le format exact du Goethe-Zertifikat</strong> visé (A1, A2, B1...) :
chaque niveau a ses propres types de tâches, pas seulement un niveau de difficulté
différent.</li>
<li><strong>Fais-toi corriger</strong> à l'écrit comme à l'oral — les fautes de
déclinaison, en particulier, deviennent des automatismes si elles ne sont jamais
corrigées.</li>
</ol>

<p>PrePla propose un parcours d'allemand structuré par niveau CECR avec correction IA
immédiate de l'écrit et de l'oral. <a href="/register">Essaie gratuitement pendant 7
jours</a>.</p>
HTML,
            ],
            [
                'slug' => 'combien-de-temps-preparer-examen-langue',
                'title' => "Combien de temps faut-il pour préparer un examen de langue en partant de zéro ?",
                'description' => "IELTS, TCF, DELF, Goethe... combien de temps réaliste prévoir pour préparer un examen de langue selon ton niveau de départ.",
                'category' => 'Méthode',
                'theme' => 'method',
                'published_at' => '2026-07-11',
                'read_minutes' => 5,
                'body' => <<<'HTML'
<p>C'est la question la plus posée avant de commencer une préparation — et la
réponse honnête est « ça dépend », mais pas de manière vague : il existe des repères
concrets selon ton niveau de départ et le niveau visé.</p>

<h2>Le vrai facteur, ce n'est pas le calendrier, c'est le nombre d'heures</h2>
<p>Les grilles du CECR (Cadre européen commun de référence) donnent des ordres de
grandeur reconnus internationalement pour le nombre d'heures d'apprentissage
nécessaires pour passer d'un niveau à l'autre :</p>
<table>
<tr><th>Transition</th><th>Heures de pratique estimées</th></tr>
<tr><td>A1 → A2</td><td>~100-150 h</td></tr>
<tr><td>A2 → B1</td><td>~150-200 h</td></tr>
<tr><td>B1 → B2</td><td>~200-250 h</td></tr>
<tr><td>B2 → C1</td><td>~200-300 h</td></tr>
</table>
<p>Ce sont des ordres de grandeur, pas des garanties individuelles — l'exposition
préalable à la langue (scolarité, médias, entourage) fait varier ces chiffres dans
les deux sens.</p>

<h2>Ce que ça donne concrètement</h2>
<p>Avec 45 minutes de pratique réelle par jour (pas de la révision passive — de la
pratique active, corrigée), 5 jours par semaine :</p>
<ul>
<li><strong>~4 heures/semaine</strong> → passer d'un niveau à l'autre prend
généralement 6 à 12 semaines selon la transition visée.</li>
<li>Doubler le temps quotidien réduit sensiblement la durée, mais ne la divise pas
par deux mécaniquement — la consolidation prend du temps, quel que soit le rythme.</li>
</ul>

<h2>Pourquoi tant de candidats sous-estiment ce temps</h2>
<p>Le piège classique : confondre « je comprends quand je lis » avec « je maîtrise ce
niveau ». La compréhension passive progresse plus vite que la production active
(parler, écrire), qui est justement ce que l'examen évalue le plus sévèrement. Un
plan de préparation réaliste consacre donc plus de temps à la production qu'à la
simple révision de règles déjà « comprises ».</p>

<h2>Comment raccourcir intelligemment (sans tricher)</h2>
<ol>
<li><strong>Commence par un vrai test de placement</strong> — beaucoup de candidats
perdent des semaines à réviser un niveau déjà acquis par excès de prudence.</li>
<li><strong>Entraîne-toi directement sur le format de l'examen visé</strong>, pas sur
des exercices génériques — le format représente une part réelle et mesurable du
score.</li>
<li><strong>Fais-toi corriger régulièrement</strong> à l'écrit et à l'oral : une
erreur non corrigée devient une habitude, une habitude prend bien plus longtemps à
corriger qu'à apprendre juste du premier coup.</li>
</ol>

<p>PrePla mesure ton niveau réel avec un test de placement gratuit, puis génère un
parcours d'exercices adapté — pour ne plus réviser à l'aveugle.
<a href="/register">Commence gratuitement</a>.</p>
HTML,
            ],
            [
                'slug' => 'score-ielts-niveau-cecr',
                'title' => 'Score IELTS et niveau CECR : comment lire ta grille de correction',
                'description' => "Comprendre la correspondance entre bandes IELTS et niveaux CECR (A2 à C2), et ce que ça change concrètement pour ton dossier.",
                'category' => 'IELTS',
                'theme' => 'ielts',
                'published_at' => '2026-07-11',
                'read_minutes' => 5,
                'body' => <<<'HTML'
<p>Beaucoup de candidats reçoivent leur relevé de notes IELTS et ne savent pas vraiment
situer leur niveau : « 6.5, c'est bien ou pas ? ». La réponse dépend surtout de comment
on relie ce score au Cadre européen commun de référence (CECR) — le référentiel utilisé
partout ailleurs (DELF, Goethe, etc.).</p>

<h2>Correspondance approximative bande IELTS / niveau CECR</h2>
<table>
<tr><th>Bande IELTS</th><th>Niveau CECR approximatif</th></tr>
<tr><td>3.0 - 3.5</td><td>A2</td></tr>
<tr><td>4.0 - 5.0</td><td>B1</td></tr>
<tr><td>5.5 - 6.5</td><td>B2</td></tr>
<tr><td>7.0 - 8.0</td><td>C1</td></tr>
<tr><td>8.5 - 9.0</td><td>C2</td></tr>
</table>
<p>Ce tableau est une correspondance indicative largement utilisée, pas une conversion
officielle exacte publiée par IELTS — pour un dossier précis (université, immigration),
vérifie toujours le seuil demandé directement en bande IELTS, tel qu'exigé par
l'organisme concerné.</p>

<h2>Pourquoi la moyenne ne suffit pas</h2>
<p>Ton relevé IELTS affiche une bande par épreuve (Listening, Reading, Writing,
Speaking) ET une bande globale (moyenne arrondie). La plupart des programmes exigent un
minimum <strong>sur chaque épreuve</strong>, pas seulement sur la moyenne. Un candidat
avec 8.0 en Listening, 8.0 en Reading, mais 5.5 en Writing peut avoir une belle moyenne
globale tout en étant recalé sur l'exigence minimale en Writing.</p>

<h2>Ce que ça change pour ta préparation</h2>
<ul>
<li>Identifie <strong>l'épreuve la plus faible</strong>, pas la moyenne — c'est
souvent le Writing ou le Speaking pour un candidat qui lit/comprend bien mais
produit moins.</li>
<li>Un score CECR "B2 à l'oral au quotidien" ne veut pas dire "B2 dans le format
IELTS" — le format (temps limité, tâches précises) fait perdre des points même à un
niveau réel plus élevé.</li>
<li>Progresser d'une demi-bande (par ex. 6.0 → 6.5) demande généralement bien moins
de temps que de progresser d'un niveau CECR entier — mais ça reste un vrai travail
ciblé sur les critères de correction précis de chaque épreuve.</li>
</ul>

<p>PrePla situe ton niveau réel par épreuve avec un test de placement, puis cible
l'entraînement sur ton épreuve la plus faible. <a href="/register">Essaie gratuitement
7 jours</a>.</p>
HTML,
            ],
            [
                'slug' => 'campus-france-dossier-cameroun',
                'title' => 'Campus France : comment monter un dossier solide depuis le Cameroun',
                'description' => "Les étapes clés d'un dossier Campus France depuis le Cameroun, et où le niveau de français (DELF/DALF) pèse vraiment dans la décision.",
                'category' => 'France',
                'theme' => 'letter',
                'published_at' => '2026-07-11',
                'read_minutes' => 7,
                'body' => <<<'HTML'
<p>Campus France est le passage obligé pour la majorité des candidats camerounais visant
des études supérieures en France. Le processus est administratif autant que
linguistique — et beaucoup de dossiers solides sur le papier échouent sur des détails
évitables.</p>

<h2>Les grandes étapes (à confirmer sur le site officiel, la procédure évolue)</h2>
<ol>
<li><strong>Création du dossier en ligne</strong> et choix des formations, généralement
avec un nombre de vœux limité.</li>
<li><strong>Justification du niveau de français</strong> — un DELF B2 (ou équivalent)
est très souvent demandé pour une formation en français, sauf pour certains
programmes enseignés en anglais.</li>
<li><strong>Lettre de motivation et projet d'études</strong> — c'est souvent ici, pas
sur le relevé de notes, que se joue la décision pour des dossiers avec un profil
académique proche.</li>
<li><strong>Entretien</strong> (selon le pays/l'établissement) — où la maîtrise réelle
du français à l'oral, pas seulement à l'écrit, est directement testée.</li>
</ol>

<h2>Où les dossiers camerounais perdent des points</h2>
<ul>
<li><strong>Projet d'études trop générique</strong> : "je veux étudier en France pour
un avenir meilleur" ne dit rien de concret. Un projet qui relie ton parcours, la
formation précise visée et un objectif professionnel clair est nettement plus
convaincant.</li>
<li><strong>Niveau de français surestimé</strong> : parler couramment au quotidien
n'équivaut pas automatiquement à un B2 académique testé en conditions DELF — d'où
l'intérêt de passer un vrai test plutôt que de deviner.</li>
<li><strong>Incohérence entre le dossier et l'entretien</strong> : un dossier écrit
parfait suivi d'un entretien hésitant interroge le jury sur qui a réellement rédigé le
dossier.</li>
</ul>

<h2>Comment se préparer utilement</h2>
<p>Deux choses comptent vraiment en amont : obtenir un DELF/DALF qui reflète ton niveau
réel (pas un score "juste suffisant" obtenu de justesse), et t'entraîner à
<strong>parler</strong> de ton projet d'études en français, à l'oral, de façon fluide et
argumentée — c'est exactement ce que l'entretien va tester.</p>

<p>PrePla t'aide à structurer ta préparation DELF/DALF avec un test de placement et des
exercices d'expression orale corrigés par IA. <a href="/register">Essaie gratuitement 7
jours</a>.</p>
HTML,
            ],
            [
                'slug' => 'quel-test-langue-canada',
                'title' => 'PVT, Entrée express ou étudiant : quel test de langue pour quel parcours vers le Canada ?',
                'description' => "TCF, TEF, IELTS, CELPIP... quel test choisir selon que tu vises un PVT, Entrée express ou un permis d'études au Canada.",
                'category' => 'Canada',
                'theme' => 'canada',
                'published_at' => '2026-07-11',
                'read_minutes' => 6,
                'body' => <<<'HTML'
<p>Un des premiers blocages dans un projet d'immigration vers le Canada, c'est souvent
de comprendre <em>quel</em> test de langue passer — la réponse dépend entièrement du
programme visé, pas d'une préférence personnelle pour le français ou l'anglais.</p>

<h2>Aperçu par type de parcours</h2>
<table>
<tr><th>Parcours</th><th>Tests généralement acceptés</th></tr>
<tr><td>Entrée express (fédéral)</td><td>IELTS ou CELPIP (anglais) · TEF Canada ou TCF Canada (français)</td></tr>
<tr><td>Programme des travailleurs qualifiés du Québec (PSTQ)</td><td>TEF Canada ou TCF Canada (français), exigé pour la majorité des profils</td></tr>
<tr><td>Permis d'études</td><td>Selon l'établissement : IELTS Academic le plus souvent, parfois TOEFL ou Duolingo English Test accepté</td></tr>
<tr><td>PVT (Programme Vacances-Travail)</td><td>Généralement aucun test de langue exigé à l'entrée</td></tr>
</table>
<p>Cette vue d'ensemble est indicative — les exigences précises changent régulièrement.
Vérifie toujours le programme exact sur le site d'IRCC, d'Immigration Québec ou de
l'établissement visé avant de t'inscrire à un test.</p>

<h2>Comment décider concrètement</h2>
<ul>
<li><strong>Détermine d'abord le programme</strong>, pas la langue — c'est le
programme qui impose le test, pas l'inverse.</li>
<li><strong>Si tu vises le Québec</strong>, le français pèse presque toujours plus
lourd dans la grille de points que pour Entrée express fédéral — un bon TEF/TCF peut
faire une vraie différence.</li>
<li><strong>Si les deux langues sont possibles</strong> pour ton programme, teste
d'abord ton niveau réel dans chacune avant de choisir — inutile de préparer un
examen dans la langue où tu es objectivement plus faible si l'autre est acceptée.</li>
</ul>

<h2>Une préparation qui ne se perd pas selon le test choisi</h2>
<p>Que ce soit IELTS, TEF ou TCF, la préparation qui fonctionne est la même dans son
principe : identifier précisément ton niveau de départ, t'entraîner sur le format exact
de l'épreuve choisie, et te faire corriger régulièrement à l'écrit et à l'oral plutôt
que de réviser la grammaire dans le vide.</p>

<p>PrePla couvre l'anglais (IELTS) et le français (TEF/TCF) avec un parcours adapté à
ton niveau réel. <a href="/register">Commence gratuitement</a>.</p>
HTML,
            ],
            [
                'slug' => 'erreurs-prononciation-anglais-francophones',
                'title' => "Les erreurs de prononciation les plus fréquentes des francophones en anglais",
                'description' => "Les erreurs de prononciation anglaise typiques chez les francophones (sons, accent tonique) et comment les corriger avant un examen oral.",
                'category' => 'Prononciation',
                'theme' => 'speaking',
                'published_at' => '2026-07-11',
                'read_minutes' => 6,
                'body' => <<<'HTML'
<p>À l'oral d'un examen comme l'IELTS ou le TOEFL, la prononciation ne se juge pas sur
un accent "parfait" — elle se juge sur l'intelligibilité. Certaines erreurs
récurrentes chez les francophones nuisent bien plus à la compréhension que d'autres :
voici celles qui comptent vraiment.</p>

<h2>Les sons qui n'existent pas en français</h2>
<ul>
<li><strong>Le "th"</strong> (think / this) — souvent remplacé par un "s" ou un "z"
français ("sink" au lieu de "think"). Solution : placer le bout de la langue entre
les dents, pas derrière.</li>
<li><strong>Le "h" aspiré</strong> — souvent muet en français, souvent oublié en
anglais ("appy" au lieu de "happy"), ce qui peut carrément changer le sens du mot
compris par l'examinateur.</li>
<li><strong>Les voyelles courtes vs longues</strong> ("ship" vs "sheep") — une
distinction qui n'existe pas en français et qui peut créer de vraies confusions de
sens.</li>
</ul>

<h2>L'accent tonique : l'erreur la plus sous-estimée</h2>
<p>En français, l'accent tonique tombe presque toujours en fin de mot ou de groupe.
En anglais, il est mobile et change parfois le sens ou la nature grammaticale du mot
("PREsent" le cadeau vs "preSENT" présenter). Un francophone qui accentue tous les
mots de la même façon reste compréhensible mais sonne "plat" et peut perdre des points
sur le critère de fluidité/prosodie, noté explicitement dans les grilles IELTS/TOEFL.</p>

<h2>Comment corriger efficacement (sans juste "écouter plus")</h2>
<ol>
<li><strong>Enregistre-toi</strong> en répétant des phrases courtes, puis compare à un
modèle natif — le décalage est souvent plus net à l'oreille qu'on ne le pense en
parlant.</li>
<li><strong>Travaille les paires minimales</strong> (ship/sheep, think/sink) plutôt que
des textes entiers — ça isole précisément le son à corriger.</li>
<li><strong>Marque l'accent tonique</strong> sur le vocabulaire nouveau que tu
apprends, dès le départ — corriger un automatisme pris depuis des années prend bien
plus de temps que d'apprendre juste du premier coup.</li>
</ol>

<p>Les exercices de speaking de PrePla incluent une correction par IA qui repère ces
erreurs récurrentes de prononciation, pas seulement le vocabulaire et la grammaire.
<a href="/register">Essaie gratuitement 7 jours</a>.</p>
HTML,
            ],
            [
                'slug' => 'lettre-motivation-visa-etudes',
                'title' => "Comment rédiger une lettre de motivation convaincante pour un visa d'études",
                'description' => "Structure, ton et erreurs à éviter pour une lettre de motivation ou un projet d'études qui convainc vraiment (Campus France, visa étudiant).",
                'category' => 'Méthode',
                'theme' => 'letter',
                'published_at' => '2026-07-11',
                'read_minutes' => 6,
                'body' => <<<'HTML'
<p>Que ce soit pour Campus France, une université anglophone ou une Studienkolleg
allemande, la lettre de motivation (ou "personal statement") pèse souvent autant que
le dossier académique dans la décision finale — et c'est aussi la pièce la plus
mal préparée par la majorité des candidats.</p>

<h2>La structure qui fonctionne</h2>
<ol>
<li><strong>Un point de départ concret</strong> : pourquoi ce domaine, illustré par une
expérience réelle (un stage, un projet, un déclic précis) — pas une déclaration
abstraite ("j'ai toujours été passionné par...").</li>
<li><strong>Le lien avec la formation précise visée</strong> : montre que tu connais le
programme (contenu, spécificités), pas juste le nom de l'établissement.</li>
<li><strong>Un projet professionnel cohérent</strong> : ce que tu comptes faire après,
et pourquoi cette formation précise t'y mène — c'est souvent ce qui manque le plus.</li>
<li><strong>Une conclusion qui ne se répète pas</strong> : évite de reformuler tout ce
qui précède ; termine sur une phrase claire et engagée.</li>
</ol>

<h2>Ce qui affaiblit une lettre, même bien écrite</h2>
<ul>
<li><strong>Trop générique</strong> : une lettre qui pourrait être envoyée à
n'importe quelle formation sans modification n'en dit rien à l'examinateur.</li>
<li><strong>Trop longue</strong> : au-delà de la limite demandée (souvent 1 page),
une lettre dilue son message au lieu de le renforcer.</li>
<li><strong>Un niveau de langue qui ne correspond pas au reste du dossier</strong> :
une lettre visiblement beaucoup mieux écrite que ton test de langue officiel éveille
des soupçons légitimes sur qui l'a réellement rédigée.</li>
</ul>

<h2>Le lien avec ta préparation linguistique</h2>
<p>Une bonne lettre en français ou en anglais suppose une vraie maîtrise de
l'argumentation écrite — exactement ce que travaillent les épreuves d'expression
écrite du DELF, du TCF/TEF ou de l'IELTS. Progresser sur ces épreuves améliore
directement la qualité de ta lettre de motivation, pas seulement ton score
d'examen.</p>

<p>PrePla corrige tes productions écrites avec un retour détaillé sur la structure et
l'argumentation, pas seulement la grammaire. <a href="/register">Essaie gratuitement 7
jours</a>.</p>
HTML,
            ],
            [
                'slug' => 'toefl-ibt-guide-afrique',
                'title' => "TOEFL iBT : comprendre le format et se préparer efficacement depuis l'Afrique",
                'description' => "Le format du TOEFL iBT (100 % ordinateur), les pièges spécifiques et un plan de préparation pour candidats africains.",
                'category' => 'TOEFL',
                'theme' => 'toefl',
                'published_at' => '2026-07-11',
                'read_minutes' => 6,
                'body' => <<<'HTML'
<p>Le TOEFL iBT (Internet-Based Test) est accepté par un très grand nombre
d'universités anglophones, souvent en alternative à l'IELTS. Son format
<strong>entièrement sur ordinateur</strong> — y compris pour l'épreuve orale — surprend
beaucoup de candidats qui n'ont pratiqué qu'à l'oral en face à face.</p>

<h2>Ce qui distingue vraiment le TOEFL de l'IELTS</h2>
<ul>
<li><strong>Speaking face à un micro, pas un examinateur</strong> : tu réponds seul, en
parlant dans un micro, souvent avec d'autres candidats qui parlent en même temps dans
la même salle — une condition qui déstabilise si elle n'a jamais été pratiquée.</li>
<li><strong>Tâches intégrées</strong> : plusieurs épreuves combinent lecture/écoute
puis production (résumer un cours à l'oral ou à l'écrit après l'avoir écouté) — une
compétence spécifique qui se travaille à part.</li>
<li><strong>Frappe au clavier</strong> pour le Writing : la vitesse et l'aisance de
frappe font une vraie différence dans le temps disponible pour structurer sa
réponse.</li>
</ul>

<h2>Plan de préparation type</h2>
<table>
<tr><th>Étape</th><th>Objectif</th></tr>
<tr><td>1. Diagnostic</td><td>Situer ton niveau réel sur les 4 épreuves, avec un vrai test de placement</td></tr>
<tr><td>2. Tâches intégrées</td><td>S'entraîner spécifiquement au format écouter/lire puis produire — le point le plus souvent négligé</td></tr>
<tr><td>3. Speaking au micro</td><td>Pratiquer à voix haute, seul face à un enregistreur, en conditions de temps limité</td></tr>
<tr><td>4. Frappe et gestion du temps</td><td>S'entraîner à taper vite et à structurer une réponse écrite dans le temps imparti</td></tr>
</table>

<h2>Erreur fréquente : préparer le TOEFL comme l'IELTS</h2>
<p>Les deux tests évaluent l'anglais académique, mais leurs formats sont assez
différents pour que des candidats bien préparés pour l'un soient déstabilisés par
l'autre le jour J. Vérifie d'abord lequel est accepté par ta destination (souvent les
deux, mais pas toujours), puis entraîne-toi spécifiquement sur le format choisi.</p>

<p>PrePla propose des exercices calqués sur le format TOEFL comme IELTS, avec
correction IA immédiate. <a href="/register">Essaie gratuitement 7 jours</a>.</p>
HTML,
            ],
            [
                'slug' => 'ressources-gratuites-langues',
                'title' => "Les meilleures ressources gratuites pour progresser en langue au quotidien",
                'description' => "Comment progresser en anglais, français ou allemand gratuitement entre deux sessions de préparation, sans dépenser plus que ta connexion internet.",
                'category' => 'Méthode',
                'theme' => 'resources',
                'published_at' => '2026-07-11',
                'read_minutes' => 5,
                'body' => <<<'HTML'
<p>Une préparation d'examen efficace ne se résume pas aux heures d'exercices ciblés —
l'exposition régulière à la langue, même informelle, accélère nettement la
progression. Voici comment en tirer parti sans que ça coûte cher en données mobiles.</p>

<h2>Écoute active plutôt que passive</h2>
<p>Regarder une série en VO, ce n'est utile que si c'est fait <strong>activement</strong> :
pause pour répéter une phrase, chercher un mot inconnu, réécouter un passage mal
compris. En fond sonore sans attention, l'effet sur la compréhension orale reste très
limité — mieux vaut 15 minutes actives qu'une heure en fond sonore.</p>

<h2>Ce qui fonctionne, à petit budget de données</h2>
<ul>
<li><strong>Podcasts</strong> : contenu audio léger en données, réécoutable, souvent
transcrit — idéal pour travailler la compréhension orale sans vidéo.</li>
<li><strong>Lecture de presse dans la langue cible</strong> : gratuite, textuelle
(quasi aucune donnée), et directement utile pour le vocabulaire académique demandé en
Reading/compréhension écrite.</li>
<li><strong>Écriture quotidienne courte</strong> : tenir un journal de 5 lignes par
jour dans la langue cible progresse la production écrite bien plus qu'on ne le pense,
sans nécessiter internet.</li>
<li><strong>Groupes d'échange linguistique</strong> : pratiquer à l'oral avec d'autres
apprenants, souvent via de simples groupes WhatsApp ou communautés en ligne
gratuites.</li>
</ul>

<h2>Le piège : confondre exposition et préparation ciblée</h2>
<p>Ces habitudes gratuites entretiennent et consolident un niveau — elles ne
remplacent pas un entraînement ciblé sur le format exact de l'examen visé (temps
limité, types de questions précis, critères de correction). Les deux sont
complémentaires : l'exposition quotidienne nourrit le niveau général, la préparation
ciblée nourrit le score à l'examen.</p>

<p>PrePla se concentre sur la partie « préparation ciblée » : exercices calqués sur le
format réel de ton examen, avec correction immédiate. <a href="/register">Essaie
gratuitement 7 jours</a>.</p>
HTML,
            ],
            [
                'slug' => 'motivation-preparation-examen-langue',
                'title' => "Comment garder sa motivation pendant plusieurs mois de préparation",
                'description' => "Tenir plusieurs mois de préparation à un examen de langue sans décrocher : méthode, régularité et pièges à éviter.",
                'category' => 'Méthode',
                'theme' => 'method',
                'published_at' => '2026-07-11',
                'read_minutes' => 5,
                'body' => <<<'HTML'
<p>Le plus grand risque d'une préparation de plusieurs mois n'est pas le niveau de
départ — c'est l'abandon en cours de route. Voici ce qui fait réellement tenir la
distance, au-delà de la simple "motivation" du premier jour.</p>

<h2>Pourquoi la motivation seule ne suffit pas</h2>
<p>La motivation initiale (le projet d'immigration, d'études, l'objectif de carrière)
est indispensable pour démarrer, mais elle fluctue naturellement dans le temps. Ce qui
fait tenir sur la durée, ce sont des <strong>habitudes</strong> qui ne dépendent pas de
l'humeur du jour : un créneau fixe, une durée courte mais non négociable, un suivi
visible de la progression.</p>

<h2>Ce qui fonctionne concrètement</h2>
<ul>
<li><strong>Des sessions courtes et régulières</strong> plutôt que de longues séances
occasionnelles — 20 à 30 minutes chaque jour progressent plus qu'une session de 3
heures une fois par semaine, et sont bien plus faciles à tenir dans la durée.</li>
<li><strong>Un objectif intermédiaire visible</strong> : suivre sa progression de
niveau (A2 → B1 → B2...) plutôt que de ne regarder que l'objectif final, lointain et
parfois décourageant.</li>
<li><strong>Une série (streak) à ne pas casser</strong> : l'effet psychologique de ne
pas vouloir "perdre" une série de jours consécutifs est un des leviers de régularité
les plus efficaces, largement documenté dans les apps d'apprentissage.</li>
<li><strong>Accepter les mauvais jours</strong> : une session ratée ou sautée
occasionnellement ne compromet rien tant que la régularité globale reprend le
lendemain — le perfectionnisme est souvent ce qui fait abandonner après un premier
écart.</li>
</ul>

<h2>Se fixer une date réaliste</h2>
<p>Une date d'examen trop proche par rapport au niveau de départ crée une pression qui
épuise la motivation plus vite qu'elle ne la stimule. Un vrai test de placement en
amont permet de fixer une date d'examen réaliste — ni trop lointaine (qui dilue
l'effort), ni trop proche (qui décourage).</p>

<p>PrePla suit ta progression, ta série de jours d'entraînement et ton niveau réel au
fil du temps — de quoi visualiser concrètement l'avancée plutôt que de naviguer à
l'aveugle. <a href="/register">Commence gratuitement</a>.</p>
HTML,
            ],
            [
                'slug' => 'cout-examens-langue-budget',
                'title' => "Combien coûte un examen de langue et comment bien budgétiser sa préparation",
                'description' => "Frais d'inscription, coûts cachés et comment budgétiser intelligemment sa préparation au TCF, IELTS, DELF ou Goethe-Zertifikat.",
                'category' => 'Budget',
                'theme' => 'budget',
                'published_at' => '2026-07-11',
                'read_minutes' => 5,
                'body' => <<<'HTML'
<p>Le coût d'un examen de langue ne se limite pas aux frais d'inscription — mal
anticiper l'ensemble des dépenses est une source fréquente de stress évitable dans un
projet déjà coûteux (immigration, études à l'étranger).</p>

<h2>Ce qui compose le vrai coût total</h2>
<ul>
<li><strong>Frais d'inscription à l'examen</strong> — variables selon le test et le
centre, à vérifier directement auprès du centre agréé le plus proche (Yaoundé,
Douala...), les tarifs évoluent régulièrement.</li>
<li><strong>Frais de déplacement</strong> si le centre agréé n'est pas dans ta ville —
souvent sous-estimés dans le budget initial.</li>
<li><strong>Matériel de préparation</strong> : manuels, plateformes en ligne, cours
particuliers éventuels.</li>
<li><strong>Le coût d'un échec évitable</strong> : repasser un examen faute de
préparation suffisante double une bonne partie de ces coûts — c'est souvent la
dépense la plus lourde et la plus évitable de tout le processus.</li>
</ul>

<h2>Comment réduire le risque de repasser l'examen</h2>
<p>La façon la plus efficace de maîtriser son budget n'est pas de chercher l'examen le
moins cher, mais de <strong>maximiser ses chances de réussir du premier coup</strong> :</p>
<ol>
<li><strong>Fais un test de placement</strong> avant de fixer une date — s'inscrire
trop tôt par rapport à son niveau réel est la cause la plus fréquente d'échec évitable.</li>
<li><strong>Priorise une préparation ciblée sur le format</strong> plutôt que des
ressources génériques payantes qui ne collent pas exactement à l'examen visé.</li>
<li><strong>Simule l'examen en conditions réelles</strong> avant la date officielle —
un candidat qui découvre le format le jour J prend un risque financier inutile.</li>
</ol>

<h2>Prévoir un budget réaliste, pas minimal</h2>
<p>Prévoir uniquement les frais d'inscription les plus bas, sans marge pour une
préparation sérieuse, pousse souvent à sous-investir dans l'entraînement — et donc à
risquer un échec plus coûteux qu'une préparation correcte dès le départ. Le bon
calcul, c'est le coût total en cas d'échec à comparer au coût d'une préparation
sérieuse en amont.</p>

<p>PrePla propose un essai gratuit de 7 jours pour évaluer ton niveau réel avant
d'investir dans une préparation ou une inscription à l'examen.
<a href="/register">Commence gratuitement</a>.</p>
HTML,
            ],
        ];
    }

    public static function find(string $slug): ?array
    {
        foreach (self::posts() as $post) {
            if ($post['slug'] === $slug) {
                return $post;
            }
        }
        return null;
    }

    /** Posts other than the given slug, most recent first, capped to $limit. */
    public static function others(string $slug, int $limit = 3): array
    {
        return array_slice(array_values(array_filter(
            self::posts(),
            fn (array $p) => $p['slug'] !== $slug
        )), 0, $limit);
    }
}
