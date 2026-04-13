import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import * as Flags from 'country-flag-icons/react/3x2';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import type { ExamRecord, ExamSection } from '@/types';

function Icon({ name, size = 20, style }: { name: string; size?: number; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} style={{ objectFit: 'contain', ...style }} />;
}

function flagEmojiToCode(flag: string): string {
    const points = [...flag].map(c => c.codePointAt(0)! - 0x1F1E6);
    if (points.length === 2 && points[0] >= 0 && points[0] <= 25) {
        return String.fromCharCode(65 + points[0], 65 + points[1]);
    }
    return '';
}

function FlagImg({ flag, size = 28 }: { flag: string; size?: number }) {
    const code = flagEmojiToCode(flag);
    const FlagComponent = code ? (Flags as Record<string, React.ComponentType<{ style?: React.CSSProperties }>>)[code] : null;
    if (FlagComponent) return <FlagComponent style={{ width: size, borderRadius: 3 }} />;
    return <span style={{ fontSize: '1.5rem' }}>{flag}</span>;
}

interface Props {
    exam: ExamRecord & { sections: ExamSection[] };
    sectionProgress: Record<number, number>;
}

const OXFORD = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';

const skillIcons: Record<string, string> = {
    reading: 'book',
    listening: 'headphones',
    writing: 'message-square',
    speaking: 'mic',
};

const skillThemes: Record<string, { bg: string; shadow: string }> = {
    reading: { bg: `linear-gradient(135deg, ${SKY}, #3478c8)`, shadow: '#2a6fc0' },
    listening: { bg: `linear-gradient(135deg, #48b77b, #3a9d68)`, shadow: '#2d7d52' },
    writing: { bg: `linear-gradient(135deg, ${OXFORD}, #2a3f6a)`, shadow: '#0e1a2e' },
    speaking: { bg: `linear-gradient(135deg, ${GOLD}, #e08c10)`, shadow: '#c07a0e' },
};

// Global exam session timer displayed as floating banner
function ExamBanner({ totalMinutes, onExpire }: { totalMinutes: number; onExpire: () => void }) {
    const { t } = useTranslation();
    const total = totalMinutes * 60;
    const [remaining, setRemaining] = useState(total);
    const calledRef = useRef(false);

    useEffect(() => {
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
    }, [onExpire]);

    const mins = Math.floor(remaining / 60);
    const secs = remaining % 60;
    const ratio = remaining / total;
    const isCritical = ratio <= 0.1;
    const isWarning = ratio <= 0.25 && !isCritical;

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
            <span className="text-xs font-bold text-white/60">{t('practice.exam_mode_badge')}</span>
        </div>
    );
}

