import { useEffect, useState } from 'react';
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

    const [phase, setPhase] = useState<Phase>(selectedAnswer ? 'done' : 'prep');
    const [countdown, setCountdown] = useState(prepTime);
    const { isRecording, audioUrl, audioBlob, startRecording, stopRecording, clearRecording, error } = useAudioRecorder();
    const { speak, isSpeaking, stop } = useTts();

    // Stop TTS when leaving
    useEffect(() => {
        return () => stop();
    }, [stop]);

    // Countdown timer
    useEffect(() => {
        if (disabled || phase === 'done') return;

        const timer = setInterval(() => {
            setCountdown((prev) => {
                if (prev <= 1) {
                    clearInterval(timer);
                    if (phase === 'prep') {
                        setPhase('recording');
                        setCountdown(speakTime);
                        startRecording();
                    } else if (phase === 'recording') {
                        stopRecording();
                        setPhase('done');
                    }
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);

        return () => clearInterval(timer);
    }, [phase, disabled, prepTime, speakTime, startRecording, stopRecording]);

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
        await startRecording();
    };

    const handleStopEarly = () => {
        stopRecording();
        setPhase('done');
    };

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
                            {phase === 'prep' ? 'Préparation' : phase === 'recording' ? 'Enregistrement' : 'Terminé'}
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
                        className="rounded-xl bg-primary px-6 py-3 text-sm font-bold text-primary-foreground transition-all hover:opacity-90"
                    >
                        Commencer maintenant
                    </button>
                )}

                {phase === 'recording' && !disabled && (
                    <button
                        onClick={handleStopEarly}
                        className="rounded-xl bg-red-500 px-6 py-3 text-sm font-bold text-white transition-all hover:opacity-90"
                    >
                        Arrêter l'enregistrement
                    </button>
                )}

                {/* Playback */}
                {phase === 'done' && (audioUrl || selectedAnswer) && (() => {
                    // Si selectedAnswer est un Blob, on crée une URL locale pour la lecture
                    const src = audioUrl || (selectedAnswer instanceof Blob ? URL.createObjectURL(selectedAnswer) : selectedAnswer);
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
