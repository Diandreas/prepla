import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent } from '@/components/ui/card';
function Icon({ name, size = 20, className }: { name: string; size?: number; className?: string }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain' }} />;
}
import type { UserProfile } from '@/types';

interface Props {
    profile: UserProfile | null;
    totalAttempts: number;
    totalTime: number;
    avgAccuracy: number;
}

export default function Stats({ profile, totalAttempts, totalTime, avgAccuracy }: Props) {
    const hours = Math.floor((totalTime ?? 0) / 3600);
    const minutes = Math.floor(((totalTime ?? 0) % 3600) / 60);

    return (
        <AppLayout>
            <Head title="My Stats" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold">My Stats</h1>
                    <p className="text-muted-foreground">Your learning statistics at a glance</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card>
                        <CardContent className="flex items-center gap-4 py-6">
                            <Icon name="zap" size={40} className="text-yellow-500" />
                            <div>
                                <p className="text-3xl font-bold">{profile?.xp_total ?? 0}</p>
                                <p className="text-sm text-muted-foreground">Total XP</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-4 py-6">
                            <Icon name="flame" size={40} className="text-orange-500" />
                            <div>
                                <p className="text-3xl font-bold">{profile?.streak_current ?? 0}</p>
                                <p className="text-sm text-muted-foreground">Day Streak</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-4 py-6">
                            <Icon name="target" size={40} className="text-blue-500" />
                            <div>
                                <p className="text-3xl font-bold">{profile?.current_level ?? '—'}</p>
                                <p className="text-sm text-muted-foreground">Current Level</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-4 py-6">
                            <Icon name="trending-up" size={40} className="text-green-500" />
                            <div>
                                <p className="text-3xl font-bold">{avgAccuracy}%</p>
                                <p className="text-sm text-muted-foreground">Avg Accuracy</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-4 py-6">
                            <Icon name="award" size={40} className="text-purple-500" />
                            <div>
                                <p className="text-3xl font-bold">{totalAttempts}</p>
                                <p className="text-sm text-muted-foreground">Exercises Done</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-4 py-6">
                            <Icon name="clock" size={40} className="text-indigo-500" />
                            <div>
                                <p className="text-3xl font-bold">
                                    {hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`}
                                </p>
                                <p className="text-sm text-muted-foreground">Time Practiced</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
