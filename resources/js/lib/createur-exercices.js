/**
 * Le createur d'exercices d'un centre, sans React : libelles francais des
 * types, regroupement par competence, verification des questions avant envoi,
 * operations sur la liste de questions, brouillon et filtres de la page
 * « Contenu ».
 *
 * Le serveur ne connait que des noms techniques anglais (« Multiple Choice »,
 * « Matching Headings »), une ligne par section et par examen. Un responsable
 * de centre doit lire « Questions à choix multiples » et savoir en une phrase
 * ce que l'eleve fera. Sans import : teste directement par node
 * (tests/js/createur-exercices.test.mjs).
 */

/** L'ordre du CECRL. */
export const NIVEAUX = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

/**
 * Les competences, dans l'ordre ou un centre les presente. Le vocabulaire est
 * range avec la grammaire : les examens ne l'evaluent jamais seul.
 */
export const COMPETENCES = [
    { cle: 'reading', nom: 'Compréhension écrite', court: 'Lecture' },
    { cle: 'listening', nom: 'Compréhension orale', court: 'Écoute' },
    { cle: 'writing', nom: 'Expression écrite', court: 'Écrit' },
    { cle: 'speaking', nom: 'Expression orale', court: 'Oral' },
    { cle: 'grammar', nom: 'Grammaire et vocabulaire', court: 'Grammaire' },
];

const AUTRE = { cle: 'autre', nom: 'Autres exercices', court: 'Autre' };

/** La competence d'un type, a partir de son skill_type. */
export function competenceDe(skillType) {
    const cle = skillType === 'vocabulary' ? 'grammar' : skillType;
    return COMPETENCES.find((c) => c.cle === cle) ?? AUTRE;
}

/**
 * Les noms francais, par slug du type (le nom que l'examen donne a sa partie),
 * plus precis que le format : « matching-headings » et « matching » partagent
 * le meme composant mais pas la meme tache.
 */
