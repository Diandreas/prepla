import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { EnTetePage } from '@/components/center/liste-centre';
import { useT } from '@/lib/i18n/traduire';

interface Grille {
    examen?: string;
    affichage?: string;
    commentaire?: string | null;
    criteres?: Array<{ nom: string; valeur: string }>;
}

interface Item {
    question_id: string;
    consigne: string | null;
    texte: string | null;
    transcription: string | null;
    audio: string | null;
    ia: { pourcentage: number | null; sous_notes: Record<string, number> | null; grille: Grille | null; explication: string | null };
}

interface Production {
    id: number;
    eleve: string | null;
    competence: 'writing' | 'speaking';
    examen: string | null;
    date: string | null;
    note_ia: number;
    items: Item[];
    correction: { sous_notes: Record<string, number>; grille: Grille | null; commentaire: string | null; enseignant: string | null } | null;
}

const LIBELLES: Record<string, string> = {
    task_achievement: 'Réponse à la consigne',
    coherence_cohesion: 'Cohérence et cohésion',
    lexical_resource: 'Vocabulaire',
    grammar_accuracy: 'Grammaire',
};

/** Relire une production, comparer a la note de l'IA, donner sa note et un commentaire. */
export default function CorrigerProduction({ production, dimensions }: { production: Production; dimensions: string[] }) {
    const { t, locale } = useT();

    // Point de depart : la note deja donnee, sinon celle de l'IA (dimension par dimension).
    const iaSous = production.items[0]?.ia.sous_notes ?? {};
    const depart = Object.fromEntries(
        dimensions.map((d) => [d, String(production.correction?.sous_notes?.[d] ?? (typeof iaSous[d] === 'number' ? iaSous[d] : 5))]),
    );
    const form = useForm({ sous_notes: depart as Record<string, string>, commentaire: production.correction?.commentaire ?? '' });

    const envoyer = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/center/corrections/${production.id}`);
    };

    return (
        <AppLayout>
            <Head title={t('Corriger une production')} />
            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                <Link href="/center/corrections" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" /> {t('À corriger')}
                </Link>
                <EnTetePage
                    titre={production.eleve ?? t('Élève')}
                    sousTitre={[
                        production.competence === 'speaking' ? t('Oral') : t('Écrit'),
                        production.examen,
                        production.date ? new Date(production.date).toLocaleDateString(locale) : null,
                    ]
                        .filter(Boolean)
                        .join(' · ')}
                />

                {production.items.map((item) => (
                    <section key={item.question_id} className="space-y-3 rounded-xl border border-border bg-card p-4">
                        {item.consigne && (
                            <div>
                                <p className="text-xs font-semibold uppercase text-muted-foreground">{t('Consigne')}</p>
                                <p className="text-sm">{item.consigne}</p>
                            </div>
                        )}
                        {item.audio && (
                            <div>
                                <p className="text-xs font-semibold uppercase text-muted-foreground">{t('Enregistrement de l’élève')}</p>
                                <audio controls preload="none" src={item.audio} className="w-full" />
                            </div>
                        )}
                        {(item.texte || item.transcription) && (
                            <div>
                                <p className="text-xs font-semibold uppercase text-muted-foreground">
                                    {item.transcription && !item.texte ? t('Transcription') : t('Texte de l’élève')}
                                </p>
                                <p className="whitespace-pre-line rounded-lg bg-muted/40 p-3 text-sm">{item.texte ?? item.transcription}</p>
                            </div>
                        )}
                        <div className="rounded-lg border border-sky-200 bg-sky-50/60 p-3 text-sm dark:border-sky-900 dark:bg-sky-950/30">
                            <p className="font-semibold">
                                {t('Note de l’IA')} : {item.ia.grille?.affichage ?? (item.ia.pourcentage !== null ? `${item.ia.pourcentage} %` : '—')}
                            </p>
                            {item.ia.explication && <p className="mt-1 text-muted-foreground">{item.ia.explication}</p>}
                        </div>
                    </section>
                ))}

                <form onSubmit={envoyer} className="space-y-4 rounded-xl border border-border bg-card p-4">
                    <h2 className="font-semibold">{t('Votre note')}</h2>
                    <p className="text-sm text-muted-foreground">
                        {t('Notez chaque critère de 0 à 9 : la note est traduite dans le barème de l’examen, et elle remplace celle de l’IA pour l’élève.')}
                    </p>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {dimensions.map((d) => (
                            <label key={d} className="flex items-center justify-between gap-3 text-sm">
                                <span>{t(LIBELLES[d] ?? d)}</span>
                                <input
                                    type="number"
                                    min={0}
                                    max={9}
                                    step={0.5}
                                    required
                                    className="w-20 rounded-md border border-border bg-background px-2 py-1 text-right tabular-nums"
                                    value={form.data.sous_notes[d]}
                                    onChange={(e) => form.setData('sous_notes', { ...form.data.sous_notes, [d]: e.target.value })}
                                />
                            </label>
                        ))}
                    </div>
                    <label className="block space-y-1 text-sm">
                        <span>{t('Commentaire pour l’élève')}</span>
                        <textarea
                            rows={5}
                            maxLength={5000}
                            className="w-full rounded-md border border-border bg-background p-2"
                            value={form.data.commentaire}
                            onChange={(e) => form.setData('commentaire', e.target.value)}
                        />
                    </label>
                    {Object.keys(form.errors).length > 0 && <p className="text-sm text-destructive">{t('Vérifiez les notes : chacune va de 0 à 9.')}</p>}
                    {production.correction?.grille?.affichage && (
                        <p className="text-sm text-muted-foreground">
                            {t('Note actuelle')} : <strong>{production.correction.grille.affichage}</strong>
                        </p>
                    )}
                    <Button type="submit" disabled={form.processing}>
                        {production.correction ? t('Mettre à jour la correction') : t('Enregistrer la correction')}
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
