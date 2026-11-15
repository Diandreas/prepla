import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { useT } from '@/lib/i18n/traduire';
import { Head, Link, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';

interface Ligne { id: number; titre: string; statut: 'brouillon' | 'publie'; classe: string | null; classroom_id: number | null; seances: number; modifie_le: string | null }

/** Les parcours du centre : par classe, et les modèles réutilisables. */
export default function CoursIndex({ parcours, classes, classeChoisie }: { parcours: Ligne[]; classes: { id: number; name: string }[]; classeChoisie: number | null }) {
    const { t, locale } = useT();
    const form = useForm<{ titre: string; classroom_id: string }>({ titre: '', classroom_id: classeChoisie ? String(classeChoisie) : '' });

    return (
        <AppLayout>
            <Head title={t('Parcours et cours')} />
            <div className="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">{t('Parcours et cours')}</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {t('Composez vos cours à votre façon : modules, séances, et dans chaque séance des blocs de texte, vidéo, audio, documents, quiz, productions, vocabulaire ou séance live.')}
                    </p>
                </div>

                <Card>
                    <CardHeader><CardTitle className="text-base">{t('Nouveau parcours')}</CardTitle></CardHeader>
                    <CardContent>
                        <form
                            className="flex flex-col gap-3 sm:flex-row sm:items-end"
                            onSubmit={(e) => {
                                e.preventDefault();
                                form.transform((d) => ({ ...d, classroom_id: d.classroom_id || null }));
                                form.post('/center/cours');
                            }}
                        >
                            <label className="flex-1 text-sm">
                                <span className="mb-1 block font-medium">{t('Titre')}</span>
                                <input className="w-full rounded-md border bg-background px-3 py-2" value={form.data.titre} onChange={(e) => form.setData('titre', e.target.value)} required maxLength={160} />
                                <InputError message={form.errors.titre} />
                            </label>
                            <label className="text-sm sm:w-64">
                                <span className="mb-1 block font-medium">{t('Pour la classe')}</span>
                                <select className="w-full rounded-md border bg-background px-3 py-2" value={form.data.classroom_id} onChange={(e) => form.setData('classroom_id', e.target.value)}>
                                    <option value="">{t('Modèle réutilisable (sans classe)')}</option>
                                    {classes.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                                </select>
                            </label>
                            <Button type="submit" disabled={form.processing}><Plus className="size-4" /> {t('Créer')}</Button>
                        </form>
                    </CardContent>
                </Card>

                <div className="space-y-2">
                    {parcours.length === 0 && <p className="text-sm text-muted-foreground">{t('Aucun parcours pour le moment.')}</p>}
                    {parcours.map((p) => (
                        <div key={p.id} className="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-card p-3">
                            <div className="min-w-0">
                                <Link href={`/center/cours/${p.id}`} className="font-medium hover:underline">{p.titre}</Link>
                                <p className="text-xs text-muted-foreground">
                                    {p.classe ?? t('Modèle réutilisable')} · {p.seances > 1 ? t('{n} séances', { n: p.seances }) : t('{n} séance', { n: p.seances })}
                                    {p.modifie_le ? ` · ${t('modifié le {date}', { date: new Date(p.modifie_le).toLocaleDateString(locale) })}` : ''}
                                </p>
                            </div>
                            <div className="flex items-center gap-2">
                                <Badge variant={p.statut === 'publie' ? 'default' : 'secondary'}>{p.statut === 'publie' ? t('Publié') : t('Brouillon')}</Badge>
                                {p.classroom_id && <Button asChild size="sm" variant="outline"><Link href={`/center/cours/${p.id}/suivi`}>{t('Suivi')}</Link></Button>}
                                <Button asChild size="sm"><Link href={`/center/cours/${p.id}`}>{t('Modifier')}</Link></Button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
