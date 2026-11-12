import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

export interface MobileMoney {
    orange: string;
    mtn: string;
    whatsapp: string;
    prix: { mensuel: number; annuel: number };
    /** Les prix sans reduction, pour barrer l'ancien prix quand un code s'applique. */
    prixPlein?: { mensuel: number; annuel: number };
    email: string;
    /** Le code delegue rattache au compte, s'il y en a un. */
    code?: { code: string; reduction: number } | null;
}

/** « 693427913 » -> « 693 42 79 13 », comme on le lit au Cameroun. */
function numero(n: string): string {
    const chiffres = n.replace(/\D/g, '');
    return chiffres.length === 9 ? `${chiffres.slice(0, 3)} ${chiffres.slice(3, 5)} ${chiffres.slice(5, 7)} ${chiffres.slice(7)}` : n;
}

const fcfa = (n: number) => `${n.toLocaleString('fr-FR')} FCFA`;

/**
 * Payer PrePla Plus par Orange Money ou MTN MoMo : on envoie le montant, puis la
 * capture sur WhatsApp avec son e-mail ; l'equipe active le compte a la main.
 */
export default function PaiementMobileMoney({ mobileMoney }: { mobileMoney: MobileMoney }) {
    const [formule, setFormule] = useState<'mensuel' | 'annuel'>('mensuel');
    const [moyen, setMoyen] = useState<'orange' | 'mtn'>('orange');
    const [copie, setCopie] = useState<string | null>(null);
    const montant = mobileMoney.prix[formule];
    const code = mobileMoney.code ?? null;
    const plein = mobileMoney.prixPlein;
    const form = useForm({ code: '' });
    const appliquer = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('delegue.appliquer'), { preserveScroll: true, onSuccess: () => form.reset() });
    };
    const destinataire = moyen === 'orange' ? mobileMoney.orange : mobileMoney.mtn;

    const message = [
        'Bonjour, je viens de payer PrePla Plus par Mobile Money.',
        `Formule : ${formule === 'mensuel' ? '1 mois' : '1 an'} (${fcfa(montant)})`,
        `Paiement : ${moyen === 'orange' ? 'Orange Money' : 'MTN MoMo'}`,
        `E-mail de mon compte : ${mobileMoney.email}`,
        ...(code ? [`Code promo : ${code.code}`] : []),
        'Voici la capture du paiement :',
    ].join('\n');
    const lienWhatsapp = `https://wa.me/${mobileMoney.whatsapp}?text=${encodeURIComponent(message)}`;

    function copier(texte: string) {
        try {
            navigator.clipboard?.writeText(texte.replace(/\s/g, ''));
            setCopie(texte);
            setTimeout(() => setCopie(null), 1500);
        } catch {
            /* le numero reste lisible a l'ecran */
        }
    }

    const choix = (actif: boolean) =>
        `flex-1 rounded-xl border-2 p-3 text-center transition-all ${actif ? 'border-amber-400 bg-amber-50 dark:bg-amber-950/30' : 'border-border hover:border-amber-300'}`;

    return (
        <Card className="border-2 border-amber-400/70 shadow-lg">
            <CardHeader>
                <CardTitle className="text-xl font-black">Payer par Mobile Money</CardTitle>
                <CardDescription>Orange Money ou MTN MoMo · activation en quelques minutes</CardDescription>
            </CardHeader>
            <CardContent className="space-y-5">
                <div className="flex gap-2">
                    <button type="button" aria-pressed={formule === 'mensuel'} onClick={() => setFormule('mensuel')} className={choix(formule === 'mensuel')}>
                        {code && plein && plein.mensuel !== mobileMoney.prix.mensuel && <div className="text-xs text-muted-foreground line-through">{fcfa(plein.mensuel)}</div>}
                        <div className="text-xl font-black text-amber-600">{fcfa(mobileMoney.prix.mensuel)}</div>
                        <div className="text-xs text-muted-foreground">1 mois</div>
                    </button>
                    <button type="button" aria-pressed={formule === 'annuel'} onClick={() => setFormule('annuel')} className={choix(formule === 'annuel')}>
                        {code && plein && plein.annuel !== mobileMoney.prix.annuel && <div className="text-xs text-muted-foreground line-through">{fcfa(plein.annuel)}</div>}
                        <div className="text-xl font-black text-amber-600">{fcfa(mobileMoney.prix.annuel)}</div>
                        <div className="text-xs text-muted-foreground">1 an</div>
                    </button>
                </div>

                {code ? (
                    <p className="rounded-xl bg-emerald-50 px-3 py-2 text-center text-sm font-semibold text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                        Code {code.code} appliqué{code.reduction > 0 ? ` : −${code.reduction} %` : ''}
                    </p>
                ) : (
                    <form onSubmit={appliquer} className="space-y-1">
                        <div className="flex gap-2">
                            <input
                                value={form.data.code}
                                onChange={(e) => form.setData('code', e.target.value.toUpperCase())}
                                placeholder="Code promo (facultatif)"
                                aria-label="Code promo"
                                className="h-11 min-w-0 flex-1 rounded-xl border border-border bg-background px-3 text-sm font-semibold uppercase tracking-wide"
                            />
                            <button type="submit" disabled={!form.data.code || form.processing} className="h-11 rounded-xl bg-foreground px-4 text-sm font-bold text-background disabled:opacity-40">
                                Appliquer
                            </button>
                        </div>
                        {form.errors.code && <p className="text-xs text-red-600">{form.errors.code}</p>}
                    </form>
                )}

                <ol className="space-y-4 text-sm">
                    <li className="flex gap-3">
                        <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-400 text-xs font-black text-amber-950">1</span>
                        <div className="min-w-0 flex-1 space-y-2">
                            <p className="font-semibold">Envoie {fcfa(montant)} à l'un de ces numéros</p>
                            <div className="grid gap-2 sm:grid-cols-2">
                                {([['orange', 'Orange Money', mobileMoney.orange], ['mtn', 'MTN MoMo', mobileMoney.mtn]] as const).map(([cle, nom, num]) => (
                                    <button
                                        key={cle}
                                        type="button"
                                        onClick={() => { setMoyen(cle); copier(numero(num)); }}
                                        aria-pressed={moyen === cle}
                                        className={`rounded-xl border-2 px-3 py-2 text-left transition ${moyen === cle ? (cle === 'orange' ? 'border-orange-500 bg-orange-50 dark:bg-orange-950/30' : 'border-yellow-400 bg-yellow-50 dark:bg-yellow-950/30') : 'border-border'}`}
                                    >
                                        <span className="block text-xs font-bold uppercase tracking-wide text-muted-foreground">{nom}</span>
                                        <span className="block text-lg font-black tabular-nums">{numero(num)}</span>
                                        <span className="block text-[11px] text-muted-foreground">{copie === numero(num) ? 'Numéro copié ✓' : 'Toucher pour copier'}</span>
                                    </button>
                                ))}
                            </div>
                        </div>
                    </li>
                    <li className="flex gap-3">
                        <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-400 text-xs font-black text-amber-950">2</span>
                        <p className="font-semibold">Fais une capture d'écran du message de confirmation du paiement.</p>
                    </li>
                    <li className="flex gap-3">
                        <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-400 text-xs font-black text-amber-950">3</span>
                        <div className="min-w-0 flex-1 space-y-2">
                            <p className="font-semibold">Envoie la capture sur WhatsApp, avec l'e-mail de ton compte</p>
                            <p className="text-xs text-muted-foreground">
                                Ton e-mail : <span className="font-semibold text-foreground">{mobileMoney.email}</span>. Le message est déjà écrit, il te reste à joindre la capture.
                            </p>
                            <a
                                href={lienWhatsapp}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-[#25D366] text-base font-black text-white shadow-md transition hover:bg-[#1ebe5a]"
                            >
                                Envoyer la capture sur WhatsApp
                            </a>
                            <p className="text-center text-xs text-muted-foreground">WhatsApp : {numero(mobileMoney.whatsapp.replace(/^237/, ''))}</p>
                        </div>
                    </li>
                </ol>

                <p className="rounded-xl bg-muted/60 px-3 py-2 text-center text-xs text-muted-foreground">
                    Ton accès PrePla Plus est activé dès réception, en quelques minutes en journée. Recharge cette page pour le voir.
                </p>
            </CardContent>
        </Card>
    );
}
