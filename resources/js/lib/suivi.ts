/**
 * Mise en forme du suivi des apprenants, cote centre.
 *
 * Le serveur envoie des dates ISO en UTC. On les affiche a l'heure du
 * navigateur de celui qui regarde : un enseignant a Douala lit l'heure de
 * Douala, sans que le serveur ait a la deviner.
 */

const FORMAT_DATE_HEURE = new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

/** « 12 oct. 2026, 18:42 » a l'heure locale. */
export function dateHeure(iso: string | null): string {
    if (!iso) return '—';
    const date = new Date(iso);
    return Number.isNaN(date.getTime()) ? '—' : FORMAT_DATE_HEURE.format(date);
}

/** « 3 h 20 », « 12 min », « 45 s ». */
export function duree(secondes: number): string {
    if (!secondes || secondes < 0) return '0 min';
    if (secondes < 60) return `${secondes} s`;
    const minutes = Math.round(secondes / 60);
    if (minutes < 60) return `${minutes} min`;
    const heures = Math.floor(minutes / 60);
    const reste = minutes % 60;
    return reste ? `${heures} h ${String(reste).padStart(2, '0')}` : `${heures} h`;
}

const RELATIF = new Intl.RelativeTimeFormat('fr-FR', { numeric: 'auto' });

/** « aujourd'hui », « hier », « il y a 5 jours », « il y a 3 mois ». */
export function ilYA(iso: string | null): string {
    if (!iso) return 'Jamais';
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return '—';

    const jours = Math.round((startOfDay(date) - startOfDay(new Date())) / 86_400_000);
    if (jours > -31) return RELATIF.format(jours, 'day');
    const mois = Math.round(jours / 30);
    if (mois > -12) return RELATIF.format(mois, 'month');
    return RELATIF.format(Math.round(mois / 12), 'year');
}

function startOfDay(date: Date): number {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
}

const COMPETENCES: Record<string, string> = {
    reading: 'Lecture',
    listening: 'Écoute',
    writing: 'Écrit',
    speaking: 'Oral',
    grammar: 'Grammaire',
    vocabulary: 'Vocabulaire',
};

export function competence(slug: string | null): string | null {
    if (!slug) return null;
    return COMPETENCES[slug] ?? slug;
}
