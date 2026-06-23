import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
function Icon({ name, size = 20, style }: { name: string; size?: number; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} style={{ objectFit: 'contain', ...style }} />;
}
import { useEffect, useState } from 'react';
import type { UserProfile, ExerciseAttempt } from '@/types';

interface SkillStat {
    count: number;
    avg_accuracy: number;
    total_xp: number;
}

interface Props {
    profile: UserProfile | null;
    skillStats: Record<string, SkillStat>;
    recentAttempts: ExerciseAttempt[];
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

const skillThemes: Record<string, { bg: string; shadow: string; text: string }> = {
    reading: { bg: `linear-gradient(135deg, ${SKY}, #3478c8)`, shadow: '#2a6fc0', text: SKY },
    listening: { bg: `linear-gradient(135deg, #48b77b, #3a9d68)`, shadow: '#2d7d52', text: '#48b77b' },
    writing: { bg: `linear-gradient(135deg, ${OXFORD}, #2a3f6a)`, shadow: '#0e1a2e', text: OXFORD },
    speaking: { bg: `linear-gradient(135deg, ${GOLD}, #e08c10)`, shadow: '#c07a0e', text: GOLD },
};

const skillLabels: Record<string, string> = {
    reading: 'Lecture',
    listening: 'Écoute',
    writing: 'Écriture',
    speaking: 'Expression',
};

const skillIcons: Record<string, string> = {
    reading: 'book',
    listening: 'listening',
    writing: 'writing',
    speaking: 'speaking',
};

export default function ResultsIndex({ profile, skillStats, recentAttempts }: Props) {
    const skills = ['reading', 'listening', 'writing', 'speaking'];
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);

    const stagger = (i: number) => ({
        opacity: mounted ? 1 : 0,
        transform: mounted ? 'translateY(0)' : 'translateY(16px)',
        transition: `all 0.5s cubic-bezier(0.22, 1, 0.36, 1) ${i * 100}ms`,
    });

    return (
        <AppLayout>
            <Head title="Résultats" />
            <div className="mx-auto max-w-2xl px-4 py-5">
                {/* Sub-header (the page title is already shown in the top bar) */}
                <div className="mb-4 flex items-center justify-between gap-3" style={stagger(0)}>
                    <p className="text-sm font-bold text-muted-foreground">Ta progression par compétence</p>
                    <Link href="/results/attempts" className="duo-btn-secondary text-xs shrink-0">
                        Historique
                        <Icon name="arrow-right" size={14} />
                    </Link>
                </div>

                {/* Skill breakdown cards */}
                <div className="mb-5 grid gap-3 grid-cols-2 lg:grid-cols-4">
                    {skills.map((skill, i) => {
                        const stat = skillStats[skill];
                        const skillIconName = skillIcons[skill] ?? 'book';
                        const theme = skillThemes[skill];
                        return (
                            <div
                                key={skill}
                                className="duo-card overflow-hidden p-0"
                                style={stagger(i + 1)}
                            >
                                <div className="p-4 pb-3">
                                    <div className="mb-3 flex items-center justify-between">
                                        <span className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">
                                            {skillLabels[skill]}
                                        </span>
                                        <div
                                            className="flex h-8 w-8 items-center justify-center rounded-xl"
                                            style={{
                                                background: theme.bg,
                                                boxShadow: `0 3px 0 0 ${theme.shadow}`,
                                            }}
                                        >
                                            <CustomIcon name={skillIcons[skill]} className="h-4 w-4" style={{ filter: 'brightness(0) saturate(100%) invert(100%)' }} />
                                        </div>
                                    </div>
                                    {stat ? (
                                        <>
                                            <div className="text-2xl font-black text-foreground">
                                                {Number(stat.avg_accuracy).toFixed(0)}%
                                            </div>
                                            <p className="text-[10px] font-bold text-muted-foreground">
                                                {stat.count} exercices · {stat.total_xp} XP
                                            </p>
                                        </>
                                    ) : (
                                        <p className="text-xs font-bold text-muted-foreground">Pas encore de données</p>
                                    )}
                                </div>
                                {/* Progress bar */}
                                {stat && (
                                    <div className="px-4 pb-4">
                                        <div className="duo-progress" style={{ height: '0.5rem' }}>
                                            <div
                                                className="h-full rounded-full transition-all duration-1000 ease-out"
                                                style={{
                                                    width: mounted ? `${Math.min(Number(stat.avg_accuracy), 100)}%` : '0%',
                                                    background: theme.bg,
                                                    boxShadow: 'inset 0 -2px 0 rgba(0,0,0,0.15), inset 0 1px 0 rgba(255,255,255,0.3)',
                                                    transitionDelay: `${(i + 1) * 200}ms`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>

                {/* Recent attempts */}
                <div className="duo-card overflow-hidden p-0" style={stagger(5)}>
                    <div className="border-b-2 border-border px-5 py-3">
                        <p className="text-xs font-black uppercase tracking-widest text-muted-foreground">
                            Tentatives récentes
                        </p>
                    </div>
                    {recentAttempts.length === 0 ? (
                        <p className="py-10 text-center text-sm font-bold text-muted-foreground">
                            Aucune tentative. Commencez à pratiquer pour voir vos résultats ici.
                        </p>
                    ) : (
                        <div className="divide-y divide-border">
                            {recentAttempts.map((attempt, i) => {
                                const acc = Number(attempt.accuracy_percent ?? 0);
                                const isGood = acc >= 80;
                                return (
                                    <div
                                        key={attempt.id}
                                        className="flex items-center justify-between px-5 py-3"
                                        style={{
                                            opacity: mounted ? 1 : 0,
                                            transform: mounted ? 'translateX(0)' : 'translateX(-8px)',
                                            transition: `all 0.4s ease ${1200 + i * 60}ms`,
                                        }}
                                    >
                                        <div>
                                            <p className="text-sm font-bold text-foreground">
                                                {attempt.exercise?.exercise_type?.name ?? 'Exercice'}
                                            </p>
                                            <p className="text-[10px] font-medium text-muted-foreground">
                                                {attempt.exercise?.exam?.name} · {new Date(attempt.created_at).toLocaleDateString('fr-FR')}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <span
                                                className="rounded-lg px-2.5 py-1 text-xs font-black text-white"
                                                style={{
                                                    background: isGood
                                                        ? `linear-gradient(135deg, ${SKY}, #3478c8)`
                                                        : 'rgba(26,43,72,0.12)',
                                                    color: isGood ? '#fff' : OXFORD,
                                                    boxShadow: isGood ? '0 2px 0 0 #2a6fc0' : '0 2px 0 0 #d1d5db',
                                                }}
                                            >
                                                {acc.toFixed(0)}%
                                            </span>
                                            <span className="flex items-center gap-0.5 text-xs font-black" style={{ color: GOLD }}>
                                                <CustomIcon name="trophy" className="h-3 w-3" style={{ filter: 'brightness(0) saturate(100%) invert(84%) sepia(40%) saturate(1734%) hue-rotate(353deg) brightness(94%) contrast(86%)' }} />
                                                +{attempt.xp_earned}
                                            </span>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
