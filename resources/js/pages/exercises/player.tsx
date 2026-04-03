import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useState, useCallback, useMemo, useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';

function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}

import { Mcq } from '@/components/exercises/mcq';
import { TrueFalseNg } from '@/components/exercises/true-false-ng';
import { GapFill } from '@/components/exercises/gap-fill';
import { Matching } from '@/components/exercises/matching';
import { EssayEditor } from '@/components/exercises/essay-editor';
import { SentenceCompletion } from '@/components/exercises/sentence-completion';
import { ShortAnswer } from '@/components/exercises/short-answer';
import { NoteCompletion } from '@/components/exercises/note-completion';
import { Ordering } from '@/components/exercises/ordering';
import { Dictation } from '@/components/exercises/dictation';
import { OpenCloze } from '@/components/exercises/open-cloze';
import { WordFormation } from '@/components/exercises/word-formation';
import { KeyWordTransformation } from '@/components/exercises/key-word-transformation';
import { ShortWriting } from '@/components/exercises/short-writing';
import { FormCompletion } from '@/components/exercises/form-completion';
import { SummaryCompletion } from '@/components/exercises/summary-completion';
import { TableCompletion } from '@/components/exercises/table-completion';
import { FlowChartCompletion } from '@/components/exercises/flow-chart-completion';
import { MultipleMatching } from '@/components/exercises/multiple-matching';
import { InsertText } from '@/components/exercises/insert-text';
import { GappedText } from '@/components/exercises/gapped-text';
import { GraphDescription } from '@/components/exercises/graph-description';
import { AcademicDiscussion } from '@/components/exercises/academic-discussion';
import { SpeakingRecorder } from '@/components/exercises/speaking-recorder';
import { RolePlay } from '@/components/exercises/role-play';
import { DiagramLabeling } from '@/components/exercises/diagram-labeling';
import { Synthesis } from '@/components/exercises/synthesis';
import { IntegratedTask } from '@/components/exercises/integrated-task';
import { VocabularyCard } from '@/components/exercises/vocabulary-card';
import { ExerciseTimer } from '@/components/exercises/exercise-timer';

interface Exercise {
    id: number;
    exercise_type: {
        component_key: string;
        name: string;
    };
    questions: any[];
    content: any;
    difficulty: string;
}

interface Props {
    node: {
        id: number;
        title: string;
        level: string;
        exam: {
            name: string;
            language: {
                name: string;
            }
        }
    };
    exercises: Exercise[];
    progress: {
        id: number;
        status: string;
    };
}

const componentMap: Record<string, React.ComponentType<any>> = {
    'mcq': Mcq,
    'true-false-ng': TrueFalseNg,
    'gap-fill': GapFill,
    'matching': Matching,
    'essay-editor': EssayEditor,
    'sentence-completion': SentenceCompletion,
    'short-answer': ShortAnswer,
    'note-completion': NoteCompletion,
    'ordering': Ordering,
    'dictation': Dictation,
    'open-cloze': OpenCloze,
    'word-formation': WordFormation,
    'key-word-transformation': KeyWordTransformation,
    'short-writing': ShortWriting,
    'form-completion': FormCompletion,
    'summary-completion': SummaryCompletion,
    'table-completion': TableCompletion,
    'flow-chart-completion': FlowChartCompletion,
    'multiple-matching': MultipleMatching,
    'insert-text': InsertText,
    'gapped-text': GappedText,
    'graph-description': GraphDescription,
    'academic-discussion': AcademicDiscussion,
    'speaking-recorder': SpeakingRecorder,
    'role-play': RolePlay,
    'diagram-labeling': DiagramLabeling,
    'synthesis': Synthesis,
    'integrated-task': IntegratedTask,
    'vocabulary-card': VocabularyCard,
};

