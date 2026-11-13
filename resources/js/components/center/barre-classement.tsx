import { usePage } from '@inertiajs/react';
import { AT, CH, DE, FR, GB } from 'country-flag-icons/react/3x2';
import { Search, X } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { ecrireEtat, entreeHistorique, lireEtat, type Option } from '@/lib/classement-centre';
import { useT } from '@/lib/i18n/traduire';

/**
 * La barre d'outils des listes du centre (classes, eleves) : recherche,
 * filtres, « Regrouper par ». La vue choisie reste dans l'adresse, pour qu'un
 * lien copie rouvre exactement la meme vue.
 */

/**
 * L'etat de la vue, lu depuis l'adresse au premier affichage, puis recopie
 * dedans a chaque changement — sans requete au serveur ni nouveau rendu de la page.
 */
export function useVueDansAdresse<K extends string>(noms: readonly K[]) {
    const { url } = usePage();
    const [etat, setEtat] = useState<Record<K, string>>(() => lireEtat(url.split('?')[1] ?? '', noms));
    const premier = useRef(true);

    useEffect(() => {
        if (premier.current) {
            premier.current = false;
            return;
        }
        if (typeof window === 'undefined') return;
        const adresse = window.location.pathname + ecrireEtat(window.location.search, etat) + window.location.hash;
        // Pas router.replace : il change l'adresse de la page Inertia, et la mise
        // en page reconstruit alors tout le contenu (key={url}) a chaque lettre.
        window.history.replaceState(entreeHistorique(window.history.state, adresse), '', adresse);
    }, [etat]);

    const poser = useCallback((nom: K, valeur: string) => setEtat((avant) => ({ ...avant, [nom]: valeur })), []);
    const reinitialiser = useCallback(
        () => setEtat(Object.fromEntries(noms.map((n) => [n, ''])) as Record<K, string>),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [],
    );

    return { etat, poser, reinitialiser };
}

const DRAPEAUX: Record<string, React.ComponentType<{ title?: string; className?: string }>> = { GB, FR, DE, AT, CH };

/** Le drapeau d'une langue. La base porte l'emoji (🇩🇪), que Windows affiche en lettres : on dessine le drapeau. */
export function Drapeau({ emoji, className = 'h-3 w-[1.125rem]' }: { emoji: string | null | undefined; className?: string }) {
    if (!emoji) return null;
    const points = [...emoji].map((c) => (c.codePointAt(0) ?? 0) - 0x1f1e6);
    const code = points.length === 2 && points.every((p) => p >= 0 && p <= 25) ? String.fromCharCode(65 + points[0], 65 + points[1]) : '';
    const Image = DRAPEAUX[code];
    if (Image) return <Image className={`inline-block shrink-0 rounded-[2px] align-[-1px] ${className}`} />;
    return <span aria-hidden>{emoji}</span>;
}

export interface FiltreBarre {
    nom: string;
    /** « Langue », « Salle »… pour les lecteurs d'ecran. */
    libelle: string;
    /** Le choix vide : « Toutes les langues ». */
    tous: string;
    options: Option[];
}

const champ =
    'h-9 w-full min-w-0 rounded-lg border bg-background px-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary sm:w-auto';

export function BarreClassement({
    recherche,
    onRecherche,
    placeholder,
    filtres,
    valeurs,
    onFiltre,
    regroupements,
    regroupement,
    onRegroupement,
    onReinitialiser,
    actions,
}: {
    recherche: string;
    onRecherche: (valeur: string) => void;
    placeholder: string;
    filtres: FiltreBarre[];
    valeurs: Record<string, string>;
    onFiltre: (nom: string, valeur: string) => void;
    regroupements: { cle: string; libelle: string }[];
    regroupement: string;
    onRegroupement: (valeur: string) => void;
    onReinitialiser: () => void;
    actions?: React.ReactNode;
}) {
    const { t } = useT();
    const actif = recherche !== '' || regroupement !== '' || filtres.some((f) => valeurs[f.nom]);

    return (
        <div className="space-y-2 rounded-xl border border-border bg-card p-3">
            <div className="flex flex-wrap items-center gap-2">
                <label className="relative min-w-0 flex-1 basis-56">
                    <span className="sr-only">{t('Rechercher')}</span>
                    <Search className="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        type="search"
                        value={recherche}
                        onChange={(e) => onRecherche(e.target.value)}
                        placeholder={placeholder}
                        className={`${champ} border-border pl-8 sm:w-full`}
                    />
                </label>
                <label className="flex min-w-0 items-center gap-2 text-sm">
                    <span className="shrink-0 text-muted-foreground">{t('Regrouper par')}</span>
                    <select
                        value={regroupement}
                        onChange={(e) => onRegroupement(e.target.value)}
                        className={`${champ} ${regroupement ? 'border-primary' : 'border-border'}`}
                    >
                        <option value="">{t('Aucun')}</option>
                        {regroupements.map((r) => (
                            <option key={r.cle} value={r.cle}>
                                {r.libelle}
                            </option>
                        ))}
                    </select>
                </label>
                {actions}
            </div>
            <div className="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center">
                {filtres.map((f) => {
                    const valeur = valeurs[f.nom] ?? '';
                    // Une valeur venue d'un lien mais absente des donnees reste visible, pour pouvoir l'enlever.
                    const inconnue = valeur !== '' && !f.options.some((o) => o.cle === valeur);
                    return (
                        <label key={f.nom} className="min-w-0">
                            <span className="sr-only">{f.libelle}</span>
                            <select
                                value={valeur}
                                onChange={(e) => onFiltre(f.nom, e.target.value)}
                                className={`${champ} ${valeur ? 'border-primary font-medium' : 'border-border'}`}
                            >
                                <option value="">{f.tous}</option>
                                {inconnue && <option value={valeur}>{valeur} (0)</option>}
                                {f.options.map((o) => (
                                    <option key={o.cle} value={o.cle}>
                                        {o.libelle} ({o.nombre})
                                    </option>
                                ))}
                            </select>
                        </label>
                    );
                })}
                {actif && (
                    <button
                        type="button"
                        onClick={onReinitialiser}
                        className="col-span-2 inline-flex h-9 items-center justify-center gap-1 rounded-lg px-2.5 text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
                    >
                        <X className="h-4 w-4" /> {t('Tout effacer')}
                    </button>
                )}
            </div>
        </div>
    );
}

/** L'en-tete d'un groupe : « Allemand · 3 classes · 41 élèves ». */
export function EnTeteGroupe({ titre, details, icone }: { titre: string; details: string[]; icone?: React.ReactNode }) {
    return (
        <h2 className="flex flex-wrap items-center gap-x-2 gap-y-0.5 px-1 text-sm">
            {icone}
            <span className="font-semibold">{titre}</span>
            {details.map((d) => (
                <span key={d} className="text-muted-foreground">
                    · {d}
                </span>
            ))}
        </h2>
    );
}
