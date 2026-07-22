import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Column } from '@/components/ui/data-table';

interface AssignmentRow {
    id: number;
    title: string;
    classroom: string | null;
    items_count: number;
    due_at: string | null;
    published: boolean;
}

export default function AssignmentsIndex({ assignments }: { assignments: AssignmentRow[] }) {
    const columns: Column<AssignmentRow>[] = [
        { key: 'title', header: 'Devoir', cell: (a) => <span className="font-semibold">{a.title}</span> },
        { key: 'classroom', header: 'Classe', cell: (a) => a.classroom ?? '—' },
        { key: 'items_count', header: 'Exercices' },
        {
            key: 'due_at',
            header: 'Échéance',
            // Locale explicite : sans elle, le format suit la locale OS/navigateur
            // (souvent M/J/AAAA en anglais US), ambigu dans une interface par
            // ailleurs entièrement en français.
            cell: (a) => (a.due_at ? new Date(a.due_at).toLocaleDateString('fr-FR') : '—'),
            sortValue: (a) => a.due_at ?? '',
        },
    ];

    return (
        <AppLayout>
            <Head title="Devoirs" />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Devoirs</h1>
                        <p className="text-sm text-muted-foreground">Assignez du contenu à vos classes et suivez leur avancement.</p>
                    </div>
                    <Link href={route('center.assignments.create')}>
                        <Button>Nouveau devoir</Button>
                    </Link>
                </div>

                <DataTable
                    columns={columns}
                    rows={assignments}
                    rowKey={(a) => a.id}
                    onRowClick={(a) => router.visit(route('center.assignments.show', a.id))}
                    empty="Aucun devoir. Créez-en un pour assigner des exercices à une classe."
                />
            </div>
        </AppLayout>
    );
}
