/**
 * Le classement des eleves d'un centre, dans l'esprit de /leaderboard : rang
 * par XP sur une periode, et quelques distinctions. Calcule sur la liste deja
 * filtree (classe, langue, niveau) : le podium suit le filtre choisi.
 * Sans import : teste par node (tests/js/podium-centre.test.mjs).
 */

export const PERIODES = {
    semaine: 'Cette semaine',
    mois: 'Ce mois',
    total: 'Depuis le début',
};

/** Une serie de 7 jours ou plus : « En feu ». */
export const SEUIL_EN_FEU = 7;
/** Au moins 3 jours actifs sur 30 pour etre « Plus régulier ». */
export const SEUIL_REGULIER = 3;
/** Au moins +5 points de precision pour la « Meilleure progression ». */
export const SEUIL_PROGRESSION = 5;

export const DISTINCTIONS = {
    en_feu: 'En feu',
    regulier: 'Plus régulier',
    progression: 'Meilleure progression',
};

const xpDe = (e, periode) => e.periodes?.[periode]?.xp ?? 0;

/**
 * Les rangs par XP sur la periode : { [id]: rang }. Seuls ceux qui ont gagne
 * des XP sont classes ; deux eleves a egalite partagent le rang.
 */
export function rangs(eleves, periode = 'semaine') {
    const classes = eleves
        .filter((e) => xpDe(e, periode) > 0)
        .sort((a, b) => xpDe(b, periode) - xpDe(a, periode) || String(a.name).localeCompare(String(b.name), 'fr'));
    const resultat = {};
    let precedent = null;
    let rang = 0;
    classes.forEach((e, i) => {
        const xp = xpDe(e, periode);
        if (xp !== precedent) rang = i + 1;
        precedent = xp;
        resultat[e.id] = rang;
    });
    return resultat;
}

/** Les trois premiers de la periode, dans l'ordre (moins s'il y a moins de classes). */
export function podium(eleves, periode = 'semaine') {
    const r = rangs(eleves, periode);
    return eleves
        .filter((e) => r[e.id] !== undefined)
        .sort((a, b) => r[a.id] - r[b.id] || String(a.name).localeCompare(String(b.name), 'fr'))
        .slice(0, 3)
        .map((e) => ({ eleve: e, rang: r[e.id], xp: xpDe(e, periode) }));
}

/**
 * Les distinctions de chacun : { [id]: cle[] }. « En feu » pour toute serie de
 * 7 jours ; « Plus régulier » et « Meilleure progression » a un seul eleve
 * (le premier, a egalite le premier par ordre alphabetique), et seulement
 * au-dessus d'un seuil, pour ne pas decorer un eleve qui a peu travaille.
 */
export function distinctions(eleves) {
    const res = {};
    const ajouter = (id, cle) => (res[id] = [...(res[id] ?? []), cle]);
    const tri = (cle) => (a, b) => (b[cle] ?? -Infinity) - (a[cle] ?? -Infinity) || String(a.name).localeCompare(String(b.name), 'fr');

    for (const e of eleves) if ((e.serie ?? 0) >= SEUIL_EN_FEU) ajouter(e.id, 'en_feu');

    const regulier = [...eleves].sort(tri('jours_actifs'))[0];
    if (regulier && (regulier.jours_actifs ?? 0) >= SEUIL_REGULIER) ajouter(regulier.id, 'regulier');

    const progres = [...eleves].filter((e) => e.progression !== null && e.progression !== undefined).sort(tri('progression'))[0];
    if (progres && progres.progression >= SEUIL_PROGRESSION) ajouter(progres.id, 'progression');

    return res;
}
