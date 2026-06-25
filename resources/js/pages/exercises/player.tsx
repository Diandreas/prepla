import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import React, { useState, useCallback, useMemo, useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';

// Read the freshest CSRF token. The XSRF-TOKEN cookie tracks the live session,
// whereas the <meta> tag is frozen at page load and goes stale on a long-running
// session — which caused 419s on /api/tts/speak and the AI endpoints.
function csrfToken(): string {
    const cookie = document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='));
    if (cookie) return decodeURIComponent(cookie.split('=')[1]);
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
}

const INLINE_SVGS: Record<string, React.ReactNode> = {
    'volume-1': <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>,
    'volume-2': <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>,
    'plus': <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>,
    'info': <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>,
};

function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    if (INLINE_SVGS[name]) {
        return (
            <span className={className} style={{ width: size, height: size, display: 'inline-flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, ...style }}>
                {React.cloneElement(INLINE_SVGS[name] as React.ReactElement, { width: size, height: size })}
            </span>
        );
    }
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
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
import { VocabularyCard } from '@/components/exercises/vocabulary-card';
import { ExerciseTimer } from '@/components/exercises/exercise-timer';
import { AiChatFeedback } from '@/components/exercises/ai-chat-feedback';

interface Exercise {
    id: number;
    exercise_type: {
        component_key: string;
        name: string;
    };
    questions: any[];
    content: any;
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
            }
        }
    };
    exercises: Exercise[];
    progress: {
        id: number;
        status: string;
    };
}

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
};

