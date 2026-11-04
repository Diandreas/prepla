import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, ClipboardList, Headphones, Mic, PenLine, Plus, Puzzle, Search, Sparkles, Trash2 } from 'lucide-react';
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
import { cn } from '@/lib/utils';

interface ExerciseRow extends LigneExercice {
    updated_at?: string | null;
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
    const [recherche, setRecherche] = useState('');
    const [examen, setExamen] = useState('');
    const [niveau, setNiveau] = useState('');
    const [competence, setCompetence] = useState('');

    const { niveaux, competences } = useMemo(() => optionsDeFiltre(exercises), [exercises]);
    const examensPresents = useMemo(() => exams.filter((e) => exercises.some((x) => x.exam_id === e.id)), [exams, exercises]);
    const visibles = useMemo(
        () => filtrerExercices(exercises, { recherche, examen, niveau, competence }),
        [exercises, recherche, examen, niveau, competence],
    );
    const filtresActifs = recherche !== '' || examen !== '' || niveau !== '' || competence !== '';

    // Creer et modifier etaient possibles, retirer ne l'etait pas : un brouillon
    // rate restait dans la liste pour toujours.
    function remove(exercise: ExerciseRow) {
        if (!confirm(`Supprimer « ${libelleExercice(exercise)} » ? Il reste conservé s’il est déjà donné en devoir ou déjà travaillé par un élève.`)) return;
        router.delete(route('center.exercises.destroy', exercise.id), { preserveScroll: true });
    }

    return (
        <AppLayout>
            <Head title="Contenu du centre" />
            <div className="mx-auto w-full max-w-5xl space-y-5 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Contenu</h1>
                        <p className="text-sm text-muted-foreground">Les exercices de votre centre, prêts à donner en devoir à vos classes.</p>
                    </div>
                    {exercises.length > 0 && (
                        <Button asChild>
                            <Link href={route('center.exercises.create')}><Plus className="h-4 w-4" aria-hidden="true" /> Créer un exercice</Link>
                        </Button>
                    )}
                </div>

                {exercises.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-2xl border-2 border-dashed border-border bg-card px-6 py-12 text-center">
                        <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <Sparkles className="h-7 w-7" aria-hidden="true" />
                        </span>
                        <h2 className="text-lg font-bold">Votre premier exercice en quelques minutes</h2>
                        <p className="max-w-md text-sm text-muted-foreground">
                            Choisissez l’examen, le niveau et le format : l’IA propose un brouillon conforme à l’épreuve, ou vous l’écrivez vous-même. Vous le voyez comme vos élèves avant de l’enregistrer.
                        </p>
                        <Button asChild size="lg" className="mt-1">
                            <Link href={route('center.exercises.create')}><Plus className="h-4 w-4" aria-hidden="true" /> Créer mon premier exercice</Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto_auto_auto]" role="search">
                            <label className="relative block">
                                <span className="sr-only">Rechercher un exercice</span>
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                                <input type="search" className={cn(filtre, 'w-full pl-9')} placeholder="Rechercher : titre, type, examen…" value={recherche} onChange={(e) => setRecherche(e.target.value)} />
                            </label>
                            <select aria-label="Filtrer par examen" className={filtre} value={examen} onChange={(e) => setExamen(e.target.value)}>
                                <option value="">Tous les examens</option>
                                {examensPresents.map((e) => <option key={e.id} value={e.id}>{e.name}</option>)}
                            </select>
                            <select aria-label="Filtrer par niveau" className={filtre} value={niveau} onChange={(e) => setNiveau(e.target.value)}>
                                <option value="">Tous les niveaux</option>
                                {niveaux.map((n) => <option key={n} value={n}>{n}</option>)}
                            </select>
                            <select aria-label="Filtrer par compétence" className={filtre} value={competence} onChange={(e) => setCompetence(e.target.value)}>
                                <option value="">Toutes les compétences</option>
                                {competences.map((c) => <option key={c.cle} value={c.cle}>{c.nom}</option>)}
                            </select>
                        </div>

                        <p className="text-sm text-muted-foreground" aria-live="polite">
                            {visibles.length} exercice{visibles.length > 1 ? 's' : ''}{filtresActifs ? ` sur ${exercises.length}` : ''}
                            {filtresActifs && (
                                <button type="button" className="ml-2 font-semibold text-primary hover:underline" onClick={() => { setRecherche(''); setExamen(''); setNiveau(''); setCompetence(''); }}>
                                    Effacer les filtres
                                </button>
                            )}
                        </p>

                        {visibles.length === 0 ? (
                            <p className="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">Aucun exercice ne correspond à ces filtres.</p>
                        ) : (
                            <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {visibles.map((e) => {
                                    const comp = competenceDe(e.skill_type);
                                    const Icone = ICONES[comp.cle] ?? ClipboardList;
                                    const type = libelleDuType({ slug: e.type_slug, component_key: e.component_key, name: e.type });
                                    return (
                                        <li key={e.id} className="group relative flex flex-col rounded-2xl border border-border bg-card p-4 transition-colors hover:border-primary/60">
                                            <div className="flex items-start justify-between gap-2">
                                                <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                                                    <Icone className="h-4 w-4" aria-hidden="true" /> {comp.nom}
                                                </span>
                                                <span className={cn(
                                                    'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                                    e.is_ai_generated ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground',
                                                )}>
                                                    {e.is_ai_generated ? 'IA' : 'Manuel'}
                                                </span>
                                            </div>
                                            <Link href={route('center.exercises.edit', e.id)} className="mt-2 line-clamp-2 font-semibold after:absolute after:inset-0 after:rounded-2xl focus-visible:outline-none focus-visible:after:ring-2 focus-visible:after:ring-ring">
                                                {libelleExercice(e)}
                                            </Link>
                                            <p className="mt-0.5 text-sm text-muted-foreground">{type}</p>
                                            <div className="mt-auto flex flex-wrap items-center gap-1.5 pt-3 text-xs">
                                                {e.exam && <span className="rounded-md bg-muted px-1.5 py-0.5 font-medium">{e.exam}</span>}
                                                {e.difficulty && <span className="rounded-md bg-muted px-1.5 py-0.5 font-bold">{e.difficulty}</span>}
                                                <span className="text-muted-foreground">{e.questions_count} question{(e.questions_count ?? 0) > 1 ? 's' : ''}</span>
                                                <button
                                                    type="button"
                                                    onClick={() => remove(e)}
                                                    className="relative z-10 ml-auto inline-flex h-9 w-9 items-center justify-center rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:hover:bg-rose-950/40"
                                                    aria-label={`Supprimer « ${libelleExercice(e)} »`}
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
