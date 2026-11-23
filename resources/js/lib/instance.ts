import { usePage } from '@inertiajs/react';
import type { Instance } from '@/types';
import i18n from './i18n/i18n';

/**
 * La marque de l'instance (donnee partagee « instance », config/marque.php).
 * Sans variable MARQUE_* cote serveur, c'est PrePla, a l'identique.
 */
export const INSTANCE_PREPLA: Instance = {
    nom: 'PrePla',
    nom_court: 'PrePla',
    slogan: '',
    logo: '/icons/logo.png?v=4',
    logo_carre: '/icons/pwa-512-v4.png',
    couleurs: { primaire: '#4A90E2', secondaire: '#0B2D63' },
    email_contact: 'prepla.mirlab@gmail.com',
    telephone: '+237 693 42 79 13',
    site: null,
    langues_interface: [],
    langue_defaut: null,
    examens: [],
    masquer_kids: false,
    masquer_prix_publics: false,
    mascotte: true,
    personnalisee: false,
};

let courante: Instance = INSTANCE_PREPLA;

/** A appeler une fois, avant le premier rendu (app.tsx). */
export function definirInstance(instance: Instance | undefined): void {
    courante = instance ?? INSTANCE_PREPLA;
    // Les textes i18n ecrivent {{marque}} la ou ils disaient « PrePla ».
    const interpolation = (i18n.options.interpolation ??= {});
    interpolation.defaultVariables = { ...(interpolation.defaultVariables ?? {}), marque: courante.nom };
}

export function instanceCourante(): Instance {
    return courante;
}

/** Remplace « PrePla » par le nom de l'instance dans un texte visible. */
export function avecMarque(texte: string): string {
    return courante.personnalisee ? texte.replace(/PrePla/g, courante.nom) : texte;
}

export function useInstance(): Instance {
    return (usePage().props as { instance?: Instance }).instance ?? courante;
}
