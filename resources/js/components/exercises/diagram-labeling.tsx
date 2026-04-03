import { useState } from 'react';

interface Label {
    id: string;
    x: number;
    y: number;
    answer?: string;
}

interface DiagramLabelingProps {
    question: {
        id: string;
        text: string;
        image_url?: string;
        labels: Label[];
    };
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

export function DiagramLabeling({ question, onAnswer, selectedAnswer, disabled }: DiagramLabelingProps) {
    const [values, setValues] = useState<Record<string, string>>(selectedAnswer ?? {});

    const labels = question.labels || [];

    const handleChange = (labelId: string, val: string) => {
        const next = { ...values, [labelId]: val };
        setValues(next);
    };

    const handleSubmit = () => {
        if (labels.every((l) => values[l.id]?.trim())) {
            onAnswer(question.id, values);
        }
    };

    return (
        <div className="space-y-4">
            <p className="text-sm font-medium">{question.text}</p>

            {/* Diagram with markers */}
            {question.image_url ? (
                <div className="relative overflow-hidden rounded-xl border bg-white">
                    <img src={question.image_url} alt="Diagram" className="w-full object-contain" />
                    {/* Numbered markers positioned on the image */}
                    {labels.map((label, i) => (
                        <div
                            key={label.id}
                            className="absolute flex h-6 w-6 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground shadow-md"
                            style={{
                                left: `${label.x}%`,
                                top: `${label.y}%`,
                                transform: 'translate(-50%, -50%)',
                            }}
                        >
                            {i + 1}
                        </div>
                    ))}
                </div>
            ) : (
                <div className="flex items-center justify-center rounded-xl border-2 border-dashed border-muted-foreground/30 py-16">
                    <p className="text-sm text-muted-foreground">Diagramme non disponible</p>
                </div>
            )}

            {/* Label inputs */}
            <div className="space-y-2">
                {labels.map((label, i) => (
                    <div key={label.id} className="flex items-center gap-3">
                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                            {i + 1}
                        </span>
                        <input
                            type="text"
                            value={values[label.id] ?? ''}
                            onChange={(e) => handleChange(label.id, e.target.value)}
                            disabled={disabled}
                            className="flex-1 rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none disabled:opacity-50"
                            placeholder={`Label ${i + 1}...`}
                        />
                    </div>
                ))}
            </div>

            {!disabled && (
                <button
                    onClick={handleSubmit}
                    disabled={!labels.every((l) => values[l.id]?.trim())}
                    className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground disabled:opacity-50"
                >
                    Valider
                </button>
            )}
        </div>
    );
}
