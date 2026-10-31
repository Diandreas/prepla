import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ClipboardList, Plus, UserPlus } from 'lucide-react';
import { dateHeure, duree, ilYA } from '@/lib/suivi';

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
}: {
    center: Center;
    semaine: Semaine;
    classes: Classe[];
    a_relancer: ARelancer[];
}) {
    const { auth } = usePage().props as unknown as { auth?: { center?: { role?: string } } };
    const responsable = auth?.center?.role === 'center_admin';

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

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <Stat
                        label="Élèves actifs cette semaine"
                        value={`${semaine.actifs} / ${semaine.eleves}`}
                        href={route('center.students.index')}
                    />
                    <Stat label="Exercices cette semaine" value={semaine.exercices} detail={`${duree(semaine.minutes * 60)} de travail`} />
                    <Stat label="Classes" value={center.classrooms_count} href={route('center.classes.index')} />
                    <Stat
                        label="Places occupées"
                        value={`${center.seats_used} / ${center.seats_limit}`}
                        detail={`${Math.max(0, center.seats_limit - center.seats_used)} restantes`}
                    />
                </div>

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
                                        {e.derniere_activite ? ilYA(e.derniere_activite) : 'jamais venu'}
                                    </span>
                                </Link>
                            ))}
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
