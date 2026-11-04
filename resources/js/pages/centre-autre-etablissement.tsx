import { Head, Link } from '@inertiajs/react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

/**
 * Sur l'adresse d'un centre, un compte connecté qui suit un autre établissement
 * (EnsureMembreDuCentreDeLHote l'envoie ici). Rien du centre de l'adresse n'est
 * montré au-delà de son nom.
 */
export default function CentreAutreEtablissement({
    centreIci,
    sesCentres,
}: {
    centreIci: string;
    sesCentres: { nom: string; url: string }[];
}) {
    return (
        <AuthLayout
            title="Ce compte suit un autre établissement"
            description={`Vous êtes sur l'adresse de « ${centreIci} », mais ce compte n'en fait pas partie.`}
        >
            <Head title="Autre établissement" />

            <div className="space-y-4 text-sm">
                {sesCentres.length > 0 && (
                    <div className="space-y-2">
                        <p className="text-muted-foreground">Retrouvez votre espace :</p>
                        {sesCentres.map((c) => (
                            <Button key={c.url} asChild className="w-full">
                                <a href={c.url}>Aller chez « {c.nom} »</a>
                            </Button>
                        ))}
                    </div>
                )}

                <p className="text-muted-foreground">
                    Vous avez reçu un code de classe de « {centreIci} » ?{' '}
                    <Link href={route('center.join')} className="font-medium text-foreground underline underline-offset-4">
                        Saisir le code
                    </Link>
                </p>

                <TextLink href={route('logout')} method="post" className="mx-auto block text-center">
                    Se déconnecter et utiliser un autre compte
                </TextLink>
            </div>
        </AuthLayout>
    );
}
