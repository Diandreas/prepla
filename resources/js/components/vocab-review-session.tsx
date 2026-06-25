import { useMemo, useState, useEffect } from 'react';

/**
 * Mixed vocabulary review session. For each word, a random exercise type is
 * picked so the learner meets the word in several ways (recognition, recall,
 * context, spelling, matching) — far better for retention than a single format.
 */

interface DictWord {
    word: string;
    language: string;
    definition: string;
    example: string;
    translation: string;
    skill_level?: string;
}
interface ReviewWord {
    id: number; // progress id
    dictionary_word_id: number;
    dictionary_word: DictWord;
}
interface Distractor {
    word: string;
    translation: string;
    definition: string;
}

type ExType = 'word2def' | 'def2word' | 'gapfill' | 'dictation' | 'translation';

interface Props {
    words: ReviewWord[];
    distractors: Distractor[];
    onPlayAudio: (wordId: number) => void;
    onFinish: (results: { progress_id: number; is_correct: boolean }[]) => void;
}

const SKY = '#4A90E2';
const GREEN = '#48b77b';
const OXFORD = '#1A2B48';

function shuffle<T>(arr: T[]): T[] {
    return [...arr].sort(() => Math.random() - 0.5);
}

// Pick the exercise type for a given word. Dictation needs audio; the rest are text.
function pickType(): ExType {
    const types: ExType[] = ['word2def', 'def2word', 'gapfill', 'dictation', 'translation'];
    return types[Math.floor(Math.random() * types.length)];
}

