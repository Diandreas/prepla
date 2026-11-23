import { instanceCourante } from './instance';

/**
 * Coordonnées du support : celles de l'instance (MARQUE_TELEPHONE,
 * MARQUE_EMAIL_CONTACT), PrePla par défaut. Source unique pour le pied de page,
 * le bouton d'aide et le menu. Le même numéro reçoit WhatsApp et les appels.
 *
 * Des fonctions, pas des constantes : l'instance n'est connue qu'au démarrage
 * (app.tsx), après le chargement de ce module.
 */

/** Coordonnées de PrePla, la plateforme : pour ce qui ne regarde que l'éditeur (formule, adresse web d'un centre). */
export const PLATEFORME_TELEPHONE = '+237 693 42 79 13';
export const PLATEFORME_WHATSAPP_URL = whatsappUrl(PLATEFORME_TELEPHONE);

function chiffres(numero: string): string {
    return numero.replace(/\D/g, '');
}

function whatsappUrl(numero: string): string {
    return `https://wa.me/${chiffres(numero)}`;
}

/** Le numéro tel qu'il s'affiche, ex. « +237 693 42 79 13 ». */
export function supportTelephone(): string {
    return instanceCourante().telephone || PLATEFORME_TELEPHONE;
}

export function supportWhatsappUrl(): string {
    return whatsappUrl(supportTelephone());
}

export function supportTelUrl(): string {
    return `tel:+${chiffres(supportTelephone())}`;
}

export function supportEmail(): string {
    return instanceCourante().email_contact || 'prepla.mirlab@gmail.com';
}

export function supportEmailUrl(): string {
    return `mailto:${supportEmail()}`;
}
