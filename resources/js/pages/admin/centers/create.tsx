import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Exam {
    id: number;
    name: string;
}

export default function CenterCreate({ exams }: { exams: Exam[] }) {
    const form = useForm({
        name: '',
        seats_limit: 50,
        default_exam_id: '' as string | number,
        admin_name: '',
        admin_email: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(route('admin.centers.store'));
    }

    const inputClass =
        'mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

    return (
        <AppLayout>
            <Head title="Nouveau centre" />
            <div className="mx-auto w-full max-w-2xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('admin.centers.index')} className="text-sm text-muted-foreground hover:text-foreground">
                        ← Centres
                    </Link>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">Nouveau centre de langue</h1>
                </div>

                <form onSubmit={submit} className="space-y-5">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Centre</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <label className="text-sm font-medium">Nom du centre</label>
                                <input className={inputClass} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                                {form.errors.name && <p className="mt-1 text-xs text-rose-500">{form.errors.name}</p>}
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="text-sm font-medium">Nombre de sièges (licences élèves)</label>
                                    <input
                                        type="number"
                                        min={1}
                                        className={inputClass}
                                        value={form.data.seats_limit}
                                        onChange={(e) => form.setData('seats_limit', Number(e.target.value))}
                                    />
                                    {form.errors.seats_limit && <p className="mt-1 text-xs text-rose-500">{form.errors.seats_limit}</p>}
                                </div>
                                <div>
                                    <label className="text-sm font-medium">Examen par défaut (facultatif)</label>
                                    <select
                                        className={inputClass}
                                        value={form.data.default_exam_id}
                                        onChange={(e) => form.setData('default_exam_id', e.target.value)}
                                    >
                                        <option value="">— Aucun —</option>
                                        {exams.map((ex) => (
                                            <option key={ex.id} value={ex.id}>
                                                {ex.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Administrateur du centre</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <p className="text-xs text-muted-foreground">
                                Ce compte gérera le centre (classes, élèves, contenu). S'il existe déjà, il sera rattaché ;
                                sinon un mot de passe provisoire sera généré.
                            </p>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="text-sm font-medium">Nom</label>
                                    <input className={inputClass} value={form.data.admin_name} onChange={(e) => form.setData('admin_name', e.target.value)} />
                                    {form.errors.admin_name && <p className="mt-1 text-xs text-rose-500">{form.errors.admin_name}</p>}
                                </div>
                                <div>
                                    <label className="text-sm font-medium">Email</label>
                                    <input type="email" className={inputClass} value={form.data.admin_email} onChange={(e) => form.setData('admin_email', e.target.value)} />
                                    {form.errors.admin_email && <p className="mt-1 text-xs text-rose-500">{form.errors.admin_email}</p>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? 'Création…' : 'Créer le centre'}
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
