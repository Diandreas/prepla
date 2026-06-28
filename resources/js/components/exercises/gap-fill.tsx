import { useState } from 'react';
import { normalizeOptions } from './normalize-options';

interface GapFillProps {
    question: {
        id: string;
        text: string;
        options?: unknown;
        correct_answer?: unknown;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

/**
 * Gap-fill : on écrit DIRECTEMENT dans le blanc, à l'intérieur de la phrase
 * (champ inline à la place du ___), au lieu d'un input séparé en bas.
 * Le champ s'élargit selon ce qui est tapé pour rester fluide dans le texte.
 */
export function GapFill({ question, onAnswer, selectedAnswer, disabled }: GapFillProps) {
    const [value, setValue] = useState(selectedAnswer ?? '');
    const raw = question.text ?? '';
    const options = normalizeOptions(question.options);

    // mcq-cloze (seeded) maps to this component WITH options → mode QCM inline.
    if (options.length > 0) {
        const parts = raw.split(/_{2,}/);
        const correct = String(question.correct_answer ?? '').trim().toUpperCase();
        const selLetter = (selectedAnswer ?? '').toUpperCase();
        const selIdx = selLetter.length === 1 ? selLetter.charCodeAt(0) - 65 : -1;
        return (
            <div className="space-y-3">
                <p className="text-lg font-medium leading-relaxed">
                    {parts[0]}
                    <span className="mx-1 inline-block min-w-[80px] border-b-2 border-primary px-1 text-center font-bold text-primary">
                        {selIdx >= 0 && selIdx < options.length ? options[selIdx] : ' '}
                    </span>
                    {parts.slice(1).join('___')}
                </p>
                <div className="grid gap-2">
                    {options.map((opt, i) => {
                        const letter = String.fromCharCode(65 + i);
                        const isSel = selLetter === letter;
                        const isCorrect = disabled && correct === letter;
                        const isWrong = disabled && isSel && correct !== letter;
                        return (
                            <button
                                key={i}
                                onClick={() => !disabled && onAnswer(question.id, letter)}
                                disabled={disabled}
                                className={`rounded-xl border-2 p-3 text-left text-sm ${
                                    isCorrect ? 'border-emerald-400 bg-emerald-50'
                                    : isWrong ? 'border-red-400 bg-red-50'
                                    : isSel ? 'border-primary bg-primary/5'
                                    : 'border-border disabled:opacity-50'}`}
                            >
                                <span className="mr-2.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs font-bold">{letter}</span>
                                {opt}
                            </button>
                        );
                    })}
                </div>
            </div>
        );
    }
    // Découpe au 1er trou (___ ou (1)___). S'il y a un indice entre parenthèses
    // juste après le blanc, ex "___ (gehen)", on le garde visible comme aide.
    const parts = raw.split(/_{2,}/);
    const before = parts[0] ?? '';
    const after = parts.slice(1).join('___');

    const update = (v: string) => {
        setValue(v);
        onAnswer(question.id, v.trim());
    };

    // Largeur du champ ~ longueur du texte (min 6ch).
    const width = Math.max(6, (value.length || (selectedAnswer?.length ?? 0) || 8) + 1);

    return (
        <div className="space-y-2">
            <p className="text-lg font-medium leading-relaxed">
                {before}
                <input
                    type="text"
                    value={selectedAnswer ?? value}
                    onChange={(e) => update(e.target.value)}
                    disabled={disabled}
                    autoComplete="off"
                    autoCapitalize="off"
                    spellCheck={false}
                    placeholder="…"
                    style={{ width: `${width}ch` }}
                    className="mx-1 inline-block border-b-2 border-primary bg-primary/5 px-1.5 py-0.5 text-center font-bold text-primary focus:outline-none focus:border-primary disabled:opacity-60"
                />
                {after}
            </p>
        </div>
    );
}
