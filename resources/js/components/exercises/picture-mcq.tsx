import { Check, X } from 'lucide-react';
import { useState } from 'react';
import { ChoiceOption, lettreDuChoix, verdictDuChoix } from './choice-option';
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
    /** Correction montree ; par defaut `disabled`, comme avant (voir mcq.tsx). */
    revealed?: boolean;
}

const liste = (v: unknown): string[] =>
    Array.isArray(v) ? v.filter((u): u is string => typeof u === 'string' && u.trim() !== '') : [];

export function PictureMcq({ question, onAnswer, selectedAnswer, disabled, revealed = disabled }: PictureMcqProps) {
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
        // Pas d'images : la liste de mots, avec le MEME choix que le QCM. Avant, ces
        // questions changeaient de style en cours d'exercice (« A. » en gras, sans
        // pastille, sans etat annonce ni correction).
        return (
            <div className="space-y-4">
                {question.text && <p data-enonce className="lecteur-enonce whitespace-pre-line">{question.text}</p>}
                <div className="grid gap-2.5">
                    {labels.map((opt, i) => {
                        const letter = lettreDuChoix(i);
                        const sel = (selectedAnswer ?? '').toUpperCase() === letter;
                        return (
                            <ChoiceOption
                                key={i}
                                index={i}
                                letter={letter}
                                selected={sel}
                                verdict={verdictDuChoix(!!revealed, correct, letter, sel)}
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

    return (
        <div className="space-y-4">
            {question.text && <p data-enonce className="lecteur-enonce whitespace-pre-line">{question.text}</p>}
            {/* Trois colonnes des le petit ecran et une hauteur bornee : en deux
                colonnes, chaque image faisait 418 px de large sur ordinateur, la
                troisieme passait seule a la ligne et « Suivant » tombait tres bas. */}
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                {urls.map((url, i) => {
                    const letter = lettreDuChoix(i);
                    const sel = (selectedAnswer ?? '').toUpperCase() === letter;
                    const verdict = verdictDuChoix(!!revealed, correct, letter, sel);
                    const isCorrect = verdict === 'juste';
                    const isWrong = verdict === 'faux';
                    return (
                        <button
                            key={i}
                            type="button"
                            data-choix={i}
                            aria-pressed={sel}
                            onClick={() => !disabled && onAnswer(question.id, letter)}
                            disabled={disabled}
                            className={`relative overflow-hidden rounded-2xl border-[3px] bg-card text-left transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background ${
                                isCorrect ? 'border-emerald-600 dark:border-emerald-400'
                                : isWrong ? 'border-red-600 dark:border-red-400'
                                : sel ? 'border-primary' : 'border-border enabled:hover:border-primary/60'}`}
                        >
                            <img
                                src={url}
                                alt={labels[i] ? `Option ${letter} : ${labels[i]}` : `Option ${letter}`}
                                loading="lazy"
                                onError={() => setQuestionEnEchec(question.id)}
                                className="aspect-[4/3] max-h-48 w-full bg-muted object-cover"
                            />
                            {/* Pastille lisible dans les deux themes : fond et texte fixes.
                                « bg-white/90 text-foreground » donnait blanc sur blanc en sombre. */}
                            <span
                                aria-hidden="true"
                                className={`absolute left-2 top-2 flex h-7 w-7 items-center justify-center rounded-full text-sm font-black shadow ${
                                    isCorrect ? 'bg-emerald-700 text-white' : isWrong ? 'bg-red-700 text-white' : sel ? 'bg-primary text-primary-foreground' : 'bg-white text-slate-900'
                                }`}
                            >
                                {letter}
                            </span>
                            {isCorrect && (
                                <span className="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-700 text-white shadow">
                                    <Check aria-hidden="true" className="h-4 w-4" />
                                    <span className="sr-only">Bonne réponse</span>
                                </span>
                            )}
                            {isWrong && (
                                <span className="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-red-700 text-white shadow">
                                    <X aria-hidden="true" className="h-4 w-4" />
                                    <span className="sr-only">Réponse incorrecte</span>
                                </span>
                            )}
                            {labels[i] && <span className="block px-2 py-1.5 text-center text-[0.8125rem] font-medium">{labels[i]}</span>}
                            {/* Les photographies de Wikimedia Commons sont souvent sous CC BY ou
                                CC BY-SA : citer l'auteur et la licence n'est pas une politesse,
                                c'est la condition pour s'en servir. Vide quand rien n'est du. */}
                            {credits[i] && (
                                <span className="block px-2 pb-1.5 text-center text-xs leading-tight text-muted-foreground">
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
