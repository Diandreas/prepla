import { createContext, useContext, useEffect, useState } from 'react';

type Theme = 'dark' | 'light';

export const ThemeContext = createContext<{ theme: Theme; toggle: () => void }>({
    theme: 'dark',
    toggle: () => {},
});

export function useLandingTheme() {
    return useContext(ThemeContext);
}

export function LandingThemeProvider({ children }: { children: React.ReactNode }) {
    // Le stockage peut etre bloque (navigation privee, donnees du site refusees) :
    // la page doit s'afficher quand meme.
    const [theme, setTheme] = useState<Theme>(() => {
        try {
            return localStorage.getItem('prepla-landing-theme') === 'light' ? 'light' : 'dark';
        } catch {
            return 'dark';
        }
    });

    useEffect(() => {
        try {
            localStorage.setItem('prepla-landing-theme', theme);
        } catch {
            // Le theme ne sera simplement pas retenu.
        }
    }, [theme]);

    const toggle = () => setTheme(t => t === 'dark' ? 'light' : 'dark');

    return (
        <ThemeContext.Provider value={{ theme, toggle }}>
            {children}
        </ThemeContext.Provider>
    );
}

// --- Shared design tokens per theme ---
export function useTokens() {
    const { theme } = useLandingTheme();
    const dark = theme === 'dark';

    return {
        theme,
        dark,
        // Backgrounds — aligned with the logo: deep navy / crisp blue-white (no beige)
        bg:        dark ? '#0b1322' : '#f5f8fc',
        bgAlt:     dark ? '#070d18' : '#e9f0fa',
        bgCard:    dark ? 'rgba(255,255,255,0.04)' : 'rgba(255,255,255,0.9)',
        bgCardHov: dark ? 'rgba(255,255,255,0.07)' : 'rgba(255,255,255,1)',
        // Text — navy ink on light, soft white on dark
        text:      dark ? '#eef3fb' : '#13233f',
        textMid:   dark ? 'rgba(238,243,251,0.72)' : 'rgba(19,35,63,0.72)',
        // Assez contraste pour du texte de 12 a 13 px (4,5:1) : a 0,36, il restait
        // vers 3:1 en sombre et 2:1 en clair.
        textDim:   dark ? 'rgba(238,243,251,0.58)' : 'rgba(19,35,63,0.66)',
        // Borders
        border:    dark ? 'rgba(255,255,255,0.08)' : 'rgba(19,35,63,0.1)',
        borderHov: dark ? 'rgba(255,255,255,0.16)' : 'rgba(19,35,63,0.2)',
        // Brand — blue is primary (the logo's dominant colour), orange is the accent
        ink:  '#1A2B48',
        sky:  '#3B82E0',
        gold: '#F5A623',
        // L'essai sans compte a sa couleur : l'orange, avec un texte marine (9:1).
        // Le bleu reste celui du compte.
        essai:    '#F5A623',
        surEssai: '#0b1322',
        // L'orange en texte sur fond clair : assez sombre pour se lire (4,7:1).
        accent:   dark ? '#F5A623' : '#A35F00',
        lien:     dark ? '#7DB3F5' : '#1F5FB8',
        // Glows
        glowSky:  dark ? 'rgba(59,130,224,0.16)' : 'rgba(59,130,224,0.10)',
        glowGold: dark ? 'rgba(245,166,35,0.10)' : 'rgba(245,166,35,0.07)',
        // Navbar
        navBg:         dark ? 'rgba(11,19,34,0.95)' : 'rgba(245,248,252,0.95)',
        navBgScroll:   dark ? 'rgba(7,13,24,0.97)' : 'rgba(233,240,250,0.97)',
        navBorder:     dark ? 'rgba(59,130,224,0.12)' : 'rgba(19,35,63,0.08)',
        // Grid
        gridColor: dark ? '#3B82E0' : '#1A2B48',
        gridOpacity: dark ? 0.05 : 0.04,
    };
}
