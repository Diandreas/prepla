import { cn } from '@/lib/utils';

/**
 * « +N XP » qui monte et s'efface au-dessus du compteur. A poser dans un
 * parent en `relative`. Rien si le gain est nul : feter zero sonnerait faux.
 */
export function XpEnvol({ xp, className }: { xp: number; className?: string }) {
    if (!xp || xp <= 0) return null;
    return (
        <span
            aria-hidden="true"
            className={cn(
                'xp-envol absolute -top-3 left-1/2 -ml-6 whitespace-nowrap text-sm font-extrabold text-amber-600 dark:text-amber-300',
                className,
            )}
        >
            +{xp} XP
        </span>
    );
}
