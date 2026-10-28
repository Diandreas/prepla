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
        /** Le credit a afficher sous chaque image, quand sa licence l'exige. */
        image_credits?: unknown;
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
    const labels = normalizeOptions(question.options);

    // UNE source, pas un melange. `prepla:fabriquer-medias` range les images sur
    // notre disque et inscrit leurs adresses dans `*_urls` — ce composant ne les
    // lisait pas du tout : il retombait sur les descriptions et rappelait le
    // service tiers A CHAQUE AFFICHAGE, qui rendait « Free limit reached » en
    // pleine epreuve.
    //
    // On prend la premiere source qui porte exactement une image par choix. Les
    // additionner laissait les restes d'une ancienne tentative s'ajouter aux
    // nouvelles adresses : le compte ne tombait plus juste, et l'exercice
    // repassait aux mots alors que ses images etaient la.
    const sources: string[][] = [
        liste(question.image_options_urls),
        liste(question.image_prompts_urls),
        liste(question.image_options),
        liste(question.image_prompts).map((p) => illustrationUrl(p, { largeur: 400, hauteur: 300 })),
    ];
    const candidates: string[] = sources.find((s) => s.length === labels.length && labels.length > 0) ?? [];
    const credits = Array.isArray(question.image_credits)
        ? question.image_credits.map((c) => (typeof c === 'string' ? c : ''))
        : [];
    const correct = String(question.correct_answer ?? '').trim().toUpperCase();

    // Une image qui ne se charge pas laisse une carte vide : l'apprenant doit
    // alors choisir entre trois rectangles gris. Des qu'une seule echoue, on
    // repasse a la liste de mots, qui reste jouable.
    //
    // On retient QUELLE question a echoue, pas un simple oui/non : le lecteur
    // garde le meme composant d'une question a l'autre, et un echec au premier
    // item aurait prive de leurs images toutes les suivantes.
    const [questionEnEchec, setQuestionEnEchec] = useState<string | null>(null);
    const echecImage = questionEnEchec === question.id;

    const urls = echecImage ? [] : candidates;

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
                                onError={() => setQuestionEnEchec(question.id)}
                                className="aspect-[4/3] w-full object-cover bg-muted"
                            />
                            <span className={`absolute left-2 top-2 flex h-7 w-7 items-center justify-center rounded-full text-xs font-black ${sel || isCorrect ? 'bg-primary text-white' : 'bg-white/90 text-foreground'}`}>
                                {letter}
                            </span>
                            {labels[i] && <span className="block px-2 py-1 text-center text-[11px] font-medium">{labels[i]}</span>}
                            {/* Les photographies de Wikimedia Commons sont souvent sous CC BY ou
                                CC BY-SA : citer l'auteur et la licence n'est pas une politesse,
                                c'est la condition pour s'en servir. Vide quand rien n'est du. */}
                            {credits[i] && (
                                <span className="block px-2 pb-1 text-center text-[9px] leading-tight text-muted-foreground">
                                    {credits[i]}
                                </span>
                            )}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