// Segmented progress dots
function ProgressDots({ total, current }: { total: number; current: number }) {
    // Cap at 40 dots for readability
    const MAX = 40;
    const capped = Math.min(total, MAX);
    const ratio = total > MAX ? current / total : current;
    const filledCount = total > MAX ? Math.round(ratio * capped) : current;

    return (
        <div className="flex items-center gap-[3px] flex-wrap">
            {Array.from({ length: capped }).map((_, i) => {
                const filled = i < filledCount;
                const active = i === filledCount;
                return (
                    <span
                        key={i}
                        style={{
                            width: capped > 20 ? 6 : 8,
                            height: capped > 20 ? 6 : 8,
                            borderRadius: '50%',
                            display: 'block',
                            transition: 'all 0.3s cubic-bezier(.4,0,.2,1)',
                            background: filled
                                ? 'var(--player-accent)'
                                : active
                                ? 'var(--player-accent-muted)'
                                : 'var(--player-dot-empty)',
                            transform: active ? 'scale(1.35)' : 'scale(1)',
                            boxShadow: active ? '0 0 0 2px var(--player-accent-muted)' : 'none',
                        }}
                    />
                );
            })}
        </div>
    );
}

// Circular SVG countdown arc
function ArcTimer({ seconds, limit, warning, critical, expired }: {
    seconds: number; limit: number;
    warning: boolean; critical: boolean; expired: boolean;
}) {
    const r = 18;
    const circ = 2 * Math.PI * r;
    const pct = limit > 0 ? seconds / limit : 1;
    const offset = circ * (1 - pct);
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;

    const trackColor = expired || critical ? '#ef4444' : warning ? '#f59e0b' : 'var(--player-accent)';
    const bgColor = expired || critical ? 'rgba(239,68,68,0.12)' : warning ? 'rgba(245,158,11,0.12)' : 'rgba(var(--player-accent-rgb),0.1)';

    return (
        <div
            style={{
                position: 'relative',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                width: 52,
                height: 52,
                animation: critical && !expired ? 'timerPulse 1s ease-in-out infinite' : 'none',
            }}
        >
            <svg width="52" height="52" style={{ position: 'absolute', top: 0, left: 0 }}>
                {/* Background track */}
                <circle cx="26" cy="26" r={r} fill="none" stroke={bgColor} strokeWidth="3" />
                {/* Progress arc */}
                <circle
                    cx="26" cy="26" r={r}
                    fill="none"
                    stroke={trackColor}
                    strokeWidth="3"
                    strokeLinecap="round"
                    strokeDasharray={circ}
                    strokeDashoffset={offset}
                    transform="rotate(-90 26 26)"
                    style={{ transition: 'stroke-dashoffset 0.9s linear, stroke 0.3s' }}
                />
            </svg>
            <span style={{
                fontSize: 11,
                fontWeight: 700,
                fontVariantNumeric: 'tabular-nums',
                letterSpacing: '-0.02em',
                color: expired || critical ? '#ef4444' : warning ? '#f59e0b' : 'var(--player-text-muted)',
                lineHeight: 1,
            }}>
                {String(mins).padStart(2, '0')}:{String(secs).padStart(2, '0')}
            </span>
        </div>
    );
}

