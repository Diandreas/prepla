import { useEffect, useState } from 'react';
import { useAudioRecorder } from '@/hooks/use-audio-recorder';
import { useTts } from '@/hooks/use-tts';
import { SpeakButton } from './speak-button';

interface DialogueTurn {
    speaker: 'examiner' | 'candidate';
    text?: string;
    prompt?: string;
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

export function RolePlay({ question, onAnswer, selectedAnswer, disabled, lang = 'en' }: RolePlayProps) {
    const { speak, stop } = useTts();
    const [currentTurn, setCurrentTurn] = useState(0);
    const [recordings, setRecordings] = useState<string[]>([]);
    const { isRecording, audioUrl, startRecording, stopRecording, error } = useAudioRecorder();

    // Défensif : le générateur IA peut omettre dialogue_turns ou l'envoyer mal formé.
    const turns = Array.isArray(question.dialogue_turns) ? question.dialogue_turns : [];
    const candidateTurns = turns.filter((t) => t?.speaker === 'candidate');
    const allDone = selectedAnswer || recordings.length >= candidateTurns.length;
    const isMyTurn = !disabled && !allDone && turns[currentTurn]?.speaker === 'candidate';

    // Lit automatiquement la réplique de l'examinateur quand on arrive à son tour.
    useEffect(() => {
        if (disabled || allDone) return;
        const turn = turns[currentTurn];
        if (turn?.speaker === 'examiner' && turn.text) {
            speak(turn.text, lang);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [currentTurn]);

    // Stoppe le TTS en quittant le composant.
    useEffect(() => () => stop(), [stop]);

    const handleRecord = async () => {
        if (isRecording) {
            stopRecording();
        } else {
            await startRecording();
        }
    };

    const handleNextTurn = () => {
        stop(); // coupe l'audio de l'examinateur en cours
        // N'enregistre l'audio que si le tour courant est une réplique du candidat.
        if (turns[currentTurn]?.speaker === 'candidate' && audioUrl) {
            setRecordings([...recordings, audioUrl]);
        }

        const nextTurnIdx = currentTurn + 1;
        if (nextTurnIdx >= turns.length) {
            // All turns done
            onAnswer(question.id, 'completed');
        } else {
            setCurrentTurn(nextTurnIdx);
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

            {/* Whose turn is it — makes it obvious WHEN the user should speak. */}
            {turns.length > 0 && !allDone && !disabled && (
                isMyTurn ? (
                    <div className="flex items-center gap-2 rounded-xl border-2 border-emerald-300 bg-emerald-50 px-4 py-2.5 animate-pulse">
                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2M12 19v4"/></svg>
                        </span>
                        <p className="text-sm font-black text-emerald-700">À vous de parler — appuyez sur « Enregistrer »</p>
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

            {error && (
                <div className="rounded-lg bg-red-50 p-3 text-sm text-red-600">{error}</div>
            )}

            {turns.length === 0 && (
                <div className="rounded-xl border border-border bg-muted/50 px-6 py-4 text-sm text-muted-foreground">
                    Dialogue non disponible pour cet exercice.
                </div>
            )}

            {/* Dialogue */}
            <div className="space-y-3">
                {turns.map((turn, i) => {
                    const isPast = i < currentTurn;
                    const isCurrent = i === currentTurn;
                    const isFuture = i > currentTurn;
                    const isExaminer = turn.speaker === 'examiner';

                    if (isFuture && !allDone) return null;

                    return (
                        <div
                            key={i}
                            className={`flex ${isExaminer ? 'justify-start' : 'justify-end'} ${isFuture ? 'opacity-50' : ''}`}
                        >
                            <div
                                className={`max-w-[80%] rounded-2xl p-3 ${
                                    isExaminer
                                        ? 'bg-muted/50 rounded-tl-sm'
                                        : 'bg-primary/10 rounded-tr-sm'
                                }`}
                            >
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
                                            <button
                                                onClick={handleNextTurn}
                                                className="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-primary-foreground"
                                            >
                                                Continuer
                                            </button>
                                        )}
                                    </div>
                                ) : isPast || allDone ? (
                                    <div className="space-y-2">
                                        {turn.prompt && <p className="text-xs italic text-muted-foreground">{turn.prompt}</p>}
                                        {recordings[turns.filter((t, idx) => t.speaker === 'candidate' && idx <= i).length - 1] && (
                                            <audio
                                                controls
                                                src={recordings[turns.filter((t, idx) => t.speaker === 'candidate' && idx <= i).length - 1]}
                                                className="h-8 w-full"
                                            />
                                        )}
                                    </div>
                                ) : isCurrent && !disabled ? (
                                    <div className="space-y-2">
                                        {turn.prompt && <p className="text-xs italic text-muted-foreground">{turn.prompt}</p>}
                                        <div className="flex items-center gap-2">
                                            <button
                                                onClick={handleRecord}
                                                className={`flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold text-white transition-all ${
                                                    isRecording ? 'bg-red-500 animate-pulse' : 'bg-primary'
                                                }`}
                                            >
                                                <div className={`h-3 w-3 rounded-full ${isRecording ? 'bg-white' : 'bg-white/60'}`} />
                                                {isRecording ? 'Stop' : 'Enregistrer'}
                                            </button>
                                            {audioUrl && !isRecording && (
                                                <button
                                                    onClick={handleNextTurn}
                                                    className="rounded-xl border border-primary bg-background px-4 py-2 text-sm font-bold text-primary"
                                                >
                                                    Suivant
                                                </button>
                                            )}
                                        </div>
                                        {audioUrl && !isRecording && (
                                            <audio controls src={audioUrl} className="h-8 w-full" />
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-xs text-muted-foreground">En attente...</p>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
