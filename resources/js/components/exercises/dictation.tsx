import { useState } from 'react';

interface DictationProps {
    question: { id: string; audio_url: string; text?: string; hint?: string };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
}

export function Dictation({ question, onAnswer, selectedAnswer }: DictationProps) {
    const [value, setValue] = useState(selectedAnswer ?? '');
    const [playing, setPlaying] = useState(false);
    const [playCount, setPlayCount] = useState(0);

    const playAudio = () => {
        if (playing) return;
        setPlaying(true);
        const audio = new Audio(question.audio_url);
        audio.onended = () => { setPlaying(false); setPlayCount((c) => c + 1); };
        audio.onerror = () => setPlaying(false);
        audio.play();
    };

    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">Listen and write what you hear</p>
            {question.hint && (
                <p className="text-sm text-muted-foreground">{question.hint}</p>
            )}

            <button
                onClick={playAudio}
                disabled={playing}
                className="flex items-center gap-3 rounded-xl border-2 border-primary bg-primary/5 px-6 py-4 text-primary transition hover:bg-primary/10 disabled:opacity-50"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" className="h-6 w-6">
                    <path d="M6.3 2.84A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.27l9.344-5.891a1.5 1.5 0 000-2.538L6.3 2.84z" />
                </svg>
                <span className="font-medium">
                    {playing ? 'Playing...' : playCount === 0 ? 'Play audio' : `Play again (${playCount})`}
                </span>
            </button>

            <textarea
                value={value}
                onChange={(e) => setValue(e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                rows={4}
                placeholder="Type what you hear..."
                disabled={!!selectedAnswer}
            />

            {!selectedAnswer && (
                <button
                    onClick={() => value.trim() && onAnswer(question.id, value.trim())}
                    disabled={!value.trim()}
                    className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground disabled:opacity-50"
                >
                    Submit
                </button>
            )}
        </div>
    );
}