export default function SessionPlayer({ node, exercises, progress }: Props) {
    const { t } = useTranslation();
    const [currentExerciseIndex, setCurrentExerciseIndex] = useState(0);
    const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
    const [answers, setAnswers] = useState<Record<string, any>>({});
    const [isChecked, setIsChecked] = useState(false);
    const [isCorrect, setIsCorrect] = useState<boolean | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [transitioning, setTransitioning] = useState(false);
    const [visible, setVisible] = useState(true);
    const [timeSpent, setTimeSpent] = useState(0);
    const [timerKey, setTimerKey] = useState(0);
    const [timerSeconds, setTimerSeconds] = useState(0);
    const contentRef = useRef<HTMLDivElement>(null);

    const exercise = exercises[currentExerciseIndex];
    const componentKey = exercise?.exercise_type?.component_key ?? 'mcq';

    const TIME_PER_QUESTION = useMemo(() => {
        const writingTypes = ['essay-editor', 'short-writing', 'graph-description', 'academic-discussion', 'synthesis', 'integrated-task'];
        const speakingTypes = ['speaking-recorder', 'role-play'];
        const longTypes = ['gapped-text', 'insert-text', 'table-completion', 'flow-chart-completion', 'summary-completion', 'form-completion', 'diagram-labeling', 'multiple-matching'];
        if (writingTypes.includes(componentKey)) return 600;
        if (speakingTypes.includes(componentKey)) return 300;
        if (longTypes.includes(componentKey)) return 120;
        return 45;
    }, [componentKey]);

    const questions = exercise?.questions ?? [];
    const question = questions[currentQuestionIndex];
    const Component = componentMap[exercise?.exercise_type?.component_key ?? 'mcq'] ?? Mcq;

    const totalQuestionsInSession = useMemo(() =>
        exercises.reduce((acc, ex) => acc + (ex.questions?.length ?? 0), 0)
    , [exercises]);

    const overallProgressCount = useMemo(() => {
        let count = 0;
        for (let i = 0; i < currentExerciseIndex; i++) {
            count += exercises[i].questions?.length ?? 0;
        }
        return count + currentQuestionIndex;
    }, [currentExerciseIndex, currentQuestionIndex, exercises]);

    const handleAnswer = useCallback((questionId: string, answer: any) => {
        if (isChecked) return;
        setAnswers(prev => ({ ...prev, [questionId]: answer }));
    }, [isChecked]);

    const handleRetry = useCallback(() => {
        setIsChecked(false);
        setIsCorrect(null);
        setAnswers(prev => {
            const next = { ...prev };
            if (question) delete next[question.id];
            return next;
        });
        setTimerSeconds(TIME_PER_QUESTION); // Reset timer 
    }, [question, TIME_PER_QUESTION]);

    const checkAnswer = useCallback(() => {
        if (!question || isChecked) return;
        const currentAnswer = answers[question.id];

        const noScoreTypes = ['essay-editor', 'speaking-recorder', 'role-play', 'short-writing', 'graph-description', 'academic-discussion', 'synthesis', 'integrated-task'];
        let isRight = false;

        if (noScoreTypes.includes(componentKey)) {
            isRight = currentAnswer !== undefined && String(currentAnswer).trim() !== '';
        } else {
            // Very naive check; works for single MCQ/TF, but backend scores partials.
            const correctAnswer = question.correct_answer ?? question.correct;
            // Also handle array of correct answers if it's form completion
            if (Array.isArray(correctAnswer) && Array.isArray(currentAnswer)) {
                isRight = JSON.stringify(correctAnswer) === JSON.stringify(currentAnswer);
            } else if (typeof correctAnswer === 'object' && typeof currentAnswer === 'object') {
                isRight = JSON.stringify(correctAnswer) === JSON.stringify(currentAnswer);
            } else {
                isRight = String(currentAnswer).trim().toUpperCase() === String(correctAnswer).trim().toUpperCase();
            }
        }

        setIsCorrect(isRight);
        setIsChecked(true);
    }, [question, isChecked, answers, componentKey]);

    const nextStep = useCallback(() => {
        setIsChecked(false);
        setIsCorrect(null);

        const isEndOfSession = currentExerciseIndex === exercises.length - 1 && currentQuestionIndex === questions.length - 1;

        if (isEndOfSession) {
            setSubmitting(true);
            router.post(route('exercise.submit_session', node.id), {
                answers,
                node_id: node.id,
                time_spent: timeSpent,
            }, {
                forceFormData: true,
            });
            return;
        }

        setVisible(false);
        setTransitioning(true);
        setTimeout(() => {
            if (currentQuestionIndex < questions.length - 1) {
                setCurrentQuestionIndex(prev => prev + 1);
            } else {
                setCurrentExerciseIndex(prev => prev + 1);
                setCurrentQuestionIndex(0);
            }
            setTransitioning(false);
            setVisible(true);
            setTimerKey(k => k + 1);
        }, 220);
    }, [currentExerciseIndex, currentQuestionIndex, exercises.length, questions.length, answers, node.id, timeSpent, isChecked]);

    const handleTimeExpire = useCallback(() => {
        if (!isChecked) {
            setIsChecked(true);
            setIsCorrect(false);
        }
        setTimeout(() => nextStep(), 1200);
    }, [isChecked, nextStep]);

    // Keyboard shortcut: Space / Enter
    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement) return;
            if (e.code === 'Space' || e.code === 'Enter') {
                e.preventDefault();
                if (isChecked) nextStep();
                else if (answers[question?.id] !== undefined) checkAnswer();
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [isChecked, nextStep, checkAnswer, answers, question]);

    const timerWarning = timerSeconds <= TIME_PER_QUESTION * 0.2 && timerSeconds > 0;
    const timerCritical = timerSeconds <= 10 && timerSeconds > 0;
    const timerExpired = timerSeconds === 0 && timerKey > 0;

    const isLastQuestion = currentExerciseIndex === exercises.length - 1 && currentQuestionIndex === questions.length - 1;
    const hasAnswer = answers[question?.id] !== undefined;

    if (!exercise || !question) {
        return (
            <AppLayout>
                <div className="flex flex-col items-center justify-center py-20">
                    <Icon name="alert-circle" size={48} />
                    <p className="mt-4 text-xl font-medium">Session non disponible</p>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title={`${node.title} - PrePla`} />
            <style>{`
                @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@400;500;600;700&display=swap');

                :root {
                    --player-accent: #6366f1;
                    --player-accent-rgb: 99,102,241;
                    --player-accent-muted: rgba(99,102,241,0.3);
                    --player-dot-empty: rgba(var(--player-accent-rgb),0.12);
                    --player-text-muted: var(--muted-foreground, #64748b);
                }

                @keyframes slideInUp {
                    from { opacity: 0; transform: translateY(20px) scale(0.99); }
                    to   { opacity: 1; transform: translateY(0) scale(1); }
                }
                @keyframes slideOutUp {
                    from { opacity: 1; transform: translateY(0) scale(1); }
                    to   { opacity: 0; transform: translateY(-14px) scale(0.99); }
                }
                @keyframes feedbackSlideIn {
                    from { opacity: 0; transform: translateY(6px); }
                    to   { opacity: 1; transform: translateY(0); }
                }
                @keyframes timerPulse {
                    0%, 100% { transform: scale(1); }
                    50%       { transform: scale(1.06); }
                }
                @keyframes checkPop {
                    0%   { transform: scale(0.6); opacity: 0; }
                    60%  { transform: scale(1.15); opacity: 1; }
                    100% { transform: scale(1); }
                }

                .player-font { font-family: 'DM Sans', system-ui, sans-serif; }
                .question-enter { animation: slideInUp 0.26s cubic-bezier(.22,1,.36,1) forwards; }
                .question-exit  { animation: slideOutUp 0.18s cubic-bezier(.4,0,.2,1) forwards; }
                .feedback-enter { animation: feedbackSlideIn 0.22s cubic-bezier(.22,1,.36,1) forwards; }
                .check-pop      { animation: checkPop 0.32s cubic-bezier(.22,1,.36,1) forwards; }

                .player-card {
                    border-radius: 16px;
                    border: 1px solid rgba(var(--player-accent-rgb), 0.12);
                    background: var(--card, #fff);
                    box-shadow: 0 2px 16px rgba(0,0,0,0.05), 0 0 0 1px rgba(var(--player-accent-rgb),0.04);
                }

                .action-bar-correct {
                    background: linear-gradient(to right, rgba(16,185,129,0.06), rgba(16,185,129,0.02));
                    border-top-color: rgba(16,185,129,0.25) !important;
                }
                .action-bar-incorrect {
                    background: linear-gradient(to right, rgba(239,68,68,0.06), rgba(239,68,68,0.02));
                    border-top-color: rgba(239,68,68,0.22) !important;
                }

                .btn-check {
                    font-family: 'DM Sans', system-ui, sans-serif;
                    font-weight: 700;
                    font-size: 0.9375rem;
                    letter-spacing: 0.01em;
                    height: 48px;
                    min-width: 148px;
                    border-radius: 12px;
                    border: none;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 7px;
                    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
                    padding: 0 24px;
                }
                .btn-check:not(:disabled):hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
                .btn-check:not(:disabled):active { transform: translateY(0); }
                .btn-check:disabled { opacity: 0.42; cursor: not-allowed; }

                .btn-default  { background: var(--player-accent); color: #fff; box-shadow: 0 3px 12px rgba(99,102,241,0.35); }
                .btn-correct  { background: #10b981; color: #fff; box-shadow: 0 3px 12px rgba(16,185,129,0.35); }
                .btn-incorrect { background: #ef4444; color: #fff; box-shadow: 0 3px 12px rgba(239,68,68,0.3); }
                .btn-submitting { background: var(--player-accent); color: #fff; opacity: 0.7; }

                .passage-card {
                    border-radius: 14px;
                    border: 1px solid rgba(var(--player-accent-rgb), 0.1);
                    background: rgba(var(--player-accent-rgb), 0.025);
                    padding: 20px 24px;
                    font-size: 0.875rem;
                    line-height: 1.75;
                }

                .kbd-hint {
                    display: inline-flex; align-items: center; gap: 4px;
                    font-size: 11px; opacity: 0.45; font-family: monospace;
                    border: 1px solid currentColor; border-radius: 4px;
                    padding: 1px 5px; margin-left: 6px;
                }
            `}</style>

            <div className="player-font mx-auto max-w-2xl py-6 px-4" style={{ paddingBottom: '120px' }}>

                {/* ── Header ── */}
                <div style={{ marginBottom: 20 }}>
                    {/* Top row: title + timer */}
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 14 }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                            <div style={{
                                width: 36, height: 36,
                                borderRadius: 10,
                                background: 'rgba(var(--player-accent-rgb),0.1)',
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                flexShrink: 0,
                            }}>
                                <Icon name="sparkles" size={18} style={{ opacity: 0.8, filter: 'hue-rotate(20deg) saturate(1.5)' }} />
                            </div>
                            <div>
                                <div style={{ fontWeight: 700, fontSize: '0.9375rem', lineHeight: 1.2 }}>{node.title}</div>
                                <div style={{ fontSize: '0.75rem', opacity: 0.5, marginTop: 2 }}>
                                    {node.exam.language.name} · {node.exam.name}
                                </div>
                            </div>
                        </div>

                        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                            {/* Question counter */}
                            <div style={{
                                fontSize: '0.8125rem',
                                fontWeight: 600,
                                fontVariantNumeric: 'tabular-nums',
                                opacity: 0.55,
                            }}>
                                {overallProgressCount + 1}
                                <span style={{ opacity: 0.5, margin: '0 3px' }}>/</span>
                                {totalQuestionsInSession}
                            </div>

                            {/* Arc timer — hidden ExerciseTimer drives the state */}
                            <div style={{ display: 'none' }}>
                                <ExerciseTimer
                                    key={timerKey}
                                    timeLimit={TIME_PER_QUESTION}
                                    onTimeUpdate={(elapsed) => {
                                        setTimeSpent(prev => prev + 1);
                                        setTimerSeconds(TIME_PER_QUESTION - elapsed);
                                    }}
                                    onExpire={handleTimeExpire}
                                />
                            </div>
                            <ArcTimer
                                seconds={timerSeconds}
                                limit={TIME_PER_QUESTION}
                                warning={timerWarning}
                                critical={timerCritical}
                                expired={timerExpired}
                            />
                        </div>
                    </div>

                    {/* Segmented progress dots */}
                    <ProgressDots total={totalQuestionsInSession} current={overallProgressCount} />
                </div>

                {/* ── Exercise Content ── */}
                <div
                    ref={contentRef}
                    className={visible ? 'question-enter' : 'question-exit'}
                    style={{ display: 'flex', flexDirection: 'column', gap: 14 }}
                >
                    {exercise.content?.passage && (
                        <div className="passage-card prose prose-slate dark:prose-invert max-w-none">
                            {exercise.content.passage}
                        </div>
                    )}

                    <div className="player-card" style={{ padding: '24px' }}>
                        <Component
                            key={question.id ?? currentQuestionIndex}
                            question={{ ...exercise.content, ...question }}
                            onAnswer={(childId: string, ans: any) => handleAnswer(childId ?? String(currentQuestionIndex), ans)}
                            selectedAnswer={answers[question.id ?? String(currentQuestionIndex)]}
                            disabled={isChecked}
                        />
                    </div>
                </div>
            </div>

            {/* ── Bottom Action Bar ── */}
            <div
                className={`fixed bottom-0 left-0 right-0 transition-all duration-300 ${
                    isChecked
                        ? isCorrect ? 'action-bar-correct' : 'action-bar-incorrect'
                        : ''
                }`}
                style={{
                    borderTop: '1px solid rgba(0,0,0,0.07)',
                    background: isChecked ? undefined : 'var(--background, #fff)',
                    backdropFilter: 'blur(12px)',
                    WebkitBackdropFilter: 'blur(12px)',
                }}
            >
                <div
                    className="player-font mx-auto"
                    style={{
                        maxWidth: 672,
                        padding: '14px 16px 18px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: 16,
                    }}
                >
                    {/* Feedback panel */}
                    <div style={{ flex: 1, minHeight: 48, display: 'flex', alignItems: 'center' }}>
                        {isChecked && (
                            <div className="feedback-enter" style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                <div
                                    className="check-pop"
                                    style={{
                                        width: 44, height: 44, borderRadius: '50%', flexShrink: 0,
                                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                                        background: isCorrect ? '#10b981' : '#ef4444',
                                    }}
                                >
                                    <Icon
                                        name={isCorrect ? 'check' : 'x'}
                                        size={22}
                                        style={{ filter: 'brightness(0) invert(1)' }}
                                    />
                                </div>
                                <div>
                                    <div style={{
                                        fontWeight: 700,
                                        fontSize: '1rem',
                                        color: isCorrect ? '#059669' : '#dc2626',
                                        lineHeight: 1.2,
                                    }}>
                                        {isCorrect ? t('exercise.correct') : t('exercise.incorrect')}
                                    </div>
                                    {!isCorrect && (
                                        <button
                                            style={{
                                                background: 'none', border: 'none', padding: 0, cursor: 'pointer',
                                                fontSize: '0.8125rem', fontWeight: 500,
                                                color: '#dc2626', textDecoration: 'underline', marginTop: 2,
                                                fontFamily: 'inherit', opacity: 0.8,
                                            }}
                                        >
                                            {t('exercise.why_incorrect')}
                                        </button>
                                    )}
                                </div>
                            </div>
                        )}

                        {!isChecked && (
                            <div style={{ fontSize: '0.75rem', opacity: 0.3, userSelect: 'none' }}>
                                <span className="kbd-hint">Space</span> {t('exercise.check', 'Check')}
                            </div>
                        )}
                    </div>

                    {/* CTA button */}
                    <button
                        className={`btn-check ${
                            submitting
                                ? 'btn-submitting'
                                : isChecked
                                    ? isCorrect ? 'btn-correct' : 'btn-incorrect'
                                    : 'btn-default'
                        }`}
                        disabled={!hasAnswer || submitting}
                        onClick={isChecked ? (isCorrect ? nextStep : handleRetry) : checkAnswer}
                    >
                        {submitting ? (
                            <>
                                <span style={{ opacity: 0.8 }}>{t('exercise.finish', 'Finish')}</span>
                            </>
                        ) : isChecked ? (
                            isCorrect ? (
                                <>
                                    {isLastQuestion ? t('exercise.finish', 'Finish') : t('exercise.next', 'Next')}
                                    <Icon name="chevron-right" size={15} style={{ filter: 'brightness(0) invert(1)' }} />
                                </>
                            ) : (
                                <>
                                    {t('exercise.retry', 'Réessayer')}
                                    <Icon name="refresh-cw" size={15} style={{ filter: 'brightness(0) invert(1)' }} />
                                </>
                            )
                        ) : (
                            <>
                                {t('exercise.check', 'Check')}
                                <Icon name="chevron-right" size={15} style={{ filter: 'brightness(0) invert(1)' }} />
                            </>
                        )}
                    </button>
                </div>
            </div>
        </AppLayout>
    );
}
