import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle2, Search, Smartphone } from 'lucide-react';

interface Compte {
    id: number;
    name: string;
    email: string;
    inscrit_le: string | null;
    premium_until: string | null;
    acces_actif: boolean;
    abonne_stripe: boolean;
}

interface Activation {
    id: number;
    email: string;
    formule: string;
    jours: number;
    moyen: string;
    montant: number | null;
    reference: string | null;
    fin: string;
    le: string | null;
    par: string | null;
    annulee: boolean;
}

const MOYENS: Record<string, string> = { orange: 'Orange Money', mtn: 'MTN MoMo', autre: 'Autre' };
const FORMULES: Record<string, string> = { mensuel: '1 mois', trimestriel: '3 mois', annuel: '1 an', jours: 'Autre durée' };
const champ = 'w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

const date = (iso: string | null) => (iso ? new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : '—');
const fcfa = (n: number | null) => (n == null ? '—' : `${n.toLocaleString('fr-FR')} FCFA`);

/**
 * Activer un compte paye par Orange Money ou MTN MoMo : coller l'e-mail recu sur
 * WhatsApp, choisir la duree, valider. Un acces en cours est prolonge.
 */
export default function Activations({
    recherche,
    compte,
    prix,
    activations,
}: {
    recherche: string;
    compte: Compte | null;
    formules: Record<string, number>;
    prix: { mensuel: number; annuel: number };
    activations: Activation[];
}) {
    const flash = (usePage().props as { flash?: { success?: string } }).flash;
    const [email, setEmail] = useState(recherche);
    const form = useForm({
        email: compte?.email ?? recherche,
        formule: 'mensuel',
        jours: '',
        moyen: 'orange',
        montant: String(prix.mensuel),
        reference: '',
    });

    function chercher(e: React.FormEvent) {
        e.preventDefault();
        router.get(route('admin.activations.index'), { email: email.trim() }, { preserveState: false });
    }

    function choisirFormule(f: string) {
        form.setData((d) => ({
            ...d,
            formule: f,
            montant: f === 'mensuel' ? String(prix.mensuel) : f === 'annuel' ? String(prix.annuel) : f === 'trimestriel' ? String(prix.mensuel * 3) : d.montant,
        }));
    }

    function activer(e: React.FormEvent) {
        e.preventDefault();
        form.post(route('admin.activations.store'), { preserveScroll: true });
    }

    return (
        <AppLayout>
            <Head title="Activations Mobile Money" />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Activer un compte</h1>
                    <p className="text-sm text-muted-foreground">
                        Paiement reçu par Orange Money ou MTN MoMo : colle l'e-mail envoyé sur WhatsApp, choisis la durée, valide.
                    </p>
                </div>

                {flash?.success && (
                    <p role="status" className="flex items-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm font-medium text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100">
                        <CheckCircle2 className="h-4 w-4 shrink-0" /> {flash.success}
                    </p>
                )}

                <Card>
                    <CardContent className="p-4">
                        <form onSubmit={chercher} className="flex flex-col gap-2 sm:flex-row">
                            <label htmlFor="chercher-email" className="sr-only">E-mail du compte</label>
                            <input
                                id="chercher-email"
                                type="email"
                                inputMode="email"
                                autoFocus
                                placeholder="email@exemple.com"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                className={champ}
                            />
                            <Button type="submit" className="shrink-0">
                                <Search className="mr-1.5 h-4 w-4" /> Chercher
                            </Button>
                        </form>
                        {recherche && !compte && (
                            <p className="mt-3 text-sm text-rose-600 dark:text-rose-400">
                                Aucun compte avec l'e-mail « {recherche} ». Vérifie l'orthographe, ou demande à la personne de s'inscrire d'abord.
                            </p>
                        )}
                    </CardContent>
                </Card>

                {compte && (
                    <Card>
                        <CardHeader className="pb-2">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <CardTitle className="text-base">{compte.name}</CardTitle>
                                {compte.acces_actif ? <Badge className="bg-emerald-600 hover:bg-emerald-600">Accès Plus actif</Badge> : <Badge variant="secondary">Gratuit</Badge>}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {compte.email} · inscrit le {date(compte.inscrit_le)}
                                {compte.premium_until && <> · Mobile Money jusqu'au {date(compte.premium_until)}</>}
                                {compte.abonne_stripe && <> · abonné par carte</>}
                            </p>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={activer} className="space-y-4">
                                <fieldset>
                                    <legend className="text-sm font-medium">Durée</legend>
                                    <div className="mt-2 flex flex-wrap gap-2">
                                        {Object.entries(FORMULES).map(([cle, libelle]) => (
                                            <button
                                                key={cle}
                                                type="button"
                                                aria-pressed={form.data.formule === cle}
                                                onClick={() => choisirFormule(cle)}
                                                className={`rounded-full border px-4 py-1.5 text-sm font-medium transition ${form.data.formule === cle ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-background hover:border-primary/50'}`}
                                            >
                                                {libelle}
                                            </button>
                                        ))}
                                    </div>
                                    {form.data.formule === 'jours' && (
                                        <div className="mt-2 max-w-[12rem]">
                                            <label htmlFor="jours" className="text-xs text-muted-foreground">Nombre de jours</label>
                                            <input id="jours" type="number" min={1} max={730} value={form.data.jours} onChange={(e) => form.setData('jours', e.target.value)} className={champ} />
                                        </div>
                                    )}
                                    {form.errors.jours && <p className="mt-1 text-xs text-rose-500">{form.errors.jours}</p>}
                                </fieldset>

                                <div className="grid gap-3 sm:grid-cols-3">
                                    <div>
                                        <label htmlFor="moyen" className="text-sm font-medium">Payé par</label>
                                        <select id="moyen" value={form.data.moyen} onChange={(e) => form.setData('moyen', e.target.value)} className={`mt-1 ${champ}`}>
                                            {Object.entries(MOYENS).map(([cle, libelle]) => <option key={cle} value={cle}>{libelle}</option>)}
                                        </select>
                                    </div>
                                    <div>
                                        <label htmlFor="montant" className="text-sm font-medium">Montant reçu (FCFA)</label>
                                        <input id="montant" type="number" min={0} value={form.data.montant} onChange={(e) => form.setData('montant', e.target.value)} className={`mt-1 ${champ}`} />
                                    </div>
                                    <div>
                                        <label htmlFor="reference" className="text-sm font-medium">Référence (facultatif)</label>
                                        <input id="reference" placeholder="ID de transaction" value={form.data.reference} onChange={(e) => form.setData('reference', e.target.value)} className={`mt-1 ${champ}`} />
                                    </div>
                                </div>

                                {form.errors.email && <p className="text-sm text-rose-600">{form.errors.email}</p>}

                                <Button type="submit" size="lg" disabled={form.processing} className="w-full sm:w-auto">
                                    <Smartphone className="mr-1.5 h-4 w-4" />
                                    {form.processing ? 'Activation…' : compte.premium_until && compte.acces_actif ? 'Prolonger l’accès' : 'Activer l’accès'}
                                </Button>
                                {compte.acces_actif && compte.premium_until && (
                                    <p className="text-xs text-muted-foreground">L'accès en cours est prolongé : les jours qui restent ne sont pas perdus.</p>
                                )}
                            </form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Dernières activations</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {activations.length === 0 && <p className="text-sm text-muted-foreground">Aucune activation pour le moment.</p>}
                        {activations.map((a) => (
                            <div key={a.id} className={`flex flex-wrap items-center justify-between gap-2 rounded-lg border px-3 py-2 text-sm ${a.annulee ? 'opacity-50' : ''}`}>
                                <div className="min-w-0">
                                    <p className="truncate font-medium">{a.email}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {FORMULES[a.formule] ?? `${a.jours} jours`} · {MOYENS[a.moyen] ?? a.moyen} · {fcfa(a.montant)}
                                        {a.reference && <> · réf. {a.reference}</>} · le {date(a.le)}{a.par && <> par {a.par}</>}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    {a.annulee ? (
                                        <Badge variant="secondary">Annulée</Badge>
                                    ) : (
                                        <>
                                            <span className="text-xs text-muted-foreground">jusqu'au {date(a.fin)}</span>
                                            <button
                                                type="button"
                                                className="text-xs font-semibold text-muted-foreground hover:text-rose-600"
                                                onClick={() => {
                                                    if (confirm(`Annuler cette activation pour ${a.email} ? Son accès recule de ${a.jours} jours.`)) {
                                                        router.delete(route('admin.activations.destroy', a.id), { preserveScroll: true });
                                                    }
                                                }}
                                            >
                                                Annuler
                                            </button>
                                        </>
                                    )}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
