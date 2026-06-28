import { useState } from 'react';

interface Paragraph { id: string; text: string; }

interface GappedTextProps {
    question: {
        id: string;
        text?: string;
        // Generator shape:
        passage_parts?: string[];          // text chunks; gaps are BETWEEN parts
        paragraphs?: Paragraph[];          // movable paragraphs {id, text}
        correct_order?: string[];          // paragraph ids in correct gap order
        // Legacy shape:
        passage_with_gaps?: (string | null)[];
        removed_paragraphs?: string[];
    };
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

export function GappedText({ question, onAnswer, selectedAnswer, disabled }: GappedTextProps) {
    // Normalize to: an array of text blocks + gap slots, and a list of movable items
    // {key, label} where key is what we compare against correct_order/answer.
    const usingGenerator = Array.isArray(question.passage_parts) && Array.isArray(question.paragraphs);

    // Build the rendered sequence: [text, GAP, text, GAP, …]
    const textBlocks: string[] = usingGenerator
        ? (question.passage_parts as string[])
        : (question.passage_with_gaps ?? []).filter((x): x is string => typeof x === 'string');

    // gapCount = number of gaps. For generator: parts.length - 1. For legacy: count nulls.
    const gapCount = usingGenerator
        ? Math.max((question.passage_parts as string[]).length - 1, 0)
        : (question.passage_with_gaps ?? []).filter((x) => x === null).length;

    // Movable items: generator → paragraphs {id,text}; legacy → removed_paragraphs (text only).
    const items: { key: string; label: string }[] = usingGenerator
        ? (question.paragraphs as Paragraph[]).map((p) => ({ key: p.id, label: p.text }))
        : (question.removed_paragraphs ?? []).map((t, i) => ({ key: String(i), label: t }));

    const [placements, setPlacements] = useState<Record<string, string>>(selectedAnswer ?? {});
    const [selectedItem, setSelectedItem] = useState<string | null>(null);

    const placedKeys = new Set(Object.values(placements));

    const reportIfComplete = (next: Record<string, string>) => {
        const filled = Array.from({ length: gapCount }).every((_, gi) => next[String(gi)]);
        if (gapCount > 0 && filled) {
            // Send both a gap→key map and (for generator) the ordered key list under "order".
            onAnswer(question.id, next);
        }
    };

    const handleGapClick = (gapKey: string) => {
        if (disabled) return;
        if (placements[gapKey]) {
            const next = { ...placements };
            delete next[gapKey];
            setPlacements(next);
            return;
        }
        if (selectedItem) {
            const next = { ...placements, [gapKey]: selectedItem };
            setPlacements(next);
            setSelectedItem(null);
            reportIfComplete(next);
        }
    };

    const handleItemClick = (key: string) => {
        if (disabled) return;
        setSelectedItem(selectedItem === key ? null : key);
    };

    const labelOf = (key: string) => items.find((it) => it.key === key)?.label ?? key;

    // Render the passage with gaps interleaved between text blocks.
    const sequence: { type: 'text' | 'gap'; value: string; gapIndex?: number }[] = [];
    textBlocks.forEach((block, i) => {
        sequence.push({ type: 'text', value: block });
        if (i < gapCount) sequence.push({ type: 'gap', value: '', gapIndex: i });
    });

    return (
        <div className="space-y-5">
            {question.text && <p className="text-sm font-medium">{question.text}</p>}

            {/* Passage with gaps */}
            <div className="space-y-3">
                {sequence.map((node, i) => {
                    if (node.type === 'text') {
                        if (!node.value) return null;
                        return (
                            <p key={i} className="rounded-lg bg-muted/30 p-3 text-sm leading-relaxed">{node.value}</p>
                        );
                    }
                    const gapKey = String(node.gapIndex);
                    const placed = placements[gapKey];
                    return (
                        <button
                            key={i}
                            onClick={() => handleGapClick(gapKey)}
                            disabled={disabled}
                            className={`w-full rounded-xl border-2 border-dashed p-4 text-left text-sm transition-all ${
                                placed
                                    ? 'border-primary/50 bg-primary/5'
                                    : selectedItem
                                        ? 'border-primary bg-primary/10 animate-pulse cursor-pointer'
                                        : 'border-muted-foreground/30 bg-muted/10'
                            } disabled:opacity-50`}
                        >
                            {placed ? (
                                <div className="flex items-start gap-2">
                                    <span className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded bg-primary text-[10px] font-bold text-primary-foreground">
                                        {(node.gapIndex ?? 0) + 1}
                                    </span>
                                    <span className="italic">{labelOf(placed)}</span>
                                    {!disabled && <span className="ml-auto shrink-0 text-xs text-red-500">✕</span>}
                                </div>
                            ) : (
                                <span className="text-muted-foreground">
                                    Espace {(node.gapIndex ?? 0) + 1} — {selectedItem ? 'Cliquez pour placer' : 'Sélectionnez un paragraphe ci-dessous'}
                                </span>
                            )}
                        </button>
                    );
                })}
            </div>

            {/* Movable items */}
            <div>
                <p className="mb-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">Paragraphes à placer</p>
                <div className="space-y-2">
                    {items.map((it) => {
                        const isPlaced = placedKeys.has(it.key);
                        const isSelected = selectedItem === it.key;
                        return (
                            <button
                                key={it.key}
                                onClick={() => !isPlaced && handleItemClick(it.key)}
                                disabled={disabled || isPlaced}
                                className={`w-full rounded-xl border-2 p-3 text-left text-sm transition-all ${
                                    isPlaced
                                        ? 'border-muted bg-muted/30 text-muted-foreground line-through opacity-50'
                                        : isSelected
                                            ? 'border-primary bg-primary/10 shadow-sm'
                                            : 'border-border bg-background hover:border-primary/50'
                                } disabled:cursor-not-allowed`}
                            >
                                {it.label}
                            </button>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
