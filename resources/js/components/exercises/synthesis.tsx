import { useState } from 'react';

interface SynthesisProps {
    question: {
        id: string;
        documents: { title: string; content: string }[];
        sources?: any[];
        writing_prompt: string;
        min_words?: number;
        max_words?: number;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

export function Synthesis({ question, onAnswer, selectedAnswer, disabled }: SynthesisProps) {
    const [value, setValue] = useState(selectedAnswer ?? '');
    const [expandedDoc, setExpandedDoc] = useState<number | null>(0);
    const minWords = question.min_words ?? 220;
    const maxWords = question.max_words ?? 250;
    const wordCount = value.trim() ? value.trim().split(/\s+/).length : 0;
    const inRange = wordCount >= minWords && wordCount <= maxWords;

    const handleChange = (text: string) => {
        setValue(text);
        onAnswer(question.id, text);
    };

    return (
        <div className="grid gap-6 md:grid-cols-2">
            <div className="space-y-4">
                <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Documents sources</p>
                {(question.documents || question.sources || []).map((doc, i) => (
                    <div key={i} className="overflow-hidden rounded-xl border">
                        <button
                            onClick={() => setExpandedDoc(expandedDoc === i ? null : i)}
                            className="flex w-full items-center justify-between bg-muted/30 px-4 py-3 text-left"
                        >
                            <div className="flex items-center gap-2">
                                <span className="flex h-6 w-6 items-center justify-center rounded bg-primary text-[10px] font-bold text-primary-foreground">
                                    {i + 1}
                                </span>
                                <span className="text-sm font-semibold">{doc.title}</span>
                            </div>
                            <span className="text-xs text-muted-foreground">
                                {expandedDoc === i ? '▲' : '▼'}
                            </span>
                        </button>
                        {expandedDoc === i && (
                            <div className="border-t bg-background p-4">
                                <p className="text-sm leading-relaxed whitespace-pre-line">{doc.content}</p>
                            </div>
                        )}
                    </div>
                ))}
            </div>

            {/* Writing prompt */}
            <div className="rounded-xl border-2 border-primary/20 bg-primary/5 p-4">
                <p className="text-sm font-medium">{question.writing_prompt}</p>
            </div>

            {/* Textarea */}
            <textarea
                className="min-h-[200px] w-full rounded-lg border border-border bg-background p-4 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:opacity-50"
                placeholder="Rédigez votre synthèse ici..."
                value={value}
                onChange={(e) => handleChange(e.target.value)}
                disabled={disabled}
            />

            <div className="flex justify-between text-sm">
                <span className={inRange ? 'font-medium text-green-600' : wordCount > maxWords ? 'font-medium text-red-500' : 'text-muted-foreground'}>
                    {wordCount} mot{wordCount !== 1 ? 's' : ''}
                </span>
                <span className="text-muted-foreground">{minWords}–{maxWords} mots</span>
            </div>
        </div>
    );
}
