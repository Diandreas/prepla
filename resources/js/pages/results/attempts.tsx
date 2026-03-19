import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import type { ExerciseAttempt } from '@/types';

interface Props {
    attempts: {
        data: ExerciseAttempt[];
        current_page: number;
        last_page: number;
        next_page_url: string | null;
        prev_page_url: string | null;
    };
}

export default function Attempts({ attempts }: Props) {
    return (
        <AppLayout>
            <Head title="All Attempts" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold">All Attempts</h1>
                    <p className="text-muted-foreground">Your complete exercise history</p>
                </div>

                <div className="space-y-2">
                    {attempts.data.map((attempt) => (
                        <Link
                            key={attempt.id}
                            href={route('results.attempt', attempt.id)}
                            className="flex items-center justify-between rounded-lg border border-border p-4 transition-all hover:border-primary/50"
                        >
                            <div>
                                <p className="font-medium">
                                    {attempt.exercise?.exercise_type?.name ?? 'Exercise'}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {attempt.exercise?.exam?.language?.flag} {attempt.exercise?.exam?.name} · {new Date(attempt.created_at).toLocaleDateString()}
                                </p>
                            </div>
                            <div className="flex items-center gap-3">
                                <Badge
                                    variant={Number(attempt.accuracy_percent ?? 0) >= 80 ? 'default' : 'secondary'}
                                >
                                    {Number(attempt.accuracy_percent ?? 0).toFixed(0)}%
                                </Badge>
                                <span className="text-sm font-medium">+{attempt.xp_earned} XP</span>
                            </div>
                        </Link>
                    ))}
                </div>

                {/* Pagination */}
                {attempts.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {attempts.prev_page_url && (
                            <Link
                                href={attempts.prev_page_url}
                                className="rounded-lg border border-border px-4 py-2 text-sm hover:bg-muted"
                            >
                                Previous
                            </Link>
                        )}
                        <span className="flex items-center px-4 text-sm text-muted-foreground">
                            Page {attempts.current_page} of {attempts.last_page}
                        </span>
                        {attempts.next_page_url && (
                            <Link
                                href={attempts.next_page_url}
                                className="rounded-lg border border-border px-4 py-2 text-sm hover:bg-muted"
                            >
                                Next
                            </Link>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
