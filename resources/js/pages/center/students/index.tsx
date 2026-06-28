import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { DataTable, type Column } from '@/components/ui/data-table';

interface StudentRow {
    id: number;
    name: string;
    email: string;
    classrooms: string[];
    attempts: number;
}

export default function StudentsIndex({ students }: { students: StudentRow[] }) {
    const columns: Column<StudentRow>[] = [
        { key: 'name', header: 'Élève', cell: (s) => <span className="font-semibold">{s.name}</span> },
        { key: 'email', header: 'Email', cell: (s) => <span className="text-muted-foreground">{s.email}</span> },
        { key: 'classrooms', header: 'Classes', cell: (s) => (s.classrooms.length ? s.classrooms.join(', ') : '—'), sortValue: (s) => s.classrooms.join(',') },
        { key: 'attempts', header: 'Exercices faits' },
    ];

    return (
        <AppLayout>
            <Head title="Élèves" />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Élèves</h1>
                    <p className="text-sm text-muted-foreground">Cliquez un élève pour voir son suivi détaillé.</p>
                </div>

                <DataTable
                    columns={columns}
                    rows={students}
                    rowKey={(s) => s.id}
                    onRowClick={(s) => router.visit(route('center.students.show', s.id))}
                    empty="Aucun élève n'a encore rejoint le centre."
                />
            </div>
        </AppLayout>
    );
}
