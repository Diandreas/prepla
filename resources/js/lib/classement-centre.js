/**
 * Ranger les classes et les eleves d'un centre : recherche, filtres,
 * regroupements, export CSV et etat garde dans l'adresse.
 *
 * Le serveur envoie la liste complete deja bornee a ce que l'acteur a le droit
 * de voir (app/Services/Center/ClassementCentre.php) ; tout le classement se
 * fait ici, sans recharger la page. Sans import : teste directement par node
 * (tests/js/classement-centre.test.mjs).
 *
 * Une « etiquette » est { cle, libelle } : la cle sert aux filtres et a
 * l'adresse, le libelle a l'affichage. Un critere renvoie toujours une LISTE
 * d'etiquettes, parce qu'un eleve peut suivre deux classes, dans deux salles.
 */

/** La valeur « aucune » d'un critere. Meme cle que ClassementCentre::SANS cote serveur. */
export const SANS = '-';

/** L'ordre du CECRL : A1 avant A2, jamais l'ordre alphabetique seul. */
export const ORDRE_NIVEAUX = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

export const STATUTS = {
    actif: 'Actif',
    a_relancer: 'À relancer',
    jamais: 'Jamais venu',
};

/** Minuscules, sans accents : « Élodie » se trouve en tapant « elodie ». */
export function normaliser(texte) {
    return String(texte ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim();
}

/** Chaque mot tape doit se retrouver dans l'un des champs. */
export function correspondRecherche(recherche, champs) {
    const mots = normaliser(recherche).split(/\s+/).filter(Boolean);
    if (mots.length === 0) return true;
    const texte = champs.map(normaliser).join(' ');
    return mots.every((mot) => texte.includes(mot));
}

/** Les etiquettes d'une ligne pour un critere ; aucune devient « sans ». */
export function etiquettesDe(ligne, critere, libelleSans = 'Aucun') {
    const etiquettes = (critere(ligne) ?? []).filter((e) => e && e.cle !== undefined && e.cle !== null && e.cle !== '');
    return etiquettes.length ? etiquettes : [{ cle: SANS, libelle: libelleSans }];
}

/**
 * Ordre des groupes et des options : niveaux A1..C2 puis niveaux libres,
 * sinon ordre alphabetique francais ; « sans » toujours a la fin.
 */
export function comparerCles(a, b, mode = 'alpha') {
    if ((a.cle === SANS) !== (b.cle === SANS)) return a.cle === SANS ? 1 : -1;
    if (mode === 'niveau') {
        const ia = ORDRE_NIVEAUX.indexOf(String(a.cle).toUpperCase());
        const ib = ORDRE_NIVEAUX.indexOf(String(b.cle).toUpperCase());
        if (ia !== ib) {
            if (ia === -1) return 1;
            if (ib === -1) return -1;
            return ia - ib;
        }
    }
    return String(a.libelle).localeCompare(String(b.libelle), 'fr', { sensitivity: 'base', numeric: true });
}

/**
 * Garde les lignes qui passent la recherche et chaque filtre pose.
 *
 * @param criteres { nom: { etiquettes: ligne => etiquette[] } }
 * @param filtres { nom: cle } ; une cle vide ne filtre pas
 * @param champsRecherche ligne => string[]
 */
export function filtrer(lignes, { recherche = '', champsRecherche = () => [], criteres = {}, filtres = {} } = {}) {
    const poses = Object.entries(filtres).filter(([nom, cle]) => cle && criteres[nom]);
    return lignes.filter((ligne) => {
        if (!correspondRecherche(recherche, champsRecherche(ligne))) return false;
        return poses.every(([nom, cle]) => etiquettesDe(ligne, criteres[nom].etiquettes).some((e) => String(e.cle) === String(cle)));
    });
}

/**
 * Les valeurs presentes d'un critere, pour remplir une liste de filtre :
 * construites a partir des donnees reelles, avec leur effectif.
 */
export function optionsDe(lignes, critere, { mode = 'alpha', libelleSans = 'Aucun' } = {}) {
    const vues = new Map();
    for (const ligne of lignes) {
        for (const e of etiquettesDe(ligne, critere, libelleSans)) {
            const cle = String(e.cle);
            const option = vues.get(cle) ?? { cle, libelle: e.libelle, nombre: 0 };
            option.nombre += 1;
            vues.set(cle, option);
        }
    }
    return [...vues.values()].sort((a, b) => comparerCles(a, b, mode));
}

/**
 * Un bloc par groupe, dans l'ordre. Une ligne a plusieurs etiquettes (un eleve
 * dans deux classes) apparait dans chacun de ses groupes.
 */
export function regrouper(lignes, critere, { mode = 'alpha', libelleSans = 'Aucun' } = {}) {
    const groupes = new Map();
    for (const ligne of lignes) {
        const vues = new Set();
        for (const e of etiquettesDe(ligne, critere, libelleSans)) {
            const cle = String(e.cle);
            if (vues.has(cle)) continue;
            vues.add(cle);
            const groupe = groupes.get(cle) ?? { cle, libelle: e.libelle, etiquette: e, lignes: [] };
            groupe.lignes.push(ligne);
            groupes.set(cle, groupe);
        }
    }
    return [...groupes.values()].sort((a, b) => comparerCles(a, b, mode));
}

/** « 1 élève », « 3 élèves », « 0 élève ». */
export function pluriel(nombre, singulier, pluriel = `${singulier}s`) {
    return `${nombre} ${Math.abs(nombre) > 1 ? pluriel : singulier}`;
}

/** Compte les lignes par statut. */
export function compterStatuts(lignes) {
    const compte = { total: lignes.length, actif: 0, a_relancer: 0, jamais: 0 };
    for (const ligne of lignes) {
        if (ligne.statut in compte) compte[ligne.statut] += 1;
    }
    return compte;
}

/**
 * Une cellule sure dans un tableur : une cellule qui commence par =, +, - ou @
 * deviendrait une formule (meme garde que app/Support/ExportTableur.php).
 */
export function celluleCsv(valeur) {
    let texte = valeur === null || valeur === undefined ? '' : String(valeur);
    if (/^[=+\-@\t\r]/.test(texte)) texte = `'${texte}`;
    return /[";\r\n]/.test(texte) ? `"${texte.replace(/"/g, '""')}"` : texte;
}

/**
 * Le CSV pour Excel : separateur « ; » (Excel en francais), BOM UTF-8 pour
 * que les accents s'affichent, fins de ligne CRLF.
 */
const BOM = String.fromCharCode(0xfeff);

export function versCsv(entetes, lignes) {
    const corps = [entetes, ...lignes].map((ligne) => ligne.map(celluleCsv).join(';')).join('\r\n');
    return `${BOM}${corps}\r\n`;
}

/**
 * L'etat de la vue (regroupement, filtres, recherche) lu depuis l'adresse :
 * un lien copie rouvre la meme vue. Seuls les parametres connus sont lus.
 */
export function lireEtat(recherche, noms) {
    const params = new URLSearchParams(recherche ?? '');
    const etat = {};
    for (const nom of noms) etat[nom] = params.get(nom) ?? '';
    return etat;
}

/** L'adresse d'une vue : les valeurs vides n'y figurent pas ; les autres parametres sont gardes. */
export function ecrireEtat(rechercheActuelle, etat) {
    const params = new URLSearchParams(rechercheActuelle ?? '');
    for (const [nom, valeur] of Object.entries(etat)) {
        if (valeur) params.set(nom, valeur);
        else params.delete(nom);
    }
    const texte = params.toString();
    return texte ? `?${texte}` : '';
}

/**
 * L'entree d'historique a poser quand la vue change : la meme, avec la
 * nouvelle adresse. On passe par window.history et non par router.replace
 * d'Inertia : router.replace change l'adresse de la page Inertia, et la mise en
 * page reconstruit tout le contenu a chaque changement d'adresse (key={url}) —
 * la recherche perdait le focus a chaque lettre, le tri et le formulaire
 * ouvert etaient perdus. La page gardee par Inertia (si elle est lisible)
 * recoit aussi la nouvelle adresse, pour qu'un retour arriere rouvre la vue.
 */
export function entreeHistorique(etatActuel, adresse) {
    if (!etatActuel || typeof etatActuel !== 'object') return etatActuel ?? null;
    const page = etatActuel.page;
    if (page && typeof page === 'object' && !Array.isArray(page) && 'url' in page) {
        return { ...etatActuel, page: { ...page, url: adresse } };
    }
    return etatActuel;
}

/** Le nombre de personnes dans un ensemble de classes : un eleve de deux classes compte une fois. */
export function elevesDistincts(classes) {
    const ids = new Set();
    for (const c of classes) for (const id of c.student_ids ?? []) ids.add(id);
    return ids.size;
}

/** Jours de calendrier (heure locale) entre une date et maintenant : 0 aujourd'hui, 1 hier. */
export function joursCalendaires(iso, maintenant = new Date()) {
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return null;
    const debut = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
    return Math.round((debut(maintenant) - debut(date)) / 86_400_000);
}

/**
 * La derniere activite affichee a cote du statut, sans le contredire. La date
 * relative compte en jours de calendrier, le statut a l'heure pres : un eleve
 * vu il y a 7 jours et 3 heures est « A relancer » mais s'afficherait « il y a
 * 7 jours ». Il s'affiche alors « il y a plus de 7 jours ».
 *
 * @param formater la date relative ordinaire (ilYA)
 */
export function activiteCoherente(iso, statut, seuil, formater, maintenant = new Date()) {
    if (!iso) return formater(iso);
    const jours = joursCalendaires(iso, maintenant);
    if (statut === 'a_relancer' && jours !== null && jours <= seuil) return `il y a plus de ${seuil} jours`;
    return formater(iso);
}
