/**
 * Appels JSON du createur de parcours et du lecteur de seance.
 * Le jeton CSRF vient du cookie XSRF-TOKEN (ou de la balise meta).
 */
function jeton(): string {
    const cookie = document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='));
    if (cookie) return decodeURIComponent(cookie.split('=')[1]);
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

export class ErreurApi extends Error {}

export async function appel<T = unknown>(url: string, methode: 'POST' | 'PATCH' | 'DELETE', corps?: Record<string, unknown> | FormData): Promise<T> {
    const formulaire = corps instanceof FormData;
    const res = await fetch(url, {
        method: methode,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-XSRF-TOKEN': jeton(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(formulaire ? {} : { 'Content-Type': 'application/json' }),
        },
        body: corps === undefined ? undefined : formulaire ? corps : JSON.stringify(corps),
    });
    const donnees = await res.json().catch(() => ({}));
    if (!res.ok) {
        const erreurs = (donnees as { errors?: Record<string, string[]> }).errors;
        const premier = erreurs ? Object.values(erreurs)[0]?.[0] : undefined;
        throw new ErreurApi(premier ?? (donnees as { message?: string }).message ?? `Erreur ${res.status}`);
    }
    return donnees as T;
}
