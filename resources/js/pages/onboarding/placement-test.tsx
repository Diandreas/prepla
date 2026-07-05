import { router } from '@inertiajs/react';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { useState, useEffect, useCallback, useRef } from 'react';
import type { ExamWithLanguage } from '@/types';

// ─── Components ──────────────────────────────────────────────────────────────
function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}

// ─── Types ──────────────────────────────────────────────────────────────────

interface MCQQuestion {
    id: string;
    level: string;
    text: string;
    sentence?: string;
    options: string[];
    correct_answer: string;
}

interface SectionBData {
    passage: string;
    questions: MCQQuestion[];
}

interface SectionCData {
    prompt: string;
}

interface Props {
    exam: ExamWithLanguage | null;
    sectionA: MCQQuestion[];
    sectionB: SectionBData;
    sectionC: SectionCData;
}

// ─── Brand colors ────────────────────────────────────────────────────────────
const OXFORD = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';

// ─── Countdown ring component ────────────────────────────────────────────────

function QuestionTimer({
    seconds: total,
    onExpire,
    expired,
}: {
    seconds: number;
    onExpire: () => void;
    expired: boolean;
}) {
    const [remaining, setRemaining] = useState(total);
    const calledRef = useRef(false);

    useEffect(() => {
        setRemaining(total);
        calledRef.current = false;
    }, [total]);

    useEffect(() => {
        if (expired) return;
        const id = setInterval(() => {
            setRemaining((prev) => Math.max(0, prev - 1));
        }, 1000);
        return () => clearInterval(id);
    }, [expired]);

    useEffect(() => {
        if (remaining <= 0 && !calledRef.current && !expired) {
            calledRef.current = true;
            onExpire();
        }
    }, [remaining, expired, onExpire]);

    const ratio = remaining / total;
    const isCritical = ratio <= 0.2;
    const isWarning = ratio <= 0.45 && !isCritical;
    const color = isCritical ? '#ef4444' : isWarning ? '#f59e0b' : SKY;

    const r = 11;
    const circ = 2 * Math.PI * r;
    const dash = circ * ratio;

    return (
        <div className="relative flex items-center justify-center" style={{ width: 34, height: 34 }}>
            <svg width="34" height="34" viewBox="0 0 34 34" style={{ transform: 'rotate(-90deg)' }}>
                <circle cx="17" cy="17" r={r} fill="none" stroke="#e5e7eb" strokeWidth="3" />
                <circle
                    cx="17" cy="17" r={r} fill="none" stroke={color} strokeWidth="3"
                    strokeDasharray={`${dash} ${circ}`} strokeLinecap="round"
                    style={{ transition: 'stroke-dasharray 1s linear, stroke 0.3s' }}
                />
            </svg>
            <span className="absolute text-[10px] font-black tabular-nums" style={{ color }}>
                {remaining}
            </span>
        </div>
    );
}

// ─── Big essay countdown ──────────────────────────────────────────────────────

function EssayTimer({
    totalSeconds,
    onExpire,
    onTick,
}: {
    totalSeconds: number;
    onExpire: () => void;
    onTick: (remaining: number) => void;
}) {
    const [remaining, setRemaining] = useState(totalSeconds);
    const calledRef = useRef(false);

    useEffect(() => {
        const id = setInterval(() => {
            setRemaining((prev) => Math.max(0, prev - 1));
        }, 1000);
        return () => clearInterval(id);
    }, []);

    useEffect(() => {
        onTick(remaining);
        if (remaining <= 0 && !calledRef.current) {
            calledRef.current = true;
            onExpire();
        }
    }, [remaining, onTick, onExpire]);

    const mins = Math.floor(remaining / 60);
    const secs = remaining % 60;
    const ratio = remaining / totalSeconds;
    const isCritical = ratio <= 0.15;
    const isWarning = ratio <= 0.35 && !isCritical;
    const color = isCritical ? '#ef4444' : isWarning ? '#f59e0b' : SKY;

    return (
        <div
            className="flex items-center gap-2 rounded-xl px-3 py-1.5 font-black tabular-nums"
            style={{
                background: isCritical ? '#fef2f2' : isWarning ? '#fffbeb' : `${SKY}12`,
                color,
                animation: isCritical ? 'pulse 0.8s ease-in-out infinite' : undefined,
            }}
        >
            <img
                src="/icons/clock.png"
                alt=""
                width={14}
                height={14}
                style={{
                    objectFit: 'contain',
                    filter: isCritical
                        ? 'brightness(0) saturate(100%) invert(30%) sepia(80%) saturate(600%) hue-rotate(330deg)'
                        : isWarning
                        ? 'brightness(0) saturate(100%) invert(55%) sepia(60%) saturate(500%) hue-rotate(15deg)'
                        : `brightness(0) saturate(100%) invert(47%) sepia(80%) saturate(600%) hue-rotate(195deg)`,
                }}
            />
            <span className="text-base">
                {String(mins).padStart(2, '0')}:{String(secs).padStart(2, '0')}
            </span>
        </div>
    );
}

