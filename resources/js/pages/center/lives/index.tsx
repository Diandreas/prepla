import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useT } from '@/lib/i18n/traduire';

interface Classe { id: number; name: string; eleves: number }
interface Live {
    id: number;
    title: string;
    agenda: string | null;
    classe: { id: number; name: string | null };
    starts_at: string;
    duration_minutes: number;
    statut: 'a_venir' | 'en_cours' | 'termine' | 'annule';
    meeting_uri: string | null;
    serie: boolean;
    rejoints: number | null;
    recording_url: string | null;
}
type Mode = 'maintenant' | 'planifie' | 'serie';

const STATUTS: Record<Live['statut'], string> = { a_venir: 'À venir', en_cours: 'En cours', termine: 'Terminé', annule: 'Annulé' };

export default function LivesIndex({
    classes,
    classeChoisie,
    aVenir,
    passes,
    google,
    accessTypeParDefaut,
}: {
    classes: Classe[];
    classeChoisie: number | null;
    aVenir: Live[];
    passes: Live[];
    google: { connecte: boolean; email: string | null; lectureAutorisee: boolean };
    accessTypeParDefaut: 'OPEN' | 'TRUSTED';
}) {
    const { t, locale } = useT();
    const { flash, errors } = usePage().props as { flash?: { success?: string; error?: string }; errors: Record<string, string> };
    const ouvrirFormulaire = typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('nouveau');
    const [formulaire, setFormulaire] = useState(ouvrirFormulaire || (classes.length > 0 && aVenir.length === 0));

    const form = useForm({
        classe: String(classeChoisie ?? classes[0]?.id ?? ''),
        mode: 'maintenant' as Mode,
        title: '',
        agenda: '',
        starts_at: '',
        duration_minutes: 60,
        repetitions: 8,
        access_type: accessTypeParDefaut,
        meeting_uri: '',
    });

    function creer(e: React.FormEvent) {
        e.preventDefault();
        form.transform(({ classe, ...reste }) => reste);
        form.post(route('center.lives.store', Number(form.data.classe)), { preserveScroll: true });
    }

    const date = (iso: string) => new Date(iso).toLocaleString(locale, { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });

    function filtrer(id: string) {
        router.get(route('center.lives.index'), id ? { classe: id } : {}, { preserveState: true });
    }

    return (
        <AppLayout>
            <Head title={t('Live class')} />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{t('Live class')}</h1>
                        <p className="text-sm text-muted-foreground">
                            {t('Cours en direct dans Google Meet, avec main levée, questions, sondages et ressources dans PrePla.')}
                        </p>
                    </div>
                    <Button className="bg-rose-600 text-white hover:bg-rose-700" onClick={() => setFormulaire((o) => !o)} disabled={classes.length === 0}>
                        ● {formulaire ? t('Fermer') : t('Nouveau live')}
                    </Button>
                </div>

                {flash?.success && <div className="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">{t(flash.success)}</div>}
                {(flash?.error || errors.google) && <div className="rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-950/30 dark:text-rose-300">{flash?.error ?? errors.google}</div>}

                <Card>
                    <CardContent className="flex flex-wrap items-center justify-between gap-3 py-4 text-sm">
                        {google.connecte ? (
                            <>
                                <span>
                                    {t('Compte Google connecté')} {google.email && <span className="font-medium">({google.email})</span>}
                                    {!google.lectureAutorisee && <span className="block text-xs text-muted-foreground">{t('La présence Google ne pourra pas être relue : reconnectez le compte pour l’autoriser.')}</span>}
                                </span>
                                <div className="flex gap-2">
                                    <Button variant="outline" size="sm" asChild><a href={route('center.google.connecter')}>{t('Reconnecter')}</a></Button>
                                    <Button variant="ghost" size="sm" onClick={() => router.delete(route('center.google.deconnecter'))}>{t('Déconnecter')}</Button>
                                </div>
                            </>
                        ) : (
                            <>
                                <span>{t('Connectez votre compte Google pour créer les salles Meet automatiquement. Sinon, collez un lien Meet existant.')}</span>
                                <Button size="sm" asChild><a href={route('center.google.connecter')}>{t('Connecter Google')}</a></Button>
                            </>
                        )}
                    </CardContent>
                </Card>

                {formulaire && classes.length > 0 && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">{t('Nouveau live')}</CardTitle></CardHeader>
                        <CardContent>
                            <form onSubmit={creer} className="space-y-4 text-sm">
                                <div className="flex flex-wrap gap-2" role="radiogroup">
                                    {([['maintenant', 'Lancer maintenant'], ['planifie', 'Planifier'], ['serie', 'Série récurrente']] as [Mode, string][]).map(([m, libelle]) => (
                                        <button type="button" key={m} role="radio" aria-checked={form.data.mode === m}
                                            onClick={() => form.setData('mode', m)}
                                            className={`rounded-full border px-3 py-1.5 font-medium ${form.data.mode === m ? 'border-primary bg-primary text-primary-foreground' : 'border-border'}`}>
                                            {t(libelle)}
                                        </button>
                                    ))}
                                </div>
                                <label className="block">
                                    <span className="font-medium">{t('Classe')}</span>
                                    <select className="mt-1 w-full rounded-md border border-input bg-background p-2" value={form.data.classe} onChange={(e) => form.setData('classe', e.target.value)}>
                                        {classes.map((c) => <option key={c.id} value={c.id}>{c.name} ({t('{n} élèves', { n: c.eleves })})</option>)}
                                    </select>
                                </label>
                                <label className="block">
                                    <span className="font-medium">{t('Titre')}</span>
                                    <input className="mt-1 w-full rounded-md border border-input bg-background p-2" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} required maxLength={160} />
                                    {form.errors.title && <span className="text-xs text-rose-600">{form.errors.title}</span>}
                                </label>
                                <label className="block">
                                    <span className="font-medium">{t('Ordre du jour')}</span>
                                    <textarea className="mt-1 w-full rounded-md border border-input bg-background p-2" rows={3} value={form.data.agenda} onChange={(e) => form.setData('agenda', e.target.value)} />
                                </label>
                                <div className="grid gap-3 sm:grid-cols-3">
                                    {form.data.mode !== 'maintenant' && (
                                        <label className="block">
                                            <span className="font-medium">{form.data.mode === 'serie' ? t('Première séance') : t('Date et heure')}</span>
                                            <input type="datetime-local" className="mt-1 w-full rounded-md border border-input bg-background p-2" value={form.data.starts_at} onChange={(e) => form.setData('starts_at', e.target.value)} required />
                                            {form.errors.starts_at && <span className="text-xs text-rose-600">{form.errors.starts_at}</span>}
                                        </label>
                                    )}
                                    <label className="block">
                                        <span className="font-medium">{t('Durée (minutes)')}</span>
                                        <input type="number" min={10} max={480} className="mt-1 w-full rounded-md border border-input bg-background p-2" value={form.data.duration_minutes} onChange={(e) => form.setData('duration_minutes', Number(e.target.value))} />
                                    </label>
                                    {form.data.mode === 'serie' && (
                                        <label className="block">
                                            <span className="font-medium">{t('Nombre de semaines')}</span>
                                            <input type="number" min={2} max={26} className="mt-1 w-full rounded-md border border-input bg-background p-2" value={form.data.repetitions} onChange={(e) => form.setData('repetitions', Number(e.target.value))} />
                                            <span className="text-xs text-muted-foreground">{t('Même jour, même heure, chaque semaine, même salle Meet.')}</span>
                                        </label>
                                    )}
                                </div>
                                <label className="block">
                                    <span className="font-medium">{t('Accès à la salle Meet')}</span>
                                    <select className="mt-1 w-full rounded-md border border-input bg-background p-2" value={form.data.access_type} onChange={(e) => form.setData('access_type', e.target.value as 'OPEN' | 'TRUSTED')}>
                                        <option value="OPEN">{t('Ouvert : le lien suffit pour entrer')}</option>
                                        <option value="TRUSTED">{t('Contrôlé : les élèves hors de votre domaine demandent à entrer')}</option>
                                    </select>
                                </label>
                                {!google.connecte && (
                                    <label className="block">
                                        <span className="font-medium">{t('Lien Google Meet')}</span>
                                        <input type="url" placeholder="https://meet.google.com/abc-defg-hij" className="mt-1 w-full rounded-md border border-input bg-background p-2" value={form.data.meeting_uri} onChange={(e) => form.setData('meeting_uri', e.target.value)} />
                                        {form.errors.meeting_uri && <span className="text-xs text-rose-600">{form.errors.meeting_uri}</span>}
                                    </label>
                                )}
                                <p className="text-xs text-muted-foreground">{t('Les élèves de la classe sont prévenus (notification, ou e-mail à défaut).')}</p>
                                <Button type="submit" disabled={form.processing}>
                                    {form.data.mode === 'maintenant' ? t('Lancer le live') : t('Planifier')}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <div className="flex items-center gap-2 text-sm">
                    <span className="text-muted-foreground">{t('Classe')}</span>
                    <select className="rounded-md border border-input bg-background p-1.5" value={classeChoisie ?? ''} onChange={(e) => filtrer(e.target.value)}>
                        <option value="">{t('Toutes mes classes')}</option>
                        {classes.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                    </select>
                </div>

                <section className="space-y-2">
                    <h2 className="text-sm font-bold uppercase tracking-wider text-muted-foreground">{t('À venir')}</h2>
                    {aVenir.length === 0 && <p className="text-sm text-muted-foreground">{t('Aucun live prévu.')}</p>}
                    {aVenir.map((l) => (
                        <Card key={l.id}>
                            <CardContent className="flex flex-wrap items-center justify-between gap-3 py-4">
                                <div className="min-w-0">
                                    <p className="font-semibold">
                                        {l.title} {l.statut === 'en_cours' && <Badge className="ml-1 bg-rose-600 text-white">{t('En cours')}</Badge>}
                                        {l.serie && <Badge variant="outline" className="ml-1">{t('Série')}</Badge>}
                                    </p>
                                    <p className="text-xs text-muted-foreground">{l.classe.name} · {date(l.starts_at)} · {t('{n} min', { n: l.duration_minutes })}</p>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <Button size="sm" onClick={() => router.post(route('center.lives.demarrer', l.id))}>
                                        {l.statut === 'en_cours' ? t('Ouvrir la salle') : t('Démarrer')}
                                    </Button>
                                    {l.statut === 'a_venir' && (
                                        <Button size="sm" variant="ghost" className="text-rose-600" onClick={() => confirm(t('Annuler ce live ?')) && router.post(route('center.lives.annuler', l.id), {}, { preserveScroll: true })}>
                                            {t('Annuler')}
                                        </Button>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <section className="space-y-2">
                    <h2 className="text-sm font-bold uppercase tracking-wider text-muted-foreground">{t('Passés')}</h2>
                    {passes.length === 0 && <p className="text-sm text-muted-foreground">{t('Aucun live passé.')}</p>}
                    {passes.map((l) => (
                        <Link key={l.id} href={route('center.lives.show', l.id)} className="flex items-center justify-between gap-3 rounded-xl border border-border p-3 text-sm hover:bg-muted/50">
                            <span className="min-w-0">
                                <span className="font-medium">{l.title}</span>
                                <span className="block text-xs text-muted-foreground">{l.classe.name} · {date(l.starts_at)}</span>
                            </span>
                            <span className="shrink-0 text-xs text-muted-foreground">
                                {t(STATUTS[l.statut])}{l.rejoints !== null && l.statut === 'termine' ? ` · ${t('{n} présents', { n: l.rejoints })}` : ''}
                            </span>
                        </Link>
                    ))}
                </section>
            </div>
        </AppLayout>
    );
}
