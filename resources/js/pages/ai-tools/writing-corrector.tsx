import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { FileEdit } from 'lucide-react';
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
            <Head title="Writing Corrector" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold">Writing Corrector</h1>
                    <p className="text-muted-foreground">
                        Submit your writing and get AI-powered corrections and feedback
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Your Writing</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <label className="text-sm font-medium">Task Description (optional)</label>
                            <input
                                className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm"
                                placeholder="e.g., IELTS Writing Task 2: Discuss advantages and disadvantages..."
                                value={task}
                                onChange={(e) => setTask(e.target.value)}
                            />
                        </div>
                        <div>
                            <textarea
                                className="min-h-[250px] w-full rounded-lg border border-border bg-background p-4 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                placeholder="Paste or write your essay here..."
                                value={text}
                                onChange={(e) => setText(e.target.value)}
                            />
                            <p className="mt-1 text-xs text-muted-foreground">{wordCount} words</p>
                        </div>
                        <Button onClick={handleSubmit} disabled={!text.trim() || submitting} className="gap-2">
                            <FileEdit className="h-4 w-4" />
                            {submitting ? 'Analyzing...' : 'Analyze Writing'}
                        </Button>
                    </CardContent>
                </Card>

                {correction && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Feedback</CardTitle>
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
