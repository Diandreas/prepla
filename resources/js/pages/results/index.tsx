import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ArrowRight, BarChart3, BookOpen, Clock, Headphones, MessageSquare, Mic, Target, Zap } from 'lucide-react';
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

const skillIcons: Record<string, typeof BookOpen> = {
    reading: BookOpen,
    listening: Headphones,
    writing: MessageSquare,
    speaking: Mic,
};

const skillColors: Record<string, { text: string; bg: string; gradient: string }> = {
    reading: { text: 'text-blue-500', bg: 'bg-blue-500/10', gradient: 'from-blue-500 to-blue-600' },
    listening: { text: 'text-green-500', bg: 'bg-green-500/10', gradient: 'from-green-500 to-green-600' },
    writing: { text: 'text-purple-500', bg: 'bg-purple-500/10', gradient: 'from-purple-500 to-purple-600' },
    speaking: { text: 'text-orange-500', bg: 'bg-orange-500/10', gradient: 'from-orange-500 to-orange-600' },
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
        transition: 'all 0.5s cubic-bezier(0.22, 1, 0.36, 1)',
        transitionDelay: `${i * 100}ms`,
    });

    return (
        <AppLayout>
            <Head title="Résultats" />
            <div className="mx-auto max-w-4xl space-y-6 p-4 md:p-6">
                <div className="flex items-center justify-between" style={stagger(0)}>
                    <div>
                        <h1 className="text-2xl font-bold">Résultats & Analyses</h1>
                        <p className="text-muted-foreground">Suivez votre progression dans toutes les compétences</p>
                    </div>
                    <Button variant="outline" asChild className="gap-1">
                        <Link href="/results/attempts">
                            Historique
                            <ArrowRight className="h-3.5 w-3.5" />
                        </Link>
                    </Button>
                </div>

                {/* Skill breakdown cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {skills.map((skill, i) => {
                        const stat = skillStats[skill];
                        const Icon = skillIcons[skill] ?? BookOpen;
                        const colors = skillColors[skill];
                        return (
                            <Card
                                key={skill}
                                className="overflow-hidden transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md"
                                style={stagger(i + 1)}
                            >
                                <CardHeader className="flex flex-row items-center justify-between pb-2">
                                    <CardTitle className="text-sm font-medium">
                                        {skillLabels[skill]}
                                    </CardTitle>
                                    <div className={`flex h-8 w-8 items-center justify-center rounded-lg ${colors.bg}`}>
                                        <Icon className={`h-4 w-4 ${colors.text}`} />
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {stat ? (
                                        <>
                                            <div className="text-2xl font-bold">{Number(stat.avg_accuracy).toFixed(0)}%</div>
                                            <p className="text-xs text-muted-foreground">
                                                {stat.count} exercices · {stat.total_xp} XP
                                            </p>
                                            {/* Mini progress bar */}
                                            <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                                                <div
                                                    className={`h-full rounded-full bg-gradient-to-r ${colors.gradient} transition-all duration-1000 ease-out`}
                                                    style={{ width: mounted ? `${Math.min(Number(stat.avg_accuracy), 100)}%` : '0%', transitionDelay: `${(i + 1) * 200}ms` }}
                                                />
                                            </div>
                                        </>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">Pas encore de données</p>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                {/* Overview stats */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card className="overflow-hidden" style={stagger(5)}>
                        <CardContent className="flex items-center gap-3 py-4">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500/10">
                                <Target className="h-6 w-6 text-blue-500" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold">{profile?.current_level ?? '—'}</p>
                                <p className="text-xs text-muted-foreground">Niveau actuel</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="overflow-hidden" style={stagger(6)}>
                        <CardContent className="flex items-center gap-3 py-4">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-yellow-500/10">
                                <Zap className="h-6 w-6 text-yellow-500" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold">{profile?.xp_total ?? 0}</p>
                                <p className="text-xs text-muted-foreground">XP Total</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="overflow-hidden" style={stagger(7)}>
                        <CardContent className="flex items-center gap-3 py-4">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-green-500/10">
                                <BarChart3 className="h-6 w-6 text-green-500" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold">{recentAttempts.length}</p>
                                <p className="text-xs text-muted-foreground">Exercices récents</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent attempts */}
                <Card style={stagger(8)}>
                    <CardHeader>
                        <CardTitle className="text-base">Tentatives récentes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recentAttempts.length === 0 ? (
                            <p className="py-4 text-center text-muted-foreground">
                                Aucune tentative. Commencez à pratiquer pour voir vos résultats ici.
                            </p>
                        ) : (
                            <div className="space-y-2">
                                {recentAttempts.map((attempt, i) => (
                                    <div
                                        key={attempt.id}
                                        className="flex items-center justify-between rounded-xl border border-border p-3 transition-all duration-200 hover:border-primary/30 hover:shadow-sm"
                                        style={{
                                            opacity: mounted ? 1 : 0,
                                            transform: mounted ? 'translateX(0)' : 'translateX(-8px)',
                                            transition: 'all 0.4s ease',
                                            transitionDelay: `${1200 + i * 60}ms`,
                                        }}
                                    >
                                        <div>
                                            <p className="text-sm font-medium">
                                                {attempt.exercise?.exercise_type?.name ?? 'Exercice'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {attempt.exercise?.exam?.name} · {new Date(attempt.created_at).toLocaleDateString('fr-FR')}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge
                                                variant={Number(attempt.accuracy_percent ?? 0) >= 80 ? 'default' : 'secondary'}
                                            >
                                                {Number(attempt.accuracy_percent ?? 0).toFixed(0)}%
                                            </Badge>
                                            <span className="text-xs text-muted-foreground">
                                                +{attempt.xp_earned} XP
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
