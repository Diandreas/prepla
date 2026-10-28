import { useState } from 'react';
import { normalizeOptions } from './normalize-options';
import { illustrationUrl } from '@/lib/illustration';

interface PictureMcqProps {
    question: {
        id: string;
        text?: string;
        // Either ready image URLs, or short prompts we turn into images.
        image_options?: string[];     // direct URLs
        image_prompts?: string[];     // descriptions → Pollinations
        // Les images deja fabriquees et rangees chez nous par
        // `prepla:fabriquer-medias`. Ce sont elles qu'il faut servir.
        image_options_urls?: string[];
        image_prompts_urls?: string[];
        options?: unknown;            // fallback text labels under each image
        correct_answer?: unknown;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

const liste = (v: unknown): string[] =>
    Array.isArray(v) ? v.filter((u): u is string => typeof u === 'string' && u.trim() !== '') : [];

export function PictureMcq({ question, onAnswer, selectedAnswer, disabled }: PictureMcqProps) {
    // L'ordre compte. `prepla:fabriquer-medias` range les images sur notre disque
    // et inscrit leurs adresses dans `*_urls` — mais ce composant ne les lisait
    // pas : il retombait sur les descriptions et rappelait le service tiers A
    // CHAQUE AFFICHAGE. Quand sa limite gratuite etait atteinte, l'apprenant
    // voyait « Free limit reached » a la place de l'image, en pleine epreuve.
    const fabriquees = [...liste(question.image_prompts_urls), ...liste(question.image_options_urls)];
    const candidates: string[] = fabriquees.length
        ? fabriquees
        : liste(question.image_options).length
            ? liste(question.image_options)
            : liste(question.image_prompts).map((p) => illustrationUrl(p, { largeur: 400, hauteur: 300 }));
    const labels = normalizeOptions(question.options);
    const correct = String(question.correct_answer ?? '').trim().toUpperCase();

    // Une image qui ne se charge pas laisse une carte vide : l'apprenant doit
    // alors choisir entre trois rectangles gris. Des qu'une seule echoue, on
    // repasse a la liste de mots, qui reste jouable. C'est arrive pour de vrai :
    // le fournisseur d'illustrations renvoyait « Free limit reached » a la
    // place de l'image.
    const [echecImage, setEchecImage] = useState(false);

    // Tout ou rien, meme raison. Avec une image pour trois choix, les images se
    // decalaient d'un cran par rapport aux libelles : on choisissait la bonne
    // image sous le mauvais mot.
    const urls = ! echecImage && candidates.length === labels.length ? candidates : [];

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
                            <img
                                src={url}
                                alt={`Option ${letter}`}
                                loading="lazy"
                                onError={() => setEchecImage(true)}
                                className="aspect-[4/3] w-full object-cover bg-muted"
                            />
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
