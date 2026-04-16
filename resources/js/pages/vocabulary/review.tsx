import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface VocabWord {
    id: number;
    word: string;
    language_slug: string;
    definition: string | null;
    ipa: string | null;
    examples: string[];
    next_review_at: string | null;
}

interface Props {
    words: VocabWord[];
}

const SKY = '#4A90E2';
const GREEN = '#48b77b';
const OXFORD = '#1A2B48';
const GOLD = '#F5A623';

export default function VocabularyReview({ words }: Props) {
    const [current, setCurrent] = useState(0);
    const [revealed, setRevealed] = useState(false);
    const [done, setDone] = useState(false);

    if (words.length === 0 || done) {
        return (
            <AppLayout>
                <Head title="Révision SM-2" />
                <div className="mx-auto max-w-lg px-4 py-16 text-center">
                    <p className="text-5xl mb-4">🎉</p>
                    <h1 className="text-2xl font-black mb-2" style={{ color: OXFORD }}>
                        {words.length === 0 ? 'Aucun mot à réviser' : 'Révision terminée !'}
                    </h1>
                    <p className="text-sm text-muted-foreground mb-8">
                        {words.length === 0
                            ? 'Tous vos mots sont à jour. Revenez plus tard !'
                            : `Vous avez révisé ${words.length} mot${words.length > 1 ? 's' : ''}.`}
                    </p>
                    <Link
                        href="/vocabulary"
                        className="inline-block rounded-2xl px-8 py-3 text-sm font-black text-white"
                        style={{ background: `linear-gradient(135deg, ${SKY}, #3478c8)` }}
                    >
                        Retour au lexique
                    </Link>
                </div>
            </AppLayout>
        );
    }

    const word = words[current];
    const isLast = current === words.length - 1;

    const handleRating = (rating: 'good' | 'hard' | 'again') => {
        const quality = rating === 'good' ? 5 : rating === 'hard' ? 3 : 1;
        router.post(`/vocabulary/${word.id}/review`, { quality }, {
            preserveScroll: true,
            onSuccess: () => {
                if (isLast) {
                    setDone(true);
                } else {
                    setCurrent(c => c + 1);
                    setRevealed(false);
                }
            },
        });
    };

    return (
        <AppLayout>
            <Head title="Révision SM-2" />
            <div className="mx-auto max-w-lg px-4 py-8">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <Link href="/vocabulary" className="text-xs font-bold" style={{ color: SKY }}>
                        ← Retour
                    </Link>
                    <span className="text-xs font-bold text-muted-foreground">
                        {current + 1} / {words.length}
                    </span>
                </div>

                {/* Progress bar */}
                <div className="mb-8 h-2 rounded-full bg-gray-100 overflow-hidden">
                    <div
                        className="h-full rounded-full transition-all"
                        style={{ width: `${((current) / words.length) * 100}%`, background: SKY }}
                    />
                </div>

                {/* Card */}
                <div
                    className="rounded-3xl p-10 text-center mb-6 shadow-sm border border-gray-100"
                    style={{ minHeight: 220 }}
                >
                    <p className="text-3xl font-black mb-2" style={{ color: OXFORD }}>{word.word}</p>
                    {word.ipa && (
                        <p className="text-sm text-muted-foreground mb-4 font-mono">{word.ipa}</p>
                    )}

                    {!revealed ? (
                        <button
                            onClick={() => setRevealed(true)}
                            className="mt-4 rounded-2xl px-6 py-2.5 text-sm font-black text-white"
                            style={{ background: `linear-gradient(135deg, ${SKY}, #3478c8)` }}
                        >
                            Afficher la définition
                        </button>
                    ) : (
                        <div className="mt-4 text-left">
                            {word.definition && (
                                <p className="text-sm font-semibold mb-3" style={{ color: OXFORD }}>
                                    {word.definition}
                                </p>
                            )}
                            {word.examples?.length > 0 && (
                                <p className="text-xs italic text-muted-foreground border-l-2 pl-3" style={{ borderColor: GOLD }}>
                                    {word.examples[0]}
                                </p>
                            )}
                        </div>
                    )}
                </div>

                {/* Rating buttons */}
                {revealed && (
                    <div className="grid grid-cols-3 gap-3">
                        <button
                            onClick={() => handleRating('again')}
                            className="rounded-2xl py-3 text-sm font-black text-white"
                            style={{ background: '#E74C3C', boxShadow: '0 3px 0 #b83228' }}
                        >
                            ✗ À revoir
                        </button>
                        <button
                            onClick={() => handleRating('hard')}
                            className="rounded-2xl py-3 text-sm font-black text-white"
                            style={{ background: GOLD, boxShadow: '0 3px 0 #c8841a' }}
                        >
                            ~ Difficile
                        </button>
                        <button
                            onClick={() => handleRating('good')}
                            className="rounded-2xl py-3 text-sm font-black text-white"
                            style={{ background: GREEN, boxShadow: '0 3px 0 #2d7d52' }}
                        >
                            ✓ Bien
                        </button>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
