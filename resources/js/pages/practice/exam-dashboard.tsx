import { ArtIcon } from '@/components/art-icon';
import AppLayout from '@/layouts/app-layout';
import type { ExamRecord, ExamSection, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import * as Flags from 'country-flag-icons/react/3x2';
import { ArrowRight, CheckCircle2, ChevronRight, Clock3, Info, Layers3 } from 'lucide-react';
import { useTranslation } from 'react-i18next';

function FlagImg({ flag }: { flag: string }) {
    const points = [...flag].map((character) => character.codePointAt(0)! - 0x1f1e6);
    const code = points.length === 2 && points.every((point) => point >= 0 && point <= 25) ? String.fromCharCode(65 + points[0], 65 + points[1]) : '';
    const Flag = code ? (Flags as Record<string, React.ComponentType<{ className?: string }>>)[code] : null;
    return Flag ? <Flag className="w-6 rounded-sm" /> : <span>{flag}</span>;
}

interface Props {
    exam: ExamRecord & { sections: ExamSection[] };
    sectionProgress: Record<number, number>;
}

const skills: Record<string, { icon: string; tone: 'blue' | 'mint' | 'amber' | 'rose'; description: string }> = {
    reading: { icon: 'book', tone: 'blue', description: 'Comprends les textes, repère les idées essentielles.' },
    listening: { icon: 'listening', tone: 'mint', description: 'Entraîne ton oreille et repère les informations utiles.' },
    writing: { icon: 'writing', tone: 'rose', description: 'Structure tes idées et trouve les mots justes.' },
    speaking: { icon: 'speaking', tone: 'amber', description: 'Prends la parole et gagne en aisance.' },
};

export default function ExamDashboard({ exam, sectionProgress }: Props) {
    const { t } = useTranslation();
    const { flash } = usePage<SharedData & { flash?: { error?: string; success?: string } }>().props;
    const totalExamMinutes = exam.sections.reduce((total, section) => total + (section.time_limit ?? 0), 0) || 180;
    const attempts = Object.values(sectionProgress).reduce((total, count) => total + count, 0);

    return (
        <AppLayout>
            <Head title={`${exam.name} - ${t('page_titles.practice')}`} />
            <div className="studio-page mx-auto w-full max-w-5xl space-y-7 px-4 py-6 sm:px-6 sm:py-8">
                <section className="studio-hero border-border relative overflow-hidden rounded-3xl border p-5 sm:p-7">
                    <div className="relative flex items-center justify-between gap-5">
                        <div className="max-w-xl min-w-0">
                            <div className="studio-kicker text-muted-foreground mb-3 flex items-center gap-2 text-xs font-bold tracking-widest uppercase">
                                {exam.language?.flag && <FlagImg flag={exam.language.flag} />}
                                <span>
                                    {exam.name} · {t('page_titles.practice')}
                                </span>
                            </div>
                            <h1 className="text-foreground text-2xl font-black tracking-tight sm:text-3xl">
                                {t('practice.studio_title', 'À chaque exercice, un pas de plus.')}
                            </h1>
                            <p className="text-muted-foreground mt-3 max-w-lg text-sm leading-relaxed">
                                {t(
                                    'practice.studio_description',
                                    'Travaille une compétence à ton rythme, puis mets-toi en situation avec un examen blanc.',
                                )}
                            </p>
                        </div>
                        <div className="hidden shrink-0 sm:block">
                            <ArtIcon name="target" size={108} tone="blue" />
                        </div>
                    </div>
                    <div className="text-muted-foreground relative mt-5 flex flex-wrap gap-2 text-xs font-semibold">
                        <span className="border-border bg-background/70 inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5">
                            <Layers3 className="h-3.5 w-3.5" aria-hidden="true" />
                            {t('practice.skill_count', { count: exam.sections.length, defaultValue: '{{count}} compétences' })}
                        </span>
                        <span className="border-border bg-background/70 inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5">
                            <CheckCircle2 className="h-3.5 w-3.5" aria-hidden="true" />
                            {t('practice.section_attempts', { count: attempts, defaultValue: '{{count}} tentatives' })}
                        </span>
                    </div>
                </section>

                {flash?.error && (
                    <div
                        role="alert"
                        className="flex items-start gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"
                    >
                        <Info className="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                        <div>
                            <p className="text-sm font-bold">{flash.error}</p>
                            <p className="mt-1 text-xs leading-relaxed opacity-80">
                                {t(
                                    'practice.generation_error_hint',
                                    'Tu peux choisir un autre format ou réessayer un peu plus tard. Ta progression est conservée.',
                                )}
                            </p>
                        </div>
                    </div>
                )}
                {flash?.success && (
                    <p
                        role="status"
                        className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100"
                    >
                        {flash.success}
                    </p>
                )}

                <section aria-labelledby="practice-skills-title">
                    <div className="mb-4">
                        <h2 id="practice-skills-title" className="text-foreground text-lg font-extrabold">
                            {t('practice.by_skill', 'Quelle compétence veux-tu travailler ?')}
                        </h2>
                        <p className="text-muted-foreground mt-1 text-sm">{t('practice.by_skill_hint', 'Choisis ton objectif du moment.')}</p>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {exam.sections.map((section) => {
                            const skill = skills[section.skill_type] ?? skills.reading;
                            const count = sectionProgress[section.id] ?? 0;
                            return (
                                <Link
                                    key={section.id}
                                    href={route('practice.section', [exam.id, section.id])}
                                    className="studio-card group border-border bg-card hover:border-primary/50 focus-visible:ring-ring flex gap-4 rounded-2xl border p-4 transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none sm:p-5"
                                >
                                    <ArtIcon name={skill.icon} size={58} tone={skill.tone} />
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-start justify-between gap-2">
                                            <h3 className="text-foreground text-base font-extrabold">{section.name}</h3>
                                            <ChevronRight
                                                className="text-muted-foreground mt-0.5 h-4 w-4 shrink-0 transition-transform motion-safe:group-hover:translate-x-1"
                                                aria-hidden="true"
                                            />
                                        </div>
                                        <p className="text-muted-foreground mt-1 text-xs leading-relaxed">
                                            {t(`practice.skill_description_${section.skill_type}`, skill.description)}
                                        </p>
                                        <div className="text-muted-foreground mt-3 flex flex-wrap gap-x-3 gap-y-1 text-[11px] font-semibold">
                                            {section.time_limit != null && section.time_limit > 0 && (
                                                <span className="inline-flex items-center gap-1">
                                                    <Clock3 className="h-3 w-3" aria-hidden="true" />
                                                    {section.time_limit} min
                                                </span>
                                            )}
                                            <span>{t('practice.section_exercise_types', { count: section.exercise_types?.length ?? 0 })}</span>
                                            {count > 0 && (
                                                <span className="text-emerald-700 dark:text-emerald-300">
                                                    {t('practice.section_attempts', { count, defaultValue: '{{count}} tentatives' })}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                    {exam.sections.length === 0 && (
                        <div className="studio-card border-border bg-card text-muted-foreground rounded-2xl border p-6 text-center text-sm">
                            {t(
                                'practice.no_sections',
                                'Les compétences de cet examen ne sont pas encore disponibles. Retrouve tes leçons dans ton parcours.',
                            )}
                        </div>
                    )}
                </section>

                <section className="studio-card border-border bg-card flex flex-col gap-4 rounded-2xl border p-5 sm:flex-row sm:items-center sm:p-6">
                    <div className="flex flex-1 items-center gap-4">
                        <ArtIcon name="clock" size={56} tone="amber" />
                        <div>
                            <p className="text-muted-foreground mb-1 text-[10px] font-bold tracking-widest uppercase">
                                {t('practice.test_yourself', 'Le grand entraînement')}
                            </p>
                            <h2 className="text-foreground font-extrabold">{t('practice.exam_mode_title')}</h2>
                            <p className="text-muted-foreground mt-1 text-xs">{t('practice.exam_mode_desc', { minutes: totalExamMinutes })}</p>
                        </div>
                    </div>
                    <Link
                        href={route('practice.simulate', exam.id)}
                        className="bg-primary text-primary-foreground focus-visible:ring-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-bold transition-opacity hover:opacity-90 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        {t('practice.exam_mode_start')}
                        <ArrowRight className="h-4 w-4" aria-hidden="true" />
                    </Link>
                </section>

                <section aria-labelledby="practice-personal-title">
                    <h2 id="practice-personal-title" className="text-foreground mb-3 text-lg font-extrabold">
                        {t('practice.personalized_title', 'Ton espace personnel')}
                    </h2>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <Link
                            href={route('errors.practice')}
                            className="studio-card group border-border bg-card hover:border-primary/50 focus-visible:ring-ring flex items-center gap-3 rounded-2xl border p-4 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                        >
                            <ArtIcon name="review" size={48} tone="rose" />
                            <div className="min-w-0 flex-1">
                                <h3 className="text-foreground text-sm font-bold">
                                    {t('practice.mistakes_review_title', 'Rebondir après une erreur')}
                                </h3>
                                <p className="text-muted-foreground mt-1 text-xs leading-relaxed">
                                    {t('practice.mistakes_review_desc', 'Reprends ce qui t’a posé problème et consolide tes acquis.')}
                                </p>
                            </div>
                            <ChevronRight className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        </Link>
                        <Link
                            href={route('dictionary.index')}
                            className="studio-card group border-border bg-card hover:border-primary/50 focus-visible:ring-ring flex items-center gap-3 rounded-2xl border p-4 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                        >
                            <ArtIcon name="vocabulary" size={48} tone="mint" />
                            <div className="min-w-0 flex-1">
                                <h3 className="text-foreground text-sm font-bold">{t('practice.dictionary_title', 'Mon dictionnaire')}</h3>
                                <p className="text-muted-foreground mt-1 text-xs leading-relaxed">
                                    {t('practice.dictionary_desc', 'Retrouve tes mots sauvegardés et révise-les régulièrement.')}
                                </p>
                            </div>
                            <ChevronRight className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        </Link>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
