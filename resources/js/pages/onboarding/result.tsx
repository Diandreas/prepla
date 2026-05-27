import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { useEffect, useState } from 'react';
import * as Flags from 'country-flag-icons/react/3x2';
import type { UserProfile } from '@/types';

function flagEmojiToCode(flag: string): string {
    const points = [...flag].map(c => c.codePointAt(0)! - 0x1F1E6);
    if (points.length === 2 && points[0] >= 0 && points[0] <= 25) {
        return String.fromCharCode(65 + points[0], 65 + points[1]);
    }
    return '';
}

function FlagImg({ flag, size = 20 }: { flag: string; size?: number }) {
    const code = flagEmojiToCode(flag);
    const FlagComponent = code ? (Flags as Record<string, React.ComponentType<{ style?: React.CSSProperties }>>)[code] : null;
    if (FlagComponent) return <FlagComponent style={{ width: size, borderRadius: 2, display: 'inline-block', verticalAlign: 'middle' }} />;
    return <span>{flag}</span>;
}

// Custom icon component using icons from /public/icons
function CustomIcon({ name, className, style }: { name: string; className?: string; style?: React.CSSProperties }) {
    return (
        <img
            src={`/icons/${name}.png`}
            alt=""
            className={className || 'h-5 w-5'}
            style={{ objectFit: 'contain', ...style }}
        />
    );
}

interface FocusArea {
    skill: string;
    priority: string;
    description: string;
}

interface Milestone {
    week: number;
    goal: string;
}

interface StudyProgram {
    summary: string;
    strengths: string[];
    focus_areas: FocusArea[];
    weekly_plan: Record<string, number>;
    milestones: Milestone[];
    daily_tip: string;
    next_level: string;
}

interface Props {
    profile: UserProfile & {
        target_exam?: {
            id: number;
            name: string;
            language: { name: string; flag: string; native_name: string };
        };
        level_source?: 'declared' | 'tested' | null;
    };
    exam?: {
        id: number;
        name: string;
        levels: string[];
        language: { name: string; flag: string; native_name: string };
    } | null;
    program: StudyProgram;
}

const levelGradients: Record<string, string> = {
    A1: 'from-emerald-400 to-emerald-600',
    A2: 'from-green-400 to-green-600',
    B1: 'from-blue-400 to-blue-600',
    B2: 'from-indigo-400 to-indigo-600',
    C1: 'from-purple-400 to-purple-600',
    C2: 'from-rose-400 to-rose-600',
};

const levelRings: Record<string, string> = {
    A1: 'ring-emerald-200 dark:ring-emerald-800',
    A2: 'ring-green-200 dark:ring-green-800',
    B1: 'ring-blue-200 dark:ring-blue-800',
    B2: 'ring-indigo-200 dark:ring-indigo-800',
    C1: 'ring-purple-200 dark:ring-purple-800',
    C2: 'ring-rose-200 dark:ring-rose-800',
};

const skillIcons: Record<string, string> = {
    reading: 'book',
    listening: 'headphones',
    writing: 'writing',
    speaking: 'speaking',
};

const skillLabels: Record<string, string> = {
    reading: 'Compréhension écrite',
    listening: 'Compréhension orale',
    writing: 'Expression écrite',
    speaking: 'Expression orale',
};

const priorityBadge: Record<string, string> = {
    haute: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    moyenne: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    basse: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
};

const CEFR = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

