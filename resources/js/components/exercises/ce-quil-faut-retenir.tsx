import { Lightbulb } from 'lucide-react';

/**
 * Ce qu'il faut retenir d'une question ratée.
 *
 * L'IA produit trois choses pour chaque erreur : la règle en une phrase, la
 * forme correcte, et une astuce concrète pour la retenir. Le rapport de fin de
 * séance n'affichait que la première et jetait les deux autres — l'apprenant
 * lisait « le participe passé s'accorde avec l'auxiliaire être » et repartait
 * sans savoir quoi en faire, ni à quoi ressemble la bonne phrase.
 *
 * Les trois sont montrées ici, distinctes, pour qu'il n'ait pas besoin d'un
 * cahier à côté.
 */
export interface Retenir {
    concept?: string;
    evidence?: string;
    hint?: string;
    french_translation?: { concept?: string; evidence?: string; hint?: string };
}

/** Le texte d'un champ, quelle que soit la forme que l'IA lui a donnée. */
function texte(v: unknown): string {
    if (v == null) return '';
    if (typeof v === 'string') return v;
    if (typeof v === 'number' || typeof v === 'boolean') return String(v);
    if (Array.isArray(v)) return v.map(texte).filter(Boolean).join(' ');
    if (typeof v === 'object') {
        const o = v as Record<string, unknown>;
        return texte(o.concept ?? o.text ?? o.message ?? o.hint ?? o.value ?? Object.values(o)[0]);
    }
    return '';
}

/** Les balises <evidence> entourent la partie corrigée : on la met en valeur. */
function Evidence({ valeur }: { valeur: string }) {
    const morceaux = valeur.split(/<\/?evidence>/);
    return (
        <>
            {morceaux.map((m, i) =>
                i % 2 === 1 ? (
                    <strong key={i} className="text-emerald-700 dark:text-emerald-300">
                        {m}
                    </strong>
                ) : (
                    <span key={i}>{m}</span>
                ),
            )}
        </>
    );
}

export function CeQuilFautRetenir({ explication, reponseType }: { explication: unknown; reponseType?: string | null }) {
    const modele = texte(reponseType);

    if (!explication && !modele) return null;

    // Une explication rendue sous forme de simple phrase n'a rien de plus à
    // montrer — sauf la réponse type, qui vaut pour elle-même.
    if (typeof explication === 'string') {
        return (
            <div className="bg-muted/40 border-border mt-2 space-y-2 rounded-lg border p-3 text-sm">
                <p className="text-muted-foreground italic">{explication}</p>
                <ReponseType valeur={modele} />
            </div>
        );
    }

    const e = (explication ?? {}) as Retenir;
    const regle = texte(e.concept) || texte(e.french_translation?.concept);
    const forme = texte(e.evidence) || texte(e.french_translation?.evidence);
    const astuce = texte(e.hint) || texte(e.french_translation?.hint);

    if (!regle && !forme && !astuce && !modele) return null;

    return (
        <div className="bg-muted/40 border-border mt-2 space-y-2 rounded-lg border p-3 text-sm">
            {regle && <p className="text-foreground font-semibold">{regle}</p>}

            {forme && (
                <p className="text-muted-foreground">
                    <span className="opacity-70">La bonne forme : </span>
                    <Evidence valeur={forme} />
                </p>
            )}

            {astuce && (
                <p className="text-foreground flex items-start gap-2">
                    <Lightbulb className="mt-0.5 h-4 w-4 shrink-0 text-amber-500" aria-hidden="true" />
                    <span>
                        <span className="font-semibold">Pour le retenir : </span>
                        {astuce}
                    </span>
                </p>
            )}

            <ReponseType valeur={modele} />
        </div>
    );
}

/**
 * À quoi ressemble une bonne réponse, à son niveau.
 *
 * Une correction dit ce qui ne va pas ; elle ne montre pas ce qu'il fallait
 * écrire. Pour une rédaction ou une prise de parole, c'est pourtant le seul
 * repère utile — celui qu'un enseignant donne en lisant une bonne copie à voix
 * haute.
 */
function ReponseType({ valeur }: { valeur: string }) {
    if (!valeur) return null;

    return (
        <div className="border-border/60 mt-1 rounded-lg border border-dashed p-3">
            <p className="text-muted-foreground text-xs font-bold tracking-wide uppercase">Une réponse possible</p>
            <p className="text-foreground mt-1 leading-relaxed">{valeur}</p>
        </div>
    );
}
