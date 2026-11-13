import { TrendingDown, TrendingUp, type LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { useT, traduire } from '@/lib/i18n/traduire';
import { ilYA } from '@/lib/suivi';
import { cheminCourbe, libelleSemaine, pointsCourbe, type Variation } from '@/lib/tableau-de-classe';
import { cn } from '@/lib/utils';

/*
 * Les petits graphiques des pages du centre (tableau de bord, fiche eleve) :
 * du SVG simple, sans bibliotheque, lisible en theme sombre et a 375 px.
 * Les calculs viennent de StatistiquesCentre (serveur) ; ici, seulement le dessin.
 */

export interface SemaineStat { debut: string; fin: string; exercices: number; precision: number | null; actifs: number; minutes: number }
export interface CompetenceStat { cle: string; libelle: string; exercices: number; precision: number }
export interface JourCalendrier { date: string; exercices: number; minutes: number; futur: boolean }

/** « il y a 3 jours » (ilYA), dans la langue de l'interface. */
export function ilYALangue(iso: string | null, langue: string): string {
    if (langue !== 'en') return ilYA(iso);
    if (!iso) return traduire('Jamais', langue);
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return '—';
    const debut = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
    const relatif = new Intl.RelativeTimeFormat('en-GB', { numeric: 'auto' });
    const jours = Math.round((debut(date) - debut(new Date())) / 86_400_000);
    if (jours > -31) return relatif.format(jours, 'day');
    const mois = Math.round(jours / 30);
    if (mois > -12) return relatif.format(mois, 'month');
    return relatif.format(Math.round(mois / 12), 'year');
}

/** Le libelle d'une semaine (libelleSemaine), dans la langue de l'interface. */
function libelleSemaineLangue(debutIso: string, derniere: boolean, langue: string, locale: string): string {
    if (derniere) return traduire('Cette sem.', langue);
    if (langue !== 'en') return libelleSemaine(debutIso, false);
    const d = new Date(debutIso);
    return Number.isNaN(d.getTime()) ? '' : new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'short' }).format(d);
}

export function couleurReussite(p: number | null): string {
    if (p === null) return 'bg-muted-foreground/40';
    if (p >= 75) return 'bg-emerald-500';
    if (p >= 50) return 'bg-amber-500';
    return 'bg-rose-500';
}

export function Tendance({ v, titre }: { v: Variation | null; titre?: string }) {
    const { t } = useT();
    if (!v) return null;
    const Icone = v.sens === 'hausse' ? TrendingUp : v.sens === 'baisse' ? TrendingDown : null;
    return (
        <span
            title={titre}
            className={cn(
                'inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[11px] font-semibold tabular-nums',
                v.sens === 'hausse' && 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
                v.sens === 'baisse' && 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
                (v.sens === 'stable' || v.sens === 'nouveau') && 'bg-muted text-muted-foreground',
            )}
        >
            {Icone && <Icone className="h-3 w-3" aria-hidden />}
            {t(v.texte)}
        </span>
    );
}

export function Kpi({ icone: Icone, libelle, valeur, detail, tendance }: {
    icone: LucideIcon;
    libelle: string;
    valeur: ReactNode;
    detail?: ReactNode;
    tendance?: ReactNode;
}) {
    return (
        <div className="min-w-0 rounded-xl border border-border bg-card p-3.5">
            <div className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                <Icone className="h-3.5 w-3.5 shrink-0" aria-hidden />
                <span className="truncate">{libelle}</span>
            </div>
            <p className="mt-1.5 text-2xl font-bold tracking-tight tabular-nums">{valeur}</p>
            <div className="mt-1 flex min-h-5 flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                {tendance}
                {detail && <span className="truncate">{detail}</span>}
            </div>
        </div>
    );
}

function Legende({ couleur, trait, children }: { couleur: string; trait?: 'plein' | 'pointille' | 'barre'; children: ReactNode }) {
    return (
        <span className="inline-flex items-center gap-1.5">
            {trait === 'barre' ? (
                <span className={cn('h-2.5 w-2.5 rounded-sm', couleur)} />
            ) : (
                <span className={cn('h-0 w-4 border-t-2', couleur, trait === 'pointille' && 'border-dashed')} />
            )}
            {children}
        </span>
    );
}

const L = 320;
const H = 120;

