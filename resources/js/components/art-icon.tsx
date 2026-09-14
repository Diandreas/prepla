import { cn } from '@/lib/utils';
import type { CSSProperties } from 'react';

interface ArtIconProps {
    name: string;
    size?: number;
    tone?: 'blue' | 'mint' | 'amber' | 'rose' | 'neutral';
    className?: string;
}

/** Colored artwork always rests on a light surface, even in the dark theme. */
export function ArtIcon({ name, size = 48, tone = 'blue', className }: ArtIconProps) {
    return (
        <span
            aria-hidden="true"
            className={cn('studio-icon', `studio-icon--${tone}`, className)}
            style={{ '--icon-size': `${size}px` } as CSSProperties}
        >
            <img src={`/icons/${name}.png`} alt="" width={Math.round(size * 0.62)} height={Math.round(size * 0.62)} data-color="true" />
        </span>
    );
}