const PAR_SLUG = {
    'mcq': ['Questions à choix multiples', 'Une question, plusieurs propositions : l’élève choisit la bonne.'],
    'grammar-mcq': ['QCM de grammaire', 'Des phrases isolées où l’élève choisit la forme correcte.'],
    'read-daily-life': ['Lire un document du quotidien', 'Une annonce, un panneau, un message : l’élève choisit ce qu’il signifie.'],
    'mcq-cloze': ['Texte à trous avec propositions', 'Pour chaque trou du texte, l’élève choisit le bon mot parmi des propositions.'],
    'gap-fill': ['Texte à trous', 'L’élève complète chaque trou avec le mot qui convient.'],
    'open-cloze': ['Texte à trous sans propositions', 'L’élève écrit lui-même le mot manquant de chaque trou numéroté.'],
    'true-false': ['Vrai ou faux', 'L’élève dit si chaque affirmation est juste ou fausse.'],
    'true-false-not-given': ['Vrai / Faux / Non mentionné', 'L’élève dit si l’affirmation est juste, fausse ou absente du document.'],
    'yes-no-not-given': ['Oui / Non / Non mentionné', 'L’élève dit si l’auteur partage l’avis, le rejette, ou n’en dit rien.'],
    'matching': ['Associer', 'L’élève relie chaque élément à la proposition qui lui correspond.'],
    'matching-headings': ['Associer des titres aux paragraphes', 'L’élève choisit le titre qui résume chaque paragraphe.'],
    'matching-features': ['Associer des informations à des personnes', 'L’élève retrouve qui dit ou fait quoi.'],
    'matching-information': ['Retrouver une information', 'L’élève indique dans quel paragraphe se trouve chaque information.'],
    'matching-sentence-endings': ['Compléter des débuts de phrase', 'L’élève associe chaque début de phrase à sa fin.'],
    'multiple-matching': ['Associer plusieurs textes', 'Plusieurs courts textes, plusieurs questions : l’élève relie les deux.'],
    'picture-mcq': ['Choisir la bonne image', 'L’élève écoute ou lit, puis choisit l’image qui correspond.'],
    'sentence-completion': ['Compléter des phrases', 'L’élève termine chaque phrase d’après le document.'],
    'short-answer': ['Réponses courtes', 'L’élève répond en quelques mots, d’après le document.'],
    'note-completion': ['Compléter des notes', 'L’élève remplit les trous d’une prise de notes.'],
    'form-completion': ['Remplir un formulaire', 'L’élève complète les champs d’un formulaire.'],
    'table-completion': ['Compléter un tableau', 'L’élève remplit les cases vides d’un tableau.'],
    'summary-completion': ['Compléter un résumé', 'L’élève complète les trous d’un résumé du document.'],
    'prose-summary': ['Choisir les idées du résumé', 'L’élève retient les phrases qui résument le mieux le texte.'],
    'flow-chart-completion': ['Compléter un schéma d’étapes', 'L’élève remplit les étapes manquantes d’un processus.'],
    'diagram-labeling': ['Légender un schéma', 'L’élève nomme les parties d’un schéma ou d’une image.'],
    'plan-map-diagram-labeling': ['Légender un plan ou une carte', 'L’élève place les lieux sur un plan, une carte ou un schéma.'],
    'complete-the-words': ['Compléter les mots', 'L’élève termine les mots dont il manque la fin.'],
    'ordering': ['Remettre dans l’ordre', 'L’élève replace des phrases ou des étapes dans le bon ordre.'],
    'gapped-text': ['Replacer des phrases dans un texte', 'L’élève remet à sa place chaque phrase retirée du texte.'],
    'insert-text': ['Insérer une phrase', 'L’élève choisit où une phrase s’insère dans le texte.'],
    'word-formation': ['Former des mots', 'L’élève transforme un mot donné pour qu’il convienne à la phrase.'],
    'key-word-transformation': ['Reformuler avec un mot imposé', 'L’élève réécrit la phrase avec le mot donné, sans en changer le sens.'],
    'build-a-sentence': ['Construire la phrase', 'L’élève remet des mots dans l’ordre pour former une phrase correcte.'],
    'dictation': ['Dictée', 'L’élève écoute et écrit ce qu’il entend.'],
    'listen-repeat': ['Écouter et répéter', 'L’élève écoute une phrase puis la répète.'],
    'listen-choose-response': ['Écouter et choisir la réplique', 'L’élève écoute une phrase et choisit la réponse qui convient.'],
    'short-writing': ['Message court', 'L’élève écrit un court message : invitation, réponse, mot d’excuse.'],
    'essay': ['Essai argumenté', 'L’élève défend un point de vue dans un texte structuré.'],
    'letter-writing': ['Lettre', 'L’élève rédige une lettre adaptée à la situation.'],
    'letter-email-writing': ['Lettre ou e-mail', 'L’élève rédige un e-mail ou une lettre en réponse à une situation.'],
    'article-writing': ['Article', 'L’élève écrit un article pour un journal ou un site.'],
    'report-writing': ['Rapport', 'L’élève rédige un rapport structuré à partir d’une situation.'],
    'review-writing': ['Critique', 'L’élève donne son avis sur un livre, un film, un lieu.'],
    'summary-writing': ['Résumé écrit', 'L’élève résume un texte avec ses propres mots.'],
    'graph-description': ['Décrire un graphique', 'L’élève décrit et compare les données d’un graphique.'],
    'guided-rewrite': ['Réécriture guidée', 'L’élève réécrit ou résume un texte en suivant des consignes.'],
    'text-continuation': ['Continuer le texte', 'L’élève imagine et écrit la suite d’un texte.'],
    'integrated-writing': ['Écrire à partir de documents', 'L’élève lit et écoute, puis rédige une synthèse argumentée.'],
    'integrated-speaking': ['Parler à partir de documents', 'L’élève lit et écoute, puis résume à l’oral.'],
    'synthesis': ['Synthèse de documents', 'L’élève rassemble les idées de plusieurs documents.'],
    'synthesis-essay': ['Synthèse et essai', 'L’élève fait la synthèse de documents, puis donne son avis.'],
    'academic-discussion': ['Discussion en ligne', 'L’élève répond par écrit à un débat entre un professeur et des étudiants.'],
    'speaking-response': ['Répondre à l’examinateur', 'L’élève répond à voix haute à des questions simples.'],
    'speaking-long-turn': ['Exposé oral', 'L’élève parle seul quelques minutes sur un sujet donné.'],
    'speaking-discussion': ['Discussion argumentée', 'L’élève donne et défend son avis à l’oral.'],
    'guided-introduction': ['Se présenter', 'L’élève se présente à voix haute en suivant des points.'],
    'picture-description': ['Décrire une image', 'L’élève décrit une image puis la commente à l’oral.'],
    'role-play': ['Jeu de rôle', 'L’élève joue une situation de la vie courante avec l’examinateur.'],
    'negotiation': ['Négocier, se mettre d’accord', 'L’élève discute pour arriver à une décision commune.'],
    'oral-debate': ['Débat argumenté', 'L’élève débat et répond aux objections.'],
    'speaking-elicitation': ['Poser des questions', 'L’élève pose des questions pour obtenir des informations.'],
};

