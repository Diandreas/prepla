import { useState } from 'react';
import { normalizeOptions } from './normalize-options';

// Matches the actual shape returned by DictionaryController::lookup() /
// the DictionaryWord model — singular definition/example fields, no
// phonetics/audio (that data doesn't exist in this system).
interface WordData {
    word: string;
    language: string;
    definition: string;
    example: string | null;
    translation: string | null;
    skill_level: string | null;
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

    const fetchWordData = async () => {
        if (wordData || loading) return;
        setLoading(true);
        setError(null);
        try {
            const res = await fetch(`/dictionary/lookup/${question.language}/${encodeURIComponent(question.word)}`);
            if (!res.ok) throw new Error('Word not found');
            const data: WordData = await res.json();
            setWordData(data);
        } catch {
            setError('Definition unavailable');
        } finally {
            setLoading(false);
        }
    };

    const handleReveal = () => {
        setRevealed(true);
        fetchWordData();
    };

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

            {/* Definition panel */}
            {revealed && !loading && wordData?.definition && (
                <div className="rounded-xl border bg-muted/30 p-4 space-y-2">
                    <p className="text-sm leading-relaxed">{wordData.definition}</p>
                    {wordData.example && (
                        <p className="text-xs italic text-muted-foreground">"{wordData.example}"</p>
                    )}
                    {wordData.translation && (
                        <p className="text-xs text-muted-foreground">Traduction : {wordData.translation}</p>
                    )}
                </div>
            )}

            {/* Reveal button */}
            {!revealed && (
                <button
                    onClick={handleReveal}
                    className="w-full rounded-lg border-2 border-dashed border-border py-3 text-sm text-muted-foreground transition hover:border-primary hover:text-primary"
                >
                    Show definition
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
