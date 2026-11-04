/**
 * Le vocabulaire des eleves, cote centre : ce que partagent la page d'un eleve
 * et celle de sa classe.
 */

export type Etape = 'discovered' | 'learning' | 'mastered';
export type Etapes = Record<Etape, string>;

export interface BilanVocabulaire {
    total: number;
    par_etape: Record<Etape, number>;
    par_niveau: Record<string, number>;
    a_reviser: number;
    laisses_de_cote: number;
    ajoutes_7_jours: number;
    revises_7_jours: number;
    derniere_revision: string | null;
}

export const ETAPE_COULEUR: Record<Etape, string> = {
    discovered: 'border-sky-300 text-sky-700 dark:text-sky-400',
    learning: 'border-amber-300 text-amber-700 dark:text-amber-400',
    mastered: 'border-emerald-300 text-emerald-700 dark:text-emerald-400',
};

const ETAPE_FOND: Record<Etape, string> = {
    discovered: 'bg-sky-400',
    learning: 'bg-amber-400',
    mastered: 'bg-emerald-500',
};

/** Une barre decoupee par etape, avec sa legende. */
export function RepartitionEtapes({ etapes, parEtape, total }: { etapes: Etapes; parEtape: Record<Etape, number>; total: number }) {
    const ordre = Object.keys(etapes) as Etape[];
    if (total === 0) return <span className="text-xs text-muted-foreground">—</span>;

    return (
        <div className="space-y-1.5">
            <div
                className="flex h-2.5 w-full overflow-hidden rounded-full bg-muted"
                role="img"
                aria-label={ordre.map((e) => `${etapes[e]} : ${parEtape[e] ?? 0}`).join(', ')}
            >
                {ordre.map((e) =>
                    (parEtape[e] ?? 0) > 0 ? <div key={e} className={ETAPE_FOND[e]} style={{ width: `${((parEtape[e] ?? 0) / total) * 100}%` }} /> : null,
                )}
            </div>
            <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                {ordre.map((e) => (
                    <span key={e} className="inline-flex items-center gap-1.5">
                        <span className={`inline-block h-2 w-2 rounded-full ${ETAPE_FOND[e]}`} />
                        {etapes[e]} {parEtape[e] ?? 0}
                    </span>
                ))}
            </div>
        </div>
    );
}
