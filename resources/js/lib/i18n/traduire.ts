import { useCallback } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * Traduction des ecrans ecrits en francais (espace centre).
 *
 * La CLE est la phrase francaise elle-meme : `t('Tableau de bord')`. En anglais,
 * on cherche la phrase dans les dictionnaires `locales/en-centre/*.json` ;
 * absente, elle reste en francais (jamais de cle technique a l'ecran). Chaque
 * ecran a son propre dictionnaire : plusieurs ecrans se traduisent sans conflit.
 *
 * Variables : `t('{n} élèves', { n: 3 })`. Les libelles fixes envoyes par le
 * serveur se traduisent de la meme facon : `t(eleve.statut)`.
 */
const fichiers = import.meta.glob<{ default: Record<string, string> }>('../../locales/en-centre/*.json', { eager: true });
const ANGLAIS: Record<string, string> = Object.assign({}, ...Object.values(fichiers).map((f) => f.default));

export type Vars = Record<string, string | number | null | undefined>;

function remplir(texte: string, vars?: Vars): string {
    if (!vars) return texte;
    return texte.replace(/\{(\w+)\}/g, (m, cle) => (vars[cle] === undefined || vars[cle] === null ? m : String(vars[cle])));
}

/** La langue de l'interface : 'en' ou 'fr' (tout ce qui n'est pas anglais reste en francais). */
export function langueInterface(langue: string | undefined | null): 'fr' | 'en' {
    return (langue ?? '').toLowerCase().startsWith('en') ? 'en' : 'fr';
}

/** Traduit hors d'un composant (libelles construits dans une fonction). */
export function traduire(fr: string, langue: string, vars?: Vars): string {
    return remplir(langueInterface(langue) === 'en' ? (ANGLAIS[fr] ?? fr) : fr, vars);
}

/**
 * Dans un composant : `const { t, langue, locale } = useT();`
 * `locale` sert aux dates et aux nombres : `toLocaleDateString(locale, ...)`.
 */
export function useT() {
    // useTranslation : le composant se redessine quand la langue change.
    const { i18n } = useTranslation();
    const langue = langueInterface(i18n.language);
    const t = useCallback((fr: string, vars?: Vars) => traduire(fr, langue, vars), [langue]);

    return { t, langue, locale: langue === 'en' ? 'en-GB' : 'fr-FR' };
}
