import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface ExamOption { id: number; name: string; language?: string | null }

/**
 * Ouvrir son espace d'enseignant. Les classes, les codes d'invitation et le suivi
 * des élèves existaient déjà, mais seul un super-administrateur pouvait créer
 * l'espace qui les porte : un professeur devait écrire et attendre.
 */
export default function OpenTeacherSpace({ existing, role, exams, suggestedName }: {
    existing?: { id: number; name: string } | null;
    role?: string | null;
    exams: ExamOption[];
    suggestedName: string;
}) {
    const form = useForm({
        name: suggestedName,
        exam_id: '' as string,
        classroom_name: 'Ma première classe',
        level: '',
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.post(route('teach.open.store'));
    }

    return (
        <AppLayout>
            <Head title="Enseigner avec PrePla" />
            <div className="mx-auto w-full max-w-xl space-y-5 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Suivre mes élèves</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Crée ton espace, invite tes élèves avec un code, et suis ce qu'ils réussissent — et ce qu'ils ratent.
                    </p>
                </div>

                {existing ? (
                    <Card>
                        <CardContent className="space-y-3 p-4 text-sm">
                            <p>
                                Tu fais déjà partie de l'espace <strong>{existing.name}</strong>
                                {role === 'student' ? ' en tant qu’élève.' : '.'}
                            </p>
                            {role !== 'student' && (
                                <Button asChild className="w-full">
                                    <Link href={route('center.dashboard')}>Ouvrir mon espace</Link>
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Mon espace</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submit} className="space-y-4">
                                    <div className="space-y-1.5">
                                        <label htmlFor="space-name" className="text-sm font-semibold">Nom de l'espace</label>
                                        <input
                                            id="space-name"
                                            autoFocus
                                            className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                            value={form.data.name}
                                            onChange={(event) => form.setData('name', event.target.value)}
                                            maxLength={120}
                                        />
                                        <p className="text-xs text-muted-foreground">Ce que tes élèves verront. Ton nom, ton école, ton cours.</p>
                                        {form.errors.name && <p className="text-xs text-rose-500">{form.errors.name}</p>}
                                    </div>

                                    <div className="space-y-1.5">
                                        <label htmlFor="space-exam" className="text-sm font-semibold">Examen préparé</label>
                                        <select
                                            id="space-exam"
                                            className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                            value={form.data.exam_id}
                                            onChange={(event) => form.setData('exam_id', event.target.value)}
                                        >
                                            <option value="">Je choisirai plus tard</option>
                                            {exams.map((exam) => (
                                                <option key={exam.id} value={exam.id}>
                                                    {exam.language ? `${exam.language} — ${exam.name}` : exam.name}
                                                </option>
                                            ))}
                                        </select>
                                        {form.errors.exam_id && <p className="text-xs text-rose-500">{form.errors.exam_id}</p>}
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-[1fr_7rem]">
                                        <div className="space-y-1.5">
                                            <label htmlFor="class-name" className="text-sm font-semibold">Première classe</label>
                                            <input
                                                id="class-name"
                                                className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                                value={form.data.classroom_name}
                                                onChange={(event) => form.setData('classroom_name', event.target.value)}
                                                maxLength={120}
                                            />
                                            {form.errors.classroom_name && <p className="text-xs text-rose-500">{form.errors.classroom_name}</p>}
                                        </div>
                                        <div className="space-y-1.5">
                                            <label htmlFor="class-level" className="text-sm font-semibold">Niveau</label>
                                            <input
                                                id="class-level"
                                                placeholder="A2"
                                                className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm uppercase focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                                value={form.data.level}
                                                onChange={(event) => form.setData('level', event.target.value.toUpperCase())}
                                                maxLength={10}
                                            />
                                        </div>
                                    </div>

                                    <Button type="submit" disabled={form.processing || !form.data.name.trim() || !form.data.classroom_name.trim()} className="w-full">
                                        {form.processing ? 'Ouverture…' : 'Ouvrir mon espace'}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="space-y-2 p-4 text-sm text-muted-foreground">
                                <p className="font-semibold text-foreground">Ensuite, en trois gestes</p>
                                <p>1. Ta classe reçoit un <strong>code d'invitation</strong> ; tes élèves le saisissent sur <code>/join</code>.</p>
                                <p>2. Tu suis chacun d'eux : précision, séries, et les catégories d'erreurs qui reviennent.</p>
                                <p>3. Tu leur envoies un devoir — ils reçoivent une notification.</p>
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
