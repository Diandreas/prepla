import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { ArrowRight, CheckCircle2, Circle, Lock } from 'lucide-react';

interface EtapeDeLEleve {
    id: number;
    numero: number;
    total: number;
    exercise_id: number;
    titre: string;
    consigne: string | null;
    seuil_reussite: number | null;
    type: string;
    competence: string | null;
    statut: 'fait' | 'en_cours' | 'a_venir';
}
interface Parcours {
    total: number;
    faites: number;
    termine: boolean;
    etapes: EtapeDeLEleve[];
}

/** « Mon parcours » : les étapes préparées par le centre pour la classe de l'élève. */
export default function MonParcours({ classe, parcours, devoirsEnAttente }: { classe: string | null; parcours: Parcours | null; devoirsEnAttente: number }) {
    const { flash } = usePage().props as { flash?: { success?: string } };

    return (
        <AppLayout>
            <Head title="Mon parcours" />
            <div className="mx-auto w-full max-w-3xl space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Mon parcours</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {classe ? `Préparé par ton centre pour ta classe « ${classe} ».` : 'Préparé par ton centre pour ta classe.'}
                    </p>
                </div>

                {flash?.success && (
                    <div className="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                        {flash.success}
                    </div>
                )}

                {devoirsEnAttente > 0 && (
                    <Link
                        href={route('devoirs.index')}
                        className="flex items-center justify-between gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950/30 dark:text-amber-200"
                    >
                        <span>
                            Tu as {devoirsEnAttente} devoir{devoirsEnAttente > 1 ? 's' : ''} à faire : commence par là.
                        </span>
                        <ArrowRight size={18} aria-hidden="true" />
                    </Link>
                )}

                {!classe ? (
                    <div className="rounded-xl border border-dashed border-border p-4 text-sm text-muted-foreground">
                        Tu n’es dans aucune classe pour le moment.{' '}
                        <Link href={route('center.join')} className="font-semibold text-primary hover:underline">Rejoindre une classe</Link>
                    </div>
                ) : !parcours || parcours.total === 0 ? (
                    <div className="rounded-xl border border-dashed border-border p-4 text-sm text-muted-foreground">
                        Ton centre n’a pas encore préparé le parcours de ta classe. Ton enseignant te donnera bientôt du travail.
                    </div>
                ) : (
                    <section aria-labelledby="etapes" className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 id="etapes" className="text-sm font-bold">Étapes</h2>
                            <span className="text-xs text-muted-foreground tabular-nums">
                                {parcours.faites}/{parcours.total} faite{parcours.faites > 1 ? 's' : ''}
                            </span>
                        </div>
                        {parcours.termine && (
                            <div className="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                                Tu as terminé ton parcours. Bravo ! Ton enseignant t’en préparera la suite.
                            </div>
                        )}
                        <ol className="space-y-2">
                            {parcours.etapes.map((etape) => {
                                const contenu = (
                                    <>
                                        <span className="shrink-0">
                                            {etape.statut === 'fait' ? (
                                                <CheckCircle2 size={20} className="text-emerald-600" aria-label="Faite" />
                                            ) : etape.statut === 'en_cours' ? (
                                                <Circle size={20} className="text-primary" aria-label="En cours" />
                                            ) : (
                                                <Lock size={18} className="text-muted-foreground" aria-label="À venir" />
                                            )}
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            <span className="block text-xs text-muted-foreground">
                                                Étape {etape.numero} sur {etape.total}
                                                {etape.competence && ` · ${etape.competence}`}
                                            </span>
                                            <span className="block truncate text-sm font-bold">{etape.titre}</span>
                                            {etape.consigne && etape.statut === 'en_cours' && <span className="mt-1 block text-xs">{etape.consigne}</span>}
                                            {etape.seuil_reussite && etape.statut !== 'fait' && (
                                                <span className="mt-0.5 block text-xs text-muted-foreground">À réussir à {etape.seuil_reussite} % au moins</span>
                                            )}
                                        </span>
                                        {etape.statut === 'en_cours' && <ArrowRight size={18} className="shrink-0 text-primary" aria-hidden="true" />}
                                    </>
                                );
                                const classes = `flex items-center gap-3 rounded-xl border p-4 ${
                                    etape.statut === 'en_cours' ? 'border-primary/50 bg-primary/5' : 'border-border bg-card'
                                } ${etape.statut === 'a_venir' ? 'opacity-70' : ''}`;

                                // On refait une étape faite si on veut ; celle à venir attend son tour.
                                return (
                                    <li key={etape.id}>
                                        {etape.statut === 'a_venir' ? (
                                            <div className={classes}>{contenu}</div>
                                        ) : (
                                            <Link href={route('exercise.show', etape.exercise_id)} className={`${classes} transition hover:border-primary/40`}>
                                                {contenu}
                                            </Link>
                                        )}
                                    </li>
                                );
                            })}
                        </ol>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
