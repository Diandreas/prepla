import { Head, Link } from '@inertiajs/react';
import { ArrowRight, History, Sparkles } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { ArtIcon } from '@/components/art-icon';
import type { UserProfile, ExerciseAttempt } from '@/types';

interface SkillStat {
    count: number;
    avg_accuracy: number;
    total_xp: number;
}

interface Props {
    profile: UserProfile | null;
    skillStats: Record<string, SkillStat>;
    recentAttempts: ExerciseAttempt[];
}

const skills = [
    { key: 'reading', label: 'Lecture', icon: 'book', tone: 'blue', bar: 'bg-sky-500' },
    { key: 'listening', label: 'Écoute', icon: 'listening', tone: 'mint', bar: 'bg-emerald-500' },
    { key: 'writing', label: 'Écriture', icon: 'writing', tone: 'rose', bar: 'bg-rose-500' },
    { key: 'speaking', label: 'Expression', icon: 'speaking', tone: 'amber', bar: 'bg-amber-500' },
] as const;

const accuracy = (value: unknown) => {
    const number = Number(value);
    return Number.isFinite(number) ? Math.max(0, Math.min(number, 100)) : 0;
};

export default function ResultsIndex({ profile, skillStats, recentAttempts }: Props) {
    const totalAttempts = Object.values(skillStats).reduce((sum, stat) => sum + Number(stat.count), 0);
    const activeSkills = skills.filter(skill => Number(skillStats[skill.key]?.count) > 0);
    const nextSkill = activeSkills.length < skills.length
        ? skills.find(skill => !Number(skillStats[skill.key]?.count))
        : [...skills].sort((a, b) => accuracy(skillStats[a.key]?.avg_accuracy) - accuracy(skillStats[b.key]?.avg_accuracy))[0];

    return (
        <AppLayout>
            <Head title="Résultats" />
            <div className="mx-auto max-w-4xl space-y-6 px-4 py-5 sm:py-8">
                <section className="relative overflow-hidden rounded-3xl border border-sky-200/70 bg-gradient-to-br from-sky-50 via-white to-emerald-50 p-5 dark:border-sky-900/70 dark:from-sky-950/60 dark:via-card dark:to-emerald-950/30 sm:p-7">
                    <div aria-hidden="true" className="pointer-events-none absolute -right-12 -top-16 h-48 w-48 rounded-full border-[24px] border-sky-100/70 dark:border-sky-800/20" />
                    <div className="relative flex items-start gap-4 sm:gap-6">
                        <ArtIcon name="trophy" size={68} tone="amber" className="shrink-0" />
                        <div className="min-w-0">
                            <p className="mb-2 text-[11px] font-black uppercase tracking-[0.18em] text-sky-700 dark:text-sky-300">Ton carnet de progression</p>
                            <h1 className="text-2xl font-black leading-tight tracking-tight text-foreground sm:text-3xl">
                                {totalAttempts > 0 ? 'Chaque séance te fait avancer.' : 'Le premier pas compte.'}
                            </h1>
                            <p className="mt-2 max-w-lg text-sm leading-relaxed text-muted-foreground">
                                {totalAttempts > 0
                                    ? 'Repère tes points forts, observe tes progrès et choisis ce que tu veux travailler ensuite.'
                                    : 'Termine un exercice pour découvrir tes points forts et construire ta progression, à ton rythme.'}
                            </p>
                        </div>
                    </div>
                    <div className="relative mt-6 grid grid-cols-3 divide-x divide-border rounded-2xl border border-border bg-card/80 py-4">
                        <div className="px-2 text-center">
                            <p className="text-xl font-black text-foreground sm:text-2xl">{totalAttempts}</p>
                            <p className="mt-1 text-[11px] font-semibold text-muted-foreground">{totalAttempts === 1 ? 'exercice terminé' : 'exercices terminés'}</p>
                        </div>
                        <div className="px-2 text-center">
                            <p className="text-xl font-black text-foreground sm:text-2xl">{Number(profile?.xp_total ?? 0).toLocaleString('fr-FR')}</p>
                            <p className="mt-1 text-[11px] font-semibold text-muted-foreground">XP gagnés</p>
                        </div>
                        <div className="px-2 text-center">
                            <p className="text-xl font-black text-foreground sm:text-2xl">{activeSkills.length}<span className="text-sm text-muted-foreground"> / 4</span></p>
                            <p className="mt-1 text-[11px] font-semibold text-muted-foreground">compétences travaillées</p>
                        </div>
                    </div>
                </section>

                <section aria-labelledby="skill-results-heading">
                    <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h2 id="skill-results-heading" className="text-lg font-black text-foreground">Tes quatre compétences</h2>
                        <span className="text-xs text-muted-foreground">Taux de réussite moyen</span>
                    </div>
                    <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        {skills.map(skill => {
                            const stat = skillStats[skill.key];
                            const hasAttempts = Number(stat?.count) > 0;
                            const percent = accuracy(stat?.avg_accuracy);
                            return (
                                <div key={skill.key} className="duo-card flex flex-col p-4">
                                    <ArtIcon name={skill.icon} tone={skill.tone} size={48} className="mb-3" />
                                    <h3 className="text-sm font-bold text-foreground">{skill.label}</h3>
                                    <p className="mt-2 text-2xl font-black text-foreground">{hasAttempts ? `${percent.toFixed(0)} %` : '—'}</p>
                                    <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                                        {hasAttempts ? `${stat.count} ${Number(stat.count) === 1 ? 'exercice' : 'exercices'} · ${stat.total_xp} XP` : 'À découvrir lors d’une séance'}
                                    </p>
                                    <div
                                        role="progressbar"
                                        aria-label={`Réussite en ${skill.label.toLowerCase()}`}
                                        aria-valuemin={0}
                                        aria-valuemax={100}
                                        aria-valuenow={hasAttempts ? Math.round(percent) : 0}
                                        aria-valuetext={hasAttempts ? `${percent.toFixed(0)} %` : 'Aucun exercice terminé'}
                                        className="mt-4 h-1.5 overflow-hidden rounded-full bg-muted"
                                    >
                                        <div className={`h-full rounded-full ${skill.bar}`} style={{ width: `${hasAttempts ? percent : 0}%` }} />
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>

                {nextSkill && (
                    <div className="flex flex-col gap-4 rounded-2xl border border-border bg-card p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-start gap-3">
                            <Sparkles className="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-300" aria-hidden="true" />
                            <div>
                                <p className="text-sm font-bold text-foreground">Ton prochain petit défi</p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {activeSkills.length < skills.length ? 'Découvre' : 'Renforce'} la compétence « {nextSkill.label} » avec une courte séance.
                                </p>
                            </div>
                        </div>
                        <Link href="/practice" className="duo-btn-primary shrink-0 text-sm">Pratiquer <ArrowRight className="h-4 w-4" aria-hidden="true" /></Link>
                    </div>
                )}

                <section className="duo-card overflow-hidden p-0" aria-labelledby="recent-attempts-heading">
                    <div className="flex flex-wrap items-center justify-between gap-2 border-b border-border px-5 py-4">
                        <h2 id="recent-attempts-heading" className="text-base font-black text-foreground">Tes dernières séances</h2>
                        <Link href="/results/attempts" className="flex min-h-10 items-center gap-1.5 text-xs font-bold text-sky-700 hover:underline dark:text-sky-300">
                            <History className="h-4 w-4" aria-hidden="true" /> Tout l’historique
                        </Link>
                    </div>
                    {recentAttempts.length === 0 ? (
                        <div className="flex flex-col items-center px-5 py-8 text-center">
                            <ArtIcon name="book" size={60} tone="blue" className="mb-4" />
                            <p className="font-bold text-foreground">Ton histoire commence ici.</p>
                            <p className="mt-2 max-w-sm text-sm leading-relaxed text-muted-foreground">Après chaque exercice, retrouve ton score et les points gagnés dans cet espace.</p>
                        </div>
                    ) : (
                        <ul className="divide-y divide-border">
                            {recentAttempts.map(attempt => {
                                const percent = accuracy(attempt.accuracy_percent);
                                const isGraded = attempt.accuracy_percent !== null;
                                return (
                                    <li key={attempt.id} className="flex items-center justify-between gap-3 px-4 py-4 sm:px-5">
                                        <div className="min-w-0">
                                            <p className="text-sm font-bold text-foreground">{attempt.exercise?.exercise_type?.name ?? 'Exercice'}</p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {[attempt.exercise?.exam?.name, new Date(attempt.created_at).toLocaleDateString('fr-FR')].filter(Boolean).join(' · ')}
                                            </p>
                                        </div>
                                        <div className="flex shrink-0 flex-col items-end gap-1.5">
                                            <span className={`rounded-lg px-2.5 py-1 text-xs font-black ${!isGraded ? 'bg-muted text-muted-foreground' : percent >= 80 ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200' : 'bg-sky-50 text-sky-800 dark:bg-sky-950/50 dark:text-sky-200'}`}>
                                                {isGraded ? `${percent.toFixed(0)} %` : 'En attente'}
                                            </span>
                                            <span className="text-xs font-bold text-amber-700 dark:text-amber-300">+{attempt.xp_earned} XP</span>
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
