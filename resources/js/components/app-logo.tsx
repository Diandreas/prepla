import { usePage } from '@inertiajs/react';
import type { Marque } from '@/types';
import { useT } from '@/lib/i18n/traduire';

/** Les initiales d'un nom de centre, quand il n'a pas (encore) de logo. */
function initiales(nom: string): string {
    const mots = nom.split(/[\s\-—–·]+/u).filter((m) => /\p{L}/u.test(m));
    return (mots.length > 1 ? mots[0][0] + mots[1][0] : nom.slice(0, 2)).toUpperCase();
}

/**
 * Le logo et le nom d'un centre. Sert a l'en-tete, et a l'apercu de la page
 * Personnalisation (couleurs passees en direct, avant d'enregistrer).
 */
export function LogoDeCentre({
    nom,
    logoUrl,
    masquerPrepla,
    primaire,
    texteSurPrimaire,
    accent,
}: {
    nom: string;
    logoUrl: string | null;
    masquerPrepla: boolean;
    /** Sans couleur passee : celles de la page (variables CSS de la marque). */
    primaire?: string;
    texteSurPrimaire?: string;
    accent?: string;
}) {
    const { t } = useT();
    return (
        <>
            <div className="flex aspect-square size-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white ring-1 ring-black/5">
                {logoUrl ? (
                    <img src={logoUrl} alt={`Logo ${nom}`} className="h-8 w-8 object-contain" />
                ) : (
                    <span
                        aria-hidden
                        className="flex h-full w-full items-center justify-center text-sm font-bold"
                        style={{ background: primaire ?? 'var(--primary)', color: texteSurPrimaire ?? 'var(--primary-foreground)' }}
                    >
                        {initiales(nom)}
                    </span>
                )}
            </div>
            <div className="ml-1 grid min-w-0 flex-1 text-left leading-tight">
                <span className="truncate font-bold tracking-tight" style={{ fontSize: '0.95rem', color: accent ?? 'var(--marque-accent, currentColor)' }} title={nom}>
                    {nom}
                </span>
                {/* En marque blanche, PrePla n'apparait plus du tout. */}
                {!masquerPrepla && <span className="truncate text-[0.65rem] text-muted-foreground">{t('avec PrePla')}</span>}
            </div>
        </>
    );
}

/** Le logo de PrePla, tel qu'il a toujours ete. */
function LogoPrePla() {
    return (
        <>
            <div className="flex aspect-square size-9 items-center justify-center rounded-lg overflow-hidden shrink-0"
                style={{ background: 'linear-gradient(135deg, #ffffff, #e7f2ff)' }}>
                <img
                    src="/icons/logo.png?v=4"
                    alt="PrePla Logo"
                    className="h-7 w-7"
                    style={{ objectFit: 'contain' }}
                />
            </div>
            <div className="ml-1 grid flex-1 text-left leading-tight">
                <span className="truncate font-bold tracking-tight" style={{ fontSize: '0.95rem', letterSpacing: '-0.01em' }}>
                    Pre<span style={{ color: '#4A90E2' }}>Pla</span>
                    <span style={{
                        marginLeft: '0.3rem',
                        padding: '0.1rem 0.3rem',
                        borderRadius: '0.2rem',
                        fontSize: '0.5rem',
                        fontWeight: 700,
                        letterSpacing: '0.08em',
                        textTransform: 'uppercase' as const,
                        background: 'rgba(74,144,226,0.12)',
                        color: '#4A90E2',
                    }}>IA</span>
                </span>
            </div>
        </>
    );
}

/**
 * Le logo de l'application : celui du centre quand la personne en voit la
 * marque (donnee partagee « marque »), sinon celui de PrePla.
 */
export default function AppLogo() {
    const marque = (usePage().props as { marque?: Marque }).marque;

    if (!marque || marque.centre_id == null) {
        return <LogoPrePla />;
    }

    return <LogoDeCentre nom={marque.nom} logoUrl={marque.logo_url} masquerPrepla={marque.masquer_prepla} />;
}
