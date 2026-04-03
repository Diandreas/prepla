import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
function Icon({ name, size = 20, style }: { name: string; size?: number; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} style={{ objectFit: 'contain', ...style }} />;
}
import { useEffect, useState } from 'react';
import type { ExamRecord } from '@/types';

interface Props {
    exams: ExamRecord[];
    targetExamId: number | null;
}

const OXFORD = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';

// Custom icon component using icons from /public/icons
function CustomIcon({ name, className, style }: { name: string; className?: string; style?: React.CSSProperties }) {
    return (
        <img
            src={`/icons/${name}.png`}
            alt={name}
            className={className || 'h-5 w-5'}
            style={{ objectFit: 'contain', ...style }}
        />
    );
}

export default function PracticeIndex({ exams, targetExamId }: Props) {
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);

    // Group exams by language
    const examsByLanguage = exams.reduce<Record<string, ExamRecord[]>>((acc, exam) => {
        const langName = exam.language?.name ?? 'Other';
        if (!acc[langName]) acc[langName] = [];
        acc[langName].push(exam);
        return acc;
    }, {});

    return (
        <AppLayout>
            <Head title="Pratiquer" />
            <div className="mx-auto max-w-2xl px-4 py-8">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-2xl font-black tracking-tight" style={{ color: OXFORD }}>
                        Pratiquer
                    </h1>
                    <p className="mt-1 text-sm font-bold text-muted-foreground">
                        Choisissez un examen pour commencer vos exercices
                    </p>
                </div>

                {/* Dictionary Section */}
                <div className="mb-10 p-6 rounded-[24px] bg-indigo-50 border-2 border-indigo-100 flex items-center justify-between gap-6 shadow-sm overflow-hidden relative group">
                    <div className="absolute -top-4 -right-4 opacity-10 rotate-12 transition-transform group-hover:scale-110 duration-500">
                        <CustomIcon name="book" className="h-28 w-28" />
                    </div>
                    <div className="flex-1 relative z-10">
                        <h2 className="text-xl font-black text-indigo-900 mb-1 flex items-center gap-2">
                             <CustomIcon name="book" className="h-6 w-6" />
                             Mon Dictionnaire
                        </h2>
                        <p className="text-sm font-bold text-indigo-700/60 mb-5 tracking-tight max-w-[280px]">Réviser vos mots appris et découvrir du vocabulaire académique ciblé.</p>
                        <div className="flex flex-wrap gap-2">
                             <Link href={route('dictionary.index')} className="px-5 py-2.5 bg-white text-indigo-600 font-black rounded-xl text-[11px] uppercase shadow-sm border border-indigo-200/50 hover:bg-slate-50 transition-all">
                                 Consulter
                             </Link>
                             <button 
                                onClick={() => router.post(route('dictionary.discover'))}
                                className="px-5 py-2.5 bg-indigo-600 text-white font-black rounded-xl text-[11px] uppercase shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all border-b-4 border-indigo-800 active:border-b-0 active:translate-y-[4px]"
                             >
                                 Découvrir (+5 XP)
                             </button>
                        </div>
                    </div>
                </div>

                {Object.entries(examsByLanguage).map(([langName, langExams], gIdx) => (
                    <div key={langName} className="mb-8">
                        <p
                            className="mb-3 text-xs font-black uppercase tracking-widest"
                            style={{ color: OXFORD, opacity: 0.5 }}
                        >
                            {langName}
                        </p>
                        <div className="grid gap-3 sm:grid-cols-2">
                            {langExams.map((exam, i) => {
                                const isTarget = exam.id === targetExamId;
                                return (
                                    <Link
                                        key={exam.id}
                                        href={route('practice.exam', exam.id)}
                                        className="duo-card flex items-center gap-4 p-4"
                                        style={{
                                            borderColor: isTarget ? SKY : undefined,
                                            background: isTarget ? 'rgba(74,144,226,0.04)' : '#ffffff',
                                            opacity: mounted ? 1 : 0,
                                            transform: mounted ? 'translateY(0)' : 'translateY(12px)',
                                            transition: `all 0.4s ease ${(gIdx * 3 + i) * 80}ms`,
                                        }}
                                    >
                                        {/* Icon */}
                                        <div
                                            className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl"
                                            style={{
                                                background: isTarget
                                                    ? `linear-gradient(135deg, ${SKY}, #3478c8)`
                                                    : 'rgba(26,43,72,0.06)',
                                                boxShadow: isTarget ? `0 4px 0 0 #2a6fc0` : '0 3px 0 0 #e5e7eb',
                                            }}
                                        >
                                            <CustomIcon
                                                name="courses"
                                                className="h-6 w-6"
                                                style={{ filter: isTarget ? 'brightness(0) saturate(100%) invert(100%)' : undefined }}
                                            />
                                        </div>

                                        {/* Content */}
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2">
                                                <h3 className="font-black text-sm truncate" style={{ color: OXFORD }}>
                                                    {exam.name}
                                                </h3>
                                                {isTarget && (
                                                    <span
                                                        className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-white"
                                                        style={{ background: GOLD, boxShadow: '0 2px 0 0 #d48b10' }}
                                                    >
                                                        <img src="/icons/star.png" alt="" width={10} height={10} style={{ objectFit: 'contain', filter: 'brightness(0) invert(1)' }} />
                                                        Mon examen
                                                    </span>
                                                )}
                                            </div>
                                            {exam.levels && (
                                                <div className="mt-1.5 flex flex-wrap gap-1">
                                                    {exam.levels.slice(0, 3).map((level) => (
                                                        <span
                                                            key={level}
                                                            className="rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                                                            style={{
                                                                background: 'rgba(26,43,72,0.06)',
                                                                color: OXFORD,
                                                            }}
                                                        >
                                                            {level}
                                                        </span>
                                                    ))}
                                                    {exam.levels.length > 3 && (
                                                        <span
                                                            className="rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                                                            style={{
                                                                background: 'rgba(26,43,72,0.06)',
                                                                color: OXFORD,
                                                            }}
                                                        >
                                                            +{exam.levels.length - 3}
                                                        </span>
                                                    )}
                                                </div>
                                            )}
                                        </div>

                                        {/* Chevron */}
                                        <Icon name="chevron-right" size={18} style={{ opacity: 0.25 }} />
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </div>
        </AppLayout>
    );
}