/** A defaut de slug connu : le format (component_key). */
const PAR_COMPOSANT = {
    'mcq': 'mcq',
    'true-false-ng': 'true-false-not-given',
    'gap-fill': 'gap-fill',
    'matching': 'matching',
    'sentence-completion': 'sentence-completion',
    'short-answer': 'short-answer',
    'word-formation': 'word-formation',
    'key-word-transformation': 'key-word-transformation',
    'insert-text': 'insert-text',
    'dictation': 'dictation',
    'listen-repeat': 'listen-repeat',
    'picture-mcq': 'picture-mcq',
    'build-a-sentence': 'build-a-sentence',
    'listen-choose-response': 'listen-choose-response',
    'open-cloze': 'open-cloze',
    'note-completion': 'note-completion',
    'form-completion': 'form-completion',
    'table-completion': 'table-completion',
    'summary-completion': 'summary-completion',
    'flow-chart-completion': 'flow-chart-completion',
    'multiple-matching': 'multiple-matching',
    'diagram-labeling': 'diagram-labeling',
    'complete-the-words': 'complete-the-words',
    'ordering': 'ordering',
    'gapped-text': 'gapped-text',
    'short-writing': 'short-writing',
    'essay-editor': 'essay',
    'synthesis': 'synthesis',
    'academic-discussion': 'academic-discussion',
    'graph-description': 'graph-description',
    'integrated-task': 'integrated-writing',
    'guided-writing': 'guided-rewrite',
    'speaking-recorder': 'speaking-long-turn',
    'role-play': 'role-play',
};

function entreeDuType(type) {
    if (!type) return null;
    return PAR_SLUG[type.slug] ?? PAR_SLUG[PAR_COMPOSANT[type.component_key]] ?? null;
}

/** Le nom francais d'un type : { slug, component_key, name }. */
export function libelleDuType(type) {
    const entree = entreeDuType(type);
    if (entree) return entree[0];
    const nom = String(type?.name ?? '').trim();
    return nom !== '' ? nom : 'Exercice';
}

/** Une phrase : ce que l'eleve fera. Vide quand le type est inconnu. */
export function descriptionDuType(type) {
    return entreeDuType(type)?.[1] ?? '';
}

/** Le nom d'une partie officielle : « Lesen, Teil 2 », ou son nom francais a defaut. */
export function nomDeLaPartie(partie) {
    if (!partie) return '';
    const nom = String(partie.name ?? '').trim();
    if (nom !== '') return nom;
    return String(partie.name_fr ?? '').trim();
}

/**
 * Les types regroupes par competence, dans l'ordre des competences. Dans un
 * groupe, les parties officielles de l'examen passent d'abord, puis l'ordre
 * alphabetique francais. `parties` : { [typeId]: [partie, ...] }.
 */
export function regrouperParCompetence(types, parties = {}) {
    const groupes = new Map();
    for (const type of types) {
        const competence = competenceDe(type.skill_type);
        if (!groupes.has(competence.cle)) groupes.set(competence.cle, { competence, types: [] });
        groupes.get(competence.cle).types.push(type);
    }
    const officiel = (t) => (Array.isArray(parties?.[t.id]) && parties[t.id].length > 0 ? 0 : 1);
    for (const groupe of groupes.values()) {
        groupe.types.sort((a, b) => officiel(a) - officiel(b) || libelleDuType(a).localeCompare(libelleDuType(b), 'fr'));
    }
    const ordre = [...COMPETENCES.map((c) => c.cle), AUTRE.cle];
    return [...groupes.values()].sort((a, b) => ordre.indexOf(a.competence.cle) - ordre.indexOf(b.competence.cle));
}

