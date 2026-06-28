import { useTts } from '@/hooks/use-tts';
import { normalizeOptions } from './normalize-options';

interface ListenChooseResponseProps {
    question: {
        id: string;
        audio_text?: string;       // the prompt sentence/question heard
        options?: unknown;         // 4 possible replies (text)
        correct_answer?: unknown;  // letter
        audio_url?: string;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
    lang?: string;
}

/**
 * TOEFL 2026 "Listen and Choose a Response" : on entend une phrase/question, on
 * choisit la meilleure réponse (pragmatique). On peut écouter le prompt ET chaque
 * réponse via TTS.
 */
export function ListenChooseResponse({ question, onAnswer, selectedAnswer, disabled, lang = 'en' }: ListenChooseResponseProps) {
    const { speak, stop, isSpeaking } = useTts();
    const options = normalizeOptions(question.options);
    const correct = String(question.correct_answer ?? '').trim().toUpperCase();
    const prompt = question.audio_text ?? '';

    const playPrompt = () => {
        if (isSpeaking) { stop(); return; }
        if (question.audio_url) { new Audio(question.audio_url).play().catch(() => speak(prompt, lang)); }
        else speak(prompt, lang);
    };

    return (
        <div className="space-y-3">
            <p className="text-sm font-bold text-muted-foreground">Écoute, puis choisis la meilleure réponse.</p>

            <button onClick={playPrompt}
                className="flex w-full items-center justify-center gap-3 rounded-xl border-2 border-primary bg-primary/5 px-4 py-3 text-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                <span className="text-sm font-bold">{isSpeaking ? 'Lecture…' : 'Écouter'}</span>
            </button>

            <div className="grid gap-2">
                {options.map((opt, i) => {
                    const letter = String.fromCharCode(65 + i);
                    const sel = (selectedAnswer ?? '').toUpperCase() === letter;
                    const isCorrect = disabled && correct === letter;
                    const isWrong = disabled && sel && correct !== letter;
                    return (
                        <div key={i}
                            className={`flex items-center gap-2 rounded-xl border-2 p-2 ${
                                isCorrect ? 'border-emerald-400 bg-emerald-50'
                                : isWrong ? 'border-red-400 bg-red-50'
                                : sel ? 'border-primary bg-primary/5' : 'border-border'}`}>
                            <button onClick={() => !disabled && onAnswer(question.id, letter)} disabled={disabled}
                                className="flex flex-1 items-center gap-2.5 text-left text-sm">
                                <span className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-black ${sel || isCorrect ? 'bg-primary text-white' : 'bg-muted'}`}>{letter}</span>
                                {opt}
                            </button>
                            <button onClick={() => speak(opt, lang)} className="shrink-0 rounded-lg p-1.5 text-slate-400 hover:text-primary" title="Écouter cette réponse">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                            </button>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
