import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { useState, useEffect, useCallback, useRef } from 'react';
import type { ExamWithLanguage } from '@/types';

interface PlacementQuestion {
    id: string;
    level: string;
    text: string;
    sentence: string;
    options: string[];
    correct: number;
}

interface Props {
    exam: ExamWithLanguage | null;
    questions: PlacementQuestion[];
}

const levelColors: Record<string, string> = {
    A1: 'bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400',
    A2: 'bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-400',
    B1: 'bg-blue-100 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-400',
    B2: 'bg-indigo-100 text-indigo-700 border border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400',
    C1: 'bg-purple-100 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-400',
    C2: 'bg-red-100 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-400',
};

// 30s per question for placement test — exam ambiance
const QUESTION_TIME = 30;

function QuestionTimer({ seconds: total, onExpire, expired }: { seconds: number; onExpire: () => void; expired: boolean }) {
    const [remaining, setRemaining] = useState(total);
    const calledRef = useRef(false);

    useEffect(() => {
        if (expired) return;
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
    }, [expired]);

    const ratio = remaining / total;
    const isWarning = ratio <= 0.4 && ratio > 0.15;
    const isCritical = ratio <= 0.15;
    const color = isCritical ? '#ef4444' : isWarning ? '#f59e0b' : '#4A90E2';

    // SVG arc
    const r = 11; const circ = 2 * Math.PI * r;
    const dash = circ * ratio;

    return (
        <div className="relative flex items-center justify-center" style={{ width: 32, height: 32 }}>
            <svg width="32" height="32" viewBox="0 0 32 32" style={{ transform: 'rotate(-90deg)' }}>
                <circle cx="16" cy="16" r={r} fill="none" stroke="#e5e7eb" strokeWidth="3" />
                <circle cx="16" cy="16" r={r} fill="none" stroke={color} strokeWidth="3"
                    strokeDasharray={`${dash} ${circ}`} strokeLinecap="round"
                    style={{ transition: 'stroke-dasharray 1s linear, stroke 0.3s' }} />
            </svg>
            <span className={`absolute text-[9px] font-bold tabular-nums`} style={{ color }}>{remaining}</span>
        </div>
    );
}

