import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, ClipboardList, Headphones, Mic, PenLine, Play, Plus, Puzzle, Search, Sparkles, Target, Trash2, Trophy } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import {
    competenceDe,
    filtrerExercices,
    libelleDuType,
    libelleExercice,
    optionsDeFiltre,
    type LigneExercice,
} from '@/lib/createur-exercices';
import { useT } from '@/lib/i18n/traduire';
import { cn } from '@/lib/utils';

interface ExerciseRow extends LigneExercice {
    updated_at?: string | null;
    /** Tentatives des eleves du centre (ou de ses classes, pour un enseignant). */
    joue?: number;
    eleves?: number;
    reussite?: number | null;
}

type Tri = 'recents' | 'joues' | 'difficiles';

/** Il faut quelques passages avant de dire qu'un exercice est difficile. */
const MIN_POUR_DIFFICULTE = 3;

function couleurReussite(p: number | null | undefined): string {
    if (p === null || p === undefined) return 'text-muted-foreground';
    if (p >= 75) return 'text-emerald-600 dark:text-emerald-400';
    if (p >= 50) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
}

function Chiffre({ icone: Icone, libelle, valeur, detail }: { icone: typeof BookOpen; libelle: string; valeur: React.ReactNode; detail?: React.ReactNode }) {
    return (
        <div className="min-w-0 rounded-xl border border-border bg-card p-3.5">
            <div className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                <Icone className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                <span className="truncate">{libelle}</span>
            </div>
            <div className="mt-1.5 min-w-0 text-2xl font-bold tracking-tight tabular-nums">{valeur}</div>
            {detail && <div className="mt-1 truncate text-xs text-muted-foreground">{detail}</div>}
        </div>
    );
}

const ICONES: Record<string, typeof BookOpen> = {
    reading: BookOpen,
    listening: Headphones,
    writing: PenLine,
    speaking: Mic,
    grammar: Puzzle,
    autre: ClipboardList,
};

const filtre = 'min-h-11 rounded-lg border border-border bg-background px-3 text-base sm:text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

