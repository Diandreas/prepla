import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { useT } from '@/lib/i18n/traduire';
import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';

interface Cellule { finie: boolean; blocs_faits: number; score: number | null }
interface Eleve { id: number; name: string; finies: number; seances: Record<number, Cellule> }
interface Production {
    id: number; eleve: string | null; type: 'ecrit' | 'oral'; seance: string | null; consigne: string; texte: string | null; audio_url: string | null;
    statut: 'a_corriger' | 'corrige' | null; correction_ia: { score?: number; feedback?: string } | null; commentaire: string | null; note: number | null; rendu_le: string | null;
}
interface Props {
    parcours: { id: number; titre: string; classe: string | null };
    seances: { id: number; titre: string; nb_blocs: number }[];
    eleves: Eleve[];
    productions: Production[];
}

/** Suivi enseignant : qui a fini quelle séance, scores des quiz, productions à corriger. */
export default function Suivi({ parcours, seances, eleves, productions }: Props) {
    const { t } = useT();
    const aCorriger = productions.filter((p) => p.statut === 'a_corriger').length;

    return (
        <AppLayout>
            <Head title={t('Suivi du parcours')} />
            <div className="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-6">
                <div>
                    <Link href={`/center/cours/${parcours.id}`} className="text-sm text-muted-foreground hover:text-foreground">← {parcours.titre}</Link>
                    <h1 className="mt-2 text-2xl font-bold tracking-tight">{t('Suivi du parcours')}</h1>
                    {parcours.classe && <p className="text-sm text-muted-foreground">{parcours.classe}</p>}
                </div>

                {eleves.length === 0 ? (
                    <p className="text-sm text-muted-foreground">{t('Aucun élève dans cette classe.')}</p>
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50">
                                <tr>
                                    <th className="sticky left-0 bg-muted/50 p-2 text-left">{t('Élève')}</th>
                                    <th className="p-2 text-left">{t('Séances finies')}</th>
                                    {seances.map((s) => <th key={s.id} className="min-w-28 p-2 text-left font-medium">{s.titre}</th>)}
                                </tr>
                            </thead>
                            <tbody>
                                {eleves.map((e) => (
                                    <tr key={e.id} className="border-t">
                                        <td className="sticky left-0 bg-background p-2 font-medium">{e.name}</td>
                                        <td className="p-2">{e.finies} / {seances.length}</td>
                                        {seances.map((s) => {
                                            const c = e.seances[s.id];
                                            return (
                                                <td key={s.id} className="p-2">
                                                    {c?.finie ? (
                                                        <span className="inline-flex items-center gap-1 text-emerald-600"><CheckCircle2 className="size-4" /> {c.score !== null ? `${c.score} %` : t('Fini')}</span>
                                                    ) : (
                                                        <span className="text-muted-foreground">{c ? `${c.blocs_faits}/${s.nb_blocs}` : '–'}{c?.score !== null && c?.score !== undefined ? ` · ${c.score} %` : ''}</span>
                                                    )}
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <section className="space-y-3">
                    <h2 className="text-lg font-semibold">{t('À corriger')} {aCorriger > 0 && <Badge>{aCorriger}</Badge>}</h2>
                    {productions.length === 0 && <p className="text-sm text-muted-foreground">{t('Aucune production rendue pour le moment.')}</p>}
                    {productions.map((p) => <FicheProduction key={p.id} p={p} />)}
                </section>
            </div>
        </AppLayout>
    );
}

function FicheProduction({ p }: { p: Production }) {
    const { t, locale } = useT();
    const form = useForm<{ commentaire: string; note: string }>({ commentaire: p.commentaire ?? '', note: p.note !== null ? String(p.note) : '' });
    return (
        <div className="space-y-2 rounded-lg border bg-card p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="font-medium">{p.eleve} · {p.type === 'ecrit' ? t('Production écrite') : t('Production orale')} · {p.seance}</p>
                <Badge variant={p.statut === 'corrige' ? 'secondary' : 'default'}>{p.statut === 'corrige' ? t('Corrigé') : t('À corriger')}</Badge>
            </div>
            {p.rendu_le && <p className="text-xs text-muted-foreground">{t('Rendu le {date}', { date: new Date(p.rendu_le).toLocaleString(locale) })}</p>}
            {p.consigne && <p className="text-xs text-muted-foreground">{p.consigne}</p>}
            {p.texte && <p className="whitespace-pre-line rounded bg-muted/40 p-3 text-sm">{p.texte}</p>}
            {p.audio_url && <audio controls src={p.audio_url} className="w-full" />}
            {p.correction_ia?.feedback && <p className="text-xs text-muted-foreground"><span className="font-medium">{t('Avis IA')} :</span> {p.correction_ia.feedback}</p>}
            <form
                className="flex flex-col gap-2 sm:flex-row sm:items-end"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.transform((d) => ({ commentaire: d.commentaire || null, note: d.note === '' ? null : Number(d.note) }));
                    form.patch(`/center/cours/productions/${p.id}`, { preserveScroll: true });
                }}
            >
                <label className="flex-1 text-sm">
                    <span className="mb-1 block">{t('Commentaire')}</span>
                    <textarea rows={2} className="w-full rounded-md border bg-background p-2" value={form.data.commentaire} onChange={(e) => form.setData('commentaire', e.target.value)} />
                </label>
                <label className="text-sm sm:w-24">
                    <span className="mb-1 block">{t('Note /20')}</span>
                    <input type="number" min={0} max={20} className="w-full rounded-md border bg-background p-2" value={form.data.note} onChange={(e) => form.setData('note', e.target.value)} />
                </label>
                <Button type="submit" disabled={form.processing}>{t('Enregistrer')}</Button>
            </form>
        </div>
    );
}
