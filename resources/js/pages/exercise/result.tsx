import { ArtIcon } from '@/components/art-icon';
import { normalizeOptions } from '@/components/exercises/normalize-options';
import { normalizeTtsLang } from '@/lib/tts-cache';
import type { ExerciseAttempt } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

// The AI sometimes returns `explanation` as an object ({concept,evidence,hint})
// instead of a string. Rendering/`.replace()` on it crashed the page
// ("explanation.replace is not a function"). Coerce anything to a safe string.
function asText(v: unknown): string {
    if (v == null) return '';
    if (typeof v === 'string') return v;
    if (typeof v === 'number' || typeof v === 'boolean') return String(v);
    if (Array.isArray(v)) return v.map(asText).filter(Boolean).join(' ');
    if (typeof v === 'object') {
        const value = v as Record<string, unknown>;
        return asText(value.concept ?? value.text ?? value.message ?? value.hint ?? value.value ?? Object.values(value)[0]);
    }
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

function readableAnswer(value: unknown, options: unknown): string {
    const answer = formatCorrectAnswer(value);
    const normalized = normalizeOptions(options);
    if (/^[A-Z]$/.test(answer)) {
        const label = normalized[answer.charCodeAt(0) - 65];
        if (label) return `${answer} · ${label}`;
    }
    return answer;
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

const SKY = '#4A90E2';
const GOLD = '#F5A623';

export default function ExerciseResult({ attempt, nodeProgress }: Props) {
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);

    const [playingTts, setPlayingTts] = useState<string | null>(null);
    const [ttsError, setTtsError] = useState<string | null>(null);
    const ttsBusy = useRef(false);
    const audioRef = useRef<HTMLAudioElement | null>(null);
    const exercise = attempt.exercise;
    const nodeCode = normalizeTtsLang(exercise?.exam?.language?.slug);
    const isStarter = exercise?.content?.source === 'starter-library';
    const exerciseTitle = typeof exercise?.content?.title === 'string' ? exercise.content.title : exercise?.exercise_type?.name;

    useEffect(() => () => {
        audioRef.current?.pause();
    }, []);

    const playTts = async (text: string, id: string, language = nodeCode) => {
        if (ttsBusy.current || !text.trim()) return;
        ttsBusy.current = true;
        setTtsError(null);
        setPlayingTts(id);
        const finish = () => {
            ttsBusy.current = false;
            setPlayingTts(null);
        };
        try {
            const response = await fetch(route('tts.speak'), {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''
                },
                body: JSON.stringify({ text, lang: language })
            });
            if (!response.ok) throw new Error('Audio unavailable');
            const data = await response.json();
            if (data.audio_url) {
                const audio = new Audio(data.audio_url);
                audioRef.current = audio;
                audio.onended = finish;
                audio.onerror = () => {
                    setTtsError('La lecture audio est indisponible pour le moment. Tu peux continuer à lire les corrections.');
                    finish();
                };
                await audio.play();
            } else {
                throw new Error('Audio unavailable');
            }
        } catch {
            setTtsError('La lecture audio est indisponible pour le moment. Tu peux continuer à lire les corrections.');
            finish();
        }
    };

    const feedback = (attempt.feedback ?? []) as Array<{
        question_id: string;
        correct: boolean;
        correct_answer: unknown;
        explanation?: string;
    }>;
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
            <div className="mx-auto max-w-2xl space-y-6 px-4 py-8 text-foreground">
                {/* Header */}
                <div className="rounded-3xl border border-border bg-card px-5 py-7 text-center" style={stagger(0)}>
                    <div className="mb-4 flex justify-center">
                        <ArtIcon name={isSuccess ? 'trophy' : 'review'} size={88} tone={isSuccess ? 'amber' : 'blue'} />
                    </div>
                    <h1 className="text-3xl font-black tracking-tight text-foreground">
                        {isSuccess ? 'Excellent !' : 'Exercice terminé !'}
                    </h1>
                    <p className="mt-2 text-sm font-bold text-foreground">{exerciseTitle}</p>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {isStarter ? `${exercise?.exam?.language?.name ?? 'Langue'} · Niveau ${exercise?.difficulty}` : exercise?.exam?.name}
                    </p>
                    {isStarter && (
                        <p className="mt-4 inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">
                            Bibliothèque PrepLa · entraînement général
                        </p>
                    )}
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
                                <ArtIcon name="award" size={48} tone="amber" />
                                <p className="text-lg font-black text-foreground">Étape complétée !</p>
                                <p className="text-sm font-bold text-muted-foreground">La prochaine étape est débloquée</p>
                            </div>
                        ) : (
                            <div className="flex flex-col items-center gap-2 p-5">
                                <div className="flex gap-1.5">
                                    {Array.from({ length: exercisesRequired }).map((_, i) => (
                                        <span key={i} className={i < exercisesDone ? '' : 'opacity-30'}>
                                            <ArtIcon name="star" size={32} tone="amber" />
                                        </span>
                                    ))}
                                </div>
                                <p className="text-sm font-black text-foreground">
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
                    <div className="duo-card flex flex-col items-center justify-center gap-1 p-3 sm:p-5">
                        <div className="text-2xl font-black text-sky-700 dark:text-sky-300 sm:text-3xl">
                            {Number(attempt.accuracy_percent ?? 0).toFixed(0)}%
                        </div>
                        <p className="text-[10px] font-black uppercase tracking-wider text-muted-foreground">Précision</p>
                    </div>
                    <div className="duo-card flex flex-col items-center justify-center gap-1 p-3 sm:p-5">
                        <div className="flex items-center gap-1 text-2xl font-black text-amber-700 dark:text-amber-300 sm:text-3xl">
                            <ArtIcon name="zap" size={28} tone="amber" className="hidden sm:inline-flex" />
                            {attempt.xp_earned}
                        </div>
                        <p className="text-[10px] font-black uppercase tracking-wider text-muted-foreground">XP gagnés</p>
                    </div>
                    <div className="duo-card flex flex-col items-center justify-center gap-1 p-3 sm:p-5">
                        <div className="flex items-center gap-1 text-2xl font-black text-foreground sm:text-3xl">
                            <ArtIcon name="clock" size={28} className="hidden sm:inline-flex" />
                            {mins}:{String(secs).padStart(2, '0')}
                        </div>
                        <p className="text-[10px] font-black uppercase tracking-wider text-muted-foreground">Temps</p>
                    </div>
                </div>

                {/* Question review */}
                <div className="space-y-3" style={stagger(3)}>
                    <h2 className="text-lg font-black text-foreground">Comprendre tes réponses</h2>
                    {ttsError && <p role="status" className="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">{ttsError}</p>}
                    {feedback.map((item, i) => {
                        const question = questions.find((q) => q.id === item.question_id) ?? questions[i];
                        const expectedAnswer = readableAnswer(item.correct_answer, question?.options);
                        const givenAnswer = readableAnswer(attempt.answers?.[item.question_id], question?.options);
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
                                            <ArtIcon name="check-circle" size={32} tone="mint" className="shrink-0" />
                                        ) : (
                                            <ArtIcon name="x-circle" size={32} tone="rose" className="shrink-0" />
                                        )}
                                        <div className="min-w-0 flex-1 space-y-1">
                                            <p className={`text-xs font-bold ${item.correct ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'}`}>
                                                Question {i + 1} · {item.correct ? 'Bonne réponse' : 'À revoir'}
                                            </p>
                                            <p className="break-words text-sm font-bold text-foreground">
                                                {question?.text ?? question?.prompt ?? `Question ${i + 1}`}
                                            </p>
                                            <p className="break-words text-xs text-muted-foreground">
                                                Ta réponse : {givenAnswer || 'Aucune réponse'}
                                            </p>
                                            {!item.correct && expectedAnswer.length > 0 && (
                                                <div className="mt-2 flex items-center justify-between gap-2 rounded-lg border border-border bg-muted/50 p-2">
                                                    <p className="min-w-0 break-words text-[13px] text-foreground">
                                                        Bonne réponse : <strong className="text-emerald-700 dark:text-emerald-300">{expectedAnswer}</strong>
                                                    </p>
                                                    <button
                                                        type="button"
                                                        onClick={() => playTts(expectedAnswer, `correct-${i}`)}
                                                        disabled={playingTts !== null}
                                                        aria-label={`Écouter la bonne réponse à la question ${i + 1}`}
                                                        aria-busy={playingTts === `correct-${i}`}
                                                        className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50"
                                                    >
                                                        <ArtIcon name={playingTts === `correct-${i}` ? 'volume-2' : 'volume-1'} size={30} />
                                                    </button>
                                                </div>
                                            )}
                                            {item.explanation && (
                                                <div className="mt-3 rounded-xl border border-dashed border-border bg-card/80 p-3 sm:p-4">
                                                    <div className="flex items-center justify-between mb-2">
                                                        <div className="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-indigo-700 dark:text-indigo-300">
                                                            <ArtIcon name="sparkles" size={26} />
                                                            Explication pédagogique
                                                        </div>
                                                        <button
                                                            type="button"
                                                            onClick={() => playTts(asText(item.explanation), `expl-${i}`, isStarter ? 'fr' : nodeCode)}
                                                            disabled={playingTts !== null}
                                                            aria-label={`Écouter l’explication de la question ${i + 1}`}
                                                            aria-busy={playingTts === `expl-${i}`}
                                                            className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50"
                                                        >
                                                            <ArtIcon name={playingTts === `expl-${i}` ? 'volume-2' : 'volume-1'} size={30} />
                                                        </button>
                                                    </div>
                                                    <p className="break-words text-[13px] leading-relaxed text-muted-foreground">
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
                <div className="flex flex-col justify-center gap-3 pt-2 sm:flex-row sm:flex-wrap" style={stagger(4)}>
                    {nodeProgress && !nodeCompleted ? (
                        <>
                            <button
                                type="button"
                                onClick={() => router.visit('/dashboard')}
                                className="duo-btn-secondary"
                            >
                                Retour au parcours
                            </button>
                            <button
                                type="button"
                                onClick={() => router.visit(`/node/${nodeProgress.node_id}/start`)}
                                className="duo-btn-primary"
                            >
                                Continuer ({exercisesDone}/{exercisesRequired})
                            </button>
                        </>
                    ) : nodeCompleted ? (
                        <button
                            type="button"
                            onClick={() => router.visit('/dashboard')}
                            className="duo-btn-primary"
                            style={{
                                background: GOLD,
                                boxShadow: `0 5px 0 0 #c07a0e`,
                            }}
                        >
                            <ArtIcon name="award" size={28} tone="amber" />
                            Voir le parcours
                        </button>
                    ) : isStarter && exercise ? (
                        <>
                            <Link href={`/practice/${exercise.exam_id}`} className="duo-btn-secondary text-center">
                                Choisir un autre format
                            </Link>
                            <Link href={route('exercise.show', exercise.id)} className="duo-btn-primary text-center">
                                Revoir cet exercice
                            </Link>
                            <p className="w-full text-center text-xs leading-relaxed text-muted-foreground">
                                La bibliothèque propose une série par format et niveau. Rejouer cette série permet de consolider tes acquis.
                            </p>
                        </>
                    ) : (
                        <>
                            <button
                                type="button"
                                onClick={() => router.visit('/practice')}
                                className="duo-btn-secondary"
                            >
                                Pratiquer
                            </button>
                            {/* "Autre exercice" du même type (biblio d'abord, sinon généré) */}
                            {exercise?.exam_id && exercise?.exercise_type_id ? (
                                <button
                                    type="button"
                                    onClick={() => router.visit(route('practice.drill.type', [exercise.exam_id, exercise.exercise_type_id]))}
                                    className="duo-btn-primary"
                                >
                                    <ArtIcon name="sparkles" size={28} />
                                    Autre exercice
                                </button>
                            ) : (
                                <button
                                    type="button"
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
