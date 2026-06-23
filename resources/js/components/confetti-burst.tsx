import { useMemo } from 'react';

/**
 * Lightweight CSS confetti burst (no library). Mount it when a session/objective
 * is completed; it plays once and respects reduced-motion (renders nothing then).
 */
export function ConfettiBurst({ count = 36 }: { count?: number }) {
    const reduced = typeof window !== 'undefined'
        && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

    const pieces = useMemo(() => {
        const colors = ['#4A90E2', '#48b77b', '#F5A623', '#F97316', '#9B59B6', '#3478c8'];
        return Array.from({ length: count }, (_, i) => ({
            left: Math.random() * 100,
            delay: Math.random() * 0.25,
            duration: 1.6 + Math.random() * 1.2,
            color: colors[i % colors.length],
            size: 6 + Math.random() * 6,
            rotate: Math.random() * 360,
            drift: (Math.random() - 0.5) * 120,
        }));
    }, [count]);

    if (reduced) return null;

    return (
        <div className="pointer-events-none fixed inset-0 z-[60] overflow-hidden" aria-hidden>
            {pieces.map((p, i) => (
                <span
                    key={i}
                    className="confetti-piece"
                    style={{
                        left: `${p.left}%`,
                        width: p.size,
                        height: p.size * 0.6,
                        background: p.color,
                        animationDelay: `${p.delay}s`,
                        animationDuration: `${p.duration}s`,
                        ['--drift' as string]: `${p.drift}px`,
                        ['--rot' as string]: `${p.rotate}deg`,
                    } as React.CSSProperties}
                />
            ))}
        </div>
    );
}
