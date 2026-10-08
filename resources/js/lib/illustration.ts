/**
 * L'illustration d'un exercice, fabriquée à partir d'une description de scène.
 *
 * La bibliothèque d'images préparées ne couvre que les graphiques IELTS. Sans ce
 * repli, toute autre épreuve visuelle — la Bildbesprechung de l'ÖSD B2, un schéma à
 * étiqueter en allemand — affichait un cadre vide sous une consigne qui demandait de
 * décrire l'image.
 *
 * Le procédé était déjà utilisé par les QCM imagés ; il est mis en commun ici pour
 * que tous les composants visuels s'illustrent de la même façon.
 */
export function illustrationUrl(description: string, taille: { largeur?: number; hauteur?: number } = {}): string {
    const propre = description.replace(/\s+/g, ' ').trim();
    const largeur = taille.largeur ?? 640;
    const hauteur = taille.hauteur ?? 420;

    return `https://image.pollinations.ai/prompt/${encodeURIComponent(
        propre + ', simple clear illustration, white background, no text',
    )}?width=${largeur}&height=${hauteur}&nologo=true`;
}
