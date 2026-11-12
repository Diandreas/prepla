/**
 * Le format d'enregistrement que ce navigateur sait produire.
 *
 * Chrome et Android enregistrent en webm/opus ; Safari, sur iPhone et sur Mac,
 * ne connait pas le webm et enregistre en mp4 (AAC). Imposer le webm faisait
 * echouer tout enregistrement sur iPhone, des le premier appui sur le micro.
 */
const FORMATS = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus', 'audio/ogg'];

export function formatEnregistrement(): string | undefined {
    if (typeof MediaRecorder === 'undefined' || typeof MediaRecorder.isTypeSupported !== 'function') return undefined;
    return FORMATS.find((f) => MediaRecorder.isTypeSupported(f));
}

/** L'extension a donner au fichier envoye, d'apres son vrai type. */
export function extensionAudio(type: string | undefined | null): string {
    const t = (type ?? '').toLowerCase();
    if (t.includes('mp4') || t.includes('aac') || t.includes('m4a')) return 'mp4';
    if (t.includes('ogg')) return 'ogg';
    if (t.includes('wav')) return 'wav';
    return 'webm';
}

/** « recording-q1 » + un enregistrement mp4 -> « recording-q1.mp4 ». */
export function nomFichierAudio(base: string, blob: Blob): string {
    return `${base}.${extensionAudio(blob.type)}`;
}
