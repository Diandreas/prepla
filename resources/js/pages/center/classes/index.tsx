import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DataTable, type Column } from '@/components/ui/data-table';
import { useState } from 'react';

interface ClassroomRow {
    id: number;
    name: string;
    level: string | null;
    exam: string | null;
    invite_code: string;
    students_count: number;
}
interface Exam { id: number; name: string }

export default function ClassesIndex({
    classrooms,
    exams,
    seatsAvailable,
}: {
    classrooms: ClassroomRow[];
    exams: Exam[];
    seatsAvailable: number;
}) {
    const [showForm, setShowForm] = useState(false);
    const form = useForm({ name: '', level: '', exam_id: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(route('center.classes.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setShowForm(false);
            },
        });
    }

    const inputClass =
        'mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

    const columns: Column<ClassroomRow>[] = [
        { key: 'name', header: 'Classe', cell: (c) => <span className="font-semibold">{c.name}</span> },
        { key: 'level', header: 'Niveau', cell: (c) => c.level ?? '—' },
        { key: 'exam', header: 'Examen', cell: (c) => c.exam ?? '—' },
        { key: 'students_count', header: 'Élèves' },
        { key: 'invite_code', header: 'Code', cell: (c) => <code className="rounded bg-muted px-2 py-0.5 text-xs">{c.invite_code}</code> },
    ];

    return (
        <AppLayout>
            <Head title="Classes" />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Classes</h1>
                        <p className="text-sm text-muted-foreground">{seatsAvailable} sièges disponibles</p>
                    </div>
                    <Button onClick={() => setShowForm((s) => !s)}>{showForm ? 'Annuler' : 'Nouvelle classe'}</Button>
                </div>

                {showForm && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Créer une classe</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="grid gap-4 sm:grid-cols-3">
                                <div className="sm:col-span-1">
                                    <label className="text-sm font-medium">Nom</label>
                                    <input className={inputClass} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                                    {form.errors.name && <p className="mt-1 text-xs text-rose-500">{form.errors.name}</p>}
                                </div>
                                <div>
                                    <label className="text-sm font-medium">Niveau</label>
                                    <select className={inputClass} value={form.data.level} onChange={(e) => form.setData('level', e.target.value)}>
                                        <option value="">—</option>
                                        {['A1', 'A2', 'B1', 'B2', 'C1', 'C2'].map((l) => (
                                            <option key={l} value={l}>{l}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="text-sm font-medium">Examen</label>
                                    <select className={inputClass} value={form.data.exam_id} onChange={(e) => form.setData('exam_id', e.target.value)}>
                                        <option value="">Défaut du centre</option>
                                        {exams.map((ex) => (
                                            <option key={ex.id} value={ex.id}>{ex.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="sm:col-span-3">
                                    <Button type="submit" disabled={form.processing}>{form.processing ? 'Création…' : 'Créer'}</Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <DataTable
                    columns={columns}
                    rows={classrooms}
                    rowKey={(c) => c.id}
                    onRowClick={(c) => router.visit(route('center.classes.show', c.id))}
                    empty="Aucune classe. Créez-en une et partagez le code d'invitation à vos élèves."
                />
            </div>
        </AppLayout>
    );
}
