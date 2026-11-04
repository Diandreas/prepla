export type Periode = 'semaine' | 'mois' | 'total';
export type Distinction = 'en_feu' | 'regulier' | 'progression';

export interface StatsPeriode {
    xp: number;
    exercices: number;
    precision: number | null;
}
export interface EleveClassable {
    id: number;
    name: string;
    periodes: Record<Periode, StatsPeriode>;
    serie: number;
    jours_actifs: number;
    progression: number | null;
}

export const PERIODES: Record<Periode, string>;
export const SEUIL_EN_FEU: number;
export const SEUIL_REGULIER: number;
export const SEUIL_PROGRESSION: number;
export const DISTINCTIONS: Record<Distinction, string>;
export function rangs(eleves: EleveClassable[], periode?: Periode): Record<number, number>;
export function podium<T extends EleveClassable>(eleves: T[], periode?: Periode): { eleve: T; rang: number; xp: number }[];
export function distinctions(eleves: EleveClassable[]): Record<number, Distinction[]>;
