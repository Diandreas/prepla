import { useState } from 'react';

interface TableCompletionProps {
    question: {
        id: string;
        title?: string;
        headers: string[];
        rows: { cells: { value: string; blank: boolean }[] }[];
    };
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

export function TableCompletion({ question, onAnswer, selectedAnswer, disabled }: TableCompletionProps) {
    const [values, setValues] = useState<Record<string, string>>(selectedAnswer ?? {});

    const blankCells: string[] = [];
    question.rows.forEach((row, ri) => {
        row.cells.forEach((cell, ci) => {
            if (cell.blank) blankCells.push(`${ri}-${ci}`);
        });
    });

    const handleChange = (key: string, val: string) => {
        const next = { ...values, [key]: val };
        setValues(next);
    };

    const handleSubmit = () => {
        if (blankCells.every((k) => values[k]?.trim())) {
            onAnswer(question.id, values);
        }
    };

    return (
        <div className="space-y-4">
            {question.title && <p className="text-lg font-bold">{question.title}</p>}

            <div className="overflow-x-auto rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="bg-muted/50">
                            {question.headers.map((h, i) => (
                                <th key={i} className="border-b px-4 py-3 text-left font-semibold">
                                    {h}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {question.rows.map((row, ri) => (
                            <tr key={ri} className="border-b last:border-b-0">
                                {row.cells.map((cell, ci) => {
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

            {!disabled && (
                <button
                    onClick={handleSubmit}
                    disabled={!blankCells.every((k) => values[k]?.trim())}
                    className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground disabled:opacity-50"
                >
                    Valider
                </button>
            )}
        </div>
    );
}
