/**
 * Son court du lecteur d'exercices, synthetise en WebAudio (aucun fichier a
 * charger) : deux notes qui montent sur une bonne reponse, une note basse et
 * douce sur une erreur. Le choix « son coupe » est memorise dans le navigateur ;
 * si le stockage est indisponible, le son reste actif et rien ne casse.
 */

export const CLE_SON_REPONSE = 'prepla-son-reponse';

export function sonReponseActif(): boolean {
    try {
        return localStorage.getItem(CLE_SON_REPONSE) !== 'off';
    } catch {
        return true;
    }
}

export function memoriserSonReponse(actif: boolean): void {
    try {
        localStorage.setItem(CLE_SON_REPONSE, actif ? 'on' : 'off');
    } catch {
        /* stockage bloque : le choix vaut pour la page seulement */
    }
}

let contexte: AudioContext | null = null;

function obtenirContexte(): AudioContext | null {
    if (typeof window === 'undefined') return null;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any -- Safari ancien
    const Ctor = window.AudioContext ?? (window as any).webkitAudioContext;
    if (!Ctor) return null;
    try {
        contexte ??= new Ctor();
    } catch {
        return null;
    }
    return contexte;
}

function note(ctx: AudioContext, frequence: number, debut: number, duree: number, volume: number, forme: OscillatorType) {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = forme;
    osc.frequency.value = frequence;
    const t0 = ctx.currentTime + debut;
    gain.gain.setValueAtTime(0.0001, t0);
    gain.gain.exponentialRampToValueAtTime(volume, t0 + 0.015);
    gain.gain.exponentialRampToValueAtTime(0.0001, t0 + duree);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start(t0);
    osc.stop(t0 + duree + 0.02);
}

export function jouerSonReponse(juste: boolean): void {
    if (!sonReponseActif()) return;
    const ctx = obtenirContexte();
    if (!ctx) return;
    try {
        if (ctx.state === 'suspended') void ctx.resume();
        if (juste) {
            note(ctx, 660, 0, 0.12, 0.18, 'sine');
            note(ctx, 990, 0.09, 0.18, 0.16, 'sine');
        } else {
            note(ctx, 220, 0, 0.22, 0.09, 'triangle');
        }
    } catch {
        /* pas de son, pas de panne */
    }
}
