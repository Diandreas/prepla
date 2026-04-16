import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}

interface UserError {
    id: number;
    skill_type: string;
    prompt: string;
    user_answer: string;
    correct_answer: string;
    explanation: string | null;
    mastered: boolean;
    review_count: number;
    next_review_at: string | null;
    created_at: string;
}

interface PaginatedErrors {
    data: UserError[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

interface Props {
    errors: PaginatedErrors;
    errorsBySkill: Record<string, number>;
    errorsByCategory: Record<string, number>;
    dueForReviewCount: number;
}

const OXFORD = '#1A2B48';
const SKY = '#4A90E2';
const RED = '#E74C3C';
const GREEN = '#48b77b';
const GOLD = '#F5A623';

const skillColors: Record<string, string> = {
    reading:   'bg-blue-100 text-blue-700',
    listening: 'bg-purple-100 text-purple-700',
    writing:   'bg-green-100 text-green-700',
    grammar:   'bg-orange-100 text-orange-700',
    speaking:  'bg-pink-100 text-pink-700',
};

const skillIcons: Record<string, string> = {
    reading:   'book',
    listening: 'headphones',
    writing:   'writing',
    grammar:   'target',
    speaking:  'speaking',
};

export default function ErrorsIndex({ errors, errorsBySkill, errorsByCategory, dueForReviewCount }: Props) {
    const { t } = useTranslation();
    const totalErrors = errors.total;

    return (
        <AppLayout>
            <Head title="Centre de Récupération" />

            <div className="mx-auto max-w-3xl px-4 py-10 space-y-8">
                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div className="flex items-center gap-4">
                        <div className="h-12 w-12 rounded-2xl flex items-center justify-center shadow-lg" style={{ background: `linear-gradient(135deg, ${RED}, #c0392b)` }}>
                            <Icon name="x-circle" size={26} className="brightness-0 invert" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-black" style={{ color: OXFORD }}>Centre de Récupération</h1>
                            <p className="text-sm text-muted-foreground font-medium">
                                {totalErrors} erreur{totalErrors !== 1 ? 's' : ''} non maîtrisée{totalErrors !== 1 ? 's' : ''}
                            </p>
                        </div>
                    </div>

                    {dueForReviewCount > 0 && (
                        <Link
                            href="/errors/practice"
                            className="inline-flex items-center gap-2 rounded-2xl px-6 py-3 text-sm font-black text-white shadow-lg"
                            style={{ background: `linear-gradient(135deg, ${RED}, #c0392b)` }}
                        >
                            <Icon name="flame" size={18} className="brightness-0 invert" />
                            Réviser ({dueForReviewCount} dû{dueForReviewCount > 1 ? 's' : ''})
                        </Link>
                    )}
                    {dueForReviewCount === 0 && totalErrors > 0 && (
                        <Link
                            href="/errors/practice"
                            className="inline-flex items-center gap-2 rounded-2xl px-6 py-3 text-sm font-black text-white shadow-lg"
                            style={{ background: `linear-gradient(135deg, ${SKY}, #3478c8)` }}
                        >
                            <Icon name="target" size={18} className="brightness-0 invert" />
                            Pratiquer les erreurs
                        </Link>
                    )}
                </div>

                {/* Empty state */}
                {totalErrors === 0 && (
                    <div className="text-center py-20 space-y-4">
                        <div className="w-20 h-20 rounded-3xl flex items-center justify-center mx-auto" style={{ background: '#f0fdf4' }}>
                            <Icon name="check-circle" size={40} style={{ filter: 'invert(55%) sepia(60%) saturate(400%) hue-rotate(96deg)' }} />
                        </div>
                        <h2 className="text-xl font-black" style={{ color: OXFORD }}>Aucune erreur !</h2>
                        <p className="text-sm text-muted-foreground">Continuez à pratiquer pour identifier vos points à améliorer.</p>
                        <Link href="/practice" className="inline-block mt-4 rounded-2xl px-6 py-2.5 text-sm font-black text-white" style={{ background: `linear-gradient(135deg, ${SKY}, #3478c8)` }}>
                            Aller pratiquer
                        </Link>
                    </div>
                )}

                {/* Stats by skill */}
                {Object.keys(errorsBySkill).length > 0 && (
                    <div>
                        <h2 className="text-sm font-black uppercase tracking-widest mb-4" style={{ color: GOLD }}>Erreurs par compétence</h2>
                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            {Object.entries(errorsBySkill).map(([skill, count]) => (
                                <Link
                                    key={skill}
                                    href={`/errors/practice?skill=${skill}`}
                                    className="p-4 bg-white rounded-2xl border border-gray-100 hover:shadow-md transition-all group"
                                >
                                    <div className="flex items-center gap-2 mb-2">
                                        <span className={`p-1.5 rounded-lg ${skillColors[skill] ?? 'bg-gray-100 text-gray-600'}`}>
                                            <Icon name={skillIcons[skill] ?? 'book'} size={14} />
                                        </span>
                                        <span className="text-xs font-black uppercase tracking-wide text-muted-foreground">{skill}</span>
                                    </div>
                                    <p className="text-2xl font-black" style={{ color: RED }}>{count}</p>
                                    <p className="text-xs text-muted-foreground">erreur{count !== 1 ? 's' : ''}</p>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

                {/* Error list */}
                {errors.data.length > 0 && (
                    <div>
                        <h2 className="text-sm font-black uppercase tracking-widest mb-4" style={{ color: OXFORD }}>Erreurs récentes</h2>
                        <div className="space-y-3">
                            {errors.data.map((error) => (
                                <div key={error.id} className="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                                    <div className="px-5 py-3 border-b border-gray-50 flex items-center justify-between">
                                        <span className={`px-2.5 py-1 rounded-lg text-xs font-black uppercase tracking-wide ${skillColors[error.skill_type] ?? 'bg-gray-100 text-gray-600'}`}>
                                            {error.skill_type}
                                        </span>
                                        <div className="flex items-center gap-3">
                                            {error.next_review_at && (
                                                <span className="text-xs text-muted-foreground">
                                                    Prochain : {new Date(error.next_review_at).toLocaleDateString('fr-FR')}
                                                </span>
                                            )}
                                            <span className={`text-xs font-bold px-2 py-0.5 rounded-full ${error.mastered ? 'bg-green-100 text-green-700' : 'bg-red-50 text-red-600'}`}>
                                                {error.mastered ? 'Maîtrisé' : `${error.review_count}× revu`}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div className="p-3 rounded-xl" style={{ background: '#fef2f2' }}>
                                            <p className="text-xs font-bold uppercase tracking-wide mb-1" style={{ color: RED }}>Votre réponse</p>
                                            <p className="text-sm font-medium text-red-700 line-clamp-2">{error.user_answer}</p>
                                        </div>
                                        <div className="p-3 rounded-xl" style={{ background: '#f0fdf4' }}>
                                            <p className="text-xs font-bold uppercase tracking-wide mb-1" style={{ color: GREEN }}>Bonne réponse</p>
                                            <p className="text-sm font-medium text-green-700 line-clamp-2">{error.correct_answer}</p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Pagination info */}
                        {errors.last_page > 1 && (
                            <p className="text-center text-xs text-muted-foreground mt-4">
                                Page {errors.current_page} sur {errors.last_page}
                            </p>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
