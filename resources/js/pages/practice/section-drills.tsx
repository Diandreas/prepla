import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import * as Flags from 'country-flag-icons/react/3x2';
import { useEffect, useState } from 'react';
import type { ExamRecord, ExamSection, ExerciseRecord } from '@/types';

function Icon({ name, size = 20, style, className }: { name: string; size?: number; style?: React.CSSProperties; className?: string }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} style={{ objectFit: 'contain', ...style }} className={className} />;
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
    exam: ExamRecord;
    section: ExamSection;
    exercisesByDifficulty: Record<string, ExerciseRecord[]>;
}

const OXFORD = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';

const difficultyThemes: Record<string, { bg: string; shadow: string; color: string }> = {
    A1: { bg: '#48b77b', shadow: '#2d7d52', color: '#48b77b' },
    A2: { bg: '#3a9d68', shadow: '#2d7d52', color: '#3a9d68' },
    B1: { bg: SKY, shadow: '#2a6fc0', color: SKY },
    B2: { bg: '#3478c8', shadow: '#2a6fc0', color: '#3478c8' },
    C1: { bg: OXFORD, shadow: '#0e1a2e', color: OXFORD },
    C2: { bg: '#d4483b', shadow: '#a52f25', color: '#d4483b' },
};

export default function SectionDrills({ exam, section, exercisesByDifficulty }: Props) {
    const difficulties = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
    const hasExercises = Object.keys(exercisesByDifficulty).length > 0;
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);

    return (
        <AppLayout>
            <Head title={`${section.name} - ${exam.name}`} />
            <div className="mx-auto max-w-2xl px-4 py-8">
                {/* Header */}
                <div className="mb-8">
                    <div className="flex items-center gap-2">
                        {exam.language?.flag && <FlagImg flag={exam.language.flag} size={28} />}
                        <h1 className="text-2xl font-black tracking-tight" style={{ color: OXFORD }}>
                            {section.name}
                        </h1>
                    </div>
                    <p className="mt-1 text-sm font-bold text-muted-foreground">
                        {exam.name} · {section.skill_type}
                    </p>
                </div>

                {!hasExercises ? (
                    <div className="duo-card flex flex-col items-center p-10 text-center">
                        <Icon name="sparkles" size={40} style={{ color: SKY }} className="mb-3" />
                        <p className="text-lg font-black" style={{ color: OXFORD }}>
                            Aucun exercice pour l'instant
                        </p>
                        <p className="mt-2 text-sm font-bold text-muted-foreground">
                            Utilisez le générateur IA pour créer des exercices pour cette section
                        </p>
                        <Link
                            href="/ai-tools"
                            className="duo-btn-primary mt-5"
                        >
                            Accéder aux outils IA
                        </Link>
                    </div>
                ) : (
                    <div className="space-y-8">
                        {difficulties.map((difficulty, dIdx) => {
                            const exercises = exercisesByDifficulty[difficulty];
                            if (!exercises || exercises.length === 0) return null;
                            const theme = difficultyThemes[difficulty] ?? difficultyThemes.B1;
                            return (
                                <div key={difficulty}>
                                    {/* Difficulty header */}
                                    <div className="mb-3 flex items-center gap-2">
                                        <span
                                            className="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-black text-white"
                                            style={{
                                                background: theme.bg,
                                                boxShadow: `0 3px 0 0 ${theme.shadow}`,
                                            }}
                                        >
                                            {difficulty}
                                        </span>
                                        <span className="text-xs font-bold text-muted-foreground">
                                            {exercises.length} exercices
                                        </span>
                                    </div>
                                    {/* Exercise list */}
                                    <div className="space-y-2">
                                        {exercises.map((exercise, i) => (
                                            <Link
                                                key={exercise.id}
                                                href={route('exercise.show', exercise.id)}
                                                className="duo-card flex items-center justify-between p-4"
                                                style={{
                                                    opacity: mounted ? 1 : 0,
                                                    transform: mounted ? 'translateX(0)' : 'translateX(-10px)',
                                                    transition: `all 0.4s ease ${(dIdx * 3 + i) * 60}ms`,
                                                }}
                                            >
                                                <div>
                                                    <p className="text-sm font-bold" style={{ color: OXFORD }}>
                                                        {exercise.exercise_type?.name ?? 'Exercice'}
                                                    </p>
                                                    <div className="mt-1 flex items-center gap-1">
                                                        <Icon name="zap" size={12} style={{ color: GOLD }} />
                                                        <span className="text-[10px] font-black" style={{ color: GOLD }}>
                                                            {exercise.xp_reward} XP
                                                        </span>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <span
                                                        className="rounded-md px-2 py-0.5 text-[10px] font-bold"
                                                        style={{
                                                            background: 'rgba(26,43,72,0.06)',
                                                            color: OXFORD,
                                                        }}
                                                    >
                                                        {exercise.exercise_type?.component_key}
                                                    </span>
                                                    <Icon name="chevron-right" size={14} style={{ opacity: 0.25 }} />
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
