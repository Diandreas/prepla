import { useState } from 'react';
import { illustrationUrl } from '@/lib/illustration';

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
        image_prompt?: string;
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
        // Depuis l'etat precedent : deux saisies dans le meme cycle de rendu
        // repartaient sinon du meme etat et la premiere etiquette etait perdue.
        setValues((prev) => {
            const next = { ...prev, [labelId]: val };
            // Transmise des la premiere etiquette : le bouton « Valider » cache dans
            // l'exercice exigeait TOUTES les etiquettes, et « Verifier » restait eteint.
            onAnswer(question.id, next);

            return next;
        });
    };

    return (
        <div className="space-y-4">
            <p className="text-sm font-medium">{question.text}</p>

            {/* Diagram with markers */}
            {(question.image_url || question.image_prompt) ? (
                <div className="relative overflow-hidden rounded-xl border bg-white">
                    <img src={question.image_url ?? illustrationUrl(question.image_prompt ?? '')} alt="Diagram" className="w-full object-contain" />
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

        </div>
    );
}
