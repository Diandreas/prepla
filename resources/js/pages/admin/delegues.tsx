import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle2, ChevronDown, Copy, Plus, Users } from 'lucide-react';

interface Bilan {
    inscrits: number;
    abonnes: number;
    encaisse: number;
    gagne: number;
    du: number;
    verse: number;
    a_reprendre: number;
}

interface Etudiant {
    nom: string;
    email: string;
    le: string | null;
    paye: number;
    acces_actif: boolean;
}

interface Delegue {
    id: number;
    nom: string;
    telephone: string | null;
    code: string;
    salle: string | null;
    taux: number;
    reduction: number;
    prix_mensuel: number | null;
    prix_barre: number | null;
    actif: boolean;
    email: string | null;
    lien: string;
    bilan: Bilan;
    etudiants: Etudiant[];
}

const champ = 'w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';
const fcfa = (n: number) => `${n.toLocaleString('fr-FR')} FCFA`;
const date = (iso: string | null) => (iso ? new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) : '—');

type Donnees = { nom: string; telephone: string; code: string; salle: string; taux: string; reduction: string; prix_mensuel: string; prix_barre: string; actif: boolean; email: string };
const vide: Donnees = { nom: '', telephone: '', code: '', salle: '', taux: '20', reduction: '10', prix_mensuel: '', prix_barre: '', actif: true, email: '' };

function Formulaire({ initial, onFini, delegue }: { initial: Donnees; onFini: () => void; delegue?: Delegue }) {
    const form = useForm<Donnees>(initial);
    function envoyer(e: React.FormEvent) {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => { form.reset(); onFini(); } };
        if (delegue) form.put(route('admin.delegues.update', delegue.id), options);
        else form.post(route('admin.delegues.store'), options);
    }
    const ligne = (cle: keyof Donnees, libelle: string, props: React.InputHTMLAttributes<HTMLInputElement> = {}) => (
        <div>
            <label htmlFor={`${cle}-${delegue?.id ?? 'n'}`} className="text-xs font-medium text-muted-foreground">{libelle}</label>
            <input
                id={`${cle}-${delegue?.id ?? 'n'}`}
                value={String(form.data[cle])}
                onChange={(e) => form.setData(cle, (cle === 'code' ? e.target.value.toUpperCase() : e.target.value) as never)}
                className={champ}
                {...props}
            />
            {form.errors[cle] && <p className="mt-1 text-xs text-rose-500">{form.errors[cle]}</p>}
        </div>
    );
    return (
        <form onSubmit={envoyer} className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {ligne('nom', 'Nom', { required: true, placeholder: 'Marie Ngo' })}
            {ligne('code', 'Code promo', { required: true, placeholder: 'MARIE' })}
            {ligne('salle', 'Salle, classe ou établissement', { placeholder: 'L2 Lettres bilingues, UY1' })}
            {ligne('telephone', 'Téléphone (WhatsApp)', { placeholder: '6 90 00 00 00' })}
            {ligne('taux', 'Commission du délégué (%)', { type: 'number', min: 0, max: 100, required: true })}
            {ligne('reduction', "Réduction pour l'étudiant (%)", { type: 'number', min: 0, max: 90, required: true })}
            {ligne('email', 'E-mail de son compte PrePla (facultatif)', { type: 'email', placeholder: 'pour sa page « Mon code »' })}
            {ligne('prix_mensuel', 'Prix du mois imposé, FCFA (facultatif)', { type: 'number', min: 0, placeholder: 'ex. 5000 pour une offre' })}
            {ligne('prix_barre', 'Prix barré affiché, FCFA (facultatif)', { type: 'number', min: 0, placeholder: 'ex. 7500' })}
            <div className="flex items-end gap-3">
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={form.data.actif} onChange={(e) => form.setData('actif', e.target.checked)} /> Actif
                </label>
                <Button type="submit" disabled={form.processing} className="ml-auto">{delegue ? 'Enregistrer' : 'Créer le délégué'}</Button>
            </div>
        </form>
    );
}

/**
 * Les delegues : chacun son code promo, ses inscrits, ce qu'ils ont paye, et ce
 * qui lui est du. Les commissions naissent a l'activation Mobile Money.
 */
