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
                        <img src="/icons/clock.png" alt="" width={48} height={48} className="mx-auto mb-3" style={{ }} />
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

            <div className="mx-auto max-w-2xl px-4 py-5">
                {/* Header */}
                <div className="mb-4 flex items-center gap-2">
                    {exam.language?.flag && <FlagImg flag={exam.language.flag} size={24} />}
                    <h1 className="text-xl font-black tracking-tight text-foreground">
                        {exam.name}
                    </h1>
                </div>

                {/* Exam mode banner — compact */}
                <div className="duo-card mb-4 flex items-center justify-between gap-3 p-3">
                    <div className="flex items-center gap-2.5 min-w-0">
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" style={{ background: OXFORD }}>
                            <img src="/icons/clock.png" alt="" width={16} height={16} style={{ filter: 'brightness(0) invert(1)' }} />
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-black text-foreground truncate">{t('practice.exam_mode_title')}</p>
                            <p className="text-[11px] text-muted-foreground">{t('practice.exam_mode_desc', { minutes: totalExamMinutes })}</p>
                        </div>
                    </div>
                    <Link
                        href={route('practice.simulate', exam.id)}
                        className="duo-press shrink-0 rounded-xl px-4 py-2 text-xs font-black text-white"
                        style={{ background: SKY, boxShadow: `0 3px 0 0 #2a6fc0` }}
                    >
                        {t('practice.exam_mode_start')}
                    </Link>
                </div>

                {/* Section Cards */}
                <h2 className="mb-3 text-xs font-black uppercase tracking-widest" style={{ color: OXFORD }}>
                    Par compétence
                </h2>
                <div className="grid gap-3 sm:grid-cols-2">
                    {exam.sections.map((section, i) => {
                        const iconName = skillIcons[section.skill_type] ?? 'book';
                        const theme = skillThemes[section.skill_type] ?? skillThemes.reading;
                        const attempts = sectionProgress[section.id] ?? 0;

                        return (
                            <Link
                                key={section.id}
                                href={route('practice.section', [exam.id, section.id])}
                                className="duo-card flex items-center gap-3 p-3"
                                style={{
                                    opacity: mounted ? 1 : 0,
                                    transform: mounted ? 'translateY(0)' : 'translateY(12px)',
                                    transition: `all 0.4s ease ${i * 100}ms`,
                                }}
                            >
                                {/* Icon */}
                                <div
                                    className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                                    style={{
                                        background: theme.bg,
                                        boxShadow: `0 4px 0 0 ${theme.shadow}`,
                                    }}
                                >
                                    <Icon name={iconName} size={20} style={{ filter: 'brightness(0) invert(1)' }} />
                                </div>

                                {/* Content — name + meta on two lines (the skill badge was redundant with the name) */}
                                <div className="flex-1 min-w-0">
                                    <h3 className="text-sm font-black text-foreground truncate">
                                        {section.name}
                                    </h3>
                                    <p className="text-[11px] font-bold text-muted-foreground">
                                        {section.time_limit} min · {t('practice.section_exercise_types', { count: section.exercise_types?.length ?? 0 })}
                                        {attempts > 0 && <> · {t('practice.section_attempts_one', { count: attempts })}</>}
                                    </p>
                                </div>

                                {/* Chevron */}
                                <Icon name="chevron-right" size={16} style={{ opacity: 0.25 }} />
                            </Link>
                        );
                    })}
                </div>

                {/* Personalized Practice — compact */}
                <div className="mt-6">
                    <h2 className="text-xs font-black uppercase tracking-widest text-muted-foreground mb-3">
                        {t('practice.personalized_title', 'Votre Espace Personnel')}
                    </h2>
                    <div className="grid gap-3">
                        {/* Mistakes */}
                        <Link
                            href={route('errors.practice')}
                            className="duo-card flex items-center gap-3 p-3 hover:bg-red-50/30 transition-colors"
                        >
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-red-100 text-red-600 shadow-[0_4px_0_0_#fecaca]">
                                <Icon name="alert-circle" size={20} />
                            </div>
                            <div className="flex-1 min-w-0">
                                <h3 className="text-sm font-black text-red-900 dark:text-red-300">
                                    {t('practice.mistakes_review_title', 'Centre de Récupération')}
                                </h3>
                                <p className="text-[11px] font-bold text-red-600/70 truncate">
                                    {t('practice.mistakes_review_desc', 'Révise tes erreurs passées.')}
                                </p>
                            </div>
                            <Icon name="chevron-right" size={16} style={{ opacity: 0.3 }} />
                        </Link>

                        {/* Dictionary */}
                        <Link
                            href={route('dictionary.index')}
                            className="duo-card flex items-center gap-3 p-3 hover:bg-indigo-50/30 transition-colors"
                        >
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600 shadow-[0_4px_0_0_#c7d2fe]">
                                <Icon name="book" size={20} />
                            </div>
                            <div className="flex-1 min-w-0">
                                <h3 className="text-sm font-black text-indigo-900 dark:text-indigo-300">
                                    {t('practice.dictionary_title', 'Mon Dictionnaire')}
                                </h3>
                                <p className="text-[11px] font-bold text-indigo-600/70 truncate">
                                    {t('practice.dictionary_desc', 'Mots sauvegardés + révision (SRS)')}
                                </p>
                            </div>
                            <Icon name="chevron-right" size={16} style={{ opacity: 0.3 }} />
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
