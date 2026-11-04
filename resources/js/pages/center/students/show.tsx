import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import AnalyseIa from '@/components/center/analyse-ia';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { BookOpen, Download } from 'lucide-react';
import { competence, dateHeure, duree, ilYA } from '@/lib/suivi';
import { ecartPoints } from '@/lib/tableau-de-classe';
import {
    BarresCompetences,
    BarresMinutes,
    CalendrierRegularite,
    CourbeSemaines,
    precisionPonderee,
    Tendance,
    type CompetenceStat,
    type JourCalendrier,
    type SemaineStat,
} from '@/components/center/statistiques';

type StatutDevoir = 'a_temps' | 'rendus_en_retard' | 'en_retard' | 'a_faire';
interface StatistiquesEleve {
    semaines: SemaineStat[];
    semaines_classe: SemaineStat[] | null;
    competences: CompetenceStat[];
    calendrier: JourCalendrier[];
    devoirs: {
        a_temps: number;
        rendus_en_retard: number;
        en_retard: number;
        a_faire: number;
        derniers: { id: number; titre: string; echeance: string | null; statut: StatutDevoir }[];
    };
}

const STATUTS: Record<StatutDevoir, { libelle: string; classe: string }> = {
    a_temps: { libelle: 'Rendu à temps', classe: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' },
    rendus_en_retard: { libelle: 'Rendu en retard', classe: 'bg-amber-500/10 text-amber-700 dark:text-amber-400' },
    en_retard: { libelle: 'En retard', classe: 'bg-rose-500/10 text-rose-700 dark:text-rose-400' },
    a_faire: { libelle: 'À faire', classe: 'bg-muted text-muted-foreground' },
};

const FORMAT_ECHEANCE = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short' });

interface Student { id: number; name: string; email: string; level: string | null; classes: string[] }
interface Jour { date: string; exercices: number; minutes: number }

interface Exemple { contexte: string | null; reponse: string | null; correction: string | null; explication: string | null; competence: string | null }
interface Faiblesse { libelle: string; nombre: number; competences: string[]; derniere: string | null; exemples: Exemple[] }
interface Observation {
    dimension: string;
    observation: string;
    gravite: 'strength' | 'minor' | 'major' | string;
    competence: string | null;
    exercice: string | null;
    tentative: number;
    date: string | null;
}

const GRAVITE: Record<string, { libelle: string; classe: string }> = {
    strength: { libelle: 'Point fort', classe: 'border-emerald-300/70 bg-emerald-50/60 dark:border-emerald-900 dark:bg-emerald-950/20' },
    minor: { libelle: 'À travailler', classe: 'border-amber-300/70 bg-amber-50/60 dark:border-amber-900 dark:bg-amber-950/20' },
    major: { libelle: 'Priorité', classe: 'border-rose-300/70 bg-rose-50/60 dark:border-rose-900 dark:bg-rose-950/20' },
};

interface Resume {
    derniere_activite: string | null;
    tentatives: number;
    precision_moyenne: number | null;
    temps_total_secondes: number;
    jours_actifs_30: number;
    activite_30_jours: Jour[];
}

interface Ligne {
    id: number;
    date: string | null;
    exercice: string;
    examen: string | null;
    niveau: string | null;
    partie: string | null;
    competence: string | null;
    format: string | null;
    justes: number | null;
    total: number | null;
    precision: number | null;
    temps_secondes: number;
}

interface Page<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

function couleur(precision: number | null): string {
    if (precision === null) return 'text-muted-foreground';
    if (precision >= 70) return 'text-emerald-600 dark:text-emerald-400';
    if (precision >= 50) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
}

/**
 * La fiche d'un apprenant, cote centre : ce qu'il a fait, quand, a quelle
 * heure, combien de temps, avec quel resultat — et le detail de chaque copie.
 *
 * Les heures s'affichent a l'heure de celui qui regarde : le serveur envoie des
 * dates UTC, le navigateur les convertit.
 */
export default function StudentShow({
    student,
    resume,
    historique,
    faiblesses,
    profil,
    statistiques = null,
}: {
    student: Student;
    resume: Resume;
    historique: Page<Ligne>;
    faiblesses: Faiblesse[];
    profil: Observation[];
    statistiques?: StatistiquesEleve | null;
}) {
    const plusActif = Math.max(1, ...resume.activite_30_jours.map((j) => j.exercices));
    const st = statistiques;
    const saPrecision = precisionPonderee(st?.semaines);
    const precisionClasse = precisionPonderee(st?.semaines_classe);
    const sesExercices = st ? st.semaines.reduce((t, s) => t + s.exercices, 0) : 0;
    const devoirs = st?.devoirs;
    const devoirsTotal = devoirs ? devoirs.a_temps + devoirs.rendus_en_retard + devoirs.en_retard + devoirs.a_faire : 0;

    return (
        <AppLayout>
            <Head title={student.name} />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <Link href={route('center.students.index')} className="text-sm text-muted-foreground hover:text-foreground">
                            ← Élèves
                        </Link>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight">{student.name}</h1>
                        <p className="text-sm text-muted-foreground">
                            {student.email} · Niveau {student.level ?? '—'}
                            {student.classes.length > 0 && <> · {student.classes.join(', ')}</>}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" size="sm">
                            <Link href={route('center.students.vocabulaire', student.id)}>
                                <BookOpen className="mr-1.5 h-4 w-4" /> Vocabulaire
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <a href={route('center.students.export', student.id)}>
                                <Download className="mr-1.5 h-4 w-4" /> Exporter l'historique
                            </a>
                        </Button>
                    </div>
                </div>

                {/* Le probleme principal et le levier de progression, d'abord : c'est ce que l'enseignant vient chercher. */}
                <AnalyseIa sujet="eleve" url={route('center.analyses.eleve', student.id)} />

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-lg font-bold leading-tight">{ilYA(resume.derniere_activite)}</p>
                            <p className="mt-0.5 text-xs text-muted-foreground">{dateHeure(resume.derniere_activite)}</p>
                            <p className="mt-1 text-sm text-muted-foreground">Dernière activité</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-2xl font-bold">{duree(resume.temps_total_secondes)}</p>
                            <p className="text-sm text-muted-foreground">Temps de travail total</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-2xl font-bold">{resume.tentatives}</p>
                            <p className="text-sm text-muted-foreground">
                                Exercices réalisés
                                {resume.precision_moyenne !== null && <> · {resume.precision_moyenne}% de réussite</>}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-2xl font-bold">{resume.jours_actifs_30}<span className="text-base font-medium text-muted-foreground"> / 30</span></p>
                            <p className="text-sm text-muted-foreground">Jours actifs ce mois-ci</p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Activité des 30 derniers jours</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex h-20 items-end gap-[3px]" role="img" aria-label="Exercices réalisés par jour sur les 30 derniers jours">
                            {resume.activite_30_jours.map((j) => (
                                <div
                                    key={j.date}
                                    className={`flex-1 rounded-t-sm ${j.exercices > 0 ? 'bg-primary' : 'bg-muted'}`}
                                    style={{ height: j.exercices > 0 ? `${Math.max(12, (j.exercices / plusActif) * 100)}%` : '6%' }}
                                    title={`${new Date(j.date + 'T12:00:00').toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' })} — ${j.exercices} exercice${j.exercices > 1 ? 's' : ''}, ${j.minutes} min`}
                                />
                            ))}
                        </div>
                        <div className="mt-1.5 flex justify-between text-xs text-muted-foreground">
                            <span>il y a 30 jours</span>
                            <span>aujourd'hui</span>
                        </div>
                    </CardContent>
                </Card>

                {st && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Progression sur 8 semaines</CardTitle>
                            <p className="text-xs text-muted-foreground">
                                Sa précision chaque semaine{st.semaines_classe ? ', comparée à la moyenne de sa classe' : ''}.
                            </p>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {saPrecision !== null && (
                                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                                    <span>
                                        <span className="text-2xl font-bold tabular-nums">{Math.round(saPrecision)} %</span>
                                        <span className="text-muted-foreground"> de réussite sur {sesExercices} exercice{sesExercices > 1 ? 's' : ''}</span>
                                    </span>
                                    {precisionClasse !== null && (
                                        <span className="inline-flex items-center gap-1.5 text-muted-foreground">
                                            classe : {Math.round(precisionClasse)} %
                                            <Tendance v={ecartPoints(saPrecision - precisionClasse)} titre="Écart avec la moyenne de la classe" />
                                        </span>
                                    )}
                                </div>
                            )}
                            <CourbeSemaines semaines={st.semaines} comparaison={st.semaines_classe} />
                        </CardContent>
                    </Card>
                )}

                {st && (
                    <div className="grid gap-5 md:grid-cols-2">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">Réussite par compétence</CardTitle>
                                <p className="text-xs text-muted-foreground">Depuis le début.</p>
                            </CardHeader>
                            <CardContent>
                                <BarresCompetences competences={st.competences} />
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">Régularité</CardTitle>
                                <p className="text-xs text-muted-foreground">Un carré par jour sur 8 semaines, à l’heure du centre.</p>
                            </CardHeader>
                            <CardContent>
                                <CalendrierRegularite jours={st.calendrier} />
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">Temps de travail par semaine</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <BarresMinutes semaines={st.semaines} />
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">Devoirs</CardTitle>
                                <p className="text-xs text-muted-foreground">Ceux de ses classes, publiés.</p>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {!devoirs || devoirsTotal === 0 ? (
                                    <p className="text-sm text-muted-foreground">Aucun devoir donné pour le moment.</p>
                                ) : (
                                    <>
                                        <div className="grid grid-cols-2 gap-2 text-center">
                                            {(Object.keys(STATUTS) as StatutDevoir[]).map((cle) => (
                                                <div key={cle} className={`rounded-lg px-2 py-2 ${STATUTS[cle].classe}`}>
                                                    <p className="text-xl font-bold tabular-nums">{devoirs[cle]}</p>
                                                    <p className="text-xs">{STATUTS[cle].libelle}</p>
                                                </div>
                                            ))}
                                        </div>
                                        <ul className="space-y-1.5">
                                            {devoirs.derniers.map((d) => (
                                                <li key={d.id} className="flex items-center justify-between gap-2 text-sm">
                                                    <span className="min-w-0 truncate">{d.titre}</span>
                                                    <span className="flex shrink-0 items-center gap-2 text-xs">
                                                        {d.echeance && <span className="text-muted-foreground">{FORMAT_ECHEANCE.format(new Date(d.echeance))}</span>}
                                                        <span className={`rounded-full px-1.5 py-0.5 font-medium ${STATUTS[d.statut].classe}`}>{STATUTS[d.statut].libelle}</span>
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                    </>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                )}

                <Card>
                    <CardHeader className="flex-row items-baseline justify-between space-y-0 pb-2">
                        <CardTitle className="text-base">Historique</CardTitle>
                        <span className="text-xs text-muted-foreground">{historique.total} exercice{historique.total > 1 ? 's' : ''}</span>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {historique.data.length === 0 && <p className="text-sm text-muted-foreground">Aucun exercice réalisé pour le moment.</p>}
                        {historique.data.map((l) => (
                            <Link
                                key={l.id}
                                href={route('center.students.attempt', { user: student.id, attempt: l.id })}
                                className="flex flex-wrap items-center gap-x-4 gap-y-1 rounded-lg border border-border p-3 text-sm transition hover:border-primary/40 hover:bg-muted/40"
                            >
                                <div className="w-28 shrink-0 text-xs text-muted-foreground tabular-nums">{dateHeure(l.date)}</div>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-medium">{l.exercice}</p>
                                    <p className="truncate text-xs text-muted-foreground">
                                        {[l.examen, l.niveau, l.partie, competence(l.competence)].filter(Boolean).join(' · ')}
                                    </p>
                                </div>
                                <div className="flex items-center gap-4 tabular-nums">
                                    <span className="text-xs text-muted-foreground" title="Temps passé">{duree(l.temps_secondes)}</span>
                                    {l.total !== null && (
                                        <span className="text-xs text-muted-foreground">{l.justes}/{l.total}</span>
                                    )}
                                    <span className={`w-12 text-right font-semibold ${couleur(l.precision)}`}>
                                        {l.precision !== null ? `${Math.round(l.precision)}%` : '—'}
                                    </span>
                                </div>
                            </Link>
                        ))}

                        {historique.last_page > 1 && (
                            <div className="flex items-center justify-between pt-2 text-sm">
                                {historique.prev_page_url ? (
                                    <Link href={historique.prev_page_url} preserveScroll className="text-primary hover:underline">← Plus récents</Link>
                                ) : <span />}
                                <span className="text-muted-foreground">Page {historique.current_page} / {historique.last_page}</span>
                                {historique.next_page_url ? (
                                    <Link href={historique.next_page_url} preserveScroll className="text-primary hover:underline">Plus anciens →</Link>
                                ) : <span />}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Profil à l'écrit et à l'oral</CardTitle>
                        <p className="text-xs text-muted-foreground">
                            Les tendances de fond relevées par l'IA dans ses rédactions et ses prises de parole, preuve à l'appui.
                        </p>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {profil.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Pas encore de rédaction ni de prise de parole analysée. C'est là que les faiblesses profondes se révèlent.
                            </p>
                        )}
                        {profil.map((o, i) => {
                            const g = GRAVITE[o.gravite] ?? GRAVITE.minor;
                            return (
                                <div key={i} className={`rounded-lg border p-3 text-sm ${g.classe}`}>
                                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                                        <span className="font-medium">{o.dimension}</span>
                                        <span className="text-xs text-muted-foreground">
                                            {g.libelle} · {competence(o.competence)} ·{' '}
                                            <Link href={route('center.students.attempt', { user: student.id, attempt: o.tentative })} className="underline-offset-2 hover:underline">
                                                {dateHeure(o.date)}
                                            </Link>
                                        </span>
                                    </div>
                                    <p className="mt-1">{o.observation}</p>
                                </div>
                            );
                        })}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Faiblesses à travailler</CardTitle>
                        <p className="text-xs text-muted-foreground">Chaque faiblesse avec les dernières fautes où elle est apparue.</p>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {faiblesses.length === 0 && <p className="text-sm text-muted-foreground">Aucune faiblesse récurrente détectée.</p>}
                        {faiblesses.map((f, i) => (
                            <details key={i} className="group rounded-lg border border-border text-sm">
                                <summary className="flex cursor-pointer list-none items-center justify-between gap-3 p-2.5">
                                    <span>
                                        <span className="font-medium">{f.libelle}</span>
                                        {f.competences.length > 0 && (
                                            <span className="text-muted-foreground"> · {f.competences.map((c) => competence(c)).join(', ')}</span>
                                        )}
                                    </span>
                                    <Badge variant="secondary">{f.nombre} faute{f.nombre > 1 ? 's' : ''}</Badge>
                                </summary>
                                <ul className="space-y-2 border-t px-3 py-2.5">
                                    {f.exemples.map((e, j) => (
                                        <li key={j} className="space-y-0.5">
                                            {e.reponse && e.correction ? (
                                                <p>
                                                    <span className="text-rose-600 line-through decoration-rose-300 dark:text-rose-400">{e.reponse}</span>
                                                    {' → '}
                                                    <span className="font-medium text-emerald-700 dark:text-emerald-400">{e.correction}</span>
                                                </p>
                                            ) : (
                                                e.contexte && <p>{e.contexte}</p>
                                            )}
                                            {e.explication && <p className="text-xs text-muted-foreground">{e.explication}</p>}
                                        </li>
                                    ))}
                                </ul>
                            </details>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
