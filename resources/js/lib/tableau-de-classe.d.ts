export interface Variation { texte: string; sens: 'hausse' | 'baisse' | 'stable' | 'nouveau' }
export interface PointCourbe { x: number; y: number | null; valeur: number | null | undefined }
export function initiales(nom: string | null | undefined): string;
export function prenom(nom: string | null | undefined): string;
export function variation(actuel: number, avant: number): Variation | null;
export function ecartPoints(ecart: number | null | undefined): Variation | null;
export function pointsCourbe(valeurs: (number | null)[], largeur: number, hauteur: number, maximum?: number): PointCourbe[];
export function cheminCourbe(points: PointCourbe[]): string;
export function libelleSemaine(debutIso: string, derniere: boolean): string;
