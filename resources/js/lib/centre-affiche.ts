import type { Page } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import axios from 'axios';

/**
 * Joint a chaque ecriture le centre que la page affiche.
 *
 * Le centre courant est range en session, donc commun a tous les onglets : un
 * formulaire ouvert pour le centre A, envoye apres un passage dans B depuis un
 * autre onglet, ecrivait dans B. Le serveur compare cet en-tete au centre
 * courant et n'ecrit rien s'ils different (app/Http/Middleware/EnsureDisplayedCenter.php).
 *
 * Inertia envoie ses requetes avec l'instance axios par defaut : l'intercepteur
 * couvre ses formulaires comme les appels axios directs.
 */
const EN_TETE = 'X-Prepla-Centre';

let centreAffiche: number | null = null;

function centreDeLaPage(page: Page): number | null {
    const centre = (page.props as { auth?: { center?: { id?: unknown } | null } }).auth?.center;

    return typeof centre?.id === 'number' ? centre.id : null;
}

export function suivreLeCentreAffiche(premierePage: Page): void {
    centreAffiche = centreDeLaPage(premierePage);

    // Chaque page affichee, y compris par les boutons precedent et suivant.
    router.on('navigate', (event) => {
        centreAffiche = centreDeLaPage(event.detail.page);
    });

    axios.interceptors.request.use((config) => {
        if (centreAffiche !== null && (config.method ?? 'get').toLowerCase() !== 'get') {
            config.headers.set(EN_TETE, String(centreAffiche));
        }

        return config;
    });
}
