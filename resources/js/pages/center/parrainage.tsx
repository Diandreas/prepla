import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Check, Copy } from 'lucide-react';
import { formaterMontant } from '@/lib/montant-stripe';

interface Total { devise: string; encaisse: number; commission: number }
interface Ligne { id: number; type: 'charge' | 'remboursement'; encaisse: number; commission: number; devise: string; date: string | null }

/** « 2026-11 » -> « novembre 2026 ». */
function moisEnClair(periode: string): string {
    const [annee, mois] = periode.split('-').map(Number);
    return new Date(annee, mois - 1, 1).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
}

/**
 * Le parrainage, pour le responsable du centre : le lien a partager, les comptes
 * qu'il a amenes, et ce que leurs abonnements lui rapportent ce mois-ci.
 */
export default function Parrainage({
    centre,
    lien,
    code,
    taux,
    comptesParraines,
    periode,
    totaux,
    lignes,
}: {
    centre: { id: number; name: string };
    lien: string;
    code: string;
    taux: number;
    comptesParraines: number;
    periode: string;
    totaux: Total[];
    lignes: Ligne[];
}) {
    const [copie, setCopie] = useState(false);

    async function copier() {
        try {
            await navigator.clipboard.writeText(lien);
            setCopie(true);
        } catch {
            setCopie(false);
        }
    }

    return (
        <AppLayout>
            <Head title={`Parrainage — ${centre.name}`} />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div>
                    <p className="text-sm text-muted-foreground">{centre.name}</p>
                    <h1 className="text-2xl font-bold tracking-tight">Parrainage</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Partagez ce lien. Chaque personne qui crée son compte en le suivant vous est rattachée,
                        et vous recevez {taux} % de chacun de ses paiements d'abonnement, tant qu'elle reste abonnée.
                    </p>
                </div>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Votre lien de parrainage</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <code className="min-w-0 flex-1 break-all rounded-md border bg-muted/40 px-3 py-2 text-sm">{lien}</code>
                            <Button type="button" size="sm" variant="outline" onClick={copier}>
                                {copie ? <Check className="mr-1.5 h-4 w-4" /> : <Copy className="mr-1.5 h-4 w-4" />}
                                {copie ? 'Copié' : 'Copier le lien'}
                            </Button>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Code du centre : <span className="font-mono font-medium tracking-wider">{code}</span>. Seuls les
                            nouveaux comptes sont rattachés ; un membre de votre équipe ne peut pas l'être.
                        </p>
                    </CardContent>
                </Card>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Card>
                        <CardHeader className="pb-1">
                            <p className="text-sm text-muted-foreground">Comptes parrainés</p>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold tabular-nums">{comptesParraines}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-1">
                            <p className="text-sm text-muted-foreground">Commissions de {moisEnClair(periode)}</p>
                        </CardHeader>
                        <CardContent>
                            {totaux.length === 0 ? (
                                <p className="text-3xl font-bold tabular-nums">0</p>
                            ) : (
                                totaux.map((t) => (
                                    <p key={t.devise} className="text-3xl font-bold tabular-nums">
                                        {formaterMontant(t.commission, t.devise)}
                                    </p>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Détail du mois</CardTitle>
                        <p className="text-xs text-muted-foreground">
                            Le montant dû est arrêté sur le relevé mensuel. Un remboursement fait une ligne négative.
                        </p>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {lignes.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Aucun paiement parrainé ce mois-ci.</p>
                        ) : (
                            lignes.map((l) => (
                                <div key={l.id} className="flex flex-wrap items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm">
                                    <span className="flex items-center gap-2">
                                        <Badge variant={l.type === 'charge' ? 'default' : 'secondary'}>
                                            {l.type === 'charge' ? 'Paiement' : 'Remboursement'}
                                        </Badge>
                                        {l.date && (
                                            <span className="text-muted-foreground">
                                                {new Date(l.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' })}
                                            </span>
                                        )}
                                    </span>
                                    <span className="flex items-center gap-3 tabular-nums">
                                        <span className="text-muted-foreground">{formaterMontant(l.encaisse, l.devise)}</span>
                                        <span className="font-medium">{formaterMontant(l.commission, l.devise)}</span>
                                    </span>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
