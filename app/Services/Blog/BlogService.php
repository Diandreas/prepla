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
