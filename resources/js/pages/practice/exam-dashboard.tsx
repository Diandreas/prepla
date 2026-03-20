import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
function Icon({ name, size = 20, style }: { name: string; size?: number; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} style={{ objectFit: 'contain', ...style }} />;
}
import { useEffect, useState } from 'react';
import type { ExamRecord, ExamSection } from '@/types';

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

export default function ExamDashboard({ exam, sectionProgress }: Props) {
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);

    return (
        <AppLayout>
            <Head title={`${exam.name} - Pratiquer`} />
            <div className="mx-auto max-w-2xl px-4 py-8">
                {/* Header */}
                <div className="mb-8">
                    <div className="flex items-center gap-2">
                        <span className="text-2xl">{exam.language?.flag}</span>
                        <h1 className="text-2xl font-black tracking-tight" style={{ color: OXFORD }}>
                            {exam.name}
                        </h1>
                    </div>
                    <p className="mt-1 text-sm font-bold text-muted-foreground">
                        Pratiquer toutes les sections de l'examen {exam.name}
                    </p>
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
                                        {section.time_limit} min · {section.exercise_types?.length ?? 0} types d'exercices
                                    </p>
                                    <div className="mt-2 flex items-center justify-between">
                                        <span className="text-[10px] font-bold text-muted-foreground">
                                            {attempts} tentatives
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
            </div>
        </AppLayout>
    );
}
