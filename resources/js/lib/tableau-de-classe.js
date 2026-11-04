// Mise en forme du tableau de bord d'une classe (center/classes/show).
// Fonctions pures, testees dans tests/js/tableau-de-classe.test.mjs.

/** « Awa Diallo » -> « AD », « paul » -> « P », vide -> « ? ». */
export function initiales(nom) {
    const mots = String(nom || '').trim().split(/\s+/).filter(Boolean);
    if (mots.length === 0) return '?';
    const lettres = mots.length === 1 ? [mots[0][0]] : [mots[0][0], mots[mots.length - 1][0]];
    return lettres.join('').toUpperCase();
}

/** Le prenom : le premier mot du nom. */
export function prenom(nom) {
    return String(nom || '').trim().split(/\s+/)[0] || '—';
}

/**
 * La variation d'un volume entre deux periodes, en pourcentage :
 * { texte: '+25 %', sens: 'hausse' | 'baisse' | 'stable' | 'nouveau' } ; null sans comparaison possible.
 */
export function variation(actuel, avant) {
    const a = Number(actuel) || 0;
    const b = Number(avant) || 0;
    if (b === 0) return a === 0 ? null : { texte: 'nouveau', sens: 'nouveau' };
    const pct = Math.round(((a - b) / b) * 100);
    if (pct === 0) return { texte: '=', sens: 'stable' };
    return { texte: `${pct > 0 ? '+' : '−'}${Math.abs(pct)} %`, sens: pct > 0 ? 'hausse' : 'baisse' };
}

/** Un ecart de precision, en points : 4.2 -> « +4 pts », -1 -> « −1 pt », null -> null. */
export function ecartPoints(ecart) {
    if (ecart === null || ecart === undefined || Number.isNaN(Number(ecart))) return null;
    const n = Math.round(Number(ecart));
    if (n === 0) return { texte: '=', sens: 'stable' };
    const abs = Math.abs(n);
    return { texte: `${n > 0 ? '+' : '−'}${abs} pt${abs > 1 ? 's' : ''}`, sens: n > 0 ? 'hausse' : 'baisse' };
}

/**
 * Les points d'une courbe SVG : valeurs (null = trou) sur une largeur et une
 * hauteur, avec un maximum donne (sinon le plus grand). Renvoie
 * [{ x, y, valeur }] ; y vaut null pour une valeur absente.
 */
export function pointsCourbe(valeurs, largeur, hauteur, maximum) {
    const n = valeurs.length;
    const max = maximum ?? Math.max(1, ...valeurs.filter((v) => v !== null && v !== undefined));
    return valeurs.map((v, i) => ({
        x: n <= 1 ? largeur / 2 : (i / (n - 1)) * largeur,
        y: v === null || v === undefined ? null : hauteur - (Math.min(Number(v), max) / (max || 1)) * hauteur,
        valeur: v,
    }));
}

/** Le chemin « M x y L x y » d'une courbe, en sautant les trous. */
export function cheminCourbe(points) {
    let d = '';
    let leve = true;
    for (const p of points) {
        if (p.y === null) {
            leve = true;
            continue;
        }
        d += `${leve ? 'M' : 'L'}${p.x.toFixed(1)} ${p.y.toFixed(1)} `;
        leve = false;
    }
    return d.trim();
}

const FORMAT_JOUR = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short' });

/** Le libelle d'une semaine glissante : « 29 oct. ». La derniere s'appelle « Cette sem. ». */
export function libelleSemaine(debutIso, derniere) {
    if (derniere) return 'Cette sem.';
    const d = new Date(debutIso);
    return Number.isNaN(d.getTime()) ? '' : FORMAT_JOUR.format(d);
}