export default function ExercisesIndex({ exercises, exams }: { exercises: ExerciseRow[]; exams: { id: number; name: string }[] }) {
    const { t } = useT();
    const nomExercice = (e: ExerciseRow) => (String(e.titre ?? '').trim() !== '' ? libelleExercice(e) : t(libelleExercice(e)));
    const [recherche, setRecherche] = useState('');
    const [examen, setExamen] = useState('');
    const [niveau, setNiveau] = useState('');
    const [competence, setCompetence] = useState('');

    const { niveaux, competences } = useMemo(() => optionsDeFiltre(exercises), [exercises]);
    const examensPresents = useMemo(() => exams.filter((e) => exercises.some((x) => x.exam_id === e.id)), [exams, exercises]);
    const [tri, setTri] = useState<Tri>('recents');
    const visibles = useMemo(() => {
        const liste = filtrerExercices(exercises, { recherche, examen, niveau, competence });
        if (tri === 'joues') return [...liste].sort((a, b) => (b.joue ?? 0) - (a.joue ?? 0));
        if (tri === 'difficiles') {
            const cle = (e: ExerciseRow) => ((e.joue ?? 0) >= MIN_POUR_DIFFICULTE && e.reussite != null ? e.reussite : 1000 - (e.joue ?? 0));
            return [...liste].sort((a, b) => cle(a) - cle(b));
        }
        return liste;
    }, [exercises, recherche, examen, niveau, competence, tri]);

    // Les chiffres du haut, sur tout le contenu du centre (pas les filtres).
    const chiffres = useMemo(() => {
        const ia = exercises.filter((e) => e.is_ai_generated).length;
        const parties = exercises.reduce((t, e) => t + (e.joue ?? 0), 0);
        const joues = exercises.filter((e) => (e.joue ?? 0) > 0).sort((a, b) => (b.joue ?? 0) - (a.joue ?? 0));
        const difficile = exercises
            .filter((e) => (e.joue ?? 0) >= MIN_POUR_DIFFICULTE && e.reussite != null)
            .sort((a, b) => (a.reussite ?? 0) - (b.reussite ?? 0))[0];
        return { ia, parties, plusJoues: joues.slice(0, 3), jamais: exercises.length - joues.length, difficile };
    }, [exercises]);
    const filtresActifs = recherche !== '' || examen !== '' || niveau !== '' || competence !== '';

    // Creer et modifier etaient possibles, retirer ne l'etait pas : un brouillon
    // rate restait dans la liste pour toujours.
    function remove(exercise: ExerciseRow) {
        if (!confirm(t('Supprimer « {nom} » ? Il reste conservé s’il est déjà donné en devoir ou déjà travaillé par un élève.', { nom: nomExercice(exercise) }))) return;
        router.delete(route('center.exercises.destroy', exercise.id), { preserveScroll: true });
    }

    return (
        <AppLayout>
            <Head title={t('Contenu du centre')} />
            <div className="mx-auto w-full max-w-5xl space-y-5 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{t('Contenu')}</h1>
                        <p className="text-sm text-muted-foreground">{t('Les exercices de votre centre, prêts à donner en devoir à vos classes.')}</p>
                    </div>
                    {exercises.length > 0 && (
                        <Button asChild>
                            <Link href={route('center.exercises.create')}><Plus className="h-4 w-4" aria-hidden="true" /> {t('Créer un exercice')}</Link>
                        </Button>
                    )}
                </div>

                {exercises.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-2xl border-2 border-dashed border-border bg-card px-6 py-12 text-center">
                        <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <Sparkles className="h-7 w-7" aria-hidden="true" />
                        </span>
                        <h2 className="text-lg font-bold">{t('Votre premier exercice en quelques minutes')}</h2>
                        <p className="max-w-md text-sm text-muted-foreground">
                            {t('Choisissez l’examen, le niveau et le format : l’IA propose un brouillon conforme à l’épreuve, ou vous l’écrivez vous-même. Vous le voyez comme vos élèves avant de l’enregistrer.')}
                        </p>
                        <Button asChild size="lg" className="mt-1">
                            <Link href={route('center.exercises.create')}><Plus className="h-4 w-4" aria-hidden="true" /> {t('Créer mon premier exercice')}</Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <section aria-label={t('Chiffres du contenu')} className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <Chiffre
                                icone={ClipboardList}
                                libelle={t('Exercices créés')}
                                valeur={exercises.length}
                                detail={t('dont {ia} avec l’IA · {main} à la main', { ia: chiffres.ia, main: exercises.length - chiffres.ia })}
                            />
                            <Chiffre
                                icone={Play}
                                libelle={t('Fois joués')}
                                valeur={chiffres.parties}
                                detail={chiffres.jamais > 0 ? (chiffres.jamais > 1 ? t('{n} jamais joués', { n: chiffres.jamais }) : t('{n} jamais joué', { n: chiffres.jamais })) : t('tous déjà joués')}
                            />
                            <div className="col-span-2 min-w-0 rounded-xl border border-border bg-card p-3.5 lg:col-span-1">
                                <div className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                    <Trophy className="h-3.5 w-3.5 shrink-0" aria-hidden="true" /> {t('Les plus joués')}
                                </div>
                                {chiffres.plusJoues.length === 0 ? (
                                    <p className="mt-1.5 text-sm text-muted-foreground">{t('Pas encore joués par vos élèves.')}</p>
                                ) : (
                                    <ol className="mt-1.5 space-y-1">
                                        {chiffres.plusJoues.map((e, i) => (
                                            <li key={e.id} className="flex items-center justify-between gap-2 text-sm">
                                                <Link href={route('center.exercises.edit', e.id)} className="min-w-0 truncate hover:underline">
                                                    <span className="text-muted-foreground">{i + 1}.</span> {nomExercice(e)}
                                                </Link>
                                                <span className="shrink-0 text-xs tabular-nums text-muted-foreground">{e.joue}×</span>
                                            </li>
                                        ))}
                                    </ol>
                                )}
                            </div>
                            <div className="col-span-2 min-w-0 rounded-xl border border-border bg-card p-3.5 lg:col-span-1">
                                <div className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                    <Target className="h-3.5 w-3.5 shrink-0" aria-hidden="true" /> {t('Le plus difficile')}
                                </div>
                                {chiffres.difficile ? (
                                    <>
                                        <Link href={route('center.exercises.edit', chiffres.difficile.id)} className="mt-1.5 line-clamp-2 text-sm font-semibold hover:underline">
                                            {nomExercice(chiffres.difficile)}
                                        </Link>
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            <span className={cn('font-semibold', couleurReussite(chiffres.difficile.reussite))}>{Math.round(chiffres.difficile.reussite ?? 0)} %</span> {t('de réussite sur {n} passages', { n: chiffres.difficile.joue })}
                                        </p>
                                    </>
                                ) : (
                                    <p className="mt-1.5 text-sm text-muted-foreground">{t('Il faut {n} passages sur un exercice pour le dire.', { n: MIN_POUR_DIFFICULTE })}</p>
                                )}
                            </div>
                        </section>

                        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_auto_auto_auto_auto]" role="search">
                            <label className="relative block">
                                <span className="sr-only">{t('Rechercher un exercice')}</span>
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                                <input type="search" className={cn(filtre, 'w-full pl-9')} placeholder={t('Rechercher : titre, type, examen…')} value={recherche} onChange={(e) => setRecherche(e.target.value)} />
                            </label>
                            <select aria-label={t('Filtrer par examen')} className={filtre} value={examen} onChange={(e) => setExamen(e.target.value)}>
                                <option value="">{t('Tous les examens')}</option>
                                {examensPresents.map((e) => <option key={e.id} value={e.id}>{e.name}</option>)}
                            </select>
                            <select aria-label={t('Filtrer par niveau')} className={filtre} value={niveau} onChange={(e) => setNiveau(e.target.value)}>
                                <option value="">{t('Tous les niveaux')}</option>
                                {niveaux.map((n) => <option key={n} value={n}>{n}</option>)}
                            </select>
                            <select aria-label={t('Filtrer par compétence')} className={filtre} value={competence} onChange={(e) => setCompetence(e.target.value)}>
                                <option value="">{t('Toutes les compétences')}</option>
                                {competences.map((c) => <option key={c.cle} value={c.cle}>{t(c.nom)}</option>)}
                            </select>
                            <select aria-label={t('Trier les exercices')} className={filtre} value={tri} onChange={(e) => setTri(e.target.value as Tri)}>
                                <option value="recents">{t('Plus récents')}</option>
                                <option value="joues">{t('Plus joués')}</option>
                                <option value="difficiles">{t('Plus difficiles')}</option>
                            </select>
                        </div>

                        <p className="text-sm text-muted-foreground" aria-live="polite">
                            {filtresActifs
                                ? (visibles.length > 1 ? t('{n} exercices sur {total}', { n: visibles.length, total: exercises.length }) : t('{n} exercice sur {total}', { n: visibles.length, total: exercises.length }))
                                : (visibles.length > 1 ? t('{n} exercices', { n: visibles.length }) : t('{n} exercice', { n: visibles.length }))}
                            {filtresActifs && (
                                <button type="button" className="ml-2 font-semibold text-primary hover:underline" onClick={() => { setRecherche(''); setExamen(''); setNiveau(''); setCompetence(''); }}>
                                    {t('Effacer les filtres')}
                                </button>
                            )}
                        </p>

                        {visibles.length === 0 ? (
                            <p className="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">{t('Aucun exercice ne correspond à ces filtres.')}</p>
                        ) : (
                            <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {visibles.map((e) => {
                                    const comp = competenceDe(e.skill_type);
                                    const Icone = ICONES[comp.cle] ?? ClipboardList;
                                    const type = t(libelleDuType({ slug: e.type_slug, component_key: e.component_key, name: e.type }));
                                    return (
                                        <li key={e.id} className="group relative flex flex-col rounded-2xl border border-border bg-card p-4 transition-colors hover:border-primary/60">
                                            <div className="flex items-start justify-between gap-2">
                                                <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                                                    <Icone className="h-4 w-4" aria-hidden="true" /> {t(comp.nom)}
                                                </span>
                                                <span className={cn(
                                                    'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                                    e.is_ai_generated ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground',
                                                )}>
                                                    {e.is_ai_generated ? t('IA') : t('Manuel')}
                                                </span>
                                            </div>
                                            <Link href={route('center.exercises.edit', e.id)} className="mt-2 line-clamp-2 font-semibold after:absolute after:inset-0 after:rounded-2xl focus-visible:outline-none focus-visible:after:ring-2 focus-visible:after:ring-ring">
                                                {nomExercice(e)}
                                            </Link>
                                            <p className="mt-0.5 text-sm text-muted-foreground">{type}</p>
                                            <p className="mt-2 flex flex-wrap items-center gap-x-2 text-xs text-muted-foreground">
                                                {(e.joue ?? 0) > 0 ? (
                                                    <>
                                                        <span>{t('Joué')} <span className="font-semibold text-foreground tabular-nums">{e.joue}</span> {t('fois')}{e.eleves ? ` · ${e.eleves > 1 ? t('{n} élèves', { n: e.eleves }) : t('{n} élève', { n: e.eleves })}` : ''}</span>
                                                        {e.reussite != null && (
                                                            <span className={cn('font-semibold tabular-nums', couleurReussite(e.reussite))}>{t('{n} % de réussite', { n: Math.round(e.reussite) })}</span>
                                                        )}
                                                    </>
                                                ) : (
                                                    <span>{t('Pas encore joué')}</span>
                                                )}
                                            </p>
                                            <div className="mt-auto flex flex-wrap items-center gap-1.5 pt-3 text-xs">
                                                {e.exam && <span className="rounded-md bg-muted px-1.5 py-0.5 font-medium">{e.exam}</span>}
                                                {e.difficulty && <span className="rounded-md bg-muted px-1.5 py-0.5 font-bold">{e.difficulty}</span>}
                                                <span className="text-muted-foreground">{(e.questions_count ?? 0) > 1 ? t('{n} questions', { n: e.questions_count }) : t('{n} question', { n: e.questions_count })}</span>
                                                <button
                                                    type="button"
                                                    onClick={() => remove(e)}
                                                    className="relative z-10 ml-auto inline-flex h-9 w-9 items-center justify-center rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:hover:bg-rose-950/40"
                                                    aria-label={t('Supprimer « {nom} »', { nom: nomExercice(e) })}
                                                >
                                                    <Trash2 className="h-4 w-4" aria-hidden="true" />
                                                </button>
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
