import { Head, Link, router } from '@inertiajs/react';
function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}
import { useEffect, useState } from 'react';
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

const OXFORD = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';

export default function ExerciseResult({ attempt, nodeProgress }: Props) {
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);

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

    const stagger = (i: number) => ({
        opacity: mounted ? 1 : 0,
        transform: mounted ? 'translateY(0)' : 'translateY(16px)',
        transition: `all 0.5s cubic-bezier(0.22, 1, 0.36, 1) ${i * 100}ms`,
    });

    return (
        <>
            <Head title="Résultats" />
            <div className="mx-auto max-w-2xl space-y-6 px-4 py-8">
                {/* Header */}
                <div className="text-center" style={stagger(0)}>
                    <h1 className="text-3xl font-black tracking-tight" style={{ color: OXFORD }}>
                        Exercice terminé !
                    </h1>
                    <p className="mt-2 text-sm font-bold text-muted-foreground">
                        {exercise?.exercise_type?.name} · {exercise?.exam?.name}
                    </p>
                </div>

                {/* Node progress banner */}
                {nodeProgress && (
                    <div
                        className="duo-card overflow-hidden p-0"
                        style={{
                            borderColor: nodeCompleted ? GOLD : SKY,
                            ...stagger(1),
                        }}
                    >
                        {nodeCompleted ? (
                            <div
                                className="flex flex-col items-center gap-2 p-5"
                                style={{ background: 'rgba(245,166,35,0.06)' }}
                            >
                                <Icon name="award" size={40} style={{ color: GOLD }} />
                                <p className="text-lg font-black" style={{ color: OXFORD }}>Étape complétée !</p>
                                <p className="text-sm font-bold text-muted-foreground">La prochaine étape est débloquée 🎉</p>
                            </div>
                        ) : (
                            <div className="flex flex-col items-center gap-2 p-5">
                                <div className="flex gap-1.5">
                                    {Array.from({ length: exercisesRequired }).map((_, i) => (
                                        <Icon
                                            key={i}
                                            name="star"
                                            size={28}
                                            style={{
                                                opacity: i < exercisesDone ? 1 : 0.2,
                                            }}
                                        />
                                    ))}
                                </div>
                                <p className="text-sm font-black" style={{ color: OXFORD }}>
                                    {exercisesDone}/{exercisesRequired} exercices complétés
                                </p>
                                {/* Mini progress */}
                                <div className="duo-progress mt-1 w-48" style={{ height: '0.5rem' }}>
                                    <div
                                        className="h-full rounded-full"
                                        style={{
                                            width: `${(exercisesDone / exercisesRequired) * 100}%`,
                                            background: `linear-gradient(180deg, #FFC24A 0%, ${GOLD} 40%, #e08c10 100%)`,
                                            boxShadow: 'inset 0 -2px 0 rgba(0,0,0,0.15), inset 0 1px 0 rgba(255,255,255,0.3)',
                                        }}
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Score cards */}
                <div className="grid gap-3 grid-cols-3" style={stagger(2)}>
                    <div className="duo-card flex flex-col items-center p-5">
                        <div className="text-3xl font-black" style={{ color: SKY }}>
                            {Number(attempt.accuracy_percent ?? 0).toFixed(0)}%
                        </div>
                        <p className="text-[10px] font-black uppercase tracking-wider text-muted-foreground">Précision</p>
                    </div>
                    <div className="duo-card flex flex-col items-center p-5">
                        <div className="flex items-center gap-1 text-3xl font-black" style={{ color: GOLD }}>
                            <Icon name="zap" size={24} />
                            {attempt.xp_earned}
                        </div>
                        <p className="text-[10px] font-black uppercase tracking-wider text-muted-foreground">XP gagnés</p>
                    </div>
                    <div className="duo-card flex flex-col items-center p-5">
                        <div className="flex items-center gap-1 text-3xl font-black" style={{ color: OXFORD }}>
                            <Icon name="clock" size={20} />
                            {mins}:{String(secs).padStart(2, '0')}
                        </div>
                        <p className="text-[10px] font-black uppercase tracking-wider text-muted-foreground">Temps</p>
                    </div>
                </div>

                {/* Question review */}
                <div className="space-y-3" style={stagger(3)}>
                    <h2 className="text-lg font-black" style={{ color: OXFORD }}>Révision</h2>
                    {feedback.map((item, i) => {
                        const question = questions.find((q) => q.id === item.question_id) ?? questions[i];
                        return (
                            <div
                                key={item.question_id}
                                className="duo-card overflow-hidden p-0"
                                style={{
                                    borderColor: item.correct ? '#48b77b' : '#ef4444',
                                    boxShadow: item.correct ? '0 4px 0 0 #2d7d52' : '0 4px 0 0 #dc2626',
                                }}
                            >
                                <div
                                    className="p-4"
                                    style={{
                                        background: item.correct ? 'rgba(72,183,123,0.06)' : 'rgba(239,68,68,0.06)',
                                    }}
                                >
                                    <div className="flex items-start gap-3">
                                        {item.correct ? (
                                            <Icon name="check-circle" size={20} className="mt-0.5 shrink-0 text-green-600" />
                                        ) : (
                                            <Icon name="x-circle" size={20} className="mt-0.5 shrink-0 text-red-500" />
                                        )}
                                        <div>
                                            <p className="text-sm font-bold" style={{ color: OXFORD }}>
                                                {question?.text ?? `Question ${i + 1}`}
                                            </p>
                                            {!item.correct && item.correct_answer && (
                                                <p className="mt-1 text-sm">
                                                    Bonne réponse : <strong>{Array.isArray(item.correct_answer) ? item.correct_answer.join(', ') : item.correct_answer}</strong>
                                                </p>
                                            )}
                                            {item.explanation && (
                                                <p className="mt-1 text-xs text-muted-foreground">{item.explanation}</p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* CTA buttons */}
                <div className="flex justify-center gap-3 pt-2" style={stagger(4)}>
                    {nodeProgress && !nodeCompleted ? (
                        <>
                            <button
                                onClick={() => router.visit('/dashboard')}
                                className="duo-btn-secondary"
                            >
                                Retour au parcours
                            </button>
                            <button
                                onClick={() => router.visit(`/node/${nodeProgress.node_id}/start`)}
                                className="duo-btn-primary"
                            >
                                Continuer ({exercisesDone}/{exercisesRequired})
                            </button>
                        </>
                    ) : nodeCompleted ? (
                        <button
                            onClick={() => router.visit('/dashboard')}
                            className="duo-btn-primary"
                            style={{
                                background: GOLD,
                                boxShadow: `0 5px 0 0 #c07a0e`,
                            }}
                        >
                            <Icon name="award" size={16} />
                            Voir le parcours
                        </button>
                    ) : (
                        <>
                            <button
                                onClick={() => router.visit('/dashboard')}
                                className="duo-btn-secondary"
                            >
                                Retour au parcours
                            </button>
                            <button
                                onClick={() => router.visit('/practice')}
                                className="duo-btn-primary"
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