// Segmented progress dots
function HighlightedPassage({ text, highlight }: { text: string; highlight: string }) {
    if (!highlight) return <p className="leading-relaxed">{text}</p>;
    
    const parts = text.split(new RegExp(`(${highlight.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi'));
    
    return (
        <p className="leading-relaxed">
            {parts.map((part, i) => 
                part.toLowerCase() === highlight.toLowerCase() ? (
                    <span key={i} className="bg-yellow-200 dark:bg-yellow-900/50 px-0.5 rounded transition-all duration-500 font-medium">
                        {part}
                    </span>
                ) : part
            )}
        </p>
    );
}

function ProgressDots({ total, current }: { total: number; current: number }) {
    // Cap at 40 dots for readability
    const MAX = 40;
    const capped = Math.min(total, MAX);
    const ratio = total > MAX ? current / total : current;
    const filledCount = total > MAX ? Math.round(ratio * capped) : current;

    return (
        <div className="flex items-center gap-[3px] flex-wrap">
            {Array.from({ length: capped }).map((_, i) => {
                const filled = i < filledCount;
                const active = i === filledCount;
                return (
                    <span
                        key={i}
                        style={{
                            width: capped > 20 ? 6 : 8,
                            height: capped > 20 ? 6 : 8,
                            borderRadius: '50%',
                            display: 'block',
                            transition: 'all 0.3s cubic-bezier(.4,0,.2,1)',
                            background: filled
                                ? 'var(--player-accent)'
                                : active
                                ? 'var(--player-accent-muted)'
                                : 'var(--player-dot-empty)',
                            transform: active ? 'scale(1.35)' : 'scale(1)',
                            boxShadow: active ? '0 0 0 2px var(--player-accent-muted)' : 'none',
                        }}
                    />
                );
            })}
        </div>
    );
}

// Circular SVG countdown arc
function ArcTimer({ seconds, limit, warning, critical, expired }: {
    seconds: number; limit: number;
    warning: boolean; critical: boolean; expired: boolean;
}) {
    const r = 18;
    const circ = 2 * Math.PI * r;
    const pct = limit > 0 ? seconds / limit : 1;
    const offset = circ * (1 - pct);
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;

    const trackColor = expired || critical ? '#ef4444' : warning ? '#f59e0b' : 'var(--player-accent)';
    const bgColor = expired || critical ? 'rgba(239,68,68,0.12)' : warning ? 'rgba(245,158,11,0.12)' : 'rgba(var(--player-accent-rgb),0.1)';

    return (
        <div
            style={{
                position: 'relative',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                width: 52,
                height: 52,
                animation: critical && !expired ? 'timerPulse 1s ease-in-out infinite' : 'none',
            }}
        >
            <svg width="52" height="52" style={{ position: 'absolute', top: 0, left: 0 }}>
                {/* Background track */}
                <circle cx="26" cy="26" r={r} fill="none" stroke={bgColor} strokeWidth="3" />
                {/* Progress arc */}
                <circle
                    cx="26" cy="26" r={r}
                    fill="none"
                    stroke={trackColor}
                    strokeWidth="3"
                    strokeLinecap="round"
                    strokeDasharray={circ}
                    strokeDashoffset={offset}
                    transform="rotate(-90 26 26)"
                    style={{ transition: 'stroke-dashoffset 0.9s linear, stroke 0.3s' }}
                />
            </svg>
            <span style={{
                fontSize: 11,
                fontWeight: 700,
                fontVariantNumeric: 'tabular-nums',
                letterSpacing: '-0.02em',
                color: expired || critical ? '#ef4444' : warning ? '#f59e0b' : 'var(--player-text-muted)',
                lineHeight: 1,
            }}>
                {String(mins).padStart(2, '0')}:{String(secs).padStart(2, '0')}
            </span>
        </div>
    );
}

interface ExplanationObj {
    concept: string;
    evidence: string;
    hint: string;
    french_translation?: { concept: string; evidence: string; hint: string };
}

function parseExplanation(raw: any): ExplanationObj | string | null {
    if (!raw) return null;
    if (typeof raw === 'object' && raw.concept !== undefined) return raw as ExplanationObj;
    if (typeof raw === 'string') {
        try {
            const parsed = JSON.parse(raw);
            if (parsed && parsed.concept !== undefined) return parsed as ExplanationObj;
        } catch {}
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

export default function SessionPlayer({ node, exercises, progress }: Props) {
    const { t, i18n } = useTranslation();
    const [currentExerciseIndex, setCurrentExerciseIndex] = useState(0);
    const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
    const [isReviewMode, setIsReviewMode] = useState(false);
    const [answers, setAnswers] = useState<Record<string, any>>({});
    const [isChecked, setIsChecked] = useState(false);
    const [isCorrect, setIsCorrect] = useState<boolean | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [transitioning, setTransitioning] = useState(false);
    const [visible, setVisible] = useState(true);
    const [timeSpent, setTimeSpent] = useState(0);
    const timeSpentRef = useRef(0);
    const [timerKey, setTimerKey] = useState(0);
    const [timerSeconds, setTimerSeconds] = useState(0);
    const [explanation, setExplanation] = useState<ExplanationObj | string | null>(null);
    const [fetchingExplanation, setFetchingExplanation] = useState(false);
    const [highlightedText, setHighlightedText] = useState<string | null>(null);
    const [playingTts, setPlayingTts] = useState<string | null>(null);
    const [isChatOpen, setIsChatOpen] = useState(false);
    const [mistakes, setMistakes] = useState<any[]>([]);
    // Frozen snapshot of the mistakes list when entering review mode, so the
    // review never grows while you retry (which used to loop forever).
    const [reviewQueue, setReviewQueue] = useState<any[]>([]);
    const [isVerifying, setIsVerifying] = useState(false);
    const [isDictionaryOpen, setIsDictionaryOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [isSearching, setIsSearching] = useState(false);
    const [searchResult, setSearchResult] = useState<any>(null);
    const [isVocabSaved, setIsVocabSaved] = useState(false);
    const contentRef = useRef<HTMLDivElement>(null);

    const exercise = exercises[currentExerciseIndex];
    const LANG_CODES: Record<string, string> = { 'English': 'en', 'French': 'fr', 'German': 'de' };
    const nodeCode = LANG_CODES[node.exam.language.name] ?? 'en';
    const componentKey = exercise?.exercise_type?.component_key ?? 'mcq';

    const TIME_PER_QUESTION = useMemo(() => {
        const writingTypes = ['essay-editor', 'short-writing', 'graph-description', 'academic-discussion', 'synthesis', 'integrated-task'];
        const speakingTypes = ['speaking-recorder', 'role-play'];
        const longTypes = ['gapped-text', 'insert-text', 'table-completion', 'flow-chart-completion', 'summary-completion', 'form-completion', 'diagram-labeling', 'multiple-matching'];
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
    const Component = componentMap[isReviewMode ? (question?.component_key ?? 'mcq') : (exercise?.exercise_type?.component_key ?? 'mcq')] ?? Mcq;

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
        (qid: string | undefined, exIdx = currentExerciseIndex) => `${exIdx}::${qid ?? ''}`,
        [currentExerciseIndex]
    );

    const handleAnswer = useCallback((questionId: string, answer: any) => {
        if (isChecked) return;
        setAnswers(prev => ({ ...prev, [answerKey(questionId)]: answer }));
    }, [isChecked, answerKey]);

    const playTts = useCallback(async (text: string, id: string) => {
        if (playingTts === id) return;
        setPlayingTts(id);
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
                const audio = new Audio(data.audio_url);
                audio.onended = () => setPlayingTts(null);
                audio.play();
            } else {
                setPlayingTts(null);
            }
        } catch (error) {
            console.error('TTS error:', error);
            setPlayingTts(null);
        }
    }, [nodeCode, playingTts]);

    const fetchExplanation = useCallback(async (questionObj = question) => {
        if (!questionObj) return;
        setFetchingExplanation(true);
        try {
            const res = await fetch(route('api.ai.explain'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    prompt: questionObj.prompt ?? questionObj.text ?? '',
                    user_answer: String(answers[answerKey(questionObj.id)] ?? ''),
                    correct_answer: String(questionObj.correct_answer ?? questionObj.correct ?? ''),
                    language: node.exam.language.name
                })
            });
            const data = await res.json();
            const parsedExpl = parseExplanation(data.explanation);
            setExplanation(parsedExpl);
            setHighlightedText(getEvidenceText(parsedExpl));
        } catch (error) {
            console.error('Failed to fetch explanation:', error);
        } finally {
            setFetchingExplanation(false);
        }
    }, [question, answers, answerKey, node.exam.language.name]);

    const handleRetry = useCallback(() => {
        setIsChecked(false);
        setIsCorrect(null);
        setExplanation(null);
        setHighlightedText(null);
        setAnswers(prev => {
            const next = { ...prev };
            if (question) delete next[question.id];
            return next;
        });
        setTimerSeconds(TIME_PER_QUESTION); // Reset timer 
    }, [question, TIME_PER_QUESTION]);

    const checkAnswer = useCallback(async () => {
        if (!question || isChecked || isVerifying) return;
        const currentAnswer = answers[answerKey(question.id)];
        if (currentAnswer === undefined) return;

        const aiTypes = ['essay-editor', 'speaking-recorder', 'role-play', 'short-writing', 'graph-description', 'academic-discussion', 'synthesis', 'integrated-task'];
        let isRight = false;
        let aiFeedback = null;

        if (aiTypes.includes(componentKey)) {
            setIsVerifying(true);
            try {
                const formData = new FormData();
                formData.append('exercise_id', String(exercise.id));
                formData.append('question_id', String(question.id));
                
                if (currentAnswer instanceof Blob) {
                    formData.append('answer', currentAnswer, 'recording.webm');
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

                if (data.transcription) {
                    setAnswers(prev => ({ ...prev, [`${question.id}_transcription`]: data.transcription }));
                }
            } catch (error) {
                console.error('Verification failed:', error);
                isRight = false;
            } finally {
                setIsVerifying(false);
            }
        } else {
            const correctAnswer = question.correct_answer ?? question.correct;
            if (Array.isArray(correctAnswer) && Array.isArray(currentAnswer)) {
                isRight = JSON.stringify(correctAnswer) === JSON.stringify(currentAnswer);
            } else if (typeof correctAnswer === 'object' && typeof currentAnswer === 'object') {
                isRight = JSON.stringify(correctAnswer) === JSON.stringify(currentAnswer);
            } else {
                isRight = String(currentAnswer).trim().toUpperCase() === String(correctAnswer).trim().toUpperCase();
            }
        }

        setIsCorrect(isRight);
        setIsChecked(true);
        
        if (!isRight && !isReviewMode) {
            if (!aiFeedback) fetchExplanation();
            // Add to mistakes queue if not already a retry. We never grow the queue
            // during review mode itself, otherwise the end condition recedes forever.
            const isAlreadyMistake = mistakes.some(m => m.id === question.id);
            if (!isAlreadyMistake) {
                setMistakes(prev => [...prev, { ...question, exercise_id: exercise.id, component_key: componentKey }]);
            }
        }
    }, [question, isChecked, answers, answerKey, componentKey, fetchExplanation, exercise.id, isVerifying, mistakes, isReviewMode]);

    const nextStep = useCallback(() => {
        setIsChecked(false);
        setIsCorrect(null);
        setExplanation(null);
        setHighlightedText(null);

        const isEndOfExercises = currentExerciseIndex === exercises.length - 1 && currentQuestionIndex === (isReviewMode ? reviewQueue.length - 1 : (exercise?.questions?.length ?? 0) - 1);

        if (isEndOfExercises) {
            if (!isReviewMode && mistakes.length > 0) {
                // Pedagogy: Start Review Mode for mistakes. Freeze the queue now so
                // retrying a wrong answer can't keep extending the session.
                setVisible(false);
                setTransitioning(true);
                setTimeout(() => {
                    setReviewQueue(mistakes);
                    setIsReviewMode(true);
                    setCurrentQuestionIndex(0);
                    setTransitioning(false);
                    setVisible(true);
                    setTimerKey(k => k + 1);
                }, 220);
                return;
            }

            // Strip the "exerciseIndex::" prefix so the backend gets the plain
            // { questionId: answer } map it expects for scoring.
            const submittedAnswers: Record<string, any> = {};
            for (const [k, v] of Object.entries(answers)) {
                const qid = k.includes('::') ? k.slice(k.indexOf('::') + 2) : k;
                submittedAnswers[qid] = v;
            }

            setSubmitting(true);
            router.post(route('exercise.submit_session', node.id), {
                answers: submittedAnswers,
                node_id: node.id,
                time_spent: timeSpentRef.current,
                exercise_ids: exercises.map((e: any) => e.id),
            }, {
                forceFormData: true,
            });
            return;
        }

        setVisible(false);
        setTransitioning(true);
        setTimeout(() => {
            if (currentQuestionIndex < (isReviewMode ? reviewQueue.length - 1 : questions.length - 1)) {
                setCurrentQuestionIndex(prev => prev + 1);
            } else if (!isReviewMode) {
                setCurrentExerciseIndex(prev => prev + 1);
                setCurrentQuestionIndex(0);
            }
            setTransitioning(false);
            setVisible(true);
            setTimerKey(k => k + 1);
        }, 220);
    }, [currentExerciseIndex, currentQuestionIndex, exercises, questions.length, answers, answerKey, node.id, mistakes, reviewQueue, isReviewMode, exercise]);

    const handleTimeUpdate = useCallback((elapsed: number) => {
        timeSpentRef.current += 1;
        setTimeSpent(timeSpentRef.current);
        setTimerSeconds(TIME_PER_QUESTION - elapsed);
    }, [TIME_PER_QUESTION]);

    const handleTimeExpire = useCallback(() => {
        if (!isChecked) {
            setIsChecked(true);
            setIsCorrect(false);
            setMistakes(prev => [...prev, { ...question, component_key: componentKey }]);
        }
        setTimeout(() => nextStep(), 1200);
    }, [isChecked, nextStep, question, componentKey]);

    // Keyboard shortcut: Space / Enter
    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement) return;
            if (e.code === 'Space' || e.code === 'Enter') {
                e.preventDefault();
                if (isChecked) nextStep();
                else if (answers[answerKey(question?.id)] !== undefined) checkAnswer();
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [isChecked, nextStep, checkAnswer, answers, answerKey, question]);

    const timerWarning = timerSeconds <= TIME_PER_QUESTION * 0.2 && timerSeconds > 0;
    const timerCritical = timerSeconds <= 10 && timerSeconds > 0;
    const timerExpired = timerSeconds === 0 && timerKey > 0;

    const isLastQuestion = currentExerciseIndex === exercises.length - 1 && currentQuestionIndex === questions.length - 1;
    const hasAnswer = answers[answerKey(question?.id)] !== undefined;

    if (!exercise || !question) {
        return (
            <AppLayout focusMode>
                <div className="flex flex-col items-center justify-center py-20">
                    <Icon name="alert-circle" size={48} />
                    <p className="mt-4 text-xl font-medium">Session non disponible</p>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout focusMode>
            <Head title={`${node.title} - PrePla`} />
            <style>{`
                @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@400;500;600;700&display=swap');

                :root {
                    --player-accent: #6366f1;
                    --player-accent-rgb: 99,102,241;
                    --player-accent-muted: rgba(99,102,241,0.3);
                    --player-dot-empty: rgba(var(--player-accent-rgb),0.12);
                    --player-text-muted: var(--muted-foreground, #64748b);
                }

                @keyframes slideInUp {
                    from { opacity: 0; transform: translateY(20px) scale(0.99); }
                    to   { opacity: 1; transform: translateY(0) scale(1); }
                }
                @keyframes slideOutUp {
                    from { opacity: 1; transform: translateY(0) scale(1); }
                    to   { opacity: 0; transform: translateY(-14px) scale(0.99); }
                }
                @keyframes feedbackSlideIn {
                    from { opacity: 0; transform: translateY(6px); }
                    to   { opacity: 1; transform: translateY(0); }
                }
                @keyframes timerPulse {
                    0%, 100% { transform: scale(1); }
                    50%       { transform: scale(1.06); }
                }
                @keyframes checkPop {
                    0%   { transform: scale(0.6); opacity: 0; }
                    60%  { transform: scale(1.15); opacity: 1; }
                    100% { transform: scale(1); }
                }

                .player-font { font-family: 'DM Sans', system-ui, sans-serif; }
                .question-enter { animation: slideInUp 0.26s cubic-bezier(.22,1,.36,1) forwards; }
                .question-exit  { animation: slideOutUp 0.18s cubic-bezier(.4,0,.2,1) forwards; }
                .feedback-enter { animation: feedbackSlideIn 0.22s cubic-bezier(.22,1,.36,1) forwards; }
                .check-pop      { animation: checkPop 0.32s cubic-bezier(.22,1,.36,1) forwards; }

                .player-card {
                    border-radius: 16px;
                    border: 1px solid rgba(var(--player-accent-rgb), 0.12);
                    background: var(--card, #fff);
                    box-shadow: 0 2px 16px rgba(0,0,0,0.05), 0 0 0 1px rgba(var(--player-accent-rgb),0.04);
                }

                .action-bar-correct {
                    background: linear-gradient(to right, rgba(16,185,129,0.06), rgba(16,185,129,0.02));
                    border-top-color: rgba(16,185,129,0.25) !important;
                }
                .action-bar-incorrect {
                    background: linear-gradient(to right, rgba(239,68,68,0.06), rgba(239,68,68,0.02));
                    border-top-color: rgba(239,68,68,0.22) !important;
                }

                .btn-check {
                    font-family: 'DM Sans', system-ui, sans-serif;
                    font-weight: 700;
                    font-size: 0.9375rem;
                    letter-spacing: 0.01em;
                    height: 48px;
                    min-width: 148px;
                    border-radius: 12px;
                    border: none;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 7px;
                    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
                    padding: 0 24px;
                }
                .btn-check:not(:disabled):hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
                .btn-check:not(:disabled):active { transform: translateY(0); }
                .btn-check:disabled { opacity: 0.42; cursor: not-allowed; }

                .btn-default  { background: var(--player-accent); color: #fff; box-shadow: 0 3px 12px rgba(99,102,241,0.35); }
                .btn-correct  { background: #10b981; color: #fff; box-shadow: 0 3px 12px rgba(16,185,129,0.35); }
                .btn-incorrect { background: #ef4444; color: #fff; box-shadow: 0 3px 12px rgba(239,68,68,0.3); }
                .btn-submitting { background: var(--player-accent); color: #fff; opacity: 0.7; }

                .passage-card {
                    border-radius: 14px;
                    border: 1px solid rgba(var(--player-accent-rgb), 0.1);
                    background: rgba(var(--player-accent-rgb), 0.025);
                    padding: 20px 24px;
                    font-size: 0.875rem;
                    line-height: 1.75;
                }

                .kbd-hint {
                    display: inline-flex; align-items: center; gap: 4px;
                    font-size: 11px; opacity: 0.45; font-family: monospace;
                    border: 1px solid currentColor; border-radius: 4px;
                    padding: 1px 5px; margin-left: 6px;
                }
            `}</style>

            <div className="player-font mx-auto max-w-2xl py-6 px-4" style={{ paddingBottom: '120px' }}>

                {/* ── Header ── */}
                <div style={{ marginBottom: 20 }}>
                    {/* Top row: title + timer */}
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 14 }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                            <div style={{
                                width: 36, height: 36,
                                borderRadius: 10,
                                background: 'rgba(var(--player-accent-rgb),0.1)',
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                flexShrink: 0,
                            }}>
                                <Icon name="sparkles" size={18} style={{ opacity: 0.8, }} />
                            </div>
                            <div>
                                <div style={{ fontWeight: 700, fontSize: '0.9375rem', lineHeight: 1.2 }}>{node.title}</div>
                                <div style={{ fontSize: '0.75rem', opacity: 0.5, marginTop: 2 }}>
                                    {node.exam.language.name} · {node.exam.name}
                                </div>
                            </div>
                        </div>

                        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                            {/* Quit session */}
                            <button
                                type="button"
                                onClick={() => {
                                    if (window.confirm(t('exercise.quit_confirm', 'Quitter la session ? Ta progression sur cette session ne sera pas enregistrée.'))) {
                                        router.visit(route('dashboard'));
                                    }
                                }}
                                title={t('exercise.quit', 'Quitter')}
                                aria-label={t('exercise.quit', 'Quitter')}
                                style={{
                                    width: 32, height: 32, borderRadius: 8,
                                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                                    background: 'rgba(0,0,0,0.04)', border: 'none', cursor: 'pointer',
                                    opacity: 0.6,
                                }}
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>

                            {/* Question counter */}
                            <div style={{
                                fontSize: '0.8125rem',
                                fontWeight: 600,
                                fontVariantNumeric: 'tabular-nums',
                                opacity: 0.55,
                            }}>
                                {isReviewMode ? (
                                    <span>{t('exercise.review', 'Révision')} {currentQuestionIndex + 1}/{reviewQueue.length}</span>
                                ) : (
                                    <>
                                        {Math.min(overallProgressCount + 1, totalQuestionsInSession)}
                                        <span style={{ opacity: 0.5, margin: '0 3px' }}>/</span>
                                        {totalQuestionsInSession}
                                    </>
                                )}
                            </div>

                            {/* Arc timer — hidden ExerciseTimer drives the state */}
                            <div style={{ display: 'none' }}>
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
                    </div>

                    {/* Segmented progress dots */}
                    <ProgressDots total={totalQuestionsInSession} current={overallProgressCount} />
                </div>

                {/* ── Exercise Content ── */}
                <div
                    ref={contentRef}
                    className={visible ? 'question-enter' : 'question-exit'}
                    style={{ display: 'flex', flexDirection: 'column', gap: 14 }}
                >
                    {exercise.content?.passage && (
                        <div className="passage-card relative overflow-hidden group">
                           <div className="flex items-center justify-between mb-3 border-b border-indigo-100/50 pb-2">
                                <span className="text-[10px] font-black uppercase tracking-widest text-indigo-400">Texte de référence</span>
                                <button 
                                    onClick={() => playTts(exercise.content.passage, 'passage')}
                                    className={`p-1.5 rounded-lg transition-all ${playingTts === 'passage' ? 'bg-indigo-100 text-indigo-600 scale-110' : 'hover:bg-indigo-50 text-slate-400 hover:text-indigo-500'}`}
                                >
                                    <Icon name={playingTts === 'passage' ? 'volume-2' : 'volume-1'} size={18} />
                                </button>
                            </div>
                            <HighlightedPassage text={exercise.content.passage} highlight={highlightedText || ''} />
                        </div>
                    )}

                    {/* Action instruction — tells the learner what to do (select / write / record…) */}
                    {INSTRUCTIONS[componentKey] && (
                        <div className="flex items-center gap-1.5 px-1" style={{ color: 'var(--player-accent)' }}>
                            <Icon name="info" size={14} style={{ opacity: 0.7 }} />
                            <span style={{ fontSize: '0.8125rem', fontWeight: 600 }}>
                                {INSTRUCTIONS[componentKey]}
                            </span>
                        </div>
                    )}

                    <div
                        className={`player-card group/card relative ${isChecked ? (isCorrect ? 'answer-pop' : 'answer-shake') : ''}`}
                        style={{ padding: '24px' }}
                    >
                        {/* Floating TTS button — the question text itself is rendered by each Component to avoid duplication */}
                        <button
                            onClick={() => playTts(question.text || question.prompt, 'question')}
                            className={`absolute top-3 right-3 p-1.5 rounded-lg transition-all z-10 ${playingTts === 'question' ? 'bg-indigo-100 text-indigo-600 scale-110' : 'opacity-50 hover:opacity-100 hover:bg-slate-100 text-slate-400'}`}
                            aria-label="Écouter la question"
                        >
                            <Icon name={playingTts === 'question' ? 'volume-2' : 'volume-1'} size={18} />
                        </button>
                        <Component
                            // Always-unique key so the input component fully remounts between
                            // questions (AI-generated questions can share/duplicate ids, which
                            // left the previous answer stuck in the text field).
                            key={`${isReviewMode ? 'r' : 'q'}-${currentExerciseIndex}-${currentQuestionIndex}-${question.id ?? ''}`}
                            question={{ ...exercise.content, ...question }}
                            lang={nodeCode}
                            onAnswer={(_childId: string, ans: any) => handleAnswer(question.id ?? String(currentQuestionIndex), ans)}
                            selectedAnswer={answers[answerKey(question.id ?? String(currentQuestionIndex))]}
                            disabled={isChecked}
                        />

                        {/* Speaking: show what the AI heard (the transcription) so the
                            learner can tell a real mistake from a mis-transcription. */}
                        {isChecked && answers[`${question.id}_transcription`] && (
                            <div className="mt-4 rounded-xl border-2 border-indigo-100 bg-indigo-50/60 p-4">
                                <p className="text-[10px] font-black uppercase tracking-widest text-indigo-400 mb-1.5">
                                    {t('exercise.heard', 'Ce que l’IA a entendu')}
                                </p>
                                <p className="text-sm font-medium italic text-slate-700">
                                    « {answers[`${question.id}_transcription`]} »
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* ── Explanation panel (above the bar) — gives long AI feedback room to
                breathe and scroll instead of being crushed into the action bar ── */}
            {isChecked && !isCorrect && (explanation || fetchingExplanation) && (
                <div className="fixed bottom-[76px] left-0 right-0 z-40 px-3">
                    <div className="player-font mx-auto max-h-[40vh] overflow-y-auto rounded-2xl border-2 border-red-200 bg-card p-4 shadow-xl" style={{ maxWidth: 672 }}>
                        {fetchingExplanation ? (
                            <p className="text-sm font-medium text-red-600/80 italic">Analyse de ton erreur…</p>
                        ) : explanation && typeof explanation === 'object' ? (
                            <div className="space-y-2">
                                <p className="text-sm font-bold text-red-700 dark:text-red-300 leading-relaxed">
                                    {(i18n.language === 'fr' && explanation.french_translation?.concept) ? explanation.french_translation.concept : explanation.concept}
                                </p>
                                {(i18n.language === 'fr' && explanation.french_translation?.hint ? explanation.french_translation.hint : explanation.hint) && (
                                    <p className="text-sm text-muted-foreground leading-relaxed">
                                        {i18n.language === 'fr' && explanation.french_translation?.hint ? explanation.french_translation.hint : explanation.hint}
                                    </p>
                                )}
                            </div>
                        ) : (
                            <FormattedFeedback
                                text={explanation as string}
                                className="text-sm font-medium text-foreground leading-relaxed space-y-1.5"
                            />
                        )}
                    </div>
                </div>
            )}

            {/* ── Bottom Action Bar ── */}
            <div
                className={`fixed bottom-0 left-0 right-0 transition-all duration-300 ${
                    isChecked
                        ? isCorrect ? 'action-bar-correct' : 'action-bar-incorrect'
                        : ''
                }`}
                style={{
                    borderTop: '1px solid rgba(0,0,0,0.07)',
                    background: isChecked ? undefined : 'var(--background, #fff)',
                    backdropFilter: 'blur(12px)',
                    WebkitBackdropFilter: 'blur(12px)',
                }}
            >
                <div
                    className="player-font mx-auto"
                    style={{
                        maxWidth: 672,
                        padding: '14px 16px 18px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: 16,
                    }}
                >
                    {/* Feedback panel */}
                    <div style={{ flex: 1, minHeight: 48, display: 'flex', alignItems: 'center' }}>
                        {isChecked && (
                            <div className="feedback-enter" style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                {isCorrect ? (
                                    // Celebratory GIF on a correct answer.
                                    <img
                                        src="/animation/Trophy.gif"
                                        alt=""
                                        width={48}
                                        height={48}
                                        className="check-pop"
                                        style={{ flexShrink: 0, objectFit: 'contain' }}
                                    />
                                ) : (
                                    <div
                                        className="check-pop"
                                        style={{
                                            width: 44, height: 44, borderRadius: '50%', flexShrink: 0,
                                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                                            background: '#ef4444',
                                        }}
                                    >
                                        <Icon
                                            name="x"
                                            size={22}
                                            style={{ filter: 'brightness(0) invert(1)' }}
                                        />
                                    </div>
                                )}
                                <div>
                                    <div style={{
                                        fontWeight: 700,
                                        fontSize: '1rem',
                                        color: isCorrect ? '#059669' : '#dc2626',
                                        lineHeight: 1.2,
                                    }}>
                                        {isCorrect ? t('exercise.correct') : t('exercise.incorrect')}
                                    </div>
                                    {!isCorrect && (
                                        <div className="mt-1 flex items-center gap-3">
                                            <span className="text-[12px] font-medium text-red-600/80">
                                                {fetchingExplanation ? 'Analyse…' : 'Vois l’explication ci-dessus'}
                                            </span>
                                            <button
                                                onClick={() => setIsChatOpen(true)}
                                                className="shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-100 text-red-700 hover:bg-red-200 transition-colors text-xs font-bold"
                                            >
                                                <Icon name="message-square" size={14} />
                                                Discuter
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                        
                        <AiChatFeedback
                            isOpen={isChatOpen}
                            onClose={() => setIsChatOpen(false)}
                            initialExplanation={explanation ? getExplanationText(explanation, i18n.language) : undefined}
                            context={{
                                prompt: question.text || question.prompt || '',
                                user_answer: String(answers[answerKey(question.id)] || ''),
                                correct_answer: String(question.correct_answer || question.correct || ''),
                                language: node.exam.language.name
                            }}
                        />

                        {!isChecked && (
                            <div className="flex items-center gap-4">
                                <div style={{ fontSize: '0.75rem', opacity: 0.3, userSelect: 'none' }}>
                                    <span className="kbd-hint">Space</span> {t('exercise.check', 'Check')}
                                </div>
                                <button 
                                    onClick={() => setIsDictionaryOpen(true)}
                                    className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors text-xs font-bold"
                                >
                                    <Icon name="search" size={14} />
                                    Dictionnaire
                                </button>
                            </div>
                        )}
                    </div>

                    {/* Dictionary Modal */}
                    {isDictionaryOpen && (
                        <div
                            className="fixed inset-0 z-[100] flex items-end justify-center sm:items-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm"
                            onClick={() => setIsDictionaryOpen(false)}
                        >
                            <div
                                onClick={(e) => e.stopPropagation()}
                                className="bg-white w-full max-w-md shadow-2xl overflow-hidden border border-slate-200 animate-in slide-in-from-bottom sm:zoom-in-95 duration-200
                                           rounded-t-2xl sm:rounded-2xl max-h-[85vh] sm:max-h-[80vh] flex flex-col"
                            >
                                <div className="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
                                    <h3 className="font-bold text-slate-800">Dictionnaire Rapide</h3>
                                    <button onClick={() => setIsDictionaryOpen(false)} className="p-1 hover:bg-slate-200 rounded-full transition-colors">
                                        <Icon name="x" size={18} />
                                    </button>
                                </div>
                                <div className="p-4 overflow-y-auto">
                                    <div className="relative">
                                        <input 
                                            autoFocus
                                            type="text" 
                                            placeholder="Rechercher un mot..." 
                                            className="w-full px-4 py-3 bg-slate-100 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all font-medium"
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            onKeyDown={async (e) => {
                                                if (e.key === 'Enter' && searchTerm.trim()) {
                                                    setIsSearching(true);
                                                    setSearchResult(null);
                                                    setIsVocabSaved(false);
                                                    try {
                                                        const res = await fetch(route('dictionary.lookup', { language: nodeCode, word: searchTerm }));
                                                        const data = await res.json();
                                                        if (data.word) setSearchResult(data);
                                                        else setSearchResult({ error: true });
                                                    } catch (err) {
                                                        setSearchResult({ error: true });
                                                    } finally {
                                                        setIsSearching(false);
                                                    }
                                                }
                                            }}
                                        />
                                    </div>
                                    <div className="mt-4 min-h-[120px]">
                                        {isSearching ? (
                                            <div className="flex flex-col items-center justify-center py-6 gap-3">
                                                <div className="w-6 h-6 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin" />
                                                <p className="text-xs text-slate-400 font-medium italic">Recherche intelligente en cours...</p>
                                            </div>
                                        ) : searchResult?.error ? (
                                            <div className="text-center py-6 text-slate-400 text-sm">Mot non trouvé. Essayez une autre orthographe.</div>
                                        ) : searchResult ? (
                                            <div className="space-y-4 animate-in fade-in slide-in-from-top-2 duration-300">
                                                <div className="p-4 bg-indigo-50/50 rounded-xl border border-indigo-100">
                                                    <div className="flex items-center justify-between mb-2">
                                                        <h4 className="font-bold text-indigo-900 text-lg">{searchResult.word}</h4>
                                                        <span className="text-[10px] font-black bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded uppercase">{searchResult.skill_level}</span>
                                                    </div>
                                                    <p className="text-sm text-slate-700 leading-relaxed italic mb-3">"{searchResult.definition}"</p>
                                                    <div className="text-xs text-slate-500 font-medium">Traduction : <span className="text-indigo-600">{searchResult.translation}</span></div>
                                                </div>
                                                <button 
                                                    disabled={isVocabSaved}
                                                    onClick={async () => {
                                                        try {
                                                            await router.post(route('vocabulary.store'), { dictionary_word_id: searchResult.id });
                                                            setIsVocabSaved(true);
                                                            setTimeout(() => {
                                                                setIsDictionaryOpen(false);
                                                                setSearchResult(null);
                                                                setSearchTerm('');
                                                                setIsVocabSaved(false);
                                                            }, 1500);
                                                        } catch (err) { console.error(err); }
                                                    }}
                                                    className={`w-full py-3 rounded-xl font-bold flex items-center justify-center gap-2 transition-all ${
                                                        isVocabSaved ? 'bg-emerald-500 text-white' : 'bg-indigo-600 text-white shadow-lg shadow-indigo-100'
                                                    }`}
                                                >
                                                    <Icon name={isVocabSaved ? 'check' : 'plus'} size={16} style={{ filter: 'brightness(0) invert(1)' }} />
                                                    {isVocabSaved ? 'Ajouté au Lexique !' : 'Ajouter à mon Lexique'}
                                                </button>
                                            </div>
                                        ) : (
                                            <div className="text-center py-6 text-slate-400 text-sm italic">
                                                Entrez un mot pour voir sa définition et l'ajouter à votre lexique.
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* CTA button */}
                    <button
                        className={`btn-check ${
                            submitting || isVerifying
                                ? 'btn-submitting'
                                : isChecked
                                    ? isCorrect ? 'btn-correct' : 'btn-incorrect'
                                    : 'btn-default'
                        }`}
                        disabled={!hasAnswer || submitting || isVerifying}
                        onClick={isChecked ? nextStep : checkAnswer}
                    >
                        {submitting || isVerifying ? (
                            <>
                                <span style={{ opacity: 0.8 }}>{isVerifying ? 'Vérification...' : t('exercise.finish', 'Finish')}</span>
                            </>
                        ) : isChecked ? (
                            <>
                                {isCorrect ? (isLastQuestion ? t('exercise.finish', 'Finish') : t('exercise.next', 'Next')) : 'Continuer'}
                                <Icon name="chevron-right" size={15} style={{ filter: 'brightness(0) invert(1)' }} />
                            </>
                        ) : (
                            <>
                                {t('exercise.check', 'Check')}
                                <Icon name="chevron-right" size={15} style={{ filter: 'brightness(0) invert(1)' }} />
                            </>
                        )}
                    </button>
                </div>
            </div>
        </AppLayout>
    );
}
