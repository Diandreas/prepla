import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CalendarCheck, ClipboardList, Clock, ListChecks, Plus, Target, TrendingDown, TrendingUp, UserPlus, Users } from 'lucide-react';
import { dateHeure, duree, ilYA } from '@/lib/suivi';
import { Drapeau } from '@/components/center/barre-classement';
import { activiteCoherente, pluriel } from '@/lib/classement-centre';
import { ecartPoints, variation } from '@/lib/tableau-de-classe';
import {
    BarresCompetences,
    CourbeSemaines,
    HistogrammeJours,
    Kpi,
    Tendance,
    type CompetenceStat,
    type SemaineStat,
} from '@/components/center/statistiques';

interface Center {
    id: number;
    name: string;
    seats_limit: number;
    seats_used: number;
    classrooms_count: number;
}
interface Semaine { eleves: number; actifs: number; exercices: number; minutes: number }
interface Classe { id: number; name: string; level: string | null; enseignants: string[]; eleves: number; derniere_activite: string | null }
interface ARelancer { id: number; name: string; derniere_activite: string | null }
interface ActifsMois { eleves: number; palier: string; plafond?: number | null }
interface ClasseActivite { id: number; name: string; eleves: number; exercices: number; avant: number; actifs: number; precision: number | null }
interface Statistiques {
    semaines: SemaineStat[];
    competences: CompetenceStat[];
    devoirs: { rendus: number; attendus: number; rendus_avant: number; attendus_avant: number };
    rythme: { jours: { jour: string; exercices: number }[]; heures: number[]; pic: { debut: number; fin: number; part: number } | null };
    classes_actives: ClasseActivite[];
    classes_decrochent: ClasseActivite[];
}

function taux(n: number, sur: number): number | null {
    return sur > 0 ? Math.round((n / sur) * 100) : null;
}

/** Une classe dans « les plus actives » ou « qui decrochent ». */
function LigneClasse({ k, decroche }: { k: ClasseActivite; decroche?: boolean }) {
    const v = variation(k.exercices, k.avant);
    return (
        <Link
            href={route('center.classes.show', k.id)}
            className="flex items-center justify-between gap-3 rounded-lg border border-border px-3 py-2 text-sm transition hover:border-primary/40 hover:bg-muted/40"
        >
            <span className="min-w-0">
                <span className="block truncate font-medium">{k.name}</span>
                <span className="block text-xs text-muted-foreground">
                    {k.actifs} / {k.eleves} actif{k.actifs > 1 ? 's' : ''}
                    {k.precision !== null && <> · {Math.round(k.precision)} % de réussite</>}
                </span>
            </span>
            <span className="flex shrink-0 flex-col items-end gap-0.5">
                <span className="text-xs tabular-nums text-muted-foreground">
                    {k.exercices} exo{decroche ? <> (avant {k.avant})</> : <> · {(k.exercices / Math.max(1, k.eleves)).toFixed(1).replace('.', ',')}/élève</>}
                </span>
                <Tendance v={v} titre="Exercices, comparés aux 7 jours d'avant" />
            </span>
        </Link>
    );
}
interface LigneRepartition { cle: string; libelle: string; drapeau?: string | null; classes: number; eleves: number }
interface Repartition { langues: LigneRepartition[]; niveaux: LigneRepartition[] }

/**
 * Une colonne de la repartition : une ligne par langue (ou niveau), une barre
 * proportionnelle au nombre d'eleves, et un lien vers la page Eleves filtree.
 */
function Colonne({ titre, filtre, lignes }: { titre: string; filtre: 'langue' | 'niveau'; lignes: LigneRepartition[] }) {
    const max = Math.max(1, ...lignes.map((l) => l.eleves));
    return (
        <div className="min-w-0 space-y-1.5">
            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{titre}</p>
            {lignes.length === 0 && <p className="text-sm text-muted-foreground">Rien à répartir pour le moment.</p>}
            {lignes.map((l) => (
                <Link
                    key={l.cle}
                    href={route('center.students.index', { [filtre]: l.cle })}
                    className="block rounded-md px-2 py-1.5 text-sm transition hover:bg-muted/50"
                >
                    <span className="flex items-center justify-between gap-2">
                        <span className="flex min-w-0 items-center gap-1.5 font-medium">
                            {l.drapeau && <Drapeau emoji={l.drapeau} />}
                            <span className="truncate">{l.libelle}</span>
                        </span>
                        <span className="shrink-0 text-xs tabular-nums text-muted-foreground">
                            {pluriel(l.eleves, 'élève')} · {pluriel(l.classes, 'classe')}
                        </span>
                    </span>
                    <span className="mt-1 block h-1.5 overflow-hidden rounded-full bg-muted">
                        <span className="block h-full rounded-full bg-primary/70" style={{ width: `${(l.eleves / max) * 100}%` }} />
                    </span>
                </Link>
            ))}
        </div>
    );
}

