export interface Etiquette {
    cle: string | number;
    libelle: string;
}
export type Critere<T> = (ligne: T) => Etiquette[] | null | undefined;
export interface Option {
    cle: string;
    libelle: string;
    nombre: number;
}
export interface Groupe<T> {
    cle: string;
    libelle: string;
    etiquette: Etiquette;
    lignes: T[];
}
export type ModeTri = 'alpha' | 'niveau';
export type Statut = 'actif' | 'a_relancer' | 'jamais';

export const SANS: string;
export const ORDRE_NIVEAUX: string[];
export const STATUTS: Record<Statut, string>;
export function normaliser(texte: unknown): string;
export function correspondRecherche(recherche: string, champs: Array<string | null | undefined>): boolean;
export function etiquettesDe<T>(ligne: T, critere: Critere<T>, libelleSans?: string): Etiquette[];
export function comparerCles(a: Etiquette, b: Etiquette, mode?: ModeTri): number;
export function filtrer<T>(
    lignes: T[],
    options?: {
        recherche?: string;
        champsRecherche?: (ligne: T) => Array<string | null | undefined>;
        criteres?: Record<string, { etiquettes: Critere<T> }>;
        filtres?: Record<string, string>;
    },
): T[];
export function optionsDe<T>(lignes: T[], critere: Critere<T>, options?: { mode?: ModeTri; libelleSans?: string }): Option[];
export function regrouper<T>(lignes: T[], critere: Critere<T>, options?: { mode?: ModeTri; libelleSans?: string }): Groupe<T>[];
export function pluriel(nombre: number, singulier: string, pluriel?: string): string;
export function compterStatuts(lignes: Array<{ statut: string }>): { total: number } & Record<Statut, number>;
export function celluleCsv(valeur: unknown): string;
export function versCsv(entetes: string[], lignes: unknown[][]): string;
export function lireEtat<K extends string>(recherche: string | null | undefined, noms: readonly K[]): Record<K, string>;
export function ecrireEtat(rechercheActuelle: string | null | undefined, etat: Record<string, string>): string;
export function entreeHistorique<T>(etatActuel: T, adresse: string): T;
export function elevesDistincts(classes: Array<{ student_ids?: number[] }>): number;
export function joursCalendaires(iso: string, maintenant?: Date): number | null;
export function activiteCoherente(
    iso: string | null,
    statut: string,
    seuil: number,
    formater: (iso: string | null) => string,
    maintenant?: Date,
): string;
