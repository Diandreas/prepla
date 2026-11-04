import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export type ContenuEleves = 'libre' | 'centre';

const CHOIX: { valeur: ContenuEleves; titre: string; detail: string }[] = [
    {
        valeur: 'libre',
        titre: 'Parcours PrePla par défaut',
        detail: "Vos élèves font d'abord vos devoirs. Quand ils n'en ont plus, ils suivent l'entraînement PrePla, réglé sur l'examen et le niveau de leur classe : rien à préparer de votre côté.",
    },
    {
        valeur: 'centre',
        titre: 'Parcours créé par le centre',
        detail: "Vous construisez le parcours de chaque classe (fiche de la classe, « Parcours »), avec vos exercices ou ceux de la banque PrePla. Vos élèves suivent vos devoirs puis ce parcours, étape par étape. La pratique libre, le parcours personnel et les outils IA PrePla leur sont masqués.",
    },
];

/**
 * Le réglage « Parcours des élèves », pour le responsable du centre.
 * Valeurs stockées inchangées : 'libre' (parcours PrePla) et 'centre'.
 * Envoyé à part du formulaire de personnalisation.
 */
export default function ReglageContenuEleves({ valeur }: { valeur: ContenuEleves }) {
    const form = useForm<{ contenu_eleves: ContenuEleves }>({ contenu_eleves: valeur });

    function enregistrer(e: React.FormEvent) {
        e.preventDefault();
        form.patch(route('center.personnalisation.contenu-eleves'), { preserveScroll: true });
    }

    return (
        <Card id="contenu-eleves" className="scroll-mt-20">
            <CardHeader className="pb-2">
                <CardTitle className="text-base">Parcours des élèves</CardTitle>
                <p className="text-xs text-muted-foreground">
                    Vous pouvez garder le parcours PrePla par défaut ou créer le parcours vous-mêmes. Dans les deux cas, l'accueil de vos élèves leur montre
                    d'abord leur devoir en retard ou leur prochain devoir, et leur examen et leur niveau viennent de leur classe : ils n'ont pas d'objectif à
                    choisir eux-mêmes.
                </p>
            </CardHeader>
            <CardContent>
                <form onSubmit={enregistrer} className="space-y-3">
                    <fieldset className="space-y-2">
                        <legend className="sr-only">Parcours des élèves</legend>
                        {CHOIX.map((c) => (
                            <label
                                key={c.valeur}
                                className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 ${form.data.contenu_eleves === c.valeur ? 'border-primary bg-primary/5' : 'hover:bg-muted/40'}`}
                            >
                                <input
                                    type="radio"
                                    name="contenu_eleves"
                                    value={c.valeur}
                                    checked={form.data.contenu_eleves === c.valeur}
                                    onChange={() => form.setData('contenu_eleves', c.valeur)}
                                    className="mt-1 accent-[var(--primary)]"
                                />
                                <span className="text-sm">
                                    <span className="font-medium">
                                        {c.titre}
                                        {c.valeur === 'libre' && <span className="ml-1.5 text-xs font-normal text-muted-foreground">(par défaut)</span>}
                                    </span>
                                    <span className="block text-xs text-muted-foreground">{c.detail}</span>
                                </span>
                            </label>
                        ))}
                    </fieldset>
                    <InputError message={form.errors.contenu_eleves} />
                    <div className="flex items-center justify-end gap-3">
                        {form.recentlySuccessful && <span className="text-sm text-emerald-600 dark:text-emerald-400">Enregistré.</span>}
                        <Button type="submit" size="sm" disabled={form.processing || form.data.contenu_eleves === valeur}>
                            {form.processing ? 'Enregistrement…' : 'Enregistrer ce choix'}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
