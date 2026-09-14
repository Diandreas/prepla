import { ArtIcon } from '@/components/art-icon';
import AppLayout from '@/layouts/app-layout';
import type { ExamRecord, ExamSection, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CheckCircle2, Clock3, Info, LoaderCircle } from 'lucide-react';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface ExerciseTypeItem {
    id: number;
    name: string;
    skill_type: string;
    component_key: string;
}

interface Props {
    exam: ExamRecord;
    section: ExamSection;
    exerciseTypes: ExerciseTypeItem[];
}

const skills: Record<string, { icon: string; tone: 'blue' | 'mint' | 'amber' | 'rose' }> = {
    reading: { icon: 'book', tone: 'blue' },
    listening: { icon: 'listening', tone: 'mint' },
    writing: { icon: 'writing', tone: 'rose' },
    speaking: { icon: 'speaking', tone: 'amber' },
};

function exercisePresentation(component: string) {
    if (['mcq', 'picture-mcq', 'listen-choose-response', 'true-false-ng'].includes(component))
        return { icon: 'tasks', hint: 'Observe les propositions et choisis la bonne réponse.', key: 'choice' };
    if (['matching', 'multiple-matching', 'ordering', 'build-a-sentence'].includes(component))
        return { icon: 'puzzle', hint: 'Fais les bonnes associations et remets les idées en place.', key: 'matching' };
    if (['speaking-recorder', 'role-play', 'listen-repeat'].includes(component))
        return { icon: 'speaking', hint: 'Prépare tes idées, puis entraîne-toi à voix haute.', key: 'speaking' };
    if (['essay-editor', 'short-writing', 'guided-writing', 'academic-discussion', 'synthesis', 'graph-description'].includes(component))
        return { icon: 'writing', hint: 'Organise tes idées et rédige une réponse claire.', key: 'writing' };
    if (component === 'dictation') return { icon: 'listening', hint: 'Écoute attentivement et retranscris ce que tu entends.', key: 'dictation' };
    if (component === 'vocabulary-card') return { icon: 'vocabulary', hint: 'Enrichis ton vocabulaire, un mot à la fois.', key: 'vocabulary' };
    if (component.includes('completion') || ['gap-fill', 'open-cloze', 'complete-the-words', 'gapped-text'].includes(component))
        return { icon: 'file-edit', hint: 'Repère les indices et complète les éléments manquants.', key: 'completion' };
    return { icon: 'lightbulb', hint: 'Lis la consigne et mets tes connaissances en pratique.', key: 'default' };
}

