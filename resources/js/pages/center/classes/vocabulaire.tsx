import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ilYA } from '@/lib/suivi';
import { useT } from '@/lib/i18n/traduire';
import { BilanVocabulaire, Etape, Etapes, RepartitionEtapes } from '@/lib/vocabulaire';

interface Classroom { id: number; name: string; level: string | null }
interface Eleve extends BilanVocabulaire { id: number; nom: string }
interface MotDifficile { mot: string | null; traduction: string | null; niveau: string | null; eleves: number; a_reviser: number }

/** Comme ilYA (lib/suivi), mais dans la langue de l'interface : « 3 days ago ». */
function ilYAEn(iso: string, locale: string): string {
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return '—';
    const debut = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
    const relatif = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });
    const jours = Math.round((debut(date) - debut(new Date())) / 86_400_000);
    if (jours > -31) return relatif.format(jours, 'day');
    const mois = Math.round(jours / 30);
    if (mois > -12) return relatif.format(mois, 'month');
    return relatif.format(Math.round(mois / 12), 'year');
}

/**
 * Le vocabulaire d'une classe : qui apprend ses mots, qui les laisse de cote,
 * et les mots que plusieurs eleves n'arrivent pas a fixer.
 */
export default function VocabulaireClasse({
    classroom,
    etapes,
    eleves,
    mots_difficiles,
}: {
    classroom: Classroom;
    etapes: Etapes;
    eleves: Eleve[];
    mots_difficiles: MotDifficile[];
}) {
    const { t, langue, locale } = useT();
    const etapesT = Object.fromEntries(Object.entries(etapes).map(([cle, libelle]) => [cle, t(libelle)])) as Record<Etape, string>;
    const sansMot = eleves.filter((e) => e.total === 0).length;
    const enRetard = eleves.filter((e) => e.laisses_de_cote > 0).length;

    return (
        <AppLayout>
            <Head title={t('Vocabulaire — {classe}', { classe: classroom.name })} />
            <div className="mx-auto w-full max-w-5xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('center.classes.show', classroom.id)} className="text-sm text-muted-foreground hover:text-foreground">
                        ← {classroom.name}
                    </Link>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">{t('Vocabulaire de la classe')}</h1>
                    <p className="text-sm text-muted-foreground">
                        {t(eleves.length > 1 ? '{n} élèves' : '{n} élève', { n: eleves.length })}
                        {sansMot > 0 && <> · {t('{n} sans aucun mot', { n: sansMot })}</>}
                        {enRetard > 0 && <> · {t("{n} avec des révisions en retard de plus d'une semaine", { n: enRetard })}</>}
                    </p>
                </div>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">{t('Par élève')}</CardTitle>
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        {eleves.length === 0 ? (
                            <p className="text-sm text-muted-foreground">{t('Aucun élève dans cette classe.')}</p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-border text-left text-xs text-muted-foreground">
                                        <th className="py-2 pr-3 font-medium">{t('Élève')}</th>
                                        <th className="py-2 pr-3 text-right font-medium">{t('Mots')}</th>
                                        <th className="w-56 py-2 pr-3 font-medium">{t('Étapes')}</th>
                                        <th className="py-2 pr-3 text-right font-medium">{t('À réviser')}</th>
                                        <th className="py-2 pr-3 text-right font-medium">{t('Révisés (7 j)')}</th>
                                        <th className="py-2 font-medium">{t('Dernière révision')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {eleves.map((e) => (
                                        <tr key={e.id} className="border-b border-border/60 last:border-0">
                                            <td className="py-2 pr-3">
                                                <Link href={route('center.students.vocabulaire', e.id)} className="font-medium hover:underline">
                                                    {e.nom}
                                                </Link>
                                            </td>
                                            <td className="py-2 pr-3 text-right tabular-nums">
                                                {e.total}
                                                {e.ajoutes_7_jours > 0 && <span className="ml-1 text-xs text-emerald-600 dark:text-emerald-400">+{e.ajoutes_7_jours}</span>}
                                            </td>
                                            <td className="py-2 pr-3">
                                                <RepartitionEtapes etapes={etapesT} parEtape={e.par_etape} total={e.total} />
                                            </td>
                                            <td
                                                className={`py-2 pr-3 text-right tabular-nums ${e.laisses_de_cote > 0 ? 'font-semibold text-rose-600 dark:text-rose-400' : ''}`}
                                                title={e.laisses_de_cote > 0 ? t("{n} en retard de plus d'une semaine", { n: e.laisses_de_cote }) : undefined}
                                            >
                                                {e.a_reviser}
                                            </td>
                                            <td className="py-2 pr-3 text-right tabular-nums">{e.revises_7_jours}</td>
                                            <td className="py-2 text-muted-foreground">{e.derniere_revision ? (langue === 'en' ? ilYAEn(e.derniere_revision, locale) : ilYA(e.derniere_revision)) : t('Jamais')}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">{t('Mots qui résistent à plusieurs élèves')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {mots_difficiles.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {t("Aucun mot n'est encore « en cours » chez au moins deux élèves.")}
                            </p>
                        ) : (
                            <div className="divide-y divide-border/60">
                                {mots_difficiles.map((m, i) => (
                                    <div key={`${m.mot}-${i}`} className="flex flex-wrap items-baseline justify-between gap-2 py-2 text-sm">
                                        <div className="min-w-0">
                                            <span className="font-medium">{m.mot}</span>
                                            {m.niveau && <span className="ml-2 text-xs text-muted-foreground">{m.niveau}</span>}
                                            {m.traduction && <span className="ml-2 text-xs text-muted-foreground">— {m.traduction}</span>}
                                        </div>
                                        <span className="text-xs text-muted-foreground tabular-nums">
                                            {t('en cours chez {n} élèves', { n: m.eleves })}
                                            {m.a_reviser > 0 && <> · {t('{n} à réviser', { n: m.a_reviser })}</>}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
