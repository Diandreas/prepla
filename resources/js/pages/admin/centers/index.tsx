import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Column } from '@/components/ui/data-table';

interface CenterRow {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    seats_limit: number;
    seats_used: number;
    classrooms_count: number;
    default_exam: string | null;
}

export default function CentersIndex({ centers }: { centers: CenterRow[] }) {
    const columns: Column<CenterRow>[] = [
        { key: 'name', header: 'Centre', cell: (c) => <span className="font-semibold">{c.name}</span> },
        { key: 'default_exam', header: 'Examen défaut', cell: (c) => c.default_exam ?? '—' },
        {
            key: 'seats',
            header: 'Sièges',
            sortValue: (c) => c.seats_used,
            cell: (c) => `${c.seats_used} / ${c.seats_limit}`,
        },
        { key: 'classrooms_count', header: 'Classes' },
        {
            key: 'is_active',
            header: 'Statut',
            cell: (c) => (
                <Badge variant={c.is_active ? 'default' : 'secondary'}>
                    {c.is_active ? 'Actif' : 'Inactif'}
                </Badge>
            ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Centres de langue" />
            <div className="mx-auto w-full max-w-5xl space-y-5 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Centres de langue</h1>
                        <p className="text-sm text-muted-foreground">Gérez les établissements partenaires.</p>
                    </div>
                    <Link href={route('admin.centers.create')}>
                        <Button>Nouveau centre</Button>
                    </Link>
                </div>

                <DataTable
                    columns={columns}
                    rows={centers}
                    rowKey={(c) => c.id}
                    onRowClick={(c) => router.visit(route('admin.centers.show', c.id))}
                    empty="Aucun centre pour l'instant. Créez-en un."
                />
            </div>
        </AppLayout>
    );
}
