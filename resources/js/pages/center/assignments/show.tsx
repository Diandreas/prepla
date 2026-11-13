import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Column } from '@/components/ui/data-table';
import { useT } from '@/lib/i18n/traduire';
import { competenceDe, libelleDuType, libelleExercice, type LigneExercice } from '@/lib/createur-exercices';

interface Assignment { id: number; title: string; classroom: string | null; due_at: string | null; items_count: number }
interface Row {
    user_id: number;
    name: string;
    email: string;
    done: number;
    total: number;
    status: 'done' | 'in_progress' | 'late' | 'not_started';
    avg_accuracy: number | null;
}

const STATUS: Record<Row['status'], { label: string; variant: 'default' | 'secondary' | 'destructive' }> = {
    done: { label: 'Fait', variant: 'default' },
    in_progress: { label: 'En cours', variant: 'secondary' },
    late: { label: 'En retard', variant: 'destructive' },
    not_started: { label: 'Pas commencé', variant: 'secondary' },
};

export default function AssignmentShow({ assignment, rows, exercises = [], centerExercises = [], canEdit = false }: {
    assignment: Assignment;
    rows: Row[];
    exercises?: LigneExercice[];
    centerExercises?: LigneExercice[];
    canEdit?: boolean;
}) {
    const { t, locale } = useT();
    const nomExercice = (e: LigneExercice) => (String(e.titre ?? '').trim() !== '' ? libelleExercice(e) : t(libelleExercice(e)));
    const [ajout, setAjout] = useState(false);
    const [choix, setChoix] = useState('');
    const [envoi, setEnvoi] = useState(false);
    const dejaLa = new Set(exercises.map((e) => e.id));
    const ajoutables = centerExercises.filter((e) => !dejaLa.has(e.id));

    function ajouterExistant() {
        if (!choix) return;
        setEnvoi(true);
        router.post(route('center.assignments.exercises.add', assignment.id), { exercise_ids: [Number(choix)] }, {
            preserveScroll: true,
            onSuccess: () => { setAjout(false); setChoix(''); },
            onFinish: () => setEnvoi(false),
        });
    }

    const columns: Column<Row>[] = [
        { key: 'name', header: t('Élève'), cell: (r) => <span className="font-semibold">{r.name}</span> },
        { key: 'progress', header: t('Avancement'), cell: (r) => `${r.done} / ${r.total}`, sortValue: (r) => r.done },
        {
            key: 'avg_accuracy',
            header: t('Précision'),
            cell: (r) => (r.avg_accuracy != null ? `${r.avg_accuracy}%` : '—'),
            sortValue: (r) => r.avg_accuracy ?? -1,
        },
        {
            key: 'status',
            header: t('Statut'),
            cell: (r) => <Badge variant={STATUS[r.status].variant}>{t(STATUS[r.status].label)}</Badge>,
            sortValue: (r) => r.status,
        },
    ];

    const doneCount = rows.filter((r) => r.status === 'done').length;

    function destroyAssignment() {
        if (confirm(t('Supprimer « {titre} » ? Cette action est définitive.', { titre: assignment.title }))) {
            router.delete(route('center.assignments.destroy', assignment.id));
        }
    }

    return (
        <AppLayout>
            <Head title={assignment.title} />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <Link href={route('center.assignments.index')} className="text-sm text-muted-foreground hover:text-foreground">{t('← Devoirs')}</Link>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight">{assignment.title}</h1>
                        <p className="text-sm text-muted-foreground">
                            {assignment.classroom} · {assignment.items_count > 1 ? t('{n} exercices', { n: assignment.items_count }) : t('{n} exercice', { n: assignment.items_count })}
                            {assignment.due_at && <> · {t('échéance {date}', { date: new Date(assignment.due_at).toLocaleDateString(locale) })}</>}
                        </p>
                    </div>
                    <Button variant="ghost" className="text-rose-500 hover:text-rose-600" onClick={destroyAssignment}>
                        {t('Supprimer')}
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-4">
                        <p className="text-2xl font-bold">{doneCount} / {rows.length}</p>
                        <p className="text-sm text-muted-foreground">{t('élèves ont terminé')}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <CardTitle className="text-base">{t('Exercices du devoir')}</CardTitle>
                            {canEdit && !ajout && (
                                <Button type="button" variant="outline" size="sm" onClick={() => setAjout(true)}>
                                    <Plus className="h-4 w-4" aria-hidden="true" /> {t('Ajouter un exercice')}
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {exercises.length === 0 && <p className="text-sm text-muted-foreground">{t('Aucun exercice dans ce devoir.')}</p>}
                        <ol className="space-y-2">
                            {exercises.map((e, i) => (
                                <li key={e.id} className="flex items-start gap-3 rounded-xl border border-border p-3 text-sm">
                                    <span className="mt-0.5 w-5 shrink-0 text-center text-xs font-bold text-muted-foreground">{i + 1}</span>
                                    <span className="min-w-0">
                                        <span className="block font-semibold">{nomExercice(e)}</span>
                                        <span className="text-xs text-muted-foreground">
                                            {t(libelleDuType({ slug: e.type_slug, component_key: e.component_key, name: e.type }))} · {t(competenceDe(e.skill_type).nom)}
                                            {e.difficulty ? ` · ${e.difficulty}` : ''}{e.source === 'prepla' ? ` · ${t('Banque PrePla')}` : ''}
                                        </span>
                                    </span>
                                </li>
                            ))}
                        </ol>
                        {canEdit && ajout && (
                            <div className="space-y-3 rounded-xl border border-dashed border-border p-3">
                                <label htmlFor="ajout-exercice" className="text-sm font-medium">{t('Un exercice du centre')}</label>
                                <div className="flex flex-col gap-2 sm:flex-row">
                                    <select id="ajout-exercice" className="min-h-10 w-full rounded-lg border border-border bg-background px-3 text-base sm:text-sm" value={choix} onChange={(e) => setChoix(e.target.value)}>
                                        <option value="">{ajoutables.length ? t('Choisir…') : t('Tous les exercices du centre y sont déjà')}</option>
                                        {ajoutables.map((e) => (
                                            <option key={e.id} value={e.id}>{nomExercice(e)}{e.difficulty ? ` · ${e.difficulty}` : ''}</option>
                                        ))}
                                    </select>
                                    <Button type="button" onClick={ajouterExistant} disabled={!choix || envoi}>{envoi ? t('Ajout…') : t('Ajouter')}</Button>
                                </div>
                                <div className="flex flex-wrap items-center gap-3 text-sm">
                                    <span className="text-muted-foreground">{t('ou')}</span>
                                    <Link href={route('center.exercises.create', { assignment_id: assignment.id })} className="font-semibold text-primary hover:underline">
                                        {t('Créer un nouvel exercice pour ce devoir')}
                                    </Link>
                                    <button type="button" className="ml-auto text-muted-foreground hover:text-foreground" onClick={() => setAjout(false)}>{t('Annuler')}</button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">{t('Suivi par élève')}</CardTitle></CardHeader>
                    <CardContent>
                        <DataTable columns={columns} rows={rows} rowKey={(r) => r.user_id} empty={t('Aucun élève dans cette classe.')} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
