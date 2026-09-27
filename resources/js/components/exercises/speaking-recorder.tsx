import { useCallback, useEffect, useRef, useState } from 'react';
import { useAudioRecorder } from '@/hooks/use-audio-recorder';
import { useTts } from '@/hooks/use-tts';

interface SpeakingRecorderProps {
    question: {
        id: string;
        text: string;
        prep_time?: number;
        speak_time?: number;
        image_url?: string;
    };
    onAnswer: (questionId: string, answer: string | Blob) => void;
    selectedAnswer?: string | Blob;
    disabled?: boolean;
    lang?: string;
}

type Phase = 'prep' | 'recording' | 'done';

export function SpeakingRecorder({ question, onAnswer, selectedAnswer, disabled, lang = 'en' }: SpeakingRecorderProps) {
    const prepTime = question.prep_time ?? 30;
    const speakTime = question.speak_time ?? 60;

    // Some exam tasks legitimately configure prep_time: 0 (immediate response, no
    // separate prep phase). Starting in 'prep' with a 0:00 countdown would flash
    // that state for one tick before auto-transitioning, which reads as broken/
    // inconsistent next to questions that do have a visible prep countdown. Skip
    // straight to 'recording' when there's no prep time to give a consistent UX.
    const [phase, setPhase] = useState<Phase>(selectedAnswer ? 'done' : (prepTime > 0 ? 'prep' : 'recording'));
    const [countdown, setCountdown] = useState(prepTime > 0 ? prepTime : speakTime);
    const { isRecording, audioUrl, audioBlob, startRecording, stopRecording, clearRecording, error } = useAudioRecorder();
    const { speak, isSpeaking, stop } = useTts();

    // Stop TTS when leaving
    useEffect(() => {
        return () => stop();
    }, [stop]);

    // The button and the automatic start can fire for the same phase; one request
    // for the microphone at a time, or the browser prompts twice.
    const startingRef = useRef(false);
    const beginRecording = useCallback(async () => {
        if (startingRef.current) return;
        startingRef.current = true;
        try {
            await startRecording();
        } finally {
            startingRef.current = false;
        }
    }, [startRecording]);

    // Entering the speaking phase asks for the microphone. A browser can refuse —
    // permission denied, or no user gesture behind the request on mobile, which is
    // exactly what happens when the preparation countdown reaches zero on its own.
    // The attempt is made here, and the clock below waits for it to succeed.
    useEffect(() => {
        if (disabled || selectedAnswer || phase !== 'recording' || isRecording || audioBlob) return;
        void beginRecording();
        // Re-running on `error` would loop on a refused microphone: the learner
        // restarts it with the button instead.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [phase]);

    // Countdown. The speaking clock only runs while the microphone is actually
    // recording: it used to run regardless, so a refused microphone burned the whole
    // answer time and dropped the learner into a finished state with nothing said,
    // no recording, and no button to start one.
    useEffect(() => {
        if (disabled || phase === 'done') return;
        if (phase === 'recording' && !isRecording) return;

        const timer = setInterval(() => {
            setCountdown((prev) => (prev <= 1 ? 0 : prev - 1));
        }, 1000);

        return () => clearInterval(timer);
    }, [phase, disabled, isRecording]);

    // Phase transitions, kept out of the tick so React never runs them twice.
    useEffect(() => {
        if (disabled || countdown > 0) return;
        if (phase === 'prep') {
            setPhase('recording');
            setCountdown(speakTime);
        } else if (phase === 'recording' && isRecording) {
            stopRecording();
            setPhase('done');
        }
    }, [countdown, phase, disabled, isRecording, speakTime, stopRecording]);

    // When recording done, submit the audio Blob
    useEffect(() => {
        if (audioBlob && phase === 'done' && audioBlob !== selectedAnswer) {
            // Naming the blob helps Laravel treat it as a file with extension
            const file = new File([audioBlob], `recording-${question.id}.webm`, { type: 'audio/webm' });
            onAnswer(question.id, file);
        }
    }, [audioBlob, phase, question.id, onAnswer, selectedAnswer]);

    const handleStartEarly = async () => {
        setPhase('recording');
        setCountdown(speakTime);
        await beginRecording();
    };

    const handleStopEarly = () => {
        stopRecording();
        setPhase('done');
    };

    // Second chance after a microphone that never started, or a finished phase with
    // nothing captured: the answer time is given back in full.
    const handleRetry = async () => {
        clearRecording();
        setPhase('recording');
        setCountdown(speakTime);
        await beginRecording();
    };

    // In the speaking phase with no microphone running: the learner is waiting on a
    // button, not on the clock.
    const micStalled = phase === 'recording' && !isRecording && !audioBlob;
    const finishedEmpty = phase === 'done' && !audioUrl && !selectedAnswer;

    const formatTime = (s: number) => `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;

    const isWarning = phase === 'recording' && countdown <= 10;

    return (
        <div className="space-y-5">
            {/* Prompt */}
            <div className="relative rounded-xl border bg-muted/30 p-4">
                <p className="text-sm font-medium leading-relaxed pr-10">{question.text}</p>
                <button
                    onClick={() => isSpeaking ? stop() : speak(question.text, lang)}
                    className="absolute right-3 top-3 rounded-full bg-white p-2 text-primary shadow hover:bg-gray-50 focus:outline-none"
                    title="Écouter avec Deepgram"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill={isSpeaking ? "currentColor" : "none"} stroke="currentColor" strokeWidth="2">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                    </svg>
                </button>
            </div>

            {question.image_url && (
                <img src={question.image_url} alt="" className="mx-auto max-h-48 rounded-xl object-contain" />
            )}

            {error && (
                <div className="rounded-lg bg-red-50 p-3 text-sm text-red-600">{error}</div>
            )}

            {/* Phase UI */}
            <div className="flex flex-col items-center gap-4 py-4">
                {/* Countdown circle */}
                <div className="relative flex h-32 w-32 items-center justify-center">
                    <svg viewBox="0 0 100 100" className="absolute inset-0 h-full w-full -rotate-90">
                        <circle cx="50" cy="50" r="44" fill="none" stroke="currentColor" className="text-muted" strokeWidth="6" />
                        <circle
                            cx="50" cy="50" r="44" fill="none"
                            strokeWidth="6"
                            strokeLinecap="round"
                            className={phase === 'prep' ? 'text-primary' : isWarning ? 'text-red-500' : 'text-green-500'}
                            stroke="currentColor"
                            strokeDasharray={`${2 * Math.PI * 44}`}
                            strokeDashoffset={`${2 * Math.PI * 44 * (1 - countdown / (phase === 'prep' ? prepTime : speakTime))}`}
                            style={{ transition: 'stroke-dashoffset 1s linear' }}
                        />
                    </svg>
                    <div className="text-center">
                        <p className={`text-2xl font-black tabular-nums ${isWarning ? 'text-red-500 animate-pulse' : ''}`}>
                            {formatTime(countdown)}
                        </p>
                        <p className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                            {phase === 'prep' ? 'Préparation' : micStalled ? 'En attente' : phase === 'recording' ? 'Enregistrement' : 'Terminé'}
                        </p>
                    </div>
                </div>

                {/* Microphone indicator */}
                {phase === 'recording' && isRecording && (
                    <div className="flex items-center gap-2">
                        <div className="h-3 w-3 animate-pulse rounded-full bg-red-500" />
                        <span className="text-sm font-medium text-red-500">Enregistrement en cours</span>
                    </div>
                )}

                {/* Action buttons */}
                {phase === 'prep' && !disabled && (
                    <button
                        onClick={handleStartEarly}
                        className="duo-press rounded-xl bg-primary px-6 py-3 text-sm font-bold text-primary-foreground"
                        style={{ boxShadow: '0 4px 0 0 rgba(0,0,0,0.25)' }}
                    >
                        Commencer maintenant
                    </button>
                )}

                {/* Microphone not running: the clock is on hold and it takes a tap to
                    start it — a browser only grants the microphone on a real gesture. */}
                {micStalled && !disabled && (
                    <div className="flex flex-col items-center gap-2">
                        <button
                            onClick={handleStartEarly}
                            className="duo-press rounded-xl bg-primary px-6 py-3 text-sm font-bold text-primary-foreground"
                            style={{ boxShadow: '0 4px 0 0 rgba(0,0,0,0.25)' }}
                        >
                            Commencer l'enregistrement
                        </button>
                        <p className="max-w-xs text-center text-xs text-muted-foreground">
                            Le temps de parole ne démarre qu'une fois le micro actif : tu ne perds rien à attendre.
                        </p>
                    </div>
                )}

                {phase === 'recording' && isRecording && !disabled && (
                    <button
                        onClick={handleStopEarly}
                        className="duo-press rounded-xl bg-red-500 px-6 py-3 text-sm font-bold text-white"
                        style={{ boxShadow: '0 4px 0 0 #b91c1c' }}
                    >
                        Arrêter l'enregistrement
                    </button>
                )}

                {/* Finished with nothing captured: this used to be a dead end — a 0:00
                    timer, no audio player, and no way back. */}
                {finishedEmpty && !disabled && (
                    <div className="flex flex-col items-center gap-2">
                        <p className="max-w-xs text-center text-sm font-medium text-muted-foreground">
                            Rien n'a été enregistré. Reprends le temps de parole depuis le début.
                        </p>
                        <button
                            onClick={handleRetry}
                            className="duo-press rounded-xl bg-primary px-6 py-3 text-sm font-bold text-primary-foreground"
                            style={{ boxShadow: '0 4px 0 0 rgba(0,0,0,0.25)' }}
                        >
                            Réessayer
                        </button>
                    </div>
                )}

                {/* Playback */}
                {phase === 'done' && (audioUrl || selectedAnswer) && (() => {
                    // Si selectedAnswer est un Blob, on crée une URL locale pour la lecture.
                    // Défensif : createObjectURL peut lever si l'objet n'est pas un Blob
                    // valide → on évite ainsi la page blanche après l'envoi du vocal.
                    let src: string | undefined = audioUrl ?? undefined;
                    if (!src) {
                        try {
                            src = selectedAnswer instanceof Blob
                                ? URL.createObjectURL(selectedAnswer)
                                : (typeof selectedAnswer === 'string' ? selectedAnswer : undefined);
                        } catch {
                            src = undefined;
                        }
                    }
                    if (!src) return null;
                    return (
                        <div className="flex w-full max-w-sm flex-col items-center gap-3">
                            <audio controls src={src as string} className="w-full rounded-lg" />
                            {!disabled && (
                                <button
                                    onClick={() => {
                                        clearRecording();
                                        setPhase('prep');
                                        setCountdown(prepTime);
                                        onAnswer(question.id, '');
                                    }}
                                    className="text-sm font-bold text-muted-foreground underline hover:text-foreground"
                                >
                                    Refaire l'enregistrement
                                </button>
                            )}
                        </div>
                    );
                })()}
            </div>
        </div>
    );
}
