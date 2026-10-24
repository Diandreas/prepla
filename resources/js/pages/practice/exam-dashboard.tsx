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
    learnerLevel: string;
    canSimulate: boolean;
    /** La durée officielle du niveau, quand l'examen en décrit une. */
    levelDuration?: number | null;
    levelIsOfficial?: boolean;
    exam: ExamRecord & { sections: ExamSection[] };
    sectionProgress: Record<number, number>;
    /** Ce qu'on propose de faire maintenant, et pourquoi. */
    prochain?: { pourquoi: string };
    /** Les parties de l'épreuve, dans l'ordre, telles que l'examen les nomme. */
    examParts?: ExamPart[];
    /** Les types qui ne reproduisent aucune partie de l'épreuve. */
    pedagogicalTypeIds?: number[];
}

/**
 * Une partie de l'épreuve — « Leseverstehen, Teil 1 » — telle que le candidat la
 * rencontre : son nom officiel, sa tâche, son nombre d'items et ses points.
 */
interface ExamPart {
    section_slug: string;
    section_name: string;
    skill_type: string | null;
    part: number | null;
    name: string | null;
    name_fr: string | null;
    task: string | null;
    items: number | null;
    item_range: string | null;
    points: number | null;
    source: string | null;
    exercise_type_id: number | null;
    /** Pourquoi cette partie ne s'ouvre pas encore, le cas échéant. */
    raison?: string | null;
}

const skills: Record<string, { icon: string; tone: 'blue' | 'mint' | 'amber' | 'rose'; description: string }> = {
    reading: { icon: 'book', tone: 'blue', description: 'Comprends les textes, repère les idées essentielles.' },
    listening: { icon: 'listening', tone: 'mint', description: 'Entraîne ton oreille et repère les informations utiles.' },
    writing: { icon: 'writing', tone: 'rose', description: 'Structure tes idées et trouve les mots justes.' },
    speaking: { icon: 'speaking', tone: 'amber', description: 'Prends la parole et gagne en aisance.' },
    // Les Sprachbausteine de telc sont un module de GRAMMAIRE : sans entrée ici,
    // ils héritaient de la description de la lecture — « comprends les textes ».
    grammar: { icon: 'writing', tone: 'blue', description: 'Travaille les structures et le choix des mots.' },
};

