import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface Student { id: number; name: string; email: string; level: string | null }
interface Weakness { error_category: string; subcategory: string | null; count: number }
interface Attempt { id: number; type: string | null; skill: string | null; accuracy: number | null; date: string }

export default function StudentShow({
    student,
    stats,
    weaknesses,
    recent_attempts,
}: {
    student: Student;
    stats: { total_attempts: number; avg_accuracy: number };
    weaknesses: Weakness[];
    recent_attempts: Attempt[];
}) {
    return (
        <AppLayout>
            <Head title={student.name} />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('center.students.index')} className="text-sm text-muted-foreground hover:text-foreground">
                        ← Élèves
                    </Link>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">{student.name}</h1>
                    <p className="text-sm text-muted-foreground">{student.email} · Niveau {student.level ?? '—'}</p>
                </div>

                <div className="grid gap-3 sm:grid-cols-2">
                    <Card><CardContent className="p-4"><p className="text-2xl font-bold">{stats.total_attempts}</p><p className="text-sm text-muted-foreground">Exercices réalisés</p></CardContent></Card>
                    <Card><CardContent className="p-4"><p className="text-2xl font-bold">{stats.avg_accuracy}%</p><p className="text-sm text-muted-foreground">Précision moyenne</p></CardContent></Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Faiblesses identifiées par l'IA</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {weaknesses.length === 0 && <p className="text-sm text-muted-foreground">Aucune faiblesse récurrente détectée.</p>}
                        {weaknesses.map((w, i) => (
                            <div key={i} className="flex items-center justify-between rounded-lg border border-border p-2.5 text-sm">
                                <span>
                                    <span className="font-medium capitalize">{w.error_category.replace(/[._]/g, ' ')}</span>
                                    {w.subcategory && <span className="text-muted-foreground"> · {w.subcategory}</span>}
                                </span>
                                <Badge variant="secondary">{w.count} erreur{w.count > 1 ? 's' : ''}</Badge>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Dernières tentatives</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {recent_attempts.length === 0 && <p className="text-sm text-muted-foreground">Aucune tentative.</p>}
                        {recent_attempts.map((a) => (
                            <div key={a.id} className="flex items-center justify-between rounded-lg border border-border p-2.5 text-sm">
                                <span>
                                    <span className="font-medium">{a.type ?? 'Exercice'}</span>
                                    {a.skill && <span className="text-muted-foreground"> · {a.skill}</span>}
                                </span>
                                <span className={`font-semibold ${(a.accuracy ?? 0) >= 70 ? 'text-emerald-500' : (a.accuracy ?? 0) >= 50 ? 'text-amber-500' : 'text-rose-500'}`}>
                                    {a.accuracy != null ? `${Math.round(a.accuracy)}%` : '—'}
                                </span>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