export default function PlacementTest({ exam, questions }: Props) {
    const [currentQ, setCurrentQ] = useState(0);
    const [answers, setAnswers] = useState<Record<string, number>>({});
    const [selectedOption, setSelectedOption] = useState<number | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [visible, setVisible] = useState(true);
    const [timerKey, setTimerKey] = useState(0);
    const [timeExpired, setTimeExpired] = useState(false);
    const autoAdvanceRef = useRef(false);

    const question = questions[currentQ];
    const total = questions.length;
    const isLast = currentQ === total - 1;
    const progress = (currentQ / total) * 100;

    useEffect(() => {
        setVisible(true);
        setTimeExpired(false);
        autoAdvanceRef.current = false;
    }, [currentQ]);

    const advanceQuestion = useCallback((forcedAnswer: number | null) => {
        if (autoAdvanceRef.current) return;
        autoAdvanceRef.current = true;

        const answer = forcedAnswer ?? selectedOption;
        const newAnswers = answer !== null ? { ...answers, [question.id]: answer } : answers;
        setAnswers(newAnswers);

        if (isLast) {
            setSubmitting(true);
            router.post(route('onboarding.placement.store'), {
                answers: newAnswers,
                questions: JSON.stringify(questions),
            });
        } else {
            setVisible(false);
            setTimeout(() => {
                setSelectedOption(null);
                setCurrentQ(prev => prev + 1);
                setTimerKey(k => k + 1);
                setVisible(true);
            }, 180);
        }
    }, [selectedOption, answers, question, isLast, questions]);

    const handleTimeExpire = useCallback(() => {
        setTimeExpired(true);
        // Brief flash then auto-advance with no answer (counts as wrong)
        setTimeout(() => advanceQuestion(null), 800);
    }, [advanceQuestion]);

    function handleSelect(index: number) {
        if (!visible || timeExpired) return;
        setSelectedOption(index);
    }

    function handleNext() {
        if (selectedOption === null || !visible || timeExpired) return;

        advanceQuestion(selectedOption);
    }

    if (!question) {
        return (
            <OnboardingLayout title="Test de placement" step={3}>
                <div className="flex items-center justify-center py-20">
                    <div className="flex flex-col items-center gap-4">
                        <div className="h-12 w-12 animate-spin rounded-full border-4 border-primary border-t-transparent" />
                        <p className="text-muted-foreground">Chargement des questions…</p>
                    </div>
                </div>
            </OnboardingLayout>
        );
    }

    return (
        <OnboardingLayout title="Test de placement" step={3}>
            <div className="space-y-6">
                {/* Header */}
                <div className="text-center">
                    <h1 className="text-2xl font-bold sm:text-3xl">
                        Test de placement
                        {exam?.language?.name ? ` — ${exam.language.name}` : ''}
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Répondez honnêtement pour que l'IA évalue votre niveau avec précision
                    </p>
                </div>

                {/* Progress bar + timer */}
                <div className="space-y-2">
                    <div className="flex items-center justify-between text-xs text-muted-foreground">
                        <span>Question {currentQ + 1} / {total}</span>
                        <div className="flex items-center gap-2">
                            <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${levelColors[question.level] ?? ''}`}>
                                Niveau {question.level}
                            </span>
                            {/* Countdown per question */}
                            <QuestionTimer key={timerKey} seconds={QUESTION_TIME} onExpire={handleTimeExpire} expired={timeExpired} />
                        </div>
                    </div>
                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                        <div
                            className="h-2 rounded-full bg-gradient-to-r from-primary to-primary/70 transition-all duration-500 ease-out"
                            style={{ width: `${progress}%` }}
                        />
                    </div>
                </div>

                {/* Question card with fade/slide animation */}
                <div
                    style={{
                        opacity: visible ? 1 : 0,
                        transform: visible ? 'translateY(0)' : 'translateY(8px)',
                        transition: 'opacity 0.18s ease, transform 0.18s ease',
                    }}
                >
                    <div className="mx-auto max-w-xl space-y-4">
                        <div className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                            <p className="font-semibold leading-relaxed">{question.text}</p>
                            {question.sentence && (
                                <div className="mt-3 rounded-xl bg-muted/60 px-4 py-3">
                                    <p className="text-center text-base italic text-muted-foreground">
                                        {question.sentence}
                                    </p>
                                </div>
                            )}
                        </div>

                        <div className="grid gap-2.5">
                            {question.options.map((option, i) => {
                                const isSelected = selectedOption === i;
                                return (
                                    <button
                                        key={i}
                                        onClick={() => handleSelect(i)}
                                        disabled={submitting}
                                        className={`group flex items-center gap-4 rounded-xl border p-4 text-left transition-all duration-150 ${
                                            isSelected
                                                ? 'border-primary bg-primary/5 shadow-md ring-2 ring-primary/20'
                                                : 'border-border bg-card hover:border-primary/40 hover:shadow-sm'
                                        }`}
                                    >
                                        <span
                                            className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold transition-colors ${
                                                isSelected
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-muted text-muted-foreground group-hover:bg-primary/15 group-hover:text-primary'
                                            }`}
                                        >
                                            {String.fromCharCode(65 + i)}
                                        </span>
                                        <span className={`font-medium ${isSelected ? 'text-primary' : ''}`}>
                                            {option}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Navigation */}
                <div className="flex items-center justify-between pt-2">
                    <Button
                        variant="outline"
                        disabled={currentQ === 0 || submitting || timeExpired}
                        onClick={() => {
                            setVisible(false);
                            setTimeout(() => {
                                const prevId = questions[currentQ - 1]?.id;
                                setSelectedOption(prevId !== undefined ? (answers[prevId] ?? null) : null);
                                setCurrentQ(prev => prev - 1);
                                setTimerKey(k => k + 1);
                                setVisible(true);
                            }, 180);
                        }}
                    >
                        ← Retour
                    </Button>

                    <Button
                        size="lg"
                        disabled={selectedOption === null || submitting || timeExpired}
                        onClick={handleNext}
                        className="min-w-[160px] gap-2 bg-gradient-to-r from-primary to-primary/85 font-semibold shadow-md hover:shadow-lg"
                    >
                        {submitting ? (
                            <>
                                <div className="h-4 w-4 animate-spin rounded-full border-2 border-primary-foreground border-t-transparent" />
                                Analyse en cours…
                            </>
                        ) : isLast ? (
                            '✨ Voir mes résultats'
                        ) : (
                            'Suivant →'
                        )}
                    </Button>
                </div>

                <p className="text-center text-xs text-muted-foreground">
                    {Math.round(progress)}% complété · Questions générées par IA pour {exam?.name ?? 'votre examen'}
                </p>
            </div>
        </OnboardingLayout>
    );
}
