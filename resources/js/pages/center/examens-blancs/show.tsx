import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { EnTetePage } from '@/components/center/liste-centre';
import { useT } from '@/lib/i18n/traduire';

interface Props {
    examen: {
        id: number;
        title: string;
        description: string | null;
        exam: string | null;
        level: string | null;
        published: boolean;
        duree: number;
        items_count: number;
    };
    sections: { slug: string; nom: string; duree: number; exercices: number }[];
    passages: { eleve: string | null; score: number; sections: Record<string, number>; temps: number; date: string | null }[];
    classes: { id: number; name: string }[];
}

/** La fiche d'un examen blanc : sa composition, le donner a une classe, et les resultats. */
export default function ExamenBlanc({ examen, sections, passages, classes }: Props) {
    const { t, locale } = useT();
    const [classe, setClasse] = useState('');
    const [echeance, setEcheance] = useState('');
    const [erreur, setErreur] = useState<string | null>(null);

    const donner = () => {
        router.post(
            `/center/examens-blancs/${examen.id}/donner`,
            { classroom_id: Number(classe), due_at: echeance || null },
            { preserveScroll: true, onError: (e) => setErreur(Object.values(e)[0] ?? null), onSuccess: () => setErreur(null) },
        );
    };
    const supprimer = () => {
        if (window.confirm(t('Supprimer cet examen blanc ? Les devoirs qui le portent le perdront.'))) {
            router.delete(`/center/examens-blancs/${examen.id}`);
        }
    };

    return (
        <AppLayout>
            <Head title={examen.title} />
            <div className="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
                <Link href="/center/examens-blancs" className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" /> {t('Examens blancs')}
                </Link>
                <EnTetePage
                    titre={examen.title}
                    sousTitre={[examen.exam, examen.level, examen.published ? t('Publié') : t('Brouillon')].filter(Boolean).join(' · ')}
                    actions={
                        <>
                            <Button asChild variant="outline">
                                <Link href={`/center/examens-blancs/${examen.id}/modifier`}>
                                    <Pencil className="mr-1 h-4 w-4" aria-hidden="true" /> {t('Modifier')}
                                </Link>
                            </Button>
                            <Button variant="outline" onClick={supprimer}>
                                <Trash2 className="mr-1 h-4 w-4" aria-hidden="true" /> {t('Supprimer')}
                            </Button>
                        </>
                    }
                />

                <section className="rounded-xl border border-border bg-card p-4">
                    <h2 className="mb-2 font-semibold">{t('Composition')}</h2>
                    <ul className="divide-y divide-border text-sm">
                        {sections.map((s) => (
                            <li key={s.slug} className="flex justify-between gap-2 py-2">
                                <span>{s.nom}</span>
                                <span className="text-muted-foreground">
                                    {t('{n} exercices', { n: s.exercices })} · {t('{n} min', { n: s.duree })}
                                </span>
                            </li>
                        ))}
                    </ul>
                    <p className="mt-2 text-sm font-medium">{t('Durée totale : {n} min', { n: examen.duree })}</p>
                </section>

                <section className="space-y-3 rounded-xl border border-border bg-card p-4">
                    <h2 className="font-semibold">{t('Donner en devoir')}</h2>
                    {!examen.published ? (
                        <p className="text-sm text-muted-foreground">{t('Publiez l’examen blanc avant de le donner à une classe.')}</p>
                    ) : classes.length === 0 ? (
                        <p className="text-sm text-muted-foreground">{t('Aucune classe disponible.')}</p>
                    ) : (
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-end">
                            <label className="flex-1 space-y-1 text-sm">
                                <span>{t('Classe')}</span>
                                <select className="w-full rounded-md border border-input bg-background px-3 py-2" value={classe} onChange={(e) => setClasse(e.target.value)}>
                                    <option value="">{t('Choisir une classe')}</option>
                                    {classes.map((c) => (
                                        <option key={c.id} value={c.id}>{c.name}</option>
                                    ))}
                                </select>
                            </label>
                            <label className="space-y-1 text-sm">
                                <span>{t('À rendre pour (facultatif)')}</span>
                                <input type="date" className="w-full rounded-md border border-input bg-background px-3 py-2" value={echeance} onChange={(e) => setEcheance(e.target.value)} />
                            </label>
                            <Button disabled={!classe} onClick={donner}>{t('Donner à la classe')}</Button>
                        </div>
                    )}
                    {erreur && <p className="text-sm text-rose-600">{erreur}</p>}
                </section>

                <section className="rounded-xl border border-border bg-card p-4">
                    <h2 className="mb-2 font-semibold">{t('Résultats')}</h2>
                    {passages.length === 0 ? (
                        <p className="text-sm text-muted-foreground">{t('Aucune copie rendue pour le moment.')}</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-max text-sm">
                                <thead>
                                    <tr className="border-b border-border text-left text-muted-foreground">
                                        <th className="py-2 pr-3 font-medium">{t('Élève')}</th>
                                        <th className="py-2 pr-3 font-medium">{t('Score global')}</th>
                                        {sections.map((s) => (
                                            <th key={s.slug} className="py-2 pr-3 font-medium">{s.nom}</th>
                                        ))}
                                        <th className="py-2 pr-3 font-medium">{t('Temps')}</th>
                                        <th className="py-2 font-medium">{t('Rendu le')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {passages.map((p, i) => (
                                        <tr key={i} className="border-b border-border last:border-0">
                                            <td className="py-2 pr-3">{p.eleve}</td>
                                            <td className="py-2 pr-3 font-semibold">{p.score} %</td>
                                            {sections.map((s) => (
                                                <td key={s.slug} className="py-2 pr-3">{p.sections[s.slug] !== undefined ? `${p.sections[s.slug]} %` : '—'}</td>
                                            ))}
                                            <td className="py-2 pr-3">{t('{n} min', { n: p.temps })}</td>
                                            <td className="py-2">{p.date ? new Date(p.date).toLocaleDateString(locale) : '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
