import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BookOpen, Flame, Sparkles, Target, Trophy, Zap } from 'lucide-react';
import type { SharedData } from '@/types';

export default function Dashboard() {
    const { auth, userProfile } = usePage<SharedData>().props;

    return (
        <AppLayout>
            <Head title="Dashboard" />
            <div className="space-y-6 p-4 md:p-6">
                {/* Welcome header */}
                <div>
                    <h1 className="text-2xl font-bold">
                        Welcome back, {auth.user.name}!
                    </h1>
                    <p className="text-muted-foreground">
                        {userProfile?.current_level
                            ? `Level ${userProfile.current_level} · ${userProfile.xp_total} XP`
                            : 'Ready to start practicing?'}
                    </p>
                </div>

                {/* Stats grid */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Current Streak</CardTitle>
                            <Flame className="h-4 w-4 text-orange-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {userProfile?.streak_current ?? 0} days
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Total XP</CardTitle>
                            <Zap className="h-4 w-4 text-yellow-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {userProfile?.xp_total ?? 0}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Level</CardTitle>
                            <Target className="h-4 w-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {userProfile?.current_level ?? '—'}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Target Exam</CardTitle>
                            <Trophy className="h-4 w-4 text-purple-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {userProfile?.target_exam?.name ?? '—'}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Quick actions */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card className="hover:border-primary/50 transition-colors">
                        <CardContent className="flex flex-col items-center p-6 text-center">
                            <BookOpen className="h-8 w-8 text-primary" />
                            <h3 className="mt-3 font-semibold">Practice</h3>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Continue your exam preparation
                            </p>
                            <Button className="mt-4" asChild>
                                <Link href="/practice">Start Practice</Link>
                            </Button>
                        </CardContent>
                    </Card>
                    <Card className="hover:border-primary/50 transition-colors">
                        <CardContent className="flex flex-col items-center p-6 text-center">
                            <Sparkles className="h-8 w-8 text-primary" />
                            <h3 className="mt-3 font-semibold">AI Tools</h3>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Generate exercises and get feedback
                            </p>
                            <Button variant="outline" className="mt-4" asChild>
                                <Link href="/ai-tools">Explore AI Tools</Link>
                            </Button>
                        </CardContent>
                    </Card>
                    <Card className="hover:border-primary/50 transition-colors">
                        <CardContent className="flex flex-col items-center p-6 text-center">
                            <Trophy className="h-8 w-8 text-primary" />
                            <h3 className="mt-3 font-semibold">Leaderboard</h3>
                            <p className="mt-1 text-sm text-muted-foreground">
                                See how you rank against others
                            </p>
                            <Button variant="outline" className="mt-4" asChild>
                                <Link href="/leaderboard">View Leaderboard</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
