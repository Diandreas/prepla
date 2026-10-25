import { useEffect, useState } from 'react';

const MOBILE_BREAKPOINT = 768;

export function useIsMobile() {
    // La largeur est connue des le premier rendu : la lire tout de suite evite
    // que le telephone soit traite comme un ordinateur pendant une frame. Ce
    // n'est pas cosmetique — c'est ce qui declenchait le prechargement des
    // pages du menu lateral sur telephone, donc de la donnee depensee pour un
    // tiroir reste ferme. Il n'y a pas de rendu serveur ici, mais le garde
    // laisse le hook sur si on en ajoute un.
    const [isMobile, setIsMobile] = useState<boolean>(() =>
        typeof window === 'undefined' ? false : window.innerWidth < MOBILE_BREAKPOINT,
    );

    useEffect(() => {
        const mql = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`);

        const onChange = () => {
            setIsMobile(window.innerWidth < MOBILE_BREAKPOINT);
        };

        mql.addEventListener('change', onChange);
        setIsMobile(window.innerWidth < MOBILE_BREAKPOINT);

        return () => mql.removeEventListener('change', onChange);
    }, []);

    return !!isMobile;
}
