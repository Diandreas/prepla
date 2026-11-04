import { useEffect, useState } from 'react';

export type Appearance = 'light' | 'dark' | 'system';

const prefersDark = () => window.matchMedia('(prefers-color-scheme: dark)').matches;

const applyTheme = (appearance: Appearance) => {
    const isDark = appearance === 'dark' || (appearance === 'system' && prefersDark());

    document.documentElement.classList.toggle('dark', isDark);
};

const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

/**
 * Le theme a appliquer quand personne n'a rien regle dans l'application.
 *
 * La page d'accueil garde son propre theme (« prepla-landing-theme »). Un
 * visiteur qui y passe en clair puis ouvre un essai retombait sur le theme du
 * systeme : la page changeait de couleur sous ses yeux. Sans reglage
 * « appearance », on reprend donc celui de l'accueil, puis le systeme.
 * Le stockage peut etre bloque (navigation privee) : on ne s'y fie qu'a l'abri.
 */
function apparenceEnregistree(): Appearance {
    try {
        const reglage = localStorage.getItem('appearance');
        if (reglage === 'light' || reglage === 'dark' || reglage === 'system') return reglage;
        const accueil = localStorage.getItem('prepla-landing-theme');
        if (accueil === 'light' || accueil === 'dark') return accueil;
    } catch {
        // Stockage refuse : on suit le systeme.
    }
    return 'system';
}

const handleSystemThemeChange = () => {
    applyTheme(apparenceEnregistree());
};

/**
 * A rappeler en arrivant sur une page publique (essai) : l'application n'est
 * pas rechargee entre l'accueil et l'essai, et le theme choisi sur l'accueil
 * apres le chargement n'aurait sinon pas ete pris en compte.
 */
export function useApparenceEnregistree() {
    useEffect(() => {
        applyTheme(apparenceEnregistree());
    }, []);
}

export function initializeTheme() {
    applyTheme(apparenceEnregistree());

    // Add the event listener for system theme changes...
    mediaQuery.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    const [appearance, setAppearance] = useState<Appearance>('system');

    const updateAppearance = (mode: Appearance) => {
        setAppearance(mode);
        try {
            localStorage.setItem('appearance', mode);
        } catch {
            // Le choix ne sera simplement pas retenu.
        }
        applyTheme(mode);
    };

    useEffect(() => {
        // Lecture seule : ecrire ici « appearance = system » faute de reglage
        // effacait pour toujours le theme choisi sur l'accueil. Seul un choix
        // explicite de l'apprenant (updateAppearance) est enregistre.
        const initiale = apparenceEnregistree();
        setAppearance(initiale);
        applyTheme(initiale);

        return () => mediaQuery.removeEventListener('change', handleSystemThemeChange);
    }, []);

    return { appearance, updateAppearance };
}
