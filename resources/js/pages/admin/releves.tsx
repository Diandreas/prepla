import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DataTable, type Column } from '@/components/ui/data-table';
import OngletsReleves from '@/components/onglets-releves';
import { dateHeure } from '@/lib/suivi';

interface LigneActifs {
    centre_id: number;
    centre: string;
    slug: string;
    is_active: boolean;
    periode: string;
    actifs: number | null;
    palier: string | null;
    computed_at: string | null;
    en_cours: boolean;
}

interface DonneesActifs {
    mois: string;
    centre: number | null;
    mois_disponibles: string[];
    centres: { id: number; name: string }[];
    mois_termine: boolean;
    non_calcules: number;
    lignes: LigneActifs[];
}

const MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

/** « 2026-10 » devient « octobre 2026 ». */
function libelleMois(periode: string): string {
    const [annee, mois] = periode.split('-').map(Number);
    return `${MOIS[mois - 1] ?? '?'} ${annee}`;
}

/** Le palier tel qu'on le lit : A1, A2, A3, ou « réseau » au-delà de 300. */
function libellePalier(palier: string | null): string {
    if (palier === null) return '—';
    return palier === 'reseau' ? 'Réseau' : palier;
}

const select = 'rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

/**
 * Les releves mensuels des centres (super administrateur). Onglet « Élèves
 * actifs » : par mois, tous les centres ; ou par centre, tous ses mois. Le mois
 * en cours est provisoire ; un mois termine montre ce qu'a arrete la commande.
 */
export default function Releves({ actifs }: { onglet: string; actifs: DonneesActifs }) {
    const filtre = actifs.centre !== null ? { centre: actifs.centre } : { mois: actifs.mois };
    const aller = (params: Record<string, string | number>) => router.get(route('admin.releves.index'), params, { preserveState: true });

    const calculer = (forcer: boolean) =>
        router.post(route('admin.releves.actifs.calculer'), { mois: actifs.mois, forcer }, { preserveScroll: true });

    const columns: Column<LigneActifs>[] = [
        actifs.centre !== null
            ? { key: 'periode', header: 'Mois', cell: (l) => <span className="font-semibold">{libelleMois(l.periode)}</span> }
            : {
                  key: 'centre',
                  header: 'Centre',
                  cell: (l) => (
                      <span className="font-semibold">
                          {l.centre}
                          {!l.is_active && <span className="ml-2 text-xs font-normal text-muted-foreground">(inactif)</span>}
                      </span>
                  ),
              },
        { key: 'actifs', header: 'Élèves actifs', sortValue: (l) => l.actifs ?? -1, cell: (l) => (l.actifs === null ? '—' : l.actifs) },
        { key: 'palier', header: 'Palier', cell: (l) => libellePalier(l.palier) },
        {
            key: 'etat',
            header: 'État',
            cell: (l) =>
                l.en_cours ? (
                    <Badge variant="secondary">En cours</Badge>
                ) : l.actifs === null ? (
                    <Badge variant="outline">Non calculé</Badge>
                ) : (
                    <span className="text-xs text-muted-foreground">Arrêté le {dateHeure(l.computed_at)}</span>
                ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Relevés" />
            <div className="mx-auto w-full max-w-5xl space-y-5 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Relevés</h1>
                    <p className="text-sm text-muted-foreground">Ce que chaque centre doit à PrePla, mois par mois.</p>
                </div>

                <OngletsReleves actif="actifs" />

                <p className="text-sm text-muted-foreground">
                    Un élève actif a fait au moins un exercice dans le mois (heure de Douala) pendant qu'il était élève du centre. Palier A1
                    jusqu'à 50, A2 jusqu'à 150, A3 jusqu'à 300, réseau au-delà.
                </p>

                <div className="flex flex-wrap items-center gap-2">
                    <select
                        className={select}
                        aria-label="Centre"
                        value={actifs.centre ?? ''}
                        onChange={(e) => aller(e.target.value ? { centre: e.target.value } : { mois: actifs.mois })}
                    >
                        <option value="">Tous les centres</option>
                        {actifs.centres.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.name}
                            </option>
                        ))}
                    </select>
                    {actifs.centre === null && (
                        <select className={select} aria-label="Mois" value={actifs.mois} onChange={(e) => aller({ mois: e.target.value })}>
                            {actifs.mois_disponibles.map((m) => (
                                <option key={m} value={m}>
                                    {libelleMois(m)}
                                </option>
                            ))}
                        </select>
                    )}
                    <Button asChild size="sm" variant="outline" className="ml-auto">
                        <a href={route('admin.releves.actifs.csv', filtre)}>Exporter (CSV)</a>
                    </Button>
                </div>

                {actifs.centre === null && actifs.mois_termine && (
                    <Card>
                        <CardContent className="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                            <span className="text-muted-foreground">
                                {actifs.non_calcules > 0
                                    ? `${actifs.non_calcules} centre(s) sans relevé arrêté pour ${libelleMois(actifs.mois)}.`
                                    : `Le relevé de ${libelleMois(actifs.mois)} est arrêté pour tous les centres.`}
                            </span>
                            {actifs.non_calcules > 0 ? (
                                <Button size="sm" onClick={() => calculer(false)}>
                                    Arrêter le relevé
                                </Button>
                            ) : (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => {
                                        if (confirm(`Recalculer ${libelleMois(actifs.mois)} pour tous les centres ? Les chiffres arrêtés seront remplacés.`)) calculer(true);
                                    }}
                                >
                                    Recalculer
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                )}

                <DataTable
                    columns={columns}
                    rows={actifs.lignes}
                    rowKey={(l) => `${l.centre_id}-${l.periode}`}
                    onRowClick={actifs.centre === null ? (l) => aller({ centre: l.centre_id }) : undefined}
                    empty="Aucun centre pour l'instant."
                />
            </div>
        </AppLayout>
    );
}
