import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import DomainesDuCentre, { type DomaineDuCentre, type ReglagesDomaines } from '@/components/domaines-du-centre';

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
    formule: 'paliers' | 'revente' | 'libre_service';
    prix_acces: number | null;
    duree_acces_jours: number | null;
}

const FORMULES: { valeur: Center['formule']; titre: string; aide: string }[] = [
    { valeur: 'paliers', titre: 'Paliers', aide: 'Le centre paie selon ses élèves actifs ; ses élèves sont illimités.' },
    { valeur: 'revente', titre: 'Revente', aide: "Ses élèves suivent la limite gratuite, sauf ceux à qui le centre ouvre un accès (30 % pour PrePla)." },
    { valeur: 'libre_service', titre: 'Libre-service', aide: 'Espace ouvert seul sur /enseigner : son personnel garde la limite gratuite.' },
];

interface Staff { id: number; name: string; email: string; role: string | null }
interface Classroom { id: number; name: string; invite_code: string }
interface Exam { id: number; name: string }

export default function CenterShow({
    center,
    staff,
    classrooms,
    exams,
    domaines = [],
    domainesReglages,
}: {
    center: Center;
    staff: Staff[];
    classrooms: Classroom[];
    exams: Exam[];
    domaines?: DomaineDuCentre[];
    domainesReglages?: ReglagesDomaines;
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

    const formule = useForm({
        formule: center.formule,
        prix_acces: center.prix_acces ?? '',
        duree_acces_jours: center.duree_acces_jours ?? '',
    });

    function saveFormule(e: React.FormEvent) {
        e.preventDefault();
        formule.patch(route('admin.centers.formule', center.id), { preserveScroll: true });
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
                        <CardTitle className="text-base">Formule</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={saveFormule} className="space-y-4">
                            <div className="space-y-2">
                                {FORMULES.map((f) => (
                                    <label key={f.valeur} className="flex items-start gap-2 text-sm">
                                        <input
                                            type="radio"
                                            name="formule"
                                            className="mt-1"
                                            checked={formule.data.formule === f.valeur}
                                            onChange={() => formule.setData('formule', f.valeur)}
                                        />
                                        <span>
                                            <span className="font-medium">{f.titre}</span>
                                            <span className="block text-xs text-muted-foreground">{f.aide}</span>
                                        </span>
                                    </label>
                                ))}
                                {formule.errors.formule && <p className="text-xs text-red-600">{formule.errors.formule}</p>}
                            </div>
                            {formule.data.formule === 'revente' && (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label className="text-sm font-medium">Prix d'un accès (FCFA)</label>
                                        <input type="number" min={0} step={1} className={inputClass} value={formule.data.prix_acces}
                                            onChange={(e) => formule.setData('prix_acces', e.target.value === '' ? '' : Number(e.target.value))} />
                                        {formule.errors.prix_acces && <p className="mt-1 text-xs text-red-600">{formule.errors.prix_acces}</p>}
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium">Durée d'un accès (jours)</label>
                                        <input type="number" min={1} max={366} className={inputClass} value={formule.data.duree_acces_jours} placeholder="30"
                                            onChange={(e) => formule.setData('duree_acces_jours', e.target.value === '' ? '' : Number(e.target.value))} />
                                        {formule.errors.duree_acces_jours && <p className="mt-1 text-xs text-red-600">{formule.errors.duree_acces_jours}</p>}
                                    </div>
                                </div>
                            )}
                            <Button type="submit" disabled={formule.processing}>{formule.processing ? 'Enregistrement…' : 'Enregistrer la formule'}</Button>
                        </form>
                    </CardContent>
                </Card>

                {domainesReglages && <DomainesDuCentre centreId={center.id} domaines={domaines} reglages={domainesReglages} />}

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
