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
}

export default function Dictionary({ words }: Props) {
    const { t } = useTranslation();
    const [searchTerm, setSearchTerm] = useState('');
    const [reviewingWord, setReviewingWord] = useState<Word | null>(null);
    const [exercise, setExercise] = useState<string | null>(null);
    const [answer, setAnswer] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [feedback, setFeedback] = useState<{ success: boolean; message: string } | null>(null);

    const filteredWords = words.filter(w => 
        w.dictionary_word.word.toLowerCase().includes(searchTerm.toLowerCase()) ||
        w.dictionary_word.translation.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const startReview = async (item: Word) => {
        setReviewingWord(item);
        setExercise(null);
        setAnswer('');
        setFeedback(null);
        try {
            const res = await fetch(route('dictionary.review', item.id));
            const data = await res.json();
            setExercise(data.sentence);
        } catch (e) {
            setExercise("Erreur lors de la génération de l'exercice.");
        }
    };

    const submitAnswer = async () => {
        if (!reviewingWord || !answer) return;
        setIsSubmitting(true);
        try {
            const res = await fetch(route('dictionary.submit_review', reviewingWord.id), {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content 
                },
                body: JSON.stringify({ answer })
            });
            const data = await res.json();
            setFeedback({ success: data.success, message: data.message });
            if (data.success) {
                setTimeout(() => {
                    setReviewingWord(null);
                    router.reload();
                }, 1500);
            }
        } catch (e) {
            setFeedback({ success: false, message: "Erreur lors de l'envoi." });
        } finally {
            setIsSubmitting(false);
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
            <Head title="Mon Dictionnaire" />
            
            <div className="mx-auto max-w-4xl px-4 py-10">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                    <div>
                        <h1 className="text-3xl font-black text-slate-900 flex items-center gap-3">
                            <div className="h-12 w-12 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-200">
                                <Icon name="book" size={28} className="brightness-0 invert" />
                            </div>
                            Mon Dictionnaire
                        </h1>
                        <p className="mt-2 text-slate-500 font-medium">Apprentissage progressif du vocabulaire académique.</p>
                    </div>

                    <button 
                        onClick={() => router.post(route('dictionary.discover'))}
                        disabled={isSubmitting}
                        className="flex items-center gap-2 px-6 py-3 bg-orange-500 text-white font-black rounded-2xl shadow-lg shadow-orange-200 hover:scale-105 active:scale-95 transition-all disabled:opacity-50"
                    >
                        <Icon name="sparkles" size={20} className="brightness-0 invert" />
                        {isSubmitting ? 'CHARGEMENT...' : 'DÉCOUVRIR UN MOT'}
                    </button>
                </div>

                <div className="relative mb-8">
                    <div className="absolute inset-y-0 left-4 flex items-center pointer-events-none opacity-40">
                        <Icon name="search" size={20} />
                    </div>
                    <input 
                        type="text" 
                        placeholder="Rechercher un mot ou une traduction..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="w-full pl-12 pr-6 py-4 bg-white border-2 border-slate-100 rounded-2xl focus:border-blue-500 focus:ring-0 font-bold transition-all shadow-sm"
                    />
                </div>

                {filteredWords.length === 0 ? (
                    <div className="text-center py-20 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
                        <div className="mx-auto h-20 w-20 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                            <Icon name="book" size={40} className="opacity-20" />
                        </div>
                        <h3 className="text-xl font-bold text-slate-400">Aucun mot trouvé</h3>
                        <p className="text-slate-400 mt-1">Commencez par découvrir de nouveaux mots !</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {filteredWords.map((item) => (
                            <div key={item.id} className="group p-6 bg-white border-2 border-slate-100 rounded-3xl hover:border-blue-500 transition-all hover:shadow-xl hover:shadow-blue-500/5 relative overflow-hidden">
                                <div className="absolute top-0 right-0 p-3 flex gap-2">
                                    <span className="px-2 py-1 bg-slate-50 text-slate-400 text-[10px] font-black rounded-lg uppercase tracking-wider">
                                        {item.dictionary_word.skill_level}
                                    </span>
                                    <span className={`px-2 py-1 text-[10px] font-black rounded-lg uppercase tracking-wider ${item.status === 'mastered' ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600'}`}>
                                        {item.status}
                                    </span>
                                </div>

                                <div className="flex items-start gap-4 mb-6">
                                    <div className="flex-1">
                                        <h3 className="text-2xl font-black text-slate-900 group-hover:text-blue-600 transition-colors uppercase">
                                            {item.dictionary_word.word}
                                        </h3>
                                        <p className="text-blue-500 font-bold text-sm">
                                            {item.dictionary_word.translation}
                                        </p>
                                    </div>
                                    <button 
                                        onClick={() => startReview(item)}
                                        className="px-4 py-2 bg-blue-600 text-white text-[11px] font-black rounded-xl shadow-lg shadow-blue-100 hover:scale-105 active:scale-95 transition-all"
                                    >
                                        S'ENTRAÎNER
                                    </button>
                                </div>
                                
                                <div className="space-y-3 mb-6">
                                    <div>
                                        <p className="text-[10px] font-black uppercase text-slate-300 tracking-[0.1em] mb-1">Définition</p>
                                        <p className="text-sm text-slate-600 leading-relaxed italic line-clamp-2" title={item.dictionary_word.definition}>
                                            "{item.dictionary_word.definition}"
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-auto border-t border-slate-50 pt-4">
                                    <div className="flex items-center justify-between mb-2">
                                        <p className="text-[10px] font-black uppercase text-slate-300 tracking-[0.1em]">Maîtrise</p>
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
                        ))}
                    </div>
                )}
            </div>

            {/* Review Modal */}
            {reviewingWord && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in duration-300">
                    <div className="w-full max-w-lg bg-white rounded-[32px] shadow-2xl p-8 animate-in zoom-in-95 duration-300">
                        <div className="flex items-center justify-between mb-6">
                            <h2 className="text-xl font-black text-slate-900 flex items-center gap-2">
                                <Icon name="sparkles" size={24} />
                                Exercice d'assimilation
                            </h2>
                            <button onClick={() => setReviewingWord(null)} className="p-2 hover:bg-slate-100 rounded-full transition-colors">
                                <Icon name="x" size={20} className="opacity-40" />
                            </button>
                        </div>

                        {!exercise ? (
                            <div className="py-12 flex flex-col items-center gap-4 text-center">
                                <div className="h-12 w-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
                                <p className="font-bold text-slate-400">L'IA prépare votre exercice...</p>
                            </div>
                        ) : (
                            <div className="space-y-6">
                                <div className="p-6 bg-slate-50 rounded-2xl border-2 border-slate-100">
                                    <p className="text-lg font-medium text-slate-800 leading-relaxed italic">
                                        {exercise}
                                    </p>
                                    <p className="mt-4 text-[10px] font-black uppercase text-slate-400 tracking-widest">
                                        Astuce : Le mot commence par "{reviewingWord.dictionary_word.word[0].toUpperCase()}"
                                    </p>
                                </div>

                                <div>
                                    <label className="block text-xs font-black uppercase text-slate-400 mb-2">Votre réponse</label>
                                    <input 
                                        autoFocus
                                        type="text" 
                                        value={answer}
                                        onChange={(e) => setAnswer(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && submitAnswer()}
                                        placeholder="Tapez le mot manquant..."
                                        className="w-full px-5 py-4 bg-white border-2 border-slate-100 rounded-2xl focus:border-blue-500 focus:ring-0 font-bold text-lg transition-all"
                                        disabled={isSubmitting || !!feedback?.success}
                                    />
                                </div>

                                {feedback && (
                                    <div className={`p-4 rounded-xl font-bold text-sm animate-in slide-in-from-top-4 duration-300 ${feedback.success ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}`}>
                                        {feedback.message}
                                    </div>
                                )}

                                <button 
                                    onClick={submitAnswer}
                                    disabled={!answer || isSubmitting || !!feedback?.success}
                                    className="w-full py-4 bg-blue-600 text-white font-black rounded-2xl shadow-xl shadow-blue-200 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50"
                                >
                                    {isSubmitting ? 'Vérification...' : 'VÉRIFIER'}
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
