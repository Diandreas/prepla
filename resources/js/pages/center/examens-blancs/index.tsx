import { Head, Link } from '@inertiajs/react';
import { FileCheck2, Plus } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { EnTetePage, EtatVide } from '@/components/center/liste-centre';
import { useT } from '@/lib/i18n/traduire';

interface ExamenBlanc {
    id: number;
    title: string;
    exam: string | null;
    level: string | null;
    published: boolean;
    items_count: number;
    passages_count: number;
    duree: number;
    creator: string | null;
}

/** Les examens blancs du centre : seulement les siens, jamais ceux d'un autre centre. */
export default function ExamensBlancs({ examens }: { examens: ExamenBlanc[] }) {
    const { t } = useT();

    const creer = (
        <Button asChild>
            <Link href="/center/examens-blancs/creer">
                <Plus className="mr-1 h-4 w-4" aria-hidden="true" /> {t('Composer un examen blanc')}
            </Link>
        </Button>
    );

    return (
        <AppLayout>
            <Head title={t('Examens blancs')} />
            <div className="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
                <EnTetePage
                    titre={t('Examens blancs')}
                    sousTitre={t('Composez une épreuve complète, chronométrée, à partir de la structure officielle de l’examen.')}
                    actions={creer}
                />

                {examens.length === 0 ? (
                    <EtatVide
                        icone={FileCheck2}
                        titre={t('Aucun examen blanc pour le moment')}
                        texte={t('Choisissez un examen et un niveau : les sections et les parties officielles vous sont proposées.')}
                        action={creer}
                    />
                ) : (
                    <ul className="space-y-2">
                        {examens.map((e) => (
                            <li key={e.id}>
                                <Link
                                    href={`/center/examens-blancs/${e.id}`}
                                    className="flex flex-col gap-2 rounded-xl border border-border bg-card p-4 transition hover:border-primary/40 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-semibold">{e.title}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {[e.exam, e.level].filter(Boolean).join(' · ')}
                                            {' · '}
                                            {t('{n} exercices', { n: e.items_count })}
                                            {' · '}
                                            {t('{n} min', { n: e.duree })}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-3 text-sm">
                                        <span className="text-muted-foreground">{t('{n} copies rendues', { n: e.passages_count })}</span>
                                        <span
                                            className={
                                                e.published
                                                    ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                    : 'rounded-full bg-muted px-2 py-0.5 text-xs font-semibold text-muted-foreground'
                                            }
                                        >
                                            {e.published ? t('Publié') : t('Brouillon')}
                                        </span>
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AppLayout>
    );
}