export function VocabReviewSession({ words, distractors, onPlayAudio, onFinish }: Props) {
    const [index, setIndex] = useState(0);
    const [results, setResults] = useState<{ progress_id: number; is_correct: boolean }[]>([]);
    const [typed, setTyped] = useState('');
    const [picked, setPicked] = useState<string | null>(null);
    const [checked, setChecked] = useState(false);
    const [isCorrect, setIsCorrect] = useState(false);

    // Phase 0: a Duolingo-style matching grid (target word ↔ French translation)
    // as a warm-up, then the per-word mixed exercises.
    const [matchingDone, setMatchingDone] = useState(false);
    const matchPairs = useMemo(
        () => words.slice(0, 5).map(rw => ({ word: rw.dictionary_word.word, fr: rw.dictionary_word.translation })),
        [words]
    );
    const leftCol = useMemo(() => shuffle(matchPairs.map(p => p.word)), [matchPairs]);
    const rightCol = useMemo(() => shuffle(matchPairs.map(p => p.fr)), [matchPairs]);
    const [selLeft, setSelLeft] = useState<string | null>(null);
    const [selRight, setSelRight] = useState<string | null>(null);
    const [matched, setMatched] = useState<Set<string>>(new Set());
    const [wrongPair, setWrongPair] = useState(false);

    const tryMatch = (word: string | null, fr: string | null) => {
        if (!word || !fr) return;
        const pair = matchPairs.find(p => p.word === word);
        if (pair && pair.fr === fr) {
            const next = new Set(matched); next.add(word);
            setMatched(next);
            setSelLeft(null); setSelRight(null);
            if (next.size === matchPairs.length) setTimeout(() => setMatchingDone(true), 500);
        } else {
            setWrongPair(true);
            setTimeout(() => { setWrongPair(false); setSelLeft(null); setSelRight(null); }, 600);
        }
    };

    // Freeze one exercise type per word for the whole session.
    const types = useMemo(() => words.map(() => pickType()), [words]);

    const w = words[index];
    const dw = w?.dictionary_word;
    const type = types[index];
    const isLast = index === words.length - 1;

    // Build MCQ options (correct + 3 distractors), depends on the exercise type.
    const options = useMemo(() => {
        if (!dw) return [];
        if (type === 'word2def') {
            const wrong = shuffle(distractors).slice(0, 3).map(d => d.definition).filter(Boolean);
            return shuffle([dw.definition, ...wrong]);
        }
        if (type === 'def2word') {
            const wrong = shuffle(distractors).slice(0, 3).map(d => d.word).filter(Boolean);
            return shuffle([dw.word, ...wrong]);
        }
        if (type === 'translation') {
            const wrong = shuffle(distractors).slice(0, 3).map(d => d.translation).filter(Boolean);
            return shuffle([dw.translation, ...wrong]);
        }
        return [];
    }, [dw, type, distractors]);

    // Play audio automatically for dictation.
    useEffect(() => {
        if (type === 'dictation' && w) onPlayAudio(w.dictionary_word_id);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [index]);

    if (!w) return null;

    // ── Phase 0: matching grid (target ↔ French) ──
    if (!matchingDone && matchPairs.length >= 2) {
        return (
            <div className="mx-auto max-w-lg px-4 py-8">
                <p className="text-sm font-black uppercase tracking-wide mb-1" style={{ color: SKY }}>Associe les paires</p>
                <p className="text-xs text-muted-foreground mb-6">Relie chaque mot à sa traduction</p>
                <div className="grid grid-cols-2 gap-3">
                    <div className="flex flex-col gap-2.5">
                        {leftCol.map((word) => {
                            const isMatched = matched.has(word);
                            const sel = selLeft === word;
                            return (
                                <button
                                    key={word}
                                    disabled={isMatched}
                                    onClick={() => { setSelLeft(word); tryMatch(word, selRight); }}
                                    className={`duo-press rounded-xl border-2 px-3 py-3 text-sm font-bold ${
                                        isMatched ? 'border-green-300 bg-green-50 text-green-600 opacity-60'
                                        : sel && wrongPair ? 'border-red-400 bg-red-50'
                                        : sel ? 'border-primary bg-primary/5' : 'border-gray-200'
                                    }`}
                                    style={{ boxShadow: isMatched ? 'none' : '0 3px 0 0 #e5e7eb' }}
                                >
                                    {word}
                                </button>
                            );
                        })}
                    </div>
                    <div className="flex flex-col gap-2.5">
                        {rightCol.map((fr) => {
                            const pairWord = matchPairs.find(p => p.fr === fr)?.word;
                            const isMatched = pairWord ? matched.has(pairWord) : false;
                            const sel = selRight === fr;
                            return (
                                <button
                                    key={fr}
                                    disabled={isMatched}
                                    onClick={() => { setSelRight(fr); tryMatch(selLeft, fr); }}
                                    className={`duo-press rounded-xl border-2 px-3 py-3 text-sm font-bold ${
                                        isMatched ? 'border-green-300 bg-green-50 text-green-600 opacity-60'
                                        : sel && wrongPair ? 'border-red-400 bg-red-50'
                                        : sel ? 'border-primary bg-primary/5' : 'border-gray-200'
                                    }`}
                                    style={{ boxShadow: isMatched ? 'none' : '0 3px 0 0 #e5e7eb' }}
                                >
                                    {fr}
                                </button>
                            );
                        })}
                    </div>
                </div>
                <button
                    onClick={() => setMatchingDone(true)}
                    className="mt-6 w-full rounded-xl py-2.5 text-xs font-bold text-muted-foreground hover:text-foreground"
                >
                    Passer →
                </button>
            </div>
        );
    }

    const gapText = dw.example
        ? dw.example.replace(new RegExp(`\\b${dw.word}\\b`, 'gi'), '_______')
        : `(${dw.definition})`;

    const correctValue =
        type === 'word2def' ? dw.definition
        : type === 'def2word' ? dw.word
        : type === 'translation' ? dw.translation
        : dw.word; // gapfill & dictation → type the word

    const check = () => {
        if (checked) return;
        let ok = false;
        if (type === 'gapfill' || type === 'dictation') {
            ok = typed.trim().toLowerCase() === dw.word.trim().toLowerCase();
        } else {
            ok = picked === correctValue;
        }
        setIsCorrect(ok);
        setChecked(true);
        const newResults = [...results, { progress_id: w.id, is_correct: ok }];
        setResults(newResults);

        setTimeout(() => {
            if (isLast) {
                onFinish(newResults);
            } else {
                setIndex(i => i + 1);
                setTyped('');
                setPicked(null);
                setChecked(false);
            }
        }, 1600);
    };

    const canCheck = (type === 'gapfill' || type === 'dictation') ? typed.trim().length > 0 : picked !== null;

    const prompt =
        type === 'word2def' ? 'Quelle est la bonne définition ?'
        : type === 'def2word' ? 'Quel mot correspond à cette définition ?'
        : type === 'translation' ? 'Quelle est la bonne traduction ?'
        : type === 'gapfill' ? 'Complète la phrase avec le bon mot'
        : ' Écoute et écris le mot';

    return (
        <div className="mx-auto max-w-lg px-4 py-8">
            {/* Progress */}
            <div className="mb-6 flex items-center justify-between">
                <span className="text-xs font-bold" style={{ color: SKY }}>RÉVISION VOCABULAIRE</span>
                <span className="text-xs font-bold text-muted-foreground">{index + 1} / {words.length}</span>
            </div>
            <div className="mb-8 h-2 rounded-full bg-gray-100 overflow-hidden">
                <div className="h-full rounded-full transition-all" style={{ width: `${(index / words.length) * 100}%`, background: SKY }} />
            </div>

            {/* Prompt */}
            <p className="text-sm font-black uppercase tracking-wide mb-3" style={{ color: OXFORD }}>{prompt}</p>

            {/* Stimulus */}
            <div className="rounded-2xl border-2 border-gray-100 bg-white p-6 mb-5 text-center">
                {type === 'word2def' && <p className="text-2xl font-black" style={{ color: OXFORD }}>{dw.word}</p>}
                {type === 'def2word' && <p className="text-base font-semibold" style={{ color: OXFORD }}>{dw.definition}</p>}
                {type === 'translation' && <p className="text-2xl font-black" style={{ color: OXFORD }}>{dw.word}</p>}
                {type === 'gapfill' && (
                    <>
                        <p className="text-base font-semibold" style={{ color: OXFORD }}>{gapText}</p>
                        {/* Hint: the definition of the word to find, so the learner
                            retrieves it from meaning rather than guessing blindly. */}
                        <p className="mt-3 text-xs italic text-muted-foreground border-t border-gray-100 pt-3">
                             {dw.definition}
                        </p>
                    </>
                )}
                {type === 'dictation' && (
                    <button
                        type="button"
                        onClick={() => onPlayAudio(w.dictionary_word_id)}
                        className="duo-press inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-black text-white"
                        style={{ background: SKY, boxShadow: '0 4px 0 0 #2a6fc0' }}
                    >
                         Réécouter
                    </button>
                )}
            </div>

            {/* Answer zone */}
            {(type === 'gapfill' || type === 'dictation') ? (
                <input
                    autoFocus
                    value={typed}
                    onChange={e => setTyped(e.target.value)}
                    onKeyDown={e => e.key === 'Enter' && canCheck && check()}
                    disabled={checked}
                    placeholder="Ta réponse…"
                    className="w-full rounded-xl border-2 border-gray-200 px-4 py-3 font-semibold focus:border-blue-400 focus:outline-none"
                />
            ) : (
                <div className="grid gap-2.5">
                    {options.map((opt, i) => {
                        const selected = picked === opt;
                        const showCorrect = checked && opt === correctValue;
                        const showWrong = checked && selected && opt !== correctValue;
                        return (
                            <button
                                key={i}
                                disabled={checked}
                                onClick={() => setPicked(opt)}
                                className={`duo-press rounded-xl border-2 px-4 py-3 text-left text-sm font-semibold ${
                                    showCorrect ? 'border-green-400 bg-green-50'
                                    : showWrong ? 'border-red-400 bg-red-50'
                                    : selected ? 'border-primary bg-primary/5' : 'border-gray-200'
                                }`}
                                style={{ boxShadow: selected ? `0 4px 0 0 #2a6fc0` : '0 3px 0 0 #e5e7eb' }}
                            >
                                {opt}
                            </button>
                        );
                    })}
                </div>
            )}

            {/* Feedback + check */}
            {checked ? (
                <div className={`mt-5 rounded-xl p-3 text-center font-black ${isCorrect ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}`}>
                    {isCorrect ? '✓ Correct !' : `✗ La réponse était : ${dw.word}`}
                </div>
            ) : (
                <button
                    onClick={check}
                    disabled={!canCheck}
                    className="duo-press mt-5 w-full rounded-xl py-3 text-sm font-black text-white disabled:opacity-40"
                    style={{ background: GREEN, boxShadow: '0 4px 0 0 #2d7d52' }}
                >
                    Vérifier
                </button>
            )}
        </div>
    );
}
