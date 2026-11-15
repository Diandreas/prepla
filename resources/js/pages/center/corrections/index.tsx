import { Head, Link, router } from '@inertiajs/react';
import { ClipboardCheck } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { EnTetePage, EtatVide } from '@/components/center/liste-centre';
import { useT } from '@/lib/i18n/traduire';

interface Production {
    id: number;
    eleve: string | null;
    competence: 'writing' | 'speaking';
    examen: string | null;
    note_ia: number;
    note_enseignant: string | null;
    date: string | null;
}

interface Props {
    productions: Production[];
    classes: Array<{ id: number; name: string }>;
    filtres: { classe: number | null; vue: 'a_corriger' | 'revues' };
}

/** La file des redactions et prises de parole a relire par l'equipe du centre. */
export default function ACorriger({ productions, classes, filtres }: Props) {
    const { t, locale } = useT();

    const filtrer = (changes: Partial<{ classe: number | null; vue: string }>) => {
        const suivant = { ...filtres, ...changes };
        const params: Record<string, string> = {};
        if (suivant.classe) params.classe = String(suivant.classe);
        if (suivant.vue === 'revues') params.vue = 'revues';
        router.get('/center/corrections', params, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title={t('À corriger')} />
            <div className="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
                <EnTetePage
                    titre={t('À corriger')}
                    sousTitre={t('Les rédactions et prises de parole de vos élèves : relisez, écoutez, donnez votre note et un commentaire.')}
                />

                <div className="flex flex-wrap items-center gap-3">
                    <div className="inline-flex rounded-lg border border-border p-1" role="tablist">
                        {(['a_corriger', 'revues'] as const).map((vue) => (
                            <button
                                key={vue}
                                type="button"
                                role="tab"
                                aria-selected={filtres.vue === vue}
                                onClick={() => filtrer({ vue })}
                                className={`rounded-md px-3 py-1.5 text-sm font-medium ${filtres.vue === vue ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'}`}
                            >
                                {vue === 'a_corriger' ? t('Non revues') : t('Déjà corrigées')}
                            </button>
                        ))}
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <span className="text-muted-foreground">{t('Classe')}</span>
                        <select
                            className="rounded-md border border-border bg-background px-2 py-1.5 text-sm"
                            value={filtres.classe ?? ''}
                            onChange={(e) => filtrer({ classe: e.target.value ? Number(e.target.value) : null })}
                        >
                            <option value="">{t('Toutes les classes')}</option>
                            {classes.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                    </label>
                </div>

                {productions.length === 0 ? (
                    <EtatVide
                        icone={ClipboardCheck}
                        titre={filtres.vue === 'revues' ? t('Aucune production corrigée') : t('Rien à corriger pour le moment')}
                        texte={t('Les rédactions et prises de parole de vos élèves apparaissent ici dès qu’ils les envoient.')}
                    />
                ) : (
                    <ul className="space-y-2">
                        {productions.map((p) => (
                            <li key={p.id}>
                                <Link
                                    href={`/center/corrections/${p.id}`}
                                    className="flex flex-col gap-2 rounded-xl border border-border bg-card p-4 transition hover:border-primary/40 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-semibold">{p.eleve}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {[p.competence === 'speaking' ? t('Oral') : t('Écrit'), p.examen, p.date ? new Date(p.date).toLocaleDateString(locale) : null]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 gap-4 text-sm tabular-nums">
                                        <span>
                                            {t('IA')} : <strong>{p.note_ia} %</strong>
                                        </span>
                                        {p.note_enseignant && (
                                            <span>
                                                {t('Enseignant')} : <strong>{p.note_enseignant}</strong>
                                            </span>
                                        )}
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
