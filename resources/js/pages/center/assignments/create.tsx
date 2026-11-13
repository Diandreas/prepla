import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import { Check, Library, Plus, Search, Sparkles } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import {
    cleDuDevoirEnCours,
    competenceDe,
    effacerObjet,
    ecrireObjet,
    filtrerExercices,
    libelleDuType,
    libelleExercice,
    lireObjet,
    optionsDeFiltre,
    stockageDuNavigateur,
    type LigneExercice,
} from '@/lib/createur-exercices';
import { useT } from '@/lib/i18n/traduire';
import { cn } from '@/lib/utils';

interface Classroom { id: number; name: string; exam_id: number | null; level: string | null }

const input = 'mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-base sm:text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';
const filtre = 'min-h-10 rounded-lg border border-border bg-background px-3 text-base sm:text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

type Formulaire = {
    classroom_id: string;
    title: string;
    instructions: string;
    due_at: string;
    exercise_ids: number[];
};

export default function AssignmentCreate({ classrooms, exercises, preselection = [] }: { classrooms: Classroom[]; exercises: LigneExercice[]; preselection?: number[] }) {
    const { t } = useT();
    const nomExercice = (e: LigneExercice) => (String(e.titre ?? '').trim() !== '' ? libelleExercice(e) : t(libelleExercice(e)));
    const page = usePage();
    const compte = (page.props as { auth?: { user?: { id?: number } | null } }).auth?.user?.id ?? null;
    const cle = cleDuDevoirEnCours(compte);

    const form = useForm<Formulaire>({
        classroom_id: classrooms[0]?.id ? String(classrooms[0].id) : '',
        title: '',
        instructions: '',
        due_at: '',
        exercise_ids: [...preselection],
    });

    const [onglet, setOnglet] = useState<'centre' | 'prepla'>('centre');
    const [recherche, setRecherche] = useState('');
    const [niveau, setNiveau] = useState('');
    const [competence, setCompetence] = useState('');
    const [vivier, setVivier] = useState<LigneExercice[]>([]);
    const [vivierEtat, setVivierEtat] = useState<'rien' | 'charge' | 'pret' | 'erreur'>('rien');
    // Les exercices du vivier deja coches, pour les afficher meme apres un
    // changement de classe ou de filtre.
    const [cochesDuVivier, setCochesDuVivier] = useState<Record<number, LigneExercice>>({});
    const restaure = useRef(false);

    // Retour du createur d'exercices : on reprend la saisie laissee en partant,
    // et l'exercice tout juste cree s'ajoute a ceux deja coches.
    useEffect(() => {
        if (restaure.current) return;
        restaure.current = true;
        const sauve = lireObjet(stockageDuNavigateur('sessionStorage'), cle);
        if (!sauve) return;
        const classe = classrooms.some((c) => String(c.id) === String(sauve.classroom_id)) ? String(sauve.classroom_id) : form.data.classroom_id;
        const ids = [...new Set([...(Array.isArray(sauve.exercise_ids) ? sauve.exercise_ids.map(Number) : []), ...preselection])];
        form.setData({
            classroom_id: classe,
            title: String(sauve.title ?? ''),
            instructions: String(sauve.instructions ?? ''),
            due_at: String(sauve.due_at ?? ''),
            exercise_ids: ids,
        });
        if (sauve.cochesDuVivier && typeof sauve.cochesDuVivier === 'object') setCochesDuVivier(sauve.cochesDuVivier);
        effacerObjet(stockageDuNavigateur('sessionStorage'), cle);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const classe = classrooms.find((c) => String(c.id) === form.data.classroom_id) ?? null;

    // La banque PrePla de l'examen et du niveau de la classe, chargee a la demande.
    useEffect(() => {
        if (onglet !== 'prepla' || !classe) return;
        let annule = false;
        setVivierEtat('charge');
        axios.get(route('center.assignments.vivier'), { params: { classroom_id: classe.id } })
            .then((res) => { if (!annule) { setVivier(res.data?.exercises ?? []); setVivierEtat('pret'); } })
            .catch(() => { if (!annule) setVivierEtat('erreur'); });
        return () => { annule = true; };
    }, [onglet, classe]);

    const liste = onglet === 'centre' ? exercises : vivier;
    const { niveaux, competences } = useMemo(() => optionsDeFiltre(liste), [liste]);
    const visibles = useMemo(() => filtrerExercices(liste, { recherche, niveau, competence }), [liste, recherche, niveau, competence]);
    const tous = useMemo(() => {
        const m = new Map<number, LigneExercice>();
        [...exercises, ...vivier, ...Object.values(cochesDuVivier)].forEach((e) => m.set(e.id, e));
        return m;
    }, [exercises, vivier, cochesDuVivier]);
    const coches = form.data.exercise_ids.map((id) => tous.get(id)).filter((e): e is LigneExercice => !!e);

    function toggleExercise(ex: LigneExercice) {
        const deja = form.data.exercise_ids.includes(ex.id);
        form.setData('exercise_ids', deja ? form.data.exercise_ids.filter((x) => x !== ex.id) : [...form.data.exercise_ids, ex.id]);
        if (ex.source === 'prepla') {
            setCochesDuVivier((c) => {
                const n = { ...c };
                if (deja) delete n[ex.id];
                else n[ex.id] = ex;
                return n;
            });
        }
    }

    function creerUnExercice() {
        // La saisie part avec nous, et revient au retour (sessionStorage : elle
        // ne survit pas a la fermeture de l'onglet, et reste sur cet appareil).
        ecrireObjet(stockageDuNavigateur('sessionStorage'), cle, { ...form.data, cochesDuVivier });
        router.visit(route('center.exercises.create', { retour: 'devoir' }));
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(route('center.assignments.store'), {
            onSuccess: () => effacerObjet(stockageDuNavigateur('sessionStorage'), cle),
        });
    }

    return (
        <AppLayout>
            <Head title={t('Nouveau devoir')} />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('center.assignments.index')} className="text-sm text-muted-foreground hover:text-foreground">{t('← Devoirs')}</Link>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">{t('Nouveau devoir')}</h1>
                </div>

                <form onSubmit={submit} className="space-y-5">
                    <Card>
                        <CardHeader><CardTitle className="text-base">{t('Détails')}</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label htmlFor="classe" className="text-sm font-medium">{t('Classe')}</label>
                                    <select id="classe" className={input} value={form.data.classroom_id} onChange={(e) => form.setData('classroom_id', e.target.value)}>
                                        {classrooms.map((c) => (<option key={c.id} value={c.id}>{c.name}{c.level ? ` · ${c.level}` : ''}</option>))}
                                    </select>
                                    {form.errors.classroom_id && <p className="mt-1 text-xs text-rose-500">{form.errors.classroom_id}</p>}
                                </div>
                                <div>
                                    <label htmlFor="echeance" className="text-sm font-medium">{t('Échéance (facultatif)')}</label>
                                    <input id="echeance" type="date" className={input} value={form.data.due_at} onChange={(e) => form.setData('due_at', e.target.value)} />
                                </div>
                            </div>
                            <div>
                                <label htmlFor="titre" className="text-sm font-medium">{t('Titre')}</label>
                                <input id="titre" className={input} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} placeholder={t('Ex. : Révisions Lesen Teil 2')} />
                                {form.errors.title && <p className="mt-1 text-xs text-rose-500">{form.errors.title}</p>}
                            </div>
                            <div>
                                <label htmlFor="consigne" className="text-sm font-medium">{t('Consigne (facultatif)')}</label>
                                <textarea id="consigne" className={`min-h-[60px] ${input}`} value={form.data.instructions} onChange={(e) => form.setData('instructions', e.target.value)} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="space-y-3">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <CardTitle className="text-base">{form.data.exercise_ids.length > 1 ? t('Exercices ({n} sélectionnés)', { n: form.data.exercise_ids.length }) : t('Exercices ({n} sélectionné)', { n: form.data.exercise_ids.length })}</CardTitle>
                                <Button type="button" variant="outline" size="sm" onClick={creerUnExercice}>
                                    <Plus className="h-4 w-4" aria-hidden="true" /> {t('Créer un exercice')}
                                </Button>
                            </div>
                            {coches.length > 0 && (
                                <ul className="flex flex-wrap gap-1.5" aria-label={t('Exercices choisis')}>
                                    {coches.map((e) => (
                                        <li key={e.id}>
                                            <button type="button" onClick={() => toggleExercise(e)} className="inline-flex max-w-[16rem] items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary hover:bg-primary/20" aria-label={t('Retirer « {nom} »', { nom: nomExercice(e) })}>
                                                <span className="truncate">{nomExercice(e)}</span> ×
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                            <div className="flex gap-1 rounded-xl bg-muted p-1" role="tablist" aria-label={t('Origine des exercices')}>
                                {([['centre', 'Exercices du centre', Sparkles], ['prepla', 'Banque PrePla', Library]] as const).map(([cleOnglet, nom, Icone]) => (
                                    <button
                                        key={cleOnglet}
                                        type="button"
                                        role="tab"
                                        aria-selected={onglet === cleOnglet}
                                        onClick={() => { setOnglet(cleOnglet); setNiveau(''); setCompetence(''); }}
                                        className={cn('inline-flex min-h-10 flex-1 items-center justify-center gap-1.5 rounded-lg text-sm font-semibold transition-colors', onglet === cleOnglet ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground')}
                                    >
                                        <Icone className="h-4 w-4" aria-hidden="true" /> {t(nom)}
                                    </button>
                                ))}
                            </div>
                            <div className="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto_auto]">
                                <label className="relative block">
                                    <span className="sr-only">{t('Rechercher')}</span>
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                                    <input type="search" className={cn(filtre, 'w-full pl-9')} placeholder={t('Rechercher un exercice…')} value={recherche} onChange={(e) => setRecherche(e.target.value)} />
                                </label>
                                <select aria-label={t('Filtrer par compétence')} className={filtre} value={competence} onChange={(e) => setCompetence(e.target.value)}>
                                    <option value="">{t('Toutes les compétences')}</option>
                                    {competences.map((c) => <option key={c.cle} value={c.cle}>{t(c.nom)}</option>)}
                                </select>
                                <select aria-label={t('Filtrer par niveau')} className={filtre} value={niveau} onChange={(e) => setNiveau(e.target.value)}>
                                    <option value="">{t('Tous les niveaux')}</option>
                                    {niveaux.map((n) => <option key={n} value={n}>{n}</option>)}
                                </select>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-2" role="tabpanel">
                            {onglet === 'prepla' && (
                                <p className="text-xs text-muted-foreground">
                                    {classe?.level
                                        ? t('Exercices PrePla de l’examen de la classe, niveau {niveau}. Ils restent dans la banque : vos élèves les font comme les vôtres.', { niveau: classe.level })
                                        : t('Exercices PrePla de l’examen de la classe. Ils restent dans la banque : vos élèves les font comme les vôtres.')}
                                </p>
                            )}
                            {onglet === 'prepla' && vivierEtat === 'charge' && <p className="text-sm text-muted-foreground" aria-live="polite">{t('Chargement de la banque…')}</p>}
                            {onglet === 'prepla' && vivierEtat === 'erreur' && <p role="alert" className="text-sm text-rose-600 dark:text-rose-400">{t('La banque n’a pas pu être chargée. Réessayez dans un instant.')}</p>}
                            {onglet === 'centre' && exercises.length === 0 && (
                                <p className="text-sm text-muted-foreground">{t('Votre centre n’a pas encore d’exercice. Créez-en un, ou piochez dans la banque PrePla.')}</p>
                            )}
                            {onglet === 'prepla' && vivierEtat === 'pret' && vivier.length === 0 && (
                                <p className="text-sm text-muted-foreground">{t('Aucun exercice PrePla pour l’examen et le niveau de cette classe.')}</p>
                            )}
                            {liste.length > 0 && visibles.length === 0 && <p className="text-sm text-muted-foreground">{t('Aucun exercice ne correspond à ces filtres.')}</p>}
                            <ul className="space-y-2">
                                {visibles.map((ex) => {
                                    const choisi = form.data.exercise_ids.includes(ex.id);
                                    return (
                                        <li key={ex.id}>
                                            <label className={cn('flex cursor-pointer items-start gap-3 rounded-xl border-[1.5px] p-3 text-sm transition-colors', choisi ? 'border-primary bg-primary/5' : 'border-border hover:bg-muted/40')}>
                                                <input type="checkbox" className="mt-0.5 h-5 w-5 shrink-0 accent-primary" checked={choisi} onChange={() => toggleExercise(ex)} />
                                                <span className="min-w-0 flex-1">
                                                    <span className="block font-semibold">{nomExercice(ex)}</span>
                                                    <span className="mt-0.5 flex flex-wrap gap-x-2 gap-y-0.5 text-xs text-muted-foreground">
                                                        <span>{t(libelleDuType({ slug: ex.type_slug, component_key: ex.component_key, name: ex.type }))}</span>
                                                        <span>· {t(competenceDe(ex.skill_type).nom)}</span>
                                                        {ex.difficulty && <span>· {ex.difficulty}</span>}
                                                        <span>· {(ex.questions_count ?? 0) > 1 ? t('{n} questions', { n: ex.questions_count }) : t('{n} question', { n: ex.questions_count })}</span>
                                                    </span>
                                                </span>
                                                {choisi && <Check className="h-4 w-4 shrink-0 text-primary" aria-hidden="true" />}
                                            </label>
                                        </li>
                                    );
                                })}
                            </ul>
                            {form.errors.exercise_ids && <p role="alert" className="text-xs text-rose-500">{form.errors.exercise_ids}</p>}
                        </CardContent>
                    </Card>

                    <div className="sticky bottom-0 z-10 -mx-4 border-t border-border bg-background/95 px-4 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0">
                        <Button type="submit" className="w-full sm:w-auto" disabled={form.processing || form.data.exercise_ids.length === 0}>
                            {form.processing ? t('Création…') : t('Créer le devoir')}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