// ───────────────────────────── reponses ─────────────────────────────

const A_CHOIX = ['mcq', 'matching', 'listen-choose-response', 'picture-mcq'];
const CHOIX_OU_TEXTE = ['gap-fill', 'sentence-completion'];

/**
 * Comment l'enseignant donne la bonne reponse d'une question de la famille
 * « exact-match » : en cochant une proposition, en choisissant vrai ou faux,
 * ou en l'ecrivant. Le texte a trous et la phrase a completer acceptent les
 * deux : avec propositions des qu'il y en a.
 */
export function modeDeReponse(componentKey, question) {
    if (componentKey === 'true-false-ng') return 'vrai-faux';
    if (A_CHOIX.includes(componentKey)) return 'choix';
    if (CHOIX_OU_TEXTE.includes(componentKey)) {
        return Array.isArray(question?.options) && question.options.length > 0 ? 'choix' : 'texte';
    }
    return 'texte';
}

/** Les formats qui acceptent les deux modes (propositions ou reponse ecrite). */
export function accepteLesDeuxModes(componentKey) {
    return CHOIX_OU_TEXTE.includes(componentKey);
}

/** Le texte d'une proposition, qu'elle soit une chaine ou un objet { text }. */
export function texteDeLOption(option) {
    if (option == null) return '';
    if (typeof option === 'object') return String(option.text ?? option.label ?? option.value ?? '');
    return String(option);
}

export const lettre = (index) => String.fromCharCode(65 + index);

/**
 * La proposition designee comme bonne reponse, ou -1. Le lecteur attend une
 * lettre ; d'anciens exercices portent le texte de l'option : on le retrouve.
 */
export function indexDeLaBonneReponse(question) {
    const options = Array.isArray(question?.options) ? question.options.map(texteDeLOption) : [];
    const brut = Array.isArray(question?.correct_answer) ? question.correct_answer[0] : question?.correct_answer;
    const reponse = String(brut ?? '').trim();
    if (reponse === '') return -1;
    if (/^[A-Za-z]$/.test(reponse)) {
        const i = reponse.toUpperCase().charCodeAt(0) - 65;
        if (i >= 0 && i < options.length) return i;
    }
    const bas = reponse.toLowerCase();
    return options.findIndex((o) => o.trim().toLowerCase() === bas);
}

const LIBELLES_VRAI_FAUX = {
    true: 'Vrai',
    false: 'Faux',
    'not given': 'Non mentionné',
    richtig: 'Richtig',
    falsch: 'Falsch',
    ja: 'Ja',
    nein: 'Nein',
    yes: 'Oui',
    no: 'Non',
};

/** Les choix d'un vrai/faux, comme le lecteur les affiche (voir true-false-ng.tsx). */
export function choixVraiFaux(question) {
    const declares = Array.isArray(question?.answer_options)
        ? question.answer_options.filter((v) => typeof v === 'string' && v.trim() !== '')
        : [];
    if (declares.length >= 2) {
        return declares.map((value) => ({ value, label: LIBELLES_VRAI_FAUX[value.trim().toLowerCase()] ?? value }));
    }
    return [
        { value: 'True', label: 'Vrai' },
        { value: 'False', label: 'Faux' },
        { value: 'Not Given', label: 'Non mentionné' },
    ];
}

// ───────────────────────────── verification ─────────────────────────────

const vide = (v) => String(v ?? '').trim() === '';

/**
 * Ce qui manque a une question, champ par champ, avant l'envoi : la meme
 * exigence que ExerciseSchemaRegistry::validateQuestions cote serveur, plus ce
 * qui rendrait la question injouable (deux propositions identiques, une bonne
 * reponse qui designe une proposition vide).
 *
 * @returns {{ champ: string, message: string }[]}
 */
