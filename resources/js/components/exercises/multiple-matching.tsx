import { useState } from 'react';
import { coerceOption, collection } from './normalize-options';

interface MultipleMatchingProps {
    question: {
        id: string;
        texts: { id: string; title: string; content: string }[];
        statements: { id: string; text: string; correct_text_id?: string }[];
    };
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

export function MultipleMatching({ question, onAnswer, selectedAnswer, disabled }: MultipleMatchingProps) {
    const [values, setValues] = useState<Record<string, string>>(selectedAnswer ?? {});

    const handleSelect = (statementId: string, textId: string) => {
        if (disabled) return;
        // Calcule depuis l'etat precedent : deux clics dans le meme cycle de rendu
        // repartaient sinon du meme etat et la premiere association etait perdue.
        setValues((prev) => {
            const next = { ...prev, [statementId]: textId };
            // La reponse est transmise des le premier choix. Avant, il fallait trouver
            // un bouton « Valider » cache dans l'exercice ET avoir associe TOUTES les
            // phrases : « Verifier » restait eteint sans un mot d'explication. La note
            // reste calculee sur l'ensemble des associations attendues.
            onAnswer(question.id, next);

            return next;
        });
    };

    // Coerce every IA-generated field defensively — a malformed object here
    // (instead of a plain string) would otherwise crash the whole session with
    // React error #31, the same class of bug already fixed on mcq/matching/ordering.
    // Les textes et les affirmations arrivent tantot en liste, tantot en carte :
    // lus comme une liste seulement, l'exercice s'affichait sans aucun choix.
    const texts = collection<any>(question.texts).map((t) => ({
        id: coerceOption(t?.id),
        title: coerceOption(t?.title),
        content: coerceOption(t?.content),
    }));
    const statements = (Array.isArray(question.statements) ? question.statements : []).map((s) => ({
        id: coerceOption(s?.id),
        text: coerceOption(s?.text),
    }));
    const textIds = texts.map((t) => t.id);


    return (
        <div className="space-y-5">
            {/* Texts */}
            <div className="grid gap-3 sm:grid-cols-2">
                {texts.map((text) => (
                    <div key={text.id} className="rounded-2xl border-[1.5px] bg-muted/30 p-4">
                        <div className="mb-2 flex items-center gap-2">
                            <span className="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                                {text.id}
                            </span>
                            <span className="text-[0.9375rem] font-bold">{text.title}</span>
                        </div>
                        <p className="lecteur-passage text-foreground/90">{text.content}</p>
                    </div>
                ))}
            </div>

            {/* Statements */}
            <div className="space-y-2">
                <p data-enonce className="lecteur-enonce">
                    Associe chaque affirmation au bon texte
                </p>
                {statements.map((stmt, i) => {
                    const selected = values[stmt.id];
                    return (
                        <div key={stmt.id} className="flex flex-col gap-2 rounded-2xl border-[1.5px] bg-card p-3 sm:flex-row sm:items-center sm:gap-3">
                            <span className="min-w-[24px] text-[0.9375rem] font-bold text-muted-foreground">{i + 1}.</span>
                            <p className="flex-1 text-base">{stmt.text}</p>
                            <div className="flex flex-wrap gap-1.5" role="group" aria-label={`Texte pour l’affirmation ${i + 1}`}>
                                {textIds.map((tid) => (
                                    <button
                                        key={tid}
                                        type="button"
                                        aria-pressed={selected === tid}
                                        onClick={() => handleSelect(stmt.id, tid)}
                                        disabled={disabled}
                                        className={`flex h-11 w-11 items-center justify-center rounded-xl text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background ${
                                            selected === tid
                                                ? 'bg-primary text-primary-foreground shadow-sm'
                                                : 'border border-border bg-muted/30 text-foreground hover:border-primary/50'
                                        } disabled:opacity-50`}
                                    >
                                        {tid}
                                    </button>
                                ))}
                            </div>
                        </div>
                    );
                })}
            </div>

        </div>
    );
}
