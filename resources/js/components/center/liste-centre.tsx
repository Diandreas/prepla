import { Link, router } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Drapeau } from '@/components/center/barre-classement';
import { useT } from '@/lib/i18n/traduire';

/**
 * Les briques communes des listes du centre (classes, eleves, devoirs) : en-tete
 * de page, chiffres de synthese, pastilles, avatars, barre d'avancement, etat
 * vide, et une liste qui passe du tableau aux cartes sur telephone.
 */

export function EnTetePage({ titre, sousTitre, actions }: { titre: string; sousTitre?: React.ReactNode; actions?: React.ReactNode }) {
    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div className="min-w-0">
                <h1 className="text-2xl font-bold tracking-tight">{titre}</h1>
                {sousTitre && <p className="mt-1 text-sm text-muted-foreground">{sousTitre}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap gap-2">{actions}</div>}
        </div>
    );
}

export type Ton = 'neutre' | 'primaire' | 'succes' | 'alerte' | 'danger';

const TON_ICONE: Record<Ton, string> = {
    neutre: 'bg-muted text-muted-foreground',
    primaire: 'bg-primary/10 text-primary',
    succes: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400',
    alerte: 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400',
    danger: 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400',
};

/** Un chiffre de synthese. Cliquable, il filtre la liste dessous. */
export function CarteKpi({
    libelle,
    valeur,
    detail,
    icone: Icone,
    ton = 'neutre',
    actif = false,
    onClick,
}: {
    libelle: string;
    valeur: React.ReactNode;
    detail?: React.ReactNode;
    icone: React.ComponentType<{ className?: string }>;
    ton?: Ton;
    actif?: boolean;
    onClick?: () => void;
}) {
    const contenu = (
        <>
            <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${TON_ICONE[ton]}`}>
                <Icone className="h-5 w-5" />
            </span>
            <span className="min-w-0">
                <span className="block text-2xl font-bold leading-tight tabular-nums">{valeur}</span>
                <span className="block text-sm text-muted-foreground">{libelle}</span>
                {detail && <span className="mt-0.5 block text-xs text-muted-foreground">{detail}</span>}
            </span>
        </>
    );
    const classe = `flex items-start gap-3 rounded-xl border bg-card p-4 text-left shadow-xs transition-colors ${
        actif ? 'border-primary ring-1 ring-primary' : 'border-border'
    }`;

    return onClick ? (
        <button type="button" onClick={onClick} aria-pressed={actif} className={`${classe} hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring`}>
            {contenu}
        </button>
    ) : (
        <div className={classe}>{contenu}</div>
    );
}

const COULEURS_AVATAR = [
    'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    'bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300',
    'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
    'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300',
];

export function initiales(nom: string): string {
    const mots = nom.trim().split(/\s+/).filter(Boolean);
    if (mots.length === 0) return '?';
    const lettres = mots.length === 1 ? mots[0].slice(0, 2) : mots[0][0] + mots[mots.length - 1][0];
    return lettres.toUpperCase();
}

/** Les initiales d'une personne, sur une couleur stable tiree de son nom. */
export function AvatarInitiales({ nom, taille = 'md', className = '' }: { nom: string; taille?: 'sm' | 'md'; className?: string }) {
    let h = 0;
    for (const c of nom) h = (h * 31 + c.charCodeAt(0)) >>> 0;
    const dim = taille === 'sm' ? 'h-7 w-7 text-[11px]' : 'h-9 w-9 text-xs';
    return (
        <span aria-hidden className={`inline-flex shrink-0 items-center justify-center rounded-full font-semibold ${dim} ${COULEURS_AVATAR[h % COULEURS_AVATAR.length]} ${className}`}>
            {initiales(nom)}
        </span>
    );
}

/** Plusieurs personnes : les avatars se chevauchent, les noms suivent. */
export function PileAvatars({ noms, vide = '—', max = 3 }: { noms: string[]; vide?: React.ReactNode; max?: number }) {
    const { t } = useT();
    if (noms.length === 0) return <span className="text-sm text-muted-foreground">{vide}</span>;
    const visibles = noms.slice(0, max);
    return (
        <span className="flex min-w-0 items-center gap-2" title={noms.join(', ')}>
            <span className="flex -space-x-2">
                {visibles.map((n) => (
                    <AvatarInitiales key={n} nom={n} taille="sm" className="ring-2 ring-card" />
                ))}
                {noms.length > max && (
                    <span className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-muted text-[11px] font-semibold text-muted-foreground ring-2 ring-card">
                        +{noms.length - max}
                    </span>
                )}
            </span>
            <span className="min-w-0 text-sm leading-snug">{noms.length === 1
                    ? noms[0]
                    : noms.length > 2
                      ? t('{nom} et {n} autres', { nom: noms[0], n: noms.length - 1 })
                      : t('{nom} et {n} autre', { nom: noms[0], n: noms.length - 1 })}</span>
        </span>
    );
}

const pastille = 'inline-flex items-center gap-1 whitespace-nowrap rounded-md border px-1.5 py-0.5 text-xs font-semibold';

/** Le niveau CECRL : debutant en vert, intermediaire en bleu, avance en violet. */
export function BadgeNiveau({ niveau, titre }: { niveau: string | null | undefined; titre?: string }) {
    if (!niveau) return null;
    const lettre = niveau.toUpperCase()[0];
    const couleur =
        lettre === 'A'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300'
            : lettre === 'B'
              ? 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950/50 dark:text-sky-300'
              : lettre === 'C'
                ? 'border-violet-200 bg-violet-50 text-violet-800 dark:border-violet-900 dark:bg-violet-950/50 dark:text-violet-300'
                : 'border-border bg-muted text-muted-foreground';
    return (
        <span className={`${pastille} ${couleur}`} title={titre}>
            {niveau}
        </span>
    );
}

export function BadgeLangue({ langue }: { langue: { nom: string; drapeau: string | null } | null | undefined }) {
    if (!langue) return null;
    return (
        <span className={`${pastille} border-border bg-background font-medium text-foreground`}>
            <Drapeau emoji={langue.drapeau} /> {langue.nom}
        </span>
    );
}

/** Une pastille de statut, avec un point de couleur. */
export function BadgeStatut({ libelle, ton }: { libelle: string; ton: Ton }) {
    const couleur: Record<Ton, string> = {
        neutre: 'border-border bg-muted text-muted-foreground',
        primaire: 'border-primary/30 bg-primary/10 text-primary',
        succes: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300',
        alerte: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-300',
        danger: 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-300',
    };
    const point: Record<Ton, string> = {
        neutre: 'bg-muted-foreground/60',
        primaire: 'bg-primary',
        succes: 'bg-emerald-500',
        alerte: 'bg-amber-500',
        danger: 'bg-rose-500',
    };
    return (
        <span className={`inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2 py-0.5 text-xs font-medium ${couleur[ton]}`}>
            <span aria-hidden className={`h-1.5 w-1.5 rounded-full ${point[ton]}`} />
            {libelle}
        </span>
    );
}

/** Avancement d'une classe : ceux qui ont fini, puis ceux qui ont commence. */
export function BarreProgression({ finis, commences = 0, total, legende }: { finis: number; commences?: number; total: number; legende?: React.ReactNode }) {
    const { t } = useT();
    const pFini = total ? Math.min(100, (100 * finis) / total) : 0;
    const pCommence = total ? Math.min(100 - pFini, (100 * commences) / total) : 0;
    return (
        <div className="min-w-[8rem] space-y-1">
            <div
                className="flex h-2 w-full overflow-hidden rounded-full bg-muted"
                role="progressbar"
                aria-valuemin={0}
                aria-valuemax={total}
                aria-valuenow={finis}
                aria-label={t('{finis} sur {total} ont fini', { finis, total })}
            >
                <span className="h-full bg-emerald-500 transition-all" style={{ width: `${pFini}%` }} />
                <span className="h-full bg-emerald-500/35 transition-all" style={{ width: `${pCommence}%` }} />
            </div>
            {legende && <div className="text-xs text-muted-foreground">{legende}</div>}
        </div>
    );
}

/** Une liste vide : une image simple, une phrase, et de quoi en sortir. */
export function EtatVide({
    icone: Icone,
    titre,
    texte,
    action,
}: {
    icone: React.ComponentType<{ className?: string }>;
    titre: string;
    texte?: React.ReactNode;
    action?: React.ReactNode;
}) {
    return (
        <div className="flex flex-col items-center rounded-xl border border-dashed border-border bg-card px-6 py-12 text-center">
            <span className="relative mb-4 flex h-16 w-16 items-center justify-center">
                <span className="absolute inset-0 rounded-2xl bg-primary/10 rotate-6" />
                <span className="absolute inset-0 rounded-2xl border border-primary/20 bg-card -rotate-3" />
                <Icone className="relative h-7 w-7 text-primary" />
            </span>
            <p className="text-base font-semibold">{titre}</p>
            {texte && <p className="mt-1 max-w-md text-sm text-muted-foreground">{texte}</p>}
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}

/**
 * Un tableau sur grand ecran, des cartes empilees sur telephone : le tableau
 * large defilait de cote sur 375 px.
 */
export function ListeAdaptative<T>({
    columns,
    rows,
    rowKey,
    lien,
    carte,
    empty,
}: {
    columns: Column<T>[];
    rows: T[];
    rowKey: (row: T) => string | number;
    lien: (row: T) => string;
    carte: (row: T) => React.ReactNode;
    empty?: React.ReactNode;
}) {
    const { t } = useT();
    if (rows.length === 0) {
        return <div className="rounded-xl border border-dashed border-border bg-card p-8 text-center text-sm text-muted-foreground">{empty ?? t('Aucune donnée.')}</div>;
    }
    return (
        <>
            <div className="hidden bg-card md:block md:rounded-xl">
                <DataTable columns={columns} rows={rows} rowKey={rowKey} onRowClick={(r) => router.visit(lien(r))} empty={empty} />
            </div>
            <ul className="space-y-2 md:hidden">
                {rows.map((r) => (
                    <li key={rowKey(r)}>
                        <Link
                            href={lien(r)}
                            className="flex items-start gap-3 rounded-xl border border-border bg-card p-4 transition-colors hover:bg-muted/40 active:bg-muted/60"
                        >
                            <div className="min-w-0 flex-1">{carte(r)}</div>
                            <ChevronRight className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" aria-hidden />
                        </Link>
                    </li>
                ))}
            </ul>
        </>
    );
}
