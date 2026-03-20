import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { AchievementRecord } from '@/types';

interface Props {
    achievements: AchievementRecord[];
    earnedIds: number[];
}

export default function Achievements({ achievements, earnedIds }: Props) {
    return (
        <AppLayout>
            <Head title="Achievements" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold">Achievements</h1>
                    <p className="text-muted-foreground">
                        {earnedIds.length} of {achievements.length} unlocked
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {achievements.map((achievement) => {
                        const earned = earnedIds.includes(achievement.id);
                        return (
                            <Card
                                key={achievement.id}
                                className={earned ? '' : 'opacity-50'}
                            >
                                <CardContent className="flex items-start gap-3 p-4">
                                    <div className={`flex h-10 w-10 items-center justify-center rounded-full ${
                                        earned ? 'bg-primary/10' : 'bg-muted'
                                    }`}>
                                        {earned ? (
                                            <img src="/icons/check-circle.png" alt="" width={20} height={20} style={{ objectFit: 'contain' }} className="text-primary" />
                                        ) : (
                                            <img src="/icons/shield.png" alt="" width={20} height={20} style={{ objectFit: 'contain' }} className="text-muted-foreground" />
                                        )}
                                    </div>
                                    <div className="flex-1">
                                        <p className="font-semibold">{achievement.name}</p>
                                        <p className="text-sm text-muted-foreground">{achievement.description}</p>
                                        <Badge variant="secondary" className="mt-2 text-xs">
                                            +{achievement.xp_reward} XP
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
