import { useState } from 'react';

interface GappedTextProps {
    question: {
        id: string;
        passage_with_gaps: (string | null)[];
        removed_paragraphs: string[];
        gap_count: number;
    };
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

export function GappedText({ question, onAnswer, selectedAnswer, disabled }: GappedTextProps) {
    const [placements, setPlacements] = useState<Record<string, string>>(selectedAnswer ?? {});
    const [selectedParagraph, setSelectedParagraph] = useState<string | null>(null);

    const placedParagraphs = new Set(Object.values(placements));
    const availableParagraphs = question.removed_paragraphs.filter((p) => !placedParagraphs.has(p));

    const gapIndices = question.passage_with_gaps
        .map((item, i) => (item === null ? String(i) : null))
        .filter(Boolean) as string[];

    const handleGapClick = (gapKey: string) => {
        if (disabled) return;

        if (placements[gapKey]) {
            // Remove placement
            const next = { ...placements };
            delete next[gapKey];
            setPlacements(next);
            return;
        }

        if (selectedParagraph) {
            const next = { ...placements, [gapKey]: selectedParagraph };
            setPlacements(next);
            setSelectedParagraph(null);
        }
    };

    const handleParagraphClick = (paragraph: string) => {
        if (disabled) return;
        setSelectedParagraph(selectedParagraph === paragraph ? null : paragraph);
    };

    const handleSubmit = () => {
        if (gapIndices.every((k) => placements[k])) {
            onAnswer(question.id, placements);
        }
    };

    let gapNumber = 0;

    return (
        <div className="space-y-5">
            {/* Passage */}
            <div className="space-y-3">
                {question.passage_with_gaps.map((item, i) => {
                    if (item !== null) {
                        return (
                            <p key={i} className="rounded-lg bg-muted/30 p-3 text-sm leading-relaxed">
                                {item}
                            </p>
                        );
                    }

                    const gapKey = String(i);
                    const placed = placements[gapKey];
                    gapNumber++;

                    return (
                        <button
                            key={i}
                            onClick={() => handleGapClick(gapKey)}
                            disabled={disabled}
                            className={`w-full rounded-xl border-2 border-dashed p-4 text-left text-sm transition-all ${
                                placed
                                    ? 'border-primary/50 bg-primary/5'
                                    : selectedParagraph
                                      ? 'border-primary bg-primary/10 animate-pulse cursor-pointer'
                                      : 'border-muted-foreground/30 bg-muted/10'
                            } disabled:opacity-50`}
                        >
                            {placed ? (
                                <div className="flex items-start gap-2">
                                    <span className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded bg-primary text-[10px] font-bold text-primary-foreground">
                                        {gapNumber}
                                    </span>
                                    <span className="italic">{placed}</span>
                                    {!disabled && (
                                        <span className="ml-auto shrink-0 text-xs text-red-500">✕</span>
                                    )}
                                </div>
                            ) : (
                                <span className="text-muted-foreground">
                                    Espace {gapNumber} — {selectedParagraph ? 'Cliquez pour placer' : 'Sélectionnez un paragraphe ci-dessous'}
                                </span>
                            )}
                        </button>
                    );
                })}
            </div>

            {/* Available paragraphs */}
            <div>
                <p className="mb-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                    Paragraphes à placer
                </p>
                <div className="space-y-2">
                    {question.removed_paragraphs.map((para, i) => {
                        const isPlaced = placedParagraphs.has(para);
                        const isSelected = selectedParagraph === para;
                        return (
                            <button
                                key={i}
                                onClick={() => !isPlaced && handleParagraphClick(para)}
                                disabled={disabled || isPlaced}
                                className={`w-full rounded-xl border-2 p-3 text-left text-sm transition-all ${
                                    isPlaced
                                        ? 'border-muted bg-muted/30 text-muted-foreground line-through opacity-50'
                                        : isSelected
                                          ? 'border-primary bg-primary/10 shadow-sm'
                                          : 'border-border bg-background hover:border-primary/50'
                                } disabled:cursor-not-allowed`}
                            >
                                {para}
                            </button>
                        );
                    })}
                </div>
            </div>

            {!disabled && (
                <button
                    onClick={handleSubmit}
                    disabled={!gapIndices.every((k) => placements[k])}
                    className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground disabled:opacity-50"
                >
                    Valider
                </button>
            )}
        </div>
    );
}
