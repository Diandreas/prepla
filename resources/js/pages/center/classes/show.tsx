import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Classroom {
    id: number;
    name: string;
    level: string | null;
    exam: string | null;
    invite_code: string;
}
interface Student { id: number; name: string; email: string }

export default function ClassShow({ classroom, students }: { classroom: Classroom; students: Student[] }) {
    const { flash } = usePage().props as any;

    function regenerate() {
        router.post(route('center.classes.regenerate-code', classroom.id), {}, { preserveScroll: true });
    }

    function removeStudent(studentId: number) {
        router.delete(route('center.classes.students.remove', [classroom.id, studentId]), { preserveScroll: true });
    }

    return (
        <AppLayout>
            <Head title={classroom.name} />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('center.classes.index')} className="text-sm text-muted-foreground hover:text-foreground">
                        ← Classes
                    </Link>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">{classroom.name}</h1>
                    <p className="text-sm text-muted-foreground">
                        {classroom.level ?? 'Niveau libre'} · {classroom.exam ?? 'Examen par défaut'}
                    </p>
                </div>

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
