import type { FormDataConvertible } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ExerciseTimer } from './exercise-timer';
import { ExerciseProgress } from './exercise-progress';
import { useCallback, useEffect, useMemo, useRef, useState, type RefObject } from 'react';
import { BookOpen, ChevronDown, Headphones, Mic, Pause, PenLine, Play, Puzzle } from 'lucide-react';
import type { ExerciseRecord } from '@/types';
import { prefetchExercisesAudio } from '@/lib/tts-cache';
import { useProgressionDansLEntete } from '@/layouts/exercise-layout';
import { cn } from '@/lib/utils';

// Exercise Components
import { Mcq } from './mcq';
import { TrueFalseNg } from './true-false-ng';
import { GapFill } from './gap-fill';
import { Matching } from './matching';
import { EssayEditor } from './essay-editor';
import { SpeakingRecorder } from './speaking-recorder';
import { Dictation } from './dictation';
import { ShortAnswer } from './short-answer';
import { SentenceCompletion } from './sentence-completion';
import { NoteCompletion } from './note-completion';
import { SummaryCompletion } from './summary-completion';
import { TableCompletion } from './table-completion';
import { FlowChartCompletion } from './flow-chart-completion';
import { FormCompletion } from './form-completion';
import { DiagramLabeling } from './diagram-labeling';
import { GraphDescription } from './graph-description';
import { InsertText } from './insert-text';
import { OpenCloze } from './open-cloze';
import { WordFormation } from './word-formation';
import { KeyWordTransformation } from './key-word-transformation';
import { GappedText } from './gapped-text';
import { MultipleMatching } from './multiple-matching';
import { Ordering } from './ordering';
import { ShortWriting } from './short-writing';
import { IntegratedTask } from './integrated-task';
import { isBlankAnswer } from '@/lib/scoring';
import { cleDeReprise, ecrireReprise, effacerReprise, FORMATS_SANS_REPRISE, lireReprise, stockageDuNavigateur } from '@/lib/reprise-exercice';
import { AcademicDiscussion } from './academic-discussion';
import { RolePlay } from './role-play';
import { Synthesis } from './synthesis';
import { VocabularyCard } from './vocabulary-card';
import { ListenRepeat } from './listen-repeat';
import { PictureMcq } from './picture-mcq';
import { CompleteTheWords } from './complete-the-words';
import { BuildASentence } from './build-a-sentence';
import { ListenChooseResponse } from './listen-choose-response';
import { GuidedWriting } from './guided-writing';
import { SpeakButton } from './speak-button';
import { ExerciseErrorBoundary } from './exercise-error-boundary';

interface ExercisePlayerProps {
    exercise: ExerciseRecord;
    /**
     * Ou envoyer les reponses. Par defaut, la correction d'un apprenant, qui
     * enregistre sa tentative. L'essai sans compte passe la sienne, qui corrige
     * sans rien enregistrer.
     */
    submitUrl?: string;
    /**
     * Essai sans compte : le chronometre compte sans s'afficher, et l'action
     * principale prend l'orange de l'essai (comme sur la page d'accueil).
     */
    essai?: boolean;
}

/**
 * Le bouton d'ecoute : TOUT le cadre est le bouton.
 *
 * Seul le mot « Ecouter » (79 x 20 px) repondait au clic dans un cadre de toute
 * la largeur. Le cadre fait maintenant au moins 64 px de haut, porte une grande
 * icone lecture/pause et une barre d'avancement. Le choix de la piste ne change
 * pas : c'est toujours `question.audio_url`.
 */