export default function ExamDashboard({
    exam,
    sectionProgress,
    learnerLevel,
    canSimulate,
    levelDuration,
    levelIsOfficial,
    examParts = [],
    pedagogicalTypeIds = [],
    prochain,
}: Props) {
    const isBeginner = ['A0', 'A1', 'A2'].includes(learnerLevel);
    const availableSections = exam.sections.filter(section => (section.exercise_types?.length ?? 0) > 0);
    const { t } = useTranslation();
    const { flash } = usePage<SharedData & { flash?: { error?: string; success?: string } }>().props;
    // La durée du niveau quand l'examen la publie : un examen peut annoncer un
    // temps COMMUN à deux modules — telc donne 90 minutes pour la lecture et les
    // Sprachbausteine ensemble — et la somme des modules la dépasserait alors.
    const totalExamMinutes =
        levelDuration ?? (exam.sections.reduce((total, section) => total + (section.time_limit ?? 0), 0) || 180);
    const attempts = Object.values(sectionProgress).reduce((total, count) => total + count, 0);
    // Les parties de l'épreuve, regroupées par module et dans l'ordre. C'est sous
    // ces noms que le candidat révise, et non sous nos noms de formats internes.
    const partsBySection = examParts.reduce<{ slug: string; name: string; skill: string | null; parts: ExamPart[] }[]>((groups, part) => {
        const last = groups[groups.length - 1];
        if (last && last.slug === part.section_slug) {
            last.parts.push(part);
        } else {
            groups.push({ slug: part.section_slug, name: part.section_name, skill: part.skill_type, parts: [part] });
        }
        return groups;
    }, []);
    const allPartsOfficial = examParts.length > 0 && examParts.every((part) => part.source === 'officiel');
    const pedagogicalCount = pedagogicalTypeIds.length;

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
                                {isBeginner ? `Des exercices au niveau ${learnerLevel}, une compétence à la fois. Commence par ton parcours si tu préfères être guidé.` : t(
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
                            {t('practice.skill_count', { count: availableSections.length, defaultValue: '{{count}} compétences' })}
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

                {prochain && (
                    <section aria-labelledby="practice-continuer" className="border-primary/25 bg-primary/5 rounded-3xl border p-5">
                        <h2 id="practice-continuer" className="text-muted-foreground text-xs font-bold tracking-widest uppercase">
                            On continue
                        </h2>
                        <p className="text-foreground mt-2 text-base leading-relaxed font-bold">{prochain.pourquoi}</p>
                        <Link
                            href={route('practice.continuer', exam.id)}
                            className="bg-primary text-primary-foreground focus-visible:ring-ring mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl px-5 py-3.5 text-base font-extrabold transition-opacity hover:opacity-90 focus-visible:ring-2 focus-visible:outline-none sm:w-auto"
                        >
                            Commencer
                            <ArrowRight className="h-5 w-5" aria-hidden="true" />
                        </Link>
                        <p className="text-muted-foreground mt-3 text-xs leading-relaxed">
                            Tu peux aussi choisir toi-même, plus bas.
                        </p>
                    </section>
                )}

                {partsBySection.length > 0 && (
                    <section aria-labelledby="practice-exam-parts-title">
                        <div className="mb-4">
                            <h2 id="practice-exam-parts-title" className="text-foreground text-lg font-extrabold">
                                Les épreuves du {exam.name} &middot; {learnerLevel}
                            </h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                {allPartsOfficial
                                    ? "Chaque partie telle qu'elle tombe à l'examen, dans l'ordre, avec son nombre de questions et ses points."
                                    : "Chaque partie telle qu'elle tombe à l'examen, dans l'ordre. Le nombre de parties et les durées viennent des documents officiels de l'examen ; là où il ne publie pas le détail de chaque tâche, nous l'avons reconstitué, et nous n'annonçons alors ni nombre de questions ni points."}
                            </p>
                        </div>
                        <div className="space-y-4">
                            {partsBySection.map((group) => (
                                <div key={group.slug} className="border-border bg-card overflow-hidden rounded-2xl border">
                                    <div className="border-border bg-muted/40 flex items-center gap-2 border-b px-4 py-2.5">
                                        <ArtIcon
                                            name={(skills[group.skill ?? 'reading'] ?? skills.reading).icon}
                                            size={28}
                                            tone={(skills[group.skill ?? 'reading'] ?? skills.reading).tone}
                                        />
                                        <h3 className="text-foreground text-sm font-extrabold">{group.name}</h3>
                                        <span className="text-muted-foreground ml-auto text-[11px] font-semibold">
                                            {group.parts.length === 1 ? '1 partie' : group.parts.length + ' parties'}
                                        </span>
                                    </div>
                                    <ul className="divide-border divide-y">
                                        {group.parts.map((part, index) => {
                                            const label = part.name ?? 'Partie ' + (part.part ?? index + 1);
                                            const corps = (
                                                <>
                                                    <div className="flex items-baseline gap-2">
                                                        <span className="text-foreground text-sm font-bold">{label}</span>
                                                        {part.name_fr && <span className="text-muted-foreground text-xs">{part.name_fr}</span>}
                                                    </div>
                                                    {part.task && <p className="text-muted-foreground mt-1 text-xs leading-relaxed">{part.task}</p>}
                                                    <div className="text-muted-foreground mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px] font-semibold">
                                                        {part.items != null && (
                                                            <span>
                                                                {part.items === 1 ? '1 tâche' : part.items + ' questions'}
                                                                {part.item_range ? ' \u00b7 ' + part.item_range : ''}
                                                            </span>
                                                        )}
                                                        {part.points != null && <span>{part.points} points</span>}
                                                        {part.source === 'officiel' ? (
                                                            <span className="text-emerald-700 dark:text-emerald-300">détail officiel</span>
                                                        ) : part.source === 'architecture' ? (
                                                            <span className="text-amber-700 dark:text-amber-300">partie reconstituée</span>
                                                        ) : (
                                                            <span className="text-sky-700 dark:text-sky-300">tâche reconstituée</span>
                                                        )}
                                                        {!part.exercise_type_id && (
                                                            <span>{part.raison ?? 'entraînement pas encore disponible'}</span>
                                                        )}
                                                    </div>
                                                </>
                                            );

                                            return (
                                                <li key={part.section_slug + '-' + (part.part ?? index)}>
                                                    {part.exercise_type_id ? (
                                                        <Link
                                                            href={route('practice.drill.type', [exam.id, part.exercise_type_id]) + (part.part ? '?partie=' + part.part : '')}
                                                            className="group hover:bg-muted/40 focus-visible:ring-ring flex items-start gap-3 px-4 py-3 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                                                        >
                                                            <div className="min-w-0 flex-1">{corps}</div>
                                                            <ChevronRight
                                                                className="text-muted-foreground mt-1 h-4 w-4 shrink-0 transition-transform motion-safe:group-hover:translate-x-1"
                                                                aria-hidden="true"
                                                            />
                                                        </Link>
                                                    ) : (
                                                        <div className="px-4 py-3 opacity-70">{corps}</div>
                                                    )}
                                                </li>
                                            );
                                        })}
                                    </ul>
                                </div>
                            ))}
                        </div>
                    </section>
                )}

                <section aria-labelledby="practice-skills-title">
                    <div className="mb-4">
                        <h2 id="practice-skills-title" className="text-foreground text-lg font-extrabold">
                            {partsBySection.length > 0
                                ? 'Travailler une compétence, hors format d\u2019examen'
                                : t('practice.by_skill', 'Quelle compétence veux-tu travailler ?')}
                        </h2>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {partsBySection.length > 0
                                ? 'Des exercices pour progresser, qui ne reproduisent pas une partie de l\u2019épreuve'
                                  + (pedagogicalCount > 0 ? ' (' + pedagogicalCount + ' formats).' : '.')
                                : t('practice.by_skill_hint', 'Choisis ton objectif du moment.')}
                        </p>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {availableSections.map((section) => {
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
                                            {!isBeginner && section.time_limit != null && section.time_limit > 0 && (
                                                <span className="inline-flex items-center gap-1">
                                                    <Clock3 className="h-3 w-3" aria-hidden="true" />
                                                    Format de l’examen : {section.time_limit} min
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
                    {availableSections.length === 0 && (
                        <div className="studio-card border-border bg-card text-muted-foreground rounded-2xl border p-6 text-center text-sm">
                            {t(
                                'practice.no_sections',
                                'Les compétences de cet examen ne sont pas encore disponibles. Retrouve tes leçons dans ton parcours.',
                            )}
                        </div>
                    )}
                </section>

                {!canSimulate ? <section className="rounded-2xl border border-primary/15 bg-primary/5 p-4">
                    <h2 className="text-sm font-bold">D’abord les bases · niveau {learnerLevel}</h2>
                    <p className="mt-2 text-xs leading-relaxed text-muted-foreground">Travaille une compétence à la fois. Ton parcours alterne leçon, pratique et validation de palier ; l’examen complet n’est pas la prochaine étape.</p>
                    <Link href={route('dashboard')} className="mt-3 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-primary">Continuer mon parcours <ArrowRight size={16} aria-hidden="true" /></Link>
                </section> : <details open className="studio-card rounded-2xl border border-border bg-card p-4">
                <summary className="cursor-pointer text-sm font-semibold">{levelIsOfficial ? `Épreuve blanche complète · ${learnerLevel}` : `Entraînement au format · ${learnerLevel}`}</summary>
                <section className="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div className="flex flex-1 items-center gap-4">
                        <ArtIcon name="clock" size={56} tone="amber" />
                        <div>
                            <p className="text-muted-foreground mb-1 text-[10px] font-bold tracking-widest uppercase">
                                {isBeginner ? 'À ton rythme' : t('practice.test_yourself', 'Le grand entraînement')}
                            </p>
                            <h2 className="text-foreground font-extrabold">{isBeginner ? `Entraînement préparé au niveau ${learnerLevel}` : t('practice.exam_mode_title')}</h2>
                            <p className="text-muted-foreground mt-1 text-xs">{isBeginner ? 'Uniquement les sujets publiés pour ton niveau. Aucun résultat officiel de certification.' : `Durée de référence : ${totalExamMinutes} min. Le contenu disponible peut ne pas couvrir une épreuve complète.`}</p>
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
                </details>}

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
