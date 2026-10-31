import { router, usePage } from '@inertiajs/react';
import { Building2 } from 'lucide-react';

interface CentreGere { id: number; name: string; role: string }

/**
 * Le centre dans lequel on agit, toujours visible pour le personnel — et, pour
 * qui en gere plusieurs, le moyen de passer de l'un a l'autre.
 *
 * Sans lui, une personne responsable de deux etablissements ne savait pas dans
 * lequel elle se trouvait, et n'avait aucun moyen d'atteindre le second.
 */
export function CentreCourant() {
    const { auth } = usePage().props as unknown as {
        auth?: { center?: { id: number; name: string; role: string } | null; centres?: CentreGere[] };
    };
    const centre = auth?.center;
    const centres = auth?.centres ?? [];

    if (!centre || !['center_admin', 'teacher'].includes(centre.role)) {
        return null;
    }

    return (
        <div className="mx-2 mb-1 rounded-lg border bg-muted/40 px-2.5 py-2 group-data-[collapsible=icon]:hidden">
            <p className="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                <Building2 className="h-3.5 w-3.5" /> {centres.length > 1 ? 'Centre' : 'Mon centre'}
            </p>
            {centres.length > 1 ? (
                <select
                    aria-label="Changer de centre"
                    className="mt-1 w-full truncate rounded-md border bg-background px-2 py-1.5 text-sm font-medium focus:border-primary focus:outline-none"
                    value={centre.id}
                    onChange={(e) => router.post(route('center.switch'), { center_id: Number(e.target.value) })}
                >
                    {centres.map((c) => (
                        <option key={c.id} value={c.id}>
                            {c.name}{c.role === 'teacher' ? ' (enseignant)' : ''}
                        </option>
                    ))}
                </select>
            ) : (
                <p className="mt-0.5 truncate text-sm font-semibold" title={centre.name}>{centre.name}</p>
            )}
        </div>
    );
}
