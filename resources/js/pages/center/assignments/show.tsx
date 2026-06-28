import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Column } from '@/components/ui/data-table';

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

export default function AssignmentShow({ assignment, rows }: { assignment: Assignment; rows: Row[] }) {
    const columns: Column<Row>[] = [
        { key: 'name', header: 'Élève', cell: (r) => <span className="font-semibold">{r.name}</span> },
        { key: 'progress', header: 'Avancement', cell: (r) => `${r.done} / ${r.total}`, sortValue: (r) => r.done },
        {
            key: 'avg_accuracy',
            header: 'Précision',
            cell: (r) => (r.avg_accuracy != null ? `${r.avg_accuracy}%` : '—'),
            sortValue: (r) => r.avg_accuracy ?? -1,
        },
        {
            key: 'status',
            header: 'Statut',
            cell: (r) => <Badge variant={STATUS[r.status].variant}>{STATUS[r.status].label}</Badge>,
            sortValue: (r) => r.status,
        },
    ];

    const doneCount = rows.filter((r) => r.status === 'done').length;

    return (
        <AppLayout>
            <Head title={assignment.title} />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('center.assignments.index')} className="text-sm text-muted-foreground hover:text-foreground">← Devoirs</Link>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">{assignment.title}</h1>
                    <p className="text-sm text-muted-foreground">
                        {assignment.classroom} · {assignment.items_count} exercice(s)
                        {assignment.due_at && <> · échéance {new Date(assignment.due_at).toLocaleDateString()}</>}
                    </p>
                </div>

                <Card>
                    <CardContent className="p-4">
                        <p className="text-2xl font-bold">{doneCount} / {rows.length}</p>
                        <p className="text-sm text-muted-foreground">élèves ont terminé</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Suivi par élève</CardTitle></CardHeader>
                    <CardContent>
                        <DataTable columns={columns} rows={rows} rowKey={(r) => r.user_id} empty="Aucun élève dans cette classe." />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
