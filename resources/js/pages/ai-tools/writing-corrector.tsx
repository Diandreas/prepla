import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useState } from 'react';

export default function WritingCorrector() {
    const { flash } = usePage().props as any;
    const correction = flash?.correction;
    const [text, setText] = useState('');
    const [task, setTask] = useState('');
    const [submitting, setSubmitting] = useState(false);

    function handleSubmit() {
        if (!text.trim()) return;
        setSubmitting(true);
        router.post(route('ai-tools.writing-corrector.store'), {
            text,
            task_description: task,
        }, {
            onFinish: () => setSubmitting(false),
        });
    }

    const wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;

    return (
        <AppLayout>
            <Head title="Correcteur de rédaction" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold">Correcteur de rédaction</h1>
                    <p className="text-muted-foreground">
                        Soumettez votre rédaction et recevez des corrections et des retours propulsés par l'IA
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Votre rédaction</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <label className="text-sm font-medium">Description de la tâche (facultatif)</label>
                            <input
                                className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm"
                                placeholder="ex. : IELTS Tâche 2 : Discutez des avantages et inconvénients..."
                                value={task}
                                onChange={(e) => setTask(e.target.value)}
                            />
                        </div>
                        <div>
                            <textarea
                                className="min-h-[250px] w-full rounded-lg border border-border bg-background p-4 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                placeholder="Collez ou rédigez votre texte ici..."
                                value={text}
                                onChange={(e) => setText(e.target.value)}
                            />
                            <p className="mt-1 text-xs text-muted-foreground">{wordCount} mots</p>
                        </div>
                        <Button onClick={handleSubmit} disabled={!text.trim() || submitting} className="gap-2">
                            <img src="/icons/file-edit.png" alt="" width={16} height={16} style={{ objectFit: 'contain' }} />
                            {submitting ? 'Analyse en cours...' : 'Analyser la rédaction'}
                        </Button>
                    </CardContent>
                </Card>

                {correction && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Retours</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                {Object.entries(correction.band_scores as Record<string, number>).map(([key, value]) => (
                                    <div key={key} className="rounded-lg border border-border p-3 text-center">
                                        <p className="text-2xl font-bold text-primary">{value}</p>
                                        <p className="text-xs text-muted-foreground capitalize">
                                            {key.replace(/_/g, ' ')}
                                        </p>
                                    </div>
                                ))}
                            </div>
                            <div className="rounded-lg bg-muted/30 p-4">
                                <p className="text-sm">{correction.feedback}</p>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
