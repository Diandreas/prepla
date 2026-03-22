import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { useMemo, useState } from 'react';
import * as Flags from 'country-flag-icons/react/3x2';
import type { ExamWithLanguage, UserProfile } from '@/types';

function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}

function flagEmojiToCode(flag: string): string {
    const points = [...flag].map(c => c.codePointAt(0)! - 0x1F1E6);
    if (points.length === 2 && points[0] >= 0 && points[0] <= 25) {
        return String.fromCharCode(65 + points[0], 65 + points[1]);
    }
    return '';
}

function FlagImg({ flag, size = 24 }: { flag: string; size?: number }) {
    const code = flagEmojiToCode(flag);
    const FlagComponent = code ? (Flags as Record<string, React.ComponentType<{ style?: React.CSSProperties }>>)[code] : null;
    if (FlagComponent) return <FlagComponent style={{ width: size, borderRadius: 2 }} />;
    return <span style={{ fontSize: '1.5rem' }}>{flag}</span>;
}

interface Props {
    exam: ExamWithLanguage | null;
    profile: UserProfile | null;
}

export default function GoalSetting({ exam, profile }: Props) {
    const [targetScore, setTargetScore] = useState<string>(
        profile?.target_score?.toString() ?? ''
    );
    const [examDate, setExamDate] = useState<string>(profile?.exam_date ?? '');

    const scorePercentage = useMemo(() => {
        if (!exam?.max_score || !targetScore) return 0;
        const score = parseInt(targetScore);
        if (isNaN(score)) return 0;
        return Math.min(100, Math.max(0, (score / exam.max_score) * 100));
    }, [targetScore, exam?.max_score]);

    const daysRemaining = useMemo(() => {
        if (!examDate) return null;
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const target = new Date(examDate);
        const diff = Math.ceil((target.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
        return diff >= 0 ? diff : null;
    }, [examDate]);

    function handleSubmit() {
        router.post(route('onboarding.goal.store'), {
            target_score: targetScore ? parseInt(targetScore) : null,
            exam_date: examDate || null,
        });
    }

    return (
        <OnboardingLayout title="Définissez votre objectif" step={3}>
            <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                {/* Premium exam badge */}
                {exam && (
                    <div className="flex justify-center animate-in fade-in zoom-in-95 duration-700">
                        <div className="inline-flex items-center gap-3 rounded-full border border-primary/20 bg-primary/5 px-5 py-2.5 shadow-sm">
                            <FlagImg flag={exam.language.flag} size={32} />
                            <div className="flex flex-col">
                                <span className="text-sm font-semibold text-foreground">{exam.name}</span>
                                <span className="text-xs text-muted-foreground">{exam.language.name}</span>
                            </div>
                        </div>
                    </div>
                )}

                {/* Header */}
                <div className="text-center animate-in fade-in slide-in-from-bottom-2 duration-500 delay-100">
                    <h1 className="text-3xl font-bold tracking-tight">Définissez votre objectif</h1>
                    <p className="mt-2 text-muted-foreground">
                        {exam
                            ? `Préparation pour ${exam.name} (${exam.language.name})`
                            : 'Parlez-nous de vos objectifs'}
                    </p>
                </div>

                {/* Form card */}
                <div className="mx-auto max-w-md animate-in fade-in slide-in-from-bottom-3 duration-500 delay-200">
                    <div className="rounded-2xl border border-border/60 bg-card p-6 shadow-lg shadow-black/5 space-y-6">
                        {exam?.max_score && (
                            <div className="space-y-2">
                                <Label htmlFor="target-score" className="flex items-center gap-2">
                                    <Icon name="target" size={16} className="text-primary" />
                                    Score visé (max {exam.max_score})
                                </Label>
                                <Input
                                    id="target-score"
                                    type="number"
                                    min={0}
                                    max={exam.max_score}
                                    placeholder={`ex. ${Math.round(exam.max_score * 0.7)}`}
                                    value={targetScore}
                                    onChange={(e) => setTargetScore(e.target.value)}
                                />
                                {/* Score progress indicator */}
                                {targetScore && (
                                    <div className="space-y-1 animate-in fade-in duration-300">
                                        <div className="flex justify-between text-xs text-muted-foreground">
                                            <span>0</span>
                                            <span className="font-medium text-foreground">
                                                {targetScore} / {exam.max_score}
                                            </span>
                                            <span>{exam.max_score}</span>
                                        </div>
                                        <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full rounded-full bg-gradient-to-r from-primary to-primary/70 transition-all duration-500 ease-out"
                                                style={{ width: `${scorePercentage}%` }}
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="exam-date" className="flex items-center gap-2">
                                <Icon name="calendar-days" size={16} className="text-primary" />
                                Date d'examen (optionnel)
                            </Label>
                            <Input
                                id="exam-date"
                                type="date"
                                value={examDate}
                                onChange={(e) => setExamDate(e.target.value)}
                            />
                            {daysRemaining !== null ? (
                                <p className="text-xs font-medium text-primary animate-in fade-in duration-300">
                                    {daysRemaining === 0
                                        ? "C'est aujourd'hui !"
                                        : daysRemaining === 1
                                          ? '1 jour restant'
                                          : `${daysRemaining} jours restants`}
                                </p>
                            ) : (
                                <p className="text-xs text-muted-foreground">
                                    Nous créerons un programme d'étude adapté à votre calendrier
                                </p>
                            )}
                        </div>
                    </div>
                </div>

                {/* Motivational tip box */}
                <div className="mx-auto max-w-md animate-in fade-in slide-in-from-bottom-2 duration-500 delay-300">
                    <div className="flex items-start gap-3 rounded-xl border border-amber-200/60 bg-amber-50/50 p-4 dark:border-amber-500/20 dark:bg-amber-950/20">
                        <Icon name="lightbulb" size={20} className="mt-0.5 shrink-0 text-amber-500" />
                        <p className="text-sm text-amber-900 dark:text-amber-200/90">
                            Fixer un objectif précis augmente vos chances de réussite de 42%
                        </p>
                    </div>
                </div>

                {/* Navigation */}
                <div className="flex justify-between animate-in fade-in duration-500 delay-300">
                    <Button
                        variant="outline"
                        onClick={() => router.get(route('onboarding.exam'))}
                    >
                        &larr; Retour
                    </Button>
                    <Button
                        size="lg"
                        onClick={handleSubmit}
                        className="bg-gradient-to-r from-primary to-primary/85 font-semibold shadow-md"
                    >
                        Continuer &rarr;
                    </Button>
                </div>
            </div>
        </OnboardingLayout>
    );
}
