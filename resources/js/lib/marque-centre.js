/**
 * La marque d'un centre cote client : contraste des couleurs (apercu en direct
 * de la page Personnalisation) et variables CSS posees sur la page.
 *
 * Le serveur decide (app/Services/Center/Couleurs.php, meme formule WCAG 2.1) ;
 * ce fichier sert a montrer le resultat avant d'enregistrer, et a appliquer les
 * couleurs d'une page a l'autre. Sans import : teste directement par node.
 */

/** Contraste minimal d'une couleur de marque sur fond blanc. */
export const CONTRASTE_MINIMAL = 3;

/** Fond du theme sombre (app.css, .dark --background). */
export const FOND_SOMBRE = '#101B2D';

const BLANC = '#FFFFFF';
const TEXTE_FONCE = '#0B1322';

export function estHexa(couleur) {
    return typeof couleur === 'string' && /^#[0-9a-fA-F]{6}$/.test(couleur);
}

function rgb(hexa) {
    const h = hexa.replace('#', '');
    return [0, 2, 4].map((i) => parseInt(h.slice(i, i + 2), 16));
}

function hexa(composantes) {
    return '#' + composantes
        .map((c) => Math.round(Math.max(0, Math.min(255, c))).toString(16).padStart(2, '0'))
        .join('')
        .toUpperCase();
}

export function luminance(couleur) {
    const [r, g, b] = rgb(couleur).map((c) => {
        const s = c / 255;
        return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

/** Rapport de contraste entre deux couleurs, de 1 a 21. */
export function contraste(a, b) {
    const la = luminance(a);
    const lb = luminance(b);
    return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
}

/** Le texte (blanc ou fonce) le plus lisible pose sur cette couleur. */
export function texteSur(fond) {
    return contraste(fond, BLANC) >= contraste(fond, TEXTE_FONCE) ? BLANC : TEXTE_FONCE;
}

/** La couleur assez lisible sur fond blanc pour etre acceptee ? */
export function couleurLisible(couleur) {
    return estHexa(couleur) && contraste(couleur, BLANC) >= CONTRASTE_MINIMAL;
}

/** La couleur, eclaircie juste assez pour rester visible sur le fond sombre. */
export function pourFondSombre(couleur) {
    const base = rgb(couleur);
    let resultat = hexa(base);
    for (let part = 0; part <= 1 && contraste(resultat, FOND_SOMBRE) < CONTRASTE_MINIMAL; part += 0.05) {
        resultat = hexa(base.map((c) => c + (255 - c) * part));
    }
    return resultat;
}

/**
 * Les variables CSS de la marque, ou '' pour la marque PrePla (app.css reste
 * tel quel). Meme contenu que resources/views/partials/marque-centre.blade.php.
 */
export function cssDeLaMarque(marque) {
    const t = marque && marque.theme;
    if (!t) return '';
    const cles = ['primaire', 'texte_sur_primaire', 'primaire_sombre', 'texte_sur_primaire_sombre', 'accent', 'accent_sombre'];
    if (!cles.every((c) => estHexa(t[c]))) return '';

    const bloc = (primaire, texte, accent) =>
        `--primary:${primaire};--primary-foreground:${texte};--ring:${primaire};--sidebar-primary:${primaire};` +
        `--sidebar-primary-foreground:${texte};--sidebar-ring:${primaire};--marque-accent:${accent}`;

    return `html:root{${bloc(t.primaire, t.texte_sur_primaire, t.accent)}}\n` +
        `html.dark{${bloc(t.primaire_sombre, t.texte_sur_primaire_sombre, t.accent_sombre)}}`;
}

/** Le nom du site dans le titre : celui du centre s'il masque PrePla. */
export function nomDuSite(marque, nomParDefaut) {
    return marque && marque.masquer_prepla && marque.nom ? marque.nom : nomParDefaut;
}