function AudioPlayer({ src }: { src: string }) {
    const audioRef = useRef<HTMLAudioElement>(null);
    const [playing, setPlaying] = useState(false);
    const [playCount, setPlayCount] = useState(0);
    const [avance, setAvance] = useState(0);

    useEffect(() => {
        setPlaying(false);
        setPlayCount(0);
        setAvance(0);
    }, [src]);

    const toggle = () => {
        const el = audioRef.current;
        if (!el) return;
        if (playing) {
            el.pause();
            setPlaying(false);
        } else {
            el.currentTime = 0;
            el.play().catch(() => setPlaying(false));
            setPlaying(true);
        }
    };

    const libelle = playing ? 'En cours…' : playCount === 0 ? 'Écouter l’enregistrement' : `Réécouter (${playCount}×)`;

    return (
        <>
            <audio
                ref={audioRef}
                src={src}
                onTimeUpdate={(e) => {
                    const el = e.currentTarget;
                    setAvance(el.duration > 0 ? Math.min(1, el.currentTime / el.duration) : 0);
                }}
                onEnded={() => { setPlaying(false); setAvance(1); setPlayCount(c => c + 1); }}
            />
            <button
                type="button"
                onClick={toggle}
                aria-label={playing ? 'Mettre l’enregistrement en pause' : libelle}
                className="flex min-h-16 w-full items-center gap-4 rounded-2xl border-[1.5px] border-primary/40 bg-primary/5 px-4 py-3 text-left transition-colors hover:bg-primary/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
            >
                <span aria-hidden="true" className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                    {playing ? <Pause className="h-6 w-6" /> : <Play className="ml-0.5 h-6 w-6" />}
                </span>
                <span className="min-w-0 flex-1">
                    <span className="block text-base font-semibold text-foreground">{libelle}</span>
                    <span aria-hidden="true" className="mt-2 block h-1.5 overflow-hidden rounded-full bg-primary/15">
                        <span className="block h-full rounded-full bg-primary" style={{ width: `${Math.round(avance * 100)}%` }} />
                    </span>
                </span>
            </button>
        </>
    );
}

/** Les caracteres qu'un clavier francais ou un telephone font chercher. */
const CARACTERES: Record<string, { minuscules: string[]; majuscules: string[] }> = {
    german: { minuscules: ['ä', 'ö', 'ü', 'ß'], majuscules: ['Ä', 'Ö', 'Ü'] },
    french: {
        minuscules: ['é', 'è', 'ê', 'ë', 'à', 'â', 'ç', 'î', 'ï', 'ô', 'œ', 'ù', 'û'],
        majuscules: ['É', 'È', 'Ê', 'À', 'Ç', 'Î', 'Ô', 'Œ', 'Ù'],
    },
};

const CHAMP_TEXTE = 'input[type="text"]:not(:disabled), textarea:not(:disabled)';

/**
 * La barre ä ö ü ß (ou é è ç… en francais) sous les champs de saisie.
 *
 * Elle n'apparait que si la question a un champ ou ecrire. Un clic insere le
 * caractere a l'endroit du curseur dans le dernier champ touche ; le champ
 * garde le focus (le bouton ne le prend pas).
 */
