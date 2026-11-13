import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { UserPlus } from 'lucide-react';
import { dateHeure, ilYA } from '@/lib/suivi';
import { useT, type Vars } from '@/lib/i18n/traduire';

interface Classe { id: number; name: string; level: string | null; eleves: number; derniere_activite: string | null }
interface Membre { id: number; name: string; email: string; role: string; depuis: string | null; classes: Classe[] }
interface Invitation { id: number; name: string; email: string; role: string; expire: string | null }

/** Une classe qui ne travaille plus depuis une semaine se voit d'un coup d'oeil. */
function etat(derniere: string | null, t: (fr: string, vars?: Vars) => string): { texte: string; classe: string } {
    if (!derniere) return { texte: t('Aucune activité'), classe: 'text-muted-foreground' };
    const jours = (Date.now() - new Date(derniere).getTime()) / 86_400_000;
    if (jours > 7) return { texte: t('Inactive · {quand}', { quand: ilYA(derniere) }), classe: 'text-amber-600 dark:text-amber-400' };
    return { texte: t('Active · {quand}', { quand: ilYA(derniere) }), classe: 'text-emerald-600 dark:text-emerald-400' };
}

interface ClasseCourte { id: number; name: string; level: string | null }

/**
 * Confier une classe existante a un membre de l'equipe. Un enseignant peut en
 * tenir plusieurs : on n'affiche que celles qu'il n'a pas encore.
 */
function AttribuerUneClasse({ membreId, dejaTenues, toutes }: { membreId: number; dejaTenues: number[]; toutes: ClasseCourte[] }) {
    const { t } = useT();
    const [choix, setChoix] = useState('');
    const libres = toutes.filter((k) => !dejaTenues.includes(k.id));
    if (libres.length === 0) return null;
    return (
        <div className="flex flex-wrap items-center gap-2 pt-1">
            <label htmlFor={`attribuer-${membreId}`} className="sr-only">{t('Attribuer une classe')}</label>
            <select
                id={`attribuer-${membreId}`}
                value={choix}
                onChange={(e) => setChoix(e.target.value)}
                className="min-w-0 flex-1 rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            >
                <option value="">{t('Attribuer une classe…')}</option>
                {libres.map((k) => (
                    <option key={k.id} value={k.id}>{k.name}{k.level ? ` · ${k.level}` : ''}</option>
                ))}
            </select>
            <Button
                size="sm"
                disabled={!choix}
                onClick={() => router.post(route('center.equipe.classes.attacher', Number(choix)), { user_id: membreId }, { preserveScroll: true, onSuccess: () => setChoix('') })}
            >
                {t('Attribuer')}
            </Button>
        </div>
    );
}

