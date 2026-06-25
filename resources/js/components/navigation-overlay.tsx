import { router } from '@inertiajs/react';
import { useEffect, useState, useRef } from 'react';

/**
 * Lightweight transition feedback between Inertia pages. The default progress
 * bar is very thin and easy to miss on mobile, and prefetched-but-not-yet-ready
 * navigations could feel "stuck". This shows a subtle dimming + skeleton shimmer
 * over the content ONLY when a navigation takes longer than a short threshold,
 * so instant (prefetched) navigations never flash it.
 */
export function NavigationOverlay() {
    const [active, setActive] = useState(false);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        const clear = () => {
            if (timer.current) { clearTimeout(timer.current); timer.current = null; }
        };

        const offStart = router.on('start', () => {
            clear();
            // Only reveal the overlay if the visit hasn't finished within 180ms —
            // cached/prefetched visits resolve faster than this and stay silent.
            timer.current = setTimeout(() => setActive(true), 180);
        });

        const stop = () => { clear(); setActive(false); };
        const offFinish = router.on('finish', stop);
        const offCancel = router.on('cancel', stop);

        return () => { clear(); offStart(); offFinish(); offCancel(); };
    }, []);

    if (!active) return null;

    return (
        <div className="pointer-events-none fixed inset-0 z-[60] flex items-center justify-center">
            <div className="absolute inset-0 bg-background/55 backdrop-blur-[1px] transition-opacity duration-150" />
            <div className="relative flex flex-col items-center gap-3">
                <div
                    className="h-9 w-9 rounded-full border-[3px] border-primary/25 border-t-primary animate-spin"
                    style={{ borderTopColor: '#4A90E2' }}
                />
                <span className="text-xs font-bold text-muted-foreground">Chargement…</span>
            </div>
        </div>
    );
}
