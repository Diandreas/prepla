import { useState } from 'react';
import { CHAMP_SAISIE, ChoiceOption, lettreDuChoix, verdictDuChoix } from './choice-option';
import { normalizeOptions } from './normalize-options';

interface SentenceCompletionProps {
    question: {
        id: string;
        text: string;
        blank_position?: string;
        // The generator emits options[4] + a LETTER correct_answer (MCQ-style).
        // Some exams instead want a free typed answer (no options).
        options?: unknown;
        correct_answer?: unknown;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
    /** Correction montree ; par defaut `disabled`, comme avant (voir mcq.tsx). */
    revealed?: boolean;
}

export function SentenceCompletion({ question, onAnswer, selectedAnswer, disabled, revealed = disabled }: SentenceCompletionProps) {
    const [value, setValue] = useState(selectedAnswer ?? '');
    const parts = (question.text ?? '').split('___');
    const options = normalizeOptions(question.options);
    const hasOptions = options.length > 0;

    // ─── Choice mode: the generator gave options → pick a letter (A/B/C/D) ───
    if (hasOptions) {
        const correct = String(question.correct_answer ?? '').trim().toUpperCase();
        return (
            <div className="space-y-4">
                <p data-enonce className="lecteur-enonce leading-relaxed">
                    {parts[0]}
                    <span className="inline-block min-w-[120px] border-b-2 border-primary mx-1 px-1 text-center font-bold">
                        {(() => {
                            const sel = selectedAnswer ?? '';
                            const idx = sel.length === 1 ? sel.toUpperCase().charCodeAt(0) - 65 : -1;
                            return idx >= 0 && idx < options.length ? options[idx] : ' ';
                        })()}
                    </span>
                    {parts.slice(1).join(' ___ ')}
                </p>
                <div className="grid gap-2.5">
                    {options.map((opt, i) => {
                        const letter = lettreDuChoix(i);
                        const isSelected = (selectedAnswer ?? '').toUpperCase() === letter;
                        return (
                            <ChoiceOption
                                key={i}
                                index={i}
                                letter={letter}
                                selected={isSelected}
                                verdict={verdictDuChoix(!!revealed, correct, letter, isSelected)}
                                disabled={disabled}
                                onSelect={() => onAnswer(question.id, letter)}
                            >
                                {opt}
                            </ChoiceOption>
                        );
                    })}
                </div>
            </div>
        );
    }

    // ─── Free-text mode: no options → type the missing word(s) ───
    return (
        <div className="space-y-4">
            <p data-enonce className="lecteur-enonce leading-relaxed">
                {parts[0]}
                <span className="inline-block min-w-[120px] border-b-2 border-primary mx-1 px-1">
                    {selectedAnswer || value || ' '}
                </span>
                {parts.slice(1).join(' ___ ')}
            </p>
            <input
                type="text"
                value={value}
                onChange={(e) => {
                    setValue(e.target.value);
                    onAnswer(question.id, e.target.value);
                }}
                aria-label="Ta réponse"
                className={CHAMP_SAISIE}
                placeholder="Écris ta réponse…"
                disabled={disabled}
            />
        </div>
    );
}
