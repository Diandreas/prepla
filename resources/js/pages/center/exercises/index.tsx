import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Column } from '@/components/ui/data-table';

interface ExerciseRow {
    id: number;
    type: string | null;
    component_key: string | null;
    difficulty: string;
    questions_count: number;
    is_ai_generated: boolean;
}

export default function ExercisesIndex({ exercises }: { exercises: ExerciseRow[] }) {
    // Creer et modifier etaient possibles, retirer ne l'etait pas : un brouillon
    // rate restait dans la liste pour toujours.
    function remove(exercise: ExerciseRow) {
        if (!confirm('Supprimer cet exercice ? Il reste conservé s’il est déjà donné en devoir ou déjà travaillé par un élève.')) return;
        router.delete(route('center.exercises.destroy', exercise.id), { preserveScroll: true });
    }

    const columns: Column<ExerciseRow>[] = [
        { key: 'type', header: 'Type', cell: (e) => <span className="font-semibold">{e.type ?? e.component_key}</span> },
        { key: 'difficulty', header: 'Niveau' },
        { key: 'questions_count', header: 'Questions' },
        {
            key: 'is_ai_generated',
            header: 'Origine',
            cell: (e) => <Badge variant="secondary">{e.is_ai_generated ? 'IA éditée' : 'Manuel'}</Badge>,
        },
        {
            key: 'actions',
            header: '',
            cell: (e) => (
                <button
                    type="button"
                    onClick={(event) => { event.stopPropagation(); remove(e); }}
                    className="rounded-lg px-2 py-1 text-xs font-bold text-rose-500 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
                    aria-label="Supprimer cet exercice"
                >
                    Supprimer
                </button>
            ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Contenu du centre" />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Contenu</h1>
                        <p className="text-sm text-muted-foreground">Vos exercices, à assigner à vos classes.</p>
                    </div>
                    <Link href={route('center.exercises.create')}>
                        <Button>Nouvel exercice</Button>
                    </Link>
                </div>

                <DataTable
                    columns={columns}
                    rows={exercises}
                    rowKey={(e) => e.id}
                    onRowClick={(e) => router.visit(route('center.exercises.edit', e.id))}
                    empty="Aucun exercice. Créez-en un (manuellement ou avec l'aide de l'IA)."
                />
            </div>
        </AppLayout>
    );
}
