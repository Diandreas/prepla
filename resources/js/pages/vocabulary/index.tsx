import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';

function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}

interface VocabWord {
    id: number;
    word: string;
    language_slug: string;
    definition: string | null;
    ipa: string | null;
    examples: string[];
    source: string;
    next_review_at: string | null;
    created_at: string;
}

interface Props {
    words: {
        data: VocabWord[];
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
    };
    dueCount: number;
}

const sourceBadge: Record<string, string> = {
    exercise: 'bg-indigo-100 text-indigo-700',
    dictionary: 'bg-blue-100 text-blue-700',
    manual: 'bg-slate-100 text-slate-600',
};

export default function VocabularyIndex({ words, dueCount }: Props) {
    const { t } = useTranslation();
    const [search, setSearch] = useState('');

    const filtered = words.data.filter(w =>
        w.word.toLowerCase().includes(search.toLowerCase()) ||
        (w.definition ?? '').toLowerCase().includes(search.toLowerCase())
    );

    const isDue = (word: VocabWord) => {
        if (!word.next_review_at) return false;
        return new Date(word.next_review_at) <= new Date();
    };

    return (
        <AppLayout>
            <Head title={t('vocabulary.title', 'Mon Vocabulaire')} />

            <div className="mx-auto max-w-4xl px-4 py-10 space-y-8">
                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h1 className="text-3xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                            <div className="h-12 w-12 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-200">
                                <Icon name="vocabulary" size={28} style={{ filter: 'brightness(0) invert(1)' }} />
                            </div>
                            {t('vocabulary.title', 'Mon Vocabulaire')}
                        </h1>
                        <p className="mt-2 text-slate-500 dark:text-slate-400 font-medium">
                            {words.total} {t('vocabulary.words_saved', 'mots sauvegardés')}
                        </p>
                    </div>
                    <div className="flex gap-3">
                        {dueCount > 0 && (
                            <Link
                                href={route('vocabulary.review')}
                                className="flex items-center gap-2 px-5 py-3 bg-amber-500 text-white font-black rounded-2xl shadow-lg shadow-amber-200 hover:scale-105 active:scale-95 transition-all"
                            >
                                <Icon name="review" size={18} style={{ filter: 'brightness(0) invert(1)' }} />
                                {t('vocabulary.review_due', 'Réviser')} ({dueCount})
                            </Link>
                        )}
                        <Link
                            href={route('vocabulary.learn')}
                            className="flex items-center gap-2 px-5 py-3 bg-indigo-600 text-white font-black rounded-2xl shadow-lg shadow-indigo-200 hover:scale-105 active:scale-95 transition-all"
                        >
                            <Icon name="sparkles" size={18} style={{ filter: 'brightness(0) invert(1)' }} />
                            {t('vocabulary.discover', 'Découvrir')}
                        </Link>
                    </div>
                </div>

                {/* Search */}
                <div className="relative">
                    <div className="absolute inset-y-0 left-4 flex items-center pointer-events-none opacity-40">
                        <Icon name="search" size={18} />
                    </div>
                    <input
                        type="text"
                        placeholder={t('vocabulary.search_placeholder', 'Rechercher un mot...')}
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="w-full pl-12 pr-6 py-4 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl focus:border-indigo-500 focus:ring-0 font-medium transition-all shadow-sm"
                    />
                </div>

                {/* Words grid */}
                {filtered.length === 0 ? (
                    <div className="text-center py-20 space-y-4">
                        <div className="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto">
                            <Icon name="vocabulary" size={32} className="opacity-30" />
                        </div>
                        <p className="text-slate-400 font-medium">
                            {search ? t('vocabulary.no_results', 'Aucun mot trouvé.') : t('vocabulary.empty', 'Aucun mot sauvegardé. Commencez à pratiquer !')}
                        </p>
                        {!search && (
                            <Link href={route('practice.index')} className="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-xl text-sm">
                                <Icon name="arrow-right" size={14} style={{ filter: 'brightness(0) invert(1)' }} />
                                {t('vocabulary.start_practice', 'Commencer à pratiquer')}
                            </Link>
                        )}
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {filtered.map((word) => (
                            <div
                                key={word.id}
                                className={`group p-5 bg-white dark:bg-slate-900 border-2 rounded-2xl transition-all hover:shadow-lg ${isDue(word) ? 'border-amber-300 dark:border-amber-700' : 'border-slate-100 dark:border-slate-800 hover:border-indigo-200'}`}
                            >
                                <div className="flex items-start justify-between gap-3 mb-3">
                                    <div>
                                        <h3 className="text-lg font-black text-slate-900 dark:text-white group-hover:text-indigo-600 transition-colors">
                                            {word.word}
                                        </h3>
                                        {word.ipa && (
                                            <span className="text-xs text-slate-400 font-mono">{word.ipa}</span>
                                        )}
                                    </div>
                                    <div className="flex items-center gap-1.5 shrink-0">
                                        {isDue(word) && (
                                            <span className="px-2 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-black rounded-lg uppercase">
                                                À réviser
                                            </span>
                                        )}
                                        <span className={`px-2 py-0.5 text-[10px] font-black rounded-lg uppercase ${sourceBadge[word.source] ?? 'bg-slate-100 text-slate-500'}`}>
                                            {word.source}
                                        </span>
                                    </div>
                                </div>
                                {word.definition && (
                                    <p className="text-sm text-slate-600 dark:text-slate-400 italic leading-relaxed line-clamp-2">
                                        "{word.definition}"
                                    </p>
                                )}
                                {word.examples?.[0] && (
                                    <p className="mt-2 text-xs text-slate-400 dark:text-slate-500 line-clamp-1">
                                        ex. {word.examples[0]}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {words.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2 pt-4">
                        {words.current_page > 1 && (
                            <Link
                                href={`${route('vocabulary.index')}?page=${words.current_page - 1}`}
                                className="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors"
                            >
                                ←
                            </Link>
                        )}
                        <span className="text-sm text-slate-500 px-2">
                            {words.current_page} / {words.last_page}
                        </span>
                        {words.current_page < words.last_page && (
                            <Link
                                href={`${route('vocabulary.index')}?page=${words.current_page + 1}`}
                                className="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors"
                            >
                                →
                            </Link>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
