import { Link } from '@inertiajs/react';
import axios from 'axios';
import { CalendarCheck, ClipboardCheck, Clock, Flame, ListChecks, RefreshCw, Target, TrendingDown, TrendingUp, Trophy, Users } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { duree, ilYA } from '@/lib/suivi';
import {
    cheminCourbe,
    ecartPoints,
    initiales,
    libelleSemaine,
    pointsCourbe,
    prenom,
    variation,
    type Variation,
} from '@/lib/tableau-de-classe';
import { cn } from '@/lib/utils';

/*
 * Le tableau de bord d'une classe, sur sa fiche : chiffres de la semaine,
 * activite des six dernieres semaines, reussite par competence, classement.
 * Les donnees viennent de GET center/classes/{id}/tableau (TableauDeClasse).
 */

type Periode = 'semaine' | 'mois' | 'tout';

interface Semaine { debut: string; fin: string; exercices: number; precision: number | null; actifs: number; temps_secondes: number }
interface Competence { cle: string; libelle: string; exercices: number; precision: number }
interface LigneClassement {
    rang: number;
    id: number;
    name: string;
    xp: number;
    exercices: number;
    precision: number | null;
    progression: number | null;
    jours_actifs: number;
    serie: number;
    derniere_activite: string | null;
}
interface Classement { eleves: LigneClassement[]; plus_regulier: number | null; meilleure_progression: number | null }
interface Tableau {
    genere_le: string;
    kpis: {
        eleves_total: number;
        eleves_actifs: number;
        exercices: number;
        exercices_avant: number;
        temps_secondes: number;
        temps_avant_secondes: number;
        precision: number | null;
        precision_avant: number | null;
        devoirs: { rendus: number; attendus: number; devoirs: number } | null;
    };
    semaines: Semaine[];
    competences: Record<Periode, Competence[]>;
    classement: Record<Periode, Classement>;
}

const PERIODES: { cle: Periode; libelle: string; detail: string }[] = [
    { cle: 'semaine', libelle: 'Cette semaine', detail: '7 derniers jours' },
    { cle: 'mois', libelle: 'Ce mois', detail: '30 derniers jours' },
    { cle: 'tout', libelle: 'Depuis le début', detail: 'depuis l’arrivée des élèves' },
];

// Or, argent, bronze : les memes que la page Classement des apprenants.
const MEDAILLES = [
    { fond: '#F5A623', ombre: '#d48b10', nom: 'Or' },
    { fond: '#A8A9AD', ombre: '#7d7e82', nom: 'Argent' },
    { fond: '#CD7F32', ombre: '#9a5e20', nom: 'Bronze' },
];

function couleurReussite(p: number | null): string {
    if (p === null) return 'bg-muted-foreground/40';
    if (p >= 75) return 'bg-emerald-500';
    if (p >= 50) return 'bg-amber-500';
    return 'bg-rose-500';
}

function Tendance({ v, titre }: { v: Variation | null; titre?: string }) {
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
            {v.texte}
        </span>
    );
}

