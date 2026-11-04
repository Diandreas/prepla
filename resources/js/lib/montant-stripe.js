// Montants Stripe : ils arrivent dans la plus petite unite de la devise. Les
// centimes pour l'euro, mais le franc CFA n'a pas de centimes : 5000 XAF vaut
// 5000 francs, pas 50. Diviser par 100 partout aurait affiche des commissions
// cent fois trop petites aux centres payes en francs.

// Devises sans decimales chez Stripe (liste de la documentation Stripe).
const SANS_DECIMALES = new Set([
    'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
]);

/** Le montant en unites de la devise (2500 centimes d'euro -> 25). */
export function enUnites(montant, devise) {
    const d = String(devise || '').toUpperCase();
    return SANS_DECIMALES.has(d) ? montant : montant / 100;
}

/** Le montant pret a afficher, a la francaise : « 25,00 € », « 5 000 FCFA ». */
export function formaterMontant(montant, devise) {
    const d = String(devise || 'EUR').toUpperCase();
    const unites = enUnites(Number(montant) || 0, d);
    const decimales = SANS_DECIMALES.has(d) ? 0 : 2;
    const nombre = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: decimales, maximumFractionDigits: decimales })
        .format(unites)
        // Intl met une espace fine insecable : on garde une espace insecable simple, partout lisible.
        .replace(/\u202f/g, '\u00a0');
    const symbole = d === 'EUR' ? '€' : d === 'XAF' || d === 'XOF' ? 'FCFA' : d;
    return `${nombre}\u00a0${symbole}`;
}
