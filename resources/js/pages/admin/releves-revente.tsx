import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import OngletsReleves from '@/components/onglets-releves';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dateHeure } from '@/lib/suivi';

interface Ligne {
    acces_id: number;
    eleve: string | null;
    ouvert_le: string | null;
    au: string | null;
    montant: number;
    moyen: string;
    reference: string | null;
    ouvert_par: string | null;
    coupe_le: string | null;
}

interface Releve {
    id: number | null;
    centre: string | null;
    centre_id: number;
    montant_brut: number;
    part_prepla: number;
    statut: string | null;
    computed_at: string | null;
    lignes: Ligne[];
}

const MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
const STATUTS: Record<string, string> = { brouillon: 'Brouillon', envoye: 'Envoyé', paye: 'Payé' };
const MOYENS: Record<string, string> = { mobile_money: 'Mobile money', cash: 'Espèces', autre: 'Autre' };

function libelleMois(periode: string): string {
    const [annee, mois] = periode.split('-').map(Number);
    return `${MOIS[mois - 1] ?? '?'} ${annee}`;
}

function fcfa(n: number): string {
    return `${n.toLocaleString('fr-FR')} FCFA`;
}

const select = 'rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

/**
 * Onglet « Revente » des relevés : par mois, la part PrePla des accès vendus
 * par chaque centre en formule revente. Le mois en cours est provisoire ; un
 * mois passé montre ce qu'a écrit la commande centres:releves.
 */
export default function ReleveRevente({
    mois,
    en_cours,
    mois_disponibles,
    taux,
    releves,
    statuts,
}: {
    mois: string;
    en_cours: boolean;
    mois_disponibles: string[];
    taux: number;
    releves: Releve[];
    statuts: string[];
}) {
    const { flash } = usePage().props as { flash?: { success?: string } };
    const [deplie, setDeplie] = useState<number | null>(null);
    const total = releves.reduce((s, r) => s + r.part_prepla, 0);

    const changerStatut = (r: Releve, statut: string) =>
        r.id !== null && router.patch(route('admin.releves.revente.statut', r.id), { statut }, { preserveScroll: true });

    return (
        <AppLayout>
            <Head title="Relevés — Revente" />
            <div className="mx-auto w-full max-w-5xl space-y-5 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Relevés</h1>
                    <p className="text-sm text-muted-foreground">
                        Part PrePla ({taux} %) des accès ouverts dans le mois par les centres en formule revente, coupés ou non.
                    </p>
                </div>

                <OngletsReleves actif="revente" />

                {flash?.success && (
                    <div className="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                        {flash.success}
                    </div>
                )}

                <div className="flex flex-wrap items-center gap-3">
                    <select className={select} value={mois} onChange={(e) => router.get(route('admin.releves.revente'), { mois: e.target.value }, { preserveState: true })}>
                        {mois_disponibles.map((m) => <option key={m} value={m}>{libelleMois(m)}</option>)}
                    </select>
                    {en_cours ? (
                        <Badge variant="secondary">Mois en cours : provisoire</Badge>
                    ) : (
                        <Button size="sm" variant="outline" onClick={() => router.post(route('admin.releves.revente.calculer'), { mois }, { preserveScroll: true })}>
                            Recalculer les brouillons
                        </Button>
                    )}
                    <span className="ml-auto text-sm">
                        Total part PrePla : <span className="font-semibold tabular-nums">{fcfa(total)}</span>
                    </span>
                </div>

                {releves.length === 0 ? (
                    <Card>
                        <CardContent className="pt-6 text-sm text-muted-foreground">
                            {en_cours ? 'Aucun centre en formule revente.' : `Aucun relevé de revente pour ${libelleMois(mois)}.`}
                        </CardContent>
                    </Card>
                ) : (
                    releves.map((r) => (
                        <Card key={r.centre_id}>
                            <CardHeader className="pb-2">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <CardTitle className="text-base">{r.centre ?? '—'}</CardTitle>
                                    {r.id !== null && r.statut !== null ? (
                                        <select className={select} value={r.statut} onChange={(e) => changerStatut(r, e.target.value)} aria-label="Statut du relevé">
                                            {statuts.map((s) => <option key={s} value={s}>{STATUTS[s] ?? s}</option>)}
                                        </select>
                                    ) : (
                                        <Badge variant="secondary">Provisoire</Badge>
                                    )}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Encaissé : {fcfa(r.montant_brut)} · part PrePla : <span className="font-semibold">{fcfa(r.part_prepla)}</span>
                                    {r.computed_at && <> · calculé le {dateHeure(r.computed_at)}</>}
                                </p>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {r.lignes.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">Aucun accès ouvert ce mois-là.</p>
                                ) : (
                                    <>
                                        <button type="button" className="text-sm text-primary hover:underline" onClick={() => setDeplie(deplie === r.centre_id ? null : r.centre_id)}>
                                            {deplie === r.centre_id ? 'Masquer' : 'Voir'} les {r.lignes.length} accès
                                        </button>
                                        {deplie === r.centre_id &&
                                            r.lignes.map((l) => (
                                                <div key={l.acces_id} className="flex flex-wrap items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm">
                                                    <span>
                                                        <span className="font-medium">{l.eleve ?? '—'}</span>
                                                        <span className="text-muted-foreground">
                                                            {' '}· {dateHeure(l.ouvert_le)} · {MOYENS[l.moyen] ?? l.moyen}
                                                            {l.reference ? ` · ${l.reference}` : ''}
                                                            {l.ouvert_par ? ` · par ${l.ouvert_par}` : ''}
                                                        </span>
                                                    </span>
                                                    <span className="flex items-center gap-2 tabular-nums">
                                                        {l.coupe_le && <Badge variant="secondary">Coupé</Badge>}
                                                        {fcfa(l.montant)}
                                                    </span>
                                                </div>
                                            ))}
                                    </>
                                )}
                            </CardContent>
                        </Card>
                    ))
                )}
            </div>
        </AppLayout>
    );
}