// ─── Section transition flash ─────────────────────────────────────────────────

function SectionTransition({ label, onDone }: { label: string; onDone: () => void }) {
    useEffect(() => {
        const t = setTimeout(onDone, 1600);
        return () => clearTimeout(t);
    }, [onDone]);

    return (
        <div className="flex flex-col items-center justify-center py-20 text-center">
            <div
                className="mb-4 flex h-16 w-16 items-center justify-center rounded-full"
                style={{ background: `${SKY}18`, border: `3px solid ${SKY}` }}
            >
                <img
                    src="/icons/check-circle.png"
                    alt=""
                    width={32}
                    height={32}
                    style={{  objectFit: 'contain' }}
                />
            </div>
            <p className="text-lg font-black" style={{ color: OXFORD }}>
                {label} terminée !
            </p>
            <p className="mt-1 text-sm text-muted-foreground">Préparation de la section suivante…</p>
        </div>
    );
}

// ─── MCQ question block ───────────────────────────────────────────────────────

function MCQBlock({
    question,
    selectedAnswer,
    onAnswer,
    disabled,
}: {
    question: MCQQuestion;
    selectedAnswer?: string;
    onAnswer: (qId: string, letter: string) => void;
    disabled: boolean;
}) {
    return (
        <div className="space-y-3 sm:space-y-4">
            <p className="text-sm sm:text-base font-bold leading-snug" style={{ color: OXFORD }}>{question.text}</p>
            {question.sentence && (
                <div className="rounded-xl bg-muted/60 px-4 py-2.5">
                    <p className="text-center italic text-sm text-muted-foreground">{question.sentence}</p>
                </div>
            )}
            <div className="grid gap-2 sm:gap-2.5">
                {question.options.map((option, i) => {
                    const letter = String.fromCharCode(65 + i);
                    const isSelected = selectedAnswer === letter;
                    // Duolingo-style answer button: chunky rounded card with a 3D
                    // bottom shadow that "presses" when selected.
                    return (
                        <button
                            key={i}
                            disabled={disabled}
                            onClick={() => onAnswer(question.id, letter)}
                            className="duo-press flex items-center gap-3 rounded-2xl px-3.5 py-3 text-left text-sm transition-all disabled:opacity-40"
                            style={{
                                background: isSelected ? 'rgba(74,144,226,0.1)' : '#fff',
                                border: `2px solid ${isSelected ? SKY : '#e5e7eb'}`,
                                boxShadow: `0 4px 0 0 ${isSelected ? '#2a6fc0' : '#e5e7eb'}`,
                                color: isSelected ? SKY : OXFORD,
                            }}
                        >
                            <span
                                className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-black transition-colors"
                                style={{
                                    background: isSelected ? SKY : '#f1f5f9',
                                    color: isSelected ? '#fff' : '#94a3b8',
                                }}
                            >
                                {letter}
                            </span>
                            <span className="font-bold">{option}</span>
                        </button>
                    );
                })}

                {/* Skip option */}
                <button
                    disabled={disabled}
                    onClick={() => onAnswer(question.id, 'SKIP')}
                    className={`mt-1 flex items-center justify-center gap-2 rounded-2xl border-2 border-dashed p-2.5 text-sm font-bold transition-all ${
                        selectedAnswer === 'SKIP'
                            ? 'border-amber-400 bg-amber-50 text-amber-700'
                            : 'border-slate-200 text-slate-400 hover:border-slate-300 hover:bg-slate-50'
                    }`}
                >
                    <Icon name="help-circle" size={16} />
                    Je ne sais pas
                </button>
            </div>
        </div>
    );
}