export function problemesDeLaQuestion(question, { famille, componentKey }) {
    const q = question ?? {};
    const problemes = [];
    const ajoute = (champ, message) => problemes.push({ champ, message });

    switch (famille) {
        case 'exact-match': {
            const sonPourTexte = ['listen-repeat', 'listen-choose-response'].includes(componentKey) && !vide(q.audio_text);
            if (vide(q.text) && !sonPourTexte) ajoute('text', 'Écrivez l’énoncé de la question.');
            const mode = modeDeReponse(componentKey, q);
            if (mode === 'choix') {
                const options = (Array.isArray(q.options) ? q.options : []).map(texteDeLOption);
                const remplies = options.filter((o) => o.trim() !== '');
                if (remplies.length < 2) ajoute('options', 'Ajoutez au moins deux propositions.');
                else if (remplies.length < options.length) ajoute('options', 'Une proposition est vide : remplissez-la ou retirez-la.');
                const vues = remplies.map((o) => o.trim().toLowerCase());
                if (new Set(vues).size < vues.length) ajoute('options', 'Deux propositions sont identiques.');
                const i = indexDeLaBonneReponse(q);
                if (i < 0) ajoute('correct_answer', 'Cochez la bonne réponse.');
                else if (vide(options[i])) ajoute('correct_answer', 'La bonne réponse cochée est vide.');
            } else if (mode === 'vrai-faux') {
                const valeurs = choixVraiFaux(q).map((c) => c.value.toLowerCase());
                if (vide(q.correct_answer) || !valeurs.includes(String(q.correct_answer).trim().toLowerCase())) {
                    ajoute('correct_answer', 'Choisissez la bonne réponse.');
                }
            } else if (vide(q.correct_answer) && !(componentKey === 'listen-repeat' && !vide(q.audio_text))) {
                ajoute('correct_answer', 'Écrivez la bonne réponse.');
            }
            break;
        }
        case 'multi-field': {
            const paires = Object.entries(q.correct_answers && typeof q.correct_answers === 'object' ? q.correct_answers : {});
            if (paires.length === 0) ajoute('correct_answers', 'Ajoutez au moins un trou et sa réponse.');
            else if (paires.some(([cle, valeur]) => vide(cle) || vide(valeur))) ajoute('correct_answers', 'Chaque trou doit avoir un repère et une réponse.');
            break;
        }
        case 'order': {
            const elements = (Array.isArray(q.items) ? q.items : []).map(texteDeLOption);
            if (elements.filter((e) => e.trim() !== '').length < 2) ajoute('items', 'Ajoutez au moins deux éléments à remettre dans l’ordre.');
            else if (elements.some((e) => e.trim() === '')) ajoute('items', 'Un élément est vide : remplissez-le ou retirez-le.');
            break;
        }
        case 'ai-writing': {
            if (vide(q.text) && vide(q.writing_prompt)) ajoute('text', 'Écrivez la consigne de rédaction.');
            const min = Number(q.min_words ?? 0);
            const max = Number(q.max_words ?? 0);
            if (min > 0 && max > 0 && min > max) ajoute('words', 'Le minimum de mots dépasse le maximum.');
            break;
        }
        case 'ai-speaking': {
            if (vide(q.text) && vide(q.prompt)) ajoute('text', 'Écrivez la consigne orale.');
            const points = Array.isArray(q.expected_points) ? q.expected_points : [];
            if (points.filter((p) => !vide(p)).length === 0) ajoute('expected_points', 'Indiquez au moins un point attendu.');
            break;
        }
        default:
            if (vide(q.text)) ajoute('text', 'Écrivez l’énoncé de la question.');
    }

    return problemes;
}

/**
 * Ce qui manque a l'exercice entier : au moins une question, et un son pour
 * une ecoute. Sans enregistrement ni texte a lire par la voix de synthese, le
 * lecteur ecarte les questions d'ecoute : l'eleve ne recevrait rien.
 */
export function problemesDeLExercice({ skillType, content, questions }) {
    const problemes = [];
    const liste = Array.isArray(questions) ? questions : [];
    if (liste.length === 0) problemes.push('Ajoutez au moins une question.');
    if (skillType === 'listening' && liste.length > 0) {
        const c = content ?? {};
        const sonCommun = !vide(c.audio_url) || !vide(c.audio_text);
        const sansSon = liste.filter((q) => !sonCommun && vide(q?.audio_url) && vide(q?.audio_text));
        if (sansSon.length > 0) problemes.push('Ajoutez un enregistrement, ou le texte que la voix de synthèse lira.');
    }
    return problemes;
}

