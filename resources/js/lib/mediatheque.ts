import axios from 'axios';

/**
 * Les fichiers de la mediatheque du centre, cote navigateur : l'envoi d'un
 * fichier avec son nom de piste, sa source et ses droits.
 */
export interface FicheMedia {
    id: number;
    type: 'image' | 'audio';
    url: string;
    name: string;
    title: string;
    source_rights: string | null;
    rights_certified_at: string | null;
    size?: number | null;
    created_at?: string | null;
}

/** Formats audio acceptes par le serveur (MediaController::AUDIO_FORMATS). */
export const EXTENSIONS_AUDIO = ['mp3', 'm4a', 'mp4', 'aac', 'adts', 'wav', 'ogg', 'oga', 'webm', 'weba'];

export const AUDIO_MAX_MO = 100;

/** Exemples proposes dans le champ « source et droits ». */
export const SOURCES_PROPOSEES = ['Enregistrement du centre', 'Sujet sous licence', 'Enregistrement libre de droits'];

/**
 * Un fichier son ? Le type MIME ne suffit pas : un .m4a arrive parfois en
 * « video/mp4 », un .aac sans type du tout sur certains Windows.
 */
export function estUnAudio(fichier: File): boolean {
    if (fichier.type.startsWith('audio/')) return true;
    const ext = fichier.name.split('.').pop()?.toLowerCase() ?? '';
    return EXTENSIONS_AUDIO.includes(ext);
}

/** Le nom du fichier sans son extension : le nom de piste propose par defaut. */
export function nomDePiste(fichier: File): string {
    return fichier.name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ').trim();
}

export async function envoyerAudio(
    fichier: File,
    infos: { title: string; source_rights: string; rights_certified: boolean },
    onProgress?: (pourcent: number) => void,
): Promise<FicheMedia> {
    const fd = new FormData();
    fd.append('type', 'audio');
    fd.append('audio', fichier);
    fd.append('title', infos.title);
    fd.append('source_rights', infos.source_rights);
    fd.append('rights_certified', infos.rights_certified ? '1' : '0');
    const res = await axios.post(route('center.media.store'), fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (e) => {
            if (onProgress && e.total) onProgress(Math.round((e.loaded / e.total) * 100));
        },
    });
    return res.data as FicheMedia;
}

/** Le premier message d'erreur renvoye par le serveur, s'il y en a un. */
export function messageErreur(e: unknown): string | null {
    const data = (e as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response?.data;
    const premier = data?.errors ? Object.values(data.errors)[0]?.[0] : undefined;
    return premier ?? data?.message ?? null;
}
