import { router } from '@inertiajs/react';
import { useEffect, useState, useRef } from 'react';
import { LoadingAnimation } from '@/components/loading-animation';

/**
 * Branded loading overlay (the "sablier" / loading.gif) shown during Inertia page
 * transitions that take more than a short threshold. Instant/prefetched
 * navigations resolve faster than the delay and never flash the overlay.
 * This makes the hourglass the DEFAULT loader across the whole app, not just
 * for AI generation.
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
            timer.current = setTimeout(() => setActive(true), 220);
        });
        const stop = () => { clear(); setActive(false); };
        const offFinish = router.on('finish', stop);
        const offCancel = router.on('cancel', stop);

        return () => { clear(); offStart(); offFinish(); offCancel(); };
    }, []);

    if (!active) return null;

    return (
        <div className="pointer-events-none fixed inset-0 z-[60] flex items-center justify-center">
            <div className="absolute inset-0 bg-background/65 backdrop-blur-[2px]" />
            <div className="relative">
                <LoadingAnimation size={120} />
            </div>
        </div>
    );
}
