import axios from 'axios';
import type { FicheMedia } from '@/lib/mediatheque';

/**
 * Le Google Drive du centre, cote navigateur.
 *
 * Envoi : PrePla ouvre une session d'envoi resumable dans le Drive du centre,
 * puis le navigateur envoie le fichier PAR MORCEAUX directement a Google. Le
 * fichier ne passe jamais par le serveur PrePla. Une coupure reseau ne fait
 * pas tout recommencer : on demande a Google ou il en est, et on reprend.
 */

export interface EtatDrive {
    connecte: boolean;
    email: string | null;
    connecte_le: string | null;
    derniere_sauvegarde_le: string | null;
    peut_connecter: boolean;
    picker: boolean;
}

export type TypeDrive = 'video' | 'audio' | 'document' | 'image';

export interface InfosDroits {
    title: string;
    source_rights: string;
    rights_certified: boolean;
}

/** Morceaux de 8 Mo : un multiple de 256 Ko, comme Google l'exige. */
const MORCEAU = 32 * 256 * 1024;

export function typeDuFichier(f: File): TypeDrive {
    if (f.type.startsWith('video/')) return 'video';
    if (f.type.startsWith('audio/')) return 'audio';
    if (f.type.startsWith('image/')) return 'image';
    return 'document';
}

function attendre(ms: number) {
    return new Promise((r) => setTimeout(r, ms));
}

/** Un PUT vers Google, avec suivi de progression ; renvoie le statut et le corps. */
function envoyerMorceau(
    url: string,
    corps: Blob | null,
    contentRange: string,
    onProgress?: (octets: number) => void,
): Promise<{ status: number; range: string | null; texte: string }> {
    return new Promise((resoudre, rejeter) => {
        const xhr = new XMLHttpRequest();
        xhr.open('PUT', url);
        xhr.setRequestHeader('Content-Range', contentRange);
        xhr.upload.onprogress = (e) => onProgress?.(e.loaded);
        xhr.onload = () => resoudre({ status: xhr.status, range: xhr.getResponseHeader('Range'), texte: xhr.responseText });
        xhr.onerror = () => rejeter(new Error('reseau'));
        xhr.send(corps);
    });
}

/** Ou en est Google ? Renvoie le prochain octet a envoyer, ou l'id si c'est fini. */
async function etatEnvoi(url: string, taille: number): Promise<{ suivant: number; id?: string }> {
    const r = await envoyerMorceau(url, null, `bytes */${taille}`);
    if (r.status === 200 || r.status === 201) return { suivant: taille, id: JSON.parse(r.texte).id };
    if (r.status === 308) return { suivant: r.range ? Number(r.range.split('-')[1]) + 1 : 0 };
    throw new Error(`Google Drive a refusé l’envoi (${r.status}).`);
}

/** Envoie un fichier dans le Drive du centre puis l'enregistre dans la mediatheque. */
export async function envoyerVersDrive(fichier: File, infos: InfosDroits, onProgress?: (pourcent: number) => void): Promise<FicheMedia> {
    const type = typeDuFichier(fichier);
    const mime = fichier.type || 'application/octet-stream';
    const { data } = await axios.post(route('center.google.upload-session'), { type, name: fichier.name, mime, size: fichier.size });
    const url: string = data.upload_url;

    let debut = 0;
    let id: string | undefined;
    let essais = 0;
    while (id === undefined) {
        const fin = Math.min(debut + MORCEAU, fichier.size);
        try {
            const r = await envoyerMorceau(url, fichier.slice(debut, fin), `bytes ${debut}-${fin - 1}/${fichier.size}`, (o) =>
                onProgress?.(Math.min(99, Math.round(((debut + o) / fichier.size) * 100))),
            );
            if (r.status === 200 || r.status === 201) {
                id = JSON.parse(r.texte).id;
            } else if (r.status === 308) {
                debut = r.range ? Number(r.range.split('-')[1]) + 1 : 0;
                essais = 0;
            } else if (r.status >= 500 && essais < 5) {
                throw new Error('serveur');
            } else {
                throw new Error(`Google Drive a refusé l’envoi (${r.status}).`);
            }
        } catch (e) {
            if (essais >= 5) throw e;
            essais++;
            // Reprise : on attend un peu, puis on demande a Google ce qu'il a deja recu.
            await attendre(1000 * 2 ** essais);
            const etat = await etatEnvoi(url, fichier.size);
            debut = etat.suivant;
            id = etat.id;
        }
    }
    onProgress?.(100);

    const res = await axios.post(route('center.google.media'), { drive_file_id: id, type, ...infos });
    return res.data as FicheMedia;
}

/** Enregistre un fichier choisi dans le Picker. */
export async function enregistrerDepuisDrive(driveFileId: string, infos: InfosDroits): Promise<FicheMedia> {
    const res = await axios.post(route('center.google.media'), { drive_file_id: driveFileId, ...infos });
    return res.data as FicheMedia;
}

declare global {
    interface Window {
        gapi?: { load: (lib: string, cb: () => void) => void };
        google?: any; // eslint-disable-line @typescript-eslint/no-explicit-any
    }
}

function chargerScript(src: string): Promise<void> {
    return new Promise((resoudre, rejeter) => {
        if (document.querySelector(`script[src="${src}"]`)) return resoudre();
        const s = document.createElement('script');
        s.src = src;
        s.async = true;
        s.onload = () => resoudre();
        s.onerror = () => rejeter(new Error('Google Picker indisponible.'));
        document.head.appendChild(s);
    });
}

/**
 * Ouvre le Google Picker sur le Drive du centre. Avec le scope drive.file,
 * chaque fichier choisi devient lisible par PrePla, et seulement lui.
 */
export async function choisirDansDrive(): Promise<{ id: string; name: string }[]> {
    const { data } = await axios.get(route('center.google.picker'));
    await chargerScript('https://apis.google.com/js/api.js');
    await new Promise<void>((r) => window.gapi!.load('picker', () => r()));
    const g = window.google;

    return new Promise((resoudre) => {
        const vue = new g.picker.DocsView(g.picker.ViewId.DOCS).setIncludeFolders(false).setSelectFolderEnabled(false);
        const picker = new g.picker.PickerBuilder()
            .addView(vue)
            .enableFeature(g.picker.Feature.MULTISELECT_ENABLED)
            .setOAuthToken(data.access_token)
            .setDeveloperKey(data.api_key)
            .setAppId(data.app_id)
            .setCallback((r: { action: string; docs?: { id: string; name: string }[] }) => {
                if (r.action === g.picker.Action.PICKED) resoudre((r.docs ?? []).map((d) => ({ id: d.id, name: d.name })));
                if (r.action === g.picker.Action.CANCEL) resoudre([]);
            })
            .build();
        picker.setVisible(true);
    });
}
