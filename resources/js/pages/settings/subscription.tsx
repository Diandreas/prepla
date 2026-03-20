import { Head, Link, router } from '@inertiajs/react';
function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useState } from 'react';

interface Props {
    currentPlan: string;
}

export default function Subscription({ currentPlan }: Props) {
    const [processing, setProcessing] = useState(false);
    const isPremium = currentPlan === 'premium';

    const handleUpgrade = () => {
        setProcessing(true);
        router.post(route('subscription.upgrade'), {}, {
            onFinish: () => setProcessing(false)
        });
    };

    const handleCancel = () => {
        if (confirm("Êtes-vous sûr de vouloir repasser au plan gratuit ? Vous perdrez vos avantages Premium.")) {
            setProcessing(true);
            router.post(route('subscription.cancel'), {}, {
                onFinish: () => setProcessing(false)
            });
        }
    };

    const features = [
        { icon: <Icon name="sparkles" size={20} className="text-amber-500" />, text: "Accès illimité à tous les examens (IELTS, TOEFL, TCF...)" },
        { icon: <Icon name="zap" size={20} className="text-blue-500" />, text: "Correction IA instantanée pour vos rédactions et oraux" },
        { icon: <Icon name="shield" size={20} className="text-emerald-500" />, text: "Expliqueur d'erreurs illimité sur chaque exercice" },
        { icon: <Icon name="award" size={20} className="text-purple-500" />, text: "Générateur d'exercices IA sans limites quotidiennes" },
        { icon: <Icon name="check" size={20} className="text-sky-500" />, text: "Statistiques de progression avancées" },
    ];

    return (
        <AppLayout>
            <Head title="Abonnement" />

            <div className="mx-auto max-w-2xl space-y-8 py-6 pb-24 md:py-10 px-4">
                {/* Mobile Back Button */}
                <div className="md:hidden mb-6">
                    <Link href={route('profile.edit')} className="inline-flex items-center text-sm font-medium text-muted-foreground hover:text-foreground">
                        <img src="/icons/chevron-right.png" alt="" width={16} height={16} style={{ objectFit: 'contain', transform: 'rotate(180deg)' }} className="mr-1" />
                        Retour au profil
                    </Link>
                </div>

                <div className="text-center space-y-2 mb-10">
                    <h1 className="text-3xl font-black tracking-tight">Choisissez votre plan</h1>
                    <p className="text-muted-foreground italic">Boostez votre préparation et réussissez votre examen</p>
                </div>

                {/* Plan Comparison */}
                <div className="grid gap-6">
                    {/* Free Plan */}
                    <Card className={`relative overflow-hidden transition-all border-2 ${!isPremium ? 'border-primary ring-1 ring-primary' : 'border-border opacity-80'}`}>
                        {!isPremium && <Badge className="absolute top-3 right-3 bg-primary text-white">PLAN ACTUEL</Badge>}
                        <CardHeader>
                            <CardTitle className="text-xl font-bold">Standard</CardTitle>
                            <CardDescription>Pour découvrir la plateforme</CardDescription>
                            <div className="mt-4 flex items-baseline gap-1">
                                <span className="text-3xl font-black">0€</span>
                                <span className="text-sm text-muted-foreground">/mois</span>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center gap-2 text-sm">
                                <Icon name="check" size={16} className="text-emerald-500" />
                                <span>1 examen au choix</span>
                            </div>
                            <div className="flex items-center gap-2 text-sm">
                                <Icon name="check" size={16} className="text-emerald-500" />
                                <span>Exercices de base</span>
                            </div>
                            <div className="flex items-center gap-2 text-sm opacity-40 italic">
                                <span className="h-4 w-4" />
                                <span>Pas de correction IA avancée</span>
                            </div>
                        </CardContent>
                        <CardFooter>
                            {isPremium ? (
                                <Button variant="outline" className="w-full font-bold" onClick={handleCancel} disabled={processing}>
                                    Repasser au plan Standard
                                </Button>
                            ) : (
                                <Button variant="secondary" className="w-full font-bold" disabled>
                                    Votre plan actuel
                                </Button>
                            )}
                        </CardFooter>
                    </Card>

                    {/* Premium Plan */}
                    <Card className={`relative overflow-hidden transition-all border-2 shadow-xl ${isPremium ? 'border-amber-400 ring-1 ring-amber-400' : 'border-border hover:border-amber-400/50'}`}>
                        <div className="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent pointer-events-none" />
                        {isPremium && <Badge className="absolute top-3 right-3 bg-amber-400 text-amber-950 font-bold">ACTIF</Badge>}
                        <CardHeader>
                            <div className="flex items-center gap-2 text-amber-600 font-bold text-sm uppercase tracking-widest">
                                <Icon name="sparkles" size={16} />
                                Recommandé
                            </div>
                            <CardTitle className="text-2xl font-black">PrePla Plus</CardTitle>
                            <CardDescription>L'expérience complète sans limites</CardDescription>
                            <div className="mt-4 flex items-baseline gap-1">
                                <span className="text-4xl font-black text-amber-600">9.99€</span>
                                <span className="text-sm text-muted-foreground font-bold">/mois</span>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="h-px bg-border my-2" />
                            {features.map((feature, i) => (
                                <div key={i} className="flex gap-3 text-sm font-medium">
                                    <div className="mt-0.5">{feature.icon}</div>
                                    <span>{feature.text}</span>
                                </div>
                            ))}
                        </CardContent>
                        <CardFooter>
                            {isPremium ? (
                                <div className="w-full space-y-3">
                                    <div className="flex items-center justify-center gap-2 rounded-xl bg-amber-50 p-3 text-sm font-bold text-amber-700 border border-amber-100">
                                        <Icon name="shield" size={16} />
                                        Abonnement actif via Carte Bancaire
                                    </div>
                                    <p className="text-center text-[10px] text-muted-foreground">Prochaine facturation le {new Date(new Date().setMonth(new Date().getMonth() + 1)).toLocaleDateString()}</p>
                                </div>
                            ) : (
                                <Button 
                                    className="w-full h-14 bg-amber-400 hover:bg-amber-500 text-amber-950 font-black text-lg shadow-lg hover:shadow-amber-400/20 rounded-2xl"
                                    onClick={handleUpgrade}
                                    disabled={processing}
                                >
                                    {processing ? 'Chargement...' : 'Devenir Premium'}
                                </Button>
                            )}
                        </CardFooter>
                    </Card>
                </div>

                <div className="rounded-2xl bg-muted/30 p-6 text-center space-y-4 border border-dashed border-border">
                    <Icon name="credit-card" size={32} className="mx-auto text-muted-foreground opacity-50" />
                    <div className="space-y-1">
                        <p className="text-sm font-bold">Paiement sécurisé</p>
                        <p className="text-xs text-muted-foreground">
                            Annulez à tout moment en un clic. Pas d'engagement de durée.
                        </p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
