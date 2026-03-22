import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import * as Flags from 'country-flag-icons/react/3x2';
import type { ExerciseAttempt } from '@/types';

function flagEmojiToCode(flag: string): string {
    const points = [...flag].map(c => c.codePointAt(0)! - 0x1F1E6);
    if (points.length === 2 && points[0] >= 0 && points[0] <= 25) {
        return String.fromCharCode(65 + points[0], 65 + points[1]);
    }
    return '';
}

function FlagImg({ flag, size = 16 }: { flag: string; size?: number }) {
    const code = flagEmojiToCode(flag);
    const FlagComponent = code ? (Flags as Record<string, React.ComponentType<{ style?: React.CSSProperties }>>)[code] : null;
    if (FlagComponent) return <FlagComponent style={{ width: size, borderRadius: 2, display: 'inline-block', verticalAlign: 'middle' }} />;
    return <span>{flag}</span>;
}

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
            <Head title="Mes tentatives" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold">Mes tentatives</h1>
                    <p className="text-muted-foreground">Historique complet de vos exercices</p>
                </div>

                {attempts.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-border py-16 text-center">
                        <img src="/icons/tasks.png" alt="" width={48} height={48} className="mb-4 opacity-40" style={{ objectFit: 'contain' }} />
                        <p className="text-lg font-semibold text-muted-foreground">Aucune tentative pour le moment</p>
                        <p className="mt-1 text-sm text-muted-foreground/70">Complétez des exercices pour voir vos résultats ici</p>
                        <Link
                            href={route('practice.index')}
                            className="mt-5 inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-primary-foreground transition-all hover:opacity-90"
                        >
                            <img src="/icons/rocket.png" alt="" width={14} height={14} style={{ filter: 'brightness(0) invert(1)', objectFit: 'contain' }} />
                            Commencer à pratiquer
                        </Link>
                    </div>
                ) : (
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
                                    {attempt.exercise?.exam?.language?.flag && <FlagImg flag={attempt.exercise.exam.language.flag} size={16} />}{' '}{attempt.exercise?.exam?.name} · {new Date(attempt.created_at).toLocaleDateString()}
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
                )}

                {/* Pagination */}
                {attempts.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {attempts.prev_page_url && (
                            <Link
                                href={attempts.prev_page_url}
                                className="rounded-lg border border-border px-4 py-2 text-sm hover:bg-muted"
                            >
                                Précédent
                            </Link>
                        )}
                        <span className="flex items-center px-4 text-sm text-muted-foreground">
                            Page {attempts.current_page} sur {attempts.last_page}
                        </span>
                        {attempts.next_page_url && (
                            <Link
                                href={attempts.next_page_url}
                                className="rounded-lg border border-border px-4 py-2 text-sm hover:bg-muted"
                            >
                                Suivant
                            </Link>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
