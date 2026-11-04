import axios from 'axios';
import { ImageIcon, Music, Trash2, Upload, X } from 'lucide-react';
import { useId, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

/**
 * Televersement d'une image ou d'un son, par glisser-deposer ou au clic, avec
 * apercu. Les fichiers vont dans la mediatheque du centre (center.media.*) :
 * le serveur refuse a l'enregistrement tout media qui n'en vient pas.
 */
export function ZoneMedia({ type, value, onChange, aide }: {
    type: 'image' | 'audio';
    value?: string;
    onChange: (url: string) => void;
    aide?: string;
}) {
    const [envoi, setEnvoi] = useState(false);
    const [survol, setSurvol] = useState(false);
    const [erreur, setErreur] = useState<string | null>(null);
    // L'identifiant du fichier envoye depuis cet ecran : il permet de le supprimer
    // vraiment, et pas seulement de le detacher de l'exercice.
    const [envoyeId, setEnvoyeId] = useState<number | null>(null);
    const champ = useRef<HTMLInputElement>(null);
    const id = useId();
    const nom = type === 'image' ? 'Image' : 'Enregistrement audio';

    async function envoyer(fichier: File) {
        const attendu = type === 'image' ? 'image/' : 'audio/';
        if (!fichier.type.startsWith(attendu)) {
            setErreur(type === 'image' ? 'Ce fichier n’est pas une image (JPG, PNG ou WebP).' : 'Ce fichier n’est pas un son (MP3, WAV, OGG, M4A).');
            return;
        }
        setEnvoi(true);
        setErreur(null);
        const fd = new FormData();
        fd.append('type', type);
        fd.append(type, fichier);
        try {
            const res = await axios.post(route('center.media.store'), fd, { headers: { 'Content-Type': 'multipart/form-data' } });
            onChange(res.data.url);
            setEnvoyeId(res.data.id ?? null);
        } catch (e: unknown) {
            const r = (e as { response?: { data?: { message?: string } } })?.response;
            setErreur(r?.data?.message ?? 'L’envoi a échoué. Vérifiez votre connexion et la taille du fichier (5 Mo pour une image, 20 Mo pour un son).');
        } finally {
            setEnvoi(false);
        }
    }

    function detacher() {
        onChange('');
        setEnvoyeId(null);
        setErreur(null);
    }

    async function supprimer() {
        if (!envoyeId) {
            detacher();
            return;
        }
        if (!confirm('Supprimer définitivement ce fichier de la médiathèque du centre ?')) return;
        try {
            await axios.delete(route('center.media.destroy', envoyeId));
            detacher();
        } catch (e: unknown) {
            const r = (e as { response?: { data?: { message?: string } } })?.response;
            setErreur(r?.data?.message ?? 'Suppression impossible.');
        }
    }

    return (
        <div>
            <p id={`${id}-titre`} className="text-sm font-medium">{nom} <span className="font-normal text-muted-foreground">(facultatif)</span></p>
            {aide && <p className="mt-0.5 text-xs text-muted-foreground">{aide}</p>}

            {value ? (
                <div className="mt-2 rounded-xl border border-border bg-muted/30 p-3">
                    {type === 'image'
                        ? <img src={value} alt="Image de l’exercice" className="max-h-48 w-auto rounded-lg object-contain" />
                        : <audio src={value} controls className="w-full" />}
                    <div className="mt-2 flex flex-wrap gap-2">
                        <button type="button" onClick={detacher} className="inline-flex min-h-9 items-center gap-1.5 rounded-lg px-2 text-xs font-semibold text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <X className="h-3.5 w-3.5" aria-hidden="true" /> Retirer de l’exercice
                        </button>
                        {envoyeId && (
                            <button type="button" onClick={supprimer} className="inline-flex min-h-9 items-center gap-1.5 rounded-lg px-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:text-rose-400 dark:hover:bg-rose-950/40">
                                <Trash2 className="h-3.5 w-3.5" aria-hidden="true" /> Supprimer le fichier
                            </button>
                        )}
                    </div>
                </div>
            ) : (
                <button
                    type="button"
                    aria-labelledby={`${id}-titre`}
                    aria-describedby={`${id}-consigne`}
                    onClick={() => champ.current?.click()}
                    onDragOver={(e) => { e.preventDefault(); setSurvol(true); }}
                    onDragLeave={() => setSurvol(false)}
                    onDrop={(e) => {
                        e.preventDefault();
                        setSurvol(false);
                        const f = e.dataTransfer.files?.[0];
                        if (f) envoyer(f);
                    }}
                    disabled={envoi}
                    className={cn(
                        'mt-2 flex w-full flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed px-4 py-6 text-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                        survol ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/60 hover:bg-muted/40',
                        envoi && 'cursor-wait opacity-70',
                    )}
                >
                    {type === 'image'
                        ? <ImageIcon className="h-6 w-6 text-muted-foreground" aria-hidden="true" />
                        : <Music className="h-6 w-6 text-muted-foreground" aria-hidden="true" />}
                    <span className="inline-flex items-center gap-1.5 text-sm font-semibold">
                        <Upload className="h-4 w-4" aria-hidden="true" />
                        {envoi ? 'Envoi en cours…' : 'Glissez un fichier ici ou cliquez pour choisir'}
                    </span>
                    <span id={`${id}-consigne`} className="text-xs text-muted-foreground">
                        {type === 'image' ? 'JPG, PNG ou WebP, 5 Mo au plus' : 'MP3, WAV, OGG ou M4A, 20 Mo au plus'}
                    </span>
                </button>
            )}
            <input
                ref={champ}
                type="file"
                className="sr-only"
                tabIndex={-1}
                aria-hidden="true"
                accept={type === 'image' ? 'image/jpeg,image/png,image/webp' : 'audio/*'}
                onChange={(e) => {
                    const f = e.target.files?.[0];
                    if (f) envoyer(f);
                    e.target.value = '';
                }}
            />
            {erreur && <p role="alert" className="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{erreur}</p>}
        </div>
    );
}
