import { Link } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import { Button } from '@/components/ui/button';

/**
 * L'en-tete des pages d'essai : le chemin vers l'inscription reste visible du
 * debut a la fin, sans jamais barrer l'exercice.
 */
export function EssaiEntete() {
    return (
        <header className="border-b bg-background/85 backdrop-blur-xl">
            <div className="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3">
                <Link href="/" className="flex items-center" aria-label="Accueil PrePla">
                    <AppLogo />
                </Link>
                <div className="flex items-center gap-2">
                    <Button asChild variant="ghost" size="sm" className="hidden sm:inline-flex">
                        <Link href={route('login')}>Se connecter</Link>
                    </Button>
                    <Button asChild size="sm">
                        <Link href={route('register')}>Créer un compte</Link>
                    </Button>
                </div>
            </div>
        </header>
    );
}
