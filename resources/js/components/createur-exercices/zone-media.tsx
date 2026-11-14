import axios from 'axios';
import { ImageIcon, Music, Trash2, Upload, X } from 'lucide-react';
import { useId, useRef, useState } from 'react';
import { useT } from '@/lib/i18n/traduire';
import { envoyerAudio, estUnAudio, messageErreur, nomDePiste, SOURCES_PROPOSEES, type FicheMedia } from '@/lib/mediatheque';
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
    const { t } = useT();
    const [envoi, setEnvoi] = useState(false);
    const [survol, setSurvol] = useState(false);
    const [erreur, setErreur] = useState<string | null>(null);
    // L'identifiant du fichier envoye depuis cet ecran : il permet de le supprimer
    // vraiment, et pas seulement de le detacher de l'exercice.
    const [envoyeId, setEnvoyeId] = useState<number | null>(null);
    const champ = useRef<HTMLInputElement>(null);
    const id = useId();
    const nom = type === 'image' ? t('Image') : t('Enregistrement audio');

    // Audio : nom de piste, source et droits, certification. Sans eux le
    // serveur refuse l'envoi (un sujet sous licence doit dire d'ou il vient).
    const [titre, setTitre] = useState('');
    const [source, setSource] = useState('');
    const [certifie, setCertifie] = useState(false);
    const [bibliotheque, setBibliotheque] = useState<FicheMedia[] | null>(null);

    async function ouvrirBibliotheque() {
        try {
            const res = await axios.get(route('center.media.index'), { params: { type: 'audio' } });
            setBibliotheque(res.data.media ?? []);
        } catch {
            setErreur(t('Impossible de charger la médiathèque.'));
        }
    }

    async function envoyer(fichier: File) {
        if (type === 'audio') {
            if (!estUnAudio(fichier)) {
                setErreur(t('Ce fichier n’est pas un son (MP3, M4A, MP4, AAC, WAV, OGG, WebM).'));
                return;
            }
            if (source.trim() === '' || !certifie) {
                setErreur(t('Indiquez la source et les droits, puis cochez la case de certification, avant d’envoyer le son.'));
                return;
            }
            setEnvoi(true);
            setErreur(null);
            try {
                const fiche = await envoyerAudio(fichier, { title: titre.trim() || nomDePiste(fichier), source_rights: source.trim(), rights_certified: true });
                onChange(fiche.url);
                setEnvoyeId(fiche.id);
            } catch (e: unknown) {
                setErreur(messageErreur(e) ?? t('L’envoi a échoué. Vérifiez votre connexion et la taille du fichier (100 Mo au plus pour un son).'));
            } finally {
                setEnvoi(false);
            }
            return;
        }
        const attendu = 'image/';
        if (!fichier.type.startsWith(attendu)) {
            setErreur(type === 'image' ? t('Ce fichier n’est pas une image (JPG, PNG ou WebP).') : t('Ce fichier n’est pas un son (MP3, WAV, OGG, M4A).'));
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
            setErreur(r?.data?.message ?? t('L’envoi a échoué. Vérifiez votre connexion et la taille du fichier (5 Mo pour une image, 20 Mo pour un son).'));
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
        if (!confirm(t('Supprimer définitivement ce fichier de la médiathèque du centre ?'))) return;
        try {
            await axios.delete(route('center.media.destroy', envoyeId));
            detacher();
        } catch (e: unknown) {
            const r = (e as { response?: { data?: { message?: string } } })?.response;
            setErreur(r?.data?.message ?? t('Suppression impossible.'));
        }
    }

    return (
        <div>
            <p id={`${id}-titre`} className="text-sm font-medium">{nom} <span className="font-normal text-muted-foreground">{t('(facultatif)')}</span></p>
            {aide && <p className="mt-0.5 text-xs text-muted-foreground">{aide}</p>}

            {value ? (
                <div className="mt-2 rounded-xl border border-border bg-muted/30 p-3">
                    {type === 'image'
                        ? <img src={value} alt={t('Image de l’exercice')} className="max-h-48 w-auto rounded-lg object-contain" />
                        : <audio src={value} controls className="w-full" />}
                    <div className="mt-2 flex flex-wrap gap-2">
                        <button type="button" onClick={detacher} className="inline-flex min-h-9 items-center gap-1.5 rounded-lg px-2 text-xs font-semibold text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <X className="h-3.5 w-3.5" aria-hidden="true" /> {t('Retirer de l’exercice')}
                        </button>
                        {envoyeId && (
                            <button type="button" onClick={supprimer} className="inline-flex min-h-9 items-center gap-1.5 rounded-lg px-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:text-rose-400 dark:hover:bg-rose-950/40">
                                <Trash2 className="h-3.5 w-3.5" aria-hidden="true" /> {t('Supprimer le fichier')}
                            </button>
                        )}
                    </div>
                </div>
            ) : (
                <>
                {type === 'audio' && (
                    <div className="mt-2 space-y-2 rounded-xl border border-border p-3">
                        <div className="flex flex-wrap items-center gap-2">
                            <button type="button" onClick={ouvrirBibliotheque} className="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-border px-2.5 text-xs font-semibold hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <Music className="h-3.5 w-3.5" aria-hidden="true" /> {t('Choisir dans la médiathèque')}
                            </button>
                            <a href={route('center.media.page')} className="text-xs font-semibold text-primary hover:underline">{t('Envoyer plusieurs pistes')}</a>
                        </div>
                        {bibliotheque && (
                            bibliotheque.length === 0
                                ? <p className="text-xs text-muted-foreground">{t('Aucun audio pour l’instant.')}</p>
                                : (
                                    <select
                                        aria-label={t('Choisir un audio de la médiathèque')}
                                        className="min-h-11 w-full rounded-lg border border-border bg-background px-3 text-base sm:text-sm"
                                        defaultValue=""
                                        onChange={(e) => { if (e.target.value) { onChange(e.target.value); setEnvoyeId(null); } }}
                                    >
                                        <option value="">{t('— Choisir une piste —')}</option>
                                        {bibliotheque.map((m) => <option key={m.id} value={m.url}>{m.title}{m.source_rights ? ` · ${m.source_rights}` : ''}</option>)}
                                    </select>
                                )
                        )}
                        <p className="text-xs font-medium text-muted-foreground">{t('Ou envoyez un nouveau fichier :')}</p>
                        <input
                            aria-label={t('Nom de la piste')}
                            className="min-h-11 w-full rounded-lg border border-border bg-background px-3 text-base sm:text-sm"
                            placeholder={t('Nom de la piste (ex. TCF CO piste 12)')}
                            maxLength={160}
                            value={titre}
                            onChange={(e) => setTitre(e.target.value)}
                        />
                        <input
                            aria-label={t('Source et droits')}
                            list={`${id}-sources`}
                            className="min-h-11 w-full rounded-lg border border-border bg-background px-3 text-base sm:text-sm"
                            placeholder={t('Source et droits (obligatoire)')}
                            maxLength={255}
                            required
                            value={source}
                            onChange={(e) => setSource(e.target.value)}
                        />
                        <datalist id={`${id}-sources`}>
                            {SOURCES_PROPOSEES.map((s) => <option key={s} value={t(s)} />)}
                        </datalist>
                        <label className="flex items-start gap-2 text-xs">
                            <input type="checkbox" className="mt-0.5 h-4 w-4 shrink-0" checked={certifie} onChange={(e) => setCertifie(e.target.checked)} />
                            <span>{t('Je certifie avoir les droits de diffuser ce fichier à mes élèves.')}</span>
                        </label>
                    </div>
                )}
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
                        {envoi ? t('Envoi en cours…') : t('Glissez un fichier ici ou cliquez pour choisir')}
                    </span>
                    <span id={`${id}-consigne`} className="text-xs text-muted-foreground">
                        {type === 'image' ? t('JPG, PNG ou WebP, 5 Mo au plus') : t('MP3, M4A, MP4, AAC, WAV, OGG ou WebM, 100 Mo au plus')}
                    </span>
                </button>
                </>
            )}
            <input
                ref={champ}
                type="file"
                className="sr-only"
                tabIndex={-1}
                aria-hidden="true"
                accept={type === 'image' ? 'image/jpeg,image/png,image/webp' : 'audio/*,.mp3,.m4a,.mp4,.aac,.wav,.ogg,.webm'}
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
