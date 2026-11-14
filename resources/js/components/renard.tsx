import { useState } from 'react';
import { cn } from '@/lib/utils';

export type PoseRenard = 'bravo' | 'encourage' | 'reflechit' | 'ecoute' | 'lecon-finie' | 'serie' | 'serie-perdue' | 'merci' | 'welcome';

const REPLI = '/illustrations/prepla-guide/welcome.png';

/** Le texte lu par les lecteurs d'ecran : la pose dit quelque chose. */
const DESCRIPTIONS: Record<PoseRenard, string> = {
    bravo: 'Le renard PrePla applaudit',
    encourage: 'Le renard PrePla t’encourage',
    reflechit: 'Le renard PrePla réfléchit',
    ecoute: 'Le renard PrePla écoute',
    'lecon-finie': 'Le renard PrePla fête la leçon terminée',
    serie: 'Le renard PrePla fête ta série',
    'serie-perdue': 'Le renard PrePla console : la série est perdue',
    merci: 'Le renard PrePla dit merci',
    welcome: 'Le renard PrePla te souhaite la bienvenue',
};

export function cheminDeLaPose(pose: PoseRenard): string {
    return `/illustrations/prepla-guide/${pose}.webp`;
}

interface RenardProps {
    pose: PoseRenard;
    size?: number;
    /** Decoratif quand un texte a cote dit deja la meme chose. */
    decoratif?: boolean;
    className?: string;
}

/**
 * La mascotte qui reagit. Chaque pose est une image a part ; tant qu'une pose
 * n'est pas dessinee, on retombe sur l'accueil (welcome.png) au lieu d'une
 * image cassee. L'entree est un petit saut avec rebond (coupe si l'eleve
 * demande moins de mouvement, voir app.css).
 */
export function Renard(props: RenardProps) {
    // La cle remonte le composant quand la pose change : repli remis a zero
    // et animation d'entree rejouee.
    return <RenardPose key={props.pose} {...props} />;
}

function RenardPose({ pose, size = 120, decoratif = false, className }: RenardProps) {
    const [repli, setRepli] = useState(false);
    const [absent, setAbsent] = useState(false);
    if (absent) return null;

    return (
        <img
            src={repli ? REPLI : cheminDeLaPose(pose)}
            alt={decoratif ? '' : DESCRIPTIONS[pose]}
            aria-hidden={decoratif || undefined}
            width={size}
            height={size}
            decoding="async"
            data-pose={pose}
            onError={() => (repli ? setAbsent(true) : setRepli(true))}
            className={cn('renard-entree mx-auto h-auto object-contain drop-shadow-lg', className)}
            style={{ maxWidth: size }}
        />
    );
}
