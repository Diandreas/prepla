import { ADVANCES } from '../fontMetrics';

export type FontKey = 'jakarta800' | 'cormorant700i';

/**
 * Largeur (px) d'un texte d'après les chasses des polices (crénage ignoré, ±2 %).
 * Calcul synchrone et déterministe : aucune mesure dans le navigateur, donc rien qui puisse
 * retarder ou bloquer le rendu d'une image.
 */
export function textWidth(text: string, font: FontKey, size: number, letterSpacingEm = 0): number {
    const table = ADVANCES[font];
    let em = 0;
    for (const ch of text) em += (table[ch] ?? 0.6) + letterSpacingEm;
    return em * size;
}