function Stat({ label, value, detail, href }: { label: string; value: React.ReactNode; detail?: string; href?: string }) {
    const inner = (
        <Card className="h-full transition-colors hover:bg-muted/30">
            <CardContent className="p-4">
                <p className="text-2xl font-bold tabular-nums">{value}</p>
                <p className="text-sm text-muted-foreground">{label}</p>
                {detail && <p className="mt-0.5 text-xs text-muted-foreground">{detail}</p>}
            </CardContent>
        </Card>
    );
    return href ? <Link href={href}>{inner}</Link> : inner;
}

/** Une classe qui ne travaille plus depuis une semaine se voit d'un coup d'oeil. */
function etat(derniere: string | null): { texte: string; classe: string } {
    if (!derniere) return { texte: 'Aucune activité', classe: 'text-muted-foreground' };
    const jours = (Date.now() - new Date(derniere).getTime()) / 86_400_000;
    if (jours > 7) return { texte: `Inactive · ${ilYA(derniere)}`, classe: 'text-amber-600 dark:text-amber-400' };
    return { texte: `Active · ${ilYA(derniere)}`, classe: 'text-emerald-600 dark:text-emerald-400' };
}

/**
 * Le point de depart d'un responsable ou d'un enseignant : la semaine a-t-elle
 * ete travaillee, quelle classe decroche, qui relancer — et les gestes courants
 * a portee de main. Il ne donnait que trois chiffres.
 */