/** Confier une classe orpheline a quelqu'un de l'equipe, directement depuis l'alerte. */
function ConfierA({ classeId, equipe }: { classeId: number; equipe: { id: number; name: string }[] }) {
    const { t } = useT();
    const [choix, setChoix] = useState('');
    return (
        <div className="flex flex-wrap items-center gap-2">
            <label htmlFor={`confier-${classeId}`} className="sr-only">{t('Confier à')}</label>
            <select
                id={`confier-${classeId}`}
                value={choix}
                onChange={(e) => setChoix(e.target.value)}
                className="min-w-0 flex-1 rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            >
                <option value="">{t('Confier à…')}</option>
                {equipe.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
            </select>
            <Button
                size="sm"
                variant="outline"
                disabled={!choix}
                onClick={() => router.post(route('center.equipe.classes.attacher', classeId), { user_id: Number(choix) }, { preserveScroll: true })}
            >
                {t('Confier')}
            </Button>
        </div>
    );
}

function LigneDeClasse({ k, onRetirer }: { k: Classe; onRetirer?: () => void }) {
    const { t } = useT();
    const e = etat(k.derniere_activite, t);
    if (onRetirer) {
        return (
            <div className="flex items-stretch gap-2">
                <div className="min-w-0 flex-1"><LigneDeClasse k={k} /></div>
                <button
                    type="button"
                    onClick={onRetirer}
                    className="rounded-md border px-3 text-xs font-semibold text-muted-foreground transition hover:border-rose-300 hover:text-rose-600"
                    title={t('Retirer cette classe à cet enseignant')}
                >
                    {t('Retirer')}
                </button>
            </div>
        );
    }
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
                <span className="text-muted-foreground">{k.eleves > 1 ? t('{n} élèves', { n: k.eleves }) : t('{n} élève', { n: k.eleves })}</span>
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
    toutesLesClasses = [],
}: {
    centre: { id: number; name: string };
    equipe: Membre[];
    toutesLesClasses?: ClasseCourte[];
    classesSansEnseignant: Classe[];
    invitations: Invitation[];
}) {
    const { t, locale } = useT();
    return (
        <AppLayout>
            <Head title={t('Équipe — {nom}', { nom: centre.name })} />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p className="text-sm text-muted-foreground">{centre.name}</p>
                        <h1 className="text-2xl font-bold tracking-tight">{t('Équipe')}</h1>
                    </div>
                    <Button asChild size="sm">
                        <Link href={route('teach.invitations')}>
                            <UserPlus className="mr-1.5 h-4 w-4" /> {t('Inviter un enseignant')}
                        </Link>
                    </Button>
                </div>

                {classesSansEnseignant.length > 0 && (
                    <Card className="border-amber-300/70 dark:border-amber-900">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">{t('Classes sans enseignant')}</CardTitle>
                            <p className="text-xs text-muted-foreground">{t("Personne n'y est rattaché : leurs élèves ne sont suivis par aucun professeur.")}</p>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {classesSansEnseignant.map((k) => (
                                <div key={k.id} className="space-y-1.5">
                                    <LigneDeClasse k={k} />
                                    {equipe.length > 0 && <ConfierA classeId={k.id} equipe={equipe} />}
                                </div>
                            ))}
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
                                        {m.role === 'center_admin' ? t('Responsable') : t('Enseignant')}
                                    </Badge>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {m.email}
                                    {m.depuis && <>{t(" · dans l'équipe depuis {date}", { date: new Date(m.depuis).toLocaleDateString(locale, { month: 'long', year: 'numeric' }) })}</>}
                                </p>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {m.classes.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">{t('Aucune classe pour le moment.')}</p>
                                ) : (
                                    m.classes.map((k) => (
                                        <LigneDeClasse
                                            key={k.id}
                                            k={k}
                                            onRetirer={() => {
                                                if (confirm(t('Retirer « {classe} » à {nom} ? Ses élèves restent dans la classe.', { classe: k.name, nom: m.name }))) {
                                                    router.delete(route('center.equipe.classes.retirer', k.id), { data: { user_id: m.id }, preserveScroll: true });
                                                }
                                            }}
                                        />
                                    ))
                                )}
                                <AttribuerUneClasse membreId={m.id} dejaTenues={m.classes.map((k) => k.id)} toutes={toutesLesClasses} />
                                {m.classes.length > 1 && (
                                    <p className="text-xs text-muted-foreground">{t('{classes} classes · {eleves} élèves suivis', { classes: m.classes.length, eleves: m.classes.reduce((n, k) => n + k.eleves, 0) })}</p>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {invitations.length > 0 && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">{t('Invitations en attente')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {invitations.map((i) => (
                                <div key={i.id} className="flex flex-wrap items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm">
                                    <span>
                                        <span className="font-medium">{i.name}</span>
                                        <span className="text-muted-foreground"> · {i.email}</span>
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {t("Lien valable jusqu'au {date}", { date: i.expire ? new Date(i.expire).toLocaleDateString(locale, { day: 'numeric', month: 'long' }) : '—' })}
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
