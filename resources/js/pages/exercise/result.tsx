import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { CheckCircle, XCircle, Clock, Zap, Trophy, Star, Award } from 'lucide-react';
import type { ExerciseAttempt } from '@/types';

interface NodeProgress {
    node_id: number;
    exercises_done: number;
    exercises_required: number;
    completed: boolean;
}

interface Props {
    attempt: ExerciseAttempt;
    nodeProgress?: NodeProgress | null;
}

export default function ExerciseResult({ attempt, nodeProgress }: Props) {
    const feedback = (attempt.feedback ?? []) as Array<{
        question_id: string;
        correct: boolean;
        correct_answer: string | string[];
        explanation?: string;
    }>;
    const exercise = attempt.exercise;
    const questions = exercise?.questions ?? [];

    const mins = attempt.time_spent ? Math.floor(attempt.time_spent / 60) : 0;
    const secs = attempt.time_spent ? attempt.time_spent % 60 : 0;

    const nodeCompleted = nodeProgress?.completed ?? false;
    const exercisesDone = nodeProgress?.exercises_done ?? 0;
    const exercisesRequired = nodeProgress?.exercises_required ?? 3;

    return (
        <>
            <Head title="Résultats" />
            <div className="mx-auto max-w-3xl space-y-6 px-4 py-8">
                <div className="text-center">
                    <h1 className="text-3xl font-bold">Exercice terminé !</h1>
                    <p className="mt-2 text-muted-foreground">
                        {exercise?.exercise_type?.name} · {exercise?.exam?.name}
                    </p>
                </div>

                {/* Node progress banner */}
                {nodeProgress && (
                    <div
                        className="rounded-xl border-2 p-4 text-center"
                        style={{
                            borderColor: nodeCompleted ? '#F5A623' : 'rgba(74,144,226,0.3)',
                            background: nodeCompleted ? 'rgba(245,166,35,0.06)' : 'rgba(74,144,226,0.05)',
                        }}
                    >
                        {nodeCompleted ? (
                            <div className="flex flex-col items-center gap-2">
                                <Trophy className="h-8 w-8" style={{ color: '#F5A623' }} />
                                <p className="font-bold text-lg" style={{ color: '#1A2B48' }}>Étape complétée !</p>
                                <p className="text-sm text-muted-foreground">La prochaine étape est débloquée 🎉</p>
                            </div>
                        ) : (
                            <div className="flex flex-col items-center gap-2">
                                <div className="flex gap-1">
                                    {Array.from({ length: exercisesRequired }).map((_, i) => (
                                        <Star
                                            key={i}
                                            className="h-6 w-6"
                                            style={{ color: i < exercisesDone ? '#F5A623' : undefined, fill: i < exercisesDone ? '#F5A623' : 'none' }}
                                        />
                                    ))}
                                </div>
                                <p className="text-sm font-medium" style={{ color: '#1A2B48' }}>
                                    {exercisesDone}/{exercisesRequired} exercices complétés
                                </p>
                            </div>
                        )}
                    </div>
                )}

                {/* Score cards */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="flex flex-col items-center py-6">
                            <div className="text-4xl font-bold" style={{ color: '#4A90E2' }}>
                                {Number(attempt.accuracy_percent ?? 0).toFixed(0)}%
                            </div>
                            <p className="text-sm text-muted-foreground">Précision</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex flex-col items-center py-6">
                            <div className="flex items-center gap-1 text-4xl font-bold">
                                <Zap className="h-6 w-6" style={{ color: '#F5A623' }} />
                                <span style={{ color: '#F5A623' }}>{attempt.xp_earned}</span>
                            </div>
                            <p className="text-sm text-muted-foreground">XP gagnés</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex flex-col items-center py-6">
                            <div className="flex items-center gap-1 text-4xl font-bold">
                                <Clock className="h-6 w-6" style={{ color: '#1A2B48' }} />
                                {mins}:{String(secs).padStart(2, '0')}
                            </div>
                            <p className="text-sm text-muted-foreground">Temps</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Question review */}
                <div className="space-y-3">
                    <h2 className="text-xl font-semibold">Révision</h2>
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
                                        {!item.correct && item.correct_answer && (
                                            <p className="mt-1 text-sm">
                                                Bonne réponse : <strong>{Array.isArray(item.correct_answer) ? item.correct_answer.join(', ') : item.correct_answer}</strong>
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

                {/* CTA buttons */}
                <div className="flex justify-center gap-3">
                    {nodeProgress && !nodeCompleted ? (
                        <>
                            <Button variant="outline" asChild style={{ borderColor: 'rgba(26,43,72,0.3)', color: '#1A2B48' }}>
                                <Link href="/dashboard">Retour au parcours</Link>
                            </Button>
                            <button
                                onClick={() => router.visit(`/node/${nodeProgress.node_id}/start`)}
                                className="inline-flex items-center gap-2 rounded-lg px-6 py-2.5 text-sm font-semibold text-white transition-all hover:-translate-y-0.5"
                                style={{ background: 'linear-gradient(135deg, #4A90E2 0%, #3478c8 100%)', boxShadow: '0 4px 14px rgba(74,144,226,0.35)' }}
                            >
                                Continuer ({exercisesDone}/{exercisesRequired})
                            </button>
                        </>
                    ) : nodeCompleted ? (
                        <button
                            onClick={() => router.visit('/dashboard')}
                            className="inline-flex items-center gap-2 rounded-lg px-8 py-3 text-sm font-semibold text-white transition-all hover:-translate-y-0.5"
                            style={{ background: 'linear-gradient(135deg, #F5A623 0%, #e08c10 100%)', boxShadow: '0 4px 14px rgba(245,166,35,0.4)' }}
                        >
                            <Trophy className="h-4 w-4" />
                            Voir le parcours
                        </button>
                    ) : (
                        <>
                            <Button variant="outline" asChild style={{ borderColor: 'rgba(26,43,72,0.3)', color: '#1A2B48' }}>
                                <Link href="/dashboard">Retour au parcours</Link>
                            </Button>
                            <button
                                onClick={() => router.visit('/practice')}
                                className="inline-flex items-center gap-2 rounded-lg px-6 py-2.5 text-sm font-semibold text-white transition-all hover:-translate-y-0.5"
                                style={{ background: 'linear-gradient(135deg, #4A90E2 0%, #3478c8 100%)' }}
                            >
                                Pratiquer
                            </button>
                        </>
                    )}
                </div>
            </div>
        </>
    );
}
