import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}
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
            <Head title="Recommandations IA" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold">Recommandations d'étude</h1>
                    <p className="text-muted-foreground">
                        Suggestions personnalisées basées sur vos performances
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="flex flex-col items-center py-6">
                            <Icon name="target" size={32} className="text-blue-500" />
                            <p className="mt-2 text-2xl font-bold">{profile?.current_level ?? '—'}</p>
                            <p className="text-sm text-muted-foreground">Niveau actuel</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex flex-col items-center py-6">
                            <Icon name="trending-up" size={32} className="text-green-500" />
                            <p className="mt-2 text-2xl font-bold">{avgAccuracy.toFixed(0)}%</p>
                            <p className="text-sm text-muted-foreground">Précision moyenne (10 derniers)</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex flex-col items-center py-6">
                            <Icon name="lightbulb" size={32} className="text-yellow-500" />
                            <p className="mt-2 text-2xl font-bold">{recentAttempts.length}</p>
                            <p className="text-sm text-muted-foreground">Exercices récents</p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Domaines prioritaires suggérés</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recentAttempts.length === 0 ? (
                            <p className="text-muted-foreground">
                                Complétez d'abord quelques exercices pour obtenir des recommandations personnalisées.
                            </p>
                        ) : (
                            <div className="space-y-3">
                                <div className="rounded-lg border border-border p-4">
                                    <div className="flex items-center gap-2">
                                        <Badge>Priorité</Badge>
                                        <span className="font-medium">Pratiquer davantage d'exercices</span>
                                    </div>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Continuez à pratiquer pour enrichir votre profil de compétences. L'IA fournira des recommandations plus précises au fur et à mesure que vous complétez des exercices.
                                    </p>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {recentAttempts.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Performances récentes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {recentAttempts.map((attempt) => (
                                    <div
                                        key={attempt.id}
                                        className="flex items-center justify-between rounded-lg border border-border p-3"
                                    >
                                        <span className="text-sm font-medium">
                                            {attempt.exercise?.exercise_type?.name ?? 'Exercice'}
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