export default function CenterDashboard({
    center,
    semaine,
    classes,
    a_relancer,
    actifs_mois = null,
    repartition = { langues: [], niveaux: [] },
    joursAvantRelance = 7,
    statistiques = null,
}: {
    center: Center;
    semaine: Semaine;
    classes: Classe[];
    a_relancer: ARelancer[];
    actifs_mois?: ActifsMois | null;
    repartition?: Repartition;
    joursAvantRelance?: number;
    statistiques?: Statistiques | null;
}) {
    const { auth } = usePage().props as unknown as { auth?: { center?: { role?: string } } };
    const responsable = auth?.center?.role === 'center_admin';

    const s = statistiques;
    const courante = s?.semaines[s.semaines.length - 1] ?? null;
    const precedente = s && s.semaines.length > 1 ? s.semaines[s.semaines.length - 2] : null;
    const tauxDevoirs = s ? taux(s.devoirs.rendus, s.devoirs.attendus) : null;
    const tauxDevoirsAvant = s ? taux(s.devoirs.rendus_avant, s.devoirs.attendus_avant) : null;

    return (
        <AppLayout>
            <Head title={center.name} />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{center.name}</h1>
                        <p className="text-sm text-muted-foreground">
                            {responsable ? 'Tableau de bord du centre' : 'Mes classes dans ce centre'}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild size="sm" variant="outline">
                            <Link href={route('center.assignments.create')}><ClipboardList className="mr-1.5 h-4 w-4" /> Donner un devoir</Link>
                        </Button>
                        {responsable && (
                            <>
                                <Button asChild size="sm" variant="outline">
                                    <Link href={route('center.classes.index')}><Plus className="mr-1.5 h-4 w-4" /> Nouvelle classe</Link>
                                </Button>
                                <Button asChild size="sm">
                                    <Link href={route('center.equipe')}><UserPlus className="mr-1.5 h-4 w-4" /> Équipe</Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                {s && courante ? (
                    <section aria-label="Chiffres de la semaine" className="space-y-3">
                        <div className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
                            <h2 className="text-base font-semibold">Cette semaine</h2>
                            <span className="text-xs text-muted-foreground">7 derniers jours, comparés aux 7 d’avant</span>
                        </div>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                            <Link href={route('center.students.index')} className="block min-w-0 rounded-xl transition hover:opacity-90">
                                <Kpi
                                    icone={Users}
                                    libelle="Élèves actifs"
                                    valeur={<>{courante.actifs}<span className="text-base font-medium text-muted-foreground"> / {semaine.eleves}</span></>}
                                    tendance={<Tendance v={variation(courante.actifs, precedente?.actifs ?? 0)} titre="Comparé aux 7 jours d'avant" />}
                                    detail={semaine.eleves > 0 ? `${taux(courante.actifs, semaine.eleves)} %` : 'Aucun élève'}
                                />
                            </Link>
                            <Kpi
                                icone={ListChecks}
                                libelle="Exercices faits"
                                valeur={courante.exercices}
                                tendance={<Tendance v={variation(courante.exercices, precedente?.exercices ?? 0)} />}
                                detail={precedente ? `avant : ${precedente.exercices}` : undefined}
                            />
                            <Kpi
                                icone={Clock}
                                libelle="Temps de travail"
                                valeur={duree(courante.minutes * 60)}
                                tendance={<Tendance v={variation(courante.minutes, precedente?.minutes ?? 0)} />}
                                detail={precedente ? `avant : ${duree(precedente.minutes * 60)}` : undefined}
                            />
                            <Kpi
                                icone={Target}
                                libelle="Précision moyenne"
                                valeur={courante.precision !== null ? `${Math.round(courante.precision)} %` : '—'}
                                tendance={<Tendance v={courante.precision !== null && precedente?.precision != null ? ecartPoints(courante.precision - precedente.precision) : null} />}
                                detail={precedente?.precision != null ? `avant : ${Math.round(precedente.precision)} %` : undefined}
                            />
                            <Kpi
                                icone={CalendarCheck}
                                libelle="Devoirs rendus à temps"
                                valeur={tauxDevoirs !== null ? `${tauxDevoirs} %` : '—'}
                                tendance={<Tendance v={tauxDevoirs !== null && tauxDevoirsAvant !== null ? ecartPoints(tauxDevoirs - tauxDevoirsAvant) : null} />}
                                detail={s.devoirs.attendus > 0 ? `${s.devoirs.rendus} / ${s.devoirs.attendus} copies` : 'aucune échéance'}
                            />
                        </div>
                    </section>
                ) : (
                    <div className="grid grid-cols-2 gap-3">
                        <Stat label="Élèves actifs cette semaine" value={`${semaine.actifs} / ${semaine.eleves}`} href={route('center.students.index')} />
                        <Stat label="Exercices cette semaine" value={semaine.exercices} detail={`${duree(semaine.minutes * 60)} de travail`} />
                    </div>
                )}

                <div className={`grid grid-cols-2 gap-3 ${responsable && actifs_mois ? 'lg:grid-cols-3' : ''}`}>
                    <Stat label="Classes" value={center.classrooms_count} href={route('center.classes.index')} />
                    <Stat
                        label="Places occupées"
                        value={`${center.seats_used} / ${center.seats_limit}`}
                        detail={`${Math.max(0, center.seats_limit - center.seats_used)} restantes`}
                    />
                    {responsable && actifs_mois && (
                        <Card className="col-span-2 lg:col-span-1">
                            <CardContent className="p-4">
                                <div className="flex items-baseline justify-between gap-2">
                                    <p className="text-2xl font-bold tabular-nums">{actifs_mois.eleves}</p>
                                    <span className="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">
                                        Palier {actifs_mois.palier === 'reseau' ? 'réseau' : actifs_mois.palier}
                                    </span>
                                </div>
                                <p className="text-sm text-muted-foreground">Élèves actifs ce mois-ci</p>
                                {actifs_mois.plafond ? (
                                    <>
                                        <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                                            <div className="h-full rounded-full bg-primary" style={{ width: `${Math.min(100, (actifs_mois.eleves / actifs_mois.plafond) * 100)}%` }} />
                                        </div>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {Math.max(0, actifs_mois.plafond - actifs_mois.eleves)} avant le plafond du palier ({actifs_mois.plafond})
                                        </p>
                                    </>
                                ) : null}
                            </CardContent>
                        </Card>
                    )}
                </div>

                {s && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Activité sur 8 semaines</CardTitle>
                            <p className="text-xs text-muted-foreground">Exercices faits chaque semaine, et la précision moyenne de vos élèves.</p>
                        </CardHeader>
                        <CardContent>
                            <CourbeSemaines semaines={s.semaines} />
                        </CardContent>
                    </Card>
                )}

                {s && (
                    <div className="grid gap-5 md:grid-cols-2">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">Réussite par compétence</CardTitle>
                                <p className="text-xs text-muted-foreground">Sur les 8 dernières semaines.</p>
                            </CardHeader>
                            <CardContent>
                                <BarresCompetences competences={s.competences} />
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">Quand vos élèves travaillent</CardTitle>
                                <p className="text-xs text-muted-foreground">Exercices par jour de la semaine, à l’heure du centre, sur 8 semaines.</p>
                            </CardHeader>
                            <CardContent>
                                <HistogrammeJours jours={s.rythme.jours} pic={s.rythme.pic} />
                            </CardContent>
                        </Card>
                    </div>
                )}

                {s && (s.classes_actives.length > 0 || s.classes_decrochent.length > 0) && (
                    <div className="grid gap-5 md:grid-cols-2">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center gap-1.5 text-base">
                                    <TrendingUp className="h-4 w-4 text-emerald-500" aria-hidden /> Classes les plus actives
                                </CardTitle>
                                <p className="text-xs text-muted-foreground">Le plus d’exercices par élève cette semaine.</p>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {s.classes_actives.length === 0 && <p className="text-sm text-muted-foreground">Aucune classe n’a travaillé cette semaine.</p>}
                                {s.classes_actives.map((k) => <LigneClasse key={k.id} k={k} />)}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center gap-1.5 text-base">
                                    <TrendingDown className="h-4 w-4 text-rose-500" aria-hidden /> Classes qui décrochent
                                </CardTitle>
                                <p className="text-xs text-muted-foreground">Au moins un tiers d’exercices en moins que la semaine d’avant.</p>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {s.classes_decrochent.length === 0 && <p className="text-sm text-muted-foreground">Aucune classe en baisse : bonne nouvelle.</p>}
                                {s.classes_decrochent.map((k) => <LigneClasse key={k.id} k={k} decroche />)}
                            </CardContent>
                        </Card>
                    </div>
                )}

                {a_relancer.length > 0 && (
                    <Card className="border-amber-300/70 dark:border-amber-900">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">À relancer</CardTitle>
                            <p className="text-xs text-muted-foreground">Jamais venus, ou sans exercice depuis plus d'une semaine.</p>
                        </CardHeader>
                        <CardContent className="grid gap-2 sm:grid-cols-2">
                            {a_relancer.map((e) => (
                                <Link
                                    key={e.id}
                                    href={route('center.students.show', e.id)}
                                    className="flex items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm transition hover:border-primary/40 hover:bg-muted/40"
                                >
                                    <span className="truncate font-medium">{e.name}</span>
                                    <span className="shrink-0 text-xs text-muted-foreground" title={dateHeure(e.derniere_activite)}>
                                        {e.derniere_activite ? activiteCoherente(e.derniere_activite, 'a_relancer', joursAvantRelance, ilYA) : 'jamais venu'}
                                    </span>
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {(repartition.langues.length > 0 || repartition.niveaux.length > 0) && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Répartition</CardTitle>
                            <p className="text-xs text-muted-foreground">Par langue et par niveau. Cliquez une ligne pour voir ces élèves.</p>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2">
                            <Colonne titre="Par langue" filtre="langue" lignes={repartition.langues} />
                            <Colonne titre="Par niveau" filtre="niveau" lignes={repartition.niveaux} />
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">{responsable ? 'Les classes' : 'Mes classes'}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {classes.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                {responsable ? 'Aucune classe pour le moment.' : "Aucune classe ne t'est encore confiée dans ce centre."}
                            </p>
                        )}
                        {classes.map((k) => {
                            const e = etat(k.derniere_activite);
                            return (
                                <Link
                                    key={k.id}
                                    href={route('center.classes.show', k.id)}
                                    className="flex flex-wrap items-center justify-between gap-x-3 gap-y-0.5 rounded-md border px-3 py-2.5 text-sm transition hover:border-primary/40 hover:bg-muted/40"
                                >
                                    <span className="min-w-0">
                                        <span className="font-medium">{k.name}</span>
                                        {k.level && <span className="text-muted-foreground"> · {k.level}</span>}
                                        {responsable && (
                                            <span className="block truncate text-xs text-muted-foreground">
                                                {k.enseignants.length ? k.enseignants.join(', ') : 'Sans enseignant'}
                                            </span>
                                        )}
                                    </span>
                                    <span className="flex items-center gap-3 text-xs tabular-nums">
                                        <span className="text-muted-foreground">{k.eleves} élève{k.eleves > 1 ? 's' : ''}</span>
                                        <span className={e.classe} title={dateHeure(k.derniere_activite)}>{e.texte}</span>
                                    </span>
                                </Link>
                            );
                        })}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
