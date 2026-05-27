import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useState, useCallback, useMemo, useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import type { ExamRecord, ExamSection } from '@/types';

// Icons & Components
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
    exam: ExamRecord & { sections: ExamSection[] };
    exercises: Exercise[];
    totalExamsTime: number; // in minutes
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

function ProgressDots({ total, current }: { total: number; current: number }) {
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

function ExamGlobalBanner({ totalMinutes, started, onExpire }: { totalMinutes: number; started: boolean; onExpire: () => void }) {
    const { t } = useTranslation();
    const total = totalMinutes * 60;
    const [remaining, setRemaining] = useState(total);
    const calledRef = useRef(false);
    const OXFORD = '#1A2B48';

    useEffect(() => {
        if (!started) return; // ne démarre pas le chrono avant que l'utilisateur clique sur "Commencer"
        const id = setInterval(() => {
            setRemaining(prev => {
                const next = prev - 1;
                if (next <= 0 && !calledRef.current) {
                    calledRef.current = true;
                    clearInterval(id);
                    onExpire();
                }
                return Math.max(0, next);
            });
        }, 1000);
        return () => clearInterval(id);
    }, [onExpire, started]);

    const mins = Math.floor(remaining / 60);
    const secs = remaining % 60;
    const ratio = remaining / total;
    const isCritical = ratio <= 0.1;

    return (
        <div
            className="fixed top-4 left-1/2 z-50 -translate-x-1/2 flex items-center gap-3 rounded-2xl px-5 py-2.5 shadow-xl"
            style={{
                background: isCritical ? '#ef4444' : OXFORD,
                boxShadow: `0 4px 0 0 ${isCritical ? '#b91c1c' : '#0e1a2e'}`,
                animation: isCritical ? 'pulse 0.8s ease-in-out infinite' : undefined,
            }}
        >
            <img src="/icons/clock.png" alt="" width={16} height={16} style={{ filter: 'brightness(0) invert(1)' }} />
            <span className="text-sm font-black tabular-nums text-white">
                {String(mins).padStart(2, '0')}:{String(secs).padStart(2, '0')}
            </span>
            <span className="text-xs font-bold text-white/60">{t('practice.exam_mode_badge', 'Mode Examen')}</span>
        </div>
    );
}

export default function ExamSimulator({ exam, exercises, totalExamsTime }: Props) {
    const { t } = useTranslation();
    const [currentExerciseIndex, setCurrentExerciseIndex] = useState(0);
    const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
    const [answers, setAnswers] = useState<Record<string, any>>({});
    const [submitting, setSubmitting] = useState(false);
    const [transitioning, setTransitioning] = useState(false);
    const [visible, setVisible] = useState(true);
    const [timeSpent, setTimeSpent] = useState(0);
    const [examExpired, setExamExpired] = useState(false);
    const [examStarted, setExamStarted] = useState(false);
    const OXFORD = '#1A2B48';

    const exercise = exercises[currentExerciseIndex];
    const componentKey = exercise?.exercise_type?.component_key ?? 'mcq';

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

    // Track total time loosely (uniquement quand l'examen est lancé)
    useEffect(() => {
        if (!examStarted) return;
        const id = setInterval(() => setTimeSpent(t => t + 1), 1000);
        return () => clearInterval(id);
    }, [examStarted]);

    const handleAnswer = useCallback((questionId: string, answer: any) => {
        setAnswers(prev => ({ ...prev, [questionId]: answer }));
    }, []);

    const submitExam = useCallback(() => {
        setSubmitting(true);
        router.post(route('practice.simulate.store', exam.id), {
            answers,
            time_spent: timeSpent,
        }, { forceFormData: true });
    }, [answers, exam.id, timeSpent]);

    const handleExamExpire = useCallback(() => {
        setExamExpired(true);
    }, []);

    const nextStep = useCallback(() => {
        const isEndOfSession = currentExerciseIndex === exercises.length - 1 && currentQuestionIndex === questions.length - 1;

        if (isEndOfSession) {
            submitExam();
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
        }, 220);
    }, [currentExerciseIndex, currentQuestionIndex, exercises.length, questions.length, submitExam]);

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
            <Head title={`Simulator: ${exam.name} - PrePla`} />
            <ExamGlobalBanner totalMinutes={totalExamsTime} started={examStarted} onExpire={handleExamExpire} />

            {/* ── Modal de pré-démarrage : explique les règles, le chrono ne démarre qu'au clic ── */}
            {!examStarted && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
                    <div className="rounded-3xl bg-white p-8 text-center shadow-2xl max-w-md mx-4 animate-in fade-in zoom-in duration-300">
                        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full" style={{ background: 'rgba(99,102,241,0.1)' }}>
                            <img src="/icons/clock.png" alt="" width={32} height={32} style={{ filter: 'hue-rotate(220deg) saturate(2)' }} />
                        </div>
                        <h2 className="text-2xl font-black mb-2" style={{ color: OXFORD }}>
                            {t('practice.exam_ready_title', 'Prêt à commencer ?')}
                        </h2>
                        <p className="text-sm text-muted-foreground mb-1">
                            {t('practice.exam_ready_exam', 'Examen')} : <strong>{exam.name}</strong>
                        </p>
                        <p className="text-sm text-muted-foreground mb-4">
                            {t('practice.exam_ready_duration', 'Durée')} : <strong>{totalExamsTime} {t('practice.minutes', 'minutes')}</strong>
                            {' · '}
                            <strong>{totalQuestionsInSession} {t('practice.questions', 'questions')}</strong>
                        </p>
                        <div className="text-left bg-slate-50 rounded-2xl p-4 mb-6 text-sm space-y-2">
                            <div className="flex items-start gap-2">
                                <span className="text-emerald-600 font-bold">•</span>
                                <span>{t('practice.exam_rule_timer', 'Le chrono démarre dès que tu cliques sur Commencer.')}</span>
                            </div>
                            <div className="flex items-start gap-2">
                                <span className="text-emerald-600 font-bold">•</span>
                                <span>{t('practice.exam_rule_validate', 'Utilise le bouton Suivant en bas pour valider chaque question.')}</span>
                            </div>
                            <div className="flex items-start gap-2">
                                <span className="text-emerald-600 font-bold">•</span>
                                <span>{t('practice.exam_rule_submit', 'À la dernière question, le bouton devient Soumettre.')}</span>
                            </div>
                            <div className="flex items-start gap-2">
                                <span className="text-amber-600 font-bold">•</span>
                                <span>{t('practice.exam_rule_noback', 'Tu ne pourras pas revenir en arrière une fois lancé.')}</span>
                            </div>
                        </div>
                        <button
                            className="w-full rounded-2xl py-4 text-base font-black text-white shadow-lg"
                            style={{ background: OXFORD, boxShadow: '0 4px 0 0 #0e1a2e' }}
                            onClick={() => setExamStarted(true)}
                        >
                            {t('practice.exam_start_cta', 'Commencer l\'examen')}
                        </button>
                    </div>
                </div>
            )}

            {examExpired && (
                <div className="fixed inset-0 z-50 flex flex-col items-center justify-center gap-4 bg-black/70 backdrop-blur-sm">
                    <div className="rounded-3xl bg-white p-8 text-center shadow-2xl max-w-sm mx-4">
                        <img src="/icons/clock.png" alt="" width={48} height={48} className="mx-auto mb-3" style={{ filter: 'brightness(0) saturate(100%) invert(30%) sepia(80%) saturate(600%) hue-rotate(330deg)' }} />
                        <h2 className="text-xl font-black" style={{ color: OXFORD }}>{t('practice.exam_expired_title', 'Temps écoulé !')}</h2>
                        <p className="mt-2 text-sm text-muted-foreground">{t('practice.exam_expired_desc', 'Vous avez atteint la limite de temps.')}</p>
                        <button
                            className="mt-5 w-full rounded-xl py-3 text-sm font-black text-white"
                            style={{ background: OXFORD }}
                            onClick={() => submitExam()}
                        >
                            {t('practice.exam_expired_cta', 'Soumettre mon examen')}
                        </button>
                    </div>
                </div>
            )}

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

                .player-font { font-family: 'DM Sans', system-ui, sans-serif; }
                .question-enter { animation: slideInUp 0.26s cubic-bezier(.22,1,.36,1) forwards; }
                .question-exit  { animation: slideOutUp 0.18s cubic-bezier(.4,0,.2,1) forwards; }

                .player-card {
                    border-radius: 16px;
                    border: 1px solid rgba(var(--player-accent-rgb), 0.12);
                    background: var(--card, #fff);
                    box-shadow: 0 2px 16px rgba(0,0,0,0.05), 0 0 0 1px rgba(var(--player-accent-rgb),0.04);
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
                .btn-submitting { background: var(--player-accent); color: #fff; opacity: 0.7; }

                .passage-card {
                    border-radius: 14px;
                    border: 1px solid rgba(var(--player-accent-rgb), 0.1);
                    background: rgba(var(--player-accent-rgb), 0.025);
                    padding: 20px 24px;
                    font-size: 0.875rem;
                    line-height: 1.75;
                }
            `}</style>

            <div className="player-font mx-auto max-w-2xl py-6 px-4 pt-16" style={{ paddingBottom: '120px' }}>

                {/* ── Header ── */}
                <div style={{ marginBottom: 20 }}>
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 14 }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                            <div style={{
                                width: 36, height: 36,
                                borderRadius: 10,
                                background: 'rgba(var(--player-accent-rgb),0.1)',
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                flexShrink: 0,
                            }}>
                                <Icon name="book" size={18} style={{ opacity: 0.8, filter: 'hue-rotate(20deg) saturate(1.5)' }} />
                            </div>
                            <div>
                                <div style={{ fontWeight: 700, fontSize: '0.9375rem', lineHeight: 1.2 }}>Examen: {exam.name}</div>
                                <div style={{ fontSize: '0.75rem', opacity: 0.5, marginTop: 2 }}>
                                    {exercise?.exercise_type?.name}
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
                        </div>
                    </div>

                    {/* Segmented progress dots */}
                    <ProgressDots total={totalQuestionsInSession} current={overallProgressCount} />
                </div>

                {/* ── Exercise Content ── */}
                <div
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
                            disabled={false} // Exams don't lock inputs immediately!
                        />
                    </div>
                </div>
            </div>

            {/* ── Bottom Action Bar ── */}
            <div
                className={`fixed bottom-0 left-0 right-0 transition-all duration-300`}
                style={{
                    borderTop: '1px solid rgba(0,0,0,0.07)',
                    background: 'var(--background, #fff)',
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
                    <div style={{ flex: 1, minHeight: 48, display: 'flex', alignItems: 'center' }}>
                        {/* No Check Feedback in Exam Simulator Mode! */}
                    </div>

                    {/* CTA button */}
                    <button
                        className={`btn-check ${submitting ? 'btn-submitting' : 'btn-default'}`}
                        disabled={submitting}
                        onClick={nextStep}
                    >
                        {submitting ? (
                            <span style={{ opacity: 0.8 }}>{t('exercise.finish', 'Finish')}</span>
                        ) : (
                            <>
                                {isLastQuestion ? t('common.submit', 'Soumettre') : t('exercise.next', 'Next')}
                                <Icon name="chevron-right" size={15} style={{ filter: 'brightness(0) invert(1)' }} />
                            </>
                        )}
                    </button>
                </div>
            </div>
        </AppLayout>
    );
}
