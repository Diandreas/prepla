import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { ArrowLeft, ArrowRight, CheckCircle2, Circle } from 'lucide-react';
import { libelleDuType } from '@/lib/createur-exercices';
import type { DevoirDeLEleve } from './index';

interface ExerciceDuDevoir { id: number; type: string; type_slug: string | null; component_key: string | null; fait: boolean }

/** Un devoir : consigne, échéance, progression et ses exercices dans l'ordre. */
export default function Devoir({ devoir, exercices }: { devoir: DevoirDeLEleve; exercices: ExerciceDuDevoir[] }) {
    const enRetard = devoir.overdue && !devoir.termine;
    const pourcent = devoir.total > 0 ? Math.round((devoir.done / devoir.total) * 100) : 0;

    return (
        <AppLayout>
            <Head title={devoir.title} />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                <Link href={route('devoirs.index')} className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft size={15} aria-hidden="true" /> Mes devoirs
                </Link>

                <div className="rounded-2xl border border-border bg-card p-5">
                    {devoir.classe && <p className="text-xs font-semibold text-muted-foreground">Classe « {devoir.classe} »</p>}
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">{devoir.title}</h1>
                    {devoir.echeance && (
                        <p className={`mt-1 text-sm font-semibold ${enRetard ? 'text-rose-600 dark:text-rose-400' : 'text-muted-foreground'}`}>
                            {devoir.echeance.charAt(0).toUpperCase() + devoir.echeance.slice(1)}
                        </p>
                    )}
                    {devoir.instructions && <p className="mt-3 whitespace-pre-line text-sm leading-relaxed">{devoir.instructions}</p>}

                    <div className="mt-4">
                        <div className="mb-1 flex justify-between text-xs font-semibold text-muted-foreground">
                            <span>Progression</span>
                            <span>{devoir.done}/{devoir.total} exercice{devoir.total > 1 ? 's' : ''}</span>
                        </div>
                        <div className="h-2.5 w-full overflow-hidden rounded-full bg-muted" role="progressbar" aria-valuenow={pourcent} aria-valuemin={0} aria-valuemax={100}>
                            <div className="h-full rounded-full bg-primary" style={{ width: `${pourcent}%` }} />
                        </div>
                    </div>

                    {devoir.termine ? (
                        <p className="mt-5 inline-flex items-center gap-2 text-sm font-bold text-emerald-600">
                            <CheckCircle2 size={18} aria-hidden="true" /> Devoir terminé. Bravo !
                        </p>
                    ) : devoir.examen_blanc_id ? (
                        // Un vrai lien de page : le lecteur d'examen blanc se charge en entier.
                        <a
                            href={`/examens-blancs/${devoir.examen_blanc_id}`}
                            className="mt-5 inline-flex min-h-11 items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-primary-foreground hover:opacity-90"
                        >
                            Passer l’examen blanc <ArrowRight size={16} aria-hidden="true" />
                        </a>
                    ) : devoir.next_exercise_id ? (
                        <Link
                            href={route('exercise.show', devoir.next_exercise_id)}
                            className="mt-5 inline-flex min-h-11 items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-primary-foreground hover:opacity-90"
                        >
                            {devoir.done > 0 ? 'Continuer mon devoir' : 'Commencer mon devoir'} <ArrowRight size={16} aria-hidden="true" />
                        </Link>
                    ) : null}
                </div>

                {exercices.length > 0 && (
                    <section aria-labelledby="exercices" className="space-y-2">
                        <h2 id="exercices" className="text-sm font-bold">Les exercices</h2>
                        <ol className="space-y-2">
                            {exercices.map((e, i) => (
                                <li key={e.id}>
                                    <Link
                                        href={route('exercise.show', e.id)}
                                        className="flex items-center justify-between gap-3 rounded-xl border border-border bg-card p-3 text-sm transition hover:border-primary/40"
                                    >
                                        <span className="min-w-0 truncate">
                                            <span className="mr-2 text-muted-foreground">{i + 1}.</span>
                                            {/* « Questions à choix multiples », pas « Multiple Choice ». */}
                                            {libelleDuType({ slug: e.type_slug, component_key: e.component_key, name: e.type })}
                                        </span>
                                        {e.fait ? (
                                            <CheckCircle2 size={18} className="shrink-0 text-emerald-600" aria-label="Fait" />
                                        ) : (
                                            <Circle size={18} className="shrink-0 text-muted-foreground" aria-label="À faire" />
                                        )}
                                    </Link>
                                </li>
                            ))}
                        </ol>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
