import { useState } from 'react';

interface GuidedWritingProps {
    question: {
        id: string;
        // A source the learner works FROM (text to rewrite/summarise, or the start
        // of a text to continue), + an instruction, + optional mandatory words.
        source_text?: string;
        text?: string;            // instruction / prompt
        must_use?: string[];      // mandatory words (guided-rewrite)
        min_words?: number;
        max_words?: number;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

/**
 * Écriture guidée : réécriture/résumé (avec mots imposés) OU continuation d'un
 * texte amorcé. Affiche le texte source + la consigne + une zone de rédaction.
 * Corrigé par l'IA (type AI-évalué côté scoring).
 */
export function GuidedWriting({ question, onAnswer, selectedAnswer, disabled }: GuidedWritingProps) {
    const [value, setValue] = useState(selectedAnswer ?? '');
    const min = question.min_words ?? 0;
    const wordCount = value.trim() ? value.trim().split(/\s+/).length : 0;

    const update = (v: string) => {
        setValue(v);
        if (v.trim().split(/\s+/).filter(Boolean).length >= Math.max(1, Math.floor(min * 0.5))) {
            onAnswer(question.id, v.trim());
        }
    };

    return (
        <div className="space-y-3">
            {question.source_text && (
                <div className="rounded-xl border bg-muted/30 p-4 text-sm leading-relaxed">
                    <p className="mb-1 text-[10px] font-black uppercase tracking-widest text-indigo-400">Texte source</p>
                    {question.source_text}
                </div>
            )}

            {question.text && <p className="text-sm font-bold">{question.text}</p>}

            {Array.isArray(question.must_use) && question.must_use.length > 0 && (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-xs font-bold text-muted-foreground">À utiliser :</span>
                    {question.must_use.map((w, i) => {
                        const used = value.toLowerCase().includes(w.toLowerCase());
                        return (
                            <span key={i} className={`rounded-lg border px-2 py-0.5 text-xs font-bold ${used ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-primary/30 text-foreground'}`}>
                                {w}
                            </span>
                        );
                    })}
                </div>
            )}

            <textarea
                value={value}
                onChange={(e) => update(e.target.value)}
                disabled={disabled}
                rows={8}
                placeholder="Rédige ta réponse ici…"
                className="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm leading-relaxed focus:border-primary focus:outline-none disabled:opacity-60"
            />
            <div className="flex items-center justify-between text-[11px] font-bold text-muted-foreground">
                <span>{wordCount} mot{wordCount > 1 ? 's' : ''}{min ? ` / ${min} min` : ''}</span>
                {min > 0 && wordCount >= min && <span className="text-emerald-600">✓ longueur atteinte</span>}
            </div>
        </div>
    );
}