export default function Result({ profile, exam, program }: Props) {
    const level = profile.current_level ?? 'B1';
    const gradient = levelGradients[level] ?? levelGradients['B1'];
    const ring = levelRings[level] ?? levelRings['B1'];
    const levelIndex = CEFR.indexOf(level);

    // Determine target level: use highest CEFR level from the exam if available,
    // otherwise fall back to next level after current
    const examCefrLevels = (exam?.levels ?? []).filter(l => CEFR.includes(l));
    const targetLevel = examCefrLevels.length > 0
        ? examCefrLevels[examCefrLevels.length - 1]
        : (program?.next_level ?? CEFR[Math.min(levelIndex + 1, 5)]);

    const [mounted, setMounted] = useState(false);
    const [showLevel, setShowLevel] = useState(false);
    useEffect(() => {
        setMounted(true);
        setTimeout(() => setShowLevel(true), 600);
    }, []);

    function handleComplete() {
        router.post(route('onboarding.complete'));
    }

    const stagger = (i: number) => ({
        opacity: mounted ? 1 : 0,
        transform: mounted ? 'translateY(0)' : 'translateY(20px)',
        transition: 'all 0.6s cubic-bezier(0.22, 1, 0.36, 1)',
        transitionDelay: `${400 + i * 150}ms`,
    });

    return (
        <OnboardingLayout title="Vos résultats" step={5}>
            <div className="space-y-7 pb-8">

                {/* Level badge */}
                {level === 'A0' ? (
                    <div className="text-center" style={stagger(0)}>
                        <div className="relative mx-auto mb-5 flex h-28 w-28 items-center justify-center">
                            <div className={`absolute inset-0 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 opacity-20 blur-2xl transition-all duration-1000 ${showLevel ? 'scale-110' : 'scale-0'}`} />
                            <div className={`absolute inset-0 rounded-full ring-4 ring-indigo-200 dark:ring-indigo-800 transition-all duration-700 ${showLevel ? 'scale-100 opacity-100' : 'scale-50 opacity-0'}`} />
                            <div className={`relative flex h-full w-full flex-col items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700 shadow-xl transition-all duration-700 ${showLevel ? 'scale-100' : 'scale-0'}`}>
                                <CustomIcon name="rocket" className="h-12 w-12" style={{ filter: 'brightness(0) invert(1)' }} />
                            </div>
                            {showLevel && (
                                <>
                                    <div className="absolute -top-2 -right-2 animate-sparkle"><CustomIcon name="star" className="h-4 w-4" style={{ filter: 'brightness(0) saturate(100%) invert(63%) sepia(31%) saturate(2078%) hue-rotate(229deg) brightness(97%) contrast(92%)' }} /></div>
                                    <div className="absolute -bottom-1 -left-3 animate-sparkle" style={{ animationDelay: '0.3s' }}><CustomIcon name="star" className="h-3 w-3" style={{ filter: 'brightness(0) saturate(100%) invert(84%) sepia(40%) saturate(1734%) hue-rotate(353deg) brightness(94%) contrast(86%)' }} /></div>
                                    <div className="absolute top-0 -left-4 animate-sparkle" style={{ animationDelay: '0.6s' }}><CustomIcon name="star" className="h-3.5 w-3.5" style={{ filter: 'brightness(0) saturate(100%) invert(39%) sepia(96%) saturate(1944%) hue-rotate(212deg) brightness(94%) contrast(91%)' }} /></div>
                                </>
                            )}
                        </div>
                        <h1 className="text-2xl font-bold sm:text-3xl">Parfait point de départ !</h1>
                        <p className="mt-2 text-sm text-muted-foreground max-w-xs mx-auto">
                            Tu pars de zéro en {profile.target_exam?.language.name || 'cette langue'} — c'est exactement là que commence la vraie progression.
                        </p>
                        {profile.target_exam && (
                            <p className="mt-1.5 text-xs text-muted-foreground inline-flex items-center gap-1">
                                <FlagImg flag={profile.target_exam.language.flag} size={16} />{' '}
                                {profile.target_exam.name}
                                {profile.target_score ? ` · Score cible : ${profile.target_score}` : ''}
                            </p>
                        )}
                    </div>
                ) : (
                    <div className="text-center" style={stagger(0)}>
                        <div className="relative mx-auto mb-5 flex h-28 w-28 items-center justify-center">
                            <div className={`absolute inset-0 rounded-full bg-gradient-to-br ${gradient} opacity-20 blur-2xl transition-all duration-1000 ${showLevel ? 'scale-110' : 'scale-0'}`} />
                            <div className={`absolute inset-0 rounded-full ring-4 ${ring} transition-all duration-700 ${showLevel ? 'scale-100 opacity-100' : 'scale-50 opacity-0'}`} />
                            <div className={`relative flex h-full w-full flex-col items-center justify-center rounded-full bg-gradient-to-br ${gradient} shadow-xl transition-all duration-700 ${showLevel ? 'scale-100' : 'scale-0'}`}>
                                <span className="text-4xl font-black text-white tracking-tight">{level}</span>
                            </div>
                            {showLevel && (
                                <>
                                    <div className="absolute -top-2 -right-2 animate-sparkle"><CustomIcon name="star" className="h-4 w-4" style={{ filter: 'brightness(0) saturate(100%) invert(84%) sepia(40%) saturate(1734%) hue-rotate(353deg) brightness(94%) contrast(86%)' }} /></div>
                                    <div className="absolute -bottom-1 -left-3 animate-sparkle" style={{ animationDelay: '0.3s' }}><CustomIcon name="star" className="h-3 w-3" style={{ filter: 'brightness(0) saturate(100%) invert(39%) sepia(96%) saturate(1944%) hue-rotate(212deg) brightness(94%) contrast(91%)' }} /></div>
                                    <div className="absolute top-0 -left-4 animate-sparkle" style={{ animationDelay: '0.6s' }}><CustomIcon name="star" className="h-3.5 w-3.5" style={{ filter: 'brightness(0) saturate(100%) invert(63%) sepia(31%) saturate(2078%) hue-rotate(229deg) brightness(97%) contrast(92%)' }} /></div>
                                </>
                            )}
                        </div>
                        <h1 className="text-2xl font-bold sm:text-3xl">
                            {profile.level_source === 'tested' ? 'Votre niveau estimé' : 'Votre point de départ'}
                        </h1>
                        {profile.target_exam && (
                            <p className="mt-1 text-sm text-muted-foreground inline-flex items-center gap-1">
                                <FlagImg flag={profile.target_exam.language.flag} size={18} />{' '}
                                {profile.target_exam.name}
                                {profile.target_score ? ` · Score cible : ${profile.target_score}` : ''}
                            </p>
                        )}
                        {profile.level_source !== 'tested' && (
                            <div className="mt-4 inline-flex flex-col items-center gap-2 rounded-xl border border-amber-300/50 bg-amber-50 dark:bg-amber-950/20 px-4 py-3 text-xs">
                                <p className="text-amber-700 dark:text-amber-300">
                                    Niveau auto-déclaré. Pour une vraie évaluation,
                                </p>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get(route('onboarding.placement'))}
                                    className="gap-1.5 border-amber-300 text-amber-700 hover:bg-amber-100 dark:text-amber-300"
                                >
                                    <CustomIcon name="file-edit" className="h-3.5 w-3.5" />
                                    Passer le test de niveau (5 min)
                                </Button>
                            </div>
                        )}
                    </div>
                )}

                {/* CEFR bar - Hidden for A0 beginners */}
                {level !== 'A0' && (
                    <div className="rounded-2xl border border-border bg-card p-4" style={stagger(1)}>
                        <p className="mb-2.5 text-xs font-medium text-muted-foreground uppercase tracking-wide">Progression CECRL</p>
                        <div className="flex gap-1">
                            {CEFR.map((l, i) => (
                                <div key={l} className="flex flex-1 flex-col items-center gap-1">
                                    <div className="relative h-3 w-full overflow-hidden rounded-full bg-muted">
                                        <div
                                            className={`absolute inset-y-0 left-0 rounded-full bg-gradient-to-r ${gradient} transition-all duration-1000 ease-out`}
                                            style={{
                                                width: mounted ? (i <= levelIndex ? '100%' : '0%') : '0%',
                                                transitionDelay: `${800 + i * 100}ms`,
                                            }}
                                        />
                                    </div>
                                    <span className={`text-xs font-semibold transition-colors duration-500 ${i === levelIndex ? 'text-foreground' : i < levelIndex ? 'text-primary' : 'text-muted-foreground'}`}>
                                        {l}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* AI Summary */}
                {program?.summary && (
                    <div className="rounded-2xl border border-primary/25 bg-primary/5 p-5" style={stagger(2)}>
                        <div className="flex items-start gap-3">
                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/15 animate-pulse-soft">
                                <CustomIcon name="sparkles" className="h-4 w-4" style={{ filter: 'brightness(0) saturate(100%) invert(65%) sepia(60%) saturate(600%) hue-rotate(195deg)' }} />
                            </div>
                            <div>
                                <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-primary">
                                    Analyse personnalisée IA
                                </p>
                                <p className="text-sm leading-relaxed">{program.summary}</p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Strengths */}
                {program?.strengths?.length > 0 && (
                    <div className="rounded-2xl border border-border bg-card p-5" style={stagger(3)}>
                        <h2 className="mb-3 flex items-center gap-2 font-semibold">
                            <CustomIcon name="trophy" className="h-4 w-4" style={{ filter: 'brightness(0) saturate(100%) invert(84%) sepia(40%) saturate(1734%) hue-rotate(353deg) brightness(94%) contrast(86%)' }} />
                            Vos points forts
                        </h2>
                        <ul className="space-y-2">
                            {program.strengths.map((s, i) => (
                                <li
                                    key={i}
                                    className="flex items-start gap-2 text-sm"
                                    style={{
                                        opacity: mounted ? 1 : 0,
                                        transform: mounted ? 'translateX(0)' : 'translateX(-12px)',
                                        transition: 'all 0.4s ease',
                                        transitionDelay: `${1000 + i * 100}ms`,
                                    }}
                                >
                                    <CustomIcon name="check-circle" className="mt-0.5 h-4 w-4 shrink-0" style={{ filter: 'brightness(0) saturate(100%) invert(47%) sepia(58%) saturate(392%) hue-rotate(96deg) brightness(97%) contrast(86%)' }} />
                                    {s}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {/* Focus Areas */}
                {program?.focus_areas?.length > 0 && (
                    <div className="rounded-2xl border border-border bg-card p-5" style={stagger(4)}>
                        <h2 className="mb-4 flex items-center gap-2 font-semibold">
                            <CustomIcon name="target" className="h-4 w-4" style={{ filter: 'brightness(0) saturate(100%) invert(39%) sepia(96%) saturate(1944%) hue-rotate(212deg) brightness(94%) contrast(91%)' }} />
                            Programme personnalisé par compétence
                        </h2>
                        <div className="grid gap-3 sm:grid-cols-2">
                            {program.focus_areas.map((area, i) => {
                                const Icon = skillIcons[area.skill] ?? 'book';
                                return (
                                    <div
                                        key={area.skill}
                                        className="rounded-xl border border-border p-4 hover-lift"
                                        style={{
                                            opacity: mounted ? 1 : 0,
                                            transform: mounted ? 'scale(1)' : 'scale(0.9)',
                                            transition: 'all 0.5s ease',
                                            transitionDelay: `${1200 + i * 100}ms`,
                                        }}
                                    >
                                        <div className="mb-2 flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <CustomIcon name={Icon} className="h-4 w-4" />
                                                <span className="text-sm font-semibold">
                                                    {skillLabels[area.skill] ?? area.skill}
                                                </span>
                                            </div>
                                            <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${priorityBadge[area.priority] ?? ''}`}>
                                                {area.priority}
                                            </span>
                                        </div>
                                        <p className="text-xs leading-relaxed text-muted-foreground">{area.description}</p>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* Weekly Plan */}
                {program?.weekly_plan && (
                    <div className="rounded-2xl border border-border bg-card p-5" style={stagger(5)}>
                        <h2 className="mb-4 flex items-center gap-2 font-semibold">
                            <CustomIcon name="zap" className="h-4 w-4" style={{ filter: 'brightness(0) saturate(100%) invert(84%) sepia(40%) saturate(1734%) hue-rotate(353deg) brightness(94%) contrast(86%)' }} />
                            Plan hebdomadaire recommandé
                        </h2>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            {Object.entries(program.weekly_plan).map(([skill, count], i) => {
                                const Icon = skillIcons[skill] ?? 'book';
                                return (
                                    <div
                                        key={skill}
                                        className="flex flex-col items-center rounded-xl bg-muted/40 p-3 text-center transition-all duration-300 hover:bg-muted/70"
                                        style={{
                                            opacity: mounted ? 1 : 0,
                                            transform: mounted ? 'translateY(0)' : 'translateY(12px)',
                                            transition: 'all 0.5s ease',
                                            transitionDelay: `${1500 + i * 80}ms`,
                                        }}
                                    >
                                        <CustomIcon name={Icon} className="h-5 w-5" />
                                        <span className="mt-1 text-2xl font-bold">{count}</span>
                                        <span className="mt-0.5 text-xs text-muted-foreground">exercices/sem.</span>
                                        <span className="text-xs font-medium text-foreground capitalize">{skill}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* Milestones */}
                {program?.milestones?.length > 0 && (
                    <div className="rounded-2xl border border-border bg-card p-5" style={stagger(6)}>
                        <h2 className="mb-4 flex items-center gap-2 font-semibold">
                            <CustomIcon name="trending-up" className="h-4 w-4" style={{ filter: 'brightness(0) saturate(100%) invert(47%) sepia(58%) saturate(392%) hue-rotate(96deg) brightness(97%) contrast(86%)' }} />
                            Objectifs étape par étape
                        </h2>
                        <div className="relative space-y-4 pl-7">
                            <div className="absolute left-2.5 top-2 bottom-2 w-0.5 bg-border" />
                            {program.milestones.map((m, i) => (
                                <div
                                    key={i}
                                    className="relative flex items-start gap-3"
                                    style={{
                                        opacity: mounted ? 1 : 0,
                                        transform: mounted ? 'translateX(0)' : 'translateX(-16px)',
                                        transition: 'all 0.5s ease',
                                        transitionDelay: `${1700 + i * 120}ms`,
                                    }}
                                >
                                    <div className={`absolute -left-5 flex h-5 w-5 items-center justify-center rounded-full bg-gradient-to-br ${gradient} text-[10px] font-bold text-white shadow`}>
                                        {i + 1}
                                    </div>
                                    <div>
                                        <p className="text-xs font-semibold text-primary">Semaine {m.week}</p>
                                        <p className="text-sm leading-snug">{m.goal}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Daily tip */}
                {program?.daily_tip && (
                    <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/30 dark:bg-amber-900/10" style={stagger(7)}>
                        <div className="flex items-start gap-3">
                            <CustomIcon name="lightbulb" className="mt-0.5 h-5 w-5 shrink-0 animate-bounce-soft" style={{ filter: 'brightness(0) saturate(100%) invert(84%) sepia(40%) saturate(1734%) hue-rotate(353deg) brightness(94%) contrast(86%)' }} />
                            <div>
                                <p className="text-sm font-semibold text-amber-800 dark:text-amber-300">Conseil du jour</p>
                                <p className="mt-0.5 text-sm text-amber-700 dark:text-amber-400">{program.daily_tip}</p>
                            </div>
                        </div>
                    </div>
                )}

                {/* CTA */}
                <div className={`rounded-2xl border-2 border-primary/20 bg-gradient-to-br from-primary/5 to-primary/10 p-6 text-center`} style={stagger(8)}>
                    <div className="flex items-center justify-center gap-2">
                        <CustomIcon name="flame" className="h-5 w-5" style={{ filter: 'brightness(0) saturate(100%) invert(50%) sepia(96%) saturate(1762%) hue-rotate(332deg) brightness(102%) contrast(96%)' }} />
                        <p className="font-semibold">
                            {level === 'A0'
                                ? 'Objectif : premiers pas vers A1'
                                : `Objectif : niveau ${targetLevel}`}
                        </p>
                    </div>
                    <p className="mt-1.5 text-sm text-muted-foreground">
                        {level === 'A0'
                            ? "Ton programme débutant est prêt. Lance-toi dès maintenant !"
                            : "Votre feuille de route IA est prête. Commencez à pratiquer dès aujourd'hui !"}
                    </p>
                    <Button
                        size="lg"
                        onClick={handleComplete}
                        className={`mt-4 w-full gap-2 bg-gradient-to-r ${level === 'A0' ? 'from-indigo-500 to-indigo-700' : gradient} font-semibold text-white shadow-lg hover:opacity-90 hover:shadow-xl hover:-translate-y-0.5 transition-all sm:w-auto`}
                    >
                        <CustomIcon name="rocket" className="h-4 w-4" />
                        {level === 'A0' ? 'Commencer mon parcours' : 'Voir ma feuille de route'}
                    </Button>
                </div>
            </div>
        </OnboardingLayout>
    );
}
