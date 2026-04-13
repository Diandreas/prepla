import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}

interface Props {
    language: string;
}

interface WordData {
    word: string;
    definition: string;
    ipa: string | null;
    translation: string | null;
    examples: string[];
}

export default function VocabularyLearn({ language }: Props) {
    const { t } = useTranslation();
    const [word, setWord] = useState<WordData | null>(null);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);

    const fetchNewWord = async () => {
        setLoading(true);
        try {
            const response = await fetch(route('vocabulary.random', { languageSlug: language }));
            if (response.ok) {
                const data = await response.json();
                setWord(data);
            }
        } catch (error) {
            console.error('Failed to fetch word:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchNewWord();
    }, []);

    const handleSave = () => {
        if (!word || saving) return;
        setSaving(true);
        router.post(route('vocabulary.store'), {
            word: word.word,
            language_slug: language,
            source: 'dictionary'
        }, {
            onSuccess: () => {
                setSaving(false);
                fetchNewWord();
            },
            onFinish: () => setSaving(false)
        });
    };

    return (
        <AppLayout>
            <Head title={t('vocabulary.learn_title', 'Découvrir du vocabulaire')} />

            <div className="mx-auto max-w-2xl px-4 py-10 space-y-8 text-center">
                <div className="space-y-2">
                    <h1 className="text-3xl font-black text-slate-900 dark:text-white">
                        {t('vocabulary.discover_words', 'Nouveaux mots')}
                    </h1>
                    <p className="text-slate-500 dark:text-slate-400 font-medium italic">
                        {t('vocabulary.discover_subtitle', 'Enrichissez votre lexique quotidiennement')}
                    </p>
                </div>

                <Card className="rounded-[40px] border-none shadow-2xl shadow-indigo-100 overflow-hidden bg-white dark:bg-slate-900 min-h-[400px] flex flex-col justify-center transition-all duration-500">
                    <CardContent className="p-10 space-y-8">
                        {loading ? (
                            <div className="flex flex-col items-center justify-center space-y-4 animate-pulse">
                                <div className="h-12 w-48 bg-slate-100 dark:bg-slate-800 rounded-full" />
                                <div className="h-4 w-32 bg-slate-100 dark:bg-slate-800 rounded-full opacity-50" />
                                <div className="h-20 w-full bg-slate-50 dark:bg-slate-800/50 rounded-3xl mt-8" />
                            </div>
                        ) : word ? (
                            <div className="animate-in fade-in zoom-in duration-500 space-y-8">
                                <div className="space-y-2">
                                    <Badge className="bg-indigo-100 text-indigo-700 hover:bg-indigo-100 px-4 py-1 rounded-full uppercase font-black tracking-widest border-none">
                                        {language}
                                    </Badge>
                                    <h2 className="text-6xl font-black text-slate-900 dark:text-white tracking-tight">
                                        {word.word}
                                    </h2>
                                    {word.ipa && (
                                        <p className="text-xl font-mono text-indigo-500 font-medium italic">
                                            /{word.ipa}/
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-4">
                                    <div className="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-[32px] relative group">
                                        <div className="absolute -top-3 left-6 px-3 py-1 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-full text-[10px] font-black uppercase text-slate-400 tracking-wider">
                                            {t('vocabulary.definition', 'Définition')}
                                        </div>
                                        <p className="text-lg text-slate-700 dark:text-slate-300 font-medium leading-relaxed italic">
                                            "{word.definition}"
                                        </p>
                                    </div>

                                    {word.examples && word.examples.length > 0 && (
                                        <div className="text-left space-y-2 px-4">
                                            <p className="text-[10px] font-black uppercase text-slate-400 tracking-wider ml-2">
                                                {t('vocabulary.example', 'Exemple')}
                                            </p>
                                            <p className="text-slate-500 dark:text-slate-400 leading-relaxed font-medium">
                                                • {word.examples[0]}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <p className="text-slate-400">Impossible de charger un mot.</p>
                                <Button onClick={fetchNewWord} variant="outline" className="rounded-2xl font-bold">Réessayer</Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="flex items-center justify-center gap-4 pt-4">
                    <Button
                        size="lg"
                        variant="outline"
                        onClick={fetchNewWord}
                        disabled={loading || saving}
                        className="h-16 px-8 rounded-3xl border-2 border-slate-100 font-black uppercase tracking-widest hover:bg-slate-50 hover:border-slate-200 transition-all active:scale-95 disabled:opacity-50"
                    >
                        {t('vocabulary.skip', 'Passer')}
                    </Button>
                    <Button
                        size="lg"
                        onClick={handleSave}
                        disabled={loading || saving || !word}
                        className="h-16 px-10 rounded-3xl bg-indigo-600 text-white font-black uppercase tracking-widest shadow-xl shadow-indigo-200 hover:bg-indigo-700 hover:shadow-indigo-300 transition-all active:scale-95 disabled:bg-slate-400 disabled:shadow-none"
                    >
                        {saving ? t('vocabulary.saving', 'Enregistrement...') : (
                            <div className="flex items-center gap-3">
                                <Icon name="check" size={20} style={{ filter: 'brightness(0) invert(1)' }} />
                                {t('vocabulary.add_word', 'Ajouter au lexique')}
                            </div>
                        )}
                    </Button>
                </div>

                <Link 
                    href={route('vocabulary.index')}
                    className="inline-block text-sm font-bold text-slate-400 hover:text-indigo-600 transition-colors"
                >
                    {t('vocabulary.back_to_list', 'Retour à ma liste')}
                </Link>
            </div>
        </AppLayout>
    );
}
