import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { BarChart3, BookOpen, Clock, Headphones, MessageSquare, Mic, Target, Zap } from 'lucide-react';
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

const skillColors: Record<string, string> = {
    reading: 'text-blue-500',
    listening: 'text-green-500',
    writing: 'text-purple-500',
    speaking: 'text-orange-500',
};

export default function ResultsIndex({ profile, skillStats, recentAttempts }: Props) {
    const skills = ['reading', 'listening', 'writing', 'speaking'];

    return (
        <AppLayout>
            <Head title="Results" />
            <div className="space-y-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Results & Analytics</h1>
                        <p className="text-muted-foreground">Track your progress across all skills</p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/results/attempts">All Attempts</Link>
                    </Button>
                </div>

                {/* Skill breakdown cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {skills.map((skill) => {
                        const stat = skillStats[skill];
                        const Icon = skillIcons[skill] ?? BookOpen;
                        return (
                            <Card key={skill}>
                                <CardHeader className="flex flex-row items-center justify-between pb-2">
                                    <CardTitle className="text-sm font-medium capitalize">{skill}</CardTitle>
                                    <Icon className={`h-4 w-4 ${skillColors[skill]}`} />
                                </CardHeader>
                                <CardContent>
                                    {stat ? (
                                        <>
                                            <div className="text-2xl font-bold">{stat.avg_accuracy}%</div>
                                            <p className="text-xs text-muted-foreground">
                                                {stat.count} exercises · {stat.total_xp} XP
                                            </p>
                                        </>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">No data yet</p>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                {/* Overview stats */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="flex items-center gap-3 py-4">
                            <Target className="h-8 w-8 text-blue-500" />
                            <div>
                                <p className="text-2xl font-bold">{profile?.current_level ?? '—'}</p>
                                <p className="text-xs text-muted-foreground">Current Level</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 py-4">
                            <Zap className="h-8 w-8 text-yellow-500" />
                            <div>
                                <p className="text-2xl font-bold">{profile?.xp_total ?? 0}</p>
                                <p className="text-xs text-muted-foreground">Total XP</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 py-4">
                            <BarChart3 className="h-8 w-8 text-green-500" />
                            <div>
                                <p className="text-2xl font-bold">{recentAttempts.length}</p>
                                <p className="text-xs text-muted-foreground">Recent Exercises</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent attempts */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Recent Attempts</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recentAttempts.length === 0 ? (
                            <p className="py-4 text-center text-muted-foreground">
                                No attempts yet. Start practicing to see your results here.
                            </p>
                        ) : (
                            <div className="space-y-2">
                                {recentAttempts.map((attempt) => (
                                    <div
                                        key={attempt.id}
                                        className="flex items-center justify-between rounded-lg border border-border p-3"
                                    >
                                        <div>
                                            <p className="text-sm font-medium">
                                                {attempt.exercise?.exercise_type?.name ?? 'Exercise'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {attempt.exercise?.exam?.name} · {new Date(attempt.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge
                                                variant={(attempt.accuracy_percent ?? 0) >= 80 ? 'default' : 'secondary'}
                                            >
                                                {attempt.accuracy_percent?.toFixed(0) ?? 0}%
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
