import { Link } from '@inertiajs/react';
import { Crown, Flame } from 'lucide-react';
import { initiales } from '@/components/center/liste-centre';

/**
 * Un podium or / argent / bronze, dans l'esprit de /leaderboard : le premier
 * au centre et plus haut, le deuxieme a gauche, le troisieme a droite.
 * Reutilisable : il ne connait que des noms, des valeurs et des liens.
 */

export interface MarcheDePodium {
    id: number;
    nom: string;
    rang: number;
    /** « 320 XP » */
    valeur: string;
    /** Une ligne de plus sous la valeur : « 12 exercices · 84 % ». */
    detail?: string;
    serie?: number;
    lien?: string;
}

export const MEDAILLES = [
    { nom: 'or', fond: '#F5A623', ombre: '#c9840f', clair: 'rgba(245,166,35,0.16)' },
    { nom: 'argent', fond: '#A8B0BA', ombre: '#7d858f', clair: 'rgba(168,176,186,0.18)' },
    { nom: 'bronze', fond: '#CD7F32', ombre: '#9a5e20', clair: 'rgba(205,127,50,0.16)' },
];

/** La medaille d'un rang (1 a 3), ou null. */
export function medaille(rang: number | undefined) {
    return rang && rang >= 1 && rang <= 3 ? MEDAILLES[rang - 1] : null;
}

/** Une petite pastille de rang : medaille pour le podium, numero ensuite. */
export function PastilleRang({ rang }: { rang: number | undefined }) {
    if (!rang) return <span className="inline-flex h-7 w-7 items-center justify-center text-xs text-muted-foreground">—</span>;
    const m = medaille(rang);
    return m ? (
        <span
            className="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-black text-white"
            style={{ background: m.fond, boxShadow: `0 2px 0 0 ${m.ombre}` }}
            title={`${rang}${rang === 1 ? 'er' : 'e'} — médaille d'${m.nom}`}
        >
            {rang}
        </span>
    ) : (
        <span className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-muted text-xs font-bold tabular-nums text-muted-foreground">{rang}</span>
    );
}

const HAUTEURS = ['h-24 sm:h-28', 'h-16 sm:h-20', 'h-12 sm:h-14'];

export function Podium({ marches, vide }: { marches: MarcheDePodium[]; vide?: React.ReactNode }) {
    if (marches.length === 0) {
        return <p className="py-8 text-center text-sm text-muted-foreground">{vide ?? 'Personne au classement pour cette période.'}</p>;
    }
    // Deuxieme, premier, troisieme : le premier au centre.
    const ordre = [marches[1], marches[0], marches[2]];

    return (
        <ol className="flex items-end justify-center gap-2 sm:gap-6">
            {ordre.map((m, i) => {
                if (!m) return <li key={`vide-${i}`} className="w-24 sm:w-32" aria-hidden />;
                const place = Math.min(m.rang, 3) - 1;
                const couleur = MEDAILLES[place];
                const premier = m.rang === 1;
                const taille = premier ? 'h-16 w-16 text-xl sm:h-20 sm:w-20 sm:text-2xl' : 'h-12 w-12 text-base sm:h-16 sm:w-16 sm:text-lg';
                const contenu = (
                    <>
                        {premier ? <Crown className="mb-1 h-5 w-5" style={{ color: couleur.fond }} aria-hidden /> : <span className="mb-1 h-5" aria-hidden />}
                        <span
                            className={`relative flex items-center justify-center rounded-full font-black text-white ${taille}`}
                            style={{ background: couleur.fond, boxShadow: `0 5px 0 0 ${couleur.ombre}` }}
                        >
                            {initiales(m.nom)}
                            <span
                                className="absolute -bottom-2 -right-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-card text-[11px] font-black text-white"
                                style={{ background: couleur.fond }}
                            >
                                {m.rang}
                            </span>
                        </span>
                        <span className="mt-3 line-clamp-2 max-w-full text-center text-sm font-bold leading-tight">{m.nom}</span>
                        <span className="mt-0.5 text-sm font-black tabular-nums" style={{ color: couleur.ombre }}>
                            {m.valeur}
                        </span>
                        {m.detail && <span className="text-center text-[11px] leading-tight text-muted-foreground">{m.detail}</span>}
                        {!!m.serie && m.serie > 0 && (
                            <span className="mt-1 inline-flex items-center gap-0.5 text-xs font-semibold text-orange-600 dark:text-orange-400">
                                <Flame className="h-3.5 w-3.5" /> {m.serie} j
                            </span>
                        )}
                        <span
                            className={`mt-2 w-full rounded-t-xl ${HAUTEURS[place]}`}
                            style={{ background: `linear-gradient(180deg, ${couleur.clair} 0%, transparent 100%)`, border: `2px solid ${couleur.clair}`, borderBottom: 'none' }}
                            aria-hidden
                        />
                    </>
                );
                return (
                    <li key={m.id} className="flex w-24 min-w-0 flex-col items-center sm:w-32">
                        {m.lien ? (
                            <Link href={m.lien} className="flex w-full flex-col items-center rounded-xl transition-transform hover:-translate-y-0.5">
                                {contenu}
                            </Link>
                        ) : (
                            contenu
                        )}
                    </li>
                );
            })}
        </ol>
    );
}