/**
 * Huit semaines : barres des exercices (ou des minutes) et, par-dessus, la
 * courbe de precision ; une seconde courbe en pointille pour comparer (la
 * moyenne de la classe sur la fiche d'un eleve).
 */
export function CourbeSemaines({ semaines, comparaison = null, barres = 'exercices', libelleComparaison = 'Moyenne de la classe' }: {
    semaines: SemaineStat[];
    comparaison?: SemaineStat[] | null;
    barres?: 'exercices' | 'minutes' | null;
    libelleComparaison?: string;
}) {
    const { t, langue, locale } = useT();
    const n = semaines.length;
    const pas = L / Math.max(1, n);
    const valeurs = barres ? semaines.map((s) => s[barres]) : [];
    const max = Math.max(1, ...valeurs);
    const centre = (i: number) => pas * i + pas / 2;
    const recaler = (pts: ReturnType<typeof pointsCourbe>) => pts.map((p, i) => ({ ...p, x: centre(i) }));
    const courbe = recaler(pointsCourbe(semaines.map((s) => s.precision), L, H, 100));
    const autre = comparaison ? recaler(pointsCourbe(comparaison.map((s) => s.precision), L, H, 100)) : null;
    const vide = semaines.every((s) => s.exercices === 0);

    return (
        <div>
            <div className="mb-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                {barres && <Legende couleur="bg-primary/60" trait="barre">{barres === 'minutes' ? t('Minutes de travail') : t('Exercices faits')}</Legende>}
                <Legende couleur="border-emerald-500">{t('Précision moyenne')}</Legende>
                {autre && <Legende couleur="border-sky-500" trait="pointille">{t(libelleComparaison)}</Legende>}
            </div>
            {vide ? (
                <p className="rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">{t('Aucun exercice sur les huit dernières semaines.')}</p>
            ) : (
                <>
                    <svg viewBox={`0 -8 ${L} ${H + 16}`} className="h-auto w-full overflow-visible" role="img" aria-label={t('Activité et précision sur huit semaines')}>
                        {[0, 50, 100].map((g) => (
                            <g key={g}>
                                <line x1={0} x2={L} y1={H - (g / 100) * H} y2={H - (g / 100) * H} className="stroke-border" strokeDasharray="3 3" />
                                <text x={L} y={H - (g / 100) * H - 2} textAnchor="end" className="fill-muted-foreground text-[8px]">{g} %</text>
                            </g>
                        ))}
                        {barres && semaines.map((s, i) => {
                            const h = s[barres] ? Math.max(3, (s[barres] / max) * H * 0.8) : 0;
                            return (
                                <rect
                                    key={s.debut}
                                    x={centre(i) - Math.min(14, pas * 0.32)}
                                    y={H - h}
                                    width={Math.min(28, pas * 0.64)}
                                    height={h}
                                    rx={3}
                                    className={i === n - 1 ? 'fill-primary' : 'fill-primary/40'}
                                >
                                    <title>{`${s.exercices > 1 ? t('{n} exercices', { n: s.exercices }) : t('{n} exercice', { n: s.exercices })} · ${t('{n} min', { n: s.minutes })}${s.precision !== null ? ` · ${s.precision} %` : ''}`}</title>
                                </rect>
                            );
                        })}
                        {autre && <path d={cheminCourbe(autre)} fill="none" className="stroke-sky-500" strokeWidth={2} strokeDasharray="5 4" strokeLinecap="round" />}
                        <path d={cheminCourbe(courbe)} fill="none" className="stroke-emerald-500" strokeWidth={2.5} strokeLinejoin="round" strokeLinecap="round" />
                        {courbe.map((p, i) => p.y === null ? null : (
                            <circle key={i} cx={p.x} cy={p.y} r={3.5} className="fill-emerald-500 stroke-card" strokeWidth={1.5}>
                                <title>{t('{p} % de précision', { p: semaines[i].precision })}</title>
                            </circle>
                        ))}
                    </svg>
                    <div className="mt-1 flex">
                        {semaines.map((s, i) => (
                            <span key={s.debut} className={cn('flex-1 truncate text-center text-[10px] sm:text-xs', i === n - 1 ? 'font-semibold text-foreground' : 'text-muted-foreground')}>
                                {libelleSemaineLangue(s.debut, i === n - 1, langue, locale)}
                            </span>
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}

/** Le temps de travail de chaque semaine, en barres simples. */
export function BarresMinutes({ semaines }: { semaines: SemaineStat[] }) {
    const { t, langue, locale } = useT();
    const max = Math.max(1, ...semaines.map((s) => s.minutes));
    const n = semaines.length;
    const total = semaines.reduce((t, s) => t + s.minutes, 0);
    return (
        <div>
            <div className="flex h-28 items-end gap-1.5" role="img" aria-label={t('Minutes de travail par semaine')}>
                {semaines.map((s, i) => (
                    <div key={s.debut} className="flex h-full flex-1 flex-col items-center justify-end gap-1">
                        <span className="text-[10px] tabular-nums text-muted-foreground">{s.minutes || ''}</span>
                        <div
                            className={cn('w-full max-w-8 rounded-t-md', i === n - 1 ? 'bg-primary' : 'bg-primary/40')}
                            style={{ height: `${s.minutes ? Math.max(6, (s.minutes / max) * 75) : 2}%` }}
                            title={`${t('{n} min', { n: s.minutes })} · ${s.exercices > 1 ? t('{n} exercices', { n: s.exercices }) : t('{n} exercice', { n: s.exercices })}`}
                        />
                    </div>
                ))}
            </div>
            <div className="mt-1 flex gap-1.5">
                {semaines.map((s, i) => (
                    <span key={s.debut} className={cn('flex-1 truncate text-center text-[10px] sm:text-xs', i === n - 1 ? 'font-semibold text-foreground' : 'text-muted-foreground')}>
                        {libelleSemaineLangue(s.debut, i === n - 1, langue, locale)}
                    </span>
                ))}
            </div>
            <p className="mt-2 text-xs text-muted-foreground">
                {t('En minutes. Moyenne :')} <span className="font-medium text-foreground">{t('{n} min', { n: Math.round(total / Math.max(1, n)) })}</span> {t('par semaine.')}
            </p>
        </div>
    );
}

/** La precision moyenne sur plusieurs semaines, ponderee par le nombre d'exercices. */
export function precisionPonderee(semaines: SemaineStat[] | null | undefined): number | null {
    if (!semaines) return null;
    let somme = 0;
    let n = 0;
    for (const s of semaines) {
        if (s.precision === null || !s.exercices) continue;
        somme += s.precision * s.exercices;
        n += s.exercices;
    }
    return n > 0 ? Math.round((somme / n) * 10) / 10 : null;
}

/** La reussite par competence : une barre par competence, coloree selon le taux. */
export function BarresCompetences({ competences, vide = 'Pas encore d’exercice noté.' }: { competences: CompetenceStat[]; vide?: string }) {
    const { t } = useT();
    if (competences.length === 0) return <p className="text-sm text-muted-foreground">{t(vide)}</p>;
    return (
        <ul className="space-y-2.5">
            {competences.map((c) => (
                <li key={c.cle} className="text-sm">
                    <div className="flex items-baseline justify-between gap-2">
                        <span className="min-w-0 truncate font-medium">{t(c.libelle)}</span>
                        <span className="shrink-0 text-xs tabular-nums text-muted-foreground">
                            <span className="font-semibold text-foreground">{Math.round(c.precision)} %</span> · {c.exercices > 1 ? t('{n} exercices', { n: c.exercices }) : t('{n} exercice', { n: c.exercices })}
                        </span>
                    </div>
                    <div className="mt-1 h-2 overflow-hidden rounded-full bg-muted">
                        <div className={cn('h-full rounded-full', couleurReussite(c.precision))} style={{ width: `${Math.max(2, Math.min(100, c.precision))}%` }} />
                    </div>
                </li>
            ))}
        </ul>
    );
}

/** Exercices par jour de la semaine, lundi d'abord, et le creneau le plus frequent. */
export function HistogrammeJours({ jours, pic }: { jours: { jour: string; exercices: number }[]; pic: { debut: number; fin: number; part: number } | null }) {
    const { t, langue } = useT();
    const max = Math.max(1, ...jours.map((j) => j.exercices));
    const meilleur = jours.reduce((a, b) => (b.exercices > a.exercices ? b : a), jours[0]);
    if (!pic) return <p className="text-sm text-muted-foreground">{t('Pas encore assez d’activité pour voir quand vos élèves travaillent.')}</p>;
    return (
        <div>
            <div className="flex h-24 items-end gap-1.5" role="img" aria-label={t('Exercices par jour de la semaine')}>
                {jours.map((j) => (
                    <div key={j.jour} className="flex h-full flex-1 flex-col items-center justify-end gap-1">
                        <span className="text-[10px] tabular-nums text-muted-foreground">{j.exercices || ''}</span>
                        <div
                            className={cn('w-full max-w-8 rounded-t-md', j === meilleur ? 'bg-primary' : 'bg-primary/35')}
                            style={{ height: `${j.exercices ? Math.max(6, (j.exercices / max) * 75) : 2}%` }}
                            title={`${t(j.jour)} : ${j.exercices > 1 ? t('{n} exercices', { n: j.exercices }) : t('{n} exercice', { n: j.exercices })}`}
                        />
                    </div>
                ))}
            </div>
            <div className="mt-1 flex gap-1.5">
                {jours.map((j) => <span key={j.jour} className="flex-1 text-center text-[10px] text-muted-foreground sm:text-xs">{t(j.jour).slice(0, 3)}</span>)}
            </div>
            <p className="mt-3 text-sm text-muted-foreground">
                {t('Jour le plus travaillé :')} <span className="font-medium text-foreground">{langue === 'en' ? t(meilleur.jour) : meilleur.jour.toLowerCase()}</span>. {t('Créneau le plus fréquent :')}{' '}
                <span className="font-medium text-foreground">{t('{debut} h – {fin} h', { debut: pic.debut, fin: pic.fin })}</span> {t('({part} % des exercices).', { part: pic.part })}
            </p>
        </div>
    );
}

const INITIALES = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
const INITIALES_EN = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];

function intensite(n: number): string {
    if (n === 0) return 'bg-muted';
    if (n === 1) return 'bg-emerald-500/35';
    if (n <= 3) return 'bg-emerald-500/65';
    return 'bg-emerald-500';
}

/** Un carre par jour, une colonne par semaine (du lundi au dimanche), sur huit semaines. */
export function CalendrierRegularite({ jours }: { jours: JourCalendrier[] }) {
    const { t, langue, locale } = useT();
    const FORMAT_JOUR = new Intl.DateTimeFormat(locale, { weekday: 'long', day: 'numeric', month: 'long' });
    const semaines: JourCalendrier[][] = [];
    for (let i = 0; i < jours.length; i += 7) semaines.push(jours.slice(i, i + 7));
    const actifs = jours.filter((j) => j.exercices > 0).length;
    const passes = jours.filter((j) => !j.futur).length;

    return (
        <div>
            <div className="flex gap-1.5">
                <div className="grid grid-rows-7 gap-1 pr-0.5 text-[10px] leading-none text-muted-foreground" aria-hidden>
                    {(langue === 'en' ? INITIALES_EN : INITIALES).map((l, i) => <span key={i} className="flex h-5 items-center sm:h-6">{l}</span>)}
                </div>
                <div className="grid flex-1 gap-1" style={{ gridTemplateColumns: `repeat(${semaines.length}, minmax(0, 1fr))` }} role="img" aria-label={t('{actifs} jours actifs sur {passes}', { actifs, passes })}>
                    {semaines.map((s, k) => (
                        <div key={k} className="grid grid-rows-7 gap-1">
                            {s.map((j) => (
                                <span
                                    key={j.date}
                                    className={cn('h-5 rounded-[4px] sm:h-6', j.futur ? 'border border-dashed border-border bg-transparent' : intensite(j.exercices))}
                                    title={j.futur ? undefined : `${FORMAT_JOUR.format(new Date(j.date + 'T12:00:00'))} : ${j.exercices ? `${j.exercices > 1 ? t('{n} exercices', { n: j.exercices }) : t('{n} exercice', { n: j.exercices })}, ${t('{n} min', { n: j.minutes })}` : t('aucun exercice')}`}
                                />
                            ))}
                        </div>
                    ))}
                </div>
            </div>
            <div className="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                <span><span className="font-semibold text-foreground">{actifs}</span> {actifs > 1 ? t('jours actifs sur {passes}', { passes }) : t('jour actif sur {passes}', { passes })}</span>
                <span className="inline-flex items-center gap-1">
                    {t('Moins')}
                    {[0, 1, 2, 4].map((n) => <span key={n} className={cn('h-3 w-3 rounded-[3px]', intensite(n))} />)}
                    {t('Plus')}
                </span>
            </div>
        </div>
    );
}
