import { useState } from 'react';

interface CompleteTheWordsProps {
    question: {
        id: string;
        // A paragraph where some words have missing letters, written as the visible
        // stem + "_" for each missing letter, e.g. "The eco___ grew" with answer "economy".
        // Generator emits: text (paragraph with blanked words like "gov______"), and
        // correct_answers (object index→full word) OR words array.
        text?: string;
        correct_answers?: Record<string, string>;
    };
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

/**
 * TOEFL 2026 "Complete the Words" : un paragraphe où certains mots ont des lettres
 * manquantes (montrées par des underscores). On tape le mot complet dans le blanc,
 * inline dans le texte. Découpe sur des tokens contenant au moins 2 underscores.
 */
export function CompleteTheWords({ question, onAnswer, selectedAnswer, disabled }: CompleteTheWordsProps) {
    const [values, setValues] = useState<Record<string, string>>(selectedAnswer ?? {});
    const text = question.text ?? '';

    // Split into tokens; a "blanked" token has ≥2 underscores (e.g. "eco___").
    const tokens = text.split(/(\s+)/);
    let blankIdx = -1;
    const blanks: number[] = [];
    tokens.forEach((tok, i) => { if (/_{2,}/.test(tok)) blanks.push(i); });

    const update = (key: string, val: string) => {
        setValues((prev) => {
            const next = { ...prev, [key]: val };
            const allFilled = blanks.every((_, k) => (next[String(k)] ?? '').trim() !== '');
            if (blanks.length > 0 && allFilled) onAnswer(question.id, next);
            return next;
        });
    };

    return (
        <div className="space-y-3">
            <p className="text-sm font-bold text-muted-foreground">Complète les mots dont il manque des lettres.</p>
            <div className="rounded-xl border bg-muted/30 p-5 leading-relaxed text-base">
                {tokens.map((tok, i) => {
                    if (!/_{2,}/.test(tok)) return <span key={i}>{tok}</span>;
                    blankIdx++;
                    const key = String(blankIdx);
                    // Show the visible stem (letters before the underscores) as a hint.
                    const stem = tok.replace(/_+.*/, '');
                    const tail = tok.replace(/^[^_]*_+/, ''); // any trailing punctuation
                    return (
                        <span key={i} className="inline-flex items-baseline">
                            {stem && <span className="font-medium">{stem}</span>}
                            <input
                                type="text"
                                value={values[key] ?? ''}
                                onChange={(e) => update(key, e.target.value)}
                                disabled={disabled}
                                autoComplete="off"
                                spellCheck={false}
                                placeholder={'·'.repeat(Math.max(2, (tok.match(/_/g) || []).length))}
                                style={{ width: `${Math.max(4, (tok.match(/_/g) || []).length + 2)}ch` }}
                                className="mx-0.5 border-b-2 border-primary bg-primary/5 px-1 text-center font-bold text-primary focus:outline-none disabled:opacity-60"
                            />
                            {tail}
                        </span>
                    );
                })}
            </div>
        </div>
    );
}
