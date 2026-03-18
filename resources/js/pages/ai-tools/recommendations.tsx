import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Lightbulb, Target, TrendingUp } from 'lucide-react';
import type { UserProfile, ExerciseAttempt } from '@/types';

interface Props {
    profile: UserProfile | null;
    recentAttempts: ExerciseAttempt[];
}

export default function Recommendations({ profile, recentAttempts }: Props) {
    const avgAccuracy = recentAttempts.length > 0
        ? recentAttempts.reduce((sum, a) => sum + (a.accuracy_percent ?? 0), 0) / recentAttempts.length
        : 0;

    return (
        <AppLayout>
            <Head title="AI Recommendations" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold">Study Recommendations</h1>
                    <p className="text-muted-foreground">
                        Personalized suggestions based on your performance
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="flex flex-col items-center py-6">
                            <Target className="h-8 w-8 text-blue-500" />
                            <p className="mt-2 text-2xl font-bold">{profile?.current_level ?? '—'}</p>
                            <p className="text-sm text-muted-foreground">Current Level</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex flex-col items-center py-6">
                            <TrendingUp className="h-8 w-8 text-green-500" />
                            <p className="mt-2 text-2xl font-bold">{avgAccuracy.toFixed(0)}%</p>
                            <p className="text-sm text-muted-foreground">Avg Accuracy (last 10)</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex flex-col items-center py-6">
                            <Lightbulb className="h-8 w-8 text-yellow-500" />
                            <p className="mt-2 text-2xl font-bold">{recentAttempts.length}</p>
                            <p className="text-sm text-muted-foreground">Recent Exercises</p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Suggested Focus Areas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recentAttempts.length === 0 ? (
                            <p className="text-muted-foreground">
                                Complete some exercises first to get personalized recommendations.
                            </p>
                        ) : (
                            <div className="space-y-3">
                                <div className="rounded-lg border border-border p-4">
                                    <div className="flex items-center gap-2">
                                        <Badge>Priority</Badge>
                                        <span className="font-medium">Practice more exercises</span>
                                    </div>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Keep practicing to build your skill profile. The AI will provide more specific recommendations as you complete more exercises.
                                    </p>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {recentAttempts.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Performance</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {recentAttempts.map((attempt) => (
                                    <div
                                        key={attempt.id}
                                        className="flex items-center justify-between rounded-lg border border-border p-3"
                                    >
                                        <span className="text-sm font-medium">
                                            {attempt.exercise?.exercise_type?.name ?? 'Exercise'}
                                        </span>
                                        <div className="flex items-center gap-2">
                                            <Badge
                                                variant={
                                                    (attempt.accuracy_percent ?? 0) >= 80
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {attempt.accuracy_percent?.toFixed(0) ?? 0}%
                                            </Badge>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
