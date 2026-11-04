/**
 * La liste des devoirs d'un centre : lire une echeance (« dans 3 jours »,
 * « en retard »), trier, mesurer l'avancement d'une classe.
 *
 * Le serveur envoie le jour de remise (« 2026-11-07 ») et l'ecart en jours
 * calendaires, deja calcules a son fuseau : une echeance saisie « a minuit »
 * ne glisse pas d'un jour dans le navigateur. Sans import : teste directement
 * par node (tests/js/devoirs-centre.test.mjs).
 */

export const STATUTS_DEVOIR = {
    brouillon: 'Brouillon',
    publie: 'Publié',
    termine: 'Terminé',
};

const MOIS = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

/** « 2026-11-07 » devient « 7 nov. » ; l'annee seulement si elle differe. */
export function jourCourt(date, anneeCourante = new Date().getFullYear()) {
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(date ?? ''));
    if (!m) return '';
    const [annee, mois, jour] = [Number(m[1]), Number(m[2]), Number(m[3])];
    const base = `${jour} ${MOIS[mois - 1] ?? ''}`.trim();
    return annee === anneeCourante ? base : `${base} ${annee}`;
}

/**
 * L'echeance d'un devoir, lisible d'un coup d'oeil.
 * ton : « aucune » (sans date), « loin », « proche » (3 jours ou moins),
 * « jour » (aujourd'hui), « retard » (echu, des eleves n'ont pas fini),
 * « passee » (echu, rien a rattraper).
 */
export function echeance(devoir, anneeCourante) {
    const { due_date: date, days_left: jours, overdue, en_retard: retard } = devoir;
    if (!date || jours === null || jours === undefined) return { texte: 'Sans échéance', detail: '', ton: 'aucune' };
    const detail = jourCourt(date, anneeCourante);

    if (overdue) {
        const depuis = Math.abs(jours);
        const ecart = depuis === 0 ? "aujourd'hui" : depuis === 1 ? 'hier' : `il y a ${depuis} jours`;
        return retard
            ? { texte: 'En retard', detail: `échu ${ecart}`, ton: 'retard' }
            : { texte: depuis <= 1 ? `Échu ${ecart}` : `Échu le ${detail}`, detail: depuis <= 1 ? detail : '', ton: 'passee' };
    }
    if (jours <= 0) return { texte: "Aujourd'hui", detail, ton: 'jour' };
    if (jours === 1) return { texte: 'Demain', detail, ton: 'proche' };
    return { texte: `Dans ${jours} jours`, detail, ton: jours <= 3 ? 'proche' : 'loin' };
}

/** La part de la classe qui a fini, en pourcentage entier ; null sans eleve. */
export function tauxFini(progress) {
    if (!progress || !progress.students) return null;
    return Math.round((100 * progress.finished) / progress.students);
}

/**
 * Trie une copie de la liste.
 * « echeance » : ce qui est a rendre d'abord, le plus proche en tete, puis
 * sans date, puis les brouillons, puis les devoirs termines (les plus recents
 * d'abord). « recent » : les derniers crees. « progression » : les classes les
 * moins avancees d'abord.
 */
export function trierDevoirs(liste, mode = 'echeance') {
    const copie = [...liste];
    const cree = (d) => String(d.created_at ?? '');
    if (mode === 'recent') return copie.sort((a, b) => cree(b).localeCompare(cree(a)));
    if (mode === 'progression') {
        return copie.sort((a, b) => (tauxFini(a.progress) ?? 101) - (tauxFini(b.progress) ?? 101) || cree(b).localeCompare(cree(a)));
    }

    const rang = (d) => {
        if (d.statut === 'termine') return 3;
        if (d.statut === 'brouillon') return 2;
        return d.due_date ? 0 : 1;
    };
    return copie.sort((a, b) => {
        const r = rang(a) - rang(b);
        if (r !== 0) return r;
        if (rang(a) === 0) return String(a.due_date).localeCompare(String(b.due_date));
        if (rang(a) === 3) return String(b.due_date ?? '').localeCompare(String(a.due_date ?? '')) || cree(b).localeCompare(cree(a));
        return cree(b).localeCompare(cree(a));
    });
}

/** Les chiffres du haut de page. */
export function syntheseDevoirs(liste) {
    const publies = liste.filter((d) => d.statut === 'publie');
    const eleves = liste.filter((d) => d.statut !== 'brouillon').reduce((s, d) => s + (d.progress?.students ?? 0), 0);
    const finis = liste.filter((d) => d.statut !== 'brouillon').reduce((s, d) => s + (d.progress?.finished ?? 0), 0);
    return {
        total: liste.length,
        enCours: publies.length,
        cetteSemaine: publies.filter((d) => d.days_left !== null && d.days_left !== undefined && d.days_left >= 0 && d.days_left <= 7).length,
        enRetard: liste.filter((d) => d.en_retard).length,
        tauxRendu: eleves ? Math.round((100 * finis) / eleves) : null,
    };
}