export default function Delegues({ delegues, totaux }: { delegues: Delegue[]; totaux: { du: number; verse: number; encaisse: number } }) {
    const flash = (usePage().props as { flash?: { success?: string } }).flash;
    const [creer, setCreer] = useState(delegues.length === 0);
    const [ouvert, setOuvert] = useState<number | null>(null);
    const [edition, setEdition] = useState<number | null>(null);
    const [copie, setCopie] = useState<string | null>(null);

    const copier = (lien: string) => {
        navigator.clipboard?.writeText(lien);
        setCopie(lien);
        setTimeout(() => setCopie(null), 1500);
    };
    const verser = (d: Delegue) => {
        if (window.confirm(`Marquer ${fcfa(d.bilan.du)} comme versés à ${d.nom} ?`)) router.post(route('admin.delegues.verser', d.id), {}, { preserveScroll: true });
    };

    return (
        <AppLayout>
            <Head title="Délégués" />
            <div className="mx-auto w-full max-w-6xl space-y-5 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Délégués</h1>
                        <p className="text-sm text-muted-foreground">
                            Chaque délégué a son code promo : l'étudiant paie moins cher, le délégué touche sa part de chaque paiement Mobile Money.
                        </p>
                    </div>
                    <Button onClick={() => setCreer((v) => !v)}><Plus className="mr-1.5 h-4 w-4" /> Nouveau délégué</Button>
                </div>

                {flash?.success && (
                    <p role="status" className="flex items-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm font-medium text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100">
                        <CheckCircle2 className="h-4 w-4 shrink-0" /> {flash.success}
                    </p>
                )}

                <div className="grid gap-3 sm:grid-cols-3">
                    {[['Encaissé grâce aux délégués', totaux.encaisse], ['À verser aux délégués', totaux.du], ['Déjà versé', totaux.verse]].map(([libelle, n]) => (
                        <Card key={libelle as string}>
                            <CardContent className="p-4">
                                <p className="text-xs text-muted-foreground">{libelle}</p>
                                <p className="text-2xl font-black tabular-nums">{fcfa(n as number)}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {creer && (
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-base">Nouveau délégué</CardTitle></CardHeader>
                        <CardContent><Formulaire initial={vide} onFini={() => setCreer(false)} /></CardContent>
                    </Card>
                )}

                {delegues.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">Aucun délégué pour l'instant.</p>
                ) : (
                    <div className="space-y-3">
                        {delegues.map((d) => (
                            <Card key={d.id} className={d.actif ? '' : 'opacity-60'}>
                                <CardContent className="space-y-3 p-4">
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="text-base font-bold">{d.nom}</span>
                                                <Badge variant="secondary" className="font-mono">{d.code}</Badge>
                                                {!d.actif && <Badge variant="outline">Inactif</Badge>}
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                {[d.salle, d.telephone, d.email].filter(Boolean).join(' · ') || '—'} · {d.taux} % pour lui · {d.prix_mensuel ? <>mois à {fcfa(d.prix_mensuel)}{d.prix_barre ? <> au lieu de {fcfa(d.prix_barre)}</> : null}</> : <>−{d.reduction} % pour l'étudiant</>}
                                            </p>
                                            <button type="button" onClick={() => copier(d.lien)} className="mt-1 inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">
                                                <Copy className="h-3 w-3" /> {copie === d.lien ? 'Lien copié' : d.lien}
                                            </button>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Button variant="outline" size="sm" onClick={() => setEdition(edition === d.id ? null : d.id)}>Modifier</Button>
                                            <Button size="sm" disabled={d.bilan.du <= 0} onClick={() => verser(d)}>Marquer versé</Button>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-2 text-sm sm:grid-cols-5">
                                        {[
                                            ['Inscrits', String(d.bilan.inscrits)],
                                            ['Abonnés', String(d.bilan.abonnes)],
                                            ['Encaissé', fcfa(d.bilan.encaisse)],
                                            ['À verser', fcfa(d.bilan.du)],
                                            ['Versé', fcfa(d.bilan.verse)],
                                        ].map(([l, v]) => (
                                            <div key={l} className="rounded-lg bg-muted/50 px-3 py-2">
                                                <p className="text-[11px] text-muted-foreground">{l}</p>
                                                <p className={`font-bold tabular-nums ${l === 'À verser' && d.bilan.du > 0 ? 'text-amber-600' : ''}`}>{v}</p>
                                            </div>
                                        ))}
                                    </div>
                                    {d.bilan.a_reprendre > 0 && (
                                        <p className="text-xs font-medium text-rose-600">{fcfa(d.bilan.a_reprendre)} versés sur des paiements annulés depuis : à reprendre.</p>
                                    )}

                                    {edition === d.id && (
                                        <div className="rounded-xl border p-3">
                                            <Formulaire
                                                delegue={d}
                                                onFini={() => setEdition(null)}
                                                initial={{ nom: d.nom, telephone: d.telephone ?? '', code: d.code, salle: d.salle ?? '', taux: String(d.taux), reduction: String(d.reduction), prix_mensuel: d.prix_mensuel ? String(d.prix_mensuel) : '', prix_barre: d.prix_barre ? String(d.prix_barre) : '', actif: d.actif, email: d.email ?? '' }}
                                            />
                                        </div>
                                    )}

                                    <button type="button" onClick={() => setOuvert(ouvert === d.id ? null : d.id)} className="flex items-center gap-1 text-xs font-semibold text-muted-foreground hover:text-foreground">
                                        <Users className="h-3.5 w-3.5" /> {d.etudiants.length} étudiant{d.etudiants.length > 1 ? 's' : ''} rattaché{d.etudiants.length > 1 ? 's' : ''}
                                        <ChevronDown className={`h-3.5 w-3.5 transition ${ouvert === d.id ? 'rotate-180' : ''}`} />
                                    </button>
                                    {ouvert === d.id && d.etudiants.length > 0 && (
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-sm">
                                                <thead className="text-left text-[11px] uppercase text-muted-foreground">
                                                    <tr><th className="py-1 pr-3">Étudiant</th><th className="py-1 pr-3">Rattaché le</th><th className="py-1 pr-3">A payé</th><th className="py-1">Accès</th></tr>
                                                </thead>
                                                <tbody>
                                                    {d.etudiants.map((e) => (
                                                        <tr key={e.email} className="border-t">
                                                            <td className="py-1.5 pr-3"><span className="font-medium">{e.nom}</span> <span className="text-xs text-muted-foreground">{e.email}</span></td>
                                                            <td className="py-1.5 pr-3 text-muted-foreground">{date(e.le)}</td>
                                                            <td className="py-1.5 pr-3 tabular-nums">{e.paye > 0 ? fcfa(e.paye) : '—'}</td>
                                                            <td className="py-1.5">{e.acces_actif ? <Badge className="bg-emerald-600 hover:bg-emerald-600">Plus</Badge> : <span className="text-xs text-muted-foreground">gratuit</span>}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
