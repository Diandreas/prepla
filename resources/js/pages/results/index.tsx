import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { ArrowRight, BookOpen, Headphones, MessageSquare, Mic, Target, Zap, BarChart3 } from 'lucide-react';
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

const skillIcons: Record<string, typeof BookOpen> = {
    reading: BookOpen,
    listening: Headphones,
    writing: MessageSquare,
    speaking: Mic,
};

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
            <div className="mx-auto max-w-2xl px-4 py-8">
                {/* Header */}
                <div className="mb-8 flex items-center justify-between" style={stagger(0)}>
                    <div>
                        <h1 className="text-2xl font-black tracking-tight" style={{ color: OXFORD }}>
                            Résultats & Analyses
                        </h1>
                        <p className="mt-1 text-sm font-bold text-muted-foreground">
                            Suivez votre progression dans toutes les compétences
                        </p>
                    </div>
                    <Link
                        href="/results/attempts"
                        className="duo-btn-secondary text-xs"
                    >
                        Historique
                        <ArrowRight size={14} />
                    </Link>
                </div>

                {/* Skill breakdown cards */}
                <div className="mb-6 grid gap-3 grid-cols-2 lg:grid-cols-4">
                    {skills.map((skill, i) => {
                        const stat = skillStats[skill];
                        const Icon = skillIcons[skill] ?? BookOpen;
                        const theme = skillThemes[skill];
                        return (
                            <div
                                key={skill}
                                className="duo-card overflow-hidden p-0"
                                style={stagger(i + 1)}
                            >
                                <div className="p-4 pb-3">
                                    <div className="mb-3 flex items-center justify-between">
                                        <span className="text-[10px] font-black uppercase tracking-widest" style={{ color: OXFORD, opacity: 0.5 }}>
                                            {skillLabels[skill]}
                                        </span>
                                        <div
                                            className="flex h-8 w-8 items-center justify-center rounded-xl"
                                            style={{
                                                background: theme.bg,
                                                boxShadow: `0 3px 0 0 ${theme.shadow}`,
                                            }}
                                        >
                                            <Icon size={14} color="white" strokeWidth={2.5} />
                                        </div>
                                    </div>
                                    {stat ? (
                                        <>
                                            <div className="text-2xl font-black" style={{ color: OXFORD }}>
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

                {/* Overview stats */}
                <div className="mb-6 grid gap-3 grid-cols-3">
                    {[
                        { label: 'Niveau actuel', value: profile?.current_level ?? '—', icon: Target, color: SKY, shadow: '#2a6fc0' },
                        { label: 'XP Total', value: profile?.xp_total ?? 0, icon: Zap, color: GOLD, shadow: '#c07a0e' },
                        { label: 'Exercices récents', value: recentAttempts.length, icon: BarChart3, color: '#48b77b', shadow: '#2d7d52' },
                    ].map((item, i) => (
                        <div
                            key={item.label}
                            className="duo-card flex flex-col items-center p-4"
                            style={stagger(i + 5)}
                        >
                            <div
                                className="mb-2 flex h-10 w-10 items-center justify-center rounded-2xl"
                                style={{
                                    background: `linear-gradient(135deg, ${item.color}, ${item.shadow})`,
                                    boxShadow: `0 3px 0 0 ${item.shadow}`,
                                }}
                            >
                                <item.icon size={18} color="white" strokeWidth={2.5} />
                            </div>
                            <p className="text-xl font-black" style={{ color: OXFORD }}>{item.value}</p>
                            <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">{item.label}</p>
                        </div>
                    ))}
                </div>

                {/* Recent attempts */}
                <div className="duo-card overflow-hidden p-0" style={stagger(8)}>
                    <div className="border-b-2 border-gray-100 px-5 py-3">
                        <p className="text-xs font-black uppercase tracking-widest" style={{ color: OXFORD, opacity: 0.5 }}>
                            Tentatives récentes
                        </p>
                    </div>
                    {recentAttempts.length === 0 ? (
                        <p className="py-10 text-center text-sm font-bold text-muted-foreground">
                            Aucune tentative. Commencez à pratiquer pour voir vos résultats ici.
                        </p>
                    ) : (
                        <div className="divide-y divide-gray-100">
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
                                            <p className="text-sm font-bold" style={{ color: OXFORD }}>
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
                                                <Zap size={12} className="fill-current" />
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
