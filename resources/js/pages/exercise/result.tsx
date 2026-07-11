import { Head, Link, router } from '@inertiajs/react';
function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}
import { useEffect, useState } from 'react';
import type { ExerciseAttempt } from '@/types';

// The AI sometimes returns `explanation` as an object ({concept,evidence,hint})
// instead of a string. Rendering/`.replace()` on it crashed the page
// ("explanation.replace is not a function"). Coerce anything to a safe string.
function asText(v: any): string {
    if (v == null) return '';
    if (typeof v === 'string') return v;
    if (typeof v === 'number' || typeof v === 'boolean') return String(v);
    if (Array.isArray(v)) return v.map(asText).filter(Boolean).join(' ');
    if (typeof v === 'object') return asText(v.concept ?? v.text ?? v.message ?? v.hint ?? v.value ?? Object.values(v)[0]);
    return '';
}

// correct_answer can also be a plain object map (multi-field/matching types,
// e.g. {s1:"B",s2:"A",...}) — JSON.stringify only kicked in for arrays, so an
// object fell through and got rendered raw as a React child (error #31).
function formatCorrectAnswer(v: unknown): string {
    if (v == null) return '';
    if (Array.isArray(v)) return v.map(String).join(', ');
    if (typeof v === 'object') {
        return Object.entries(v as Record<string, unknown>).map(([k, val]) => `${k}: ${val}`).join(', ');
    }
    return String(v);
}

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

const nodeCode = 'de'; // Or fetch from context if available

export default function ExerciseResult({ attempt, nodeProgress }: Props) {
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);

    const [playingTts, setPlayingTts] = useState<string | null>(null);

    const playTts = async (text: string, id: string) => {
        if (playingTts === id) return;
        setPlayingTts(id);
        try {
            const response = await fetch(route('tts.speak'), {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content 
                },
                body: JSON.stringify({ text, lang: nodeCode })
            });
            const data = await response.json();
            if (data.audio_url) {
                const audio = new Audio(data.audio_url);
                audio.onended = () => setPlayingTts(null);
                audio.play();
            } else {
                setPlayingTts(null);
            }
        } catch (error) {
            console.error('TTS error:', error);
            setPlayingTts(null);
        }
    };

    const feedback = (attempt.feedback ?? []) as Array<{
        question_id: string;
        correct: boolean;
        correct_answer: any;
        explanation?: string;
    }>;
    const exercise = attempt.exercise;
    const questions = exercise?.questions ?? [];

    const mins = attempt.time_spent ? Math.floor(attempt.time_spent / 60) : 0;
    const secs = attempt.time_spent ? attempt.time_spent % 60 : 0;

    const nodeCompleted = nodeProgress?.completed ?? false;
    const exercisesDone = nodeProgress?.exercises_done ?? 0;
    const exercisesRequired = nodeProgress?.exercises_required ?? 3;
    const accuracy = Number(attempt.accuracy_percent ?? 0);
    const isSuccess = accuracy >= 60;

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
                    {isSuccess && (
                        <div className="flex justify-center mb-2">
                            <img
                                src="/animation/Trophy.gif"
                                alt="Trophée"
                                className="w-24 h-24 object-contain"
                            />
                        </div>
                    )}
                    <h1 className="text-3xl font-black tracking-tight" style={{ color: OXFORD }}>
                        {isSuccess ? 'Excellent !' : 'Exercice terminé !'}
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
                                <p className="text-sm font-bold text-muted-foreground">La prochaine étape est débloquée</p>
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
                                                {question?.text ?? question?.prompt ?? `Question ${i + 1}`}
                                            </p>
                                            {!item.correct && item.correct_answer && (
                                                <div className="mt-2 p-2 bg-slate-50 rounded-lg border border-slate-100 flex items-center justify-between">
                                                    <p className="text-[13px]">
                                                        Bonne réponse : <strong className="text-green-700">{formatCorrectAnswer(item.correct_answer)}</strong>
                                                    </p>
                                                    <button
                                                        onClick={() => playTts(formatCorrectAnswer(item.correct_answer), `correct-${i}`)}
                                                        className="p-1 hover:bg-white rounded transition-all text-slate-400 hover:text-indigo-600"
                                                    >
                                                        <Icon name={playingTts === `correct-${i}` ? 'volume-2' : 'volume-1'} size={14} />
                                                    </button>
                                                </div>
                                            )}
                                            {item.explanation && (
                                                <div className="mt-3 p-4 bg-white/80 rounded-xl border border-dashed border-indigo-200/50">
                                                    <div className="flex items-center justify-between mb-2">
                                                        <div className="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-indigo-500">
                                                            <Icon name="sparkles" size={12} />
                                                            Explication pédagogique
                                                        </div>
                                                        <button
                                                            onClick={() => playTts(asText(item.explanation), `expl-${i}`)}
                                                            className={`p-1 rounded transition-all ${playingTts === `expl-${i}` ? 'text-indigo-600' : 'text-slate-400 hover:text-indigo-600'}`}
                                                        >
                                                            <Icon name={playingTts === `expl-${i}` ? 'volume-2' : 'volume-1'} size={14} />
                                                        </button>
                                                    </div>
                                                    <p className="text-[13px] leading-relaxed text-slate-600">
                                                        {asText(item.explanation).replace(/<\/?evidence>/g, '')}
                                                    </p>
                                                </div>
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
                                onClick={() => router.visit('/practice')}
                                className="duo-btn-secondary"
                            >
                                Pratiquer
                            </button>
                            {/* "Autre exercice" du même type (biblio d'abord, sinon généré) */}
                            {exercise?.exam_id && exercise?.exercise_type_id ? (
                                <button
                                    onClick={() => router.visit(route('practice.drill.type', [exercise.exam_id, exercise.exercise_type_id]))}
                                    className="duo-btn-primary"
                                >
                                    <Icon name="sparkles" size={16} style={{ filter: 'brightness(0) invert(1)' }} />
                                    Autre exercice
                                </button>
                            ) : (
                                <button
                                    onClick={() => router.visit('/dashboard')}
                                    className="duo-btn-primary"
                                >
                                    Retour au parcours
                                </button>
                            )}
                        </>
                    )}
                </div>
            </div>
        </>
    );
}
