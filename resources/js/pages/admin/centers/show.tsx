import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface Center {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    seats_limit: number;
    seats_used: number;
    default_exam_id: number | null;
    default_exam: string | null;
    owner_email: string | null;
}

interface Staff { id: number; name: string; email: string; role: string | null }
interface Classroom { id: number; name: string; invite_code: string }
interface Exam { id: number; name: string }

export default function CenterShow({
    center,
    staff,
    classrooms,
    exams,
}: {
    center: Center;
    staff: Staff[];
    classrooms: Classroom[];
    exams: Exam[];
}) {
    const { flash } = usePage().props as any;

    const form = useForm({
        name: center.name,
        seats_limit: center.seats_limit,
        default_exam_id: center.default_exam_id ?? '',
        is_active: center.is_active,
    });

    function save(e: React.FormEvent) {
        e.preventDefault();
        form.patch(route('admin.centers.update', center.id), { preserveScroll: true });
    }

    const inputClass =
        'mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

    return (
        <AppLayout>
            <Head title={center.name} />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('admin.centers.index')} className="text-sm text-muted-foreground hover:text-foreground">
                        ← Centres
                    </Link>
                    <div className="mt-1 flex items-center gap-3">
                        <h1 className="text-2xl font-bold tracking-tight">{center.name}</h1>
                        <Badge variant={center.is_active ? 'default' : 'secondary'}>{center.is_active ? 'Actif' : 'Inactif'}</Badge>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {center.seats_used} / {center.seats_limit} sièges utilisés
                    </p>
                </div>

                {flash?.success && (
                    <div className="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                        {flash.success}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Paramètres</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={save} className="space-y-4">
                            <div>
                                <label className="text-sm font-medium">Nom</label>
                                <input className={inputClass} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="text-sm font-medium">Sièges</label>
                                    <input type="number" min={1} className={inputClass} value={form.data.seats_limit} onChange={(e) => form.setData('seats_limit', Number(e.target.value))} />
                                </div>
                                <div>
                                    <label className="text-sm font-medium">Examen par défaut</label>
                                    <select className={inputClass} value={form.data.default_exam_id} onChange={(e) => form.setData('default_exam_id', e.target.value)}>
                                        <option value="">— Aucun —</option>
                                        {exams.map((ex) => (
                                            <option key={ex.id} value={ex.id}>{ex.name}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            <label className="flex items-center gap-2 text-sm">
                                <input type="checkbox" checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} />
                                Centre actif
                            </label>
                            <Button type="submit" disabled={form.processing}>{form.processing ? 'Enregistrement…' : 'Enregistrer'}</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Administrateurs & enseignants</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {staff.length === 0 && <p className="text-sm text-muted-foreground">Aucun membre du personnel.</p>}
                        {staff.map((s) => (
                            <div key={s.id} className="flex items-center justify-between rounded-lg border border-border p-2.5 text-sm">
                                <div>
                                    <p className="font-medium">{s.name}</p>
                                    <p className="text-xs text-muted-foreground">{s.email}</p>
                                </div>
                                <Badge variant="secondary">{s.role === 'center_admin' ? 'Admin' : 'Enseignant'}</Badge>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Classes ({classrooms.length})</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {classrooms.length === 0 && <p className="text-sm text-muted-foreground">Aucune classe encore créée par le centre.</p>}
                        {classrooms.map((c) => (
                            <div key={c.id} className="flex items-center justify-between rounded-lg border border-border p-2.5 text-sm">
                                <span className="font-medium">{c.name}</span>
                                <code className="rounded bg-muted px-2 py-0.5 text-xs">{c.invite_code}</code>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
