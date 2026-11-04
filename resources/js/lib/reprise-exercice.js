/**
 * Reprise d'un exercice interrompu (page fermee, reseau coupe, telephone eteint).
 *
 * Le lecteur gardait la question courante et les reponses en memoire seulement :
 * un rechargement repartait de zero. On range donc, dans le navigateur, l'index
 * courant et les reponses deja donnees, rien d'autre — jamais l'enonce, ni les
 * bonnes reponses, ni les explications (l'essai sans compte les cache
 * volontairement avant la reponse).
 *
 * Seules des donnees JSON legeres sont gardees : un enregistrement audio (Blob,
 * File) n'est pas sauvegarde, et la question qui le portait sera simplement
 * reposee.
 *
 * Le stockage peut manquer ou refuser d'ecrire (navigation privee, quota plein) :
 * chaque acces est protege, et sans stockage le lecteur marche comme avant.
 *
 * JavaScript simple, comme scoring.js : TypeScript l'importe via
 * reprise-exercice.d.ts et `node --test` le teste sans compilation.
 */

const VERSION = 1;

/** Au-dela, l'eleve a sans doute oublie l'exercice : on repart de zero. */
export const DUREE_DE_VIE_MS = 7 * 24 * 60 * 60 * 1000;

// Une redaction depasse rarement quelques milliers de caracteres : au-dela, ce
// n'est plus une reponse tapee, on ne la range pas.
const LONGUEUR_MAX = 50000;

/**
 * Formats dont la reponse vient d'un enregistrement ou d'un dialogue joue au
 * micro : l'etat interne du composant ne se reconstruit pas depuis la seule
 * reponse. On ne les reprend pas plutot que d'afficher un ecran incoherent.
 */
export const FORMATS_SANS_REPRISE = ['speaking-recorder', 'role-play', 'listen-repeat'];

/**
 * Une cle par compte ET par exercice : sur l'ordinateur partage d'un centre, un
 * eleve ne doit pas retrouver les reponses du precedent.
 */
export function cleDeReprise(exerciseId, compte) {
    return `prepla-reprise-${compte ?? 'essai'}-${exerciseId}`;
}

/** Le localStorage du navigateur, ou null s'il est inaccessible. */
export function stockageDuNavigateur() {
    try {
        if (typeof window === 'undefined' || !window.localStorage) return null;
        return window.localStorage;
    } catch {
        return null;
    }
}

function estObjetSimple(v) {
    if (v === null || typeof v !== 'object') return false;
    const proto = Object.getPrototypeOf(v);
    return proto === Object.prototype || proto === null;
}

function estLegere(v, profondeur = 0) {
    if (typeof v === 'string') return v.length <= LONGUEUR_MAX;
    if (typeof v === 'boolean') return true;
    if (typeof v === 'number') return Number.isFinite(v);
    if (profondeur >= 2) return false;
    if (Array.isArray(v)) return v.every((x) => estLegere(x, profondeur + 1));
    if (estObjetSimple(v)) return Object.values(v).every((x) => estLegere(x, profondeur + 1));
    // Blob, File, Date, null... : rien de tout cela ne se range proprement.
    return false;
}

/** Les reponses qu'on peut ranger telles quelles, sans les enregistrements. */
export function reponsesLegeres(answers) {
    const gardees = {};
    for (const [id, valeur] of Object.entries(answers ?? {})) {
        if (estLegere(valeur)) gardees[id] = valeur;
    }
    return gardees;
}

/** Efface la sauvegarde ; ne leve jamais. */
export function effacerReprise(stockage, cle) {
    if (!stockage) return;
    try {
        stockage.removeItem(cle);
    } catch {
        /* rien a faire : la sauvegarde restera, et sera ignoree si elle ne colle plus */
    }
}

/**
 * Range l'etat courant. Ne fait rien tant qu'aucune reponse legere n'est donnee :
 * il n'y a alors rien a reprendre. Renvoie true si l'ecriture a reussi.
 */
export function ecrireReprise(stockage, cle, { index, answers, questionIds, tempsPasse }, maintenant = Date.now()) {
    if (!stockage) return false;
    const reponses = reponsesLegeres(answers);
    if (Object.keys(reponses).length === 0) return false;
    try {
        stockage.setItem(cle, JSON.stringify({
            v: VERSION,
            enregistre_le: maintenant,
            questions: questionIds.map(String),
            index,
            reponses,
            temps: Number.isFinite(tempsPasse) && tempsPasse > 0 ? Math.round(tempsPasse) : 0,
        }));
        return true;
    } catch {
        // Quota plein ou stockage refuse : l'exercice continue, sans reprise.
        return false;
    }
}

/**
 * Relit la sauvegarde si elle vaut encore pour cet exercice. Une sauvegarde
 * perimee, illisible ou faite pour d'autres questions est effacee.
 *
 * L'index rendu ne depasse jamais la premiere question restee sans reponse : un
 * enregistrement non sauvegarde est repose au lieu d'etre saute.
 */
export function lireReprise(stockage, cle, questionIds, maintenant = Date.now()) {
    if (!stockage) return null;
    let brut;
    try {
        brut = stockage.getItem(cle);
    } catch {
        return null;
    }
    if (!brut) return null;

    const ids = questionIds.map(String);
    let donnees;
    try {
        donnees = JSON.parse(brut);
    } catch {
        effacerReprise(stockage, cle);
        return null;
    }

    const valide = estObjetSimple(donnees)
        && donnees.v === VERSION
        && typeof donnees.enregistre_le === 'number'
        && maintenant - donnees.enregistre_le >= 0
        && maintenant - donnees.enregistre_le < DUREE_DE_VIE_MS
        && Array.isArray(donnees.questions)
        && donnees.questions.length === ids.length
        && donnees.questions.every((id, i) => id === ids[i])
        && Number.isInteger(donnees.index)
        && estObjetSimple(donnees.reponses);
    if (!valide) {
        effacerReprise(stockage, cle);
        return null;
    }

    // Seules les reponses aux questions de l'exercice, et encore legeres.
    const reponses = {};
    for (const id of ids) {
        if (Object.prototype.hasOwnProperty.call(donnees.reponses, id) && estLegere(donnees.reponses[id])) {
            reponses[id] = donnees.reponses[id];
        }
    }
    if (Object.keys(reponses).length === 0) {
        effacerReprise(stockage, cle);
        return null;
    }

    let index = Math.min(Math.max(donnees.index, 0), ids.length - 1);
    const premiereSansReponse = ids.findIndex((id) => !(id in reponses));
    if (premiereSansReponse !== -1 && premiereSansReponse < index) index = premiereSansReponse;

    const temps = Number.isFinite(donnees.temps) && donnees.temps > 0 ? Math.round(donnees.temps) : 0;

    return { index, answers: reponses, tempsPasse: temps };
}
