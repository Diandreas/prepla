/**
 * Lien « Relancer sur WhatsApp » de la fiche eleve (espace centre).
 *
 * Le numero est saisi librement a l'inscription (« 6 93 42 79 13 »,
 * « +237 693... », « 00237... »). wa.me veut l'indicatif pays sans « + » ni
 * espaces. Un numero camerounais de 9 chiffres commencant par 6 recoit 237 :
 * c'est le cas le plus courant chez nos centres. Sans numero exploitable, pas de
 * lien (le bouton ne s'affiche pas plutot que d'ouvrir une conversation vide).
 */
export function numeroWhatsApp(telephone: string | null | undefined): string | null {
    if (!telephone) return null;
    let chiffres = telephone.replace(/\D/g, '');
    if (chiffres.startsWith('00')) chiffres = chiffres.slice(2);
    if (chiffres.length === 9 && chiffres.startsWith('6')) chiffres = `237${chiffres}`;
    // Un numero international fait au moins 8 chiffres et au plus 15 (norme E.164).
    if (chiffres.length < 8 || chiffres.length > 15) return null;
    return chiffres;
}

export function lienWhatsApp(telephone: string | null | undefined, message: string): string | null {
    const numero = numeroWhatsApp(telephone);
    return numero ? `https://wa.me/${numero}?text=${encodeURIComponent(message)}` : null;
}
