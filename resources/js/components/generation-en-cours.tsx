import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * Attente d'une generation IA visible (lecon, correction d'ecrit ou d'oral).
 *
 * Pourquoi pas un simple spinner : au-dela de ~400 ms (seuil de Doherty), une roue
 * qui tourne donne l'impression que rien ne se passe. Un squelette de contenu montre
 * la forme de ce qui arrive, et des messages d'etape qui avancent montrent que le
 * travail progresse. L'attente reelle ne change pas ; l'attente percue, si.
 */

export const ETAPES_ECRIT = [
    'Je lis ta copie…',
    'Je vérifie la grammaire…',
    'Je regarde le vocabulaire…',
    'Je compare avec les critères de l’examen…',
    'Je rédige mes conseils…',
];

export const ETAPES_ORAL = [
    'J’écoute ton enregistrement…',
    'Je transcris ce que tu as dit…',
    'Je vérifie la prononciation et la grammaire…',
    'Je compare avec les critères de l’examen…',
    'Je rédige mes conseils…',
];

export const ETAPES_LECON = [
    'Je regarde où tu en es…',
    'Je reprends tes erreurs récentes…',
    'J’écris l’explication…',
    'Je prépare les exemples…',
    'Je prépare le quiz…',
];

export const ETAPES_SESSION = [
    'J’enregistre tes réponses…',
    'Je calcule ton score…',
    'Je prépare ton bilan…',
];

/** Index de l'etape a afficher : avance seule, puis reste sur la derniere. */
export function useEtapeQuiAvance(nombre: number, actif: boolean, intervalleMs = 2200): number {
    const [index, setIndex] = useState(0);
    useEffect(() => {
        if (!actif) {
            setIndex(0);
            return;
        }
        const id = setInterval(() => {
            setIndex((i) => Math.min(i + 1, nombre - 1));
        }, intervalleMs);
        return () => clearInterval(id);
    }, [actif, nombre, intervalleMs]);
    return index;
}

function Squelette() {
    return (
        <div aria-hidden="true" className="space-y-3 motion-safe:animate-pulse">
            <div className="h-5 w-2/3 rounded-md bg-muted" />
            <div className="h-3 w-full rounded bg-muted" />
            <div className="h-3 w-11/12 rounded bg-muted" />
            <div className="h-3 w-4/5 rounded bg-muted" />
            <div className="mt-4 h-16 w-full rounded-xl bg-muted/70" />
        </div>
    );
}

type Props = {
    etapes: string[];
    actif?: boolean;
    /** En plein ecran (au-dessus de la page) ou dans le flux. */
    pleinEcran?: boolean;
    titre?: string;
};

export function GenerationEnCours({ etapes, actif = true, pleinEcran = false, titre }: Props) {
    const index = useEtapeQuiAvance(etapes.length, actif);
    if (!actif) return null;

    const carte = (
        <div className="w-full max-w-md rounded-2xl border border-border bg-card p-5 shadow-lg">
            {titre && <p className="mb-3 text-[0.9375rem] font-bold text-foreground">{titre}</p>}
            <Squelette />
            <div role="status" aria-live="polite" className="mt-4">
                <p key={index} className="text-[0.9375rem] font-medium text-foreground motion-safe:animate-in motion-safe:fade-in">
                    {etapes[index]}
                </p>
                <ol aria-hidden="true" className="mt-3 flex gap-1.5">
                    {etapes.map((_, i) => (
                        <li key={i} className={`h-1.5 flex-1 rounded-full ${i <= index ? 'bg-primary' : 'bg-muted'}`} />
                    ))}
                </ol>
            </div>
        </div>
    );

    if (!pleinEcran) return carte;

    // data-page-loader : le sablier global (NavigationOverlay) ne s'empile pas dessus.
    return (
        <div data-page-loader className="fixed inset-0 z-50 flex items-center justify-center bg-background/85 p-4 backdrop-blur-sm">
            {carte}
        </div>
    );
}

/**
 * Lien « lecon suivante » : la generation se fait cote serveur pendant la visite.
 * Si la lecon a ete preparee a l'avance (NextLessonPrefetcher), la page s'ouvre
 * avant meme que l'attente ne s'affiche (delai de 300 ms).
 */
export function useAttenteLeconSuivante() {
    const [visite, setVisite] = useState(false);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        if (!visite) return;
        const t = setTimeout(() => setVisible(true), 300);
        const off = router.on('finish', () => {
            setVisite(false);
            setVisible(false);
        });
        return () => {
            clearTimeout(t);
            off();
        };
    }, [visite]);

    return {
        demarrer: () => setVisite(true),
        attente: <GenerationEnCours etapes={ETAPES_LECON} actif={visible} pleinEcran titre="Ta prochaine leçon se prépare" />,
    };
}