/** Les numeros (a partir de 0) des questions qui ont encore un probleme. */
export function questionsACompleter(questions, contexte) {
    const indices = [];
    (Array.isArray(questions) ? questions : []).forEach((q, i) => {
        if (problemesDeLaQuestion(q, contexte).length > 0) indices.push(i);
    });
    return indices;
}

export function pluriel(n, singulier, pluriel_) {
    return `${n} ${n > 1 ? pluriel_ : singulier}`;
}

/** « 2 questions à compléter », ou une chaine vide quand tout est pret. */
export function resumeACompleter(n) {
    if (!n) return '';
    return n > 1 ? `${n} questions à compléter` : '1 question à compléter';
}

// ───────────────────────────── liste de questions ─────────────────────────────

/** Deplace la question i d'un cran (sens = -1 vers le haut, +1 vers le bas). */
export function deplacer(liste, i, sens) {
    const j = i + sens;
    if (i < 0 || i >= liste.length || j < 0 || j >= liste.length) return liste;
    const copie = [...liste];
    [copie[i], copie[j]] = [copie[j], copie[i]];
    return copie;
}

/** Une copie de la question i, inseree juste apres elle, sans son identifiant. */
export function dupliquer(liste, i) {
    if (i < 0 || i >= liste.length) return liste;
    const copie = JSON.parse(JSON.stringify(liste[i] ?? {}));
    copie.id = '';
    return [...liste.slice(0, i + 1), copie, ...liste.slice(i + 1)];
}

export function retirer(liste, i) {
    return liste.filter((_, k) => k !== i);
}

/**
 * Pret a envoyer : chaque question recoit le son et l'image communs quand
 * elle n'a pas les siens. Le lecteur lit `question.audio_url`, jamais celui
 * de l'exercice : sans cela, un enregistrement televerse ne se jouait pas.
 */
export function questionsAEnvoyer(questions, content) {
    const c = content ?? {};
    return (Array.isArray(questions) ? questions : []).map((q) => {
        const sortie = { ...q };
        if (!vide(c.audio_url) && vide(sortie.audio_url)) sortie.audio_url = c.audio_url;
        if (!vide(c.image_url) && vide(sortie.image_url)) sortie.image_url = c.image_url;
        if (!vide(c.audio_text) && vide(sortie.audio_text)) sortie.audio_text = c.audio_text;
        return sortie;
    });
}

// ───────────────────────────── stockage ─────────────────────────────

/** Le stockage du navigateur, ou null (navigation privee, rendu serveur, refus). */
export function stockageDuNavigateur(nom = 'localStorage') {
    try {
        if (typeof window === 'undefined') return null;
        const s = window[nom];
        return s ?? null;
    } catch {
        return null;
    }
}

export const cleDuBrouillon = (compte) => `prepla:createur-exercice:${compte ?? 'invite'}`;
export const cleDuDevoirEnCours = (compte) => `prepla:devoir-en-cours:${compte ?? 'invite'}`;

/** Lit un objet range sous cette cle ; null s'il manque, est illisible ou d'une autre version. */
export function lireObjet(stockage, cle) {
    try {
        const brut = stockage?.getItem(cle);
        if (!brut) return null;
        const objet = JSON.parse(brut);
        return objet && typeof objet === 'object' && objet.version === 1 ? objet : null;
    } catch {
        return null;
    }
}

export function ecrireObjet(stockage, cle, donnees) {
    try {
        if (!stockage) return false;
        stockage.setItem(cle, JSON.stringify({ ...donnees, version: 1, le: Date.now() }));
        return true;
    } catch {
        return false;
    }
}

export function effacerObjet(stockage, cle) {
    try {
        stockage?.removeItem(cle);
    } catch {
        // rien a faire : le stockage est indisponible
    }
}

/** Un brouillon vaut d'etre restaure s'il porte un type choisi et du contenu ecrit. */
export function brouillonUtile(brouillon) {
    if (!brouillon || !brouillon.typeId) return false;
    const questions = Array.isArray(brouillon.questions) ? brouillon.questions : [];
    const ecrit = (v) => typeof v === 'string' && v.trim() !== '';
    const contenu = brouillon.content && typeof brouillon.content === 'object' ? brouillon.content : {};
    return questions.some((q) => ecrit(q?.text) || (Array.isArray(q?.options) && q.options.some(ecrit)))
        || ecrit(contenu.passage) || ecrit(contenu.title);
}

