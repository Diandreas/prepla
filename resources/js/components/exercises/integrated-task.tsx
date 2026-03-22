import { useState } from 'react';
import { useTts } from '@/hooks/use-tts';

interface IntegratedTaskProps {
    question: {
        id: string;
        reading_passage?: { title: string; content: string };
        audio_text?: string;
        audio_url?: string;
        audio_lang?: string;
        writing_prompt?: string;
        speaking_prompt?: string;
        response_type: 'writing' | 'speaking';
        min_words?: number;
        max_words?: number;
        prep_time?: number;
        speak_time?: number;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

type Step = 'reading' | 'listening' | 'responding';

export function IntegratedTask({ question, onAnswer, selectedAnswer, disabled }: IntegratedTaskProps) {
    const hasReading = !!question.reading_passage;
    const hasAudio = !!(question.audio_text || question.audio_url);

    const initialStep: Step = hasReading ? 'reading' : hasAudio ? 'listening' : 'responding';
    const [step, setStep] = useState<Step>(selectedAnswer ? 'responding' : initialStep);
    const [value, setValue] = useState(selectedAnswer ?? '');
    const { speak, stop, isSpeaking, isSupported } = useTts();

    const minWords = question.min_words ?? 150;
    const maxWords = question.max_words ?? 225;
    const wordCount = value.trim() ? value.trim().split(/\s+/).length : 0;
    const inRange = wordCount >= minWords && wordCount <= maxWords;

    const handleChange = (text: string) => {
        setValue(text);
        onAnswer(question.id, text);
    };

    const goToListening = () => {
        if (hasAudio) {
            setStep('listening');
        } else {
            setStep('responding');
        }
    };

    const goToResponding = () => {
        stop();
        setStep('responding');
    };

    const handlePlayAudio = () => {
        if (question.audio_text && isSupported) {
            speak(question.audio_text, question.audio_lang ?? 'en');
        }
    };

    const steps: { key: Step; label: string }[] = [];
    if (hasReading) steps.push({ key: 'reading', label: 'Lecture' });
    if (hasAudio) steps.push({ key: 'listening', label: 'Écoute' });
    steps.push({ key: 'responding', label: question.response_type === 'speaking' ? 'Expression orale' : 'Rédaction' });

    const currentIdx = steps.findIndex((s) => s.key === step);

    return (
        <div className="space-y-4">
            {/* Step indicator */}
            <div className="flex items-center gap-2">
                {steps.map((s, i) => (
                    <div key={s.key} className="flex items-center gap-2">
                        {i > 0 && <div className="h-px w-6 bg-border" />}
                        <div
                            className={`flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold ${
                                i === currentIdx
                                    ? 'bg-primary text-primary-foreground'
                                    : i < currentIdx
                                      ? 'bg-green-100 text-green-700'
                                      : 'bg-muted text-muted-foreground'
                            }`}
                        >
                            <span>{i + 1}</span>
                            <span>{s.label}</span>
                        </div>
                    </div>
                ))}
            </div>

            {/* Reading step */}
            {step === 'reading' && question.reading_passage && (
                <div className="space-y-3">
                    <div className="rounded-xl border bg-muted/20 p-5">
                        <p className="mb-2 text-xs font-bold uppercase tracking-wider text-primary">
                            {question.reading_passage.title}
                        </p>
                        <p className="text-sm leading-relaxed whitespace-pre-line">
                            {question.reading_passage.content}
                        </p>
                    </div>
                    {!disabled && (
                        <div className="flex justify-end">
                            <button
                                onClick={goToListening}
                                className="rounded-xl bg-primary px-6 py-3 text-sm font-bold text-primary-foreground"
                            >
                                {hasAudio ? 'Passer à l\'écoute' : 'Passer à la rédaction'} →
                            </button>
                        </div>
                    )}
                </div>
            )}

            {/* Listening step */}
            {step === 'listening' && (
                <div className="space-y-3">
                    <div className="flex flex-col items-center gap-4 rounded-xl border bg-muted/20 p-8">
                        {question.audio_url ? (
                            <audio controls src={question.audio_url} className="w-full max-w-md" />
                        ) : question.audio_text && isSupported ? (
                            <>
                                <div
                                    className={`flex h-20 w-20 items-center justify-center rounded-full ${
                                        isSpeaking ? 'bg-primary animate-pulse' : 'bg-muted'
                                    }`}
                                >
                                    <svg className="h-8 w-8 text-primary-foreground" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3A4.5 4.5 0 0014 8.5v7a4.49 4.49 0 002.5-3.5zM14 3.23v2.06a6.51 6.51 0 010 13.42v2.06A8.5 8.5 0 0014 3.23z" />
                                    </svg>
                                </div>
                                <button
                                    onClick={isSpeaking ? stop : handlePlayAudio}
                                    className={`rounded-xl px-6 py-3 text-sm font-bold text-white ${
                                        isSpeaking ? 'bg-red-500' : 'bg-primary'
                                    }`}
                                >
                                    {isSpeaking ? 'Arrêter' : 'Écouter le passage'}
                                </button>
                                <p className="text-xs text-muted-foreground">
                                    Écoutez attentivement, vous ne pourrez écouter qu'une seule fois lors de l'examen.
                                </p>
                            </>
                        ) : (
                            <p className="text-sm text-muted-foreground">Audio non disponible</p>
                        )}
                    </div>
                    {!disabled && (
                        <div className="flex justify-end">
                            <button
                                onClick={goToResponding}
                                className="rounded-xl bg-primary px-6 py-3 text-sm font-bold text-primary-foreground"
                            >
                                Passer à la rédaction →
                            </button>
                        </div>
                    )}
                </div>
            )}

            {/* Responding step — writing */}
            {step === 'responding' && question.response_type === 'writing' && (
                <div className="space-y-3">
                    <div className="rounded-xl border-2 border-primary/20 bg-primary/5 p-4">
                        <p className="text-sm font-medium">{question.writing_prompt}</p>
                    </div>
                    <textarea
                        className="min-h-[200px] w-full rounded-lg border border-border bg-background p-4 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:opacity-50"
                        placeholder="Rédigez votre réponse..."
                        value={value}
                        onChange={(e) => handleChange(e.target.value)}
                        disabled={disabled}
                    />
                    <div className="flex justify-between text-sm">
                        <span
                            className={
                                inRange
                                    ? 'font-medium text-green-600'
                                    : wordCount > maxWords
                                      ? 'font-medium text-red-500'
                                      : 'text-muted-foreground'
                            }
                        >
                            {wordCount} mot{wordCount !== 1 ? 's' : ''}
                        </span>
                        <span className="text-muted-foreground">
                            {minWords}–{maxWords} mots
                        </span>
                    </div>
                </div>
            )}

            {/* Responding step — speaking (simplified, just shows prompt) */}
            {step === 'responding' && question.response_type === 'speaking' && (
                <div className="space-y-3">
                    <div className="rounded-xl border-2 border-primary/20 bg-primary/5 p-4">
                        <p className="text-sm font-medium">{question.speaking_prompt}</p>
                    </div>
                    <p className="text-center text-sm text-muted-foreground">
                        Utilisez le composant d'enregistrement oral pour cette partie.
                    </p>
                </div>
            )}
        </div>
    );
}
