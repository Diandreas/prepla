import { normalizeOptions } from './normalize-options';

interface PictureMcqProps {
    question: {
        id: string;
        text?: string;
        // Either ready image URLs, or short prompts we turn into images.
        image_options?: string[];     // direct URLs
        image_prompts?: string[];     // descriptions → Pollinations
        options?: unknown;            // fallback text labels under each image
        correct_answer?: unknown;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

// Free image generation (no key) from a short description.
function imgUrl(prompt: string): string {
    const clean = prompt.replace(/\s+/g, ' ').trim();
    return `https://image.pollinations.ai/prompt/${encodeURIComponent(clean + ', simple clear illustration, white background, no text')}?width=400&height=300&nologo=true`;
}

export function PictureMcq({ question, onAnswer, selectedAnswer, disabled }: PictureMcqProps) {
    const urls: string[] =
        (Array.isArray(question.image_options) && question.image_options.length)
            ? question.image_options
            : (Array.isArray(question.image_prompts) ? question.image_prompts.map(imgUrl) : []);
    const labels = normalizeOptions(question.options);
    const correct = String(question.correct_answer ?? '').trim().toUpperCase();

    if (urls.length === 0) {
        // No images available → degrade gracefully to a text label list.
        return (
            <div className="space-y-3">
                {question.text && <p className="text-lg font-medium">{question.text}</p>}
                <div className="grid gap-2">
                    {labels.map((opt, i) => {
                        const letter = String.fromCharCode(65 + i);
                        const sel = (selectedAnswer ?? '').toUpperCase() === letter;
                        return (
                            <button key={i} onClick={() => !disabled && onAnswer(question.id, letter)} disabled={disabled}
                                className={`rounded-xl border-2 p-3 text-left text-sm ${sel ? 'border-primary bg-primary/5' : 'border-border'}`}>
                                <span className="mr-2 font-bold">{letter}.</span>{opt}
                            </button>
                        );
                    })}
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {question.text && <p className="text-lg font-medium">{question.text}</p>}
            <div className="grid grid-cols-2 gap-3">
                {urls.map((url, i) => {
                    const letter = String.fromCharCode(65 + i);
                    const sel = (selectedAnswer ?? '').toUpperCase() === letter;
                    const isCorrect = disabled && correct === letter;
                    const isWrong = disabled && sel && correct !== letter;
                    return (
                        <button
                            key={i}
                            onClick={() => !disabled && onAnswer(question.id, letter)}
                            disabled={disabled}
                            className={`relative overflow-hidden rounded-2xl border-4 transition-all ${
                                isCorrect ? 'border-emerald-400'
                                : isWrong ? 'border-red-400'
                                : sel ? 'border-primary' : 'border-transparent ring-1 ring-border'}`}
                        >
                            <img src={url} alt={`Option ${letter}`} loading="lazy" className="aspect-[4/3] w-full object-cover bg-muted" />
                            <span className={`absolute left-2 top-2 flex h-7 w-7 items-center justify-center rounded-full text-xs font-black ${sel || isCorrect ? 'bg-primary text-white' : 'bg-white/90 text-foreground'}`}>
                                {letter}
                            </span>
                            {labels[i] && <span className="block px-2 py-1 text-center text-[11px] font-medium">{labels[i]}</span>}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
