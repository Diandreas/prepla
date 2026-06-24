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
        // Backgrounds — aligned with the logo: deep navy / crisp blue-white (no beige)
        bg:        dark ? '#0b1322' : '#f5f8fc',
        bgAlt:     dark ? '#070d18' : '#e9f0fa',
        bgCard:    dark ? 'rgba(255,255,255,0.04)' : 'rgba(255,255,255,0.9)',
        bgCardHov: dark ? 'rgba(255,255,255,0.07)' : 'rgba(255,255,255,1)',
        // Text — navy ink on light, soft white on dark
        text:      dark ? '#eef3fb' : '#13233f',
        textMid:   dark ? 'rgba(238,243,251,0.66)' : 'rgba(19,35,63,0.62)',
        textDim:   dark ? 'rgba(238,243,251,0.36)' : 'rgba(19,35,63,0.36)',
        // Borders
        border:    dark ? 'rgba(255,255,255,0.08)' : 'rgba(19,35,63,0.1)',
        borderHov: dark ? 'rgba(255,255,255,0.16)' : 'rgba(19,35,63,0.2)',
        // Brand — blue is primary (the logo's dominant colour), orange is the accent
        ink:  '#1A2B48',
        sky:  '#3B82E0',
        gold: '#F5A623',
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
