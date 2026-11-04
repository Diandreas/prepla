import { router, useForm, usePage } from '@inertiajs/react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export interface DomaineDuCentre {
    id: number;
    host: string;
    type: 'subdomain' | 'custom';
    verifie: boolean;
    verifie_le: string | null;
    principal: boolean;
    txt_nom: string;
    txt_valeur: string;
}

export interface ReglagesDomaines {
    racine: string;
    cible_cname: string;
}

/**
 * Sous-domaines et domaines propres d'un centre (lot B), sur la page du super
 * administrateur. Un domaine propre n'est actif qu'après vérification DNS ; le
 * certificat et nginx restent à faire sur le serveur (docs/domaines-centres.md).
 */
export default function DomainesDuCentre({
    centreId,
    domaines,
    reglages,
}: {
    centreId: number;
    domaines: DomaineDuCentre[];
    reglages: ReglagesDomaines;
}) {
    const { flash } = usePage().props as { flash?: { error?: string | null } };
    const form = useForm({ host: '' });

    function ajouter(e: React.FormEvent) {
        e.preventDefault();
        form.post(route('admin.centers.domaines.store', centreId), {
            preserveScroll: true,
            onSuccess: () => form.reset('host'),
        });
    }

    const options = { preserveScroll: true };

    function verifier(d: DomaineDuCentre) {
        router.post(route('admin.centers.domaines.verifier', [centreId, d.id]), {}, options);
    }

    function rendrePrincipal(d: DomaineDuCentre) {
        router.patch(route('admin.centers.domaines.principal', [centreId, d.id]), {}, options);
    }

    function retirer(d: DomaineDuCentre) {
        if (!confirm(`Retirer « ${d.host} » ? Cette adresse ne mènera plus au centre.`)) return;
        router.delete(route('admin.centers.domaines.destroy', [centreId, d.id]), options);
    }

    const inputClass =
        'w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Adresses du centre</CardTitle>
                <p className="text-xs text-muted-foreground">
                    Un mot seul crée un sous-domaine (« ecole » donne ecole.{reglages.racine}), actif tout de suite. Un domaine
                    propre (prepa.ecole.cm) n'est actif qu'après vérification DNS.
                </p>
            </CardHeader>
            <CardContent className="space-y-4">
                {flash?.error && (
                    <div className="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950/30 dark:text-red-300">
                        {flash.error}
                    </div>
                )}

                {domaines.length === 0 && <p className="text-sm text-muted-foreground">Aucune adresse : le centre s'ouvre sur l'adresse principale.</p>}

                {domaines.map((d) => (
                    <div key={d.id} className="space-y-2 rounded-lg border border-border p-3 text-sm">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="font-medium break-all">{d.host}</span>
                            <Badge variant="secondary">{d.type === 'subdomain' ? 'Sous-domaine' : 'Domaine propre'}</Badge>
                            {d.verifie ? <Badge>Vérifié</Badge> : <Badge variant="outline">À vérifier</Badge>}
                            {d.principal && <Badge variant="outline">Principale</Badge>}
                        </div>

                        {!d.verifie && (
                            <div className="space-y-1 rounded-md bg-muted p-2 text-xs">
                                <p>Demandez au centre de créer chez son hébergeur DNS l'un des deux enregistrements :</p>
                                <p>
                                    TXT <code className="break-all">{d.txt_nom}</code> = <code className="break-all">{d.txt_valeur}</code>
                                </p>
                                <p>
                                    ou CNAME <code className="break-all">{d.host}</code> → <code>{reglages.cible_cname}</code>
                                </p>
                            </div>
                        )}

                        <div className="flex flex-wrap gap-2">
                            {!d.verifie && (
                                <Button type="button" size="sm" onClick={() => verifier(d)}>
                                    Vérifier
                                </Button>
                            )}
                            {d.verifie && !d.principal && (
                                <Button type="button" size="sm" variant="outline" onClick={() => rendrePrincipal(d)}>
                                    Rendre principale
                                </Button>
                            )}
                            <Button type="button" size="sm" variant="outline" onClick={() => retirer(d)}>
                                Retirer
                            </Button>
                        </div>
                    </div>
                ))}

                <form onSubmit={ajouter} className="flex flex-col gap-2 sm:flex-row sm:items-start">
                    <div className="flex-1">
                        <input
                            className={inputClass}
                            value={form.data.host}
                            placeholder={`ecole ou prepa.ecole.cm`}
                            aria-label="Nouvelle adresse"
                            onChange={(e) => form.setData('host', e.target.value)}
                        />
                        {form.errors.host && <p className="mt-1 text-xs text-red-600">{form.errors.host}</p>}
                    </div>
                    <Button type="submit" disabled={form.processing || form.data.host.trim() === ''}>
                        {form.processing ? 'Ajout…' : 'Ajouter'}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}
