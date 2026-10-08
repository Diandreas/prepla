import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface Classroom {
    id: number;
    name: string;
    level: string | null;
    exam: string | null;
    exam_id: number | null;
    invite_code: string;
}
interface ExamOption { id: number; name: string; language?: string | null }
interface Student { id: number; name: string; email: string }
interface Weakness { category: string; subcategory: string | null; count: number }
interface Stats {
    student_count: number;
    class_avg_accuracy: number | null;
    dropouts: { user_id: number; name: string; attempts: number }[];
    common_weaknesses: Weakness[];
}

export default function ClassShow({ classroom, students, stats, exams }: { classroom: Classroom; students: Student[]; stats: Stats; exams: ExamOption[] }) {
    const page = usePage().props as any;
    const { flash } = page;
    // Archiver est reserve au responsable de l'espace : le bouton etait aussi
    // montre aux enseignants, chez qui il ne pouvait que renvoyer un refus.
    const peutArchiver = page.auth?.center?.role === 'center_admin' || page.auth?.role === 'super_admin';

    // Renommer une classe, corriger son niveau ou changer son examen etait possible
    // cote serveur depuis le debut, mais aucun bouton n'y menait.
    const [editing, setEditing] = useState(false);
    const edit = useForm({
        name: classroom.name,
        level: classroom.level ?? '',
        exam_id: classroom.exam_id ? String(classroom.exam_id) : '',
    });

    function saveClass(event: React.FormEvent) {
        event.preventDefault();
        edit.patch(route('center.classes.update', classroom.id), {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    }

    function regenerate() {
        router.post(route('center.classes.regenerate-code', classroom.id), {}, { preserveScroll: true });
    }

    function removeStudent(studentId: number) {
        router.delete(route('center.classes.students.remove', [classroom.id, studentId]), { preserveScroll: true });
    }

    function archiveClass() {
        if (confirm(`Archiver « ${classroom.name} » ? Les élèves n'y auront plus accès. L'historique des devoirs et des tentatives est conservé.`)) {
            router.delete(route('center.classes.archive', classroom.id));
        }
    }

    return (
        <AppLayout>
            <Head title={classroom.name} />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <Link href={route('center.classes.index')} className="text-sm text-muted-foreground hover:text-foreground">
                            ← Classes
                        </Link>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight">{classroom.name}</h1>
                        <p className="text-sm text-muted-foreground">
                            {classroom.level ?? 'Niveau libre'} · {classroom.exam ?? 'Examen par défaut'}
                        </p>
                    </div>
                    <div className="flex shrink-0 items-center gap-1">
                        <Button variant="ghost" onClick={() => setEditing((open) => !open)} aria-expanded={editing}>
                            {editing ? 'Annuler' : 'Modifier'}
                        </Button>
                        {peutArchiver && (
                            <Button variant="ghost" className="text-rose-500 hover:text-rose-600" onClick={archiveClass}>
                                Archiver
                            </Button>
                        )}
                    </div>
                </div>

                {editing && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Modifier la classe</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={saveClass} className="space-y-4">
                                <div className="grid gap-2">
                                    <label htmlFor="class-edit-name" className="text-sm font-semibold">Nom</label>
                                    <input
                                        id="class-edit-name"
                                        className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                        value={edit.data.name}
                                        onChange={(event) => edit.setData('name', event.target.value)}
                                    />
                                    {edit.errors.name && <p className="text-xs text-rose-500">{edit.errors.name}</p>}
                                </div>

                                <div className="grid gap-4 sm:grid-cols-[7rem_1fr]">
                                    <div className="grid gap-2">
                                        <label htmlFor="class-edit-level" className="text-sm font-semibold">Niveau</label>
                                        <input
                                            id="class-edit-level"
                                            placeholder="A2"
                                            className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm uppercase focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                            value={edit.data.level}
                                            onChange={(event) => edit.setData('level', event.target.value.toUpperCase())}
                                            maxLength={10}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <label htmlFor="class-edit-exam" className="text-sm font-semibold">Examen</label>
                                        <select
                                            id="class-edit-exam"
                                            className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                            value={edit.data.exam_id}
                                            onChange={(event) => edit.setData('exam_id', event.target.value)}
                                        >
                                            <option value="">Examen par défaut de l'espace</option>
                                            {exams.map((exam) => (
                                                <option key={exam.id} value={exam.id}>
                                                    {exam.language ? `${exam.language} — ${exam.name}` : exam.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                <Button type="submit" disabled={edit.processing || !edit.data.name.trim()}>
                                    {edit.processing ? 'Enregistrement…' : 'Enregistrer'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {flash?.success && (
                    <div className="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                        {flash.success}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Code d'invitation</CardTitle>
                    </CardHeader>
                    <CardContent className="flex items-center justify-between gap-3">
                        <div>
                            <code className="rounded bg-muted px-3 py-1.5 text-lg font-bold tracking-widest">{classroom.invite_code}</code>
                            <p className="mt-1 text-xs text-muted-foreground">Vos élèves saisissent ce code sur /join pour rejoindre la classe.</p>
                        </div>
                        <Button variant="outline" onClick={regenerate}>Régénérer</Button>
                    </CardContent>
                </Card>

                {/* Vue agrégée de la classe */}
                <div className="grid gap-3 sm:grid-cols-3">
                    <Card><CardContent className="p-4"><p className="text-2xl font-bold">{stats.student_count}</p><p className="text-sm text-muted-foreground">Élèves</p></CardContent></Card>
                    <Card><CardContent className="p-4"><p className="text-2xl font-bold">{stats.class_avg_accuracy != null ? `${stats.class_avg_accuracy}%` : '—'}</p><p className="text-sm text-muted-foreground">Précision moyenne</p></CardContent></Card>
                    <Card><CardContent className="p-4"><p className="text-2xl font-bold text-rose-500">{stats.dropouts.length}</p><p className="text-sm text-muted-foreground">Décrocheurs</p></CardContent></Card>
                </div>

                {stats.common_weaknesses.length > 0 && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">Faiblesses communes de la classe</CardTitle></CardHeader>
                        <CardContent className="space-y-2">
                            {stats.common_weaknesses.map((w, i) => (
                                <div key={i} className="flex items-center justify-between rounded-lg border border-border p-2.5 text-sm">
                                    <span className="capitalize">{w.category.replace(/[._]/g, ' ')}{w.subcategory && <span className="text-muted-foreground"> · {w.subcategory}</span>}</span>
                                    <Badge variant="secondary">{w.count}</Badge>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {stats.dropouts.length > 0 && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">À relancer ({stats.dropouts.length})</CardTitle></CardHeader>
                        <CardContent className="space-y-2">
                            {stats.dropouts.map((d) => (
                                <div key={d.user_id} className="flex items-center justify-between rounded-lg border border-border p-2.5 text-sm">
                                    <Link href={route('center.students.show', d.user_id)} className="font-medium hover:underline">{d.name}</Link>
                                    <span className="text-xs text-muted-foreground">{d.attempts === 0 ? 'Aucune activité' : 'Inactif ≥ 7 j'}</span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <div className="flex justify-end">
                    <a href={route('center.classes.export', classroom.id)}>
                        <Button variant="outline">Exporter en CSV</Button>
                    </a>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Élèves ({students.length})</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {students.length === 0 && <p className="text-sm text-muted-foreground">Aucun élève n'a encore rejoint cette classe.</p>}
                        {students.map((s) => (
                            <div key={s.id} className="flex items-center justify-between rounded-lg border border-border p-2.5 text-sm">
                                <Link href={route('center.students.show', s.id)} className="min-w-0 hover:underline">
                                    <p className="truncate font-medium">{s.name}</p>
                                    <p className="truncate text-xs text-muted-foreground">{s.email}</p>
                                </Link>
                                <button onClick={() => removeStudent(s.id)} className="shrink-0 text-xs text-rose-500 hover:underline">
                                    Retirer
                                </button>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