// ─── Level badge ──────────────────────────────────────────────────────────────

const levelColors: Record<string, string> = {
    A1: 'bg-emerald-100 text-emerald-700 border-emerald-200',
    A2: 'bg-green-100 text-green-700 border-green-200',
    B1: 'bg-blue-100 text-blue-700 border-blue-200',
    B2: 'bg-indigo-100 text-indigo-700 border-indigo-200',
    C1: 'bg-purple-100 text-purple-700 border-purple-200',
};

// ─── Section progress mini-bar ────────────────────────────────────────────────

function SectionBar({ label, state }: { label: string; state: 'done' | 'active' | 'pending' }) {
    return (
        <div className="flex flex-1 flex-col items-center gap-1">
            <div className="relative h-1.5 w-full overflow-hidden rounded-full bg-muted">
                <div
                    className="absolute inset-y-0 left-0 rounded-full transition-all duration-700"
                    style={{
                        width: state === 'done' ? '100%' : state === 'active' ? '55%' : '0%',
                        background: state === 'done' ? '#22c55e' : SKY,
                    }}
                />
            </div>
            <span
                className="text-[10px] font-bold"
                style={{ color: state === 'active' ? OXFORD : state === 'done' ? '#22c55e' : '#94a3b8' }}
            >
                {label}
            </span>
        </div>
    );
}

// ─── Main component ───────────────────────────────────────────────────────────

type Section = 'A' | 'transition_B' | 'B' | 'transition_C' | 'C' | 'submitting';

