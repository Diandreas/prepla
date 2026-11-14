import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Plus, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { EnTetePage } from '@/components/center/liste-centre';
import { useT } from '@/lib/i18n/traduire';

interface Partie { cle: string; libelle: string | null; tache: string | null; items: number | null; exercise_type_id: number | null }
interface Section { slug: string; nom: string; duree: number | null; parties: Partie[] }
interface Exercice {
    id: number;
    titre: string | null;
    type: string | null;
    difficulty: string | null;
    questions_count: number;
    source: 'centre' | 'prepla';
    exercise_type_id: number;
}
type Choix = { exercise_id: number; section_slug: string; partie: string | null };
interface Props {
    examen: { id: number; title: string; description: string | null; published: boolean; durees: Record<string, number>; items: Choix[] } | null;
    exams: { id: number; name: string }[];
    niveaux: string[];
    examId: number | null;
    niveau: string | null;
    sections: Section[];
    exercices: Exercice[];
}

/**
 * Composer (ou modifier) un examen blanc : l'examen et le niveau d'abord, puis
 * pour chaque partie officielle un ou plusieurs exercices, et le minutage par
 * section. Changer d'examen ou de niveau recharge la structure.
 */
export default function ComposerExamenBlanc({ examen, exams, niveaux, examId, niveau, sections, exercices }: Props) {
    const { t } = useT();
    const [titre, setTitre] = useState(examen?.title ?? '');
    const [description, setDescription] = useState(examen?.description ?? '');
    const [choix, setChoix] = useState<Choix[]>(examen?.items ?? []);
    const [durees, setDurees] = useState<Record<string, number>>(() => {
        const depart: Record<string, number> = {};
        sections.forEach((s) => (depart[s.slug] = examen?.durees?.[s.slug] ?? s.duree ?? 30));
        return depart;
    });
    const [erreurs, setErreurs] = useState<Record<string, string>>({});
    const [envoi, setEnvoi] = useState(false);

    const parId = useMemo(() => new Map(exercices.map((e) => [e.id, e])), [exercices]);
    const total = Object.values(durees).reduce((a, b) => a + (Number(b) || 0), 0);

    // Changer d'examen ou de niveau : on recharge la page avec la nouvelle structure.
    const recharger = (exam: string, level: string) => {
        const adresse = examen ? `/center/examens-blancs/${examen.id}/modifier` : '/center/examens-blancs/creer';
        router.get(adresse, { exam_id: exam || undefined, level: level || undefined }, { preserveScroll: true });
    };

    const ajouter = (section: string, partie: string, id: number) => {
        if (!id) return;
        setChoix((c) => [...c, { exercise_id: id, section_slug: section, partie }]);
    };
    const retirer = (index: number) => setChoix((c) => c.filter((_, i) => i !== index));

    const enregistrer = (publier: boolean) => {
        if (!examId) return;
        setEnvoi(true);
        const donnees = { exam_id: examId, level: niveau, title: titre, description, durees, items: choix, publier };
        const options = {
            onError: (e: Record<string, string>) => setErreurs(e),
            onFinish: () => setEnvoi(false),
        };
        if (examen) router.put(`/center/examens-blancs/${examen.id}`, donnees, options);
        else router.post('/center/examens-blancs', donnees, options);
    };

    return (
        <AppLayout>
            <Head title={examen ? t('Modifier l’examen blanc') : t('Composer un examen blanc')} />
            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                <Link href="/center/examens-blancs" className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" /> {t('Examens blancs')}
                </Link>
                <EnTetePage titre={examen ? t('Modifier l’examen blanc') : t('Composer un examen blanc')} />

                <section className="grid gap-4 rounded-xl border border-border bg-card p-4 sm:grid-cols-2">
                    <label className="space-y-1 text-sm font-medium">
                        <span>{t('Examen')}</span>
                        <select
                            className="w-full rounded-md border border-input bg-background px-3 py-2"
                            value={examId ?? ''}
                            onChange={(e) => recharger(e.target.value, niveau ?? '')}
                        >
                            <option value="">{t('Choisir un examen')}</option>
                            {exams.map((e) => (
                                <option key={e.id} value={e.id}>{e.name}</option>
                            ))}
                        </select>
                    </label>
                    <label className="space-y-1 text-sm font-medium">
                        <span>{t('Niveau')}</span>
                        <select
                            className="w-full rounded-md border border-input bg-background px-3 py-2"
                            value={niveau ?? ''}
                            onChange={(e) => recharger(String(examId ?? ''), e.target.value)}
                        >
                            <option value="">{t('Épreuve unique (sans niveau)')}</option>
                            {niveaux.map((n) => (
                                <option key={n} value={n}>{n}</option>
                            ))}
                        </select>
                    </label>
                    <label className="space-y-1 text-sm font-medium sm:col-span-2">
                        <span>{t('Titre')}</span>
                        <input
                            className="w-full rounded-md border border-input bg-background px-3 py-2"
                            value={titre}
                            onChange={(e) => setTitre(e.target.value)}
                            placeholder={t('Examen blanc n° 1')}
                        />
                        {erreurs.title && <span className="text-xs text-rose-600">{erreurs.title}</span>}
                    </label>
                    <label className="space-y-1 text-sm font-medium sm:col-span-2">
                        <span>{t('Consigne pour les élèves (facultatif)')}</span>
                        <textarea
                            className="w-full rounded-md border border-input bg-background px-3 py-2"
                            rows={2}
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                        />
                    </label>
                </section>

                {!examId ? (
                    <p className="text-sm text-muted-foreground">{t('Choisissez un examen pour voir sa structure officielle.')}</p>
                ) : sections.length === 0 ? (
                    <p className="text-sm text-muted-foreground">{t('Cet examen n’a pas encore de structure à ce niveau.')}</p>
                ) : (
                    sections.map((s) => (
                        <section key={s.slug} className="space-y-3 rounded-xl border border-border bg-card p-4">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <h2 className="font-semibold">{s.nom}</h2>
                                <label className="flex items-center gap-2 text-sm">
                                    <span>{t('Durée (minutes)')}</span>
                                    <input
                                        type="number"
                                        min={0}
                                        max={600}
                                        className="w-20 rounded-md border border-input bg-background px-2 py-1"
                                        value={durees[s.slug] ?? 0}
                                        onChange={(e) => setDurees((d) => ({ ...d, [s.slug]: Number(e.target.value) }))}
                                    />
                                </label>
                            </div>
                            {s.parties.map((p) => {
                                const places = choix.map((c, i) => ({ c, i })).filter(({ c }) => c.section_slug === s.slug && c.partie === p.cle);
                                const possibles = exercices.filter((e) => !p.exercise_type_id || e.exercise_type_id === p.exercise_type_id);
                                return (
                                    <div key={p.cle} className="rounded-lg border border-border p-3">
                                        <p className="text-sm font-medium">
                                            {p.cle === 'libre' ? t('Exercices de la section') : t('Partie {n}', { n: p.cle })}
                                            {p.libelle ? ` · ${p.libelle}` : ''}
                                            {p.items ? ` · ${t('{n} items', { n: p.items })}` : ''}
                                        </p>
                                        {p.tache && <p className="text-xs text-muted-foreground">{p.tache}</p>}
                                        <ul className="mt-2 space-y-1">
                                            {places.map(({ c, i }) => {
                                                const e = parId.get(c.exercise_id);
                                                return (
                                                    <li key={i} className="flex items-center justify-between gap-2 rounded-md bg-muted px-2 py-1 text-sm">
                                                        <span className="min-w-0 truncate">
                                                            {e?.titre ?? e?.type ?? `#${c.exercise_id}`}
                                                            <span className="ml-2 text-xs text-muted-foreground">
                                                                {e?.source === 'centre' ? t('Centre') : t('PrePla')}
                                                            </span>
                                                        </span>
                                                        <button type="button" onClick={() => retirer(i)} aria-label={t('Retirer')} className="text-muted-foreground hover:text-foreground">
                                                            <X className="h-4 w-4" />
                                                        </button>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                        {possibles.length === 0 ? (
                                            <p className="mt-2 text-xs text-muted-foreground">{t('Aucun exercice disponible pour cette partie.')}</p>
                                        ) : (
                                            <label className="mt-2 flex items-center gap-2 text-sm">
                                                <Plus className="h-4 w-4 shrink-0" aria-hidden="true" />
                                                <select
                                                    className="w-full min-w-0 rounded-md border border-input bg-background px-2 py-1"
                                                    value=""
                                                    aria-label={t('Ajouter un exercice')}
                                                    onChange={(e) => ajouter(s.slug, p.cle, Number(e.target.value))}
                                                >
                                                    <option value="">{t('Ajouter un exercice')}</option>
                                                    {possibles.map((e) => (
                                                        <option key={e.id} value={e.id}>
                                                            {(e.source === 'centre' ? t('Centre') : t('PrePla')) + ' · ' + (e.titre ?? e.type ?? `#${e.id}`)}
                                                        </option>
                                                    ))}
                                                </select>
                                            </label>
                                        )}
                                    </div>
                                );
                            })}
                        </section>
                    ))
                )}

                {erreurs.items && <p className="text-sm text-rose-600">{erreurs.items}</p>}
                {erreurs.exam_id && <p className="text-sm text-rose-600">{erreurs.exam_id}</p>}

                <div className="flex flex-col gap-3 border-t border-border pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-sm text-muted-foreground">
                        {t('{n} exercices', { n: choix.length })} · {t('Durée totale : {n} min', { n: total })}
                    </p>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" disabled={envoi || !examId} onClick={() => enregistrer(false)}>
                            {t('Enregistrer le brouillon')}
                        </Button>
                        <Button disabled={envoi || !examId || choix.length === 0} onClick={() => enregistrer(true)}>
                            {t('Publier')}
                        </Button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
