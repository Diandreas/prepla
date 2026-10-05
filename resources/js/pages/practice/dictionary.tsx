import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import axios from 'axios';

function Icon({ name, size = 20, className }: { name: string; size?: number; className?: string }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain' }} />;
}

interface Word {
    id: number;
    dictionary_word_id: number;
    dictionary_word: {
        word: string;
        language: string;
        definition: string;
        example: string;
        translation: string;
        skill_level: string;
    };
    status: string;
    last_reviewed_at: string;
}

interface Props {
    language: string;
    words: Word[];
    reviewableCount: number;
}

export default function Dictionary({ words, reviewableCount, language }: Props) {
    const { t, i18n } = useTranslation();
    const [searchTerm, setSearchTerm] = useState('');
    const [isDiscovering, setIsDiscovering] = useState(false);
    const [lookupTerm, setLookupTerm] = useState('');
    const [lookupWord, setLookupWord] = useState<(Word['dictionary_word'] & { id: number }) | null>(null);
    const [lookupBusy, setLookupBusy] = useState(false);
    const [lookupMessage, setLookupMessage] = useState('');
    

    const filteredWords = words.filter(w =>
        w.dictionary_word.word.toLowerCase().includes(searchTerm.toLowerCase()) ||
        w.dictionary_word.translation.toLowerCase().includes(searchTerm.toLowerCase())
    );
    // Split: words still being learned vs. mastered (shown in a separate section).
    const learningWords = filteredWords.filter(w => w.status !== 'mastered');
    const masteredWords = filteredWords.filter(w => w.status === 'mastered');

    const playAudio = async (wordId: number) => {
        try {
            const res = await fetch(route('dictionary.audio', { word: wordId }));
            const data = await res.json();
            if (data.url) {
                const audio = new Audio(data.url);
                audio.play();
            }
        } catch (e) {
            console.error("Erreur audio");
        }
    };

    return (
        <AppLayout>
            <Head title="Mes mots" />
            
            <div className="mx-auto max-w-5xl px-3 py-3 sm:px-4 sm:py-5">
                <header className="mb-5 flex items-center gap-4 rounded-2xl border border-primary/15 bg-primary/5 p-4">
                    <img src="/illustrations/prepla-guide/welcome.png" alt="" width="80" height="80" className="h-20 w-20 shrink-0 object-contain" />
                    <div><h1 className="text-2xl font-extrabold">Mes mots</h1><p className="mt-1 text-sm text-muted-foreground">Des mots rencontrés dans tes séances, des exemples pour les réutiliser et de courtes révisions au bon moment.</p></div>
                </header>
                <form className="mb-4 flex gap-2" onSubmit={async e => {
                    e.preventDefault(); if (!lookupTerm.trim() || lookupBusy) return;
                    setLookupBusy(true); setLookupWord(null); setLookupMessage('');
                    try { const { data } = await axios.get(`/dictionary/lookup/${encodeURIComponent(language)}/${encodeURIComponent(lookupTerm.trim())}`); setLookupWord(data); }
                    catch { setLookupMessage('La recherche est indisponible. Réessaie dans un instant.'); }
                    finally { setLookupBusy(false); }
                }}>
                    <input aria-label="Chercher un nouveau mot" maxLength={100} value={lookupTerm} onChange={e => setLookupTerm(e.target.value)} placeholder="Que veut dire ce mot ?" className="min-w-0 flex-1 rounded-xl border border-border bg-card px-4 py-3 text-sm" />
                    <button disabled={lookupBusy || !lookupTerm.trim()} className="rounded-xl bg-primary px-4 text-sm font-bold text-primary-foreground disabled:opacity-50">{lookupBusy ? '…' : 'Chercher'}</button>
                </form>
                {lookupMessage && <p role="status" className="mb-4 text-sm text-muted-foreground">{lookupMessage}</p>}
                {lookupWord && <div className="mb-5 rounded-2xl border border-primary/20 bg-card p-4"><h2 className="font-bold">{lookupWord.word} · {lookupWord.translation}</h2><p className="mt-2 text-sm text-muted-foreground">{lookupWord.definition}</p><p className="mt-2 text-sm italic">{lookupWord.example}</p><button disabled={lookupBusy} className="mt-3 text-sm font-bold text-primary" onClick={async () => {
                    setLookupBusy(true);
                    try { await axios.post(route('dictionary.save'), { dictionary_word_id: lookupWord.id }); setLookupMessage('Mot gardé dans Mes mots.'); setLookupWord(null); router.reload({ only: ['words', 'reviewableCount'] }); }
                    catch { setLookupMessage('Le mot n’a pas été enregistré. Réessaie.'); }
                    finally { setLookupBusy(false); }
                }}>+ Garder ce mot</button></div>}
                {/* Title moved to the global header → only the actions remain here,
                    freeing the vertical space for the word list. */}
                <div className="flex gap-2 sm:gap-3 mb-3 sm:mb-4">
                    <button
                        onClick={() => router.visit(route('dictionary.review_page'))}
                        disabled={reviewableCount === 0}
                        className="duo-press flex flex-1 items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-black rounded-xl disabled:opacity-40 disabled:cursor-not-allowed"
                        style={{ boxShadow: '0 4px 0 0 #1e4fa0' }}
                    >
                        <Icon name="award" size={18} className="brightness-0 invert" />
                        {t('dictionary.review_btn_dynamic', { count: reviewableCount })}
                    </button>
                    <button
                        onClick={() => {
                            setIsDiscovering(true);
                            router.post(route('dictionary.discover'), {}, {
                                onFinish: () => setIsDiscovering(false)
                            });
                        }}
                        disabled={isDiscovering}
                        className="duo-press flex flex-1 items-center justify-center gap-2 px-4 py-2.5 bg-orange-500 text-white text-sm font-black rounded-xl disabled:opacity-50"
                        style={{ boxShadow: '0 4px 0 0 #c2620a' }}
                    >
                        <Icon name="sparkles" size={18} className="brightness-0 invert" />
                        {isDiscovering ? t('dictionary.loading') : t('dictionary.discover_btn')}
                    </button>
                </div>

                <div className="relative mb-3 sm:mb-4">
                    <div className="absolute inset-y-0 left-3.5 flex items-center pointer-events-none opacity-40">
                        <Icon name="search" size={16} />
                    </div>
                    <input
                        type="text"
                        placeholder={t('dictionary.search_placeholder')}
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-4 py-2.5 bg-white border-2 border-slate-100 rounded-xl focus:border-blue-500 focus:ring-0 font-bold text-sm transition-all shadow-sm"
                    />
                </div>

                {/* En cours d'apprentissage */}
                {learningWords.length > 0 && (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                        {learningWords.map(renderCard)}
                    </div>
                )}

                {/* Mots maîtrisés — section séparée, repliée visuellement */}
                {masteredWords.length > 0 && (
                    <div className="mt-5 sm:mt-6">
                        <div className="flex items-center gap-2 mb-2.5">
                            <Icon name="check-circle" size={16} />
                            <h2 className="text-xs font-black uppercase tracking-widest text-green-600">
                                Retenus après révision ({masteredWords.length})
                            </h2>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 opacity-70">
                            {masteredWords.map(renderCard)}
                        </div>
                    </div>
                )}

                {learningWords.length === 0 && masteredWords.length === 0 && (
                    <p className="text-center text-slate-400 font-medium py-12">
                        Aucun mot pour l'instant. Clique sur « Découvrir » pour commencer.
                    </p>
                )}
            </div>
        </AppLayout>
    );

    function renderCard(item: Word) {
        return (
            <div key={item.id} className="duo-row group p-3 rounded-xl relative overflow-hidden">
                {/* Header: word + translation on the left, audio + level on the right */}
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-1.5">
                            <h3 className="text-base font-black text-slate-900 group-hover:text-blue-600 transition-colors uppercase truncate">
                                {item.dictionary_word.word}
                            </h3>
                            <button
                                aria-label={`Écouter ${item.dictionary_word.word}`}
                                onClick={() => playAudio(item.dictionary_word_id)}
                                className="shrink-0 p-1 hover:bg-blue-50 text-blue-400 rounded-md transition-colors"
                            >
                                <Icon name="headphones" size={14} />
                            </button>
                        </div>
                        <p className="text-blue-500 font-bold text-xs truncate">
                            {item.dictionary_word.translation}
                        </p>
                    </div>
                    <span className="shrink-0 px-1.5 py-0.5 bg-slate-50 text-slate-400 text-[9px] font-black rounded uppercase tracking-wider">
                        {item.dictionary_word.skill_level}
                    </span>
                </div>

                <p className="mt-1.5 text-xs text-slate-500 leading-snug italic line-clamp-1">
                    "{item.dictionary_word.definition}"
                </p>

                <div className="mt-2 text-xs font-semibold text-muted-foreground">{item.status === 'mastered' ? 'Retenu · à revoir plus tard' : item.status === 'learning' ? 'À consolider' : 'Découvert'}</div>
                <details className="mt-3 text-xs"><summary className="cursor-pointer font-semibold text-primary">Voir le sens et un exemple</summary><p className="mt-2 text-muted-foreground">{item.dictionary_word.definition}</p><p className="mt-2 leading-relaxed">{item.dictionary_word.example || 'Pas encore d’exemple disponible.'}</p></details>
            </div>
        );
    }
}