export default function ExamDashboard({ exam, sectionProgress }: Props) {
    const { t } = useTranslation();
    const [mounted, setMounted] = useState(false);
    const [examMode, setExamMode] = useState(false);
    const [examExpired, setExamExpired] = useState(false);

    // Total exam time = sum of all section time limits
    const totalExamMinutes = exam.sections.reduce((acc, s) => acc + (s.time_limit ?? 0), 0) || 180;

    useEffect(() => setMounted(true), []);

    const handleExamExpire = () => {
        setExamExpired(true);
        setExamMode(false);
    };

    return (
        <AppLayout>
            <Head title={`${exam.name} - Pratiquer`} />

            {examMode && <ExamBanner totalMinutes={totalExamMinutes} onExpire={handleExamExpire} />}

            {examExpired && (
                <div className="fixed inset-0 z-50 flex flex-col items-center justify-center gap-4 bg-black/70 backdrop-blur-sm">
                    <div className="rounded-3xl bg-white p-8 text-center shadow-2xl max-w-sm mx-4">
                        <img src="/icons/clock.png" alt="" width={48} height={48} className="mx-auto mb-3" style={{ filter: 'brightness(0) saturate(100%) invert(30%) sepia(80%) saturate(600%) hue-rotate(330deg)' }} />
                        <h2 className="text-xl font-black" style={{ color: OXFORD }}>{t('practice.exam_expired_title')}</h2>
                        <p className="mt-2 text-sm text-muted-foreground">{t('practice.exam_expired_desc')}</p>
                        <button
                            className="mt-5 w-full rounded-xl py-3 text-sm font-black text-white"
                            style={{ background: OXFORD }}
                            onClick={() => setExamExpired(false)}
                        >
                            {t('practice.exam_expired_cta')}
                        </button>
                    </div>
                </div>
            )}

            <div className="mx-auto max-w-2xl px-4 py-8">
                {/* Header */}
                <div className="mb-6">
                    <div className="flex items-center gap-2">
                        {exam.language?.flag && <FlagImg flag={exam.language.flag} size={28} />}
                        <h1 className="text-2xl font-black tracking-tight" style={{ color: OXFORD }}>
                            {exam.name}
                        </h1>
                    </div>
                    <p className="mt-1 text-sm font-bold text-muted-foreground">
                        {t('practice.all_sections', { name: exam.name })}
                    </p>
                </div>

                {/* Exam mode banner */}
                <div
                    className="duo-card mb-6 flex items-center justify-between gap-4 p-4"
                    style={{ background: examMode ? 'rgba(26,43,72,0.04)' : undefined }}
                >
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl" style={{ background: OXFORD }}>
                            <img src="/icons/clock.png" alt="" width={18} height={18} style={{ filter: 'brightness(0) invert(1)' }} />
                        </div>
                        <div>
                            <p className="text-sm font-black" style={{ color: OXFORD }}>{t('practice.exam_mode_title')}</p>
                            <p className="text-xs text-muted-foreground">{t('practice.exam_mode_desc', { minutes: totalExamMinutes })}</p>
                        </div>
                    </div>
                    <Link
                        href={route('practice.simulate', exam.id)}
                        className="rounded-xl px-4 py-2 text-xs font-black text-white transition-all"
                        style={{
                            background: SKY,
                            boxShadow: `0 3px 0 0 #2a6fc0`,
                        }}
                    >
                        {t('practice.exam_mode_start')}
                    </Link>
                </div>

                {/* Section Cards */}
                <div className="grid gap-4 sm:grid-cols-2">
                    {exam.sections.map((section, i) => {
                        const iconName = skillIcons[section.skill_type] ?? 'book';
                        const theme = skillThemes[section.skill_type] ?? skillThemes.reading;
                        const attempts = sectionProgress[section.id] ?? 0;

                        return (
                            <Link
                                key={section.id}
                                href={route('practice.section', [exam.id, section.id])}
                                className="duo-card flex items-center gap-4 p-5"
                                style={{
                                    opacity: mounted ? 1 : 0,
                                    transform: mounted ? 'translateY(0)' : 'translateY(12px)',
                                    transition: `all 0.4s ease ${i * 100}ms`,
                                }}
                            >
                                {/* Icon */}
                                <div
                                    className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl"
                                    style={{
                                        background: theme.bg,
                                        boxShadow: `0 4px 0 0 ${theme.shadow}`,
                                    }}
                                >
                                    <Icon name={iconName} size={20} style={{ filter: 'brightness(0) invert(1)' }} />
                                </div>

                                {/* Content */}
                                <div className="flex-1 min-w-0">
                                    <h3 className="text-sm font-black" style={{ color: OXFORD }}>
                                        {section.name}
                                    </h3>
                                    <p className="text-[10px] font-bold text-muted-foreground">
                                        {section.time_limit} min · {t('practice.section_exercise_types', { count: section.exercise_types?.length ?? 0 })}
                                    </p>
                                    <div className="mt-2 flex items-center justify-between">
                                        <span className="text-[10px] font-bold text-muted-foreground">
                                            {t('practice.section_attempts_one', { count: attempts })}
                                        </span>
                                        <span
                                            className="rounded-md px-2 py-0.5 text-[10px] font-black uppercase tracking-wider"
                                            style={{
                                                background: 'rgba(26,43,72,0.06)',
                                                color: OXFORD,
                                            }}
                                        >
                                            {section.skill_type}
                                        </span>
                                    </div>
                                </div>

                                {/* Chevron */}
                                <Icon name="chevron-right" size={16} style={{ opacity: 0.25 }} />
                            </Link>
                        );
                    })}
                </div>

                {/* Personalized Practice */}
                <div className="mt-10 mb-6">
                    <h2 className="text-lg font-black tracking-tight mb-4" style={{ color: OXFORD }}>
                        {t('practice.personalized_title', 'Votre Espace Personnel')}
                    </h2>
                    <div className="grid gap-4">
                        {/* Mistakes Card */}
                        <Link
                            href={route('errors.practice')}
                            className="duo-card flex items-center gap-4 p-5 border-red-100 hover:bg-red-50/30 transition-colors"
                        >
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-red-100 text-red-600 shadow-[0_4px_0_0_#fecaca]">
                                <Icon name="alert-circle" size={24} />
                            </div>
                            <div className="flex-1">
                                <h3 className="text-sm font-black text-red-900">
                                    {t('practice.mistakes_review_title', 'Centre de Récupération')}
                                </h3>
                                <p className="text-xs font-bold text-red-600/70">
                                    {t('practice.mistakes_review_desc', 'Révisez vos erreurs passées pour ne plus les refaire.')}
                                </p>
                            </div>
                            <Icon name="chevron-right" size={16} style={{ opacity: 0.3 }} />
                        </Link>

                        <div className="grid gap-4 sm:grid-cols-2">
                            {/* Vocabulary Card */}
                            <Link
                                href={route('vocabulary.index')}
                                className="duo-card flex flex-col gap-3 p-5 hover:bg-indigo-50/30 transition-colors"
                            >
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 shadow-[0_4px_0_0_#c7d2fe]">
                                    <Icon name="book" size={20} />
                                </div>
                                <div>
                                    <h3 className="text-sm font-black text-indigo-900">
                                        {t('practice.vocabulary_title', 'Mon Lexique')}
                                    </h3>
                                    <p className="text-[10px] font-bold text-indigo-600/70 uppercase tracking-wider">
                                        {t('practice.vocabulary_desc', 'Gérer mes mots sauvegardés')}
                                    </p>
                                </div>
                            </Link>

                            {/* Spaced Repetition Card */}
                            <Link
                                href={route('vocabulary.review')}
                                className="duo-card flex flex-col gap-3 p-5 hover:bg-emerald-50/30 transition-colors"
                            >
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 shadow-[0_4px_0_0_#a7f3d0]">
                                    <Icon name="sparkles" size={20} />
                                </div>
                                <div>
                                    <h3 className="text-sm font-black text-emerald-900">
                                        {t('practice.review_session_title', 'Session de Révision')}
                                    </h3>
                                    <p className="text-[10px] font-bold text-emerald-600/70 uppercase tracking-wider">
                                        {t('practice.review_session_desc', 'Pratique intelligente (SRS)')}
                                    </p>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
