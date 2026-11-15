import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { appel } from '@/lib/cours-api';
import { useT } from '@/lib/i18n/traduire';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowDown, ArrowUp, Bold, Copy, Eye, GripVertical, Heading2, Heading3, ImageIcon, Italic, Link2, List, ListOrdered, Plus, Trash2, Underline, Upload,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState, type ReactNode } from 'react';

/* eslint-disable @typescript-eslint/no-explicit-any */
interface Bloc { id: number; type: string; titre: string | null; obligatoire: boolean; contenu: any; exercice: { id: number; titre: string } | null }
interface Seance {
    id: number; titre: string; description: string | null; statut: 'brouillon' | 'publie'; obligatoire: boolean; blocs_ordre_impose: boolean;
    duree_minutes: number | null; deblocage: 'libre' | 'completion' | 'quiz' | 'date'; seuil_quiz: number | null;
    deblocage_at: string | null; debut_at: string | null; limite_at: string | null; blocs: Bloc[];
}
interface Module { id: number; titre: string; description: string | null; seances: Seance[] }
interface Parcours { id: number; titre: string; description: string | null; statut: 'brouillon' | 'publie'; classroom_id: number | null; modules: Module[] }
interface Media { id: number; type: string; titre: string; nom: string | null; url: string; drive: boolean }
interface Exo { id: number; titre: string; type: string | null; niveau: string | null }
interface Props {
    parcours: Parcours;
    classes: { id: number; name: string }[];
    medias: Media[];
    exercicesCentre: Exo[];
    banque: Exo[];
    types: { value: string; label: string }[];
    liveDisponible: boolean;
    examParDefaut: number | null;
    niveauParDefaut: string | null;
    seanceInitiale: number | null;
}

type Glisse = { kind: 'module' | 'seance' | 'bloc'; id: number } | null;

/** Ordre envoye au serveur : modules, seances, blocs. */
const ordreDe = (p: Parcours) => p.modules.map((m) => ({ id: m.id, seances: m.seances.map((s) => ({ id: s.id, blocs: s.blocs.map((b) => b.id) })) }));

function deplacerDans<T>(liste: T[], de: number, vers: number): T[] {
    const copie = [...liste];
    const [x] = copie.splice(de, 1);
    copie.splice(Math.max(0, Math.min(vers, copie.length)), 0, x);
    return copie;
}

