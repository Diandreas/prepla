import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Trophy } from 'lucide-react';
import type { LeaderboardEntry } from '@/types';

interface Props {
    entries: LeaderboardEntry[];
    currentUserId: number;
}

export default function Leaderboard({ entries, currentUserId }: Props) {
    return (
        <AppLayout>
            <Head title="Leaderboard" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold">Leaderboard</h1>
                    <p className="text-muted-foreground">Weekly rankings by XP earned</p>
                </div>

                {/* Top 3 podium */}
                {entries.length >= 3 && (
                    <div className="flex items-end justify-center gap-4">
                        {[1, 0, 2].map((idx) => {
                            const entry = entries[idx];
                            if (!entry) return null;
                            const isFirst = idx === 0;
                            return (
                                <div
                                    key={entry.id}
                                    className={`flex flex-col items-center ${isFirst ? 'order-2' : idx === 1 ? 'order-1' : 'order-3'}`}
                                >
                                    <div
                                        className={`flex h-12 w-12 items-center justify-center rounded-full text-lg font-bold ${
                                            isFirst
                                                ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                : idx === 1
                                                ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
                                                : 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
                                        }`}
                                    >
                                        {idx + 1}
                                    </div>
                                    <p className={`mt-2 font-semibold ${entry.user_id === currentUserId ? 'text-primary' : ''}`}>
                                        {entry.user?.name ?? 'User'}
                                    </p>
                                    <p className="text-sm text-muted-foreground">{entry.xp} XP</p>
                                    <div
                                        className={`mt-2 w-20 rounded-t-lg bg-primary/10 ${
                                            isFirst ? 'h-24' : idx === 1 ? 'h-16' : 'h-12'
                                        }`}
                                    />
                                </div>
                            );
                        })}
                    </div>
                )}

                {/* Full list */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">This Week</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {entries.length === 0 ? (
                            <p className="text-center text-muted-foreground py-8">
                                No entries yet. Complete exercises to earn XP and climb the leaderboard!
                            </p>
                        ) : (
                            <div className="space-y-1">
                                {entries.map((entry, i) => (
                                    <div
                                        key={entry.id}
                                        className={`flex items-center gap-3 rounded-lg p-3 ${
                                            entry.user_id === currentUserId
                                                ? 'bg-primary/5 ring-1 ring-primary/20'
                                                : i % 2 === 0
                                                ? 'bg-muted/30'
                                                : ''
                                        }`}
                                    >
                                        <span className="w-8 text-center text-sm font-bold text-muted-foreground">
                                            {i + 1}
                                        </span>
                                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-sm font-medium">
                                            {(entry.user?.name ?? 'U').charAt(0).toUpperCase()}
                                        </div>
                                        <span className="flex-1 font-medium">
                                            {entry.user?.name ?? 'User'}
                                            {entry.user_id === currentUserId && (
                                                <span className="ml-2 text-xs text-primary">(you)</span>
                                            )}
                                        </span>
                                        <span className="font-semibold">{entry.xp} XP</span>
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
