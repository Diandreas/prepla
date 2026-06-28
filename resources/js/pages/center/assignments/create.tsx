import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Classroom { id: number; name: string }
interface ExerciseOpt { id: number; label: string }

export default function AssignmentCreate({ classrooms, exercises }: { classrooms: Classroom[]; exercises: ExerciseOpt[] }) {
    const form = useForm<{
        classroom_id: string;
        title: string;
        instructions: string;
        due_at: string;
        exercise_ids: number[];
    }>({
        classroom_id: classrooms[0]?.id ? String(classrooms[0].id) : '',
        title: '',
        instructions: '',
        due_at: '',
        exercise_ids: [],
    });

    function toggleExercise(id: number) {
        form.setData(
            'exercise_ids',
            form.data.exercise_ids.includes(id)
                ? form.data.exercise_ids.filter((x) => x !== id)
                : [...form.data.exercise_ids, id],
        );
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(route('center.assignments.store'));
    }

    const input = 'mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

    return (
        <AppLayout>
            <Head title="Nouveau devoir" />
            <div className="mx-auto w-full max-w-2xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('center.assignments.index')} className="text-sm text-muted-foreground hover:text-foreground">← Devoirs</Link>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">Nouveau devoir</h1>
                </div>

                <form onSubmit={submit} className="space-y-5">
                    <Card>
                        <CardHeader><CardTitle className="text-base">Détails</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="text-sm font-medium">Classe</label>
                                    <select className={input} value={form.data.classroom_id} onChange={(e) => form.setData('classroom_id', e.target.value)}>
                                        {classrooms.map((c) => (<option key={c.id} value={c.id}>{c.name}</option>))}
                                    </select>
                                    {form.errors.classroom_id && <p className="mt-1 text-xs text-rose-500">{form.errors.classroom_id}</p>}
                                </div>
                                <div>
                                    <label className="text-sm font-medium">Échéance (facultatif)</label>
                                    <input type="date" className={input} value={form.data.due_at} onChange={(e) => form.setData('due_at', e.target.value)} />
                                </div>
                            </div>
                            <div>
                                <label className="text-sm font-medium">Titre</label>
                                <input className={input} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                                {form.errors.title && <p className="mt-1 text-xs text-rose-500">{form.errors.title}</p>}
                            </div>
                            <div>
                                <label className="text-sm font-medium">Consigne (facultatif)</label>
                                <textarea className={`min-h-[60px] ${input}`} value={form.data.instructions} onChange={(e) => form.setData('instructions', e.target.value)} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle className="text-base">Exercices ({form.data.exercise_ids.length} sélectionné{form.data.exercise_ids.length > 1 ? 's' : ''})</CardTitle></CardHeader>
                        <CardContent className="space-y-2">
                            {exercises.length === 0 && <p className="text-sm text-muted-foreground">Aucun exercice. Créez d'abord du contenu.</p>}
                            {exercises.map((ex) => (
                                <label key={ex.id} className="flex items-center gap-2 rounded-lg border border-border p-2.5 text-sm">
                                    <input type="checkbox" checked={form.data.exercise_ids.includes(ex.id)} onChange={() => toggleExercise(ex.id)} />
                                    {ex.label}
                                </label>
                            ))}
                            {form.errors.exercise_ids && <p className="text-xs text-rose-500">{form.errors.exercise_ids}</p>}
                        </CardContent>
                    </Card>

                    <Button type="submit" disabled={form.processing || form.data.exercise_ids.length === 0}>
                        {form.processing ? 'Création…' : 'Créer le devoir'}
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
