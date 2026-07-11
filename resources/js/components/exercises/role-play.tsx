import { useEffect, useState } from 'react';
import { useAudioRecorder } from '@/hooks/use-audio-recorder';
import { useTts } from '@/hooks/use-tts';
import { prefetchTts } from '@/lib/tts-cache';
import { SpeakButton } from './speak-button';

// Read the freshest CSRF token (cookie tracks the live session).
function csrfToken(): string {
    const cookie = document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='));
    if (cookie) return decodeURIComponent(cookie.split('=')[1]);
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
}

interface DialogueTurn {
    speaker: 'examiner' | 'candidate';
    text?: string;
    prompt?: string;
}

interface TurnResult {
    accuracy: number;
    transcription: string;
    covered: string[];
    missing: string[];
    explanation: string;
}

interface RolePlayProps {
    question: {
        id: string;
        scenario?: string;
        role?: string;
        dialogue_turns?: DialogueTurn[];
        prep_time?: number;
        speak_time_per_turn?: number;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
    lang?: string;
}

function asText(v: any): string {
    if (v == null) return '';
    if (typeof v === 'string') return v;
    if (typeof v === 'object') return String(v.concept ?? v.text ?? v.hint ?? '');
    return String(v);
}

export function RolePlay({ question, onAnswer, selectedAnswer, disabled, lang = 'en' }: RolePlayProps) {
    const { speak, stop } = useTts();
    const [currentTurn, setCurrentTurn] = useState(0);
    const { isRecording, audioUrl, audioBlob, startRecording, stopRecording, clearRecording, error } = useAudioRecorder();
    // Per-turn live correction results, keyed by turn index.
    const [turnResults, setTurnResults] = useState<Record<number, TurnResult>>({});
    const [evaluatingTurn, setEvaluatingTurn] = useState<number | null>(null);

    const rawTurns = Array.isArray(question.dialogue_turns) ? question.dialogue_turns : [];
    const isExaminerTurn = (t?: DialogueTurn): boolean => {
        if (!t) return false;
        const s = (t.speaker ?? '').toLowerCase();
        if (['examiner', 'interviewer', 'teacher', 'examinateur', 'prof', 'professeur', 'agent', 'system'].some(k => s.includes(k))) return true;
        if (['candidate', 'student', 'you', 'user', 'me', 'candidat', 'élève', 'eleve', 'client'].some(k => s.includes(k))) return false;
        return !!(t.text && t.text.trim());
    };
    // If the AI emitted every turn as "examiner" with the candidate reply hidden in
    // `prompt`, expand each into [examiner, candidate(prompt)] so the user speaks.
    const hasRealCandidate = rawTurns.some((t) => !isExaminerTurn(t));
    const turns: DialogueTurn[] = (() => {
        if (hasRealCandidate) return rawTurns;
        const expanded: DialogueTurn[] = [];
        for (const t of rawTurns) {
            expanded.push(t);
            if (t?.prompt && t.prompt.trim()) expanded.push({ speaker: 'candidate', prompt: t.prompt });
        }
        return expanded.length ? expanded : rawTurns;
    })();

    const candidateTurns = turns.filter((t) => !isExaminerTurn(t));
    const answeredCount = Object.keys(turnResults).length;
    const allDone = !!selectedAnswer || (candidateTurns.length > 0 && answeredCount >= candidateTurns.length);
    const isMyTurn = !disabled && !allDone && !isExaminerTurn(turns[currentTurn]);
    const currentDone = turnResults[currentTurn] !== undefined;

    // Prefetch every examiner line at mount so each turn's audio plays instantly
    // (deduped with the session player's own prefetch — cache hits are free).
    useEffect(() => {
        const lines = rawTurns
            .filter((t) => isExaminerTurn(t) && t?.text)
            .map((t) => ({ text: t.text, lang }));
        if (lines.length) void prefetchTts(lines);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // Auto-play the examiner line when we reach their turn.
    useEffect(() => {
        if (disabled || allDone) return;
        const turn = turns[currentTurn];
        if (isExaminerTurn(turn) && turn?.text) speak(turn.text, lang);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [currentTurn]);

    useEffect(() => () => stop(), [stop]);

    const handleRecord = async () => {
        if (isRecording) stopRecording();
        else await startRecording();
    };

    // Evaluate the current candidate turn LIVE: send the audio blob + turn prompt.
    const submitTurn = async () => {
        if (!audioBlob || evaluatingTurn !== null) return;
        setEvaluatingTurn(currentTurn);
        try {
            const fd = new FormData();
            fd.append('audio', audioBlob, `turn-${currentTurn}.webm`);
            fd.append('prompt', turns[currentTurn]?.prompt ?? '');
            fd.append('lang', lang);
            const res = await fetch(route('api.exercise.evaluate-turn'), {
                method: 'POST',
                headers: { 'X-XSRF-TOKEN': csrfToken() },
                body: fd,
            });
            const data = await res.json();
            setTurnResults(prev => ({
                ...prev,
                [currentTurn]: {
                    accuracy: data.accuracy ?? 0,
                    transcription: data.transcription ?? '',
                    covered: data.covered_points ?? [],
                    missing: data.missing_points ?? [],
                    explanation: asText(data.explanation),
                },
            }));
        } catch {
            setTurnResults(prev => ({
                ...prev,
                [currentTurn]: { accuracy: 0, transcription: '', covered: [], missing: [], explanation: "Erreur d'analyse, réessaie." },
            }));
        } finally {
            setEvaluatingTurn(null);
        }
    };

    const goNext = () => {
        stop();
        clearRecording();
        const nextIdx = currentTurn + 1;
        if (nextIdx >= turns.length) {
            // Synthèse : moyenne des accuracy → verdict non vide pour la soumission.
            const vals = Object.values(turnResults);
            const avg = vals.length ? Math.round(vals.reduce((a, r) => a + r.accuracy, 0) / vals.length) : 0;
            onAnswer(question.id, `completed:${avg}`);
        } else {
            setCurrentTurn(nextIdx);
        }
    };

    return (
        <div className="space-y-4">
            {/* Scenario */}
            <div className="rounded-xl border-2 border-primary/20 bg-primary/5 p-4">
                <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-primary">Scénario</p>
                <p className="text-sm">{question.scenario}</p>
                <p className="mt-2 text-sm font-medium italic text-primary">Votre rôle : {question.role}</p>
            </div>

            {/* Whose turn */}
            {turns.length > 0 && !allDone && !disabled && (
                isMyTurn ? (
                    <div className="flex items-center gap-2 rounded-xl border-2 border-emerald-300 bg-emerald-50 px-4 py-2.5">
                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2M12 19v4"/></svg>
                        </span>
                        <p className="text-sm font-black text-emerald-700">À vous de parler</p>
                    </div>
                ) : (
                    <div className="flex items-center gap-2 rounded-xl border border-border bg-muted/50 px-4 py-2.5">
                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
                        </span>
                        <p className="text-sm font-bold text-muted-foreground">L'examinateur parle — écoutez puis « Continuer »</p>
                    </div>
                )
            )}

            {error && <div className="rounded-lg bg-red-50 p-3 text-sm text-red-600">{error}</div>}
            {turns.length === 0 && (
                <div className="space-y-3 rounded-xl border border-border bg-muted/50 px-6 py-4 text-sm text-muted-foreground">
                    <p>Dialogue non disponible pour cet exercice.</p>
                    {!disabled && !selectedAnswer && (
                        <button
                            onClick={() => onAnswer(question.id, '__no_dialogue__')}
                            className="rounded-lg bg-primary px-4 py-2 text-xs font-bold text-primary-foreground"
                        >
                            Passer cette question
                        </button>
                    )}
                </div>
            )}

            {/* Dialogue */}
            <div className="space-y-3">
                {turns.map((turn, i) => {
                    const isPast = i < currentTurn;
                    const isCurrent = i === currentTurn;
                    const isFuture = i > currentTurn;
                    const isExaminer = isExaminerTurn(turn);
                    const result = turnResults[i];

                    if (isFuture && !allDone) return null;

                    return (
                        <div key={i} className={`flex ${isExaminer ? 'justify-start' : 'justify-end'}`}>
                            <div className={`max-w-[85%] rounded-2xl p-3 ${isExaminer ? 'bg-muted/50 rounded-tl-sm' : 'bg-primary/10 rounded-tr-sm'}`}>
                                <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                                    {isExaminer ? 'Examinateur' : 'Vous'}
                                </p>

                                {isExaminer ? (
                                    <div className="space-y-2">
                                        <div className="flex items-start gap-2">
                                            <p className="flex-1 text-sm">{turn.text}</p>
                                            {turn.text && <SpeakButton text={turn.text} lang={lang} compact />}
                                        </div>
                                        {isCurrent && !disabled && !allDone && (
                                            <button onClick={goNext} className="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-primary-foreground">
                                                Continuer
                                            </button>
                                        )}
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        {turn.prompt && <p className="text-xs italic text-muted-foreground">{turn.prompt}</p>}

                                        {/* Recording controls — only on the current unanswered candidate turn */}
                                        {isCurrent && !disabled && !currentDone && evaluatingTurn !== i && (
                                            <div className="flex flex-wrap items-center gap-2">
                                                <button
                                                    onClick={handleRecord}
                                                    className={`flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold text-white transition-all ${isRecording ? 'bg-red-500 animate-pulse' : 'bg-primary'}`}
                                                >
                                                    <div className={`h-3 w-3 rounded-full ${isRecording ? 'bg-white' : 'bg-white/60'}`} />
                                                    {isRecording ? 'Stop' : 'Enregistrer'}
                                                </button>
                                                {audioUrl && !isRecording && (
                                                    <button onClick={submitTurn} className="rounded-xl bg-emerald-500 px-4 py-2 text-sm font-bold text-white">
                                                        Valider ma réponse
                                                    </button>
                                                )}
                                            </div>
                                        )}
                                        {isCurrent && audioUrl && !isRecording && !currentDone && evaluatingTurn !== i && (
                                            <audio controls src={audioUrl} className="h-8 w-full" />
                                        )}

                                        {/* Mini-loader during evaluation */}
                                        {evaluatingTurn === i && (
                                            <div className="flex items-center gap-2 text-xs font-bold text-primary">
                                                <img src="/animation/loading.gif" alt="" width={20} height={20} />
                                                Analyse de ta réponse…
                                            </div>
                                        )}

                                        {/* Per-turn correction */}
                                        {result && (
                                            <div className="space-y-2 rounded-xl border border-border bg-background p-2.5">
                                                {result.transcription
                                                    ? <p className="text-xs italic text-slate-600">« {result.transcription} »</p>
                                                    : <p className="text-xs italic text-amber-600">Aucun son détecté.</p>}
                                                <div className="flex items-center gap-2">
                                                    <span className={`rounded-full px-2 py-0.5 text-[10px] font-black ${result.accuracy >= 50 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}`}>
                                                        {result.accuracy}%
                                                    </span>
                                                    {result.accuracy >= 50 && <span className="text-[10px] font-bold text-emerald-600">Validé</span>}
                                                </div>
                                                {result.covered.length > 0 && (
                                                    <ul className="space-y-0.5">
                                                        {result.covered.map((p, k) => <li key={k} className="text-[11px] text-emerald-700">✓ {p}</li>)}
                                                    </ul>
                                                )}
                                                {result.missing.length > 0 && (
                                                    <ul className="space-y-0.5">
                                                        {result.missing.map((p, k) => <li key={k} className="text-[11px] text-amber-700">+ {p}</li>)}
                                                    </ul>
                                                )}
                                                {result.explanation && <p className="text-[11px] text-slate-600">{result.explanation}</p>}
                                                {isCurrent && !disabled && (
                                                    <button onClick={goNext} className="mt-1 rounded-xl bg-primary px-4 py-2 text-sm font-bold text-primary-foreground">
                                                        Continuer
                                                    </button>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>

            {allDone && (
                <div className="rounded-xl border-2 border-emerald-200 bg-emerald-50 p-4 text-center">
                    <p className="text-sm font-black text-emerald-700">Dialogue terminé !</p>
                    <p className="text-xs text-emerald-600">
                        Score moyen : {Object.values(turnResults).length
                            ? Math.round(Object.values(turnResults).reduce((a, r) => a + r.accuracy, 0) / Object.values(turnResults).length)
                            : 0}%
                    </p>
                </div>
            )}
        </div>
    );
}
