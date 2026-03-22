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
    const [theme, setTheme] = useState<Theme>(() => {
        if (typeof window !== 'undefined') {
            return (localStorage.getItem('prepla-landing-theme') as Theme) ?? 'dark';
        }
        return 'dark';
    });

    useEffect(() => {
        localStorage.setItem('prepla-landing-theme', theme);
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
        // Backgrounds
        bg:        dark ? '#0f1623' : '#f8f6f1',
        bgAlt:     dark ? '#070b14' : '#ede9e2',
        bgCard:    dark ? 'rgba(255,255,255,0.04)' : 'rgba(255,255,255,0.85)',
        bgCardHov: dark ? 'rgba(255,255,255,0.07)' : 'rgba(255,255,255,0.98)',
        // Text
        text:      dark ? '#e8e4db' : '#1a1612',
        textMid:   dark ? 'rgba(232,228,219,0.65)' : 'rgba(26,22,18,0.6)',
        textDim:   dark ? 'rgba(232,228,219,0.35)' : 'rgba(26,22,18,0.35)',
        // Borders
        border:    dark ? 'rgba(255,255,255,0.07)' : 'rgba(26,22,18,0.1)',
        borderHov: dark ? 'rgba(255,255,255,0.14)' : 'rgba(26,22,18,0.2)',
        // Brand
        ink:  '#1A2B48',
        sky:  '#4A90E2',
        gold: '#F5A623',
        // Glows
        glowSky:  dark ? 'rgba(74,144,226,0.12)' : 'rgba(74,144,226,0.08)',
        glowGold: dark ? 'rgba(245,166,35,0.10)' : 'rgba(245,166,35,0.07)',
        // Navbar
        navBg:         dark ? 'rgba(15,22,35,0.95)' : 'rgba(248,246,241,0.95)',
        navBgScroll:   dark ? 'rgba(7,11,20,0.97)' : 'rgba(240,237,230,0.97)',
        navBorder:     dark ? 'rgba(245,166,35,0.1)' : 'rgba(26,22,18,0.08)',
        // Grid
        gridColor: dark ? '#4A90E2' : '#1A2B48',
        gridOpacity: dark ? 0.04 : 0.03,
    };
}