export default function SectionDrills({ exam, section, exerciseTypes = [] }: Props) {
    const { t } = useTranslation();
    const { flash } = usePage<SharedData & { flash?: { error?: string } }>().props;
    const [launching, setLaunching] = useState<number | null>(null);
    const launchPending = useRef(false);
    const skill = skills[section.skill_type] ?? skills.reading;

    return (
        <AppLayout>
            <Head title={`${section.name} - ${exam.name}`} />
            <div className="studio-page mx-auto w-full max-w-5xl space-y-6 px-4 py-6 sm:px-6 sm:py-8">
                <Link
                    href={route('practice.exam', exam.id)}
                    className="text-muted-foreground hover:text-foreground focus-visible:ring-ring inline-flex min-h-10 items-center gap-2 rounded-lg text-sm font-semibold transition-colors focus-visible:ring-2 focus-visible:outline-none"
                >
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                    {t('practice.back_to_skills', 'Toutes les compétences')}
                </Link>

                <section className="studio-hero border-border rounded-3xl border p-5 sm:p-7">
                    <div className="flex items-center gap-4 sm:gap-6">
                        <ArtIcon name={skill.icon} size={72} tone={skill.tone} />
                        <div className="min-w-0">
                            <p className="studio-kicker text-muted-foreground mb-2 text-xs font-bold tracking-widest uppercase">{exam.name}</p>
                            <h1 className="text-foreground text-2xl font-black tracking-tight sm:text-3xl">{section.name}</h1>
                        </div>
                    </div>
                    <p className="text-muted-foreground mt-4 max-w-xl text-sm leading-relaxed">
                        {t(
                            'practice.drills_intro',
                            'Choisis un format pour t’entraîner. Un exercice sera sélectionné pour toi, ou préparé s’il n’est pas encore disponible.',
                        )}
                    </p>
                    <div className="text-muted-foreground mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs font-semibold">
                        <span className="inline-flex items-center gap-1.5">
                            <CheckCircle2 className="h-3.5 w-3.5" aria-hidden="true" />
                            {t('practice.section_exercise_types', { count: exerciseTypes.length })}
                        </span>
                        {section.time_limit != null && section.time_limit > 0 && (
                            <span className="inline-flex items-center gap-1.5">
                                <Clock3 className="h-3.5 w-3.5" aria-hidden="true" />
                                {t('practice.section_exam_duration', { minutes: section.time_limit, defaultValue: '{{minutes}} min à l’examen' })}
                            </span>
                        )}
                    </div>
                </section>

                {flash?.error && (
                    <div
                        role="alert"
                        className="flex gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"
                    >
                        <Info className="h-5 w-5 shrink-0" aria-hidden="true" />
                        <p>{flash.error}</p>
                    </div>
                )}

                {exerciseTypes.length === 0 ? (
                    <div className="studio-card border-border bg-card flex flex-col items-center rounded-3xl border px-6 py-10 text-center">
                        <ArtIcon name="courses" size={76} tone="blue" />
                        <h2 className="text-foreground mt-5 text-lg font-extrabold">
                            {t('practice.no_types', 'Cette compétence se prépare encore')}
                        </h2>
                        <p className="text-muted-foreground mt-2 max-w-md text-sm leading-relaxed">
                            {t(
                                'practice.no_types_hint',
                                'Aucun exercice n’est disponible ici pour le moment. Tu peux continuer avec une autre compétence.',
                            )}
                        </p>
                        <Link
                            href={route('practice.exam', exam.id)}
                            className="bg-primary text-primary-foreground mt-5 inline-flex min-h-11 items-center gap-2 rounded-xl px-5 py-3 text-sm font-bold"
                        >
                            {t('practice.other_skill', 'Choisir une autre compétence')}
                            <ArrowRight className="h-4 w-4" aria-hidden="true" />
                        </Link>
                    </div>
                ) : (
                    <section aria-labelledby="drill-formats-title">
                        <h2 id="drill-formats-title" className="text-foreground mb-4 text-lg font-extrabold">
                            {t('practice.exercise_formats', 'À toi de choisir')}
                        </h2>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {exerciseTypes.map((type) => {
                                const presentation = exercisePresentation(type.component_key);
                                const busy = launching === type.id;
                                return (
                                    <Link
                                        key={type.id}
                                        href={route('practice.drill.type', [exam.id, type.id])}
                                        onBefore={() => {
                                            if (launchPending.current) return false;
                                            launchPending.current = true;
                                            setLaunching(type.id);
                                        }}
                                        onFinish={() => {
                                            launchPending.current = false;
                                            setLaunching(null);
                                        }}
                                        aria-disabled={launching !== null}
                                        aria-busy={busy}
                                        className={`studio-card group bg-card focus-visible:ring-ring flex h-full flex-col rounded-2xl border p-5 transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none ${busy ? 'border-primary ring-primary/20 ring-1' : 'border-border hover:border-primary/50'} ${launching !== null && !busy ? 'opacity-60' : ''}`}
                                    >
                                        <div className="mb-4 flex items-center justify-between gap-3">
                                            <ArtIcon name={presentation.icon} size={52} tone={skill.tone} />
                                            {busy ? (
                                                <LoaderCircle
                                                    className="text-primary h-5 w-5 animate-spin motion-reduce:animate-none"
                                                    aria-hidden="true"
                                                />
                                            ) : (
                                                <ArrowRight
                                                    className="text-muted-foreground h-4 w-4 transition-transform motion-safe:group-hover:translate-x-1"
                                                    aria-hidden="true"
                                                />
                                            )}
                                        </div>
                                        <h3 className="text-foreground text-sm leading-snug font-extrabold">{type.name}</h3>
                                        <p className="text-muted-foreground mt-2 flex-1 text-xs leading-relaxed">
                                            {t(`practice.format_hint_${presentation.key}`, presentation.hint)}
                                        </p>
                                        <span className="text-primary mt-4 text-xs font-bold">
                                            {busy
                                                ? t('practice.preparing_drill', 'Préparation en cours…')
                                                : t('practice.launch_drill', 'M’entraîner')}
                                        </span>
                                    </Link>
                                );
                            })}
                        </div>
                    </section>
                )}
                <p role="status" aria-live="polite" className="text-muted-foreground min-h-5 text-center text-xs leading-relaxed">
                    {launching !== null
                        ? t('practice.preparing_drill_hint', 'Ton exercice arrive. La préparation peut prendre quelques instants.')
                        : t('practice.practice_tip', 'Le bon rythme, c’est celui que tu peux garder. Un exercice aujourd’hui, un autre demain.')}
                </p>
            </div>
        </AppLayout>
    );
}
