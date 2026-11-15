import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useT } from '@/lib/i18n/traduire';

interface Presence { id: number; name: string; present: boolean; source: 'google' | 'prepla' | null; minutes: number | null; interactions: number }
interface Live {
    id: number;
    title: string;
    classe: { id: number; name: string | null };
    starts_at: string;
    statut: string;
    recording_url: string | null;
    summary: string | null;
    assignment_id: number | null;
    replay_published: boolean;
    duree_reelle: number | null;
}

export default function LiveBilan({
    live,
    presence,
    questions,
    sondages,
    devoirs,
}: {
    live: Live;
    presence: Presence[];
    questions: { id: number; body: string; auteur: string | null; votes: number }[];
    sondages: { question: string; options: string[]; correct: number | null; resultats: number[] }[];
    devoirs: { id: number; title: string }[];
}) {
    const { t, locale } = useT();
    const { flash } = usePage().props as { flash?: { success?: string } };
    const form = useForm({
        recording_url: live.recording_url ?? '',
        summary: live.summary ?? '',
        assignment_id: live.assignment_id ? String(live.assignment_id) : '',
        replay_published: live.replay_published,
    });
    const presents = presence.filter((p) => p.present).length;

    function enregistrer(e: React.FormEvent) {
        e.preventDefault();
        form.transform((d) => ({ ...d, assignment_id: d.assignment_id ? Number(d.assignment_id) : null, recording_url: d.recording_url || null }));
        form.patch(route('center.lives.update', live.id), { preserveScroll: true });
    }

    return (
        <AppLayout>
            <Head title={live.title} />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('center.lives.index', { classe: live.classe.id })} className="text-sm text-muted-foreground hover:text-foreground">← {t('Live class')}</Link>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">{live.title}</h1>
                    <p className="text-sm text-muted-foreground">
                        {live.classe.name} · {new Date(live.starts_at).toLocaleString(locale)}
                        {live.duree_reelle !== null && ` · ${t('{n} min', { n: live.duree_reelle })}`}
                    </p>
                    <Link href={route('live.show', live.id)} className="text-sm font-medium text-primary hover:underline">{t('Ouvrir la salle')}</Link>
                </div>
                {flash?.success && <div className="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">{t(flash.success)}</div>}

                <Card>
                    <CardHeader><CardTitle className="text-base">{t('Présence')} · {presents}/{presence.length}</CardTitle></CardHeader>
                    <CardContent className="space-y-1 text-sm">
                        {presence.map((p) => (
                            <div key={p.id} className="flex items-center justify-between border-b border-border py-1.5 last:border-0">
                                <span>{p.name}</span>
                                <span className={p.present ? 'text-emerald-600' : 'text-muted-foreground'}>
                                    {p.present ? (p.source === 'google' ? t('Présent (Google Meet)') : t('Présent (PrePla)')) : t('Absent')}
                                    {p.minutes !== null && ` · ${t('{n} min', { n: p.minutes })}`}
                                    {p.interactions > 0 && ` · ${t('{n} interactions', { n: p.interactions })}`}
                                </span>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">{t('Après le live')}</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={enregistrer} className="space-y-3 text-sm">
                            <label className="block">
                                <span className="font-medium">{t('Lien de l’enregistrement')}</span>
                                <input type="url" className="mt-1 w-full rounded-md border border-input bg-background p-2" value={form.data.recording_url} onChange={(e) => form.setData('recording_url', e.target.value)} placeholder="https://drive.google.com/..." />
                                {form.errors.recording_url && <span className="text-xs text-rose-600">{form.errors.recording_url}</span>}
                            </label>
                            <label className="flex items-center gap-2">
                                <input type="checkbox" checked={form.data.replay_published} onChange={(e) => form.setData('replay_published', e.target.checked)} />
                                {t('Publier le replay pour les élèves de la classe')}
                            </label>
                            <label className="block">
                                <span className="font-medium">{t('Résumé du cours')}</span>
                                <textarea rows={5} className="mt-1 w-full rounded-md border border-input bg-background p-2" value={form.data.summary} onChange={(e) => form.setData('summary', e.target.value)} />
                            </label>
                            <label className="block">
                                <span className="font-medium">{t('Devoir à faire après le live')}</span>
                                <select className="mt-1 w-full rounded-md border border-input bg-background p-2" value={form.data.assignment_id} onChange={(e) => form.setData('assignment_id', e.target.value)}>
                                    <option value="">{t('Aucun')}</option>
                                    {devoirs.map((d) => <option key={d.id} value={d.id}>{d.title}</option>)}
                                </select>
                            </label>
                            <Button type="submit" disabled={form.processing}>{t('Enregistrer')}</Button>
                        </form>
                    </CardContent>
                </Card>

                {questions.length > 0 && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">{t('Questions des élèves')}</CardTitle></CardHeader>
                        <CardContent className="space-y-1 text-sm">
                            {questions.map((q) => <p key={q.id}><span className="font-semibold">▲ {q.votes}</span> {q.body} <span className="text-xs text-muted-foreground">— {q.auteur}</span></p>)}
                        </CardContent>
                    </Card>
                )}
                {sondages.length > 0 && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">{t('Sondages')}</CardTitle></CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            {sondages.map((s, i) => (
                                <div key={i}>
                                    <p className="font-medium">{s.question}</p>
                                    {s.options.map((o, j) => <p key={j} className={s.correct === j ? 'text-emerald-600' : ''}>{o} : {s.resultats[j]}</p>)}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
