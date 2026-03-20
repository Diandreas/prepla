import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Zap, ChevronRight, Sparkles } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ExamRecord, ExamSection, ExerciseRecord } from '@/types';

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
                        <span className="text-2xl">{exam.language?.flag}</span>
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
                        <Sparkles size={40} style={{ color: SKY }} className="mb-3" />
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
                                                        <Zap size={12} className="fill-current" style={{ color: GOLD }} />
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
                                                    <ChevronRight size={14} style={{ color: 'rgba(26,43,72,0.25)' }} />
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
