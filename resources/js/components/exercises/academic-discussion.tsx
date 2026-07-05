import { useState } from 'react';
import { coerceOption } from './normalize-options';

interface AcademicDiscussionProps {
    question: {
        id: string;
        professor_prompt: string;
        student_posts: { name: string; text: string }[];
        writing_prompt: string;
        min_words?: number;
        max_words?: number;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

export function AcademicDiscussion({ question, onAnswer, selectedAnswer, disabled }: AcademicDiscussionProps) {
    const [value, setValue] = useState(selectedAnswer ?? '');
    const minWords = question.min_words ?? 100;
    const maxWords = question.max_words ?? 150;
    const wordCount = value.trim() ? value.trim().split(/\s+/).length : 0;
    const inRange = wordCount >= minWords && wordCount <= maxWords;

    const handleChange = (text: string) => {
        setValue(text);
        onAnswer(question.id, text);
    };

    return (
        <div className="space-y-4">
            {/* Professor prompt */}
            <div className="rounded-xl border-2 border-primary/20 bg-primary/5 p-4">
                <div className="mb-2 flex items-center gap-2">
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                        P
                    </div>
                    <span className="text-sm font-bold">Professeur</span>
                </div>
                <p className="text-sm leading-relaxed">{question.professor_prompt}</p>
            </div>

            {/* Student posts */}
            <div className="space-y-3">
                {(Array.isArray(question.student_posts) ? question.student_posts : []).map((post, i) => {
                    const name = coerceOption(post?.name);
                    const text = coerceOption(post?.text);
                    return (
                        <div key={i} className="rounded-xl border bg-muted/30 p-4">
                            <div className="mb-2 flex items-center gap-2">
                                <div className="flex h-7 w-7 items-center justify-center rounded-full bg-muted text-xs font-bold text-foreground">
                                    {name[0] ?? '?'}
                                </div>
                                <span className="text-sm font-semibold">{name}</span>
                            </div>
                            <p className="text-sm leading-relaxed text-muted-foreground">{text}</p>
                        </div>
                    );
                })}
            </div>

            {/* Your contribution */}
            <div className="rounded-xl border-2 border-primary/30 p-4">
                <p className="mb-2 text-xs font-bold uppercase tracking-wider text-primary">Votre contribution</p>
                <p className="mb-3 text-sm">{question.writing_prompt}</p>
                <textarea
                    className="min-h-[120px] w-full rounded-lg border border-border bg-background p-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:opacity-50"
                    placeholder="Rédigez votre réponse..."
                    value={value}
                    onChange={(e) => handleChange(e.target.value)}
                    disabled={disabled}
                />
                <div className="mt-2 flex justify-between text-sm">
                    <span className={inRange ? 'font-medium text-green-600' : wordCount > maxWords ? 'font-medium text-red-500' : 'text-muted-foreground'}>
                        {wordCount} mot{wordCount !== 1 ? 's' : ''}
                    </span>
                    <span className="text-muted-foreground">{minWords}–{maxWords} mots</span>
                </div>
            </div>
        </div>
    );
}