// ───────────────────────────── liste « Contenu » ─────────────────────────────

/** Minuscules, sans accents : « Écoute » se trouve en tapant « ecoute ». */
export function normaliser(texte) {
    return String(texte ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim();
}

/** Le nom lisible d'un exercice : son titre ou son extrait, a defaut son type. */
export function libelleExercice(ligne) {
    const titre = String(ligne?.titre ?? '').trim();
    if (titre !== '') return titre;
    return libelleDuType({ slug: ligne?.type_slug, component_key: ligne?.component_key, name: ligne?.type });
}

/**
 * Filtre une liste d'exercices : examen, niveau, competence, origine
 * (« centre » / « prepla ») et recherche libre (chaque mot dans le titre, le
 * type ou l'examen). Un filtre vide ne filtre rien.
 */
export function filtrerExercices(lignes, filtres = {}) {
    const { examen = '', niveau = '', competence = '', source = '', recherche = '' } = filtres;
    const mots = normaliser(recherche).split(/\s+/).filter(Boolean);
    return (Array.isArray(lignes) ? lignes : []).filter((l) => {
        if (examen !== '' && String(l.exam_id) !== String(examen)) return false;
        if (niveau !== '' && l.difficulty !== niveau) return false;
        if (competence !== '' && competenceDe(l.skill_type).cle !== competence) return false;
        if (source !== '' && (l.source ?? 'centre') !== source) return false;
        if (mots.length === 0) return true;
        const texte = normaliser([
            libelleExercice(l),
            libelleDuType({ slug: l.type_slug, component_key: l.component_key, name: l.type }),
            l.exam ?? '',
            l.difficulty ?? '',
        ].join(' '));
        return mots.every((m) => texte.includes(m));
    });
}

/** Les niveaux et competences presents dans une liste, pour ne proposer que des filtres utiles. */
export function optionsDeFiltre(lignes) {
    const liste = Array.isArray(lignes) ? lignes : [];
    const niveaux = NIVEAUX.filter((n) => liste.some((l) => l.difficulty === n));
    const cles = new Set(liste.map((l) => competenceDe(l.skill_type).cle));
    const competences = [...COMPETENCES, AUTRE].filter((c) => cles.has(c.cle));
    return { niveaux, competences };
}

// ───────────────────────────── propositions ─────────────────────────────

/**
 * Retire la proposition i d'une question a choix, et garde la bonne reponse
 * sur la meme proposition : retirer B quand C etait cochee doit cocher la
 * nouvelle B (l'ancienne C), pas laisser une lettre qui designe autre chose.
 * Retirer la bonne reponse elle-meme la decoche.
 */
export function retirerOption(question, i) {
    const options = Array.isArray(question?.options) ? [...question.options] : [];
    if (i < 0 || i >= options.length) return question;
    const bonne = indexDeLaBonneReponse(question);
    options.splice(i, 1);
    let correct = '';
    if (bonne >= 0 && bonne !== i) correct = lettre(bonne > i ? bonne - 1 : bonne);
    return { ...question, options, correct_answer: correct };
}

/** La bonne reponse au format du lecteur : la lettre de la proposition. */
export function cocherOption(question, i) {
    return { ...question, correct_answer: lettre(i) };
}

/**
 * Le nombre de questions a demander a l'IA, ou null pour « comme a
 * l'examen ». Une saisie vide, nulle ou absurde ne demande rien.
 */
export function nombreDeQuestions(valeur) {
    const n = Number.parseInt(String(valeur ?? ''), 10);
    if (!Number.isFinite(n) || n < 1) return null;
    return Math.min(n, 20);
}

/**
 * Le niveau CECRL d'une classe (« B1 », « B1 soir » donnent B1), ou null :
 * c'est lui qui pre-remplit le niveau quand on vise une classe.
 */
export function niveauDeLaClasse(niveau) {
    const m = String(niveau ?? '').trim().toUpperCase().match(/^(A1|A2|B1|B2|C1|C2)/);
    return m ? m[1] : null;
}
