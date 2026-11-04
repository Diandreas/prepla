import type { FormDataConvertible } from '@inertiajs/core';

export interface StockageReprise {
    getItem(cle: string): string | null;
    setItem(cle: string, valeur: string): void;
    removeItem(cle: string): void;
}

export interface Reprise {
    index: number;
    answers: Record<string, FormDataConvertible>;
    tempsPasse: number;
}

export const DUREE_DE_VIE_MS: number;
export const FORMATS_SANS_REPRISE: string[];
export function cleDeReprise(exerciseId: number | string, compte: number | string | null | undefined): string;
export function stockageDuNavigateur(): StockageReprise | null;
export function reponsesLegeres(answers: Record<string, unknown>): Record<string, FormDataConvertible>;
export function effacerReprise(stockage: StockageReprise | null, cle: string): void;
export function ecrireReprise(
    stockage: StockageReprise | null,
    cle: string,
    etat: { index: number; answers: Record<string, unknown>; questionIds: (string | number)[]; tempsPasse: number },
    maintenant?: number,
): boolean;
export function lireReprise(
    stockage: StockageReprise | null,
    cle: string,
    questionIds: (string | number)[],
    maintenant?: number,
): Reprise | null;
