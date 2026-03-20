import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { BookOpen, ChevronRight, Star } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ExamRecord } from '@/types';

interface Props {
    exams: ExamRecord[];
    targetExamId: number | null;
}

const OXFORD = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';

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
                                            <BookOpen size={20} color={isTarget ? 'white' : OXFORD} strokeWidth={2.5} />
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
                                                        <Star size={10} fill="white" />
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
                                        <ChevronRight size={18} style={{ color: 'rgba(26,43,72,0.25)' }} />
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
