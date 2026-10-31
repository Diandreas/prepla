import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { UserPlus } from 'lucide-react';
import { dateHeure, ilYA } from '@/lib/suivi';

interface Classe { id: number; name: string; level: string | null; eleves: number; derniere_activite: string | null }
interface Membre { id: number; name: string; email: string; role: string; depuis: string | null; classes: Classe[] }
interface Invitation { id: number; name: string; email: string; role: string; expire: string | null }

/** Une classe qui ne travaille plus depuis une semaine se voit d'un coup d'oeil. */
function etat(derniere: string | null): { texte: string; classe: string } {
    if (!derniere) return { texte: 'Aucune activité', classe: 'text-muted-foreground' };
    const jours = (Date.now() - new Date(derniere).getTime()) / 86_400_000;
    if (jours > 7) return { texte: `Inactive · ${ilYA(derniere)}`, classe: 'text-amber-600 dark:text-amber-400' };
    return { texte: `Active · ${ilYA(derniere)}`, classe: 'text-emerald-600 dark:text-emerald-400' };
}

function LigneDeClasse({ k }: { k: Classe }) {
    const e = etat(k.derniere_activite);
    return (
        <Link
            href={route('center.classes.show', k.id)}
            className="flex flex-wrap items-center justify-between gap-x-3 gap-y-0.5 rounded-md border px-3 py-2 text-sm transition hover:border-primary/40 hover:bg-muted/40"
        >
            <span className="font-medium">
                {k.name}
                {k.level && <span className="text-muted-foreground"> · {k.level}</span>}
            </span>
            <span className="flex items-center gap-3 text-xs tabular-nums">
                <span className="text-muted-foreground">{k.eleves} élève{k.eleves > 1 ? 's' : ''}</span>
                <span className={e.classe} title={dateHeure(k.derniere_activite)}>{e.texte}</span>
            </span>
        </Link>
    );
}

/**
 * L'equipe d'un centre, pour son responsable : qui enseigne, quelles classes,
 * combien d'eleves, et si ces classes travaillent. Il pouvait inviter un
 * enseignant, mais ne voyait nulle part qui faisait partie de son centre.
 */
export default function Equipe({
    centre,
    equipe,
    classesSansEnseignant,
    invitations,
}: {
    centre: { id: number; name: string };
    equipe: Membre[];
    classesSansEnseignant: Classe[];
    invitations: Invitation[];
}) {
    return (
        <AppLayout>
            <Head title={`Équipe — ${centre.name}`} />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p className="text-sm text-muted-foreground">{centre.name}</p>
                        <h1 className="text-2xl font-bold tracking-tight">Équipe</h1>
                    </div>
                    <Button asChild size="sm">
                        <Link href={route('teach.invitations')}>
                            <UserPlus className="mr-1.5 h-4 w-4" /> Inviter un enseignant
                        </Link>
                    </Button>
                </div>

                {classesSansEnseignant.length > 0 && (
                    <Card className="border-amber-300/70 dark:border-amber-900">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Classes sans enseignant</CardTitle>
                            <p className="text-xs text-muted-foreground">Personne n'y est rattaché : leurs élèves ne sont suivis par aucun professeur.</p>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {classesSansEnseignant.map((k) => <LigneDeClasse key={k.id} k={k} />)}
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-4">
                    {equipe.map((m) => (
                        <Card key={m.id}>
                            <CardHeader className="pb-2">
                                <div className="flex flex-wrap items-baseline justify-between gap-2">
                                    <CardTitle className="text-base">{m.name}</CardTitle>
                                    <Badge variant={m.role === 'center_admin' ? 'default' : 'secondary'}>
                                        {m.role === 'center_admin' ? 'Responsable' : 'Enseignant'}
                                    </Badge>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {m.email}
                                    {m.depuis && <> · dans l'équipe depuis {new Date(m.depuis).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })}</>}
                                </p>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {m.classes.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">Aucune classe pour le moment.</p>
                                ) : (
                                    m.classes.map((k) => <LigneDeClasse key={k.id} k={k} />)
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {invitations.length > 0 && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Invitations en attente</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {invitations.map((i) => (
                                <div key={i.id} className="flex flex-wrap items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm">
                                    <span>
                                        <span className="font-medium">{i.name}</span>
                                        <span className="text-muted-foreground"> · {i.email}</span>
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        Lien valable jusqu'au {i.expire ? new Date(i.expire).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' }) : '—'}
                                    </span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
