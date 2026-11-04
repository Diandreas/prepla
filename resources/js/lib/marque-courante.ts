import type { Page } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import type { Marque } from '@/types';
import { cssDeLaMarque, nomDuSite } from './marque-centre.js';

/**
 * Tient la page aux couleurs et au nom de la marque affichee (donnee partagee
 * « marque », app/Services/Center/Marque.php), d'une page a l'autre.
 *
 * app.blade.php pose deja les couleurs au premier affichage ; ici on les suit
 * quand la marque change sans recharger (personnalisation enregistree, page
 * publique d'un autre centre, changement de centre).
 */
const ID_STYLE = 'marque-centre';

let nomCourant = 'PrePla';
let nomParDefaut = 'PrePla';

function marqueDeLaPage(page: Page): Marque | undefined {
    return (page.props as { marque?: Marque }).marque;
}

function appliquer(marque: Marque | undefined): void {
    nomCourant = nomDuSite(marque, nomParDefaut);

    if (typeof document === 'undefined') return;
    const css = cssDeLaMarque(marque);
    let style = document.getElementById(ID_STYLE);

    if (!css) {
        style?.remove();
        return;
    }
    if (!style) {
        style = document.createElement('style');
        style.id = ID_STYLE;
        document.head.appendChild(style);
    }
    if (style.textContent !== css) style.textContent = css;
}

/** A appeler une fois, avant le premier rendu : le titre lit nomDuSiteCourant(). */
export function suivreLaMarque(premierePage: Page, nomDuSiteParDefaut: string): void {
    nomParDefaut = nomDuSiteParDefaut;
    appliquer(marqueDeLaPage(premierePage));

    // Enregistre avant celui d'Inertia (qui recalcule le titre a la navigation) :
    // le nouveau titre porte deja le nom de la nouvelle marque.
    router.on('navigate', (event) => appliquer(marqueDeLaPage(event.detail.page)));
}

export function nomDuSiteCourant(): string {
    return nomCourant;
}