function Kpi({ icone: Icone, libelle, valeur, detail, tendance }: {
    icone: typeof Users;
    libelle: string;
    valeur: React.ReactNode;
    detail?: React.ReactNode;
    tendance?: React.ReactNode;
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

function ChoixPeriode({ valeur, onChange }: { valeur: Periode; onChange: (p: Periode) => void }) {
    return (
        <div role="tablist" aria-label="Période" className="inline-flex max-w-full rounded-lg border border-border bg-muted/50 p-0.5 text-xs">
            {PERIODES.map((p) => (
                <button
                    key={p.cle}
                    type="button"
                    role="tab"
                    aria-selected={valeur === p.cle}
                    onClick={() => onChange(p.cle)}
                    className={cn(
                        'whitespace-nowrap rounded-md px-2 py-1 font-medium transition-colors sm:px-2.5',
                        valeur === p.cle ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    {p.libelle}
                </button>
            ))}
        </div>
    );
}

function Avatar({ nom, rang, taille = 'md' }: { nom: string; rang: number | null; taille?: 'sm' | 'md' | 'lg' }) {
    const medaille = rang !== null && rang <= 3 ? MEDAILLES[rang - 1] : null;
    const dims = taille === 'lg' ? 'h-16 w-16 text-xl' : taille === 'md' ? 'h-12 w-12 text-base' : 'h-8 w-8 text-xs';
    return (
        <div
            className={cn('flex shrink-0 items-center justify-center rounded-full font-bold', dims, !medaille && 'bg-muted text-foreground')}
            style={medaille ? { background: medaille.fond, color: '#fff', boxShadow: `0 3px 0 0 ${medaille.ombre}` } : undefined}
            aria-hidden
        >
            {initiales(nom)}
        </div>
    );
}

function Courbe({ semaines }: { semaines: Semaine[] }) {
    const max = Math.max(1, ...semaines.map((s) => s.exercices));
    const points = pointsCourbe(semaines.map((s) => s.precision), 100, 100, 100);
    const derniere = semaines.length - 1;

    return (
        <div>
            <div className="mb-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                <span className="inline-flex items-center gap-1.5"><span className="h-2.5 w-2.5 rounded-sm bg-primary/70" />Exercices faits</span>
                <span className="inline-flex items-center gap-1.5"><span className="h-0.5 w-4 rounded bg-emerald-500" />Précision moyenne</span>
            </div>
            <div className="relative h-40">
                {/* Lignes de repere : 0, 50 et 100 % de precision. */}
                {[0, 50, 100].map((g) => (
                    <div key={g} className="pointer-events-none absolute inset-x-0 border-t border-dashed border-border" style={{ bottom: `${g}%` }}>
                        <span className="absolute -top-2 right-0 bg-card pl-1 text-[10px] text-muted-foreground">{g} %</span>
                    </div>
                ))}
                <div className="absolute inset-0 flex items-end pr-8">
                    {semaines.map((s, i) => (
                        <div key={s.debut} className="flex h-full flex-1 flex-col items-center justify-end px-1 sm:px-1.5">
                            <span className="mb-1 text-[11px] font-semibold tabular-nums text-muted-foreground">{s.exercices || ''}</span>
                            <div
                                className={cn('w-full max-w-10 rounded-t-md', i === derniere ? 'bg-primary' : 'bg-primary/40')}
                                style={{ height: `${Math.max(s.exercices ? 4 : 0, (s.exercices / max) * 80)}%` }}
                                title={`${s.exercices} exercice${s.exercices > 1 ? 's' : ''} · ${s.actifs} élève${s.actifs > 1 ? 's' : ''} actif${s.actifs > 1 ? 's' : ''}`}
                            />
                        </div>
                    ))}
                </div>
                {/* La precision, par-dessus : meme repere horizontal que les barres. */}
                <div className="pointer-events-none absolute inset-y-0 left-0 right-8">
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" className="absolute inset-0 h-full w-full overflow-visible text-emerald-500" aria-hidden>
                        <path
                            d={cheminCourbe(points.map((p) => ({ ...p, x: (p.x / 100) * ((semaines.length - 1) / semaines.length) * 100 + 50 / semaines.length })))}
                            fill="none"
                            stroke="currentColor"
                            strokeWidth={2.5}
                            strokeLinejoin="round"
                            strokeLinecap="round"
                            vectorEffect="non-scaling-stroke"
                        />
                    </svg>
                    {points.map((p, i) =>
                        p.y === null ? null : (
                            <span
                                key={i}
                                className="absolute h-2.5 w-2.5 -translate-x-1/2 translate-y-1/2 rounded-full border-2 border-card bg-emerald-500"
                                style={{ left: `${((i + 0.5) / semaines.length) * 100}%`, bottom: `${100 - p.y}%` }}
                                title={`${semaines[i].precision} % de précision`}
                            />
                        ),
                    )}
                </div>
            </div>
            <div className="mt-2 flex pr-8">
                {semaines.map((s, i) => (
                    <span key={s.debut} className={cn('flex-1 truncate text-center text-[10px] sm:text-xs', i === derniere ? 'font-semibold text-foreground' : 'text-muted-foreground')}>
                        {libelleSemaine(s.debut, i === derniere)}
                    </span>
                ))}
            </div>
        </div>
    );
}

function Podium({ eleves }: { eleves: LigneClassement[] }) {
    // 2e, 1er, 3e : le premier au centre, plus haut.
    const ordre = [eleves[1], eleves[0], eleves[2]];
    const hauteurs = ['h-14', 'h-20', 'h-10'];
    return (
        <div className="flex items-end justify-center gap-2 pb-1 sm:gap-5">
            {ordre.map((e, i) => {
                if (!e) return <div key={i} className="w-[5.5rem] sm:w-28" />;
                const premier = i === 1;
                const medaille = MEDAILLES[Math.min(e.rang, 3) - 1];
                return (
                    <div key={e.id} className="flex w-[5.5rem] min-w-0 flex-col items-center sm:w-28">
                        {premier && <Trophy className="mb-1 h-5 w-5" style={{ color: MEDAILLES[0].fond }} aria-hidden />}
                        <div className="relative">
                            <Avatar nom={e.name} rang={e.rang} taille={premier ? 'lg' : 'md'} />
                            <span
                                className="absolute -bottom-1.5 -right-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-card text-[11px] font-bold text-white"
                                style={{ background: medaille.fond }}
                                aria-label={`Médaille ${medaille.nom.toLowerCase()}`}
                            >
                                {e.rang}
                            </span>
                        </div>
                        <Link href={route('center.students.show', e.id)} className="mt-2.5 max-w-full truncate text-sm font-semibold hover:underline">
                            {prenom(e.name)}
                        </Link>
                        <p className="text-xs font-medium tabular-nums text-muted-foreground">{e.xp} XP</p>
                        <div
                            className={cn('mt-2 w-full rounded-t-xl border border-b-0', hauteurs[i])}
                            style={{ background: `${medaille.fond}22`, borderColor: `${medaille.fond}55` }}
                        />
                    </div>
                );
            })}
        </div>
    );
}

function Squelette() {
    return (
        <div className="space-y-5" aria-busy="true" aria-label="Chargement du tableau de la classe">
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                {[0, 1, 2, 3].map((i) => <Skeleton key={i} className="h-[6.5rem] rounded-xl" />)}
            </div>
            <Skeleton className="h-60 rounded-xl" />
            <Skeleton className="h-48 rounded-xl" />
            <Skeleton className="h-80 rounded-xl" />
        </div>
    );
}

export default function TableauDeClasse({ classroomId }: { classroomId: number }) {
    const [donnees, setDonnees] = useState<Tableau | null>(null);
    const [erreur, setErreur] = useState(false);
    const [periode, setPeriode] = useState<Periode>('semaine');

    const charger = useCallback(() => {
        setErreur(false);
        axios
            .get<Tableau>(route('center.classes.tableau', classroomId))
            .then((r) => setDonnees(r.data))
            .catch(() => setErreur(true));
    }, [classroomId]);

    useEffect(() => charger(), [charger]);

    if (erreur) {
        return (
            <Card>
                <CardContent className="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                    <span className="text-muted-foreground">Le tableau de la classe n’a pas pu être chargé.</span>
                    <Button variant="outline" size="sm" onClick={charger}><RefreshCw className="mr-1.5 h-3.5 w-3.5" />Réessayer</Button>
                </CardContent>
            </Card>
        );
    }
    if (!donnees) return <Squelette />;

    const { kpis, semaines } = donnees;
    const classement = donnees.classement[periode];
    const competences = donnees.competences[periode];
    const detailPeriode = PERIODES.find((p) => p.cle === periode)!.detail;
    const actifsDuClassement = classement.eleves.filter((e) => e.exercices > 0);
    const podium = actifsDuClassement.slice(0, 3);
    const devoirs = kpis.devoirs;
    const tauxDevoirs = devoirs && devoirs.attendus > 0 ? Math.round((devoirs.rendus / devoirs.attendus) * 100) : null;
    const ecartPrecision = kpis.precision !== null && kpis.precision_avant !== null ? kpis.precision - kpis.precision_avant : null;

    return (
        <div className="space-y-5">
            {/* Chiffres de la semaine */}
            <section aria-label="Chiffres de la semaine" className="space-y-3">
                <div className="flex items-baseline justify-between gap-3">
                    <h2 className="text-base font-semibold">Cette semaine</h2>
                    <span className="text-xs text-muted-foreground">7 derniers jours, comparés aux 7 d’avant</span>
                </div>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <Kpi
                        icone={Users}
                        libelle="Élèves actifs"
                        valeur={<>{kpis.eleves_actifs}<span className="text-base font-medium text-muted-foreground"> / {kpis.eleves_total}</span></>}
                        detail={kpis.eleves_total > 0 ? `${Math.round((kpis.eleves_actifs / kpis.eleves_total) * 100)} % de la classe` : 'Aucun élève'}
                    />
                    <Kpi
                        icone={ListChecks}
                        libelle="Exercices faits"
                        valeur={kpis.exercices}
                        tendance={<Tendance v={variation(kpis.exercices, kpis.exercices_avant)} titre={`${kpis.exercices_avant} la semaine précédente`} />}
                        detail="vs sem. préc."
                    />
                    <Kpi
                        icone={Clock}
                        libelle="Temps de travail"
                        valeur={duree(kpis.temps_secondes)}
                        tendance={<Tendance v={variation(kpis.temps_secondes, kpis.temps_avant_secondes)} titre={`${duree(kpis.temps_avant_secondes)} la semaine précédente`} />}
                        detail="vs sem. préc."
                    />
                    <Kpi
                        icone={Target}
                        libelle="Précision moyenne"
                        valeur={kpis.precision !== null ? `${Math.round(kpis.precision)} %` : '—'}
                        tendance={<Tendance v={ecartPoints(ecartPrecision)} titre="Écart en points avec la semaine précédente" />}
                        detail={kpis.precision === null ? 'Pas d’exercice noté' : 'vs sem. préc.'}
                    />
                </div>
                {devoirs && (
                    <div className="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl border border-border bg-card p-3.5 text-sm">
                        <span className="inline-flex items-center gap-1.5 font-medium">
                            <ClipboardCheck className="h-4 w-4 text-muted-foreground" aria-hidden />
                            Devoirs rendus à temps
                        </span>
                        <div className="h-2 min-w-24 flex-1 overflow-hidden rounded-full bg-muted">
                            <div className={cn('h-full rounded-full', couleurReussite(tauxDevoirs))} style={{ width: `${tauxDevoirs ?? 0}%` }} />
                        </div>
                        <span className="tabular-nums">
                            <span className="font-semibold">{tauxDevoirs !== null ? `${tauxDevoirs} %` : '—'}</span>
                            <span className="text-muted-foreground"> · {devoirs.rendus} / {devoirs.attendus} sur {devoirs.devoirs} devoir{devoirs.devoirs > 1 ? 's' : ''} échu{devoirs.devoirs > 1 ? 's' : ''}</span>
                        </span>
                    </div>
                )}
            </section>

            {/* Activite des six dernieres semaines */}
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-base">Activité des 6 dernières semaines</CardTitle>
                </CardHeader>
                <CardContent>
                    {semaines.every((s) => s.exercices === 0) ? (
                        <p className="py-6 text-center text-sm text-muted-foreground">Aucun exercice fait par la classe ces six dernières semaines.</p>
                    ) : (
                        <Courbe semaines={semaines} />
                    )}
                </CardContent>
            </Card>

            {/* Reussite par competence */}
            <Card>
                <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-2 space-y-0 pb-3">
                    <CardTitle className="text-base">Réussite par compétence</CardTitle>
                    <ChoixPeriode valeur={periode} onChange={setPeriode} />
                </CardHeader>
                <CardContent>
                    {competences.length === 0 ? (
                        <p className="py-4 text-center text-sm text-muted-foreground">Aucun exercice noté sur cette période ({detailPeriode}).</p>
                    ) : (
                        <ul className="space-y-3">
                            {competences.map((c) => (
                                <li key={c.cle} className="grid grid-cols-[minmax(0,8.5rem)_1fr_auto] items-center gap-3 text-sm sm:grid-cols-[11rem_1fr_auto]">
                                    <span className="truncate font-medium" title={c.libelle}>{c.libelle}</span>
                                    <div className="h-2.5 overflow-hidden rounded-full bg-muted">
                                        <div className={cn('h-full rounded-full', couleurReussite(c.precision))} style={{ width: `${Math.max(2, c.precision)}%` }} />
                                    </div>
                                    <span className="w-20 text-right tabular-nums">
                                        <span className="font-semibold">{Math.round(c.precision)} %</span>
                                        <span className="ml-1 text-xs text-muted-foreground">({c.exercices})</span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>

            {/* Classement de la classe */}
            <Card>
                <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-2 space-y-0 pb-3">
                    <div>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Trophy className="h-4 w-4 text-amber-500" aria-hidden />
                            Classement de la classe
                        </CardTitle>
                        <p className="mt-0.5 text-xs text-muted-foreground">XP gagnés en exercices · {detailPeriode}</p>
                    </div>
                    <ChoixPeriode valeur={periode} onChange={setPeriode} />
                </CardHeader>
                <CardContent className="space-y-5">
                    {classement.eleves.length === 0 ? (
                        <p className="py-4 text-center text-sm text-muted-foreground">Aucun élève n’a encore rejoint cette classe.</p>
                    ) : (
                        <>
                            {podium.length > 0 ? (
                                <Podium eleves={podium} />
                            ) : (
                                <p className="py-2 text-center text-sm text-muted-foreground">Personne n’a encore fait d’exercice sur cette période.</p>
                            )}

                            <div className="overflow-hidden rounded-xl border border-border">
                                <div className="hidden grid-cols-[2.5rem_minmax(0,1fr)_4.5rem_4rem_6.5rem_3.5rem] gap-2 border-b border-border bg-muted/40 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground sm:grid">
                                    <span>Rang</span>
                                    <span>Élève</span>
                                    <span className="text-right">XP</span>
                                    <span className="text-right">Exerc.</span>
                                    <span className="text-right">Précision</span>
                                    <span className="text-right">Série</span>
                                </div>
                                <ol className="divide-y divide-border">
                                    {classement.eleves.map((e) => {
                                        const actif = e.exercices > 0;
                                        const regulier = classement.plus_regulier === e.id;
                                        const progresse = classement.meilleure_progression === e.id;
                                        const ecart = ecartPoints(e.progression);
                                        return (
                                            <li
                                                key={e.id}
                                                className={cn(
                                                    'grid grid-cols-[2rem_minmax(0,1fr)_auto] items-center gap-2 px-3 py-2.5 text-sm sm:grid-cols-[2.5rem_minmax(0,1fr)_4.5rem_4rem_6.5rem_3.5rem]',
                                                    !actif && 'text-muted-foreground',
                                                )}
                                            >
                                                <span className={cn('text-center font-bold tabular-nums sm:text-left', actif && e.rang <= 3 && 'text-foreground')}>
                                                    {actif ? e.rang : '—'}
                                                </span>
                                                <div className="flex min-w-0 items-center gap-2.5">
                                                    <Avatar nom={e.name} rang={actif ? e.rang : null} taille="sm" />
                                                    <div className="min-w-0">
                                                        <Link href={route('center.students.show', e.id)} className={cn('block truncate font-medium hover:underline', actif && 'text-foreground')}>
                                                            {e.name}
                                                        </Link>
                                                        <div className="flex flex-wrap items-center gap-1 pt-0.5">
                                                            {regulier && (
                                                                <span className="inline-flex items-center gap-1 rounded-full bg-sky-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-sky-700 dark:text-sky-300" title={`${e.jours_actifs} jours d’activité sur la période`}>
                                                                    <CalendarCheck className="h-3 w-3" aria-hidden />Plus régulier
                                                                </span>
                                                            )}
                                                            {progresse && (
                                                                <span className="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 dark:text-emerald-300">
                                                                    <TrendingUp className="h-3 w-3" aria-hidden />Meilleure progression
                                                                </span>
                                                            )}
                                                            {/* Sur telephone, les colonnes se lisent ici. */}
                                                            <span className="text-xs text-muted-foreground sm:hidden">
                                                                {actif
                                                                    ? `${e.exercices} ex. · ${e.precision !== null ? `${Math.round(e.precision)} %` : '—'}${e.serie > 0 ? ` · ${e.serie} j` : ''}`
                                                                    : e.derniere_activite ? `Dernière activité ${ilYA(e.derniere_activite)}` : 'Jamais venu'}
                                                            </span>
                                                            {!actif && (
                                                                <span className="hidden text-xs text-muted-foreground sm:inline">
                                                                    {e.derniere_activite ? `Dernière activité ${ilYA(e.derniere_activite)}` : 'Jamais venu'}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                                <span className={cn('text-right font-semibold tabular-nums', actif && 'text-amber-600 dark:text-amber-400')}>
                                                    {e.xp}<span className="text-xs font-normal text-muted-foreground sm:hidden"> XP</span>
                                                </span>
                                                <span className="hidden text-right tabular-nums sm:block">{e.exercices}</span>
                                                <span className="hidden flex-col items-end gap-0.5 tabular-nums sm:flex">
                                                    <span>{e.precision !== null ? `${Math.round(e.precision)} %` : '—'}</span>
                                                    {ecart && ecart.sens !== 'stable' && <Tendance v={ecart} titre="Écart avec la période précédente" />}
                                                </span>
                                                <span className="hidden items-center justify-end gap-0.5 tabular-nums sm:flex" title="Jours d’affilée">
                                                    {e.serie > 0 ? (<><Flame className="h-3.5 w-3.5 text-orange-500" aria-hidden />{e.serie}</>) : '—'}
                                                </span>
                                            </li>
                                        );
                                    })}
                                </ol>
                            </div>
                        </>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
