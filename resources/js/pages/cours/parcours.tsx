import AppLayout from '@/layouts/app-layout';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Circle, Clock, Lock } from 'lucide-react';
import type { TFunction } from 'i18next';
import { useTranslation } from 'react-i18next';
import { LIBELLES } from './seance';

export interface SeanceDuParcours {
    id: number;
    titre: string;
    description: string | null;
    obligatoire: boolean;
    duree_minutes: number | null;
    types: string[];
    nb_blocs: number;
    limite_at: string | null;
    finie: boolean;
    ouverte: boolean;
    raison: { code: 'debut' | 'date' | 'completion' | 'quiz'; valeur: string; seuil: number | null } | null;
    score: number | null;
    blocs_faits: number;
    en_retard: boolean;
}
export interface ParcoursEleve {
    id: number;
    titre: string;
    description: string | null;
    total: number;
    finies: number;
    termine: boolean;
    prochaine: SeanceDuParcours | null;
    modules: { id: number; titre: string; description: string | null; finies: number; total: number; seances: SeanceDuParcours[] }[];
}

/** « audio + texte, 12 min » */
export function resume(s: SeanceDuParcours, t: TFunction): string {
    const types = s.types.map((ty) => (ty === 'audio_texte' ? t('courses.type.audio_text', 'audio + texte') : t(`courses.type.${ty}`, LIBELLES[ty] ?? ty).toLowerCase()));
    return [types.join(' + '), s.duree_minutes ? t('courses.minutes', '{{n}} min', { n: s.duree_minutes }) : null].filter(Boolean).join(', ');
}

export function raisonCadenas(s: SeanceDuParcours, t: TFunction): string | null {
    if (!s.raison) return null;
    const date = (v: string) => new Date(v).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
    switch (s.raison.code) {
        case 'debut':
        case 'date':
            return t('courses.lock_date', 'Disponible le {{date}}', { date: date(s.raison.valeur) });
        case 'completion':
            return t('courses.lock_completion', 'Terminez d’abord « {{titre}} »', { titre: s.raison.valeur });
        case 'quiz':
            return t('courses.lock_quiz', 'Obtenez {{n}} % aux quiz de « {{titre}} »', { n: s.raison.seuil, titre: s.raison.valeur });
    }
}

export function ProchainCours({ parcours }: { parcours: ParcoursEleve }) {
    const { t } = useTranslation();
    const p = parcours.prochaine;
    if (!p) return null;
    return (
        <Link href={`/mes-cours/seances/${p.id}`} className="block rounded-xl border-2 border-primary/40 bg-primary/5 p-4 transition hover:border-primary">
            <p className="text-xs font-semibold uppercase tracking-wide text-primary">{t('courses.next_lesson', 'Prochain cours')}</p>
            <p className="mt-1 text-lg font-semibold">{p.titre}</p>
            <p className="text-sm text-muted-foreground">{resume(p, t)}</p>
            <span className="mt-2 inline-flex items-center gap-1 text-sm font-medium text-primary">
                {p.blocs_faits > 0 ? t('courses.resume', 'Reprendre') : t('courses.start', 'Commencer')} <ArrowRight className="size-4" />
            </span>
        </Link>
    );
}

export default function Parcours({ parcours }: { parcours: ParcoursEleve }) {
    const { t } = useTranslation();
    const { flash } = usePage().props as { flash?: { error?: string } };
    const pct = parcours.total ? Math.round((parcours.finies / parcours.total) * 100) : 0;

    return (
        <AppLayout>
            <Head title={parcours.titre} />
            <div className="mx-auto w-full max-w-3xl space-y-6 p-4 md:p-6">
                <div>
                    <Link href="/mes-cours" className="text-sm text-muted-foreground hover:text-foreground">← {t('courses.my_courses', 'Mes cours')}</Link>
                    <h1 className="mt-2 text-2xl font-bold tracking-tight">{parcours.titre}</h1>
                    {parcours.description && <p className="mt-1 text-sm text-muted-foreground">{parcours.description}</p>}
                    <div className="mt-3 h-2 w-full overflow-hidden rounded-full bg-muted" role="progressbar" aria-valuenow={pct} aria-valuemin={0} aria-valuemax={100}>
                        <div className="h-full bg-primary" style={{ width: `${pct}%` }} />
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">{t('courses.sessions_done', '{{done}} / {{total}} séances terminées', { done: parcours.finies, total: parcours.total })}</p>
                </div>

                {flash?.error && <div className="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950/30 dark:text-amber-200">{flash.error}</div>}

                {parcours.termine ? (
                    <p className="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">{t('courses.path_done', 'Parcours terminé, bravo !')}</p>
                ) : (
                    <ProchainCours parcours={parcours} />
                )}

                {parcours.modules.map((m) => (
                    <section key={m.id} className="space-y-2">
                        <div className="flex items-baseline justify-between gap-2">
                            <h2 className="text-lg font-semibold">{m.titre}</h2>
                            <span className="text-xs text-muted-foreground">{m.finies} / {m.total}</span>
                        </div>
                        {m.description && <p className="text-sm text-muted-foreground">{m.description}</p>}
                        <ul className="space-y-2">
                            {m.seances.map((s) => {
                                const contenu = (
                                    <div className="flex items-start gap-3">
                                        {s.finie ? <CheckCircle2 className="mt-0.5 size-5 shrink-0 text-emerald-600" /> : s.ouverte ? <Circle className="mt-0.5 size-5 shrink-0 text-primary" /> : <Lock className="mt-0.5 size-5 shrink-0 text-muted-foreground" aria-label={t('courses.locked', 'Verrouillée')} />}
                                        <div className="min-w-0 flex-1">
                                            <p className="font-medium">
                                                {s.titre}
                                                {!s.obligatoire && <span className="ml-2 text-xs font-normal text-muted-foreground">{t('courses.optional', 'Facultatif')}</span>}
                                            </p>
                                            <p className="text-xs text-muted-foreground">{resume(s, t)}{s.score !== null ? ` · ${s.score} %` : ''}</p>
                                            {!s.ouverte && <p className="mt-1 text-xs text-muted-foreground">{raisonCadenas(s, t)}</p>}
                                            {s.limite_at && !s.finie && (
                                                <p className={`mt-1 inline-flex items-center gap-1 text-xs ${s.en_retard ? 'text-destructive' : 'text-muted-foreground'}`}>
                                                    <Clock className="size-3" /> {t('courses.deadline', 'À faire avant le {{date}}', { date: new Date(s.limite_at).toLocaleDateString() })}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                );
                                return (
                                    <li key={s.id}>
                                        {s.ouverte ? (
                                            <Link href={`/mes-cours/seances/${s.id}`} className="block rounded-lg border bg-card p-3 transition hover:border-primary/40">{contenu}</Link>
                                        ) : (
                                            <div className="rounded-lg border bg-muted/40 p-3 opacity-80">{contenu}</div>
                                        )}
                                    </li>
                                );
                            })}
                        </ul>
                    </section>
                ))}
            </div>
        </AppLayout>
    );
}
