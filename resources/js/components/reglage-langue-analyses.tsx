import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useT } from '@/lib/i18n/traduire';

type Langue = 'fr' | 'en';

/**
 * La langue dans laquelle l'IA redige les analyses des eleves, des classes et
 * du centre. Envoye a part du formulaire de personnalisation.
 */
export default function ReglageLangueAnalyses({ valeur }: { valeur: Langue }) {
    const { t } = useT();
    const form = useForm<{ langue_analyses: Langue }>({ langue_analyses: valeur });

    function enregistrer(e: React.FormEvent) {
        e.preventDefault();
        form.patch(route('center.personnalisation.langue-analyses'), { preserveScroll: true });
    }

    return (
        <Card id="langue-analyses" className="scroll-mt-20">
            <CardHeader className="pb-2">
                <CardTitle className="text-base">{t('Langue des analyses IA')}</CardTitle>
                <p className="text-xs text-muted-foreground">
                    {t("La langue dans laquelle l'IA rédige le problème principal, le levier de progression et les conseils de chaque élève, de chaque classe et du centre. Les analyses se mettent à jour à la prochaine ouverture de chaque fiche.")}
                </p>
            </CardHeader>
            <CardContent>
                <form onSubmit={enregistrer} className="flex flex-wrap items-center gap-2">
                    {([['fr', 'Français'], ['en', 'English']] as const).map(([cle, nom]) => (
                        <label
                            key={cle}
                            className={`flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium ${form.data.langue_analyses === cle ? 'border-primary bg-primary/5' : 'hover:bg-muted/40'}`}
                        >
                            <input
                                type="radio"
                                name="langue_analyses"
                                value={cle}
                                checked={form.data.langue_analyses === cle}
                                onChange={() => form.setData('langue_analyses', cle)}
                                className="accent-[var(--primary)]"
                            />
                            {nom}
                        </label>
                    ))}
                    <Button type="submit" size="sm" disabled={form.processing || form.data.langue_analyses === valeur} className="ml-auto">
                        {t('Enregistrer')}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}