export default function PlacementTest({ exam, sectionA, sectionB, sectionC }: Props) {
    const [section, setSection] = useState<Section>('A');
    const [currentQ, setCurrentQ] = useState(0);
    const [answers, setAnswers] = useState<Record<string, string>>({});
    const [timerKey, setTimerKey] = useState(0);
    const [qExpired, setQExpired] = useState(false);
    const [essayText, setEssayText] = useState('');
    const [essayRemaining, setEssayRemaining] = useState(300);
    const [visible, setVisible] = useState(true);
    const submittingRef = useRef(false);

    // Current question depends on section
    const currentQuestions = section === 'A' || section === 'transition_B'
        ? sectionA
        : section === 'B' || section === 'transition_C'
        ? sectionB.questions
        : [];

    const question = currentQuestions[currentQ];
    const isLastQ = currentQ === currentQuestions.length - 1;

    // Section-level progress state
    const sectionState = (s: 'A' | 'B' | 'C') => {
        if (s === 'A') return section === 'A' ? 'active' : 'done';
        if (s === 'B') return section === 'B' || section === 'transition_C' ? 'active' : section === 'A' || section === 'transition_B' ? 'pending' : 'done';
        if (s === 'C') return section === 'C' ? 'active' : section === 'submitting' ? 'done' : 'pending';
        return 'pending';
    };

    // --- Navigate question ---
    const advanceQuestion = useCallback(
        (forcedAnswer?: string) => {
            const qId = question?.id;
            const ans = forcedAnswer ?? answers[qId ?? ''];
            const newAnswers = qId ? { ...answers, [qId]: ans ?? '' } : answers;
            setAnswers(newAnswers);
            setQExpired(false);

            if (isLastQ) {
                // End of section
                if (section === 'A') {
                    setSection('transition_B');
                } else if (section === 'B') {
                    setSection('transition_C');
                }
            } else {
                setVisible(false);
                setTimeout(() => {
                    setCurrentQ((q) => q + 1);
                    setTimerKey((k) => k + 1);
                    setVisible(true);
                }, 180);
            }
        },
        [question, answers, isLastQ, section]
    );

    const handleQExpire = useCallback(() => {
        setQExpired(true);
        setTimeout(() => advanceQuestion(''), 700);
    }, [advanceQuestion]);

    // --- Essay ---
    const handleEssaySubmit = useCallback(() => {
        if (submittingRef.current) return;
        submittingRef.current = true;
        setSection('submitting');

        const allMcq = [...sectionA, ...sectionB.questions];
        router.post(route('onboarding.placement.store'), {
            answers,
            questions: JSON.stringify(allMcq),
            essay_text: essayText,
            essay_time_used: 300 - essayRemaining,
        });
    }, [answers, essayText, essayRemaining, sectionA, sectionB]);

    const handleEssayExpire = useCallback(() => {
        handleEssaySubmit();
    }, [handleEssaySubmit]);

    const wordCount = essayText.trim() ? essayText.trim().split(/\s+/).length : 0;

    // ─── Render ───────────────────────────────────────────────────────────────

    if (section === 'submitting') {
        return (
            <OnboardingLayout title="Test de placement" step={4}>
                <div className="flex flex-col items-center justify-center py-20 text-center">
                    <img src="/icons/loader.png" alt="" width={40} height={40} className="animate-spin mb-4"
                        style={{ }} />
                    <p className="text-lg font-black" style={{ color: OXFORD }}>Analyse de ton niveau…</p>
                    <p className="mt-1 text-sm text-muted-foreground">L'IA analyse tes réponses de grammaire et de lecture</p>
                </div>
            </OnboardingLayout>
        );
    }

    if (section === 'transition_B') {
        return (
            <OnboardingLayout title="Test de placement" step={4}>
                <SectionTransition label="Section A" onDone={() => { setSection('B'); setCurrentQ(0); setTimerKey((k) => k + 1); }} />
            </OnboardingLayout>
        );
    }

    if (section === 'transition_C') {
        return (
            <OnboardingLayout title="Test de placement" step={4}>
                <SectionTransition label="Section B" onDone={() => setSection('C')} />
            </OnboardingLayout>
        );
    }

    // Section C — Essay
    if (section === 'C') {
        return (
            <OnboardingLayout title="Test de placement" step={4}>
                <div className="space-y-4 sm:space-y-6">
                    {/* Section progress */}
                    <div className="flex items-center gap-2">
                        <SectionBar label="Section A" state="done" />
                        <SectionBar label="Section B" state="done" />
                        <SectionBar label="Section C" state="active" />
                    </div>

                    {/* Header */}
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="text-xs font-black uppercase tracking-widest" style={{ color: SKY }}>
                                Section C — Expression Écrite
                            </p>
                            <h2 className="mt-1 text-xl font-black" style={{ color: OXFORD }}>
                                Rédige ta réponse
                            </h2>
                            <p className="text-xs text-muted-foreground mt-0.5">Prends ton temps, la longueur de ta réponse confirme ton niveau</p>
                        </div>
                        <EssayTimer
                            totalSeconds={300}
                            onExpire={handleEssayExpire}
                            onTick={setEssayRemaining}
                        />
                    </div>

                    {/* Prompt */}
                    <div
                        className="rounded-2xl border-2 p-5"
                        style={{ borderColor: `${GOLD}60`, background: `${GOLD}08` }}
                    >
                        <p className="text-xs font-black uppercase tracking-wider mb-2" style={{ color: GOLD }}>
                            Sujet
                        </p>
                        <p className="text-sm leading-relaxed font-medium" style={{ color: OXFORD }}>
                            {sectionC.prompt}
                        </p>
                    </div>

                    {/* Textarea */}
                    <div className="space-y-2">
                        <textarea
                            value={essayText}
                            onChange={(e) => setEssayText(e.target.value)}
                            placeholder="Rédige ta réponse ici…"
                            rows={8}
                            className="w-full resize-none rounded-2xl border-2 border-border bg-card p-4 text-sm leading-relaxed outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20"
                            style={{ minHeight: 200 }}
                        />
                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                            <span>
                                <span className="font-bold" style={{ color: wordCount >= 100 ? '#22c55e' : wordCount >= 50 ? GOLD : OXFORD }}>
                                    {wordCount}
                                </span>{' '}
                                mot{wordCount !== 1 ? 's' : ''}
                                <span className="ml-2 opacity-60">· Visez 150+ mots pour un meilleur score</span>
                            </span>
                        </div>
                    </div>

                    {/* Submit button */}
                    <div className="flex justify-end">
                        <button
                            onClick={handleEssaySubmit}
                            disabled={submittingRef.current}
                            className="flex items-center gap-2 rounded-2xl px-6 py-3 text-sm font-black text-white transition-all disabled:opacity-50"
                            style={{
                                background: `linear-gradient(135deg, ${SKY}, #3478c8)`,
                                boxShadow: '0 4px 0 0 #2a6fc0',
                            }}
                        >
                            <img src="/icons/check.png" alt="" width={14} height={14} style={{ filter: 'brightness(0) invert(1)', objectFit: 'contain' }} />
                            Soumettre mon test
                        </button>
                    </div>
                </div>
            </OnboardingLayout>
        );
    }

    // Sections A & B — MCQ
    const isSectionA = section === 'A';
    const qTimer = isSectionA ? 20 : 45;
    const sectionLabel = isSectionA ? 'Section A — Grammaire & Vocabulaire' : 'Section B — Compréhension Écrite';
    const totalInSection = currentQuestions.length;
    const progress = ((currentQ) / totalInSection) * 100;

    return (
        <OnboardingLayout title="Test de placement" step={4}>
            <div className="space-y-4 sm:space-y-6">
                {/* Section mini-progress */}
                <div className="flex items-center gap-2">
                    <SectionBar label="Section A" state={sectionState('A')} />
                    <SectionBar label="Section B" state={sectionState('B')} />
                    <SectionBar label="Section C" state="pending" />
                </div>

                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-xs font-black uppercase tracking-widest" style={{ color: SKY }}>
                            {sectionLabel}
                        </p>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            Question {currentQ + 1} / {totalInSection}
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <span className={`rounded-full border px-2.5 py-0.5 text-[10px] font-black ${levelColors[question?.level ?? 'B1'] ?? ''}`}>
                            {question?.level}
                        </span>
                        <QuestionTimer
                            key={timerKey}
                            seconds={qTimer}
                            onExpire={handleQExpire}
                            expired={qExpired}
                        />
                    </div>
                </div>

                {/* Progress bar */}
                <div className="h-1.5 overflow-hidden rounded-full bg-muted">
                    <div
                        className="h-1.5 rounded-full transition-all duration-500"
                        style={{ width: `${progress}%`, background: SKY }}
                    />
                </div>

                {/* Reading passage for Section B */}
                {section === 'B' && (
                    <div className="rounded-2xl border bg-muted/30 p-5 text-sm leading-relaxed max-h-48 overflow-y-auto">
                        <p className="text-[10px] font-black uppercase tracking-wider mb-2" style={{ color: OXFORD, opacity: 0.5 }}>Texte à lire</p>
                        <p>{sectionB.passage}</p>
                    </div>
                )}

                {/* Question card */}
                {question && (
                    <div
                        style={{
                            opacity: visible ? 1 : 0,
                            transform: visible ? 'translateY(0)' : 'translateY(10px)',
                            transition: 'opacity 0.18s ease, transform 0.18s ease',
                        }}
                    >
                        <MCQBlock
                            question={question}
                            selectedAnswer={answers[question.id]}
                            onAnswer={(qId, letter) => setAnswers((prev) => ({ ...prev, [qId]: letter }))}
                            disabled={qExpired}
                        />
                    </div>
                )}

                {/* Navigation */}
                <div className="flex items-center justify-between pt-2">
                    <button
                        disabled={currentQ === 0 && section === 'A'}
                        onClick={() => {
                            if (currentQ > 0) {
                                setVisible(false);
                                setTimeout(() => { setCurrentQ((q) => q - 1); setTimerKey((k) => k + 1); setVisible(true); }, 180);
                            }
                        }}
                        className="rounded-xl border px-4 py-2.5 text-sm font-bold text-muted-foreground transition-all hover:border-primary/40 disabled:opacity-30"
                    >
                        ← Retour
                    </button>

                    <button
                        disabled={!answers[question?.id] && !qExpired}
                        onClick={() => advanceQuestion()}
                        className="flex items-center gap-2 rounded-xl px-6 py-2.5 text-sm font-black text-white transition-all disabled:opacity-40"
                        style={{
                            background: `linear-gradient(135deg, ${SKY}, #3478c8)`,
                            boxShadow: '0 3px 0 0 #2a6fc0',
                        }}
                    >
                        {isLastQ
                            ? isSectionA ? 'Section B →' : 'Section C →'
                            : 'Suivant →'}
                    </button>
                </div>
            </div>
        </OnboardingLayout>
    );
}
