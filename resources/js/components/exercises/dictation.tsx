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

    const hasAudio = !!question.audio_url;

    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">Écoutez et écrivez ce que vous entendez</p>
            {question.hint && (
                <p className="text-sm text-muted-foreground">{question.hint}</p>
            )}

            {hasAudio ? (
                <button
                    onClick={playAudio}
                    disabled={playing}
                    className="flex items-center gap-3 rounded-xl border-2 border-primary bg-primary/5 px-6 py-4 text-primary transition hover:bg-primary/10 disabled:opacity-50"
                >
                    <img src="/icons/listening.png" alt="" width={24} height={24} style={{ objectFit: 'contain' }} />
                    <span className="font-medium">
                        {playing ? 'Lecture...' : playCount === 0 ? 'Écouter' : `Réécouter (${playCount})`}
                    </span>
                </button>
            ) : (
                <div className="rounded-xl border border-border bg-muted/50 px-6 py-4 text-sm text-muted-foreground">
                    Audio non disponible pour cet exercice
                </div>
            )}

            <textarea
                value={value}
                onChange={(e) => setValue(e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                rows={4}
                placeholder="Écrivez ce que vous entendez..."
                disabled={!!selectedAnswer}
            />

            {!selectedAnswer && (
                <button
                    onClick={() => value.trim() && onAnswer(question.id, value.trim())}
                    disabled={!value.trim()}
                    className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground disabled:opacity-50"
                >
                    Valider
                </button>
            )}
        </div>
    );
}
