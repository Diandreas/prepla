export interface Competence {
    cle: string;
    nom: string;
    court: string;
}

export interface TypeDecrit {
    id?: number;
    slug?: string | null;
    component_key?: string | null;
    name?: string | null;
    skill_type?: string | null;
}

export interface PartieOfficielle {
    section?: string | null;
    part?: number | string | null;
    name?: string | null;
    name_fr?: string | null;
    items?: number | null;
}

export interface Probleme {
    champ: string;
    message: string;
}

export interface ContexteQuestion {
    famille: string;
    componentKey: string;
}

export interface LigneExercice {
    id: number;
    titre?: string | null;
    type?: string | null;
    type_slug?: string | null;
    component_key?: string | null;
    skill_type?: string | null;
    difficulty?: string | null;
    exam_id?: number | null;
    exam?: string | null;
    questions_count?: number;
    is_ai_generated?: boolean;
    source?: 'centre' | 'prepla';
}

export interface FiltresExercices {
    examen?: string;
    niveau?: string;
    competence?: string;
    source?: string;
    recherche?: string;
}

export type ModeDeReponse = 'choix' | 'vrai-faux' | 'texte';

export const NIVEAUX: string[];
export const COMPETENCES: Competence[];
export function competenceDe(skillType: string | null | undefined): Competence;
export function libelleDuType(type: TypeDecrit | null | undefined): string;
export function descriptionDuType(type: TypeDecrit | null | undefined): string;
export function nomDeLaPartie(partie: PartieOfficielle | null | undefined): string;
export function regrouperParCompetence<T extends TypeDecrit>(
    types: T[],
    parties?: Record<string | number, PartieOfficielle[] | undefined>,
): { competence: Competence; types: T[] }[];
export function modeDeReponse(componentKey: string, question: unknown): ModeDeReponse;
export function accepteLesDeuxModes(componentKey: string): boolean;
export function texteDeLOption(option: unknown): string;
export function lettre(index: number): string;
export function indexDeLaBonneReponse(question: unknown): number;
export function choixVraiFaux(question: unknown): { value: string; label: string }[];
export function problemesDeLaQuestion(question: unknown, contexte: ContexteQuestion): Probleme[];
export function problemesDeLExercice(exercice: { skillType?: string | null; content?: Record<string, unknown> | null; questions?: unknown[] }): string[];
export function questionsACompleter(questions: unknown[], contexte: ContexteQuestion): number[];
export function pluriel(n: number, singulier: string, pluriel: string): string;
export function resumeACompleter(n: number): string;
export function deplacer<T>(liste: T[], i: number, sens: number): T[];
export function dupliquer<T>(liste: T[], i: number): T[];
export function retirer<T>(liste: T[], i: number): T[];
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function questionsAEnvoyer(questions: any[], content: Record<string, unknown> | null | undefined): any[];
export function stockageDuNavigateur(nom?: 'localStorage' | 'sessionStorage'): Storage | null;
export function cleDuBrouillon(compte: number | string | null | undefined): string;
export function cleDuDevoirEnCours(compte: number | string | null | undefined): string;
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function lireObjet(stockage: Storage | null | undefined, cle: string): Record<string, any> | null;
export function ecrireObjet(stockage: Storage | null | undefined, cle: string, donnees: Record<string, unknown>): boolean;
export function effacerObjet(stockage: Storage | null | undefined, cle: string): void;
export function brouillonUtile(brouillon: unknown): boolean;
export function normaliser(texte: unknown): string;
export function libelleExercice(ligne: LigneExercice | null | undefined): string;
export function filtrerExercices<T extends LigneExercice>(lignes: T[], filtres?: FiltresExercices): T[];
export function optionsDeFiltre(lignes: LigneExercice[]): { niveaux: string[]; competences: Competence[] };
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function retirerOption<T extends Record<string, any>>(question: T, i: number): T;
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function cocherOption<T extends Record<string, any>>(question: T, i: number): T;
export function nombreDeQuestions(valeur: unknown): number | null;
export function niveauDeLaClasse(niveau: unknown): string | null;