function CaracteresSpeciaux({ langue, zone, cle }: { langue: string; zone: RefObject<HTMLElement | null>; cle: string }) {
    const jeu = CARACTERES[langue];
    const [visible, setVisible] = useState(false);
    const [majuscules, setMajuscules] = useState(false);
    const dernierChamp = useRef<HTMLInputElement | HTMLTextAreaElement | null>(null);

    useEffect(() => {
        const el = zone.current;
        if (!el || !jeu) return;
        dernierChamp.current = null;
        const verifier = () => setVisible(el.querySelector(CHAMP_TEXTE) !== null);
        verifier();
        const surFocus = (e: FocusEvent) => {
            const t = e.target;
            if ((t instanceof HTMLInputElement && t.type === 'text') || t instanceof HTMLTextAreaElement) dernierChamp.current = t;
        };
        el.addEventListener('focusin', surFocus);
        const observateur = new MutationObserver(verifier);
        observateur.observe(el, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled'] });
        return () => {
            el.removeEventListener('focusin', surFocus);
            observateur.disconnect();
        };
    }, [zone, jeu, cle]);

    if (!jeu || !visible) return null;

    const inserer = (c: string) => {
        const el = zone.current;
        const memorise = dernierChamp.current;
        const champ = memorise && el?.contains(memorise) && !memorise.disabled ? memorise : el?.querySelector<HTMLInputElement | HTMLTextAreaElement>(CHAMP_TEXTE);
        if (!champ) return;
        const debut = champ.selectionStart ?? champ.value.length;
        const fin = champ.selectionEnd ?? debut;
        const valeur = champ.value.slice(0, debut) + c + champ.value.slice(fin);
        // Les champs sont controles par React : on passe par le setter natif puis
        // un evenement « input », sinon React ne verrait pas le changement.
        const proto = champ instanceof HTMLTextAreaElement ? HTMLTextAreaElement.prototype : HTMLInputElement.prototype;
        Object.getOwnPropertyDescriptor(proto, 'value')?.set?.call(champ, valeur);
        champ.dispatchEvent(new Event('input', { bubbles: true }));
        champ.focus();
        champ.setSelectionRange(debut + c.length, debut + c.length);
        dernierChamp.current = champ;
    };

    const garderLeFocus = (e: React.MouseEvent) => e.preventDefault();
    const touche = 'inline-flex h-11 min-w-11 items-center justify-center rounded-xl border-[1.5px] border-border bg-card px-2 text-lg font-semibold text-foreground transition-colors hover:border-primary/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';

    return (
        <div role="group" aria-label="Caractères spéciaux" className="flex flex-wrap items-center gap-1.5">
            {(majuscules ? jeu.majuscules : jeu.minuscules).map((c) => (
                <button key={c} type="button" onMouseDown={garderLeFocus} onClick={() => inserer(c)} aria-label={`Insérer ${c}`} className={touche}>
                    {c}
                </button>
            ))}
            <button
                type="button"
                onMouseDown={garderLeFocus}
                onClick={() => setMajuscules((m) => !m)}
                aria-pressed={majuscules}
                aria-label="Majuscules"
                className={cn(touche, 'px-3 text-[0.9375rem]', majuscules && 'border-primary bg-primary/10')}
            >
                Maj
            </button>
        </div>
    );
}

const COMPETENCES: Record<string, { nom: string; icone: typeof BookOpen }> = {
    reading: { nom: 'Lecture', icone: BookOpen },
    listening: { nom: 'Écoute', icone: Headphones },
    writing: { nom: 'Écrit', icone: PenLine },
    speaking: { nom: 'Oral', icone: Mic },
    grammar: { nom: 'Grammaire', icone: Puzzle },
    vocabulary: { nom: 'Vocabulaire', icone: BookOpen },
};

// eslint-disable-next-line @typescript-eslint/no-explicit-any -- each renderer declares its own question and answer shape
const componentMap: Record<string, React.ComponentType<any>> = {
    'mcq': Mcq,
    'true-false-ng': TrueFalseNg,
    'gap-fill': GapFill,
    'matching': Matching,
    'essay-editor': EssayEditor,
    'speaking-recorder': SpeakingRecorder,
    'dictation': Dictation,
    'short-answer': ShortAnswer,
    'sentence-completion': SentenceCompletion,
    'note-completion': NoteCompletion,
    'summary-completion': SummaryCompletion,
    'table-completion': TableCompletion,
    'flow-chart-completion': FlowChartCompletion,
    'form-completion': FormCompletion,
    'diagram-labeling': DiagramLabeling,
    'graph-description': GraphDescription,
    'insert-text': InsertText,
    'open-cloze': OpenCloze,
    'word-formation': WordFormation,
    'key-word-transformation': KeyWordTransformation,
    'gapped-text': GappedText,
    'multiple-matching': MultipleMatching,
    'ordering': Ordering,
    'short-writing': ShortWriting,
    'integrated-task': IntegratedTask,
    'academic-discussion': AcademicDiscussion,
    'role-play': RolePlay,
    'synthesis': Synthesis,
    'vocabulary-card': VocabularyCard,
    'listen-repeat': ListenRepeat,
    'picture-mcq': PictureMcq,
    'complete-the-words': CompleteTheWords,
    'build-a-sentence': BuildASentence,
    'listen-choose-response': ListenChooseResponse,
    'guided-writing': GuidedWriting,
};

/**
 * Le composant qui affiche une question de ce format, celui du lecteur. Le
 * createur d'exercices des centres s'en sert pour son apercu : l'enseignant voit
 * exactement ce que l'eleve verra, pas une maquette.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any -- each renderer declares its own question and answer shape
export function composantDuLecteur(componentKey: string): React.ComponentType<any> {
    return componentMap[componentKey] ?? Mcq;
}

export function ExercisePlayer({ exercise, submitUrl, essai = false }: ExercisePlayerProps) {
    const [currentIndex, setCurrentIndex] = useState(0);
    const [answers, setAnswers] = useState<Record<string, FormDataConvertible>>({});
    const timeRef = useRef(0);
    const [submitting, setSubmitting] = useState(false);
    const [submitError, setSubmitError] = useState<string | null>(null);
    const submitPending = useRef(false);
    // Reprise apres une coupure : voir lib/reprise-exercice.js.
    const page = usePage();
    const compte = (page.props as { auth?: { user?: { id?: number } | null } }).auth?.user?.id ?? null;
    const cleReprise = cleDeReprise(exercise.id, compte);
    const [repris, setRepris] = useState(false);
    // Change a chaque reprise ou « Recommencer » : les formats gardent leur saisie
    // dans leur propre etat, lu une seule fois au montage ; on les remonte donc.
    const [generation, setGeneration] = useState(0);
    const tempsDejaPasse = useRef(0);
    const repriseLue = useRef(false);
    const envoiTermine = useRef(false);

    const questions = exercise.questions ?? [];
    const question = questions[currentIndex];
    const componentKey = exercise.exercise_type?.component_key ?? 'mcq';
    const Component = componentMap[componentKey] ?? Mcq;
    const isLast = currentIndex === questions.length - 1;
    const lang = exercise.exam?.language?.slug ?? 'en';
    const skillType = exercise.exercise_type?.skill_type;
    const isStarter = exercise.content?.source === 'starter-library';
    const repriseImpossible = FORMATS_SANS_REPRISE.includes(componentKey);
    const idsDesQuestions = useMemo(() => (exercise.questions ?? []).map((q) => String(q.id)), [exercise.questions]);
    const passage = exercise.content?.passage ?? exercise.content?.text;
    // Le passage parle n'est pas toujours range dans la question : le generateur
    // l'ecrit souvent dans l'exercice, sous « audio_text », parfois sous « passage »
    // quand il a cru ecrire une lecture. Le pre-chargement suivait deja cette regle
    // (tts-cache) : le son etait prepare, mais aucun bouton ne le jouait, et
    // l'epreuve sortait muette.
    const texteAEcouter = useMemo(() => {
        const dit = (v: unknown) => (typeof v === 'string' && v.trim() !== '' ? v.trim() : '');
        const deLaQuestion = dit(question?.audio_text);
        if (deLaQuestion) return deLaQuestion;
        if (skillType !== 'listening') return '';
        const c = (exercise.content ?? {}) as Record<string, unknown>;
        return dit(c.audio_text) || dit(c.transcript) || dit(c.script) || dit(c.dialogue) || dit(passage);
    }, [question, skillType, exercise.content, passage]);

    const currentAnswer = answers[question?.id];
    // Meme regle que le lecteur du parcours : un objet dont tous les champs sont
    // vides, ou un enregistrement efface, ne sont pas des reponses.
    const hasAnswer = !isBlankAnswer(currentAnswer);

    // Prefetch the exercise's audio at mount (TTS texts + pre-generated MP3s)
    // so "Écouter" plays instantly instead of calling the TTS API at click time.
    useEffect(() => {
        prefetchExercisesAudio([exercise], lang);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // Relue apres le montage, pas pendant le rendu : le rendu serveur n'a pas de
    // localStorage, et lire avant l'hydratation ferait diverger les deux rendus.
    useEffect(() => {
        repriseLue.current = true;
        if (repriseImpossible) return;
        const reprise = lireReprise(stockageDuNavigateur(), cleReprise, idsDesQuestions);
        if (!reprise) return;
        setAnswers(reprise.answers);
        setCurrentIndex(reprise.index);
        tempsDejaPasse.current = reprise.tempsPasse;
        setRepris(true);
        setGeneration((g) => g + 1);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // A chaque reponse ou changement de question. Rien n'est ecrit tant qu'aucune
    // reponse n'est donnee : la sauvegarde existante n'est donc jamais ecrasee par
    // l'etat vide du premier rendu.
    useEffect(() => {
        if (!repriseLue.current || repriseImpossible || envoiTermine.current) return;
        ecrireReprise(stockageDuNavigateur(), cleReprise, {
            index: currentIndex,
            answers,
            questionIds: idsDesQuestions,
            tempsPasse: tempsDejaPasse.current + timeRef.current,
        });
    }, [answers, currentIndex, cleReprise, idsDesQuestions, repriseImpossible]);

    function recommencer() {
        effacerReprise(stockageDuNavigateur(), cleReprise);
        setAnswers({});
        setCurrentIndex(0);
        setSubmitError(null);
        tempsDejaPasse.current = 0;
        timeRef.current = 0;
        setRepris(false);
        setGeneration((g) => g + 1);
    }

    const handleAnswer = useCallback((questionId: string, answer: FormDataConvertible) => {
        setAnswers((prev) => ({ ...prev, [questionId]: answer }));
    }, []);

    const handleTimeUpdate = useCallback((seconds: number) => {
        timeRef.current = seconds;
    }, []);

    // La progression s'affiche dans l'en-tete collant quand la mise en page en
    // offre un ; sinon le lecteur l'affiche lui-meme.
    const definirProgression = useProgressionDansLEntete();
    useEffect(() => {
        definirProgression?.({ current: currentIndex + 1, total: questions.length });
    }, [definirProgression, currentIndex, questions.length]);
    useEffect(() => () => definirProgression?.(null), [definirProgression]);

    // Le texte a lire : toujours visible a cote de la question sur ordinateur ;
    // sur telephone, deplie a la premiere question puis replie (« Revoir le
    // texte ») pour que la question suivante arrive sans tout faire defiler.
    const [passageOuvert, setPassageOuvert] = useState(true);
    const zoneQuestion = useRef<HTMLElement>(null);
    const premiereQuestion = useRef(true);

    // A chaque nouvelle question, le focus va sur l'enonce : un lecteur d'ecran
    // le lit, et au clavier on repart de la question, pas du bas de la page.
    useEffect(() => {
        if (premiereQuestion.current) {
            premiereQuestion.current = false;
            return;
        }
        setPassageOuvert(currentIndex === 0);
        const zone = zoneQuestion.current;
        if (!zone) return;
        const cible = zone.querySelector<HTMLElement>('[data-enonce]') ?? zone;
        if (!cible.hasAttribute('tabindex')) cible.setAttribute('tabindex', '-1');
        cible.focus({ preventScroll: true });
        const reduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        zone.scrollIntoView({ block: 'start', behavior: reduit ? 'auto' : 'smooth' });
    }, [currentIndex]);

    const peutAvancer = (hasAnswer || componentKey === 'speaking-recorder') && !submitting;
    const avancer = useRef<() => void>(() => {});
    avancer.current = () => {
        if (peutAvancer) handleNext();
    };

    // Clavier : 1-9 ou A-H choisissent un choix, Entree passe a la suite.
    // Dans un champ de saisie, Entree va au champ suivant, puis a la suite apres
    // le dernier ; les lettres et chiffres y restent de la saisie.
    useEffect(() => {
        const surTouche = (e: KeyboardEvent) => {
            if (e.defaultPrevented || e.ctrlKey || e.metaKey || e.altKey || e.isComposing) return;
            const zone = zoneQuestion.current;
            if (!zone) return;
            const cible = e.target instanceof HTMLElement ? e.target : null;
            const enSaisie = !!cible && (cible.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(cible.tagName));

            if (e.key === 'Enter') {
                if (cible instanceof HTMLInputElement && cible.type === 'text' && zone.contains(cible)) {
                    e.preventDefault();
                    const champs = Array.from(zone.querySelectorAll<HTMLInputElement>('input[type="text"]:not(:disabled)'));
                    const rang = champs.indexOf(cible);
                    if (rang >= 0 && rang < champs.length - 1) champs[rang + 1].focus();
                    else avancer.current();
                    return;
                }
                // Entree sur le choix deja retenu : on valide (apres « 2 », le
                // focus est sur ce choix). Sur un autre bouton ou un lien, Entree
                // garde son sens habituel.
                const choixRetenu = cible?.closest('[data-choix][aria-pressed="true"]');
                if (choixRetenu && zone.contains(choixRetenu)) {
                    e.preventDefault();
                    avancer.current();
                    return;
                }
                if (enSaisie || cible?.closest('button, a, summary, [role="button"]')) return;
                e.preventDefault();
                avancer.current();
                return;
            }

            if (enSaisie) return;
            let rang = -1;
            if (/^[1-9]$/.test(e.key)) rang = Number(e.key) - 1;
            else if (/^[a-h]$/i.test(e.key)) rang = e.key.toUpperCase().charCodeAt(0) - 65;
            if (rang < 0) return;
            const choix = zone.querySelector<HTMLButtonElement>(`[data-choix="${rang}"]:not(:disabled)`);
            if (!choix) return;
            e.preventDefault();
            choix.click();
            choix.focus();
        };
        document.addEventListener('keydown', surTouche);
        return () => document.removeEventListener('keydown', surTouche);
    }, []);

    function handleNext() {
        if (isLast) {
            handleSubmit();
        } else {
            setCurrentIndex((prev) => prev + 1);
        }
    }

    function handleSubmit() {
        if (submitPending.current) return;
        submitPending.current = true;
        setSubmitting(true);
        setSubmitError(null);
        // Force FormData if there are any Blobs/Files
        const hasFiles = Object.values(answers).some(a => a instanceof Blob || a instanceof File);
        
        router.post(submitUrl ?? route('exercise.submit', exercise.id), {
            answers,
            time_spent: tempsDejaPasse.current + timeRef.current,
        }, {
            forceFormData: hasFiles,
            // Correction affichee : l'exercice est fini, la reprise n'a plus lieu
            // d'etre. Un renvoi sur ce meme lecteur (envoi encore en cours cote
            // serveur) n'en est pas un : on garde alors la sauvegarde.
            onSuccess: (suite) => {
                if (suite.component === page.component) return;
                envoiTermine.current = true;
                effacerReprise(stockageDuNavigateur(), cleReprise);
            },
            onError: () => setSubmitError('L’envoi n’a pas abouti. Tes réponses sont conservées sur cette page ; tu peux réessayer.'),
            onFinish: () => {
                submitPending.current = false;
                setSubmitting(false);
            },
        });
    }

    if (!question) {
        return <p className="text-center text-muted-foreground">Aucune question dans cet exercice.</p>;
    }

    const avecPassage = skillType !== 'listening' && typeof passage === 'string' && passage.trim() !== '';
    const competence = COMPETENCES[skillType ?? ''];
    const instructions = typeof exercise.content?.instructions === 'string' ? exercise.content.instructions : '';
    const description = typeof exercise.content?.description === 'string' ? exercise.content.description : 'Entraînement général, indépendant des sujets officiels d’examen.';

    // Precedent / Suivant : une fois en bas d'ecran (telephone), une fois sous la
    // question (ordinateur) ; une seule des deux est affichee.
    const actions = (
        <div className={cn('flex gap-3', !avecPassage && 'mx-auto max-w-3xl lg:mx-0 lg:max-w-none')}>
            <button
                type="button"
                disabled={currentIndex === 0 || submitting}
                onClick={() => setCurrentIndex((prev) => prev - 1)}
                className="inline-flex h-12 shrink-0 items-center justify-center rounded-full border-[1.5px] border-border bg-card px-5 text-[0.9375rem] font-semibold text-foreground transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50"
            >
                Précédent
            </button>
            <button
                type="button"
                onClick={handleNext}
                disabled={!peutAvancer}
                aria-keyshortcuts="Enter"
                className={cn(
                    'inline-flex h-12 flex-1 items-center justify-center rounded-full px-6 text-base font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 lg:ml-auto lg:flex-none lg:min-w-40',
                    essai ? 'bg-[var(--essai)] text-[var(--sur-essai)] hover:brightness-105' : 'bg-primary text-primary-foreground hover:bg-primary/90',
                )}
            >
                {isLast ? (submitting ? 'Envoi…' : 'Terminer') : 'Suivant'}
            </button>
        </div>
    );

    return (
        <div className="flex flex-1 flex-col gap-4">
            {/* Une ligne de presentation au lieu de la grande carte et de l'encadre
                « Bibliotheque » : la question doit etre visible des le chargement. */}
            <div className="flex flex-wrap items-center gap-2">
                {competence && (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-[0.8125rem] font-semibold text-primary">
                        <competence.icone aria-hidden="true" className="h-3.5 w-3.5" />
                        {competence.nom}
                    </span>
                )}
                {isStarter && (
                    <span className="rounded-full bg-emerald-100 px-3 py-1 text-[0.8125rem] font-semibold text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100">
                        Bibliothèque PrepLa · {exercise.difficulty} · sans IA
                    </span>
                )}
                {/* Le temps est toujours compte (reprise comprise) ; en essai, il
                    n'est simplement pas affiche. */}
                <div className="ml-auto">
                    <ExerciseTimer key={generation} onTimeUpdate={handleTimeUpdate} decalage={tempsDejaPasse.current} masque={essai} />
                </div>
            </div>

            {/* La description de la bibliotheque reste un vrai paragraphe : en
                infobulle, elle etait illisible au toucher. */}
            {isStarter && (
                <p className="max-w-[70ch] text-[0.875rem] leading-relaxed text-muted-foreground">
                    {description} Prends ton temps : tu retrouveras tes réponses et leurs explications à la fin.
                </p>
            )}

            {instructions !== '' && <p className="max-w-[70ch] text-[0.9375rem] leading-relaxed text-muted-foreground">{instructions}</p>}

            {repris && (
                <div className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-dashed px-4 py-2 text-[0.875rem] text-muted-foreground">
                    <span>Tu reprends là où tu t’étais arrêté.</span>
                    <Button variant="link" size="sm" className="h-11 p-0 text-[0.875rem]" disabled={submitting} onClick={recommencer}>
                        Recommencer
                    </Button>
                </div>
            )}

            {!definirProgression && <ExerciseProgress current={currentIndex + 1} total={questions.length} />}

            <div className={cn('w-full', avecPassage ? 'lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-start lg:gap-8' : 'mx-auto max-w-3xl')}>
                {/* Le texte a lire — jamais pour une ecoute (c'est la transcription
                    a entendre, pas a lire). Sur ordinateur, il reste colle a gauche
                    pendant qu'on repond a droite : texte et question enfin a l'ecran
                    ensemble. `whitespace-pre-line` : les sauts de ligne comptent,
                    une partie qui donne DEUX documents ne doit pas les recoller. */}
                {avecPassage && (
                    <aside aria-label="Texte à lire" className="mb-4 lg:sticky lg:top-24 lg:mb-0 lg:max-h-[calc(100vh-7.5rem)] lg:overflow-y-auto">
                        <button
                            type="button"
                            aria-expanded={passageOuvert}
                            aria-controls={`passage-${exercise.id}`}
                            onClick={() => setPassageOuvert((o) => !o)}
                            className="flex min-h-12 w-full items-center justify-between gap-2 rounded-2xl border-[1.5px] border-border bg-card px-4 text-left text-[0.9375rem] font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring lg:hidden"
                        >
                            {passageOuvert ? 'Masquer le texte' : 'Revoir le texte'}
                            <ChevronDown aria-hidden="true" className={cn('h-5 w-5 transition-transform', passageOuvert && 'rotate-180')} />
                        </button>
                        <div
                            id={`passage-${exercise.id}`}
                            className={cn('mt-2 rounded-2xl border-[1.5px] border-border bg-card p-4 sm:p-5 lg:mt-0 lg:block', !passageOuvert && 'hidden')}
                        >
                            <p className="lecteur-passage text-foreground">{passage}</p>
                        </div>
                    </aside>
                )}

                <section ref={zoneQuestion} aria-label={`Question ${currentIndex + 1} sur ${questions.length}`} className="scroll-mt-28 space-y-5">
                    {/* Ecoute : la piste pre-generee de la question. */}
                    {question.audio_url && componentKey !== 'dictation' && <AudioPlayer src={question.audio_url} />}

                    {/* Fallback TTS : exercice d'écoute sans audio pré-généré mais avec un texte à dire.
                        Évite un listening totalement muet ; lit le texte via Deepgram dans la bonne langue. */}
                    {!question.audio_url && skillType === 'listening' && texteAEcouter !== '' && (
                        <div className="flex min-h-16 items-center gap-3 rounded-2xl border-[1.5px] border-primary/40 bg-primary/5 px-4 py-3">
                            <SpeakButton text={texteAEcouter} lang={lang} label="Écouter l'enregistrement" />
                        </div>
                    )}

                    <ExerciseErrorBoundary resetKey={question.id} onSkip={() => handleAnswer(question.id, '__skipped__')}>
                        {/* `disabled` verrouille pendant l'envoi ; `revealed` reste faux :
                            la correction n'apparait que sur la page de resultat. Avant,
                            chaque choix fait passait au rouge (« Reponse incorrecte »)
                            pendant « Envoi… », et un compte voyait la bonne reponse
                            s'afficher en vert un instant. */}
                        <Component
                            key={`${question.id}-${generation}`}
                            question={question}
                            onAnswer={handleAnswer}
                            selectedAnswer={answers[question.id]}
                            lang={lang}
                            disabled={submitting}
                            revealed={false}
                        />
                    </ExerciseErrorBoundary>

                    <CaracteresSpeciaux langue={lang} zone={zoneQuestion} cle={`${question.id}-${generation}`} />

                    {submitError && <p role="alert" className="text-[0.9375rem] text-destructive">{submitError}</p>}

                    <div className="hidden pt-2 lg:block">{actions}</div>
                </section>
            </div>

            {/* Sur telephone, les actions restent collees en bas, a portee de pouce
                (48 px de haut au moins). Sur ordinateur, elles suivent la question,
                dans sa colonne (voir plus haut). */}
            <div className="sticky bottom-0 z-20 -mx-4 mt-auto border-t border-border/70 bg-background/90 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] backdrop-blur-xl lg:hidden">
                {actions}
            </div>
        </div>
    );
}
