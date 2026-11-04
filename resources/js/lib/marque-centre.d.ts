import type { Marque } from '@/types';

export const CONTRASTE_MINIMAL: number;
export const FOND_SOMBRE: string;
export function estHexa(couleur: unknown): couleur is string;
export function luminance(couleur: string): number;
export function contraste(a: string, b: string): number;
export function texteSur(fond: string): string;
export function couleurLisible(couleur: unknown): boolean;
export function pourFondSombre(couleur: string): string;
export function cssDeLaMarque(marque: Partial<Marque> | null | undefined): string;
export function nomDuSite(marque: Partial<Marque> | null | undefined, nomParDefaut: string): string;
