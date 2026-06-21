import { useState } from 'react';
import { normalizeOptions } from './normalize-options';

interface Definition {
    part_of_speech: string;
    definition: string;
    examples: string[];
}

interface Phonetic {
    ipa: string;
    audio: string | null;
}

interface AudioEntry {
    url: string;
    speaker: string | null;
    country: string | null;
}

interface WordData {
    word: string;
    language: string;
    definitions: Definition[];
    phonetics: Phonetic[];
    audio: AudioEntry[];
    examples: string[];
}

interface VocabularyCardProps {
    question: {
        id: string;
        word: string;
        language: string;
        hint?: string;
        options?: unknown;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
}

export function VocabularyCard({ question, onAnswer, selectedAnswer }: VocabularyCardProps) {
    const vocabOptions = normalizeOptions(question.options);
    const [wordData, setWordData] = useState<WordData | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [revealed, setRevealed] = useState(false);
    const [audioPlaying, setAudioPlaying] = useState(false);

    const fetchWordData = async () => {
        if (wordData || loading) return;
        setLoading(true);
        setError(null);
        try {
            const res = await fetch(`/api/dictionary/${question.language}/${encodeURIComponent(question.word)}`);
            if (!res.ok) throw new Error('Word not found');
            const data: WordData = await res.json();
            setWordData(data);
        } catch {
            setError('Definition unavailable');
        } finally {
            setLoading(false);
        }
    };

    const playAudio = async (url: string) => {
        if (audioPlaying) return;
        setAudioPlaying(true);
        try {
            const audio = new Audio(url);
            audio.onended = () => setAudioPlaying(false);
            audio.onerror = () => setAudioPlaying(false);
            await audio.play();
        } catch {
            setAudioPlaying(false);
        }
    };

    const handleReveal = () => {
        setRevealed(true);
        fetchWordData();
    };

    // IPA from phonetics or audio entries
    const ipa = wordData?.phonetics?.[0]?.ipa ?? null;
    const audioUrl =
        wordData?.audio?.[0]?.url ??
        wordData?.phonetics?.find((p) => p.audio)?.audio ??
        null;

    const topDefinitions = wordData?.definitions?.slice(0, 3) ?? [];

    return (
        <div className="space-y-4">
            {/* Word display */}
            <div className="rounded-xl border bg-card p-6 text-center shadow-sm">
                <p className="mb-1 text-xs font-medium uppercase tracking-widest text-muted-foreground">
                    {question.language}
                </p>
                <p className="text-3xl font-bold tracking-tight">{question.word}</p>

                {/* IPA + audio */}
                {revealed && (
                    <div className="mt-3 flex items-center justify-center gap-2">
                        {loading && (
                            <span className="text-sm text-muted-foreground">Loading…</span>
                        )}
                        {!loading && ipa && (
                            <span className="rounded bg-muted px-2 py-0.5 font-mono text-sm text-foreground">
                                /{ipa}/
                            </span>
                        )}
                        {!loading && audioUrl && (
                            <button
                                onClick={() => playAudio(audioUrl)}
                                disabled={audioPlaying}
                                className="flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 text-primary transition hover:bg-primary/20 disabled:opacity-50"
                                title="Listen to pronunciation"
                            >
                                <svg viewBox="0 0 20 20" fill="currentColor" className="h-4 w-4">
                                    <path d="M6.3 2.84A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.27l9.344-5.891a1.5 1.5 0 000-2.538L6.3 2.84z" />
                                </svg>
                            </button>
                        )}
                        {!loading && error && (
                            <span className="text-xs text-muted-foreground">{error}</span>
                        )}
                    </div>
                )}

                {/* Hint */}
                {question.hint && (
                    <p className="mt-2 text-sm text-muted-foreground">{question.hint}</p>
                )}
            </div>

            {/* Definitions panel */}
            {revealed && !loading && topDefinitions.length > 0 && (
                <div className="rounded-xl border bg-muted/30 p-4 space-y-3">
                    {topDefinitions.map((def, i) => (
                        <div key={i} className="space-y-1">
                            <div className="flex items-center gap-2">
                                {def.part_of_speech && (
                                    <span className="rounded bg-primary/10 px-1.5 py-0.5 text-xs font-medium text-primary">
                                        {def.part_of_speech}
                                    </span>
                                )}
                            </div>
                            <p className="text-sm leading-relaxed">{def.definition}</p>
                            {def.examples?.[0] && (
                                <p className="text-xs italic text-muted-foreground">
                                    "{def.examples[0]}"
                                </p>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {/* Reveal button */}
            {!revealed && (
                <button
                    onClick={handleReveal}
                    className="w-full rounded-lg border-2 border-dashed border-border py-3 text-sm text-muted-foreground transition hover:border-primary hover:text-primary"
                >
                    Show definition & pronunciation
                </button>
            )}

            {/* Answer options (MCQ style) */}
            {vocabOptions.length > 0 && (
                <div className="grid gap-2">
                    {vocabOptions.map((option, i) => {
                        const letter = String.fromCharCode(65 + i);
                        return (
                            <button
                                key={i}
                                onClick={() => onAnswer(question.id, letter)}
                                className={`rounded-lg border p-4 text-left transition-all ${
                                    selectedAnswer === letter
                                        ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                        : 'border-border hover:border-primary/50'
                                }`}
                            >
                                <span className="mr-3 inline-flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs font-medium">
                                    {letter}
                                </span>
                                {option}
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
