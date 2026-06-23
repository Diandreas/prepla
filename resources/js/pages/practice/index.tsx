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
            <div className="mx-auto max-w-2xl px-4 py-5">
                {/* Two compact action tiles: Dictionnaire + Test IA */}
                <div className="mb-5 grid grid-cols-2 gap-3">
                    <Link
                        href={route('dictionary.index')}
                        className="duo-press flex flex-col gap-2 rounded-2xl p-3.5 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900"
                    >
                        <CustomIcon name="book" className="h-6 w-6" />
                        <div>
                            <p className="text-sm font-black text-indigo-900 dark:text-indigo-200">Dictionnaire</p>
                            <p className="text-[10px] font-bold text-indigo-600/70">Réviser le vocabulaire</p>
                        </div>
                    </Link>
                    <Link
                        href={route('practice.simulate', targetExamId || 1)}
                        className="duo-press flex flex-col gap-2 rounded-2xl p-3.5 text-white"
                        style={{ background: `linear-gradient(135deg, #1A2B48 0%, #4A90E2 100%)`, boxShadow: '0 4px 0 0 #2a6fc0' }}
                    >
                        <Icon name="sparkles" size={24} style={{ filter: 'brightness(0) invert(1)' }} />
                        <div>
                            <p className="text-sm font-black">Test de niveau</p>
                            <p className="text-[10px] font-bold opacity-80">Évaluation IA</p>
                        </div>
                    </Link>
                </div>

                {Object.entries(examsByLanguage).map(([langName, langExams], gIdx) => (
                    <div key={langName} className="mb-5">
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
                                        className="duo-card flex items-center gap-3 p-3"
                                        style={{
                                            borderColor: isTarget ? SKY : undefined,
                                            background: isTarget ? 'rgba(74,144,226,0.06)' : undefined,
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
                                                <h3 className="font-black text-sm truncate text-foreground">
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
