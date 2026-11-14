import { Head, Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { AlertCircle, ArrowLeft, ArrowRight, BookOpen, Check, ClipboardList, Headphones, Mic, PenLine, Plus, Puzzle, RefreshCw, Sparkles, X } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Apercu } from '@/components/createur-exercices/apercu';
import { champ, EditeurQuestion } from '@/components/createur-exercices/editeur-question';
import { ZoneMedia } from '@/components/createur-exercices/zone-media';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/app-layout';
import {
    brouillonUtile,
    cleDuBrouillon,
    descriptionDuType,
    deplacer,
    dupliquer,
    ecrireObjet,
    effacerObjet,
    libelleDuType,
    lireObjet,
    niveauDeLaClasse,
    nomDeLaPartie,
    nombreDeQuestions,
    NIVEAUX,
    problemesDeLaQuestion,
    problemesDeLExercice,
    questionsACompleter,
    questionsAEnvoyer,
    regrouperParCompetence,
    retirer,
    stockageDuNavigateur,
    type PartieOfficielle,
} from '@/lib/createur-exercices';
import { EXERCISE_SCHEMAS, emptyQuestion, familyOf, type Family } from '@/lib/exercise-schemas';
import { useT } from '@/lib/i18n/traduire';
import { cn } from '@/lib/utils';

interface ExerciseType { id: number; name: string; slug: string | null; component_key: string; skill_type: string | null; exam_id: number | null }
interface Exam { id: number; name: string; language: string | null }
interface Classroom { id: number; name: string; exam_id: number | null; level: string | null }
// eslint-disable-next-line @typescript-eslint/no-explicit-any -- le contenu suit le schema de chaque format
type Libre = Record<string, any>;
interface Props {
    exerciseTypes: ExerciseType[];
    exams: Exam[];
    defaultExamId: number | null;
    classrooms: Classroom[];
    retour: 'devoir' | null;
    devoir: { id: number; title: string; classroom_id: number } | null;
    limiteIa: { par_fenetre: number; minutes: number };
    exercise?: {
        id: number;
        exercise_type_id: number;
        exam_id: number;
        difficulty: string;
        content: Libre | null;
        questions: Libre[];
        is_ai_generated: boolean;
    };
}

const ETAPES = ['Examen et niveau', 'Type d’exercice', 'Contenu', 'Aperçu'];

const ICONES: Record<string, typeof BookOpen> = {
    reading: BookOpen,
    listening: Headphones,
    writing: PenLine,
    speaking: Mic,
    grammar: Puzzle,
    autre: ClipboardList,
};

const MESSAGES_IA = [
    'L’IA lit la structure officielle de l’examen…',
    'Elle écrit le texte et les questions…',
    'Elle vérifie chaque bonne réponse…',
    'Encore quelques secondes : elle relit l’ensemble…',
];

type Origine = 'ia' | 'manuel' | null;

