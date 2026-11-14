import { useState } from 'react';
import { cn } from '@/lib/utils';

interface IllustrationProps {
    /** L'image voulue, ex. /illustrations/etats/carnet-vide.webp */
    src: string;
    /** L'image de secours si la premiere manque (pas encore generee). */
    fallback?: string;
    /** Toujours descriptif : l'image porte le sens de l'etat vide. */
    alt: string;
    size?: number;
    className?: string;
}

/**
 * Une illustration d'etat (vide, erreur, premiere fois). Les images sont
 * generees petit a petit : si `src` manque, on passe a `fallback`, et si lui
 * aussi manque, on n'affiche rien plutot qu'une image cassee.
 */
export function Illustration({ src, fallback = '/illustrations/prepla-guide/welcome.png', alt, size = 160, className }: IllustrationProps) {
    const [etape, setEtape] = useState<0 | 1 | 2>(0);
    if (etape === 2) return null;

    return (
        <img
            src={etape === 0 ? src : fallback}
            alt={alt}
            width={size}
            height={size}
            loading="lazy"
            decoding="async"
            onError={() => setEtape((e) => (e === 0 && fallback && fallback !== src ? 1 : 2))}
            className={cn('mx-auto h-auto object-contain', className)}
            style={{ maxWidth: size }}
        />
    );
}
