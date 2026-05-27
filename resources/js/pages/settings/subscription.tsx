import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useState } from 'react';

interface Plan {
    id: string;
    amount: number;
    interval: 'month' | 'year';
}

interface Props {
    currentPlan: string;
    stripeEnabled: boolean;
    isSubscribed: boolean;
    cancelAtPeriodEnd: boolean;
    renewsAt: number | null;
    plans: { monthly: Plan; annual: Plan };
}

export default function Subscription({ currentPlan, stripeEnabled, isSubscribed, cancelAtPeriodEnd, renewsAt, plans }: Props) {
    const [processing, setProcessing] = useState(false);
    const [selectedPlan, setSelectedPlan] = useState<'monthly' | 'annual'>('monthly');
    const isPremium = isSubscribed || currentPlan === 'premium';

    const handleCheckout = () => {
        // Native form POST → browser follows 303 cross-origin to Stripe.
        // Inertia would abort the cross-origin redirect.
        setProcessing(true);
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = route('subscription.checkout');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = csrf;
        form.appendChild(tokenInput);
        const priceInput = document.createElement('input');
        priceInput.type = 'hidden';
        priceInput.name = 'price_id';
        priceInput.value = plans[selectedPlan].id;
        form.appendChild(priceInput);
        document.body.appendChild(form);
        form.submit();
    };

    const handleCancel = () => {
        if (confirm('Votre accès Premium restera actif jusqu\'à la fin de la période payée. Confirmer ?')) {
            setProcessing(true);
            router.post(route('subscription.cancel'), {}, { onFinish: () => setProcessing(false) });
        }
    };

    const handleResume = () => {
        setProcessing(true);
        router.post(route('subscription.resume'), {}, { onFinish: () => setProcessing(false) });
    };

    const renewDate = renewsAt ? new Date(renewsAt * 1000).toLocaleDateString('fr-FR') : null;

    const features = [
        'Accès illimité à tous les examens (IELTS, DELF, Goethe...)',
        'Correction IA instantanée pour rédactions et oraux',
        'Expliqueur d\'erreurs illimité sur chaque exercice',
        'Générateur d\'exercices IA sans limites quotidiennes',
        'Statistiques de progression avancées',
        'Audio TTS pour tous les exercices d\'écoute',
    ];

    return (
        <AppLayout>
            <Head title="Abonnement" />

            <div className="mx-auto max-w-2xl space-y-8 py-6 pb-24 md:py-10 px-4">
                <div className="md:hidden mb-2">
                    <Link href={route('profile.edit')} className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground">
                        ← Retour au profil
                    </Link>
                </div>

                <div className="text-center space-y-2">
                    <h1 className="text-3xl font-black tracking-tight">Votre abonnement</h1>
                    <p className="text-muted-foreground">Boostez votre préparation et réussissez votre examen</p>
                </div>

                {/* Plan gratuit */}
                <Card className={`border-2 transition-all ${!isPremium ? 'border-primary ring-1 ring-primary' : 'border-border opacity-70'}`}>
                    {!isPremium && <Badge className="absolute top-3 right-3">PLAN ACTUEL</Badge>}
                    <CardHeader>
                        <CardTitle>Standard</CardTitle>
                        <CardDescription>Pour découvrir la plateforme</CardDescription>
                        <div className="mt-2 flex items-baseline gap-1">
                            <span className="text-3xl font-black">0€</span>
                            <span className="text-sm text-muted-foreground">/mois</span>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-muted-foreground">
                        <p>✓ 1 examen au choix</p>
                        <p>✓ Exercices de base</p>
                        <p className="opacity-50">✗ Pas de correction IA avancée</p>
                    </CardContent>
                </Card>

                {/* Plan Premium */}
                <Card className={`relative border-2 shadow-xl transition-all ${isPremium ? 'border-amber-400 ring-1 ring-amber-400' : 'border-border hover:border-amber-400/50'}`}>
                    <div className="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent pointer-events-none rounded-xl" />
                    {isPremium && <Badge className="absolute top-3 right-3 bg-amber-400 text-amber-950 font-bold">ACTIF</Badge>}

                    <CardHeader>
                        <div className="text-amber-600 font-bold text-xs uppercase tracking-widest mb-1">⭐ Recommandé</div>
                        <CardTitle className="text-2xl font-black">PrePla Plus</CardTitle>
                        <CardDescription>L'expérience complète sans limites</CardDescription>

                        {!isPremium && (
                            <div className="mt-4 flex gap-2">
                                <button
                                    onClick={() => setSelectedPlan('monthly')}
                                    className={`flex-1 rounded-xl border-2 p-3 text-center transition-all ${selectedPlan === 'monthly' ? 'border-amber-400 bg-amber-50' : 'border-border'}`}
                                >
                                    <div className="text-2xl font-black text-amber-600">9.99€</div>
                                    <div className="text-xs text-muted-foreground">/ mois</div>
                                </button>
                                <button
                                    onClick={() => setSelectedPlan('annual')}
                                    className={`flex-1 rounded-xl border-2 p-3 text-center transition-all relative ${selectedPlan === 'annual' ? 'border-amber-400 bg-amber-50' : 'border-border'}`}
                                >
                                    <div className="absolute -top-2 left-1/2 -translate-x-1/2 bg-emerald-500 text-white text-[10px] font-bold px-2 rounded-full">-33%</div>
                                    <div className="text-2xl font-black text-amber-600">79.99€</div>
                                    <div className="text-xs text-muted-foreground">/ an</div>
                                </button>
                            </div>
                        )}
                    </CardHeader>

                    <CardContent className="space-y-2">
                        <div className="h-px bg-border my-2" />
                        {features.map((f, i) => (
                            <div key={i} className="flex gap-2 text-sm font-medium">
                                <span className="text-amber-500">✓</span>
                                <span>{f}</span>
                            </div>
                        ))}
                    </CardContent>

                    <CardFooter className="flex-col gap-3">
                        {isPremium ? (
                            <>
                                <div className="w-full rounded-xl bg-amber-50 border border-amber-100 p-3 text-center text-sm font-bold text-amber-700">
                                    ✓ Abonnement Premium actif
                                </div>
                                {renewDate && !cancelAtPeriodEnd && (
                                    <p className="text-xs text-muted-foreground text-center">Prochain renouvellement : {renewDate}</p>
                                )}
                                {cancelAtPeriodEnd ? (
                                    <>
                                        <p className="text-xs text-orange-600 text-center font-medium">Annulé — accès jusqu'au {renewDate}</p>
                                        <Button variant="outline" className="w-full" onClick={handleResume} disabled={processing}>
                                            {processing ? 'Chargement...' : 'Réactiver l\'abonnement'}
                                        </Button>
                                    </>
                                ) : (
                                    <Button variant="ghost" className="w-full text-muted-foreground text-xs" onClick={handleCancel} disabled={processing}>
                                        {processing ? 'Chargement...' : 'Annuler l\'abonnement'}
                                    </Button>
                                )}
                            </>
                        ) : (
                            <Button
                                className="w-full h-14 bg-amber-400 hover:bg-amber-500 text-amber-950 font-black text-lg rounded-2xl shadow-lg disabled:opacity-50"
                                onClick={handleCheckout}
                                disabled={processing || !stripeEnabled}
                            >
                                {processing ? 'Redirection...' : `S'abonner ${selectedPlan === 'monthly' ? '9.99€/mois' : '79.99€/an'}`}
                            </Button>
                        )}
                        <p className="text-center text-[11px] text-muted-foreground">Paiement sécurisé Stripe · Annulable à tout moment</p>
                    </CardFooter>
                </Card>
            </div>
        </AppLayout>
    );
}
