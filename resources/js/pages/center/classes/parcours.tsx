import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Etape {
    id: number;
    numero: number;
    total: number;
    exercise_id: number;
    titre: string;
    consigne: string | null;
    seuil_reussite: number | null;
    type: string;
    competence: string | null;
    niveau: string | null;
    source: 'centre' | 'prepla';
    /** Faux quand l'exercice n'est plus servi aux élèves (autre examen, exercice retiré). */
    jouable: boolean;
}
interface ExerciceChoisissable {
    id: number;
    titre: string;
    type: string;
    competence: string | null;
    niveau: string | null;
    deja_pris: boolean;
}
interface Avancement {
    id: number;
    name: string;
    faites: number;
    total: number;
    actuelle: { numero: number; titre: string } | null;
}
interface Props {
    classroom: { id: number; name: string; level: string | null; exam: string | null };
    contenuCentre: boolean;
    peutChangerLeReglage: boolean;
    etapes: Etape[];
    avancement: Avancement[];
    banque: ExerciceChoisissable[];
    exercicesCentre: ExerciceChoisissable[];
    filtres: { q: string; competence: string; niveau: string };
    competences: { value: string; label: string }[];
    autresClasses: { id: number; name: string; etapes: number }[];
}

const NIVEAUX = ['A0', 'A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
const champ =
    'w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

/** Une étape du parcours : titre, consigne, seuil, et les gestes pour la ranger. */
function LigneEtape({ classroomId, etape, premiere, derniere }: { classroomId: number; etape: Etape; premiere: boolean; derniere: boolean }) {
    const [ouverte, setOuverte] = useState(false);
    const form = useForm({
        titre: etape.titre === etape.type ? '' : etape.titre,
        consigne: etape.consigne ?? '',
        seuil_reussite: etape.seuil_reussite ? String(etape.seuil_reussite) : '',
    });

    function deplacer(sens: 'haut' | 'bas') {
        router.post(route('center.classes.parcours.deplacer', [classroomId, etape.id]), { sens }, { preserveScroll: true });
    }
    function retirer() {
        if (confirm(`Retirer l'étape ${etape.numero} « ${etape.titre} » du parcours ?`)) {
            router.delete(route('center.classes.parcours.destroy', [classroomId, etape.id]), { preserveScroll: true });
        }
    }
    function enregistrer(e: React.FormEvent) {
        e.preventDefault();
        form.transform((d) => ({ ...d, seuil_reussite: d.seuil_reussite === '' ? null : Number(d.seuil_reussite) }));
        form.patch(route('center.classes.parcours.update', [classroomId, etape.id]), { preserveScroll: true, onSuccess: () => setOuverte(false) });
    }

    return (
        <li className="border-border rounded-lg border p-3">
            {/* Sur téléphone, les gestes passent sous le titre : à côté, ils ne lui laissaient presque rien. */}
            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:gap-3">
                <div className="flex min-w-0 flex-1 items-start gap-3">
                    <span className="bg-primary/10 text-primary mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold">
                        {etape.numero}
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="text-sm font-semibold break-words sm:truncate">{etape.titre}</p>
                        <p className="text-muted-foreground mt-0.5 flex flex-wrap gap-x-2 text-xs">
                            <span>{etape.type}</span>
                            {etape.competence && <span>· {etape.competence}</span>}
                            {etape.niveau && <span>· {etape.niveau}</span>}
                            <span>· {etape.source === 'centre' ? 'Exercice du centre' : 'Banque PrePla'}</span>
                            {etape.seuil_reussite && <span>· Réussite ≥ {etape.seuil_reussite} %</span>}
                        </p>
                        {etape.consigne && <p className="mt-1 text-xs">{etape.consigne}</p>}
                        {!etape.jouable && (
                            <p className="mt-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                                Vos élèves ne voient plus cette étape : l'exercice n'appartient pas à l'examen actuel de la classe, ou il a été
                                retiré. Retirez-la ou remplacez-la.
                            </p>
                        )}
                    </div>
                </div>
                <div className="flex shrink-0 items-center justify-end gap-0.5">
                    <Button
                        variant="ghost"
                        size="icon"
                        disabled={premiere}
                        onClick={() => deplacer('haut')}
                        aria-label={`Monter l'étape ${etape.numero}`}
                    >
                        <ArrowUp size={16} />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        disabled={derniere}
                        onClick={() => deplacer('bas')}
                        aria-label={`Descendre l'étape ${etape.numero}`}
                    >
                        <ArrowDown size={16} />
                    </Button>
                    <Button variant="ghost" size="sm" onClick={() => setOuverte((o) => !o)} aria-expanded={ouverte}>
                        {ouverte ? 'Fermer' : 'Modifier'}
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="text-rose-500 hover:text-rose-600"
                        onClick={retirer}
                        aria-label={`Retirer l'étape ${etape.numero}`}
                    >
                        <Trash2 size={16} />
                    </Button>
                </div>
            </div>
            {ouverte && (
                <form onSubmit={enregistrer} className="border-border mt-3 grid gap-3 border-t pt-3">
                    <div className="grid gap-3 sm:grid-cols-[1fr_9rem]">
                        <div className="grid gap-1.5">
                            <label htmlFor={`titre-${etape.id}`} className="text-xs font-semibold">
                                Titre (facultatif)
                            </label>
                            <input
                                id={`titre-${etape.id}`}
                                className={champ}
                                maxLength={120}
                                placeholder={etape.type}
                                value={form.data.titre}
                                onChange={(e) => form.setData('titre', e.target.value)}
                            />
                            <InputError message={form.errors.titre} />
                        </div>
                        <div className="grid gap-1.5">
                            <label htmlFor={`seuil-${etape.id}`} className="text-xs font-semibold">
                                Seuil de réussite
                            </label>
                            <input
                                id={`seuil-${etape.id}`}
                                type="number"
                                min={1}
                                max={100}
                                className={champ}
                                placeholder="Aucun"
                                value={form.data.seuil_reussite}
                                onChange={(e) => form.setData('seuil_reussite', e.target.value)}
                            />
                            <InputError message={form.errors.seuil_reussite} />
                        </div>
                    </div>
                    <div className="grid gap-1.5">
                        <label htmlFor={`consigne-${etape.id}`} className="text-xs font-semibold">
                            Consigne pour l'élève (facultatif)
                        </label>
                        <textarea
                            id={`consigne-${etape.id}`}
                            rows={2}
                            maxLength={2000}
                            className={champ}
                            value={form.data.consigne}
                            onChange={(e) => form.setData('consigne', e.target.value)}
                        />
                        <InputError message={form.errors.consigne} />
                    </div>
                    <p className="text-muted-foreground text-xs">
                        Sans seuil, l'étape est faite dès que l'élève a terminé l'exercice. Avec un seuil, il doit atteindre ce pourcentage de
                        réussite.
                    </p>
                    <div>
                        <Button type="submit" size="sm" disabled={form.processing}>
                            {form.processing ? 'Enregistrement…' : 'Enregistrer'}
                        </Button>
                    </div>
                </form>
            )}
        </li>
    );
}

/** Une ligne d'exercice à ajouter au parcours. */
function ExerciceAAjouter({ classroomId, exercice }: { classroomId: number; exercice: ExerciceChoisissable }) {
    const [envoi, setEnvoi] = useState(false);
    function ajouter() {
        router.post(
            route('center.classes.parcours.store', classroomId),
            { exercise_id: exercice.id },
            { preserveScroll: true, onStart: () => setEnvoi(true), onFinish: () => setEnvoi(false) },
        );
    }
    return (
        <li className="border-border flex items-center justify-between gap-3 rounded-lg border p-2.5 text-sm">
            <div className="min-w-0">
                <p className="truncate font-medium">{exercice.titre}</p>
                <p className="text-muted-foreground truncate text-xs">
                    {exercice.type}
                    {exercice.competence && ` · ${exercice.competence}`}
                    {exercice.niveau && ` · ${exercice.niveau}`}
                    {exercice.deja_pris && ' · déjà dans le parcours'}
                </p>
            </div>
            <Button variant="outline" size="sm" onClick={ajouter} disabled={envoi || exercice.deja_pris} className="shrink-0">
                {exercice.deja_pris ? (
                    'Déjà ajouté'
                ) : (
                    <>
                        <Plus size={14} /> Ajouter
                    </>
                )}
            </Button>
        </li>
    );
}

/**
 * Le parcours d'une classe, construit par le centre : la liste ordonnée des
 * étapes, de quoi en ajouter (exercices du centre ou banque PrePla de l'examen
 * de la classe), et l'avancement de chaque élève.
 */
export default function ParcoursDeClasse({
    classroom,
    contenuCentre,
    peutChangerLeReglage,
    etapes,
    avancement,
    banque,
    exercicesCentre,
    filtres,
    competences,
    autresClasses,
}: Props) {
    const { flash, errors } = usePage().props as { flash?: { success?: string }; errors?: Record<string, string> };
    const [source, setSource] = useState<'prepla' | 'centre'>('prepla');
    const [recherche, setRecherche] = useState(filtres);
    const [copieDepuis, setCopieDepuis] = useState('');

    function chercher(e?: React.FormEvent, valeurs = recherche) {
        e?.preventDefault();
        router.get(route('center.classes.parcours', classroom.id), valeurs, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['banque', 'filtres'],
        });
    }
    function copier() {
        const autre = autresClasses.find((c) => String(c.id) === copieDepuis);
        if (autre && confirm(`Ajouter les ${autre.etapes} étapes de « ${autre.name} » à la fin de ce parcours ?`)) {
            router.post(
                route('center.classes.parcours.copier', classroom.id),
                { source_id: autre.id },
                { preserveScroll: true, onSuccess: () => setCopieDepuis('') },
            );
        }
    }

    return (
        <AppLayout>
            <Head title={`Parcours · ${classroom.name}`} />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('center.classes.show', classroom.id)} className="text-muted-foreground hover:text-foreground text-sm">
                        ← {classroom.name}
                    </Link>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">Parcours de la classe</h1>
                    <p className="text-muted-foreground text-sm">
                        {classroom.level ?? 'Niveau libre'} · {classroom.exam ?? 'Examen par défaut'}
                    </p>
                </div>

                {!contenuCentre && (
                    <div className="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                        Votre espace utilise le <strong>parcours PrePla par défaut</strong> : vos élèves ne voient pas encore ce parcours.{' '}
                        {peutChangerLeReglage ? (
                            <Link href={`${route('center.personnalisation.edit')}#contenu-eleves`} className="font-semibold underline">
                                Passer au « Parcours créé par le centre »
                            </Link>
                        ) : (
                            'Le responsable de l’espace peut passer au « Parcours créé par le centre ».'
                        )}
                    </div>
                )}

                {flash?.success && (
                    <div className="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                        {flash.success}
                    </div>
                )}
                <InputError message={errors?.exercise_id} />

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Étapes ({etapes.length})</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {etapes.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Aucune étape pour le moment. Ajoutez des exercices ci-dessous, dans l'ordre où vos élèves doivent les faire.
                            </p>
                        ) : (
                            <ol className="space-y-2">
                                {etapes.map((etape, i) => (
                                    <LigneEtape
                                        key={etape.id}
                                        classroomId={classroom.id}
                                        etape={etape}
                                        premiere={i === 0}
                                        derniere={i === etapes.length - 1}
                                    />
                                ))}
                            </ol>
                        )}
                        {autresClasses.length > 0 && (
                            <div className="border-border flex flex-wrap items-end gap-2 border-t pt-3">
                                <div className="grid min-w-[12rem] flex-1 gap-1.5">
                                    <label htmlFor="copier-depuis" className="text-xs font-semibold">
                                        Copier le parcours d'une autre classe
                                    </label>
                                    <select id="copier-depuis" className={champ} value={copieDepuis} onChange={(e) => setCopieDepuis(e.target.value)}>
                                        <option value="">Choisir une classe</option>
                                        {autresClasses.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.name} ({c.etapes} étape{c.etapes > 1 ? 's' : ''})
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <Button variant="outline" onClick={copier} disabled={!copieDepuis}>
                                    Copier
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Ajouter une étape</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div role="tablist" aria-label="Origine des exercices" className="border-border inline-flex rounded-lg border p-0.5 text-sm">
                            {(['prepla', 'centre'] as const).map((s) => (
                                <button
                                    key={s}
                                    role="tab"
                                    aria-selected={source === s}
                                    onClick={() => setSource(s)}
                                    className={`rounded-md px-3 py-1.5 ${source === s ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'}`}
                                >
                                    {s === 'prepla' ? 'Banque PrePla' : `Exercices du centre (${exercicesCentre.length})`}
                                </button>
                            ))}
                        </div>

                        {source === 'prepla' ? (
                            <>
                                <form onSubmit={chercher} className="grid gap-2 sm:grid-cols-[1fr_11rem_7rem_auto]">
                                    <input
                                        aria-label="Rechercher un exercice"
                                        placeholder="Rechercher (titre, format)"
                                        className={champ}
                                        value={recherche.q}
                                        onChange={(e) => setRecherche({ ...recherche, q: e.target.value })}
                                    />
                                    <select
                                        aria-label="Compétence"
                                        className={champ}
                                        value={recherche.competence}
                                        onChange={(e) => chercher(undefined, { ...recherche, competence: e.target.value })}
                                    >
                                        <option value="">Toutes compétences</option>
                                        {competences.map((c) => (
                                            <option key={c.value} value={c.value}>
                                                {c.label}
                                            </option>
                                        ))}
                                    </select>
                                    <select
                                        aria-label="Niveau"
                                        className={champ}
                                        value={recherche.niveau}
                                        onChange={(e) => chercher(undefined, { ...recherche, niveau: e.target.value })}
                                    >
                                        <option value="">Tous niveaux</option>
                                        {NIVEAUX.map((n) => (
                                            <option key={n} value={n}>
                                                {n}
                                            </option>
                                        ))}
                                    </select>
                                    <Button type="submit" variant="outline">
                                        Chercher
                                    </Button>
                                </form>
                                <p className="text-muted-foreground text-xs">
                                    Les exercices PrePla de l'examen de la classe{classroom.exam ? ` (${classroom.exam})` : ''}, au niveau de la
                                    classe par défaut.
                                </p>
                                {banque.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">Aucun exercice ne correspond.</p>
                                ) : (
                                    <ul className="space-y-2">
                                        {banque.map((e) => (
                                            <ExerciceAAjouter key={e.id} classroomId={classroom.id} exercice={e} />
                                        ))}
                                    </ul>
                                )}
                            </>
                        ) : exercicesCentre.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Votre centre n'a pas encore d'exercice.{' '}
                                <Link href={route('center.exercises.create')} className="text-primary font-semibold hover:underline">
                                    Créer un exercice
                                </Link>
                            </p>
                        ) : (
                            <ul className="space-y-2">
                                {exercicesCentre.map((e) => (
                                    <ExerciceAAjouter key={e.id} classroomId={classroom.id} exercice={e} />
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Avancement des élèves</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {avancement.length === 0 && <p className="text-muted-foreground text-sm">Aucun élève dans cette classe.</p>}
                        {avancement.map((a) => (
                            <div
                                key={a.id}
                                className="border-border flex flex-wrap items-center justify-between gap-x-4 gap-y-1 rounded-lg border p-2.5 text-sm"
                            >
                                <Link href={route('center.students.show', a.id)} className="min-w-0 flex-1 truncate font-medium hover:underline">
                                    {a.name}
                                </Link>
                                <span className="text-muted-foreground text-xs">
                                    {a.total === 0 ? '—' : a.actuelle ? `Étape ${a.actuelle.numero} : ${a.actuelle.titre}` : 'Parcours terminé'}
                                </span>
                                <Badge variant={a.total > 0 && a.faites === a.total ? 'default' : 'secondary'} className="tabular-nums">
                                    {a.faites}/{a.total} étape{a.total > 1 ? 's' : ''}
                                </Badge>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
