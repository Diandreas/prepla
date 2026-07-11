import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}
import type { ExerciseAttempt } from '@/types';

// correct_answer can be a string, an array (ordering/multi-select), or a plain
// object map (multi-field/matching types, e.g. {s1:"B",s2:"A",...}) — rendering
// that object directly as a React child crashes the whole tree (React error #31).
function formatCorrectAnswer(v: unknown): string {
    if (v == null) return '';
    if (Array.isArray(v)) return v.map(String).join(', ');
    if (typeof v === 'object') {
        return Object.entries(v as Record<string, unknown>).map(([k, val]) => `${k}: ${val}`).join(', ');
    }
    return String(v);
}

// explanation can also be an object for AI-evaluated types (essay/speaking) —
// same crash risk, same fix as exercise/result.tsx's asText().
function asText(v: any): string {
    if (v == null) return '';
    if (typeof v === 'string') return v;
    if (typeof v === 'number' || typeof v === 'boolean') return String(v);
    if (Array.isArray(v)) return v.map(asText).filter(Boolean).join(' ');
    if (typeof v === 'object') return asText(v.concept ?? v.text ?? v.message ?? v.hint ?? v.value ?? Object.values(v)[0]);
    return '';
}

interface Props {
    attempt: ExerciseAttempt;
}

export default function AttemptDetail({ attempt }: Props) {
    const feedback = (attempt.feedback ?? []) as Array<{
        question_id: string;
        correct: boolean;
        correct_answer: string | string[];
        explanation?: string;
    }>;
    const questions = attempt.exercise?.questions ?? [];
    const mins = attempt.time_spent ? Math.floor(attempt.time_spent / 60) : 0;
    const secs = attempt.time_spent ? attempt.time_spent % 60 : 0;

    return (
        <>
            <Head title="Attempt Detail" />
            <div className="mx-auto max-w-3xl space-y-6 px-4 py-8">
                <div>
                    <h1 className="text-2xl font-bold">
                        {attempt.exercise?.exercise_type?.name ?? 'Exercise'} Results
                    </h1>
                    <p className="text-muted-foreground">
                        {attempt.exercise?.exam?.name} · {new Date(attempt.created_at).toLocaleDateString()}
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="flex flex-col items-center py-6">
                            <div className="text-4xl font-bold text-primary">
                                {Number(attempt.accuracy_percent ?? 0).toFixed(0)}%
                            </div>
                            <p className="text-sm text-muted-foreground">Accuracy</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex flex-col items-center py-6">
                            <div className="flex items-center gap-1 text-4xl font-bold">
                                <Icon name="zap" size={24} className="text-yellow-500" />
                                {attempt.xp_earned}
                            </div>
                            <p className="text-sm text-muted-foreground">XP Earned</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex flex-col items-center py-6">
                            <div className="flex items-center gap-1 text-4xl font-bold">
                                <Icon name="clock" size={24} className="text-blue-500" />
                                {mins}:{String(secs).padStart(2, '0')}
                            </div>
                            <p className="text-sm text-muted-foreground">Time</p>
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-3">
                    <h2 className="text-xl font-semibold">Review</h2>
                    {feedback.map((item, i) => {
                        const question = questions.find((q) => q.id === item.question_id) ?? questions[i];
                        return (
                            <div
                                key={item.question_id}
                                className={`rounded-lg border p-4 ${
                                    item.correct
                                        ? 'border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-900/10'
                                        : 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-900/10'
                                }`}
                            >
                                <div className="flex items-start gap-2">
                                    {item.correct ? (
                                        <Icon name="check-circle" size={20} className="mt-0.5 shrink-0 text-green-600" />
                                    ) : (
                                        <Icon name="x-circle" size={20} className="mt-0.5 shrink-0 text-red-600" />
                                    )}
                                    <div>
                                        <p className="font-medium">{question?.text ?? `Question ${i + 1}`}</p>
                                        {!item.correct && (
                                            <p className="mt-1 text-sm">
                                                Correct: <strong>{formatCorrectAnswer(item.correct_answer)}</strong>
                                            </p>
                                        )}
                                        {item.explanation && (
                                            <p className="mt-1 text-sm text-muted-foreground">{asText(item.explanation)}</p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>

                <div className="flex justify-center gap-3">
                    <Button variant="outline" asChild>
                        <Link href="/results/attempts">All Attempts</Link>
                    </Button>
                    <Button asChild>
                        <Link href="/practice">Practice More</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
