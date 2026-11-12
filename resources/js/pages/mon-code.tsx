import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent } from '@/components/ui/card';

interface Props {
    delegue: { nom: string; code: string; taux: number; reduction: number; actif: boolean; lien: string };
    bilan: { inscrits: number; abonnes: number; encaisse: number; gagne: number; du: number; verse: number };
    etudiants: Array<{ prenom: string; le: string | null; abonne: boolean; gain: number }>;
}

const fcfa = (n: number) => `${n.toLocaleString('fr-FR')} FCFA`;

/**
 * La page du delegue : son code, son lien, combien de camarades l'ont utilise,
 * et ce qu'il a gagne. Prenoms seulement : il n'a pas a voir les e-mails.
 */
export default function MonCode({ delegue, bilan, etudiants }: Props) {
    const [copie, setCopie] = useState(false);
    const message = `Je prépare mon examen de langue avec PrePla. Avec mon code ${delegue.code}, tu as ${delegue.reduction} % de réduction sur l'abonnement : ${delegue.lien}`;

    const copier = () => {
        navigator.clipboard?.writeText(delegue.lien);
        setCopie(true);
        setTimeout(() => setCopie(false), 1500);
    };

    return (
        <AppLayout>
            <Head title="Mon code promo" />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Mon code promo</h1>
                    <p className="text-sm text-muted-foreground">
                        Partage ton code : tes camarades ont {delegue.reduction} % de réduction, et tu touches {delegue.taux} % de chacun de leurs paiements.
                    </p>
                </div>

                {!delegue.actif && (
                    <p className="rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">Ton code est suspendu pour l'instant. Contacte l'équipe PrePla.</p>
                )}

                <Card className="border-2 border-primary/40">
                    <CardContent className="space-y-4 p-5 text-center">
                        <p className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Ton code</p>
                        <p className="font-mono text-4xl font-black tracking-widest">{delegue.code}</p>
                        <div className="flex flex-col gap-2 sm:flex-row sm:justify-center">
                            <button type="button" onClick={copier} className="h-11 rounded-xl border border-border px-4 text-sm font-bold">
                                {copie ? 'Lien copié ✓' : 'Copier mon lien'}
                            </button>
                            <a
                                href={`https://wa.me/?text=${encodeURIComponent(message)}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="flex h-11 items-center justify-center rounded-xl bg-[#25D366] px-4 text-sm font-black text-white"
                            >
                                Partager sur WhatsApp
                            </a>
                        </div>
                        <p className="break-all text-xs text-muted-foreground">{delegue.lien}</p>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {[
                        ['Inscrits avec ton code', String(bilan.inscrits)],
                        ['Abonnés', String(bilan.abonnes)],
                        ['Gagné au total', fcfa(bilan.gagne)],
                        ['À recevoir', fcfa(bilan.du)],
                    ].map(([l, v]) => (
                        <Card key={l}>
                            <CardContent className="p-4">
                                <p className="text-xs text-muted-foreground">{l}</p>
                                <p className="text-xl font-black tabular-nums">{v}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>
                {bilan.verse > 0 && <p className="text-sm text-muted-foreground">Déjà reçu : {fcfa(bilan.verse)}.</p>}

                <Card>
                    <CardContent className="p-4">
                        <p className="mb-2 text-sm font-bold">Tes camarades</p>
                        {etudiants.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Personne encore : partage ton lien dans le groupe WhatsApp de ta salle.</p>
                        ) : (
                            <ul className="divide-y text-sm">
                                {etudiants.map((e, i) => (
                                    <li key={i} className="flex items-center justify-between py-2">
                                        <span className="font-medium">{e.prenom}</span>
                                        <span className={e.abonne ? 'font-semibold text-emerald-600' : 'text-muted-foreground'}>
                                            {e.abonne ? `Abonné · +${fcfa(e.gain)}` : 'Inscrit, pas encore abonné'}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
