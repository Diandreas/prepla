import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState, useEffect } from 'react';

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
    words: Word[];
    reviewableCount: number;
}

export default function Dictionary({ words, reviewableCount }: Props) {
    const { t, i18n } = useTranslation();
    const [searchTerm, setSearchTerm] = useState('');
    const [isDiscovering, setIsDiscovering] = useState(false);
    

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

    const getStatusPercent = (status: string) => {
        switch(status) {
            case 'discovered': return 33;
            case 'learning': return 66;
            case 'mastered': return 100;
            default: return 0;
        }
    }

    return (
        <AppLayout>
            <Head title={t('dictionary.title')} />
            
            <div className="mx-auto max-w-4xl px-4 py-10">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                    <div>
                        <h1 className="text-3xl font-black text-slate-900 flex items-center gap-3">
                            <div className="h-12 w-12 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-200">
                                <Icon name="book" size={28} className="brightness-0 invert" />
                            </div>
                            {t('dictionary.title')}
                        </h1>
                        <p className="mt-2 text-slate-500 font-medium">{t('dictionary.subtitle')}</p>
                    </div>

                    <div className="flex gap-3">
                        <button
                            onClick={() => router.visit(route('dictionary.review_page'))}
                            disabled={reviewableCount === 0}
                            className="duo-press flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-black rounded-2xl disabled:opacity-40 disabled:cursor-not-allowed"
                            style={{ boxShadow: '0 4px 0 0 #1e4fa0' }}
                        >
                            <Icon name="award" size={20} className="brightness-0 invert" />
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
                            className="duo-press flex items-center gap-2 px-6 py-3 bg-orange-500 text-white font-black rounded-2xl disabled:opacity-50"
                            style={{ boxShadow: '0 4px 0 0 #c2620a' }}
                        >
                            <Icon name="sparkles" size={20} className="brightness-0 invert" />
                            {isDiscovering ? t('dictionary.loading') : t('dictionary.discover_btn')}
                        </button>
                    </div>
                </div>

                <div className="relative mb-8">
                    <div className="absolute inset-y-0 left-4 flex items-center pointer-events-none opacity-40">
                        <Icon name="search" size={20} />
                    </div>
                    <input 
                        type="text" 
                        placeholder={t('dictionary.search_placeholder')}
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="w-full pl-12 pr-6 py-4 bg-white border-2 border-slate-100 rounded-2xl focus:border-blue-500 focus:ring-0 font-bold transition-all shadow-sm"
                    />
                </div>

                {/* En cours d'apprentissage */}
                {learningWords.length > 0 && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {learningWords.map(renderCard)}
                    </div>
                )}

                {/* Mots maîtrisés — section séparée, repliée visuellement */}
                {masteredWords.length > 0 && (
                    <div className="mt-10">
                        <div className="flex items-center gap-2 mb-4">
                            <Icon name="check-circle" size={18} />
                            <h2 className="text-sm font-black uppercase tracking-widest text-green-600">
                                Maîtrisés ({masteredWords.length})
                            </h2>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 opacity-70">
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
            <div key={item.id} className="duo-row group p-6 rounded-3xl relative overflow-hidden">
                <div className="absolute top-0 right-0 p-3 flex gap-2">
                    <span className="px-2 py-1 bg-slate-50 text-slate-400 text-[10px] font-black rounded-lg uppercase tracking-wider">
                        {item.dictionary_word.skill_level}
                    </span>
                    <span className={`px-2 py-1 text-[10px] font-black rounded-lg uppercase tracking-wider ${item.status === 'mastered' ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600'}`}>
                        {item.status}
                    </span>
                </div>

                <div className="flex items-start gap-4 mb-4">
                    <div className="flex-1">
                        <div className="flex items-center gap-3">
                            <h3 className="text-2xl font-black text-slate-900 group-hover:text-blue-600 transition-colors uppercase">
                                {item.dictionary_word.word}
                            </h3>
                            <button
                                onClick={() => playAudio(item.dictionary_word_id)}
                                className="p-1.5 hover:bg-blue-50 text-blue-400 rounded-lg transition-colors"
                            >
                                <Icon name="headphones" size={16} />
                            </button>
                        </div>
                        <p className="text-blue-500 font-bold text-sm">
                            {item.dictionary_word.translation}
                        </p>
                    </div>
                </div>

                <p className="text-sm text-slate-600 leading-relaxed italic line-clamp-2 mb-4">
                    "{item.dictionary_word.definition}"
                </p>

                <div className="mt-auto border-t border-slate-50 pt-4">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-[10px] font-black uppercase text-slate-300 tracking-[0.1em]">{t('dictionary.mastery')}</p>
                        <p className="text-[10px] font-black text-blue-600">{getStatusPercent(item.status)}%</p>
                    </div>
                    <div className="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                        <div
                            className={`h-full transition-all duration-700 ${item.status === 'mastered' ? 'bg-green-500' : 'bg-blue-500'}`}
                            style={{ width: `${getStatusPercent(item.status)}%` }}
                        />
                    </div>
                </div>
            </div>
        );
    }
}
