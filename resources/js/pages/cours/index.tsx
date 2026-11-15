import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { ProchainCours, type ParcoursEleve } from './parcours';

/** « Mes cours » : les parcours publiés par le centre pour les classes de l'élève. */
export default function MesCours({ parcours }: { parcours: ParcoursEleve[] }) {
    const { t } = useTranslation();
    return (
        <AppLayout>
            <Head title={t('courses.my_courses', 'Mes cours')} />
            <div className="mx-auto w-full max-w-3xl space-y-6 p-4 md:p-6">
                <h1 className="text-2xl font-bold tracking-tight">{t('courses.my_courses', 'Mes cours')}</h1>
                {parcours.length === 0 && <p className="text-sm text-muted-foreground">{t('courses.none', 'Votre centre n’a pas encore publié de cours pour votre classe.')}</p>}
                {parcours.map((p) => {
                    const pct = p.total ? Math.round((p.finies / p.total) * 100) : 0;
                    return (
                        <section key={p.id} className="space-y-3 rounded-xl border bg-card p-4">
                            <div className="flex items-baseline justify-between gap-2">
                                <Link href={`/mes-cours/${p.id}`} className="text-lg font-semibold hover:underline">{p.titre}</Link>
                                <span className="text-xs text-muted-foreground">{pct} %</span>
                            </div>
                            <div className="h-2 w-full overflow-hidden rounded-full bg-muted"><div className="h-full bg-primary" style={{ width: `${pct}%` }} /></div>
                            {p.termine ? <p className="text-sm text-emerald-600">{t('courses.path_done', 'Parcours terminé, bravo !')}</p> : <ProchainCours parcours={p} />}
                            <Link href={`/mes-cours/${p.id}`} className="text-sm font-medium text-primary hover:underline">{t('courses.see_path', 'Voir tout le parcours')}</Link>
                        </section>
                    );
                })}
            </div>
        </AppLayout>
    );
}
