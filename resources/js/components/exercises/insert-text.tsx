import { useState } from 'react';

interface InsertTextProps {
    question: {
        id: string;
        passage: string;
        sentence?: string;
        sentence_to_insert?: string;
        correct_answer?: string;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

export function InsertText({ question, onAnswer, selectedAnswer, disabled }: InsertTextProps) {
    const [selected, setSelected] = useState<string | null>(selectedAnswer ?? null);

    const passage = question.passage || '';
    const markers = passage.match(/\[([A-Z])\]/g) ?? [];
    const parts = passage.split(/\[[A-Z]\]/);

    const handleSelect = (marker: string) => {
        if (disabled) return;
        const letter = marker.replace(/[[\]]/g, '');
        setSelected(letter);
        onAnswer(question.id, letter);
    };

    return (
        <div className="space-y-4">
            {/* Sentence to insert */}
            <div className="rounded-xl border-2 border-primary/30 bg-primary/5 p-4">
                <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-primary">Phrase à insérer</p>
                <p className="text-sm font-medium italic">{question.sentence ?? question.sentence_to_insert}</p>
            </div>

            {/* Passage with markers */}
            <div className="rounded-xl border bg-muted/30 p-6 leading-relaxed text-sm">
                {parts.map((part, i) => (
                    <span key={i}>
                        {part}
                        {i < markers.length && (
                            <button
                                onClick={() => handleSelect(markers[i])}
                                disabled={disabled}
                                className={`mx-1 inline-flex h-7 w-7 items-center justify-center rounded-lg text-xs font-bold transition-all ${
                                    selected === markers[i].replace(/[[\]]/g, '')
                                        ? 'bg-primary text-primary-foreground shadow-md scale-110'
                                        : 'border-2 border-primary/40 bg-background text-primary hover:bg-primary/10'
                                } disabled:opacity-50`}
                            >
                                {markers[i].replace(/[[\]]/g, '')}
                            </button>
                        )}
                    </span>
                ))}
            </div>

            {selected && (
                <p className="text-sm text-muted-foreground">
                    Position sélectionnée : <span className="font-bold text-primary">[{selected}]</span>
                </p>
            )}
        </div>
    );
}