export default function ExerciseBuilder({ exerciseTypes, exams, defaultExamId, classrooms, retour, devoir, limiteIa, exercise }: Props) {
    const { t } = useT();
    const editing = !!exercise;
    const page = usePage();
    const compte = (page.props as { auth?: { user?: { id?: number } | null } }).auth?.user?.id ?? null;
    const cle = cleDuBrouillon(compte);

    const examInitial = exercise?.exam_id ?? (exams.some((e) => e.id === defaultExamId) ? defaultExamId : exams[0]?.id) ?? null;
    const [etape, setEtape] = useState(editing ? 3 : 1);
    const [examId, setExamId] = useState<number | null>(examInitial);
    const [difficulty, setDifficulty] = useState(exercise?.difficulty ?? 'B1');
    const [classroomId, setClassroomId] = useState<number | null>(null);
    const [typeId, setTypeId] = useState<number | null>(exercise?.exercise_type_id ?? null);
    const [content, setContent] = useState<Libre>(exercise?.content ?? {});
    const [questions, setQuestions] = useState<Libre[]>(exercise?.questions ?? []);
    const [origine, setOrigine] = useState<Origine>(editing ? (exercise?.is_ai_generated ? 'ia' : 'manuel') : null);
    // Le chemin choisi a l'etape 3 : l'IA par defaut, ou l'ecriture a la main.
    const [chemin, setChemin] = useState<'ia' | 'manuel'>(editing ? 'manuel' : 'ia');
    const [ouvertes, setOuvertes] = useState<Record<number, boolean>>({ 0: true });
    const [afficherProblemes, setAfficherProblemes] = useState(false);
    const [modifie, setModifie] = useState(false);
    const [brouillonRestaure, setBrouillonRestaure] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // IA
    const [theme, setTheme] = useState('');
    const [consignes, setConsignes] = useState('');
    const [nombre, setNombre] = useState('');
    const [aiLoading, setAiLoading] = useState(false);
    const [aiError, setAiError] = useState<string | null>(null);
    const [message, setMessage] = useState(0);

    const [parties, setParties] = useState<Record<string, PartieOfficielle[]>>({});
    const [partiesChargees, setPartiesChargees] = useState(false);
    const envoiEnCours = useRef(false);

    const exam = exams.find((e) => e.id === examId) ?? null;
    const availableTypes = useMemo(() => exerciseTypes.filter((t) => t.exam_id === examId), [exerciseTypes, examId]);
    const selectedType = useMemo(() => exerciseTypes.find((t) => t.id === typeId) ?? null, [typeId, exerciseTypes]);
    const componentKey = selectedType?.component_key ?? '';
    const family: Family | null = componentKey ? familyOf(componentKey) : null;
    const schema = componentKey ? EXERCISE_SCHEMAS[componentKey] : null;
    const ecoute = selectedType?.skill_type === 'listening';
    const contexte = useMemo(() => ({ famille: family ?? '', componentKey }), [family, componentKey]);
    const aCompleter = useMemo(() => (family ? questionsACompleter(questions, contexte) : []), [questions, contexte, family]);
    const problemesExercice = useMemo(() => problemesDeLExercice({ skillType: selectedType?.skill_type, content, questions }), [selectedType, content, questions]);
    const titreDuType = t(libelleDuType(selectedType));
    const groupes = useMemo(() => regrouperParCompetence(availableTypes, parties), [availableTypes, parties]);
    const contenuPret = family !== null && questions.length > 0 && aCompleter.length === 0 && problemesExercice.length === 0;

    // ── Brouillon : jamais pour un exercice en edition ──
    const brouillonLu = useRef(false);
    useEffect(() => {
        brouillonLu.current = true;
        if (editing) return;
        const b = lireObjet(stockageDuNavigateur(), cle);
        if (!b || !brouillonUtile(b)) return;
        if (!exams.some((e) => e.id === b.examId)) return;
        setExamId(b.examId);
        setDifficulty(b.difficulty ?? 'B1');
        setTypeId(b.typeId);
        setContent(b.content ?? {});
        setQuestions(Array.isArray(b.questions) ? b.questions : []);
        setOrigine(b.origine ?? 'manuel');
        setChemin(b.origine === 'ia' ? 'ia' : 'manuel');
        setClassroomId(b.classroomId ?? null);
        setEtape(Math.min(Math.max(Number(b.etape) || 3, 3), 4));
        setBrouillonRestaure(true);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        if (editing || !brouillonLu.current || !modifie) return;
        const donnees = { examId, difficulty, typeId, content, questions, origine, classroomId, etape };
        if (brouillonUtile(donnees)) ecrireObjet(stockageDuNavigateur(), cle, donnees);
    }, [editing, modifie, examId, difficulty, typeId, content, questions, origine, classroomId, etape, cle]);

    function repartirDeZero() {
        effacerObjet(stockageDuNavigateur(), cle);
        setBrouillonRestaure(false);
        setTypeId(null);
        setContent({});
        setQuestions([]);
        setOrigine(null);
        setChemin('ia');
        setModifie(false);
        setEtape(1);
    }

    // ── Quitter avec des modifications non enregistrees ──
    useEffect(() => {
        if (!modifie) return;
        const avant = (e: BeforeUnloadEvent) => {
            if (envoiEnCours.current) return;
            e.preventDefault();
            e.returnValue = '';
        };
        window.addEventListener('beforeunload', avant);
        const retirerEcoute = router.on('before', (event) => {
            if (envoiEnCours.current) return;
            const methode = event.detail.visit.method;
            if (methode !== 'get') return;
            if (!confirm(t('Vos modifications ne sont pas enregistrées. Quitter quand même ? (Un brouillon reste gardé sur cet appareil.)'))) {
                event.preventDefault();
            }
        });
        return () => {
            window.removeEventListener('beforeunload', avant);
            retirerEcoute();
        };
    }, [modifie, t]);

    // ── Parties officielles de l'examen a ce niveau ──
    useEffect(() => {
        if (!examId) return;
        let annule = false;
        setPartiesChargees(false);
        axios.get(route('center.exercises.parties'), { params: { exam_id: examId, difficulty } })
            .then((res) => { if (!annule) setParties(res.data?.parties ?? {}); })
            .catch(() => { if (!annule) setParties({}); })
            .finally(() => { if (!annule) setPartiesChargees(true); });
        return () => { annule = true; };
    }, [examId, difficulty]);

    // ── Messages pendant la generation ──
    useEffect(() => {
        if (!aiLoading) return;
        setMessage(0);
        const t = window.setInterval(() => setMessage((m) => Math.min(m + 1, MESSAGES_IA.length - 1)), 4500);
        return () => window.clearInterval(t);
    }, [aiLoading]);

    const changer = useCallback(() => setModifie(true), []);

    function choisirExamen(id: number) {
        setExamId(id);
        changer();
        if (!editing && typeId && !exerciseTypes.some((t) => t.id === typeId && t.exam_id === id)) {
            setTypeId(null);
            setQuestions([]);
            setOrigine(null);
        }
    }

    function choisirClasse(id: number | null) {
        setClassroomId(id);
        const classe = classrooms.find((c) => c.id === id);
        if (!classe) return;
        if (!editing && classe.exam_id && exams.some((e) => e.id === classe.exam_id)) choisirExamen(classe.exam_id);
        const niveau = niveauDeLaClasse(classe.level);
        if (niveau) setDifficulty(niveau);
    }

    function choisirType(t: ExerciseType) {
        if (t.id !== typeId) {
            const fam = familyOf(t.component_key);
            setTypeId(t.id);
            setQuestions([]);
            setContent((c) => (c.title ? { title: c.title } : {}));
            setOrigine(null);
            setOuvertes({ 0: true });
            if (!fam) return;
        }
        changer();
        setEtape(3);
    }

    function ecrireMoiMeme() {
        if (!family) return;
        setChemin('manuel');
        if (origine === null) setOrigine('manuel');
        if (questions.length === 0) setQuestions([emptyQuestion(family)]);
        setOuvertes({ 0: true });
        changer();
    }

    async function genererAvecIa() {
        if (!typeId || !examId) return;
        if (questions.length > 0 && modifie && !confirm(t('Remplacer les questions actuelles par une nouvelle proposition de l’IA ?'))) return;
        setAiLoading(true);
        setAiError(null);
        try {
            const res = await axios.post(route('center.exercises.ai-draft'), {
                exercise_type_id: typeId,
                exam_id: examId,
                difficulty,
                theme: theme.trim() || null,
                consignes: consignes.trim() || null,
                nombre_questions: nombreDeQuestions(nombre),
            });
            const titre = typeof content.title === 'string' ? content.title : '';
            setContent({ ...(res.data.content ?? {}), ...(titre ? { title: titre } : {}) });
            setQuestions(Array.isArray(res.data.questions) ? res.data.questions : []);
            setOrigine('ia');
            setOuvertes({ 0: true });
            setAfficherProblemes(false);
            changer();
        } catch (e: unknown) {
            const r = (e as { response?: { status?: number; data?: { error?: string; message?: string } } })?.response;
            setAiError(r?.data?.error ?? r?.data?.message ?? (r?.status === 419 ? 'La session a expiré : rechargez la page.' : 'La génération a échoué. Vérifiez votre connexion et réessayez.'));
        } finally {
            setAiLoading(false);
        }
    }

    function majQuestions(next: Libre[]) {
        setQuestions(next);
        changer();
    }

    function aller(cible: number) {
        if (cible === 4 && !contenuPret) {
            setAfficherProblemes(true);
            const premiere = aCompleter[0];
            if (premiere !== undefined) {
                setOuvertes((o) => ({ ...o, [premiere]: true }));
                window.setTimeout(() => document.getElementById(`question-${premiere}`)?.scrollIntoView({ block: 'center', behavior: 'smooth' }), 50);
            }
            return;
        }
        setEtape(cible);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function accessible(n: number) {
        if (editing) return n >= 3 || n === 1;
        if (n === 1) return true;
        if (n === 2) return !!examId;
        if (n === 3) return !!typeId;
        return !!typeId && contenuPret;
    }

    function save(versDevoir: boolean) {
        if (!typeId || !examId) {
            setError('Choisissez un examen et un type d’exercice.');
            return;
        }
        if (!contenuPret) {
            setAfficherProblemes(true);
            setEtape(3);
            setError(problemesExercice[0] ?? resume ?? 'L’exercice n’est pas complet.');
            return;
        }
        setSaving(true);
        envoiEnCours.current = true;
        setError(null);
        const payload = {
            exercise_type_id: typeId,
            exam_id: examId,
            difficulty,
            content,
            questions: questionsAEnvoyer(questions, content),
            is_ai_generated: origine === 'ia',
            retour: versDevoir || retour === 'devoir' ? 'devoir' : null,
            assignment_id: devoir?.id ?? null,
        };
        const opts = {
            onSuccess: () => {
                if (!editing) effacerObjet(stockageDuNavigateur(), cle);
                setModifie(false);
            },
            onError: (errs: Record<string, string>) => {
                envoiEnCours.current = false;
                setError(Object.values(errs)[0] ?? 'L’enregistrement a échoué.');
            },
            onFinish: () => {
                setSaving(false);
                envoiEnCours.current = false;
            },
        };
        if (editing) router.patch(route('center.exercises.update', exercise!.id), payload, opts);
        else router.post(route('center.exercises.store'), payload, opts);
    }

    const titrePage = editing ? t('Modifier un exercice') : t('Créer un exercice');
    const retourLien = devoir ? route('center.assignments.show', devoir.id) : retour === 'devoir' ? route('center.assignments.create') : route('center.exercises.index');
    const retourTexte = devoir ? t('← Devoir « {titre} »', { titre: devoir.title }) : retour === 'devoir' ? t('← Retour au devoir') : t('← Contenu');
    const libelleEnregistrer = devoir ? t('Enregistrer et ajouter au devoir') : retour === 'devoir' ? t('Enregistrer et revenir au devoir') : t('Enregistrer');
    // resumeACompleter(n), en phrase traduisible.
    const resume = aCompleter.length > 1 ? t('{n} questions à compléter', { n: aCompleter.length }) : aCompleter.length === 1 ? t('1 question à compléter') : '';
    const enEdition = family !== null && (questions.length > 0 || chemin === 'manuel');

    return (
        <AppLayout>
            <Head title={titrePage} />
            <div className="mx-auto w-full max-w-6xl space-y-5 p-4 pb-32 md:p-6 md:pb-32">
                <div>
                    <Link href={retourLien} className="text-sm text-muted-foreground hover:text-foreground">{retourTexte}</Link>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">{titrePage}</h1>
                    <p className="text-sm text-muted-foreground">
                        {editing ? `${titreDuType} · ${exam?.name ?? ''} · ${difficulty}` : t('Quatre étapes : l’examen, le type, le contenu, puis l’aperçu avant d’enregistrer.')}
                    </p>
                </div>

                <Etapes etape={etape} accessible={accessible} onAller={aller} />

                {brouillonRestaure && (
                    <div role="status" className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-sky-300 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950/40 dark:text-sky-100">
                        <span><strong>{t('Brouillon restauré.')}</strong> {t('Vous reprenez l’exercice commencé sur cet appareil.')}</span>
                        <div className="flex gap-2">
                            <Button type="button" variant="ghost" size="sm" onClick={repartirDeZero}>{t('Repartir de zéro')}</Button>
                            <Button type="button" variant="ghost" size="icon" className="h-8 w-8" onClick={() => setBrouillonRestaure(false)} aria-label={t('Fermer')}><X className="h-4 w-4" /></Button>
                        </div>
                    </div>
                )}

                {error && (
                    <div role="alert" className="flex items-start gap-2 rounded-xl border border-rose-300 bg-rose-50 p-3 text-sm text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-100">
                        <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" /> {t(error)}
                    </div>
                )}

                {/* ── Etape 1 : examen, niveau, classe ── */}
                {etape === 1 && (
                    <section aria-labelledby="titre-etape-1" className="space-y-6 rounded-2xl border border-border bg-card p-4 sm:p-6">
                        <h2 id="titre-etape-1" className="text-lg font-bold">{t('Pour quel examen et quel niveau ?')}</h2>

                        {classrooms.length > 0 && !editing && (
                            <div>
                                <label htmlFor="classe" className="text-sm font-medium">{t('Classe visée')} <span className="font-normal text-muted-foreground">{t('(facultatif : remplit l’examen et le niveau)')}</span></label>
                                <select id="classe" className={`mt-1 max-w-sm ${champ}`} value={classroomId ?? ''} onChange={(e) => choisirClasse(e.target.value ? Number(e.target.value) : null)}>
                                    <option value="">{t('Aucune classe en particulier')}</option>
                                    {classrooms.map((c) => <option key={c.id} value={c.id}>{c.name}{c.level ? ` · ${c.level}` : ''}</option>)}
                                </select>
                            </div>
                        )}

                        <fieldset>
                            <legend className="text-sm font-medium">{t('Examen')}</legend>
                            {editing && <p className="text-xs text-muted-foreground">{t('L’examen d’un exercice existant ne change pas.')}</p>}
                            <div className="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {exams.map((e) => (
                                    <CarteChoix key={e.id} choisi={examId === e.id} disabled={editing} onClick={() => choisirExamen(e.id)} titre={e.name} />
                                ))}
                            </div>
                            {exams.length === 0 && <p className="mt-2 text-sm text-muted-foreground">{t('Aucun examen n’est proposé par votre centre : choisissez-en dans les paramètres.')}</p>}
                        </fieldset>

                        <fieldset>
                            <legend className="text-sm font-medium">{t('Niveau')}</legend>
                            <div className="mt-2 flex flex-wrap gap-2" role="radiogroup" aria-label={t('Niveau')}>
                                {NIVEAUX.map((n) => (
                                    <button
                                        key={n}
                                        type="button"
                                        role="radio"
                                        aria-checked={difficulty === n}
                                        onClick={() => { setDifficulty(n); changer(); }}
                                        className={cn('min-h-11 min-w-14 rounded-xl border-[1.5px] px-4 text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring', difficulty === n ? 'border-primary bg-primary text-primary-foreground' : 'border-border hover:bg-muted')}
                                    >
                                        {n}
                                    </button>
                                ))}
                            </div>
                        </fieldset>
                    </section>
                )}

                {/* ── Etape 2 : type en cartes, par competence ── */}
                {etape === 2 && (
                    <section aria-labelledby="titre-etape-2" className="space-y-6">
                        <div>
                            <h2 id="titre-etape-2" className="text-lg font-bold">{t('Quel type d’exercice ?')}</h2>
                            <p className="text-sm text-muted-foreground">{t('Les formats de {examen}, rangés par compétence. Le badge indique la partie officielle de l’épreuve au niveau {niveau}.', { examen: exam?.name ?? t('l’examen'), niveau: difficulty })}</p>
                        </div>
                        {availableTypes.length === 0 && (
                            <p className="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">{t('Aucun format n’est encore disponible pour cet examen.')}</p>
                        )}
                        {groupes.map(({ competence, types }) => {
                            const Icone = ICONES[competence.cle] ?? ClipboardList;
                            return (
                                <div key={competence.cle}>
                                    <h3 className="mb-2 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-muted-foreground">
                                        <Icone className="h-4 w-4" aria-hidden="true" /> {t(competence.nom)}
                                    </h3>
                                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        {types.map((ty) => {
                                            const sesParties = parties[ty.id] ?? [];
                                            return (
                                                <button
                                                    key={ty.id}
                                                    type="button"
                                                    onClick={() => choisirType(ty)}
                                                    aria-pressed={typeId === ty.id}
                                                    className={cn(
                                                        'flex h-full flex-col items-start gap-1.5 rounded-2xl border-[1.5px] bg-card p-4 text-left transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                                                        typeId === ty.id ? 'border-primary ring-1 ring-primary' : 'border-border hover:border-primary/60 hover:bg-muted/40',
                                                    )}
                                                >
                                                    <span className="flex w-full items-start justify-between gap-2">
                                                        <span className="font-semibold">{t(libelleDuType(ty))}</span>
                                                        {typeId === ty.id && <Check className="h-4 w-4 shrink-0 text-primary" aria-hidden="true" />}
                                                    </span>
                                                    {descriptionDuType(ty) && <span className="text-sm text-muted-foreground">{t(descriptionDuType(ty))}</span>}
                                                    {sesParties.length > 0 && (
                                                        <span className="mt-auto flex flex-wrap gap-1 pt-1">
                                                            {sesParties.slice(0, 3).map((p, i) => (
                                                                <span key={i} className="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary">
                                                                    {nomDeLaPartie(p)}{p.items ? ` · ${t('{n} items', { n: p.items })}` : ''}
                                                                </span>
                                                            ))}
                                                        </span>
                                                    )}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            );
                        })}
                        {!partiesChargees && availableTypes.length > 0 && <p className="text-xs text-muted-foreground" aria-live="polite">{t('Recherche des parties officielles…')}</p>}
                    </section>
                )}

                {/* ── Etape 3 : contenu ── */}
                {etape === 3 && family && (
                    <div className={cn('gap-6', enEdition && 'lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)]')}>
                        <section aria-labelledby="titre-etape-3" className="space-y-5">
                            <div>
                                <h2 id="titre-etape-3" className="text-lg font-bold">{titreDuType}</h2>
                                {descriptionDuType(selectedType) && <p className="text-sm text-muted-foreground">{t(descriptionDuType(selectedType))}</p>}
                            </div>

                            {!editing && (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <CheminCarte
                                        actif={chemin === 'ia'}
                                        icone={Sparkles}
                                        titre={t('Générer avec l’IA')}
                                        texte={t('Un brouillon conforme à l’épreuve en quelques secondes, à relire et modifier.')}
                                        onClick={() => setChemin('ia')}
                                        id="chemin-ia"
                                    />
                                    <CheminCarte
                                        actif={chemin === 'manuel'}
                                        icone={PenLine}
                                        titre={t('Écrire moi-même')}
                                        texte={t('Vous rédigez les questions et cochez les bonnes réponses.')}
                                        onClick={ecrireMoiMeme}
                                        id="chemin-manuel"
                                    />
                                </div>
                            )}

                            {/* Formulaire IA : visible tant qu'on n'ecrit pas a la main. */}
                            {!editing && chemin === 'ia' && (
                                <div className="space-y-4 rounded-2xl border border-border bg-card p-4 sm:p-5">
                                    <div className="flex items-center gap-2">
                                        <Sparkles className="h-5 w-5 text-primary" aria-hidden="true" />
                                        <h3 className="font-semibold">{t('Ce que l’IA doit savoir')} <span className="font-normal text-muted-foreground">{t('(tout est facultatif)')}</span></h3>
                                    </div>
                                    <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_10rem]">
                                        <label className="text-sm font-medium">{t('Thème')}
                                            <input className={`mt-1 ${champ}`} maxLength={120} value={theme} onChange={(e) => setTheme(e.target.value)} placeholder={t('Ex. : la santé, le travail en Allemagne')} />
                                        </label>
                                        <label className="text-sm font-medium">{t('Nombre de questions')}
                                            <select className={`mt-1 ${champ}`} value={nombre} onChange={(e) => setNombre(e.target.value)}>
                                                <option value="">{t('Comme à l’examen')}</option>
                                                {Array.from({ length: 20 }, (_, i) => i + 1).map((n) => <option key={n} value={n}>{n}</option>)}
                                            </select>
                                        </label>
                                    </div>
                                    <label className="block text-sm font-medium">{t('Consignes pour l’IA')}
                                        <textarea className={`mt-1 min-h-[70px] ${champ}`} maxLength={600} value={consignes} onChange={(e) => setConsignes(e.target.value)} placeholder={t('Ex. : vocabulaire de l’hôpital, des prénoms camerounais, phrases courtes.')} />
                                    </label>
                                    <div className="flex flex-wrap items-center gap-3">
                                        <Button type="button" onClick={genererAvecIa} disabled={aiLoading}>
                                            {questions.length > 0 && origine === 'ia' ? <RefreshCw className="h-4 w-4" aria-hidden="true" /> : <Sparkles className="h-4 w-4" aria-hidden="true" />}
                                            {aiLoading ? t('Génération en cours…') : questions.length > 0 && origine === 'ia' ? t('Régénérer') : t('Générer le brouillon')}
                                        </Button>
                                        <span className="text-xs text-muted-foreground">{t('Jusqu’à {n} générations toutes les {minutes} minutes.', { n: limiteIa.par_fenetre, minutes: limiteIa.minutes })}</span>
                                    </div>
                                    {aiError && (
                                        <div role="alert" className="flex flex-wrap items-center gap-2 rounded-xl border border-rose-300 bg-rose-50 p-3 text-sm text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-100">
                                            <AlertCircle className="h-4 w-4 shrink-0" aria-hidden="true" />
                                            <span className="flex-1">{t(aiError)}</span>
                                            <Button type="button" variant="outline" size="sm" onClick={genererAvecIa} disabled={aiLoading}>{t('Réessayer')}</Button>
                                            <Button type="button" variant="ghost" size="sm" onClick={ecrireMoiMeme}>{t('Écrire moi-même')}</Button>
                                        </div>
                                    )}
                                </div>
                            )}

                            {aiLoading && (
                                <div className="space-y-3 rounded-2xl border border-border bg-card p-4" aria-busy="true">
                                    <p className="text-sm font-medium" aria-live="polite">{t(MESSAGES_IA[message])}</p>
                                    <Skeleton className="h-5 w-2/3" />
                                    <Skeleton className="h-20 w-full" />
                                    <Skeleton className="h-10 w-full" />
                                    <Skeleton className="h-10 w-full" />
                                    <Skeleton className="h-10 w-5/6" />
                                </div>
                            )}

                            {enEdition && !aiLoading && (
                                <>
                                    {origine === 'ia' && !editing && (
                                        <p className="rounded-xl bg-primary/5 px-3 py-2 text-sm"><Sparkles className="mr-1 inline h-4 w-4 text-primary" aria-hidden="true" />{t('Brouillon écrit par l’IA : relisez-le et corrigez ce qui ne vous convient pas avant d’enregistrer.')}</p>
                                    )}

                                    <div className="space-y-4 rounded-2xl border border-border bg-card p-4 sm:p-5">
                                        <h3 className="font-semibold">{t('L’exercice')}</h3>
                                        <label className="block text-sm font-medium">{t('Titre')} <span className="font-normal text-muted-foreground">{t('(c’est lui que vous retrouverez dans vos listes)')}</span>
                                            <input className={`mt-1 ${champ}`} maxLength={160} value={content.title ?? ''} onChange={(e) => { setContent({ ...content, title: e.target.value }); changer(); }} placeholder={t('Ex. : Rendez-vous chez le médecin')} />
                                        </label>
                                        {!ecoute && (
                                            <label className="block text-sm font-medium">{t('Texte à lire ou consigne générale')} <span className="font-normal text-muted-foreground">{t('(facultatif)')}</span>
                                                <textarea className={`mt-1 min-h-[110px] ${champ}`} value={content.passage ?? ''} onChange={(e) => { setContent({ ...content, passage: e.target.value }); changer(); }} />
                                            </label>
                                        )}
                                        {ecoute && (
                                            <label className="block text-sm font-medium">{t('Texte audio')} <span className="font-normal text-muted-foreground">{t('(lu par la voix de synthèse si vous n’ajoutez pas d’enregistrement)')}</span>
                                                <textarea className={`mt-1 min-h-[110px] ${champ}`} value={content.audio_text ?? ''} onChange={(e) => { setContent({ ...content, audio_text: e.target.value }); changer(); }} placeholder={t('Le dialogue ou l’annonce que l’élève entendra…')} />
                                            </label>
                                        )}
                                        {(schema?.media.includes('audio') && ecoute) && (
                                            <ZoneMedia type="audio" value={content.audio_url} onChange={(url) => { setContent({ ...content, audio_url: url }); changer(); }} aide={t('Un enregistrement remplace la voix de synthèse.')} />
                                        )}
                                        {/* Comme a l'examen : l'eleve n'ecoute la piste du centre qu'un nombre de fois choisi. */}
                                        {ecoute && content.audio_url && (
                                            <label className="block text-sm font-medium">{t('Nombre d’écoutes autorisées')}
                                                <select
                                                    className={`mt-1 ${champ}`}
                                                    value={String(content.max_ecoutes ?? 2)}
                                                    onChange={(e) => { setContent({ ...content, max_ecoutes: Number(e.target.value) }); changer(); }}
                                                >
                                                    <option value="1">{t('Une seule écoute')}</option>
                                                    <option value="2">{t('Deux écoutes')}</option>
                                                    <option value="0">{t('Illimité')}</option>
                                                </select>
                                            </label>
                                        )}
                                        {schema?.media.includes('image') && (
                                            <ZoneMedia type="image" value={content.image_url} onChange={(url) => { setContent({ ...content, image_url: url }); changer(); }} />
                                        )}
                                        {afficherProblemes && problemesExercice.map((p) => (
                                            <p key={p} className="flex items-start gap-1 text-xs text-rose-600 dark:text-rose-400"><AlertCircle className="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />{t(p)}</p>
                                        ))}
                                    </div>

                                    <div className="space-y-3">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <h3 className="font-semibold">{t('Questions ({n})', { n: questions.length })}</h3>
                                            {resume && <span className="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-900 dark:bg-amber-900/40 dark:text-amber-100" aria-live="polite">{resume}</span>}
                                        </div>
                                        {questions.map((q, i) => (
                                            <div key={i} id={`question-${i}`}>
                                                <EditeurQuestion
                                                    index={i}
                                                    total={questions.length}
                                                    famille={family}
                                                    componentKey={componentKey}
                                                    ecoute={ecoute}
                                                    question={q}
                                                    problemes={problemesDeLaQuestion(q, contexte)}
                                                    afficherProblemes={afficherProblemes}
                                                    ouverte={ouvertes[i] ?? questions.length <= 3}
                                                    onToggle={() => setOuvertes((o) => ({ ...o, [i]: !(o[i] ?? questions.length <= 3) }))}
                                                    onChange={(nq) => majQuestions(questions.map((x, j) => (j === i ? nq : x)))}
                                                    onMonter={() => majQuestions(deplacer(questions, i, -1))}
                                                    onDescendre={() => majQuestions(deplacer(questions, i, 1))}
                                                    onDupliquer={() => { majQuestions(dupliquer(questions, i)); setOuvertes((o) => ({ ...o, [i + 1]: true })); }}
                                                    onSupprimer={() => majQuestions(retirer(questions, i))}
                                                />
                                            </div>
                                        ))}
                                        <Button type="button" variant="outline" onClick={() => { majQuestions([...questions, emptyQuestion(family)]); setOuvertes((o) => ({ ...o, [questions.length]: true })); }}>
                                            <Plus className="h-4 w-4" aria-hidden="true" /> {t('Ajouter une question')}
                                        </Button>
                                    </div>
                                </>
                            )}
                        </section>

                        {enEdition && !aiLoading && (
                            <aside className="hidden lg:block" aria-label={t('Aperçu en direct')}>
                                <div className="sticky top-20 max-h-[calc(100vh-6rem)] overflow-y-auto rounded-2xl border border-border bg-muted/30 p-4">
                                    <Apercu componentKey={componentKey} skillType={selectedType?.skill_type} content={content} questions={questionsAEnvoyer(questions, content)} lang={exam?.language ?? 'en'} titreDuType={titreDuType} />
                                </div>
                            </aside>
                        )}
                    </div>
                )}

                {etape === 3 && !family && (
                    <p className="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">{t('Choisissez d’abord un type d’exercice.')}</p>
                )}

                {/* ── Etape 4 : apercu final ── */}
                {etape === 4 && family && (
                    <section aria-labelledby="titre-etape-4" className="mx-auto max-w-3xl space-y-4">
                        <div>
                            <h2 id="titre-etape-4" className="text-lg font-bold">{t('Vérifiez avant d’enregistrer')}</h2>
                            <p className="text-sm text-muted-foreground">{titreDuType} · {exam?.name} · {difficulty} · {questions.length > 1 ? t('{n} questions', { n: questions.length }) : t('{n} question', { n: questions.length })}. {t('Répondez comme un élève, puis affichez la correction.')}</p>
                        </div>
                        <div className="rounded-2xl border border-border bg-muted/30 p-4 sm:p-6">
                            <Apercu componentKey={componentKey} skillType={selectedType?.skill_type} content={content} questions={questionsAEnvoyer(questions, content)} lang={exam?.language ?? 'en'} titreDuType={titreDuType} />
                        </div>
                    </section>
                )}
            </div>

            {/* Barre d'actions : collee en bas, a portee de pouce sur telephone. */}
            <div className="sticky bottom-0 z-20 border-t border-border bg-background/95 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] backdrop-blur">
                <div className="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-2">
                    <div>
                        {etape > 1 && !(editing && etape === 3) && (
                            <Button type="button" variant="ghost" onClick={() => aller(etape - 1)}><ArrowLeft className="h-4 w-4" aria-hidden="true" /> {t('Retour')}</Button>
                        )}
                        {editing && etape === 1 && (
                            <Button type="button" variant="ghost" onClick={() => aller(3)}><ArrowLeft className="h-4 w-4" aria-hidden="true" /> {t('Retour au contenu')}</Button>
                        )}
                    </div>
                    <div className="flex flex-wrap items-center justify-end gap-2">
                        {etape === 3 && resume && afficherProblemes && <span className="hidden text-xs font-semibold text-amber-700 sm:inline dark:text-amber-300">{resume}</span>}
                        {etape === 1 && <Button type="button" onClick={() => aller(editing ? 3 : 2)} disabled={!examId}>{t('Continuer')} <ArrowRight className="h-4 w-4" aria-hidden="true" /></Button>}
                        {etape === 2 && <Button type="button" onClick={() => aller(3)} disabled={!typeId}>{t('Continuer')} <ArrowRight className="h-4 w-4" aria-hidden="true" /></Button>}
                        {etape === 3 && enEdition && <Button type="button" onClick={() => aller(4)}>{t('Voir l’aperçu')} <ArrowRight className="h-4 w-4" aria-hidden="true" /></Button>}
                        {etape === 4 && (
                            <>
                                {!devoir && retour !== 'devoir' && (
                                    <Button type="button" variant="outline" onClick={() => save(true)} disabled={saving}>{t('Enregistrer et donner en devoir')}</Button>
                                )}
                                <Button type="button" onClick={() => save(false)} disabled={saving}>{saving ? t('Enregistrement…') : libelleEnregistrer}</Button>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function Etapes({ etape, accessible, onAller }: { etape: number; accessible: (n: number) => boolean; onAller: (n: number) => void }) {
    const { t } = useT();
    return (
        <nav aria-label={t('Étapes')}>
            <ol className="grid grid-cols-4 gap-1.5 sm:gap-3">
                {ETAPES.map((nom, i) => {
                    const n = i + 1;
                    const actuelle = n === etape;
                    const faite = n < etape;
                    const ok = accessible(n);
                    return (
                        <li key={nom}>
                            <button
                                type="button"
                                disabled={!ok || actuelle}
                                onClick={() => onAller(n)}
                                aria-current={actuelle ? 'step' : undefined}
                                className={cn(
                                    'flex w-full flex-col items-start gap-1 rounded-xl border-t-4 pt-2 text-left text-xs font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:text-sm',
                                    actuelle ? 'border-primary text-foreground' : faite ? 'border-primary/50 text-muted-foreground hover:text-foreground' : 'border-border text-muted-foreground',
                                    !ok && 'cursor-not-allowed opacity-60',
                                )}
                            >
                                <span className="flex items-center gap-1">
                                    <span className={cn('inline-flex h-5 w-5 items-center justify-center rounded-full text-[11px]', actuelle || faite ? 'bg-primary text-primary-foreground' : 'bg-muted')}>
                                        {faite ? <Check className="h-3 w-3" aria-hidden="true" /> : n}
                                    </span>
                                    <span className="sr-only">{t('Étape {n} :', { n })}</span>
                                </span>
                                <span className="leading-tight">{t(nom)}</span>
                            </button>
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}

function CarteChoix({ choisi, disabled, onClick, titre }: { choisi: boolean; disabled?: boolean; onClick: () => void; titre: string }) {
    return (
        <button
            type="button"
            role="radio"
            aria-checked={choisi}
            disabled={disabled && !choisi}
            onClick={onClick}
            className={cn(
                'flex min-h-14 items-center justify-between gap-2 rounded-xl border-[1.5px] bg-background px-4 text-left font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50',
                choisi ? 'border-primary ring-1 ring-primary' : 'border-border hover:border-primary/60 hover:bg-muted/40',
            )}
        >
            {titre}
            {choisi && <Check className="h-4 w-4 text-primary" aria-hidden="true" />}
        </button>
    );
}

function CheminCarte({ actif, icone: Icone, titre, texte, onClick, id }: { actif: boolean; icone: typeof Sparkles; titre: string; texte: string; onClick: () => void; id: string }) {
    return (
        <button
            id={id}
            type="button"
            onClick={onClick}
            aria-pressed={actif}
            className={cn(
                'flex items-start gap-3 rounded-2xl border-[1.5px] bg-card p-4 text-left transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                actif ? 'border-primary ring-1 ring-primary' : 'border-border hover:border-primary/60 hover:bg-muted/40',
            )}
        >
            <span className={cn('flex h-10 w-10 shrink-0 items-center justify-center rounded-xl', actif ? 'bg-primary text-primary-foreground' : 'bg-primary/10 text-primary')}>
                <Icone className="h-5 w-5" aria-hidden="true" />
            </span>
            <span>
                <span className="block font-semibold">{titre}</span>
                <span className="block text-sm text-muted-foreground">{texte}</span>
            </span>
        </button>
    );
}
