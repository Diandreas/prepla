/**
 * Fait monter « +N XP » depuis un element (la reponse, la carte d'XP) jusqu'au
 * compteur d'XP de l'en-tete (marque data-compteur-xp). Rien si le gain est
 * nul, si le compteur n'est pas affiche, ou si l'appareil demande moins de
 * mouvement : le chiffre du compteur suffit alors.
 */
export function envolerXpVersCompteur(depuis: HTMLElement | null, xp: number): void {
    if (!depuis || !xp || xp <= 0 || typeof document === 'undefined') return;
    try {
        if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return;
    } catch {
        /* matchMedia absent : on anime */
    }
    const cible = document.querySelector<HTMLElement>('[data-compteur-xp]');
    if (!cible) return;
    const a = depuis.getBoundingClientRect();
    const b = cible.getBoundingClientRect();
    if (b.width === 0 && b.height === 0) return;

    const bulle = document.createElement('span');
    bulle.textContent = `+${xp} XP`;
    bulle.setAttribute('aria-hidden', 'true');
    bulle.className = 'xp-vers-compteur pointer-events-none fixed z-[100] whitespace-nowrap text-sm font-extrabold text-amber-600 dark:text-amber-300';
    bulle.style.left = `${a.left + a.width / 2}px`;
    bulle.style.top = `${a.top + a.height / 2}px`;
    document.body.appendChild(bulle);

    const dx = b.left + b.width / 2 - (a.left + a.width / 2);
    const dy = b.top + b.height / 2 - (a.top + a.height / 2);
    if (typeof bulle.animate !== 'function') {
        bulle.remove();
        return;
    }
    const anim = bulle.animate(
        [
            { transform: 'translate(-50%, -50%) scale(1)', opacity: 1 },
            { transform: 'translate(-50%, calc(-50% - 16px)) scale(1.15)', opacity: 1, offset: 0.25 },
            { transform: `translate(calc(-50% + ${dx}px), calc(-50% + ${dy}px)) scale(0.7)`, opacity: 0.2 },
        ],
        { duration: 900, easing: 'cubic-bezier(0.2, 0.7, 0.2, 1)' },
    );
    anim.onfinish = () => bulle.remove();
    anim.oncancel = () => bulle.remove();
}
