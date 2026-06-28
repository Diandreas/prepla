import { useState } from 'react';

interface TableCompletionProps {
    question: {
        id: string;
        title?: string;
        text?: string;
        headers: string[];
        // Two shapes supported:
        //  (A) generator: rows = string[][] with "" for blank cells
        //  (B) legacy:    rows = { cells: { value, blank }[] }[]
        rows: unknown;
        correct_answers?: Record<string, string>;
    };
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

interface Cell { value: string; blank: boolean; }

// Normalize either shape into a 2D array of {value, blank}.
function normalizeRows(rows: unknown): Cell[][] {
    if (!Array.isArray(rows)) return [];
    return rows.map((row: any) => {
        // Shape B: { cells: [{value, blank}] }
        if (row && typeof row === 'object' && Array.isArray(row.cells)) {
            return row.cells.map((c: any) => ({
                value: String(c?.value ?? ''),
                blank: typeof c?.blank === 'boolean' ? c.blank : String(c?.value ?? '') === '',
            }));
        }
        // Shape A: array of strings ("" = blank)
        if (Array.isArray(row)) {
            return row.map((v: any) => ({ value: String(v ?? ''), blank: String(v ?? '') === '' }));
        }
        return [];
    });
}

export function TableCompletion({ question, onAnswer, selectedAnswer, disabled }: TableCompletionProps) {
    const [values, setValues] = useState<Record<string, string>>(selectedAnswer ?? {});
    const rows = normalizeRows(question.rows);

    const blankCells: string[] = [];
    rows.forEach((cells, ri) => cells.forEach((cell, ci) => { if (cell.blank) blankCells.push(`${ri}-${ci}`); }));

    const handleChange = (key: string, val: string) => {
        setValues((prev) => {
            const next = { ...prev, [key]: val };
            if (blankCells.length > 0 && blankCells.every((k) => (next[k] ?? '').trim() !== '')) {
                onAnswer(question.id, next);
            }
            return next;
        });
    };

    return (
        <div className="space-y-4">
            {(question.title || question.text) && <p className="text-lg font-bold">{question.title ?? question.text}</p>}

            {blankCells.length === 0 ? (
                <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Ce tableau n'a aucune cellule à compléter.
                </div>
            ) : (
                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        {(question.headers || []).length > 0 && (
                            <thead>
                                <tr className="bg-muted/50">
                                    {(question.headers || []).map((h, i) => (
                                        <th key={i} className="border-b px-4 py-3 text-left font-semibold">{h}</th>
                                    ))}
                                </tr>
                            </thead>
                        )}
                        <tbody>
                            {rows.map((cells, ri) => (
                                <tr key={ri} className="border-b last:border-b-0">
                                    {cells.map((cell, ci) => {
                                        const key = `${ri}-${ci}`;
                                        return (
                                            <td key={ci} className={`px-4 py-3 ${cell.blank ? 'bg-primary/5' : ''}`}>
                                                {cell.blank ? (
                                                    <input
                                                        type="text"
                                                        value={values[key] ?? ''}
                                                        onChange={(e) => handleChange(key, e.target.value)}
                                                        disabled={disabled}
                                                        className="w-full rounded border border-border bg-background px-2 py-1 text-sm focus:border-primary focus:outline-none disabled:opacity-50"
                                                        placeholder="..."
                                                    />
                                                ) : (
                                                    <span>{cell.value}</span>
                                                )}
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