/** Le createur de parcours : modules, seances et blocs, enregistrement automatique. */
export default function Editeur({ parcours: initial, classes, medias: mediasInitiaux, exercicesCentre, banque, types, liveDisponible, examParDefaut, niveauParDefaut, seanceInitiale }: Props) {
    const { t } = useT();
    const [p, setP] = useState<Parcours>(initial);
    const [medias, setMedias] = useState<Media[]>(mediasInitiaux);
    // Au retour du createur d'exercices, la seance d'ou l'on venait.
    const [choisie, setChoisie] = useState<number | null>(
        (seanceInitiale && initial.modules.some((m) => m.seances.some((s) => s.id === seanceInitiale)) ? seanceInitiale : null)
            ?? initial.modules[0]?.seances[0]?.id ?? null,
    );
    const [etat, setEtat] = useState<'ok' | 'en_cours' | 'erreur'>('ok');
    const [erreur, setErreur] = useState<string | null>(null);
    const minuteries = useRef<Record<string, number>>({});
    const enVol = useRef(0);
    const [glisse, setGlisse] = useState<Glisse>(null);

    // Ne pas quitter la page pendant un enregistrement.
    useEffect(() => {
        const avant = (e: BeforeUnloadEvent) => {
            if (Object.keys(minuteries.current).length || enVol.current) e.preventDefault();
        };
        window.addEventListener('beforeunload', avant);
        return () => window.removeEventListener('beforeunload', avant);
    }, []);

    const executer = useCallback(async (url: string, methode: 'POST' | 'PATCH' | 'DELETE', corps?: Record<string, unknown>, remplacer = true) => {
        enVol.current += 1;
        setEtat('en_cours');
        try {
            const r = await appel<{ parcours: Parcours }>(url, methode, corps);
            if (remplacer && r.parcours) setP(r.parcours);
            setErreur(null);
            setEtat('ok');
            return r;
        } catch (e) {
            setErreur((e as Error).message);
            setEtat('erreur');
            return null;
        } finally {
            enVol.current -= 1;
        }
    }, []);

    /** Enregistrement automatique : un envoi par champ, 700 ms apres la derniere frappe. */
    const plusTard = useCallback((cle: string, url: string, corps: Record<string, unknown>) => {
        window.clearTimeout(minuteries.current[cle]);
        setEtat('en_cours');
        minuteries.current[cle] = window.setTimeout(() => {
            delete minuteries.current[cle];
            executer(url, 'PATCH', corps, false);
        }, 700);
    }, [executer]);

    const majParcours = (partiel: Partial<Parcours>) => {
        setP((x) => ({ ...x, ...partiel }));
        plusTard(`p-${Object.keys(partiel).join()}`, `/center/cours/${p.id}`, partiel);
    };
    const majModule = (id: number, partiel: Partial<Module>) => {
        setP((x) => ({ ...x, modules: x.modules.map((m) => (m.id === id ? { ...m, ...partiel } : m)) }));
        plusTard(`m-${id}-${Object.keys(partiel).join()}`, `/center/cours/modules/${id}`, partiel);
    };
    const majSeance = (id: number, partiel: Partial<Seance>) => {
        setP((x) => ({ ...x, modules: x.modules.map((m) => ({ ...m, seances: m.seances.map((s) => (s.id === id ? { ...s, ...partiel } : s)) })) }));
        plusTard(`s-${id}-${Object.keys(partiel).join()}`, `/center/cours/seances/${id}`, partiel);
    };
    const majBloc = (id: number, partiel: Partial<Bloc>) => {
        setP((x) => ({
            ...x,
            modules: x.modules.map((m) => ({ ...m, seances: m.seances.map((s) => ({ ...s, blocs: s.blocs.map((b) => (b.id === id ? { ...b, ...partiel } : b)) })) })),
        }));
        plusTard(`b-${id}-${Object.keys(partiel).join()}`, `/center/cours/blocs/${id}`, partiel);
    };

    /**
     * Ouvre le createur d'exercices pour ce bloc : examen et niveau de la classe
     * proposes, tout reste modifiable. On attend d'abord la fin des
     * enregistrements automatiques, pour ne rien perdre en quittant la page.
     */
    const creerExercice = async (blocId: number) => {
        for (let i = 0; i < 60 && (Object.keys(minuteries.current).length || enVol.current); i++) {
            await new Promise((fin) => window.setTimeout(fin, 100));
        }
        const params = new URLSearchParams({ bloc: String(blocId) });
        if (examParDefaut) params.set('exam_id', String(examParDefaut));
        if (niveauParDefaut) params.set('difficulty', niveauParDefaut);
        router.visit(`/center/exercises/create?${params.toString()}`);
    };

    const reordonner = (nouveau: Parcours) => {
        setP(nouveau);
        executer(`/center/cours/${p.id}/ordre`, 'POST', { modules: ordreDe(nouveau) });
    };

    // ── Deplacements (glisser-deposer et boutons haut/bas) ──
    const deplacerModule = (id: number, vers: number) => {
        const i = p.modules.findIndex((m) => m.id === id);
        if (i < 0 || vers < 0 || vers >= p.modules.length) return;
        reordonner({ ...p, modules: deplacerDans(p.modules, i, vers) });
    };
    const deplacerSeance = (id: number, moduleCible: number, vers: number) => {
        let seance: Seance | undefined;
        const sans = p.modules.map((m) => {
            const s = m.seances.find((x) => x.id === id);
            if (s) seance = s;
            return { ...m, seances: m.seances.filter((x) => x.id !== id) };
        });
        if (!seance) return;
        const s = seance;
        reordonner({ ...p, modules: sans.map((m) => (m.id === moduleCible ? { ...m, seances: deplacerDans([...m.seances, s], m.seances.length, vers) } : m)) });
    };
    const deplacerBloc = (id: number, seanceCible: number, vers: number) => {
        let bloc: Bloc | undefined;
        const sans = p.modules.map((m) => ({
            ...m,
            seances: m.seances.map((s) => {
                const b = s.blocs.find((x) => x.id === id);
                if (b) bloc = b;
                return { ...s, blocs: s.blocs.filter((x) => x.id !== id) };
            }),
        }));
        if (!bloc) return;
        const b = bloc;
        reordonner({ ...p, modules: sans.map((m) => ({ ...m, seances: m.seances.map((s) => (s.id === seanceCible ? { ...s, blocs: deplacerDans([...s.blocs, b], s.blocs.length, vers) } : s)) })) });
    };
    const ou = (seanceId: number) => {
        for (const m of p.modules) {
            const i = m.seances.findIndex((s) => s.id === seanceId);
            if (i >= 0) return { module: m, index: i };
        }
        return null;
    };

    const seance = p.modules.flatMap((m) => m.seances).find((s) => s.id === choisie) ?? null;
    const toutesSeances = p.modules.flatMap((m) => m.seances);

    return (
        <AppLayout>
            <Head title={p.titre} />
            <div className="mx-auto w-full max-w-7xl space-y-4 p-4 md:p-6">
                {/* En-tete du parcours */}
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0 flex-1 space-y-2">
                        <Link href="/center/cours" className="text-sm text-muted-foreground hover:text-foreground">← {t('Parcours et cours')}</Link>
                        <input
                            aria-label={t('Titre du parcours')}
                            className="w-full rounded-md border-transparent bg-transparent px-1 text-2xl font-bold tracking-tight hover:border-input focus:border-input"
                            value={p.titre}
                            maxLength={160}
                            onChange={(e) => majParcours({ titre: e.target.value })}
                        />
                        <textarea
                            aria-label={t('Description du parcours')}
                            placeholder={t('Description (facultative)')}
                            rows={2}
                            className="w-full rounded-md border bg-background p-2 text-sm"
                            value={p.description ?? ''}
                            onChange={(e) => majParcours({ description: e.target.value || null })}
                        />
                    </div>
                    <div className="flex flex-col items-end gap-2">
                        <span className="text-xs text-muted-foreground" role="status" aria-live="polite">
                            {etat === 'en_cours' ? t('Enregistrement…') : etat === 'erreur' ? t('Non enregistré') : t('Enregistré')}
                        </span>
                        <div className="flex flex-wrap justify-end gap-2">
                            <select
                                aria-label={t('Classe du parcours')}
                                className="rounded-md border bg-background px-2 py-1.5 text-sm"
                                value={p.classroom_id ?? ''}
                                onChange={(e) => majParcours({ classroom_id: e.target.value ? Number(e.target.value) : null })}
                            >
                                <option value="">{t('Modèle réutilisable (sans classe)')}</option>
                                {classes.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                            </select>
                            <Button
                                variant={p.statut === 'publie' ? 'outline' : 'default'}
                                onClick={() => majParcours({ statut: p.statut === 'publie' ? 'brouillon' : 'publie' })}
                            >
                                {p.statut === 'publie' ? t('Repasser en brouillon') : t('Publier le parcours')}
                            </Button>
                            {p.classroom_id && <Button asChild variant="outline"><Link href={`/center/cours/${p.id}/suivi`}>{t('Suivi des élèves')}</Link></Button>}
                        </div>
                        <Dupliquer parcoursId={p.id} classes={classes} />
                    </div>
                </div>
                {p.statut === 'publie' && !p.classroom_id && (
                    <p className="rounded-md bg-muted p-2 text-xs text-muted-foreground">{t('Un modèle n’est vu par aucun élève : dupliquez-le vers une classe.')}</p>
                )}
                {erreur && <p className="rounded-md border border-destructive/40 bg-destructive/5 p-2 text-sm text-destructive" role="alert">{erreur}</p>}

                <div className="grid gap-4 lg:grid-cols-[320px_1fr]">
                    {/* Plan : modules et seances */}
                    <nav aria-label={t('Plan du parcours')} className="space-y-3">
                        {p.modules.map((m, mi) => (
                            <div
                                key={m.id}
                                className={`rounded-lg border bg-card p-2 ${glisse ? 'transition-colors' : ''}`}
                                onDragOver={(e) => glisse && e.preventDefault()}
                                onDrop={(e) => {
                                    e.preventDefault();
                                    if (glisse?.kind === 'module') deplacerModule(glisse.id, mi);
                                    if (glisse?.kind === 'seance') deplacerSeance(glisse.id, m.id, m.seances.length);
                                    setGlisse(null);
                                }}
                            >
                                <div className="flex items-center gap-1">
                                    <span draggable onDragStart={() => setGlisse({ kind: 'module', id: m.id })} onDragEnd={() => setGlisse(null)} className="cursor-grab text-muted-foreground" title={t('Glisser pour déplacer')}>
                                        <GripVertical className="size-4" />
                                    </span>
                                    <input aria-label={t('Titre du module')} className="min-w-0 flex-1 rounded border-transparent bg-transparent px-1 font-semibold hover:border-input focus:border-input" value={m.titre} maxLength={160} onChange={(e) => majModule(m.id, { titre: e.target.value })} />
                                    <BoutonIcone label={t('Monter le module')} onClick={() => deplacerModule(m.id, mi - 1)} disabled={mi === 0}><ArrowUp className="size-3.5" /></BoutonIcone>
                                    <BoutonIcone label={t('Descendre le module')} onClick={() => deplacerModule(m.id, mi + 1)} disabled={mi === p.modules.length - 1}><ArrowDown className="size-3.5" /></BoutonIcone>
                                    <BoutonIcone
                                        label={t('Supprimer le module')}
                                        onClick={() => window.confirm(t('Supprimer ce module et ses séances ?')) && executer(`/center/cours/modules/${m.id}`, 'DELETE')}
                                    >
                                        <Trash2 className="size-3.5" />
                                    </BoutonIcone>
                                </div>
                                <ul className="mt-1 space-y-1">
                                    {m.seances.map((s, si) => (
                                        <li
                                            key={s.id}
                                            draggable
                                            onDragStart={(e) => {
                                                e.stopPropagation();
                                                setGlisse({ kind: 'seance', id: s.id });
                                            }}
                                            onDragEnd={() => setGlisse(null)}
                                            onDragOver={(e) => glisse && glisse.kind !== 'module' && e.preventDefault()}
                                            onDrop={(e) => {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                if (glisse?.kind === 'seance') deplacerSeance(glisse.id, m.id, si);
                                                if (glisse?.kind === 'bloc') deplacerBloc(glisse.id, s.id, s.blocs.length);
                                                setGlisse(null);
                                            }}
                                            className={`flex items-center gap-1 rounded-md px-1 py-1 text-sm ${s.id === choisie ? 'bg-primary/10' : 'hover:bg-muted'}`}
                                        >
                                            <GripVertical className="size-3.5 shrink-0 cursor-grab text-muted-foreground" />
                                            <button type="button" onClick={() => setChoisie(s.id)} className="min-w-0 flex-1 truncate text-left" aria-current={s.id === choisie ? 'true' : undefined}>
                                                {s.titre}
                                            </button>
                                            {s.statut === 'brouillon' && <Badge variant="secondary" className="text-[10px]">{t('Brouillon')}</Badge>}
                                            <BoutonIcone label={t('Monter la séance')} onClick={() => si > 0 ? deplacerSeance(s.id, m.id, si - 1) : mi > 0 && deplacerSeance(s.id, p.modules[mi - 1].id, p.modules[mi - 1].seances.length)} disabled={si === 0 && mi === 0}><ArrowUp className="size-3" /></BoutonIcone>
                                            <BoutonIcone label={t('Descendre la séance')} onClick={() => si < m.seances.length - 1 ? deplacerSeance(s.id, m.id, si + 1) : mi < p.modules.length - 1 && deplacerSeance(s.id, p.modules[mi + 1].id, 0)} disabled={si === m.seances.length - 1 && mi === p.modules.length - 1}><ArrowDown className="size-3" /></BoutonIcone>
                                        </li>
                                    ))}
                                </ul>
                                <Button variant="ghost" size="sm" className="mt-1 w-full justify-start" onClick={() => executer(`/center/cours/modules/${m.id}/seances`, 'POST', {})}>
                                    <Plus className="size-3.5" /> {t('Ajouter une séance')}
                                </Button>
                            </div>
                        ))}
                        <Button variant="outline" className="w-full" onClick={() => executer(`/center/cours/${p.id}/modules`, 'POST', {})}>
                            <Plus className="size-4" /> {t('Ajouter un module')}
                        </Button>
                    </nav>

                    {/* La seance choisie */}
                    <div className="min-w-0">
                        {!seance ? (
                            <p className="rounded-lg border p-6 text-sm text-muted-foreground">{t('Choisissez ou ajoutez une séance.')}</p>
                        ) : (
                            <EditeurSeance
                                key={seance.id}
                                seance={seance}
                                estPremiere={toutesSeances[0]?.id === seance.id}
                                majSeance={majSeance}
                                majBloc={majBloc}
                                executer={executer}
                                deplacerBloc={deplacerBloc}
                                glisse={glisse}
                                setGlisse={setGlisse}
                                types={types}
                                medias={medias}
                                ajouterMedia={(m) => setMedias((x) => [m, ...x])}
                                parcoursId={p.id}
                                exercicesCentre={exercicesCentre}
                                banque={banque}
                                liveDisponible={liveDisponible}
                                creerExercice={creerExercice}
                                onSupprimee={() => {
                                    const o = ou(seance.id);
                                    const voisine = o?.module.seances[o.index - 1] ?? o?.module.seances[o.index + 1];
                                    setChoisie(voisine?.id ?? null);
                                }}
                            />
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function BoutonIcone({ label, onClick, disabled, children }: { label: string; onClick: () => void; disabled?: boolean; children: ReactNode }) {
    return (
        <button type="button" aria-label={label} title={label} onClick={onClick} disabled={disabled} className="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-30">
            {children}
        </button>
    );
}

function Dupliquer({ parcoursId, classes }: { parcoursId: number; classes: { id: number; name: string }[] }) {
    const { t } = useT();
    const [vers, setVers] = useState('');
    return (
        <div className="flex items-center gap-2">
            <select aria-label={t('Dupliquer vers')} className="rounded-md border bg-background px-2 py-1.5 text-sm" value={vers} onChange={(e) => setVers(e.target.value)}>
                <option value="">{t('Copie comme modèle')}</option>
                {classes.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
            </select>
            <Button variant="outline" size="sm" onClick={() => router.post(`/center/cours/${parcoursId}/dupliquer`, { classroom_id: vers ? Number(vers) : null })}>
                <Copy className="size-4" /> {t('Dupliquer le parcours')}
            </Button>
            <Button
                variant="ghost"
                size="sm"
                aria-label={t('Supprimer le parcours')}
                onClick={() => window.confirm(t('Supprimer définitivement ce parcours et la progression de ses élèves ?')) && router.delete(`/center/cours/${parcoursId}`)}
            >
                <Trash2 className="size-4" />
            </Button>
        </div>
    );
}

interface PropsSeance {
    seance: Seance;
    estPremiere: boolean;
    majSeance: (id: number, partiel: Partial<Seance>) => void;
    majBloc: (id: number, partiel: Partial<Bloc>) => void;
    executer: (url: string, methode: 'POST' | 'PATCH' | 'DELETE', corps?: Record<string, unknown>, remplacer?: boolean) => Promise<unknown>;
    deplacerBloc: (id: number, seanceCible: number, vers: number) => void;
    glisse: Glisse;
    setGlisse: (g: Glisse) => void;
    types: { value: string; label: string }[];
    medias: Media[];
    ajouterMedia: (m: Media) => void;
    parcoursId: number;
    exercicesCentre: Exo[];
    banque: Exo[];
    liveDisponible: boolean;
    creerExercice: (blocId: number) => void;
    onSupprimee: () => void;
}

function Champ({ label, children, className = '' }: { label: string; children: ReactNode; className?: string }) {
    return (
        <label className={`block text-sm ${className}`}>
            <span className="mb-1 block font-medium">{label}</span>
            {children}
        </label>
    );
}
const champ = 'w-full rounded-md border bg-background px-2 py-1.5 text-sm';

function EditeurSeance(props: PropsSeance) {
    const { seance: s, majSeance, executer, types, glisse, setGlisse, deplacerBloc } = props;
    const { t } = useT();
    const [ajout, setAjout] = useState('texte');
    const nombre = (v: string) => (v === '' ? null : Number(v));

    return (
        <div className="space-y-4">
            <div className="space-y-3 rounded-lg border bg-card p-4">
                <div className="flex flex-wrap items-center gap-2">
                    <input aria-label={t('Titre de la séance')} className="min-w-0 flex-1 rounded-md border bg-background px-2 py-1.5 text-lg font-semibold" value={s.titre} maxLength={160} onChange={(e) => majSeance(s.id, { titre: e.target.value })} />
                    <Button variant={s.statut === 'publie' ? 'outline' : 'default'} size="sm" onClick={() => majSeance(s.id, { statut: s.statut === 'publie' ? 'brouillon' : 'publie' })}>
                        {s.statut === 'publie' ? t('Repasser en brouillon') : t('Publier la séance')}
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <a href={`/center/cours/seances/${s.id}/apercu`} target="_blank" rel="noopener noreferrer"><Eye className="size-4" /> {t('Voir comme l’élève')}</a>
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => executer(`/center/cours/seances/${s.id}/dupliquer`, 'POST')}><Copy className="size-4" /> {t('Dupliquer')}</Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        aria-label={t('Supprimer la séance')}
                        onClick={async () => {
                            if (!window.confirm(t('Supprimer cette séance ?'))) return;
                            if (await executer(`/center/cours/seances/${s.id}`, 'DELETE')) props.onSupprimee();
                        }}
                    >
                        <Trash2 className="size-4" />
                    </Button>
                </div>
                <textarea aria-label={t('Description de la séance')} placeholder={t('Description (facultative)')} rows={2} className={champ} value={s.description ?? ''} onChange={(e) => majSeance(s.id, { description: e.target.value || null })} />

                <fieldset className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <legend className="mb-2 text-sm font-semibold">{t('Règles de déroulement')}</legend>
                    <Champ label={t('Ouverture')}>
                        <select className={champ} value={s.deblocage} onChange={(e) => majSeance(s.id, { deblocage: e.target.value as Seance['deblocage'] })}>
                            <option value="libre">{t('Ordre libre : toujours ouverte')}</option>
                            <option value="completion" disabled={props.estPremiere}>{t('Après la séance précédente terminée')}</option>
                            <option value="quiz" disabled={props.estPremiere}>{t('Après réussite des quiz de la séance précédente')}</option>
                            <option value="date">{t('À une date')}</option>
                        </select>
                    </Champ>
                    {s.deblocage === 'quiz' && (
                        <Champ label={t('Seuil de réussite (%)')}>
                            <input type="number" min={1} max={100} className={champ} value={s.seuil_quiz ?? ''} onChange={(e) => majSeance(s.id, { seuil_quiz: nombre(e.target.value) })} />
                        </Champ>
                    )}
                    {s.deblocage === 'date' && (
                        <Champ label={t('Ouverte à partir du')}>
                            <input type="datetime-local" className={champ} value={s.deblocage_at ?? ''} onChange={(e) => majSeance(s.id, { deblocage_at: e.target.value || null })} />
                        </Champ>
                    )}
                    <Champ label={t('Date de début')}>
                        <input type="datetime-local" className={champ} value={s.debut_at ?? ''} onChange={(e) => majSeance(s.id, { debut_at: e.target.value || null })} />
                    </Champ>
                    <Champ label={t('Date limite')}>
                        <input type="datetime-local" className={champ} value={s.limite_at ?? ''} onChange={(e) => majSeance(s.id, { limite_at: e.target.value || null })} />
                    </Champ>
                    <Champ label={t('Durée estimée (min)')}>
                        <input type="number" min={1} max={600} className={champ} value={s.duree_minutes ?? ''} onChange={(e) => majSeance(s.id, { duree_minutes: nombre(e.target.value) })} />
                    </Champ>
                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={s.obligatoire} onChange={(e) => majSeance(s.id, { obligatoire: e.target.checked })} /> {t('Séance obligatoire')}
                    </label>
                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={s.blocs_ordre_impose} onChange={(e) => majSeance(s.id, { blocs_ordre_impose: e.target.checked })} /> {t('Blocs dans l’ordre imposé')}
                    </label>
                </fieldset>
            </div>

            <ol className="space-y-3">
                {s.blocs.map((b, i) => (
                    <li
                        key={b.id}
                        onDragOver={(e) => glisse?.kind === 'bloc' && e.preventDefault()}
                        onDrop={(e) => {
                            e.preventDefault();
                            if (glisse?.kind === 'bloc') deplacerBloc(glisse.id, s.id, i);
                            setGlisse(null);
                        }}
                        className="rounded-lg border bg-card"
                    >
                        <div className="flex items-center gap-2 border-b px-3 py-2">
                            <span draggable onDragStart={() => setGlisse({ kind: 'bloc', id: b.id })} onDragEnd={() => setGlisse(null)} className="cursor-grab text-muted-foreground" title={t('Glisser pour déplacer (ici ou vers une autre séance)')}>
                                <GripVertical className="size-4" />
                            </span>
                            <Badge variant="secondary">{t(types.find((x) => x.value === b.type)?.label ?? b.type)}</Badge>
                            <input aria-label={t('Titre du bloc')} placeholder={t('Titre du bloc (facultatif)')} className="min-w-0 flex-1 rounded border-transparent bg-transparent px-1 text-sm hover:border-input focus:border-input" value={b.titre ?? ''} maxLength={160} onChange={(e) => props.majBloc(b.id, { titre: e.target.value || null })} />
                            <label className="flex items-center gap-1 text-xs">
                                <input type="checkbox" checked={b.obligatoire} onChange={(e) => props.majBloc(b.id, { obligatoire: e.target.checked })} /> {t('Obligatoire')}
                            </label>
                            <BoutonIcone label={t('Monter le bloc')} onClick={() => deplacerBloc(b.id, s.id, i - 1)} disabled={i === 0}><ArrowUp className="size-3.5" /></BoutonIcone>
                            <BoutonIcone label={t('Descendre le bloc')} onClick={() => deplacerBloc(b.id, s.id, i + 1)} disabled={i === s.blocs.length - 1}><ArrowDown className="size-3.5" /></BoutonIcone>
                            <BoutonIcone label={t('Supprimer le bloc')} onClick={() => window.confirm(t('Supprimer ce bloc ?')) && executer(`/center/cours/blocs/${b.id}`, 'DELETE')}><Trash2 className="size-3.5" /></BoutonIcone>
                        </div>
                        <div className="p-3">
                            <EditeurBloc bloc={b} {...props} />
                        </div>
                    </li>
                ))}
            </ol>

            <div className="flex flex-wrap items-center gap-2 rounded-lg border border-dashed p-3">
                <select aria-label={t('Type de bloc')} className="rounded-md border bg-background px-2 py-1.5 text-sm" value={ajout} onChange={(e) => setAjout(e.target.value)}>
                    {types.map((ty) => <option key={ty.value} value={ty.value}>{t(ty.label)}</option>)}
                </select>
                <Button onClick={() => executer(`/center/cours/seances/${s.id}/blocs`, 'POST', { type: ajout })}><Plus className="size-4" /> {t('Ajouter un bloc')}</Button>
            </div>
        </div>
    );
}

function EditeurBloc({ bloc: b, majBloc, medias, ajouterMedia, parcoursId, exercicesCentre, banque, liveDisponible, creerExercice }: PropsSeance & { bloc: Bloc }) {
    const { t } = useT();
    const c = b.contenu ?? {};
    const maj = (partiel: Record<string, unknown>) => majBloc(b.id, { contenu: { ...c, ...partiel } });
    const nombre = (v: string) => (v === '' ? null : Number(v));
    const choixMedia = (typesAcceptes: string[], cle = 'media_id') => (
        <ChoixMedia types={typesAcceptes} valeur={c[cle] ?? null} onChange={(id) => maj({ [cle]: id })} medias={medias} ajouterMedia={ajouterMedia} parcoursId={parcoursId} />
    );

    switch (b.type) {
        case 'texte':
            return <TexteRiche html={c.html ?? ''} onChange={(html) => maj({ html })} medias={medias} />;
        case 'video':
            return (
                <div className="grid gap-3 sm:grid-cols-2">
                    <Champ label={t('Source')}>
                        <select className={champ} value={c.source ?? 'youtube'} onChange={(e) => maj({ source: e.target.value })}>
                            <option value="youtube">YouTube</option>
                            <option value="vimeo">Vimeo</option>
                            <option value="media">{t('Fichier du centre (ou Google Drive)')}</option>
                        </select>
                    </Champ>
                    {c.source === 'media' ? choixMedia(['video']) : (
                        <Champ label={t('Lien de la vidéo')}>
                            <input className={champ} placeholder="https://" value={c.url ?? ''} onChange={(e) => maj({ url: e.target.value })} />
                        </Champ>
                    )}
                    <Champ label={t('Doit regarder au moins (%)')}>
                        <input type="number" min={0} max={100} className={champ} value={c.seuil_visionnage ?? 0} onChange={(e) => maj({ seuil_visionnage: nombre(e.target.value) ?? 0 })} />
                    </Champ>
                    {c.source !== 'media' && (
                        <Champ label={t('Durée de la vidéo (s), pour le seuil')}>
                            <input type="number" min={1} className={champ} value={c.duree_secondes ?? ''} onChange={(e) => maj({ duree_secondes: nombre(e.target.value) })} />
                        </Champ>
                    )}
                    {c.source !== 'media' && (c.seuil_visionnage ?? 0) > 0 && (
                        <p className="text-xs text-muted-foreground sm:col-span-2">{t('Une vidéo YouTube ou Vimeo ne se mesure pas : l’élève peut la valider après la part demandée de sa durée.')}</p>
                    )}
                </div>
            );
        case 'audio':
            return (
                <div className="space-y-3">
                    {choixMedia(['audio'])}
                    <div className="grid gap-3 sm:grid-cols-3">
                        <Champ label={t('Texte associé')}>
                            <select className={champ} value={c.mode_texte ?? 'aucun'} onChange={(e) => maj({ mode_texte: e.target.value })}>
                                <option value="aucun">{t('Audio seul')}</option>
                                <option value="transcription">{t('Transcription affichable / masquable')}</option>
                                <option value="lecture">{t('Texte à lire pendant l’écoute')}</option>
                            </select>
                        </Champ>
                        {c.mode_texte === 'transcription' && (
                            <label className="flex items-center gap-2 text-sm">
                                <input type="checkbox" checked={c.texte_masque ?? true} onChange={(e) => maj({ texte_masque: e.target.checked })} /> {t('Masquée au départ')}
                            </label>
                        )}
                        <Champ label={t('Écoutes maximum (vide = illimité)')}>
                            <input type="number" min={1} max={20} className={champ} value={c.ecoutes_max ?? ''} onChange={(e) => maj({ ecoutes_max: nombre(e.target.value) })} />
                        </Champ>
                    </div>
                    {c.mode_texte !== 'aucun' && <TexteRiche html={c.texte_html ?? ''} onChange={(html) => maj({ texte_html: html })} medias={medias} />}
                </div>
            );
        case 'document':
            return (
                <div className="space-y-3">
                    {choixMedia(['document', 'image'])}
                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={c.telechargeable ?? true} onChange={(e) => maj({ telechargeable: e.target.checked })} /> {t('Téléchargement autorisé')}
                    </label>
                </div>
            );
        case 'exercice':
            return (
                <div className="grid gap-3 sm:grid-cols-[1fr_160px]">
                    <Champ label={t('Exercice')}>
                        <select className={champ} value={c.exercise_id ?? ''} onChange={(e) => maj({ exercise_id: nombre(e.target.value) })}>
                            <option value="">{t('Choisir…')}</option>
                            {b.exercice && ![...exercicesCentre, ...banque].some((x) => x.id === b.exercice!.id) && <option value={b.exercice.id}>{b.exercice.titre}</option>}
                            <optgroup label={t('Exercices publiés du centre')}>
                                {exercicesCentre.map((x) => <option key={x.id} value={x.id}>{x.titre}{x.niveau ? ` · ${x.niveau}` : ''}</option>)}
                            </optgroup>
                            <optgroup label={t('Banque PrePla')}>
                                {banque.map((x) => <option key={x.id} value={x.id}>{x.titre}{x.niveau ? ` · ${x.niveau}` : ''}</option>)}
                            </optgroup>
                        </select>
                    </Champ>
                    <Champ label={t('Seuil de réussite (%)')}>
                        <input type="number" min={1} max={100} className={champ} value={c.seuil ?? ''} onChange={(e) => maj({ seuil: nombre(e.target.value) })} />
                    </Champ>
                    <div className="sm:col-span-2">
                        <Button type="button" variant="outline" size="sm" onClick={() => creerExercice(b.id)}>
                            {t('Créer un nouvel exercice')}
                        </Button>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {t('Tous les formats d’examen, écrit à la main ou avec l’IA. Il remplira ce bloc dès son enregistrement.')}
                        </p>
                    </div>
                </div>
            );
        case 'quiz':
            return <EditeurQuiz contenu={c} maj={maj} />;
        case 'ecrit':
            return (
                <div className="space-y-3">
                    <Champ label={t('Consigne')}><textarea rows={3} className={champ} value={c.consigne ?? ''} onChange={(e) => maj({ consigne: e.target.value })} /></Champ>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <Champ label={t('Mots minimum')}><input type="number" min={1} className={champ} value={c.mots_min ?? ''} onChange={(e) => maj({ mots_min: nombre(e.target.value) })} /></Champ>
                        <Champ label={t('Mots maximum')}><input type="number" min={1} className={champ} value={c.mots_max ?? ''} onChange={(e) => maj({ mots_max: nombre(e.target.value) })} /></Champ>
                    </div>
                    <p className="text-xs text-muted-foreground">{t('La copie part dans « À corriger » du suivi, avec un premier avis automatique.')}</p>
                </div>
            );
        case 'oral':
            return (
                <div className="space-y-3">
                    <Champ label={t('Consigne')}><textarea rows={3} className={champ} value={c.consigne ?? ''} onChange={(e) => maj({ consigne: e.target.value })} /></Champ>
                    <Champ label={t('Durée maximale d’enregistrement (s)')} className="sm:w-64">
                        <input type="number" min={10} max={900} className={champ} value={c.duree_max_secondes ?? 120} onChange={(e) => maj({ duree_max_secondes: nombre(e.target.value) })} />
                    </Champ>
                    <p className="text-xs text-muted-foreground">{t('L’enregistrement part dans « À corriger » du suivi.')}</p>
                </div>
            );
        case 'vocabulaire': {
            const mots: { mot: string; traduction: string; exemple: string }[] = c.mots ?? [];
            const fixer = (i: number, partiel: Record<string, string>) => maj({ mots: mots.map((m, j) => (j === i ? { ...m, ...partiel } : m)) });
            return (
                <div className="space-y-2">
                    {mots.map((m, i) => (
                        <div key={i} className="grid grid-cols-[1fr_1fr_1.5fr_auto] gap-2">
                            <input aria-label={t('Mot')} placeholder={t('Mot')} className={champ} value={m.mot} onChange={(e) => fixer(i, { mot: e.target.value })} />
                            <input aria-label={t('Traduction')} placeholder={t('Traduction')} className={champ} value={m.traduction} onChange={(e) => fixer(i, { traduction: e.target.value })} />
                            <input aria-label={t('Exemple')} placeholder={t('Exemple (facultatif)')} className={champ} value={m.exemple ?? ''} onChange={(e) => fixer(i, { exemple: e.target.value })} />
                            <BoutonIcone label={t('Retirer le mot')} onClick={() => maj({ mots: mots.filter((_, j) => j !== i) })}><Trash2 className="size-3.5" /></BoutonIcone>
                        </div>
                    ))}
                    <Button variant="outline" size="sm" onClick={() => maj({ mots: [...mots, { mot: '', traduction: '', exemple: '' }] })}><Plus className="size-3.5" /> {t('Ajouter un mot')}</Button>
                </div>
            );
        }
        case 'lien':
            return (
                <div className="grid gap-3 sm:grid-cols-2">
                    <Champ label={t('Adresse')}><input className={champ} placeholder="https://" value={c.url ?? ''} onChange={(e) => maj({ url: e.target.value })} /></Champ>
                    <Champ label={t('Libellé')}><input className={champ} value={c.libelle ?? ''} onChange={(e) => maj({ libelle: e.target.value })} /></Champ>
                </div>
            );
        case 'consigne':
            return <Champ label={t('Message de l’enseignant')}><textarea rows={3} className={champ} value={c.texte ?? ''} onChange={(e) => maj({ texte: e.target.value })} /></Champ>;
        case 'live':
            return (
                <div className="grid gap-3 sm:grid-cols-2">
                    <Champ label={t('Lien de la séance (Google Meet, Zoom…)')}><input className={champ} placeholder="https://meet.google.com/…" value={c.url ?? ''} onChange={(e) => maj({ url: e.target.value })} /></Champ>
                    <Champ label={t('Date et heure')}><input type="datetime-local" className={champ} value={c.date ?? ''} onChange={(e) => maj({ date: e.target.value || null })} /></Champ>
                    {liveDisponible && (
                        <Champ label={t('Live class du centre (n°)')}><input type="number" min={1} className={champ} value={c.live_class_id ?? ''} onChange={(e) => maj({ live_class_id: nombre(e.target.value) })} /></Champ>
                    )}
                </div>
            );
        default:
            return null;
    }
}

function EditeurQuiz({ contenu: c, maj }: { contenu: any; maj: (p: Record<string, unknown>) => void }) {
    const { t } = useT();
    const questions: any[] = c.questions ?? [];
    const fixer = (i: number, partiel: Record<string, unknown>) => maj({ questions: questions.map((q, j) => (j === i ? { ...q, ...partiel } : q)) });
    const ajouter = (type: string) =>
        maj({ questions: [...questions, type === 'qcm' ? { type, enonce: '', choix: ['', ''], bonne: 0 } : type === 'vrai_faux' ? { type, enonce: '', reponse: true } : { type, texte: '' }] });

    return (
        <div className="space-y-3">
            <Champ label={t('Seuil de réussite (%)')} className="sm:w-48">
                <input type="number" min={1} max={100} className={champ} value={c.seuil ?? 60} onChange={(e) => maj({ seuil: Number(e.target.value) || 60 })} />
            </Champ>
            {questions.map((q, i) => (
                <div key={i} className="space-y-2 rounded-md border p-3">
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-semibold uppercase text-muted-foreground">
                            {i + 1}. {q.type === 'qcm' ? t('QCM') : q.type === 'vrai_faux' ? t('Vrai / faux') : t('Texte à trous')}
                        </span>
                        <BoutonIcone label={t('Retirer la question')} onClick={() => maj({ questions: questions.filter((_, j) => j !== i) })}><Trash2 className="size-3.5" /></BoutonIcone>
                    </div>
                    {q.type !== 'trous' && <input aria-label={t('Énoncé')} placeholder={t('Énoncé')} className={champ} value={q.enonce} onChange={(e) => fixer(i, { enonce: e.target.value })} />}
                    {q.type === 'qcm' && (
                        <div className="space-y-1">
                            {q.choix.map((ch: string, j: number) => (
                                <div key={j} className="flex items-center gap-2">
                                    <input type="radio" name={`bonne-${i}`} aria-label={t('Bonne réponse')} checked={q.bonne === j} onChange={() => fixer(i, { bonne: j })} />
                                    <input aria-label={t('Choix {n}', { n: j + 1 })} className={champ} value={ch} onChange={(e) => fixer(i, { choix: q.choix.map((x: string, k: number) => (k === j ? e.target.value : x)) })} />
                                    <BoutonIcone label={t('Retirer le choix')} disabled={q.choix.length <= 2} onClick={() => fixer(i, { choix: q.choix.filter((_: string, k: number) => k !== j), bonne: q.bonne >= j && q.bonne > 0 ? q.bonne - 1 : q.bonne })}><Trash2 className="size-3" /></BoutonIcone>
                                </div>
                            ))}
                            {q.choix.length < 8 && <Button variant="ghost" size="sm" onClick={() => fixer(i, { choix: [...q.choix, ''] })}><Plus className="size-3" /> {t('Ajouter un choix')}</Button>}
                        </div>
                    )}
                    {q.type === 'vrai_faux' && (
                        <select aria-label={t('Bonne réponse')} className={champ} value={q.reponse ? '1' : '0'} onChange={(e) => fixer(i, { reponse: e.target.value === '1' })}>
                            <option value="1">{t('Vrai')}</option>
                            <option value="0">{t('Faux')}</option>
                        </select>
                    )}
                    {q.type === 'trous' && (
                        <>
                            <textarea aria-label={t('Texte à trous')} rows={2} className={champ} value={q.texte} onChange={(e) => fixer(i, { texte: e.target.value })} placeholder="Ich [bin] müde. Er [hat|hatte] Zeit." />
                            <p className="text-xs text-muted-foreground">{t('Mettez la réponse entre crochets : [bin]. Plusieurs réponses acceptées : [hat|hatte].')}</p>
                        </>
                    )}
                </div>
            ))}
            <div className="flex flex-wrap gap-2">
                <Button variant="outline" size="sm" onClick={() => ajouter('qcm')}><Plus className="size-3.5" /> {t('QCM')}</Button>
                <Button variant="outline" size="sm" onClick={() => ajouter('vrai_faux')}><Plus className="size-3.5" /> {t('Vrai / faux')}</Button>
                <Button variant="outline" size="sm" onClick={() => ajouter('trous')}><Plus className="size-3.5" /> {t('Texte à trous')}</Button>
            </div>
        </div>
    );
}

function ChoixMedia({ types, valeur, onChange, medias, ajouterMedia, parcoursId }: { types: string[]; valeur: number | null; onChange: (id: number | null) => void; medias: Media[]; ajouterMedia: (m: Media) => void; parcoursId: number }) {
    const { t } = useT();
    const [fichier, setFichier] = useState<File | null>(null);
    const [droits, setDroits] = useState(false);
    const [envoi, setEnvoi] = useState(false);
    const [erreur, setErreur] = useState<string | null>(null);
    const choisis = medias.filter((m) => types.includes(m.type));
    const typeEnvoi = types.includes('video') ? 'video' : types.includes('audio') ? 'audio' : 'document';
    const accept = typeEnvoi === 'video' ? 'video/mp4,video/webm,video/quicktime' : typeEnvoi === 'audio' ? 'audio/*' : 'application/pdf,image/*';

    return (
        <div className="space-y-2">
            <Champ label={t('Fichier')}>
                <select className={champ} value={valeur ?? ''} onChange={(e) => onChange(e.target.value ? Number(e.target.value) : null)}>
                    <option value="">{t('Choisir dans la médiathèque…')}</option>
                    {choisis.map((m) => <option key={m.id} value={m.id}>{m.titre}{m.drive ? ' (Drive)' : ''}</option>)}
                </select>
            </Champ>
            <div className="flex flex-wrap items-center gap-2 text-xs">
                <input type="file" accept={accept} aria-label={t('Envoyer un fichier')} onChange={(e) => setFichier(e.target.files?.[0] ?? null)} className="text-xs" />
                <label className="flex items-center gap-1">
                    <input type="checkbox" checked={droits} onChange={(e) => setDroits(e.target.checked)} /> {t('J’ai les droits de diffuser ce fichier')}
                </label>
                <Button
                    size="sm"
                    variant="outline"
                    disabled={!fichier || !droits || envoi}
                    onClick={async () => {
                        if (!fichier) return;
                        setEnvoi(true);
                        setErreur(null);
                        const f = new FormData();
                        f.append('type', typeEnvoi);
                        f.append('fichier', fichier);
                        f.append('droits_certifies', '1');
                        try {
                            const m = await appel<Media>(`/center/cours/${parcoursId}/media`, 'POST', f);
                            ajouterMedia(m);
                            onChange(m.id);
                            setFichier(null);
                        } catch (e) {
                            setErreur((e as Error).message);
                        } finally {
                            setEnvoi(false);
                        }
                    }}
                >
                    <Upload className="size-3.5" /> {envoi ? t('Envoi…') : t('Envoyer')}
                </Button>
            </div>
            {erreur && <p className="text-xs text-destructive">{erreur}</p>}
        </div>
    );
}

/** Texte riche : titres, gras, italique, souligné, listes, liens, images. Le serveur assainit le HTML. */
function TexteRiche({ html, onChange, medias }: { html: string; onChange: (html: string) => void; medias: Media[] }) {
    const { t } = useT();
    const zone = useRef<HTMLDivElement>(null);
    const [image, setImage] = useState(false);
    useEffect(() => {
        if (zone.current && zone.current.innerHTML !== html) zone.current.innerHTML = html;
        // Seulement au montage : ensuite, la zone est la source.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);
    const cmd = (commande: string, valeur?: string) => {
        zone.current?.focus();
        document.execCommand(commande, false, valeur);
        onChange(zone.current?.innerHTML ?? '');
    };
    const outils: [string, ReactNode, () => void][] = [
        [t('Titre'), <Heading2 key="h2" className="size-4" />, () => cmd('formatBlock', 'h2')],
        [t('Sous-titre'), <Heading3 key="h3" className="size-4" />, () => cmd('formatBlock', 'h3')],
        [t('Gras'), <Bold key="b" className="size-4" />, () => cmd('bold')],
        [t('Italique'), <Italic key="i" className="size-4" />, () => cmd('italic')],
        [t('Souligné'), <Underline key="u" className="size-4" />, () => cmd('underline')],
        [t('Liste à puces'), <List key="ul" className="size-4" />, () => cmd('insertUnorderedList')],
        [t('Liste numérotée'), <ListOrdered key="ol" className="size-4" />, () => cmd('insertOrderedList')],
        [t('Lien'), <Link2 key="a" className="size-4" />, () => {
            const url = window.prompt(t('Adresse du lien (https://…)'));
            if (url && /^https?:\/\//i.test(url)) cmd('createLink', url);
        }],
        [t('Image'), <ImageIcon key="img" className="size-4" />, () => setImage((x) => !x)],
    ];
    const images = medias.filter((m) => m.type === 'image');

    return (
        <div className="rounded-md border">
            <div className="flex flex-wrap gap-1 border-b bg-muted/40 p-1" role="toolbar" aria-label={t('Mise en forme')}>
                {outils.map(([label, icone, action]) => (
                    <button key={label} type="button" aria-label={label} title={label} onMouseDown={(e) => e.preventDefault()} onClick={action} className="rounded p-1.5 hover:bg-background">
                        {icone}
                    </button>
                ))}
                <button type="button" onMouseDown={(e) => e.preventDefault()} onClick={() => cmd('formatBlock', 'p')} className="rounded px-2 text-xs hover:bg-background">{t('Paragraphe')}</button>
            </div>
            {image && (
                <div className="flex flex-wrap items-center gap-2 border-b p-2 text-xs">
                    <select aria-label={t('Image de la médiathèque')} className="rounded border bg-background px-2 py-1" defaultValue="" onChange={(e) => e.target.value && (cmd('insertImage', e.target.value), setImage(false))}>
                        <option value="">{t('Image de la médiathèque…')}</option>
                        {images.map((m) => <option key={m.id} value={m.url}>{m.titre}</option>)}
                    </select>
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => {
                            const url = window.prompt(t('Adresse de l’image (https://…)'));
                            if (url && /^https?:\/\//i.test(url)) cmd('insertImage', url);
                            setImage(false);
                        }}
                    >
                        {t('Par adresse')}
                    </Button>
                </div>
            )}
            <div
                ref={zone}
                contentEditable
                role="textbox"
                aria-multiline="true"
                aria-label={t('Texte')}
                suppressContentEditableWarning
                className="prose prose-sm min-h-32 max-w-none p-3 outline-none dark:prose-invert [&_img]:max-w-full"
                onInput={() => onChange(zone.current?.innerHTML ?? '')}
            />
        </div>
    );
}
