import { useState } from 'react';

interface DictationProps {
    question: { id: string; audio_url: string; text?: string; hint?: string };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

/**
 * Dictée : écouter, puis écrire ce qui a été entendu.
 *
 * Sans audio, l'exercice est impossible — il fallait quand même écrire quelque chose,
 * et la question était comptée fausse. Une panne de lecture est le cas courant en
 * production (le lien public/storage mal possédé renvoie 404 en silence), et rien ne
 * la signalait : le bouton s'arrêtait de tourner, sans un mot. L'apprenant peut
 * maintenant passer la question, qui sort alors du score.
 */
export function Dictation({ question, onAnswer, selectedAnswer, disabled }: DictationProps) {
    const [playing, setPlaying] = useState(false);
    const [playCount, setPlayCount] = useState(0);
    const [audioFailed, setAudioFailed] = useState(false);

    const skipped = selectedAnswer === '__skipped__';
    const value = skipped ? '' : (selectedAnswer ?? '');
    const hasAudio = !!question.audio_url;

    const playAudio = () => {
        if (playing) return;
        setPlaying(true);
        setAudioFailed(false);
        const audio = new Audio(question.audio_url);
        audio.onended = () => {
            setPlaying(false);
            setPlayCount((count) => count + 1);
        };
        audio.onerror = () => {
            setPlaying(false);
            setAudioFailed(true);
        };
        audio.play().catch(() => {
            setPlaying(false);
            setAudioFailed(true);
        });
    };

    const unavailable = !hasAudio || audioFailed;

    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">Écoute, puis écris ce que tu entends.</p>
            {question.hint && <p className="text-muted-foreground text-sm">{question.hint}</p>}

            {hasAudio && (
                <button
                    onClick={playAudio}
                    disabled={playing || disabled}
                    className="border-primary bg-primary/5 text-primary hover:bg-primary/10 flex items-center gap-3 rounded-xl border-2 px-6 py-4 transition disabled:opacity-50"
                >
                    <img src="/icons/listening.png" alt="" width={24} height={24} style={{ objectFit: 'contain' }} />
                    <span className="font-medium">
                        {playing ? 'Lecture…' : playCount === 0 ? 'Écouter' : `Réécouter (${playCount})`}
                    </span>
                </button>
            )}

            {unavailable && (
                <div
                    role="status"
                    className="space-y-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"
                >
                    <p className="font-bold">
                        {hasAudio ? "L'enregistrement n'a pas pu être lu." : "Cet exercice est arrivé sans enregistrement."}
                    </p>
                    <p>Sans le son, la dictée est impossible : passe la question, elle ne comptera pas dans ton score.</p>
                    {!disabled && !selectedAnswer && (
                        <button
                            onClick={() => onAnswer(question.id, '__skipped__')}
                            className="bg-primary text-primary-foreground rounded-lg px-4 py-2 text-xs font-bold"
                        >
                            Passer cette question
                        </button>
                    )}
                    {skipped && <p className="font-medium">Question passée.</p>}
                </div>
            )}

            {!skipped && (
                <textarea
                    value={value}
                    onChange={(e) => onAnswer(question.id, e.target.value)}
                    className="border-border bg-background focus:border-primary focus:ring-primary w-full rounded-lg border px-4 py-3 text-sm focus:ring-1 focus:outline-none disabled:opacity-60"
                    rows={4}
                    aria-label="Ce que tu entends"
                    placeholder="Écris ce que tu entends…"
                    disabled={disabled}
                />
            )}
        </div>
    );
}
