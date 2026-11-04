import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import OngletsReleves from '@/components/onglets-releves';
import { formaterMontant } from '@/lib/montant-stripe';

interface LigneCentre {
    center_id: number;
    centre: string;
    devise: string;
    lignes: number;
    comptes: number;
    encaisse: number;
    commission: number;
}

function moisEnClair(periode: string): string {
    const [annee, mois] = periode.split('-').map(Number);
    return new Date(annee, mois - 1, 1).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
}

/**
 * Commissions de parrainage, par centre et par mois, pour le super
 * administrateur. Lecture seule : le paiement passe par le releve mensuel.
 */
export default function Commissions({
    periode,
    periodes,
    taux,
    parCentre,
}: {
    periode: string;
    periodes: string[];
    taux: number;
    parCentre: LigneCentre[];
}) {
    return (
        <AppLayout>
            <Head title="Commissions de parrainage" />
            <div className="mx-auto w-full max-w-5xl space-y-5 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Relevés</h1>
                        <p className="text-sm text-muted-foreground">
                            Commissions de parrainage : {taux} % des factures payées par les comptes qu'un centre a amenés, remboursements déduits.
                        </p>
                    </div>
                    <select
                        aria-label="Mois"
                        value={periode}
                        onChange={(e) => router.get(route('admin.commissions.index'), { periode: e.target.value }, { preserveScroll: true })}
                        className="rounded-md border bg-background px-3 py-1.5 text-sm"
                    >
                        {periodes.map((p) => (
                            <option key={p} value={p}>{moisEnClair(p)}</option>
                        ))}
                    </select>
                </div>

                <OngletsReleves actif="commissions" />

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">{moisEnClair(periode)}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {parCentre.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Aucune commission ce mois-ci.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="py-2 pr-3 font-medium">Centre</th>
                                            <th className="py-2 pr-3 text-right font-medium">Comptes</th>
                                            <th className="py-2 pr-3 text-right font-medium">Lignes</th>
                                            <th className="py-2 pr-3 text-right font-medium">Encaissé</th>
                                            <th className="py-2 text-right font-medium">Commission</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {parCentre.map((c) => (
                                            <tr key={`${c.center_id}-${c.devise}`} className="border-b last:border-0">
                                                <td className="py-2 pr-3 font-medium">{c.centre}</td>
                                                <td className="py-2 pr-3 text-right tabular-nums">{c.comptes}</td>
                                                <td className="py-2 pr-3 text-right tabular-nums">{c.lignes}</td>
                                                <td className="py-2 pr-3 text-right tabular-nums">{formaterMontant(c.encaisse, c.devise)}</td>
                                                <td className="py-2 text-right font-semibold tabular-nums">{formaterMontant(c.commission, c.devise)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
