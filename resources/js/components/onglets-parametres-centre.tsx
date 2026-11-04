import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Gift, KeyRound, LayoutGrid, Palette } from 'lucide-react';
import type { MenusDisponibles } from '@/types';

type Onglet = { titre: string; url: string; icone: typeof Palette; present: boolean };

/**
 * La barre commune « Paramètres du centre », en haut de chaque page de reglage
 * du responsable : on circule entre elles sans repasser par le menu. Un onglet
 * n'apparait que si sa route existe cote serveur (prop partagee menus).
 */
export default function OngletsParametresCentre() {
    const page = usePage();
    const props = page.props as { menus?: MenusDisponibles; auth?: { center?: { formule?: string } | null } };
    const menus = props.menus;
    // Hors formule revente, il n'y a rien a vendre : pas d'onglet, comme pour la carte.
    const enRevente = props.auth?.center?.formule === 'revente';
    const url = (page.url ?? '').split('?')[0];

    const onglets: Onglet[] = [
        { titre: "Vue d'ensemble", url: '/center/parametres', icone: LayoutGrid, present: !!menus?.parametres },
        { titre: 'Identité et examens', url: '/center/personnalisation', icone: Palette, present: !!menus?.personnalisation },
        { titre: 'Accès vendus', url: '/center/acces', icone: KeyRound, present: !!menus?.acces && enRevente },
        { titre: 'Parrainage', url: '/center/parrainage', icone: Gift, present: !!menus?.parrainage },
    ];
    const visibles = onglets.filter((o) => o.present);

    // Sans accueil des parametres, la barre n'a pas de sens : chaque page reste seule.
    if (!menus?.parametres) return null;

    const surLAccueil = url === '/center/parametres';

    return (
        <nav aria-label="Paramètres du centre" className="space-y-2">
            {!surLAccueil && (
                <Link
                    href="/center/parametres"
                    className="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="h-3.5 w-3.5" aria-hidden="true" />
                    Paramètres du centre
                </Link>
            )}
            {/* Defilement horizontal sur telephone plutot qu'un debordement de la page. */}
            <div className="-mx-4 overflow-x-auto px-4 md:mx-0 md:px-0">
                <ul className="flex w-max min-w-full gap-1 rounded-xl border border-border bg-card p-1">
                    {visibles.map((o) => {
                        const actif = url === o.url || url.startsWith(o.url + '/');
                        const Icone = o.icone;
                        return (
                            <li key={o.url} className="shrink-0">
                                <Link
                                    href={o.url}
                                    aria-current={actif ? 'page' : undefined}
                                    className={`flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium whitespace-nowrap transition-colors ${
                                        actif
                                            ? 'bg-primary text-primary-foreground shadow-sm'
                                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                    }`}
                                >
                                    <Icone className="h-4 w-4" aria-hidden="true" />
                                    {o.titre}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </div>
        </nav>
    );
}
