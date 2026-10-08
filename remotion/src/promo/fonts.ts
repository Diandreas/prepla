import { loadFont } from '@remotion/fonts';
import { staticFile } from 'remotion';

const pending: Promise<void>[] = [];

// Polices de la marque, servies localement (pas de dépendance réseau au rendu).
// loadFont bloque le rendu tant que chaque fichier n'est pas chargé.
const JAKARTA_WEIGHTS = ['400', '500', '600', '700', '800'];
const CORMORANT_ITALIC_WEIGHTS = ['500', '600', '700'];

for (const weight of JAKARTA_WEIGHTS) {
    pending.push(loadFont({
        family: 'Plus Jakarta Sans',
        url: staticFile(`promo/fonts/plus-jakarta-sans-latin-${weight}-normal.woff2`),
        weight,
        style: 'normal',
    }));
}

for (const weight of CORMORANT_ITALIC_WEIGHTS) {
    pending.push(loadFont({
        family: 'Cormorant Garamond',
        url: staticFile(`promo/fonts/cormorant-garamond-latin-${weight}-italic.woff2`),
        weight,
        style: 'italic',
    }));
}

pending.push(loadFont({
    family: 'Cormorant Garamond',
    url: staticFile('promo/fonts/cormorant-garamond-latin-600-normal.woff2'),
    weight: '600',
    style: 'normal',
}));

/** Résolue quand toutes les polices sont prêtes (pour mesurer du texte de façon fiable). */
export const fontsReady = Promise.all(pending).then(() => undefined);
