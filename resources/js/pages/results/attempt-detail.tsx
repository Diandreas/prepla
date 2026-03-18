import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { CheckCircle, Clock, XCircle, Zap } from 'lucide-react';
import type { ExerciseAttempt } from '@/types';

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
                                {attempt.accuracy_percent?.toFixed(0) ?? 0}%
                            </div>
                            <p className="text-sm text-muted-foreground">Accuracy</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex flex-col items-center py-6">
                            <div className="flex items-center gap-1 text-4xl font-bold">
                                <Zap className="h-6 w-6 text-yellow-500" />
                                {attempt.xp_earned}
                            </div>
                            <p className="text-sm text-muted-foreground">XP Earned</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex flex-col items-center py-6">
                            <div className="flex items-center gap-1 text-4xl font-bold">
                                <Clock className="h-6 w-6 text-blue-500" />
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
                                        <CheckCircle className="mt-0.5 h-5 w-5 shrink-0 text-green-600" />
                                    ) : (
                                        <XCircle className="mt-0.5 h-5 w-5 shrink-0 text-red-600" />
                                    )}
                                    <div>
                                        <p className="font-medium">{question?.text ?? `Question ${i + 1}`}</p>
                                        {!item.correct && (
                                            <p className="mt-1 text-sm">
                                                Correct: <strong>{Array.isArray(item.correct_answer) ? item.correct_answer.join(', ') : item.correct_answer}</strong>
                                            </p>
                                        )}
                                        {item.explanation && (
                                            <p className="mt-1 text-sm text-muted-foreground">{item.explanation}</p>
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
