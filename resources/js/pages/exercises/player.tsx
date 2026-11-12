import AppLayout from '@/layouts/app-layout';
import { coerceOption, collection } from '@/components/exercises/normalize-options';
import { assessmentAnswers } from '@/lib/session-answers.js';
import type { FormDataConvertible } from '@inertiajs/core';
import { Head, router } from '@inertiajs/react';
import React, { useState, useCallback, useMemo, useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import { useTranslation } from 'react-i18next';
import { playSound } from '@/hooks/use-sound';
import { getCachedTtsUrl, rememberTtsUrl, prefetchExercisesAudio } from '@/lib/tts-cache';
import { evaluateAnswer, expectedAnswerText, isBlankAnswer, isSkippedAnswer, needsServerEvaluation, optionList } from '@/lib/scoring';
import { AlertCircle, BookOpen, Check, ChevronDown, ChevronRight, Headphones, Info, Loader2, MessageSquare, Mic, PenLine, Play, Plus, Puzzle, Search, Volume1, Volume2, X } from 'lucide-react';
import { cn } from '@/lib/utils';

// Read the freshest CSRF token. The XSRF-TOKEN cookie tracks the live session,
// whereas the <meta> tag is frozen at page load and goes stale on a long-running
// session — which caused 419s on /api/tts/speak and the AI endpoints.
function csrfToken(): string {
    const cookie = document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='));
    if (cookie) return decodeURIComponent(cookie.split('=')[1]);
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
}

import { Mcq } from '@/components/exercises/mcq';
import { TrueFalseNg } from '@/components/exercises/true-false-ng';
import { GapFill } from '@/components/exercises/gap-fill';
import { Matching } from '@/components/exercises/matching';
import { EssayEditor } from '@/components/exercises/essay-editor';
import { SentenceCompletion } from '@/components/exercises/sentence-completion';
import { ShortAnswer } from '@/components/exercises/short-answer';
import { NoteCompletion } from '@/components/exercises/note-completion';
import { Ordering } from '@/components/exercises/ordering';
import { Dictation } from '@/components/exercises/dictation';
import { OpenCloze } from '@/components/exercises/open-cloze';
import { WordFormation } from '@/components/exercises/word-formation';
import { KeyWordTransformation } from '@/components/exercises/key-word-transformation';
import { ShortWriting } from '@/components/exercises/short-writing';
import { FormCompletion } from '@/components/exercises/form-completion';
import { SummaryCompletion } from '@/components/exercises/summary-completion';
import { TableCompletion } from '@/components/exercises/table-completion';
import { FlowChartCompletion } from '@/components/exercises/flow-chart-completion';
import { MultipleMatching } from '@/components/exercises/multiple-matching';
import { InsertText } from '@/components/exercises/insert-text';
import { GappedText } from '@/components/exercises/gapped-text';
import { GraphDescription } from '@/components/exercises/graph-description';
import { AcademicDiscussion } from '@/components/exercises/academic-discussion';
import { SpeakingRecorder } from '@/components/exercises/speaking-recorder';
import { RolePlay } from '@/components/exercises/role-play';
import { DiagramLabeling } from '@/components/exercises/diagram-labeling';
import { Synthesis } from '@/components/exercises/synthesis';
import { IntegratedTask } from '@/components/exercises/integrated-task';
import { ExerciseErrorBoundary } from '@/components/exercises/exercise-error-boundary';
import { VocabularyCard } from '@/components/exercises/vocabulary-card';
import { ListenRepeat } from '@/components/exercises/listen-repeat';
import { PictureMcq } from '@/components/exercises/picture-mcq';
import { CompleteTheWords } from '@/components/exercises/complete-the-words';
import { BuildASentence } from '@/components/exercises/build-a-sentence';
import { ListenChooseResponse } from '@/components/exercises/listen-choose-response';
import { GuidedWriting } from '@/components/exercises/guided-writing';
import { ExerciseTimer } from '@/components/exercises/exercise-timer';
import { AiChatFeedback } from '@/components/exercises/ai-chat-feedback';
import { nomFichierAudio } from '@/lib/format-audio';

interface ExerciseContent {
    passage?: string;
    audio_text?: string;
    audio_url?: string;
    [key: string]: unknown;
}

interface PlayerQuestion {
    id?: string;
    text?: string;
    prompt?: string;
    correct_answer?: FormDataConvertible;
    correct?: FormDataConvertible;
    correct_answers?: Record<string, string>;
    audio_url?: string;
    audio_text?: string;
    component_key?: string;
    exercise_id?: number;
    // Contexte emporté par une erreur rejouée en révision (voir questionContext).
    skill_type?: string;
    type_name?: string;
    passage?: string;
    [key: string]: unknown;
}

interface DictionaryLookup {
    id?: number;
    word?: string;
    definition?: string;
    translation?: string;
    skill_level?: string;
    error?: boolean;
}

interface Exercise {
    id: number;
    exercise_type: {
        component_key: string;
        name: string;
        skill_type?: string;
    };
    questions: PlayerQuestion[];
    content: ExerciseContent;
    difficulty: string;
}

interface Props {
    node: {
        id: number;
        title: string;
        level: string;
        exam: {
            name: string;
            language: {
                name: string;
                slug?: string;
            }
        }
    };
    exercises: Exercise[];
    progress: {
        id: number;
        status: string;
    };
    /** Jeton a usage unique de cette seance : il empeche qu'un renvoi recredite l'XP. */
    sessionToken?: string;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any -- each renderer declares its own question and answer shape
const componentMap: Record<string, React.ComponentType<any>> = {
    'mcq': Mcq,
    'true-false-ng': TrueFalseNg,
    'gap-fill': GapFill,
    'matching': Matching,
    'essay-editor': EssayEditor,
    'sentence-completion': SentenceCompletion,
    'short-answer': ShortAnswer,
    'note-completion': NoteCompletion,
    'ordering': Ordering,
    'dictation': Dictation,
    'open-cloze': OpenCloze,
    'word-formation': WordFormation,
    'key-word-transformation': KeyWordTransformation,
    'short-writing': ShortWriting,
    'form-completion': FormCompletion,
    'summary-completion': SummaryCompletion,
    'table-completion': TableCompletion,
    'flow-chart-completion': FlowChartCompletion,
    'multiple-matching': MultipleMatching,
    'insert-text': InsertText,
    'gapped-text': GappedText,
    'graph-description': GraphDescription,
    'academic-discussion': AcademicDiscussion,
    'speaking-recorder': SpeakingRecorder,
    'role-play': RolePlay,
    'diagram-labeling': DiagramLabeling,
    'synthesis': Synthesis,
    'integrated-task': IntegratedTask,
    'vocabulary-card': VocabularyCard,
    'listen-repeat': ListenRepeat,
    'picture-mcq': PictureMcq,
    'complete-the-words': CompleteTheWords,
    'build-a-sentence': BuildASentence,
    'listen-choose-response': ListenChooseResponse,
    'guided-writing': GuidedWriting,
};

// Short action instruction per exercise type — answers the user's complaint that
// some exercises only show a "submit"/"validate" button with no cue about what to
// do. Rendered as a small badge above each question card so every type is covered
// consistently (instead of patching 30 components individually).
const INSTRUCTIONS: Record<string, string> = {
    'mcq': 'Choisis la bonne réponse',
    'true-false-ng': 'Choisis : Vrai, Faux ou Non mentionné',
    'gap-fill': 'Complète avec le mot manquant',
    'matching': 'Associe chaque élément à sa correspondance',
    'multiple-matching': 'Associe chaque élément à sa correspondance',
    'sentence-completion': 'Complète la phrase',
    'short-answer': 'Écris ta réponse, puis valide',
    'note-completion': 'Complète les notes',
    'ordering': 'Remets les éléments dans le bon ordre',
    'dictation': 'Écoute puis écris ce que tu entends',
    'open-cloze': 'Complète le texte avec le mot juste',
    'word-formation': 'Forme le mot correct',
    'key-word-transformation': 'Réécris la phrase avec le mot imposé',
    'short-writing': 'Rédige ta réponse, puis valide',
    'essay-editor': 'Rédige ton texte, puis valide',
    'form-completion': 'Complète le formulaire',
    'summary-completion': 'Complète le résumé',
    'table-completion': 'Complète le tableau',
    'flow-chart-completion': 'Complète le schéma',
    'insert-text': 'Insère la phrase au bon endroit',
    'gapped-text': 'Replace chaque paragraphe au bon endroit',
    'graph-description': 'Décris le graphique, puis valide',
    'academic-discussion': 'Rédige ta contribution, puis valide',
    'speaking-recorder': 'Enregistre ta réponse à voix haute',
    'role-play': 'Réponds à voix haute, puis valide',
    'diagram-labeling': 'Étiquette le schéma',
    'synthesis': 'Rédige ta synthèse, puis valide',
    'integrated-task': 'Lis/écoute puis rédige ta réponse',
    'vocabulary-card': 'Mémorise puis valide',
    'listen-repeat': 'Écoute la phrase, puis répète-la à voix haute',
    'picture-mcq': 'Observe puis choisis la bonne image',
    'complete-the-words': 'Complète les lettres manquantes dans le texte',
    'build-a-sentence': 'Replace les mots pour construire la phrase',
    'listen-choose-response': 'Écoute puis choisis la meilleure réponse',
    'guided-writing': 'Rédige en suivant les contraintes indiquées',
};

// Le texte a lire. `lecteur-passage` (app.css) : 16 px, interligne 28 px, une
// largeur de lecture, et les sauts de ligne du texte source comptent. Une partie
// de lecture qui donne DEUX documents — un message puis un courriel — les voyait
// recoller en un seul bloc, et on ne savait plus ou l'un finissait.
function HighlightedPassage({ text, highlight }: { text: string; highlight: string }) {
    if (!highlight) return <p className="lecteur-passage text-foreground">{text}</p>;

    const parts = text.split(new RegExp(`(${highlight.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi'));

    return (
        <p className="lecteur-passage text-foreground">
            {parts.map((part, i) =>
                part.toLowerCase() === highlight.toLowerCase() ? (
                    <mark key={i} className="rounded bg-amber-200 px-0.5 font-medium text-amber-950 dark:bg-amber-400/30 dark:text-amber-50">
                        {part}
                    </mark>
                ) : part
            )}
        </p>
    );
}

/**
 * Ou en est l'eleve dans la seance : « Question n/N » et une barre sur toute la
 * largeur. Le libelle est annonce poliment a chaque question ; la barre porte son
 * role et ses valeurs. Elle remplace les pastilles, illisibles au-dela de vingt
 * questions et muettes pour un lecteur d'ecran.
 */
function BarreDeProgression({ libelle, fait, total }: { libelle: string; fait: number; total: number }) {
    const faites = Math.max(0, Math.min(fait, total));
    const pourcent = total > 0 ? (faites / total) * 100 : 0;

    return (
        <div className="flex items-center gap-3">
            <span aria-live="polite" className="shrink-0 text-[0.8125rem] font-semibold tabular-nums text-muted-foreground">
                {libelle}
            </span>
            <div
                role="progressbar"
                aria-label="Progression dans la séance"
                aria-valuemin={0}
                aria-valuemax={total}
                aria-valuenow={faites}
                aria-valuetext={`${faites} sur ${total}`}
                className="h-2.5 flex-1 overflow-hidden rounded-full bg-muted"
            >
                <div className="h-full rounded-full bg-primary transition-[width] duration-300 motion-reduce:transition-none" style={{ width: `${pourcent}%` }} />
            </div>
        </div>
    );
}

/**
 * Le temps restant pour la question : un petit arc et les minutes:secondes en
 * 14 px (11 px auparavant, dans des rouges et ambres ecrits en dur). L'alerte
 * passe par la couleur ET le fond de la pastille, aux couleurs du theme.
 */
function ArcTimer({ seconds, limit, warning, critical, expired }: {
    seconds: number; limit: number;
    warning: boolean; critical: boolean; expired: boolean;
}) {
    const r = 9;
    const circ = 2 * Math.PI * r;
    const pct = limit > 0 ? Math.max(0, Math.min(1, seconds / limit)) : 1;
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    const alerte = expired || critical;

    return (
        <div
            role="timer"
            aria-label={`Temps restant pour cette question : ${mins} min ${secs} s`}
            className={cn(
                'inline-flex h-10 shrink-0 items-center gap-1.5 rounded-full px-2.5 text-[0.875rem] font-bold tabular-nums',
                alerte
                    ? 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-200'
                    : warning
                        ? 'bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-100'
                        : 'bg-muted text-foreground',
                critical && !expired && 'motion-safe:animate-pulse',
            )}
        >
            <svg viewBox="0 0 24 24" className="h-5 w-5 -rotate-90" aria-hidden="true">
                <circle cx="12" cy="12" r={r} fill="none" stroke="currentColor" strokeOpacity={0.2} strokeWidth="3" />
                <circle
                    cx="12" cy="12" r={r}
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="3"
                    strokeLinecap="round"
                    strokeDasharray={circ}
                    strokeDashoffset={circ * (1 - pct)}
                    className={alerte || warning ? '' : 'text-primary'}
                    style={{ transition: 'stroke-dashoffset 0.9s linear' }}
                />
            </svg>
            <span aria-hidden="true">{String(mins).padStart(2, '0')}:{String(secs).padStart(2, '0')}</span>
        </div>
    );
}

/** La competence de l'exercice, en une pastille (a la place de la grande carte). */
const COMPETENCES: Record<string, { nom: string; icone: typeof BookOpen }> = {
    reading: { nom: 'Lecture', icone: BookOpen },
    listening: { nom: 'Écoute', icone: Headphones },
    writing: { nom: 'Écrit', icone: PenLine },
    speaking: { nom: 'Oral', icone: Mic },
    grammar: { nom: 'Grammaire', icone: Puzzle },
    vocabulary: { nom: 'Vocabulaire', icone: BookOpen },
};

/** Les caracteres qu'un clavier francais ou un telephone font chercher. */
const CARACTERES: Record<string, { minuscules: string[]; majuscules: string[] }> = {
    german: { minuscules: ['ä', 'ö', 'ü', 'ß'], majuscules: ['Ä', 'Ö', 'Ü'] },
    french: {
        minuscules: ['é', 'è', 'ê', 'ë', 'à', 'â', 'ç', 'î', 'ï', 'ô', 'œ', 'ù', 'û'],
        majuscules: ['É', 'È', 'Ê', 'À', 'Ç', 'Î', 'Ô', 'Œ', 'Ù'],
    },
};

// Un <input> sans attribut type est un champ texte : le selecteur
// `input[type="text"]` seul le manquait.
const CHAMP_TEXTE = 'input:not([type]):not(:disabled), input[type="text"]:not(:disabled), textarea:not(:disabled)';

const estChampTexte = (t: EventTarget | null): t is HTMLInputElement | HTMLTextAreaElement =>
    (t instanceof HTMLInputElement && t.type === 'text') || t instanceof HTMLTextAreaElement;

/**
 * La barre ä ö ü ß (ou é è ç… en francais) sous les champs de saisie.
 *
 * Meme barre que dans le lecteur de l'essai (components/exercises/exercise-player.tsx).
 * Elle n'apparait que si la question a un champ ou ecrire encore actif. Un clic
 * insere le caractere a l'endroit du curseur dans le dernier champ touche ; le
 * champ garde le focus (le bouton ne le prend pas).
 */
function CaracteresSpeciaux({ langue, zone, cle }: { langue: string; zone: React.RefObject<HTMLElement | null>; cle: string }) {
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
            if (estChampTexte(e.target)) dernierChamp.current = e.target;
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
        <div role="group" aria-label="Caractères spéciaux" className="mt-4 flex flex-wrap items-center gap-1.5">
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

/** La hauteur reelle d'un element (en-tete ou barre fixes), suivie a chaque changement. */
function useHauteurDe(el: HTMLElement | null): number {
    const [hauteur, setHauteur] = useState(0);
    useEffect(() => {
        if (!el) return;
        const mesurer = () => setHauteur(Math.round(el.getBoundingClientRect().height));
        mesurer();
        if (typeof ResizeObserver === 'undefined') return;
        const observateur = new ResizeObserver(mesurer);
        observateur.observe(el);
        return () => observateur.disconnect();
    }, [el]);
    return hauteur;
}

interface ExplanationObj {
    concept: string;
    evidence: string;
    hint: string;
    french_translation?: { concept: string; evidence: string; hint: string };
}

// Coerce any value to a renderable string. Guards against React error #31
// (rendering an object/array as a child) which white-screened the page when the
// AI returned a structured explanation (e.g. nested concept/feedback objects).
function asText(v: unknown): string {
    if (v == null) return '';
    if (typeof v === 'string') return v;
    if (typeof v === 'number' || typeof v === 'boolean') return String(v);
    if (Array.isArray(v)) return v.map(asText).filter(Boolean).join(' ');
    if (typeof v === 'object') {
        const record = v as Record<string, unknown>;
        return String(record.text ?? record.message ?? record.value ?? '');
    }
    return '';
}

function normalizeExplObj(o: Record<string, unknown>): ExplanationObj {
    const ft = o.french_translation && typeof o.french_translation === 'object'
        ? o.french_translation as Record<string, unknown>
        : undefined;
    return {
        concept: asText(o.concept),
        evidence: asText(o.evidence),
        hint: asText(o.hint),
        french_translation: ft ? {
            concept: asText(ft.concept),
            evidence: asText(ft.evidence),
            hint: asText(ft.hint),
        } : undefined,
    };
}

function parseExplanation(raw: unknown): ExplanationObj | string | null {
    if (!raw) return null;
    if (typeof raw === 'object') {
        const record = raw as Record<string, unknown>;
        return record.concept !== undefined ? normalizeExplObj(record) : asText(raw) || null;
    }
    if (typeof raw === 'string') {
        try {
            const parsed = JSON.parse(raw);
            if (parsed && parsed.concept !== undefined) return normalizeExplObj(parsed);
        } catch {
            // Plain-text feedback is a valid explanation too.
        }
        return raw;
    }
    return null;
}

function getExplanationText(explanation: ExplanationObj | string | null, lang: string): string {
    if (!explanation) return '';
    if (typeof explanation === 'string') return explanation;
    const useFr = lang === 'fr';
    const src = useFr && explanation.french_translation ? explanation.french_translation : explanation;
    const parts: string[] = [];
    if (src.concept) parts.push(src.concept);
    if (src.hint) parts.push(src.hint);
    return parts.join(' — ');
}

function getEvidenceText(explanation: ExplanationObj | string | null): string | null {
    if (!explanation) return null;
    if (typeof explanation === 'string') {
        const m = explanation.match(/<evidence>(.*?)<\/evidence>/);
        return m?.[1] ?? null;
    }
    if (explanation.evidence) {
        const m = explanation.evidence.match(/<evidence>(.*?)<\/evidence>/);
        return m?.[1] ?? explanation.evidence ?? null;
    }
    return null;
}

/**
 * The learner's own answer, written out the way the expected one is.
 *
 * A bare option letter or a map of fields tells them nothing about what they got
 * wrong, and a recording has no text to show at all.
 */
function givenAnswerText(question: PlayerQuestion, given: unknown): string {
    if (given === null || given === undefined) return '';
    if (typeof given === 'object' && !Array.isArray(given)) {
        if (typeof Blob !== 'undefined' && given instanceof Blob) return '';
        return Object.values(given as Record<string, unknown>)
            .map((value) => String(value ?? '').trim())
            .filter(Boolean)
            .join(', ');
    }
    if (Array.isArray(given)) return given.map((value) => String(value ?? '').trim()).filter(Boolean).join(' → ');

    const text = String(given).trim();
    if (/^[A-Za-z]$/.test(text) && Array.isArray(question.options)) {
        const letter = text.toUpperCase();
        // Un choix rendu sous forme d'objet s'affichait « [object Object] », et une
        // carte lettree glissee dans la liste rendait toujours le premier choix.
        const option = optionList(question.options)[letter.charCodeAt(0) - 65] ?? '';
        if (option !== '') return `${letter}) ${option}`;
    }
    return text;
}

// Render light markdown coming from the AI feedback (**bold**, <evidence> tags,
// bullet "-" lines) as real elements instead of showing the raw asterisks/tags.
function FormattedFeedback({ text, className }: { text: string; className?: string }) {
    if (!text) return null;
    // Pull out <evidence>…</evidence> to show it as a highlighted quote.
    const evidenceMatch = text.match(/<evidence>([\s\S]*?)<\/evidence>/i);
    const evidence = evidenceMatch?.[1]?.trim();
    const body = text.replace(/<\/?evidence>/gi, '').trim();

    const renderInline = (s: string, keyPrefix: string) =>
        s.split(/(\*\*[^*]+\*\*)/g).map((part, i) =>
            part.startsWith('**') && part.endsWith('**')
                ? <strong key={`${keyPrefix}-${i}`}>{part.slice(2, -2)}</strong>
                : <React.Fragment key={`${keyPrefix}-${i}`}>{part}</React.Fragment>
        );

    const lines = body.split('\n').map(l => l.trim()).filter(Boolean);

    return (
        <div className={className}>
            {lines.map((line, i) => {
                const isBullet = line.startsWith('- ') || line.startsWith('* ');
                const content = isBullet ? line.slice(2) : line;
                return (
                    <p key={i} className={isBullet ? 'pl-3 leading-snug' : 'leading-snug'}>
                        {isBullet ? '• ' : ''}{renderInline(content, `l${i}`)}
                    </p>
                );
            })}
            {evidence && (
                <p className="mt-1 border-l-2 border-red-300 pl-2 italic opacity-80">{renderInline(evidence, 'ev')}</p>
            )}
        </div>
    );
}

export default function SessionPlayer({ node, exercises, sessionToken }: Props) {
    const { t, i18n } = useTranslation();
    const [currentExerciseIndex, setCurrentExerciseIndex] = useState(0);
    const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
    const [isReviewMode, setIsReviewMode] = useState(false);
    const [answers, setAnswers] = useState<Record<string, FormDataConvertible>>({});
    const [isChecked, setIsChecked] = useState(false);
    const [isCorrect, setIsCorrect] = useState<boolean | null>(null);
    // Question passee faute d'avoir pu etre presentee : ni juste, ni fausse.
    const [skipped, setSkipped] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [visible, setVisible] = useState(true);
    const timeSpentRef = useRef(0);
    // Guards against a double advance: the per-question timer expiry schedules an
    // auto-nextStep, and the user can advance manually at the same moment. Without
    // these, nextStep runs twice → the index overshoots the last question and the
    // player renders "Session non disponible". advancingRef blocks re-entry while a
    // transition is in flight; expireTimeoutRef lets a manual advance cancel the
    // pending expiry auto-advance so it can't fire on the *next* question.
    const advancingRef = useRef(false);
    const expireTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const [timerKey, setTimerKey] = useState(0);
    const [timerSeconds, setTimerSeconds] = useState(0);
    const [explanation, setExplanation] = useState<ExplanationObj | string | null>(null);
    const [fetchingExplanation, setFetchingExplanation] = useState(false);
    const [highlightedText, setHighlightedText] = useState<string | null>(null);
    const [playingTts, setPlayingTts] = useState<string | null>(null);
    const [isChatOpen, setIsChatOpen] = useState(false);
    const [mistakes, setMistakes] = useState<PlayerQuestion[]>([]);
    // Speaking formative feedback: points covered / still to add.
    const [speakingPoints, setSpeakingPoints] = useState<{ covered: string[]; missing: string[] } | null>(null);
    // Frozen snapshot of the mistakes list when entering review mode, so the
    // review never grows while you retry (which used to loop forever).
    const [reviewQueue, setReviewQueue] = useState<PlayerQuestion[]>([]);
    const [isVerifying, setIsVerifying] = useState(false);
    const [isDictionaryOpen, setIsDictionaryOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [isSearching, setIsSearching] = useState(false);
    const [searchResult, setSearchResult] = useState<DictionaryLookup | null>(null);
    const [isVocabSaved, setIsVocabSaved] = useState(false);
    const [vocabError, setVocabError] = useState<string | null>(null);
    // Listening: how many times the recording was played (exam-realistic cap of 2).
    const [listenCount, setListenCount] = useState(0);
    // Neither the stored file nor live speech could be played: the transcript is
    // then shown, otherwise the question could not be answered at all.
    const [audioUnavailable, setAudioUnavailable] = useState(false);
    const contentRef = useRef<HTMLDivElement>(null);
    const correctionRef = useRef<HTMLDivElement>(null);
    // La zone de la question : focus sur l'enonce, raccourcis 1-4 / A-D, barre
    // de caracteres. En-tete et barre d'actions fixes, mesures pour poser le
    // contenu entre les deux.
    const zoneQuestion = useRef<HTMLElement>(null);
    const premierAffichage = useRef(true);
    const [passageOuvert, setPassageOuvert] = useState(true);
    const [entete, setEntete] = useState<HTMLElement | null>(null);
    const [barreActions, setBarreActions] = useState<HTMLDivElement | null>(null);
    // Lecture audio : un seul élément actif à la fois + garde synchrone anti
    // double-lancement (l'état React est trop lent pour ça — voir playTts).
    const currentAudioRef = useRef<HTMLAudioElement | null>(null);
    const ttsBusyRef = useRef(false);
    // Safari (et les navigateurs stricts sur mobile) n'autorisent la lecture que si
    // play() part du clic lui-même. Quand le son doit d'abord être fabriqué par le
    // serveur, l'attente réseau fait perdre ce droit et rien ne sort — d'où « l'écoute
    // ne marche pas » sur certains navigateurs, alors qu'elle marche quand le fichier
    // est déjà en cache. On débloque donc un lecteur unique dès le clic, puis on lui
    // donne sa source une fois l'audio prêt.
    const SILENT_CLIP = 'data:audio/mp3;base64,//uQxAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAACAAACcQCA';

    const exercise = exercises[currentExerciseIndex];
    // TTS language for this node. Prefer the language SLUG ("english"/"french"/
    // "german") which use-tts normalizes reliably. The display name can't be used
    // as a lookup key: the DB stores localized names ("Allemand"/"Français"), so a
    // map keyed on English names ("German"/"French") silently fell through to 'en'
    // — which is why a German reading passage was read with an English voice.
    const NAME_TO_SLUG: Record<string, string> = {
        English: 'english', Anglais: 'english',
        French: 'french', Français: 'french', Francais: 'french',
        German: 'german', Allemand: 'german', Deutsch: 'german',
    };
    const nodeCode = node.exam.language.slug
        ?? NAME_TO_SLUG[node.exam.language.name]
        ?? 'en';
    const componentKey = exercise?.exercise_type?.component_key ?? 'mcq';
    // Listening skill → the passage is a TRANSCRIPT that must NOT be shown (the
    // learner has to LISTEN, not read). We render an audio-only card instead and
    // play the passage/audio_text via TTS. (listeningAudioText is computed below,
    // once `question` is available.)
    const skillType = exercise?.exercise_type?.skill_type ?? '';
    // Une erreur est rejouée en fin de séance, dans un autre exercice : elle emporte
    // donc son contexte, sinon la révision affiche la consigne et le texte de
    // l'exercice courant sur une question qui n'a rien à voir.
    const questionContext = useMemo(() => ({
        component_key: exercise?.exercise_type?.component_key ?? 'mcq',
        skill_type: exercise?.exercise_type?.skill_type ?? '',
        type_name: exercise?.exercise_type?.name ?? '',
        passage: exercise?.content?.passage ?? '',
    }), [exercise]);

    const TIME_PER_QUESTION = useMemo(() => {
        const writingTypes = ['essay-editor', 'short-writing', 'graph-description', 'academic-discussion', 'synthesis', 'integrated-task', 'guided-writing'];
        const speakingTypes = ['speaking-recorder', 'role-play', 'listen-repeat'];
        const longTypes = ['gapped-text', 'insert-text', 'table-completion', 'flow-chart-completion', 'summary-completion', 'form-completion', 'diagram-labeling', 'multiple-matching', 'complete-the-words'];
        // Timers kept tight enough to feel the effort, but long enough not to
        // punish reading: exercises with a reference passage get extra time to read it.
        const hasPassage = !!exercise?.content?.passage;
        if (writingTypes.includes(componentKey)) return 600;
        if (speakingTypes.includes(componentKey)) return 300;
        if (longTypes.includes(componentKey)) return 180;
        if (hasPassage) return 120;
        return 75;
    }, [componentKey, exercise]);

    const questions = isReviewMode ? reviewQueue : (exercise?.questions ?? []);
    const question = questions[currentQuestionIndex];
    const activeComponentKey = isReviewMode ? (question?.component_key ?? 'mcq') : componentKey;
    const activeSkillType = isReviewMode ? (question?.skill_type ?? '') : skillType;
    const activeTypeName = isReviewMode ? (question?.type_name ?? '') : (exercise?.exercise_type?.name ?? '');

    // La consigne du vrai-faux annoncait « Vrai, Faux ou Non mentionne » meme
    // quand la partie n'offre que deux choix — les Ja/Nein du Goethe et de telc.
    const choixDeclares = Array.isArray((question as { answer_options?: unknown })?.answer_options)
        ? ((question as { answer_options: unknown[] }).answer_options.filter(
              (v): v is string => typeof v === 'string' && v.trim() !== '',
          ) as string[])
        : [];
    const consigneDuType =
        activeComponentKey === 'true-false-ng' && choixDeclares.length === 2
            ? 'Choisis entre les deux réponses proposées'
            : INSTRUCTIONS[activeComponentKey];
    // Passage LU : il est rendu HORS de la barriere d'erreur, donc un passage
    // arrive en liste de paragraphes ou en objet vidait la seance entiere, pas
    // seulement la question.
    const activePassage = coerceOption(isReviewMode ? question?.passage : exercise?.content?.passage);
    const isListening = activeSkillType === 'listening';
    const Component = componentMap[activeComponentKey] ?? Mcq;

    // Listening audio source (transcript that stays hidden) — see isListening above.
    const listeningAudioText: string =
        exercise?.content?.audio_text || question?.audio_text || exercise?.content?.passage || '';
    // Pre-generated audio file (built before the exercise loads) → instant play.
    const listeningAudioUrl: string | undefined =
        question?.audio_url || exercise?.content?.audio_url || undefined;

    // Reset the play counter each time we move to a new question, and stop any
    // audio still playing so it doesn't bleed into the next question.
    useEffect(() => {
        setListenCount(0);
        setAudioUnavailable(false);
        currentAudioRef.current?.pause();
        ttsBusyRef.current = false;
        setPlayingTts(null);
    }, [currentExerciseIndex, currentQuestionIndex]);

    // Stop audio on unmount (leaving the session).
    useEffect(() => () => { currentAudioRef.current?.pause(); }, []);

    // Prefetch ALL the session's audio at mount (TTS texts + pre-generated MP3s),
    // so pressing "Écouter" mid-exercise plays instantly instead of firing the
    // TTS API call at click time.
    useEffect(() => {
        prefetchExercisesAudio(exercises, nodeCode);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const totalQuestionsInSession = useMemo(() =>
        exercises.reduce((acc, ex) => acc + (ex.questions?.length ?? 0), 0)
    , [exercises]);

    const overallProgressCount = useMemo(() => {
        let count = 0;
        for (let i = 0; i < currentExerciseIndex; i++) {
            count += exercises[i].questions?.length ?? 0;
        }
        return count + currentQuestionIndex;
    }, [currentExerciseIndex, currentQuestionIndex, exercises]);

    // Answers are keyed by a composite "exerciseIndex::questionId" so that two
    // questions sharing the same id (AI-generated questions sometimes do) don't
    // leak each other's answer into the input. We strip the prefix before sending
    // to the backend, which expects a plain { questionId: answer } map.
    const answerKey = useCallback(
        (qid: string | undefined, exIdx = currentExerciseIndex) => isReviewMode
            ? `review-${question?.exercise_id}::${qid ?? ''}`
            : `${exIdx}::${qid ?? ''}`,
        [currentExerciseIndex, isReviewMode, question?.exercise_id]
    );

    const handleAnswer = useCallback((questionId: string, answer: FormDataConvertible) => {
        // Ni apres la correction, ni pendant la verification par le serveur :
        // le verdict affiche doit porter sur la reponse enregistree.
        if (isChecked || isVerifying) return;
        setAnswers(prev => ({ ...prev, [answerKey(questionId)]: answer }));
    }, [isChecked, isVerifying, answerKey]);

    /** Prépare (et débloque) le lecteur partagé, dans le geste de l'apprenant. */
    const unlockedPlayer = useCallback((): HTMLAudioElement => {
        let audio = currentAudioRef.current;
        if (!audio) {
            audio = new Audio();
            currentAudioRef.current = audio;
        }
        audio.pause();
        if (!audio.src) {
            audio.src = SILENT_CLIP;
            audio.play().catch(() => {}); // le refus éventuel n'a aucune conséquence
        }

        return audio;
    }, []);

    const playTts = useCallback(async (text: string, id: string) => {
        // Garde synchrone (ref) : l'état playingTts est asynchrone, deux appels
        // rapprochés (double-clic, ou onerror + catch du même échec) passaient
        // tous les deux → deux lectures superposées (effet écho).
        if (playingTts === id || ttsBusyRef.current) return;
        ttsBusyRef.current = true;
        setPlayingTts(id);

        // Only the recording matters here: a failed question read-out leaves its text visible.
        const unavailable = () => {
            if (id === 'passage') setAudioUnavailable(true);
        };

        const player = unlockedPlayer();

        const playUrl = (url: string) => {
            // Un seul lecteur, réutilisé : c'est lui qui a reçu le droit de jouer.
            const audio = player;
            audio.pause();
            audio.src = url;
            const done = () => { ttsBusyRef.current = false; setPlayingTts(null); };
            const failed = () => { done(); unavailable(); };
            audio.onended = done;
            audio.onerror = failed;
            audio.play().catch(failed);
        };

        // Instant path: prefetched at session mount (see prefetchExercisesAudio).
        const cachedUrl = getCachedTtsUrl(text, nodeCode);
        if (cachedUrl) {
            playUrl(cachedUrl);
            return;
        }

        try {
            const response = await fetch(route('tts.speak'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ text, lang: nodeCode })
            });
            if (!response.ok) throw new Error(`TTS HTTP ${response.status}`);
            const data = await response.json();
            if (data.audio_url) {
                rememberTtsUrl(text, nodeCode, data.audio_url);
                playUrl(data.audio_url);
            } else {
                ttsBusyRef.current = false;
                setPlayingTts(null);
                unavailable();
            }
        } catch (error) {
            console.error('TTS error:', error);
            ttsBusyRef.current = false;
            setPlayingTts(null);
            unavailable();
        }
    }, [nodeCode, playingTts, unlockedPlayer]);

    // Play a listening recording: prefer the pre-generated audio file (instant),
    // fall back to live TTS only if the file wasn't pre-built.
    const playListening = useCallback((url: string | undefined, text: string) => {
        if (playingTts === 'passage') return;
        if (url) {
            setPlayingTts('passage');
            // Lecteur unique, débloqué dans le clic (voir unlockedPlayer).
            const audio = unlockedPlayer();
            audio.src = url;
            // Le fallback TTS ne doit partir qu'UNE fois : sur un fichier en échec
            // (ex. 404 du symlink storage), onerror ET le rejet de play() se
            // déclenchaient tous les deux → deux lectures TTS superposées (écho).
            let fellBack = false;
            const fallback = () => {
                if (fellBack) return;
                fellBack = true;
                setPlayingTts(null);
                if (text) playTts(text, 'passage');
            };
            audio.onended = () => setPlayingTts(null);
            audio.onerror = fallback;
            audio.play().catch(fallback);
            return;
        }
        if (text) playTts(text, 'passage');
    }, [playingTts, playTts, unlockedPlayer]);

    const fetchExplanation = useCallback(async (questionObj = question) => {
        if (!questionObj) return;

        // Multi-field types (note/form/table/summary/flow-chart-completion) keep
        // their expected answers in `correct_answers` (a key→value map), not the
        // single `correct_answer` string every other type uses. Without this,
        // the request went out with an empty correct_answer, which Laravel's
        // `required` rule rejects — and without an Accept: application/json
        // header the validation-failure response comes back as an HTML page,
        // crashing res.json() with "Unexpected token '<'".
        const userAnswerRaw = answers[answerKey(questionObj.id)];
        const correctAnswerText = expectedAnswerText(questionObj);
        const userAnswerText = (userAnswerRaw && typeof userAnswerRaw === 'object')
            ? Object.values(userAnswerRaw).join(', ')
            : String(userAnswerRaw ?? '');

        if (!correctAnswerText.trim() || !userAnswerText.trim()) return;

        // Note taking and tables have no question text, yet the server requires one:
        // fall back to what the learner actually worked on.
        const prompt = [
            questionObj.prompt, questionObj.text, questionObj.statement, questionObj.title,
            questionObj.audio_text, exercise?.content?.passage, exercise?.exercise_type?.name,
        ].find((value): value is string => typeof value === 'string' && value.trim() !== '') ?? 'Exercice';
        const unavailableText = 'L’explication détaillée n’est pas disponible pour le moment.';

        setFetchingExplanation(true);
        try {
            const res = await fetch(route('api.ai.explain'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    prompt,
                    user_answer: userAnswerText,
                    correct_answer: correctAnswerText,
                    language: node.exam.language.name
                })
            });
            if (!res.ok) throw new Error(`Explain HTTP ${res.status}`);
            const data = await res.json();
            const parsedExpl = parseExplanation(data.explanation);
            setExplanation(parsedExpl ?? unavailableText);
            setHighlightedText(getEvidenceText(parsedExpl));
        } catch (error) {
            console.error('Failed to fetch explanation:', error);
            // The expected answer stays on screen; say plainly that the detail is missing.
            setExplanation(unavailableText);
        } finally {
            setFetchingExplanation(false);
        }
    }, [question, answers, answerKey, node.exam.language.name, exercise]);

    const checkAnswer = useCallback(async () => {
        if (!question || isChecked || isVerifying) return;
        const currentAnswer = answers[answerKey(question.id)];
        // Une reponse vide n'est pas une reponse : l'envoyer a la correction perdait
        // la question (champ efface, « Refaire l'enregistrement »).
        if (isBlankAnswer(currentAnswer)) return;

        // Une question passee parce qu'elle ne s'affichait pas n'est pas une erreur de
        // l'apprenant. Le serveur la sort deja du score ; l'ecran, lui, jouait le son
        // d'erreur, affichait « Incorrect » avec la reponse attendue et la renvoyait en
        // revision — juste apres lui avoir promis qu'elle ne compterait pas.
        if (isSkippedAnswer(currentAnswer)) {
            setIsChecked(true);
            setIsCorrect(null);
            setSkipped(true);
            return;
        }

        // Role-play & listen-repeat self-evaluate inside the component; the answer
        // arrives as "completed:NN" / "repeat:NN". Don't re-score via the API.
        if (activeComponentKey === 'role-play' || activeComponentKey === 'listen-repeat') {
            const sc = parseInt(String(currentAnswer).split(':')[1] ?? '0', 10) || 0;
            setIsCorrect(sc >= 50);
            setIsChecked(true);
            playSound(sc >= 50 ? 'correct' : 'incorrect');
            return;
        }

        const aiTypes = ['essay-editor', 'speaking-recorder', 'role-play', 'short-writing', 'graph-description', 'academic-discussion', 'synthesis', 'integrated-task', 'guided-writing'];
        let isRight = false;
        let aiFeedback = null;

        if (aiTypes.includes(activeComponentKey) || needsServerEvaluation(question, activeComponentKey)) {
            setIsVerifying(true);
            try {
                const formData = new FormData();
                formData.append('exercise_id', String(exercise.id));
                formData.append('question_id', String(question.id));
                
                if (currentAnswer instanceof Blob) {
                    formData.append('answer', currentAnswer, currentAnswer instanceof File ? currentAnswer.name : nomFichierAudio('recording', currentAnswer));
                } else {
                    formData.append('answer', String(currentAnswer));
                }

                const res = await fetch(route('api.exercise.verify-single'), {
                    method: 'POST',
                    headers: { 'X-XSRF-TOKEN': csrfToken() },
                    body: formData
                });
                const data = await res.json();
                isRight = data.correct;
                const parsedExpl = parseExplanation(data.explanation);
                aiFeedback = parsedExpl;
                setExplanation(parsedExpl);
                setHighlightedText(getEvidenceText(parsedExpl));

                // Store the transcription whenever the backend returned the key
                // (even an empty string = "audio, but heard nothing") so the UI can
                // always show "Ce que l'IA a entendu" / a clear no-audio state.
                if (data.transcription !== null && data.transcription !== undefined) {
                    setAnswers(prev => ({
                        ...prev,
                        [`${question.id}_transcription`]: data.transcription === '' ? '(aucun son détecté)' : data.transcription,
                    }));
                }
                // Speaking: capture covered/missing points to show continuation help.
                if ((data.covered_points && data.covered_points.length) || (data.missing_points && data.missing_points.length)) {
                    setSpeakingPoints({ covered: data.covered_points ?? [], missing: data.missing_points ?? [] });
                } else {
                    setSpeakingPoints(null);
                }
            } catch (error) {
                console.error('Verification failed:', error);
                isRight = false;
            } finally {
                setIsVerifying(false);
            }
        } else {
            // Same rules as the server, so what the learner sees matches the recorded score.
            isRight = evaluateAnswer(question, currentAnswer).correct;
        }

        setIsCorrect(isRight);
        setIsChecked(true);
        playSound(isRight ? 'correct' : 'incorrect');

        if (!isRight) {
            // Most exercises ship their own explanation: show it at once instead of
            // waiting on the AI, which may be unavailable.
            const stored = aiFeedback ? null : parseExplanation(question.explanation);
            if (stored) {
                setExplanation(stored);
                setHighlightedText(getEvidenceText(stored));
            } else if (!aiFeedback && !isReviewMode) {
                fetchExplanation();
            }
        }

        if (!isRight && !isReviewMode) {
            // Add to mistakes queue if not already a retry. We never grow the queue
            // during review mode itself, otherwise the end condition recedes forever.
            const isAlreadyMistake = mistakes.some(m => m.id === question.id && m.exercise_id === exercise.id);
            if (!isAlreadyMistake) {
                setMistakes(prev => [...prev, { ...question, exercise_id: exercise.id, ...questionContext }]);
            }
        }
    }, [question, isChecked, answers, answerKey, activeComponentKey, questionContext, fetchExplanation, exercise.id, isVerifying, mistakes, isReviewMode]);

    const nextStep = useCallback(() => {
        // Block re-entry while a transition is already in flight (double click, or
        // timer-expiry auto-advance racing a manual advance).
        if (advancingRef.current) return;
        advancingRef.current = true;
        // A manual advance cancels any pending timer-expiry auto-advance so it can't
        // fire again on the next question.
        if (expireTimeoutRef.current) {
            clearTimeout(expireTimeoutRef.current);
            expireTimeoutRef.current = null;
        }

        setIsChecked(false);
        setIsCorrect(null);
        // Sans cette remise a zero, « Question passee » restait affiche sur toutes les
        // questions suivantes et masquait leur correction.
        setSkipped(false);
        setExplanation(null);
        setHighlightedText(null);
        setSpeakingPoints(null);

        const isEndOfExercises = currentExerciseIndex === exercises.length - 1 && currentQuestionIndex === (isReviewMode ? reviewQueue.length - 1 : (exercise?.questions?.length ?? 0) - 1);

        if (isEndOfExercises) {
            if (!isReviewMode && mistakes.length > 0) {
                // Pedagogy: Start Review Mode for mistakes. Freeze the queue now so
                // retrying a wrong answer can't keep extending the session.
                setVisible(false);
                setTimeout(() => {
                    setReviewQueue(mistakes);
                    setIsReviewMode(true);
                    setCurrentQuestionIndex(0);
                    setVisible(true);
                    setTimerKey(k => k + 1);
                    advancingRef.current = false;
                }, 220);
                return;
            }

            // Group answers by exercise. Several exercises in a single set reuse the
            // SAME question ids (q1/q2/q3), so a flat { questionId: answer } map made
            // the last exercise's answers overwrite all the others — every exercise
            // was then scored against those, giving wrong grades AND polluting the
            // error-review log. We send { exerciseId: { questionId: answer } } instead.
            // The internal key is "exerciseIndex::questionId"; map the index → real id.
            const answersByExercise = assessmentAnswers(answers, exercises);

            setSubmitting(true);
            playSound('complete');
            router.post(route('exercise.submit_session', node.id), {
                answers_by_exercise: answersByExercise,
                node_id: node.id,
                time_spent: timeSpentRef.current,
                exercise_ids: exercises.map((e) => e.id),
                // Jeton a usage unique : il empeche qu'un renvoi de la meme seance
                // (bouton Retour, double envoi) recredite XP et tentatives.
                session_token: sessionToken ?? '',
            }, {
                forceFormData: true,
                // A successful submit leaves the page; any failure hands the session back for a retry.
                onFinish: () => {
                    setSubmitting(false);
                    advancingRef.current = false;
                },
            });
            return;
        }

        setVisible(false);
        setTimeout(() => {
            if (currentQuestionIndex < (isReviewMode ? reviewQueue.length - 1 : questions.length - 1)) {
                setCurrentQuestionIndex(prev => prev + 1);
            } else if (!isReviewMode) {
                setCurrentExerciseIndex(prev => prev + 1);
                setCurrentQuestionIndex(0);
            }
            setVisible(true);
            setTimerKey(k => k + 1);
            advancingRef.current = false;
        }, 220);
    }, [currentExerciseIndex, currentQuestionIndex, exercises, questions.length, answers, node.id, mistakes, reviewQueue, isReviewMode, exercise, sessionToken]);

    const handleTimeUpdate = useCallback((elapsed: number) => {
        timeSpentRef.current += 1;
        setTimerSeconds(TIME_PER_QUESTION - elapsed);
    }, [TIME_PER_QUESTION]);

    const handleTimeExpire = useCallback(async () => {
        // Une correction est deja en vol (clic sur « Verifier » juste avant la fin) :
        // la laisser poser son verdict et declencher elle-meme la suite. Appeler
        // checkAnswer ici n'aurait rien fait — sa propre garde le refuse — et
        // l'avance programmee aurait repeint ce verdict sur la question SUIVANTE.
        if (isVerifying) return;

        if (!isChecked) {
            const saisie = answers[answerKey(question?.id)];

            if (isBlankAnswer(saisie)) {
                // Rien n'a ete repondu : la question est comptee fausse, comme avant.
                setIsChecked(true);
                setIsCorrect(false);
                setMistakes(prev => [...prev, { ...question, ...questionContext }]);
            } else {
                // Une reponse etait saisie : on la corrige vraiment. Le temps ecoule la
                // declarait fausse sans jamais la regarder — son d'erreur, cadre rouge
                // et « reponse attendue » qui etait mot pour mot ce que l'apprenant
                // venait d'ecrire — alors que le serveur, lui, l'enregistrait comme
                // juste. On attend la correction (locale ou serveur) avant de
                // programmer l'avance, sinon elle se repeindrait sur la question
                // suivante.
                try {
                    await checkAnswer();
                } catch {
                    // La correction a echoue : on retombe sur l'ancien comportement
                    // plutot que de laisser la question sans verdict ni avance.
                    setIsChecked(true);
                    setIsCorrect(false);
                    setMistakes(prev => [...prev, { ...question, ...questionContext }]);
                }
            }
        }
        // Track the pending auto-advance so a manual advance (or another expiry) can
        // cancel it — otherwise nextStep runs twice and overshoots the last question.
        if (expireTimeoutRef.current) clearTimeout(expireTimeoutRef.current);
        expireTimeoutRef.current = setTimeout(() => nextStep(), 1200);
    }, [isChecked, isVerifying, nextStep, question, questionContext, answers, answerKey, checkAnswer]);

    // Clavier : Espace / Entree verifient puis passent a la suite ; 1-4 ou A-D
    // choisissent un choix (comme dans le lecteur de l'essai).
    useEffect(() => {
        const valider = () => {
            if (isChecked) nextStep();
            // Pendant l'oral le focus n'est dans aucun champ : Espace/Entree
            // envoyait encore une reponse vide.
            else if (!isBlankAnswer(answers[answerKey(question?.id)])) checkAnswer();
        };
        const onKey = (e: KeyboardEvent) => {
            if (e.defaultPrevented || e.ctrlKey || e.metaKey || e.altKey || e.isComposing) return;
            // Dictionnaire ou discussion ouverts : les touches leur appartiennent.
            if (isDictionaryOpen || isChatOpen) return;
            const zone = zoneQuestion.current;
            const cible = e.target instanceof HTMLElement ? e.target : null;

            // Entree dans un champ de la question : champ suivant, puis on verifie
            // apres le dernier. Elle ne faisait rien.
            if (e.key === 'Enter' && cible instanceof HTMLInputElement && cible.type === 'text' && zone?.contains(cible)) {
                e.preventDefault();
                const champs = Array.from(zone.querySelectorAll<HTMLInputElement>('input[type="text"]:not(:disabled), input:not([type]):not(:disabled)'));
                const rang = champs.indexOf(cible);
                if (rang >= 0 && rang < champs.length - 1) champs[rang + 1].focus();
                else valider();
                return;
            }
            if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement || e.target instanceof HTMLSelectElement) return;

            if (e.code === 'Space' || e.code === 'Enter') {
                // Sur un choix, Espace le coche et Entree le coche puis verifie
                // une fois retenu ; sur un autre bouton ou un lien (Dictionnaire,
                // ecoute, Quitter…), la touche garde son sens au lieu de verifier.
                const choix = cible?.closest('[data-choix]');
                if (choix) {
                    if (e.code === 'Space' || choix.getAttribute('aria-pressed') !== 'true') return;
                } else if (cible?.closest('button, a, summary, [role="button"]')) {
                    return;
                }
                e.preventDefault();
                valider();
                return;
            }

            // Pendant la verification par le serveur, les choix sont verrouilles :
            // le raccourci ne doit pas les changer non plus.
            if (isChecked || isVerifying || submitting || !zone) return;
            let rang = -1;
            if (/^[1-9]$/.test(e.key)) rang = Number(e.key) - 1;
            else if (/^[a-h]$/i.test(e.key)) rang = e.key.toUpperCase().charCodeAt(0) - 65;
            if (rang < 0) return;
            const bouton = zone.querySelector<HTMLButtonElement>(`[data-choix="${rang}"]:not(:disabled)`);
            if (!bouton) return;
            e.preventDefault();
            bouton.click();
            bouton.focus();
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [isChecked, isVerifying, submitting, nextStep, checkAnswer, answers, answerKey, question, isDictionaryOpen, isChatOpen]);

    const timerWarning = timerSeconds <= TIME_PER_QUESTION * 0.2 && timerSeconds > 0;
    const timerCritical = timerSeconds <= 10 && timerSeconds > 0;
    const timerExpired = timerSeconds === 0 && timerKey > 0;

    const isLastQuestion = currentExerciseIndex === exercises.length - 1 && currentQuestionIndex === questions.length - 1;
    const hasAnswer = !isBlankAnswer(answers[answerKey(question?.id)]);
    // Les ids de questions generees par l'IA se repetent : la cle de remontage est
    // composite, et la barriere d'erreur doit utiliser la MEME, sinon elle reste
    // bloquee en erreur quand deux questions consecutives partagent un id.
    const remountKey = `${isReviewMode ? 'r' : 'q'}-${currentExerciseIndex}-${currentQuestionIndex}-${question?.id ?? ''}`;
    // Shown with every wrong answer: note taking and tables never display it otherwise.
    const expectedText = isChecked && isCorrect === false && question ? expectedAnswerText(question) : '';
    // What the learner actually answered, in words — a wrong answer is only
    // instructive next to the expected one. Recordings have nothing to show here.
    const givenText = isChecked && isCorrect === false && question ? givenAnswerText(question, answers[answerKey(question.id)]) : '';

    // A wrong answer on a long passage pushes the correction below the fold; bring it
    // into view instead of leaving the learner to hunt for it.
    useEffect(() => {
        if (!isChecked || isCorrect !== false) return;
        const node = correctionRef.current;
        if (!node) return;
        const frame = requestAnimationFrame(() => node.scrollIntoView({ behavior: 'smooth', block: 'nearest' }));
        return () => cancelAnimationFrame(frame);
    }, [isChecked, isCorrect, explanation, fetchingExplanation]);

    // L'en-tete et la barre d'actions sont fixes : le contenu se pose entre les
    // deux, a leur hauteur reelle (le titre peut passer sur deux lignes, la barre
    // grandit avec la correction). Un en-tete « sticky » ne collerait pas ici :
    // la mise en page de l'application masque le debordement horizontal, ce qui
    // fait de son conteneur le repere de defilement.
    const hauteurEntete = useHauteurDe(entete);
    const hauteurBarre = useHauteurDe(barreActions);

    // Le texte a lire : toujours visible a cote de la question sur ordinateur ;
    // sur telephone, deplie a la premiere question d'un exercice puis replie
    // (« Revoir le texte ») pour que la question suivante arrive sans tout faire
    // defiler. A chaque nouvelle question, le focus va sur l'enonce : un lecteur
    // d'ecran le lit, et au clavier on repart de la question.
    useEffect(() => {
        setPassageOuvert(currentQuestionIndex === 0 && !isReviewMode);
        if (premierAffichage.current) {
            premierAffichage.current = false;
            return;
        }
        const zone = zoneQuestion.current;
        if (!zone) return;
        const frame = requestAnimationFrame(() => {
            const cible = zone.querySelector<HTMLElement>('[data-enonce]') ?? zone;
            if (!cible.hasAttribute('tabindex')) cible.setAttribute('tabindex', '-1');
            cible.focus({ preventScroll: true });
            const reduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({ top: 0, behavior: reduit ? 'auto' : 'smooth' });
        });
        return () => cancelAnimationFrame(frame);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [remountKey]);

    // Une erreur surligne la phrase qui la prouve dans le texte : on le rouvre.
    useEffect(() => {
        if (highlightedText) setPassageOuvert(true);
    }, [highlightedText]);

    if (!exercise || !question) {
        return (
            <AppLayout focusMode>
                <div className="flex flex-col items-center justify-center py-20">
                    <AlertCircle aria-hidden="true" className="h-12 w-12 text-muted-foreground" />
                    <h1 className="mt-4 text-xl font-medium">Session non disponible</h1>
                </div>
            </AppLayout>
        );
    }

    const avecEcoute = isListening && !!(listeningAudioUrl || listeningAudioText);
    const avecPassage = !avecEcoute && !!activePassage;
    const competence = COMPETENCES[activeSkillType];
    const enLecture = playingTts === 'passage';
    const ecoutesRestantes = Math.max(0, 2 - listenCount);
    const libelleEcoute = enLecture
        ? 'Lecture en cours…'
        : listenCount === 0
            ? 'Écouter l’enregistrement'
            : listenCount >= 2
                ? 'Limite d’écoutes atteinte'
                : `Réécouter (${ecoutesRestantes} restante${ecoutesRestantes > 1 ? 's' : ''})`;

    const totalAffiche = isReviewMode ? reviewQueue.length : totalQuestionsInSession;
    const rangAffiche = isReviewMode ? currentQuestionIndex + 1 : Math.min(overallProgressCount + 1, totalQuestionsInSession);
    const faites = (isReviewMode ? currentQuestionIndex : overallProgressCount) + (isChecked ? 1 : 0);
    const libelleProgression = `${isReviewMode ? t('exercise.review', 'Révision') : 'Question'} ${rangAffiche}/${totalAffiche}`;

    const etat: 'neutre' | 'passee' | 'juste' | 'faux' = !isChecked ? 'neutre' : skipped ? 'passee' : isCorrect ? 'juste' : 'faux';
    const enAttente = submitting || isVerifying;

    const quitter = () => {
        if (window.confirm(t('exercise.quit_confirm', 'Quitter la session ? Ta progression sur cette session ne sera pas enregistrée.'))) {
            router.visit(route('dashboard'));
        }
    };

    const boutonRond = 'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background';

    return (
        <AppLayout focusMode>
            <Head title={`${node.title} - PrePla`} />

            {/* Hauteur d'ecran en style en ligne : `.learning-canvas` (CSS hors couche,
                `min-height: 100%`) l'emporte sur `min-h-screen`. `palette-lecteur` : le
                bleu des boutons et son texte, lisibles dans les deux themes (app.css). */}
            <div className="learning-canvas palette-lecteur flex flex-col" style={{ minHeight: '100dvh' }}>
                {/* ── En-tete : une bande fine, la progression sur toute la largeur ── */}
                <header ref={setEntete} className="fixed inset-x-0 top-0 z-40 border-b border-border/70 bg-background/90 backdrop-blur-xl">
                    <div className="mx-auto flex max-w-6xl items-center gap-2 px-2 py-1.5 sm:px-4">
                        <button
                            type="button"
                            onClick={quitter}
                            title={t('exercise.quit', 'Quitter')}
                            aria-label={t('exercise.quit', 'Quitter')}
                            className="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-foreground transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <X className="h-5 w-5" aria-hidden="true" />
                        </button>
                        <div className="min-w-0 flex-1">
                            <h1 className="line-clamp-2 text-[0.9375rem] font-semibold leading-tight sm:truncate">{node.title}</h1>
                            <p className="truncate text-[0.8125rem] leading-tight text-muted-foreground">
                                {node.exam.language.name} · {node.exam.name}
                                {!isReviewMode && <span className="hidden sm:inline"> · {t('exercise.mastery_threshold', 'Valide ce concept à 60%')}</span>}
                            </p>
                        </div>

                        {/* Le minuteur cache tient l'etat ; l'arc l'affiche. */}
                        <div className="hidden">
                            <ExerciseTimer
                                key={timerKey}
                                timeLimit={TIME_PER_QUESTION}
                                onTimeUpdate={handleTimeUpdate}
                                onExpire={handleTimeExpire}
                            />
                        </div>
                        <ArcTimer
                            seconds={timerSeconds}
                            limit={TIME_PER_QUESTION}
                            warning={timerWarning}
                            critical={timerCritical}
                            expired={timerExpired}
                        />
                    </div>
                    <div className="mx-auto max-w-6xl px-3 pb-2 sm:px-4">
                        <BarreDeProgression libelle={libelleProgression} fait={faites} total={totalAffiche} />
                    </div>
                </header>

                {/* Un <div> : la mise en page de l'application fournit deja le <main>. */}
                <div
                    className="mx-auto w-full max-w-6xl flex-1 px-4"
                    style={{ paddingTop: (hauteurEntete || 96) + 16, paddingBottom: (hauteurBarre || 80) + 24 }}
                >
                    <div
                        ref={contentRef}
                        className={visible ? 'animate-in fade-in slide-in-from-bottom-2 duration-300 motion-reduce:animate-none' : 'opacity-0 transition-opacity duration-150'}
                    >
                        {/* Une ligne de presentation au lieu de la grande carte : la
                            question doit etre visible des le chargement. */}
                        <div className="mb-4 flex flex-wrap items-center gap-2">
                            {isReviewMode && (
                                <span className="rounded-full bg-amber-100 px-3 py-1 text-[0.8125rem] font-semibold text-amber-900 dark:bg-amber-950/60 dark:text-amber-100">
                                    Révision de tes erreurs
                                </span>
                            )}
                            {(competence || activeTypeName) && (
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-[0.8125rem] font-semibold text-primary">
                                    {competence && <competence.icone aria-hidden="true" className="h-3.5 w-3.5" />}
                                    {[competence?.nom, activeTypeName].filter(Boolean).join(' · ')}
                                </span>
                            )}
                        </div>

                        <div className={avecPassage ? 'lg:grid lg:grid-cols-2 lg:items-start lg:gap-8' : 'mx-auto w-full max-w-3xl'}>
                            {/* Le texte a lire — jamais pour une ecoute (c'est la
                                transcription a entendre). Sur ordinateur, a gauche,
                                avec son propre defilement : texte et question restent
                                a l'ecran ensemble. */}
                            {avecPassage && (
                                <aside
                                    aria-label="Texte de référence"
                                    className="mb-4 lg:sticky lg:top-[var(--haut-passage)] lg:mb-0 lg:max-h-[var(--max-passage)] lg:overflow-y-auto"
                                    style={{
                                        '--haut-passage': `${(hauteurEntete || 96) + 16}px`,
                                        '--max-passage': `calc(100dvh - ${(hauteurEntete || 96) + (hauteurBarre || 80) + 40}px)`,
                                    } as React.CSSProperties}
                                >
                                    <button
                                        type="button"
                                        aria-expanded={passageOuvert}
                                        aria-controls="passage-seance"
                                        onClick={() => setPassageOuvert((o) => !o)}
                                        className="flex min-h-12 w-full items-center justify-between gap-2 rounded-2xl border-[1.5px] border-border bg-card px-4 text-left text-[0.9375rem] font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring lg:hidden"
                                    >
                                        {passageOuvert ? 'Masquer le texte' : 'Revoir le texte'}
                                        <ChevronDown aria-hidden="true" className={cn('h-5 w-5 transition-transform', passageOuvert && 'rotate-180')} />
                                    </button>
                                    <div
                                        id="passage-seance"
                                        className={cn('mt-2 rounded-2xl border-[1.5px] border-border bg-card p-4 sm:p-5 lg:mt-0 lg:block', !passageOuvert && 'hidden')}
                                    >
                                        <div className="mb-3 flex items-center justify-between gap-2 border-b border-border pb-2">
                                            <span className="text-[0.8125rem] font-bold uppercase tracking-wide text-muted-foreground">Texte de référence</span>
                                            <button
                                                type="button"
                                                onClick={() => playTts(activePassage, 'passage')}
                                                aria-label={enLecture ? 'Lecture du texte en cours' : 'Écouter le texte'}
                                                aria-pressed={enLecture}
                                                className={cn(boutonRond, enLecture ? 'bg-primary/15 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground')}
                                            >
                                                {enLecture ? <Volume2 aria-hidden="true" className="h-5 w-5" /> : <Volume1 aria-hidden="true" className="h-5 w-5" />}
                                            </button>
                                        </div>
                                        <HighlightedPassage text={activePassage} highlight={highlightedText || ''} />
                                    </div>
                                </aside>
                            )}

                            <section ref={zoneQuestion} aria-label={libelleProgression} className="space-y-4">
                                {/* ECOUTE : la transcription n'est JAMAIS montree, il faut
                                    ecouter. Deux ecoutes au plus, comme a l'examen. Tout le
                                    cadre est le bouton. */}
                                {avecEcoute && (
                                    <div className="space-y-2">
                                        <button
                                            type="button"
                                            onClick={() => {
                                                if (listenCount >= 2 || playingTts === 'passage') return;
                                                setListenCount(c => c + 1);
                                                playListening(listeningAudioUrl, listeningAudioText);
                                            }}
                                            disabled={listenCount >= 2 && playingTts !== 'passage'}
                                            aria-label={libelleEcoute}
                                            aria-busy={enLecture}
                                            className="flex min-h-16 w-full items-center gap-4 rounded-2xl border-[1.5px] border-primary/40 bg-primary/5 px-4 py-3 text-left transition-colors enabled:hover:bg-primary/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <span aria-hidden="true" className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                                {enLecture ? <Volume2 className="h-6 w-6 motion-safe:animate-pulse" /> : <Play className="ml-0.5 h-6 w-6" />}
                                            </span>
                                            <span className="min-w-0 flex-1">
                                                <span className="block text-base font-semibold text-foreground">{libelleEcoute}</span>
                                                <span className="mt-0.5 block text-[0.8125rem] text-muted-foreground">
                                                    Écoute {Math.min(listenCount, 2)}/2 · puis réponds à la question
                                                </span>
                                            </span>
                                            <Headphones aria-hidden="true" className="hidden h-5 w-5 shrink-0 text-muted-foreground sm:block" />
                                        </button>
                                        {audioUnavailable && listeningAudioText && (
                                            <div
                                                role="status"
                                                className="rounded-2xl border-[1.5px] border-amber-300 bg-amber-50 p-4 text-left text-[0.9375rem] text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"
                                            >
                                                <p className="font-bold">L’audio est indisponible pour le moment.</p>
                                                <p className="mt-1">Voici le texte de l’enregistrement, pour que tu puisses quand même répondre :</p>
                                                <p className="mt-2 italic">{listeningAudioText}</p>
                                            </div>
                                        )}
                                    </div>
                                )}

                                <div className={cn('rounded-2xl border-[1.5px] border-border bg-card p-4 sm:p-6', isChecked ? (isCorrect ? 'answer-pop' : 'answer-shake') : '')}>
                                    {/* Ce qu'il faut faire, et l'ecoute de la question. L'enonce
                                        lui-meme est rendu par chaque format. */}
                                    <div className="mb-4 flex items-start justify-between gap-3">
                                        {consigneDuType ? (
                                            <p className="flex items-center gap-1.5 pt-2.5 text-[0.875rem] font-semibold text-primary">
                                                <Info aria-hidden="true" className="h-4 w-4 shrink-0" />
                                                {consigneDuType}
                                            </p>
                                        ) : <span />}
                                        <button
                                            type="button"
                                            onClick={() => playTts(question.text || question.prompt || '', 'question')}
                                            aria-label={playingTts === 'question' ? 'Lecture de la question en cours' : 'Écouter la question'}
                                            aria-pressed={playingTts === 'question'}
                                            className={cn(boutonRond, playingTts === 'question' ? 'bg-primary/15 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground')}
                                        >
                                            {playingTts === 'question' ? <Volume2 aria-hidden="true" className="h-5 w-5" /> : <Volume1 aria-hidden="true" className="h-5 w-5" />}
                                        </button>
                                    </div>

                                    {/* Sans cette barriere, un seul champ mal forme venu de l'IA
                                        (un objet la ou une chaine etait attendue) vidait l'ecran de
                                        la seance et effacait toutes les reponses deja donnees. Elle
                                        n'entoure que le champ de saisie : barre d'action, minuteur
                                        et reponses du parent restent intacts, et la question peut
                                        etre passee sans compter. */}
                                    <ExerciseErrorBoundary
                                        resetKey={remountKey}
                                        onSkip={() => handleAnswer(question.id ?? String(currentQuestionIndex), '__skipped__')}
                                    >
                                        {/* Ici la correction s'affiche question par question :
                                            `revealed` la montre des que la reponse est verifiee.
                                            `disabled` verrouille aussi pendant la verification par
                                            le serveur et pendant l'envoi : sinon la reponse
                                            modifiee entre-temps partait au serveur alors que le
                                            verdict affiche portait sur l'ancienne. */}
                                        <Component
                                            // Always-unique key so the input component fully remounts between
                                            // questions (AI-generated questions can share/duplicate ids, which
                                            // left the previous answer stuck in the text field).
                                            key={remountKey}
                                            question={{ ...exercise.content, ...question }}
                                            lang={nodeCode}
                                            onAnswer={(_childId: string, ans: FormDataConvertible) => handleAnswer(question.id ?? String(currentQuestionIndex), ans)}
                                            selectedAnswer={answers[answerKey(question.id ?? String(currentQuestionIndex))]}
                                            disabled={isChecked || enAttente}
                                            revealed={isChecked}
                                        />
                                    </ExerciseErrorBoundary>

                                    <CaracteresSpeciaux langue={nodeCode} zone={zoneQuestion} cle={remountKey} />

                                    {/* Speaking: show what the AI heard (the transcription) so the
                                        learner can tell a real mistake from a mis-transcription. */}
                                    {isChecked && Boolean(answers[`${question.id}_transcription`]) && (
                                        <div className="mt-4 rounded-2xl border-[1.5px] border-border bg-muted/40 p-4">
                                            <p className="mb-1.5 text-[0.8125rem] font-bold uppercase tracking-wide text-muted-foreground">
                                                {t('exercise.heard', 'Ce que l’IA a entendu')}
                                            </p>
                                            <p className="text-[0.9375rem] font-medium italic text-foreground">
                                                « {String(answers[`${question.id}_transcription`])} »
                                            </p>
                                        </div>
                                    )}

                                    {/* Speaking formative feedback: what you covered + what to add to go further */}
                                    {isChecked && speakingPoints && (speakingPoints.covered.length > 0 || speakingPoints.missing.length > 0) && (
                                        <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                            {speakingPoints.covered.length > 0 && (
                                                <div className="rounded-2xl border-[1.5px] border-emerald-300 bg-emerald-50 p-3 dark:border-emerald-800 dark:bg-emerald-950/40">
                                                    <p className="mb-1.5 text-[0.8125rem] font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-200">Bien dit</p>
                                                    <ul className="space-y-1">
                                                        {collection<unknown>(speakingPoints.covered).map((p, i) => (
                                                            <li key={i} className="flex gap-1.5 text-[0.875rem] font-medium text-foreground">
                                                                <Check aria-hidden="true" className="mt-0.5 h-4 w-4 shrink-0 text-emerald-700 dark:text-emerald-300" />
                                                                {coerceOption(p)}
                                                            </li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            )}
                                            {speakingPoints.missing.length > 0 && (
                                                <div className="rounded-2xl border-[1.5px] border-amber-300 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950/40">
                                                    <p className="mb-1.5 text-[0.8125rem] font-bold uppercase tracking-wide text-amber-900 dark:text-amber-100">Pour aller plus loin</p>
                                                    <ul className="space-y-1">
                                                        {collection<unknown>(speakingPoints.missing).map((p, i) => (
                                                            <li key={i} className="flex gap-1.5 text-[0.875rem] font-medium text-foreground">
                                                                <Plus aria-hidden="true" className="mt-0.5 h-4 w-4 shrink-0 text-amber-700 dark:text-amber-300" />
                                                                {coerceOption(p)}
                                                            </li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>

                                {/* ── Correction — dans le flux, juste sous l'exercice ──
                                    Ce panneau flottait au-dessus de la barre d'action : la barre
                                    « Incorrect » grandit avec son bouton et recouvrait le bas des
                                    explications, et le panneau masquait l'exercice, donc la réponse
                                    donnée n'était plus visible au moment de lire la correction. Il
                                    défile maintenant avec la page, sous la question et sous ce que
                                    l'apprenant a répondu. */}
                                {isChecked && skipped && (
                                    <div role="status" className="flex gap-3 rounded-2xl border-[1.5px] border-amber-300 bg-card p-4 dark:border-amber-800">
                                        <Info aria-hidden="true" className="mt-0.5 h-5 w-5 shrink-0 text-amber-700 dark:text-amber-300" />
                                        <p className="text-[0.9375rem] leading-relaxed text-foreground">
                                            Question passée : elle ne compte pas dans ton score, l’exercice ne s’est pas affiché correctement.
                                        </p>
                                    </div>
                                )}

                                {isChecked && !skipped && !isCorrect && (explanation || fetchingExplanation || expectedText) && (
                                    <div
                                        ref={correctionRef}
                                        role="status"
                                        className="space-y-3 rounded-2xl border-[1.5px] border-red-300 bg-card p-4 sm:p-5 dark:border-red-800"
                                    >
                                        {expectedText && (
                                            <p className="flex gap-2 text-[0.9375rem] leading-relaxed text-foreground">
                                                <Check aria-hidden="true" className="mt-0.5 h-5 w-5 shrink-0 text-emerald-700 dark:text-emerald-300" />
                                                <span>Réponse attendue : <strong>{expectedText}</strong></span>
                                            </p>
                                        )}
                                        {givenText && (
                                            <p className="flex gap-2 text-[0.9375rem] leading-relaxed text-muted-foreground">
                                                <X aria-hidden="true" className="mt-0.5 h-5 w-5 shrink-0 text-red-700 dark:text-red-300" />
                                                <span>Ta réponse : <span className="font-medium line-through decoration-red-500">{givenText}</span></span>
                                            </p>
                                        )}
                                        {fetchingExplanation ? (
                                            <p className="flex items-center gap-2 text-[0.9375rem] font-medium italic text-muted-foreground">
                                                <Loader2 aria-hidden="true" className="h-4 w-4 animate-spin" />
                                                Analyse de ton erreur…
                                            </p>
                                        ) : explanation ? (
                                            <div className="rounded-xl bg-muted/50 p-3 sm:p-4">
                                                <p className="mb-1 text-[0.8125rem] font-bold uppercase tracking-wide text-foreground/80">Pourquoi</p>
                                                {typeof explanation === 'object' ? (
                                                    <div className="space-y-2">
                                                        <p className="text-[0.9375rem] font-bold leading-relaxed text-foreground">
                                                            {(i18n.language === 'fr' && explanation.french_translation?.concept) ? explanation.french_translation.concept : explanation.concept}
                                                        </p>
                                                        {(i18n.language === 'fr' && explanation.french_translation?.hint ? explanation.french_translation.hint : explanation.hint) && (
                                                            <p className="text-[0.9375rem] leading-relaxed text-foreground">
                                                                {i18n.language === 'fr' && explanation.french_translation?.hint ? explanation.french_translation.hint : explanation.hint}
                                                            </p>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <FormattedFeedback
                                                        text={explanation}
                                                        className="space-y-1.5 text-[0.9375rem] font-medium leading-relaxed text-foreground"
                                                    />
                                                )}
                                            </div>
                                        ) : null}
                                    </div>
                                )}
                            </section>
                        </div>
                    </div>
                </div>
            </div>

            {/* ── Barre d'actions, collee en bas a toutes les tailles ──
                Elle porte le verdict (couleur ET icone ET mot, jamais la couleur
                seule) et l'action principale, d'au moins 48 px de haut. */}
            <div
                ref={setBarreActions}
                className={cn(
                    'palette-lecteur fixed inset-x-0 bottom-0 z-40 border-t backdrop-blur-xl transition-colors duration-300',
                    etat === 'neutre' && 'border-border/70 bg-background/90',
                    etat === 'juste' && 'border-emerald-300 bg-emerald-50/95 dark:border-emerald-800 dark:bg-emerald-950/90',
                    etat === 'faux' && 'border-red-300 bg-red-50/95 dark:border-red-800 dark:bg-red-950/90',
                    etat === 'passee' && 'border-amber-300 bg-amber-50/95 dark:border-amber-800 dark:bg-amber-950/90',
                )}
            >
                <div
                    className={cn(
                        'mx-auto flex max-w-6xl gap-3 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))]',
                        isChecked ? 'flex-col sm:flex-row sm:items-center' : 'flex-row items-center',
                    )}
                >
                    {/* Verdict, ou outils tant que la reponse n'est pas verifiee */}
                    <div aria-live="polite" className={cn('flex min-h-12 items-center gap-3', isChecked ? 'flex-1' : 'shrink-0 sm:flex-1')}>
                        {isChecked && (
                            <div className="flex items-center gap-3 animate-in fade-in slide-in-from-bottom-1 duration-200 motion-reduce:animate-none">
                                {etat === 'juste' ? (
                                    // Light "win" GIF on a correct answer (the big trophy is
                                    // reserved for end-of-session results).
                                    <img
                                        src="/animation/star.gif"
                                        alt=""
                                        width={44}
                                        height={44}
                                        className="shrink-0 object-contain animate-in zoom-in-50 duration-300 motion-reduce:animate-none"
                                    />
                                ) : (
                                    <span
                                        aria-hidden="true"
                                        className={cn(
                                            'flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-white animate-in zoom-in-50 duration-300 motion-reduce:animate-none',
                                            etat === 'passee' ? 'bg-amber-700' : 'bg-red-700',
                                        )}
                                    >
                                        {etat === 'passee' ? <Info className="h-6 w-6" /> : <X className="h-6 w-6" />}
                                    </span>
                                )}
                                <div className="min-w-0">
                                    <p
                                        className={cn(
                                            'text-base font-bold leading-tight',
                                            etat === 'juste' && 'text-emerald-800 dark:text-emerald-200',
                                            etat === 'faux' && 'text-red-800 dark:text-red-200',
                                            etat === 'passee' && 'text-amber-900 dark:text-amber-100',
                                        )}
                                    >
                                        {skipped ? 'Question passée' : isCorrect ? t('exercise.correct') : t('exercise.incorrect')}
                                    </p>
                                    {!isCorrect && !skipped && (
                                        <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <span className="text-[0.8125rem] font-medium text-red-900 dark:text-red-200">
                                                {fetchingExplanation ? 'Analyse…' : 'Vois l’explication ci-dessus'}
                                            </span>
                                            <button
                                                type="button"
                                                onClick={() => setIsChatOpen(true)}
                                                className="inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-full border-[1.5px] border-red-300 bg-background/70 px-3 text-[0.875rem] font-bold text-red-800 transition-colors hover:bg-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:border-red-700 dark:text-red-100"
                                            >
                                                <MessageSquare aria-hidden="true" className="h-4 w-4" />
                                                Discuter
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}


                        {!isChecked && (
                            <>
                                <button
                                    type="button"
                                    onClick={() => setIsDictionaryOpen(true)}
                                    className="inline-flex h-12 items-center gap-2 rounded-full border-[1.5px] border-border bg-card px-4 text-[0.9375rem] font-semibold text-foreground transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                >
                                    <Search aria-hidden="true" className="h-4 w-4" />
                                    Dictionnaire
                                </button>
                                <span className="hidden items-center gap-1.5 text-[0.8125rem] text-muted-foreground select-none lg:inline-flex" aria-hidden="true">
                                    <kbd className="rounded border border-border px-1.5 font-mono text-[0.8125rem]">1–4</kbd> choisir
                                    <kbd className="ml-1 rounded border border-border px-1.5 font-mono text-[0.8125rem]">Entrée</kbd> {t('exercise.check', 'Check')}
                                </span>
                            </>
                        )}
                    </div>

                    {/* La discussion s'ouvre en plein ecran : rendue dans <body>, car le flou
                        de cette barre (backdrop-filter) devient le repere des enfants en
                        position fixed, et la fenetre restait coincee dans la barre. Toujours
                        montee (elle garde la conversation), elle ne rend rien fermee. */}
                    {typeof document !== 'undefined' && createPortal((
                        <AiChatFeedback
                            isOpen={isChatOpen}
                            onClose={() => setIsChatOpen(false)}
                            initialExplanation={explanation ? getExplanationText(explanation, i18n.language) : undefined}
                            context={{
                                prompt: question.text || question.prompt || '',
                                // Multi-field types (note/form/table-completion…) keep the user's
                                // answer and the expected one as maps, not single scalars.
                                user_answer: (() => {
                                    const ua = answers[answerKey(question.id)];
                                    return (ua && typeof ua === 'object') ? Object.values(ua).join(', ') : String(ua ?? '');
                                })(),
                                correct_answer: String(
                                    question.correct_answer || question.correct
                                    || (question.correct_answers ? Object.values(question.correct_answers).join(', ') : '')
                                ),
                                language: node.exam.language.name
                            }}
                        />
                    ), document.body)}

                    {/* Dictionnaire : rendu dans <body> par un portail. Les conteneurs du
                        lecteur portent des transform d'animation, et un parent transforme
                        devient le repere des enfants en position fixed : le panneau se
                        retrouvait ancre a la question au lieu de l'ecran, et restait
                        coince hors de vue sur grand ecran. */}
                    {isDictionaryOpen && createPortal((
                        <div
                            className="palette-lecteur fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-0 backdrop-blur-sm sm:items-center sm:p-4"
                            onClick={() => setIsDictionaryOpen(false)}
                        >
                            <div
                                role="dialog"
                                aria-modal="true"
                                aria-labelledby="titre-dictionnaire"
                                onClick={(e) => e.stopPropagation()}
                                className="flex max-h-[85vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl border-[1.5px] border-border bg-card text-foreground shadow-2xl animate-in slide-in-from-bottom duration-200 sm:max-h-[80vh] sm:rounded-2xl sm:zoom-in-95"
                            >
                                <div className="flex justify-center pt-2 pb-1 sm:hidden" aria-hidden="true">
                                    <span className="h-1 w-10 rounded-full bg-border" />
                                </div>
                                <div className="flex flex-shrink-0 items-center justify-between border-b border-border bg-muted/40 px-4 py-2">
                                    <h2 id="titre-dictionnaire" className="font-bold">Dictionnaire rapide</h2>
                                    <button
                                        type="button"
                                        onClick={() => setIsDictionaryOpen(false)}
                                        aria-label="Fermer le dictionnaire"
                                        className={cn(boutonRond, 'hover:bg-muted')}
                                    >
                                        <X aria-hidden="true" className="h-5 w-5" />
                                    </button>
                                </div>
                                <div className="overflow-y-auto p-4">
                                    <div className="relative">
                                        <input
                                            autoFocus
                                            type="text"
                                            aria-label="Mot à rechercher"
                                            placeholder="Rechercher un mot…"
                                            className="h-12 w-full rounded-xl border-[1.5px] border-border bg-background px-4 text-base font-medium text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            onKeyDown={async (e) => {
                                                if (e.key === 'Enter' && searchTerm.trim()) {
                                                    setIsSearching(true);
                                                    setSearchResult(null);
                                                    setIsVocabSaved(false);
                                                    setVocabError(null);
                                                    try {
                                                        const res = await fetch(route('dictionary.lookup', { language: nodeCode, word: searchTerm }));
                                                        const data = await res.json();
                                                        if (data.word) setSearchResult(data);
                                                        else setSearchResult({ error: true });
                                                    } catch {
                                                        setSearchResult({ error: true });
                                                    } finally {
                                                        setIsSearching(false);
                                                    }
                                                }
                                            }}
                                        />
                                    </div>
                                    <div className="mt-4 min-h-[120px]" aria-live="polite">
                                        {isSearching ? (
                                            <div className="flex flex-col items-center justify-center gap-3 py-6">
                                                <Loader2 aria-hidden="true" className="h-6 w-6 animate-spin text-primary" />
                                                <p className="text-[0.875rem] font-medium italic text-muted-foreground">Recherche en cours…</p>
                                            </div>
                                        ) : searchResult?.error ? (
                                            <div className="py-6 text-center text-[0.9375rem] text-muted-foreground">Mot non trouvé. Essaie une autre orthographe.</div>
                                        ) : searchResult ? (
                                            <div className="space-y-4 animate-in fade-in slide-in-from-top-2 duration-300">
                                                <div className="rounded-xl border-[1.5px] border-primary/25 bg-primary/5 p-4">
                                                    <div className="mb-2 flex items-center justify-between gap-2">
                                                        <h3 className="text-lg font-bold text-foreground">{searchResult.word}</h3>
                                                        {searchResult.skill_level && (
                                                            <span className="rounded-full bg-primary/15 px-2 py-0.5 text-[0.8125rem] font-bold uppercase text-primary">{searchResult.skill_level}</span>
                                                        )}
                                                    </div>
                                                    <p className="mb-3 text-[0.9375rem] italic leading-relaxed text-foreground">« {searchResult.definition} »</p>
                                                    <p className="text-[0.875rem] font-medium text-muted-foreground">Traduction : <span className="text-foreground">{searchResult.translation}</span></p>
                                                </div>
                                                <button
                                                    type="button"
                                                    disabled={isVocabSaved}
                                                    onClick={async () => {
                                                        // Plain fetch, not an Inertia visit: the previous
                                                        // router.post followed a redirect back to this page
                                                        // and restarted the session mid-exercise.
                                                        setVocabError(null);
                                                        try {
                                                            const res = await fetch(route('dictionary.save'), {
                                                                method: 'POST',
                                                                headers: {
                                                                    'Content-Type': 'application/json',
                                                                    Accept: 'application/json',
                                                                    'X-Requested-With': 'XMLHttpRequest',
                                                                    'X-XSRF-TOKEN': csrfToken(),
                                                                },
                                                                body: JSON.stringify({ dictionary_word_id: searchResult.id }),
                                                            });
                                                            if (!res.ok) throw new Error(`HTTP ${res.status}`);
                                                            setIsVocabSaved(true);
                                                            setTimeout(() => {
                                                                setIsDictionaryOpen(false);
                                                                setSearchResult(null);
                                                                setSearchTerm('');
                                                                setIsVocabSaved(false);
                                                            }, 1500);
                                                        } catch {
                                                            setVocabError("Le mot n'a pas pu être ajouté. Vérifie ta connexion, puis réessaie.");
                                                        }
                                                    }}
                                                    className={cn(
                                                        'flex h-12 w-full items-center justify-center gap-2 rounded-full font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
                                                        isVocabSaved ? 'bg-emerald-700 text-white' : 'bg-primary text-primary-foreground hover:bg-primary/90',
                                                    )}
                                                >
                                                    {isVocabSaved ? <Check aria-hidden="true" className="h-4 w-4" /> : <Plus aria-hidden="true" className="h-4 w-4" />}
                                                    {isVocabSaved ? 'Ajouté au lexique !' : 'Ajouter à mon lexique'}
                                                </button>
                                                {vocabError && (
                                                    <p role="alert" className="text-[0.875rem] font-medium text-red-700 dark:text-red-300">{vocabError}</p>
                                                )}
                                            </div>
                                        ) : (
                                            <div className="py-6 text-center text-[0.9375rem] italic text-muted-foreground">
                                                Tape un mot pour voir sa définition et l’ajouter à ton lexique.
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    ), document.body)}

                    {/* Action principale */}
                    <button
                        type="button"
                        className={cn(
                            'inline-flex h-12 items-center justify-center gap-2 rounded-full px-6 text-base font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50',
                            isChecked ? 'w-full sm:w-auto sm:min-w-44' : 'flex-1 sm:flex-none sm:min-w-44',
                            enAttente || etat === 'neutre'
                                ? 'bg-primary text-primary-foreground enabled:hover:bg-primary/90'
                                : etat === 'juste'
                                    ? 'bg-emerald-700 text-white hover:bg-emerald-800'
                                    : etat === 'faux'
                                        ? 'bg-red-700 text-white hover:bg-red-800'
                                        : 'bg-amber-700 text-white hover:bg-amber-800',
                        )}
                        disabled={!hasAnswer || submitting || isVerifying}
                        onClick={isChecked ? nextStep : checkAnswer}
                        aria-keyshortcuts="Enter"
                    >
                        {enAttente ? (
                            <>
                                <Loader2 aria-hidden="true" className="h-4 w-4 animate-spin" />
                                {isVerifying ? 'Vérification…' : t('exercise.finish', 'Finish')}
                            </>
                        ) : isChecked ? (
                            <>
                                {isCorrect || skipped ? (isLastQuestion ? t('exercise.finish', 'Finish') : t('exercise.next', 'Next')) : 'Continuer'}
                                <ChevronRight aria-hidden="true" className="h-4 w-4" />
                            </>
                        ) : (
                            <>
                                {t('exercise.check', 'Check')}
                                <ChevronRight aria-hidden="true" className="h-4 w-4" />
                            </>
                        )}
                    </button>
                </div>
            </div>
        </AppLayout>
    );
}
