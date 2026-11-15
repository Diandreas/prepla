import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { AchievementRecord } from '@/types';
import { Illustration } from '@/components/illustration';

// La medaille de chaque badge ; un badge sans medaille garde son icone.
const MEDAILLES: Record<string, string> = {
    'first-exercise': '/illustrations/badges/premier-pas.webp',
    'ten-exercises': '/illustrations/badges/premier-pas.webp',
    'streak-3': '/illustrations/badges/serie-7j.webp',
    'streak-7': '/illustrations/badges/serie-7j.webp',
    'streak-30': '/illustrations/badges/serie-30j.webp',
    'perfect-score': '/illustrations/badges/examen-blanc.webp',
    'speed-demon': '/illustrations/badges/examen-blanc.webp',
    'fifty-exercises': '/illustrations/badges/niveau-gagne.webp',
    'hundred-exercises': '/illustrations/badges/niveau-gagne.webp',
    'xp-1000': '/illustrations/badges/niveau-gagne.webp',
    'xp-10000': '/illustrations/badges/niveau-gagne.webp',
    'all-skills': '/illustrations/badges/niveau-gagne.webp',
};

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
                                    {MEDAILLES[achievement.slug] ? (
                                        <Illustration src={MEDAILLES[achievement.slug]} alt="" size={56} className={`mx-0 shrink-0 ${earned ? '' : 'grayscale'}`} />
                                    ) : (
                                    <div className={`flex h-10 w-10 items-center justify-center rounded-full ${
                                        earned ? 'bg-primary/10' : 'bg-muted'
                                    }`}>
                                        {earned ? (
                                            <img src="/icons/check-circle.png" alt="" width={20} height={20} style={{ objectFit: 'contain' }} className="text-primary" />
                                        ) : (
                                            <img src="/icons/shield.png" alt="" width={20} height={20} style={{ objectFit: 'contain' }} className="text-muted-foreground" />
                                        )}
                                    </div>
                                    )}
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
