import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { ArrowRight, CheckCircle2 } from 'lucide-react';

export interface DevoirDeLEleve {
    id: number;
    title: string;
    instructions: string | null;
    classe: string | null;
    due_at: string | null;
    /** « pour lundi 10 novembre », « en retard de 2 jours » (serveur). */
    echeance: string | null;
    overdue: boolean;
    total: number;
    done: number;
    termine: boolean;
    next_exercise_id: number | null;
    // Examen blanc du centre encore a rendre.
    examen_blanc_id?: number | null;
}

/** Une ligne de devoir : titre, classe, échéance, progression. */
export function LigneDevoir({ devoir }: { devoir: DevoirDeLEleve }) {
    const enRetard = devoir.overdue && !devoir.termine;
    return (
        <Link
            href={route('devoirs.show', devoir.id)}
            className="flex items-center justify-between gap-3 rounded-xl border border-border bg-card p-4 transition hover:border-primary/40 focus-visible:outline focus-visible:outline-primary"
        >
            <div className="min-w-0">
                <p className="truncate text-sm font-bold">{devoir.title}</p>
                <p className="mt-0.5 text-xs text-muted-foreground">
                    {devoir.classe && <>Classe « {devoir.classe} » · </>}
                    {devoir.done}/{devoir.total} exercice{devoir.total > 1 ? 's' : ''}
                    {devoir.echeance && (
                        <span className={enRetard ? 'font-semibold text-rose-600 dark:text-rose-400' : ''}> · {devoir.echeance}</span>
                    )}
                </p>
            </div>
            {devoir.termine ? (
                <CheckCircle2 size={20} className="shrink-0 text-emerald-600" aria-label="Terminé" />
            ) : (
                <ArrowRight size={18} className="shrink-0 text-primary" aria-hidden="true" />
            )}
        </Link>
    );
}

/** « Mes devoirs » : le travail donné par le centre, à faire puis terminé. */
export default function MesDevoirs({ aFaire, termines, contenuCentre, sansClasse = false }: { aFaire: DevoirDeLEleve[]; termines: DevoirDeLEleve[]; contenuCentre: boolean; sansClasse?: boolean }) {
    // « Tous faits » seulement s'il en a déjà rendu : l'élève qui arrive dans son
    // centre était félicité avant d'avoir reçu le moindre devoir.
    const videMessage = sansClasse
        ? 'Tu n’es encore dans aucune classe. Entre le code que t’a donné ton enseignant pour recevoir tes devoirs.'
        : termines.length > 0
          ? 'Tu as fait tous tes devoirs. Ton enseignant t’en donnera bientôt.'
          : 'Ton enseignant ne t’a pas encore donné de devoir. Ils arriveront ici.';
    return (
        <AppLayout>
            <Head title="Mes devoirs" />
            <div className="mx-auto w-full max-w-3xl space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Mes devoirs</h1>
                    <p className="mt-1 text-sm text-muted-foreground">Le travail donné par ton enseignant, le plus urgent en premier.</p>
                </div>

                <section aria-labelledby="a-faire" className="space-y-2">
                    <h2 id="a-faire" className="text-sm font-bold">À faire</h2>
                    {aFaire.length === 0 ? (
                        <div className="rounded-xl border border-dashed border-border p-4 text-sm text-muted-foreground">
                            <p>{videMessage}</p>
                            <p className="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                                {sansClasse ? (
                                    <Link href={route('center.join')} className="font-semibold text-primary hover:underline">Entrer le code de ma classe</Link>
                                ) : (
                                    <Link href={route('results.index')} className="font-semibold text-primary hover:underline">Voir mes résultats</Link>
                                )}
                                {!contenuCentre && (
                                    <Link href={route('practice.index')} className="font-semibold text-primary hover:underline">M’entraîner en attendant</Link>
                                )}
                            </p>
                        </div>
                    ) : (
                        aFaire.map((d) => <LigneDevoir key={d.id} devoir={d} />)
                    )}
                </section>

                {termines.length > 0 && (
                    <section aria-labelledby="termines" className="space-y-2">
                        <h2 id="termines" className="text-sm font-bold">Terminés</h2>
                        {termines.map((d) => <LigneDevoir key={d.id} devoir={d} />)}
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
