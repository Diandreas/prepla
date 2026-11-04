export type StatutDevoir = 'brouillon' | 'publie' | 'termine';
export type TonEcheance = 'aucune' | 'loin' | 'proche' | 'jour' | 'retard' | 'passee';
export type TriDevoirs = 'echeance' | 'recent' | 'progression';

export interface ProgressionDevoir {
    students: number;
    finished: number;
    started: number;
}
export interface DevoirTriable {
    statut: StatutDevoir;
    due_date: string | null;
    days_left: number | null;
    overdue: boolean;
    en_retard: boolean;
    created_at: string | null;
    progress: ProgressionDevoir;
}

export const STATUTS_DEVOIR: Record<StatutDevoir, string>;
export function jourCourt(date: string | null | undefined, anneeCourante?: number): string;
export function echeance(
    devoir: Pick<DevoirTriable, 'due_date' | 'days_left' | 'overdue' | 'en_retard'>,
    anneeCourante?: number,
): { texte: string; detail: string; ton: TonEcheance };
export function tauxFini(progress: ProgressionDevoir | null | undefined): number | null;
export function trierDevoirs<T extends DevoirTriable>(liste: T[], mode?: TriDevoirs): T[];
export function syntheseDevoirs(liste: DevoirTriable[]): {
    total: number;
    enCours: number;
    cetteSemaine: number;
    enRetard: number;
    tauxRendu: number | null;
};
