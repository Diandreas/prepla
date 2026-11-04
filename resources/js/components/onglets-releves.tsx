import { Link } from '@inertiajs/react';

/**
 * Les onglets des relevés du super administrateur. Chaque lot livre son onglet
 * par sa route : un onglet ne s'affiche que si sa route existe côté serveur.
 */
const ONGLETS: { cle: string; titre: string; route: string }[] = [
    { cle: 'actifs', titre: 'Élèves actifs', route: 'admin.releves.index' },
    { cle: 'revente', titre: 'Revente', route: 'admin.releves.revente' },
    { cle: 'commissions', titre: 'Commissions', route: 'admin.commissions.index' },
];

export default function OngletsReleves({ actif }: { actif: string }) {
    const disponibles = ONGLETS.filter((o) => route().has(o.route));

    return (
        <nav className="flex flex-wrap gap-1 border-b" aria-label="Relevés">
            {disponibles.map((o) => (
                <Link
                    key={o.cle}
                    href={route(o.route)}
                    aria-current={o.cle === actif ? 'page' : undefined}
                    className={
                        '-mb-px border-b-2 px-3 py-2 text-sm font-medium transition ' +
                        (o.cle === actif ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground')
                    }
                >
                    {o.titre}
                </Link>
            ))}
        </nav>
    );
}
