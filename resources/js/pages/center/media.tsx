import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import { ArrowLeft, Check, Music, Pencil, Trash2, Upload, X } from 'lucide-react';
import { useId, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { useT } from '@/lib/i18n/traduire';
import {
    envoyerAudio,
    estUnAudio,
    messageErreur,
    nomDePiste,
    SOURCES_PROPOSEES,
    type FicheMedia,
} from '@/lib/mediatheque';

/**
 * La mediatheque du centre : les audios d'examen (pistes de comprehension
 * orale), envoyes par lot, renommes, et toujours accompagnes de leur source
 * et de leurs droits. Un centre qui diffuse un sujet sous licence doit
 * pouvoir dire d'ou vient chaque piste.
 */

interface EnAttente {
    cle: string;
    fichier: File;
    titre: string;
    etat: 'pret' | 'envoi' | 'fait' | 'erreur';
    progression: number;
    erreur?: string;
}

const champ = 'min-h-11 w-full rounded-lg border border-border bg-background px-3 text-base sm:text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

function taille(octets?: number | null): string {
    if (!octets) return '';
    return octets > 1024 * 1024 ? `${(octets / 1024 / 1024).toFixed(1)} Mo` : `${Math.max(1, Math.round(octets / 1024))} Ko`;
}

export default function Mediatheque({ media, limites }: { media: FicheMedia[]; limites: { audio_mo: number; image_mo: number } }) {
    const { t } = useT();
    const id = useId();
    const entree = useRef<HTMLInputElement>(null);
    const [liste, setListe] = useState<FicheMedia[]>(media.filter((m) => m.type === 'audio'));
    const [attente, setAttente] = useState<EnAttente[]>([]);
    const [source, setSource] = useState('');
    const [certifie, setCertifie] = useState(false);
    const [erreur, setErreur] = useState<string | null>(null);
    const [envoiEnCours, setEnvoiEnCours] = useState(false);

    function ajouter(fichiers: FileList | File[]) {
        const refuses: string[] = [];
        const nouveaux: EnAttente[] = [];
        for (const f of Array.from(fichiers)) {
            if (!estUnAudio(f)) {
                refuses.push(t('{nom} n’est pas un son.', { nom: f.name }));
            } else if (f.size > limites.audio_mo * 1024 * 1024) {
                refuses.push(t('{nom} dépasse {mo} Mo.', { nom: f.name, mo: limites.audio_mo }));
            } else {
                nouveaux.push({ cle: `${f.name}-${f.size}-${Math.random()}`, fichier: f, titre: nomDePiste(f), etat: 'pret', progression: 0 });
            }
        }
        setErreur(refuses.length ? refuses.join(' ') : null);
        setAttente((a) => [...a, ...nouveaux]);
    }

    function majAttente(cle: string, maj: Partial<EnAttente>) {
        setAttente((a) => a.map((x) => (x.cle === cle ? { ...x, ...maj } : x)));
    }

    // Les fichiers partent l'un apres l'autre : un lot de pistes de 50 Mo
    // envoye d'un bloc depasserait la limite du serveur, et une piste ratee
    // ne fait pas echouer les autres.
    async function envoyerTout() {
        setErreur(null);
        if (source.trim() === '') {
            setErreur(t('Indiquez la source et les droits de ces fichiers.'));
            return;
        }
        if (!certifie) {
            setErreur(t('Cochez la case : vous certifiez avoir les droits de diffuser ces fichiers à vos élèves.'));
            return;
        }
        setEnvoiEnCours(true);
        for (const p of attente.filter((x) => x.etat === 'pret' || x.etat === 'erreur')) {
            majAttente(p.cle, { etat: 'envoi', progression: 0, erreur: undefined });
            try {
                const fiche = await envoyerAudio(
                    p.fichier,
                    { title: p.titre.trim() || nomDePiste(p.fichier), source_rights: source.trim(), rights_certified: true },
                    (pc) => majAttente(p.cle, { progression: pc }),
                );
                majAttente(p.cle, { etat: 'fait', progression: 100 });
                setListe((l) => [fiche, ...l]);
            } catch (e) {
                majAttente(p.cle, { etat: 'erreur', erreur: messageErreur(e) ?? t('L’envoi a échoué.') });
            }
        }
        setEnvoiEnCours(false);
        setAttente((a) => a.filter((x) => x.etat !== 'fait'));
    }

    async function supprimer(m: FicheMedia) {
        if (!confirm(t('Supprimer définitivement « {nom} » ? Les exercices qui l’utilisent n’auront plus de son.', { nom: m.title }))) return;
        try {
            await axios.delete(route('center.media.destroy', m.id));
            setListe((l) => l.filter((x) => x.id !== m.id));
        } catch (e) {
            setErreur(messageErreur(e) ?? t('Suppression impossible.'));
        }
    }

    const aEnvoyer = attente.filter((x) => x.etat !== 'fait').length;

    return (
        <AppLayout>
            <Head title={t('Médiathèque')} />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('center.exercises.index')} className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" /> {t('Contenu')}
                    </Link>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">{t('Médiathèque')}</h1>
                    <p className="text-sm text-muted-foreground">{t('Les audios d’examen de votre centre, à utiliser dans vos exercices d’écoute.')}</p>
                </div>

                <section aria-labelledby={`${id}-envoi`} className="space-y-4 rounded-2xl border border-border bg-card p-4">
                    <h2 id={`${id}-envoi`} className="font-semibold">{t('Ajouter des audios')}</h2>
                    <button
                        type="button"
                        onClick={() => entree.current?.click()}
                        onDragOver={(e) => e.preventDefault()}
                        onDrop={(e) => { e.preventDefault(); if (e.dataTransfer.files?.length) ajouter(e.dataTransfer.files); }}
                        className="flex w-full flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-border px-4 py-6 text-center hover:border-primary/60 hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <Music className="h-6 w-6 text-muted-foreground" aria-hidden="true" />
                        <span className="inline-flex items-center gap-1.5 text-sm font-semibold">
                            <Upload className="h-4 w-4" aria-hidden="true" /> {t('Glissez vos fichiers ici ou cliquez pour en choisir plusieurs')}
                        </span>
                        <span className="text-xs text-muted-foreground">{t('MP3, M4A, MP4, AAC, WAV, OGG ou WebM, {mo} Mo au plus par fichier', { mo: limites.audio_mo })}</span>
                    </button>
                    <input
                        ref={entree}
                        type="file"
                        multiple
                        className="sr-only"
                        tabIndex={-1}
                        aria-hidden="true"
                        accept="audio/*,.mp3,.m4a,.mp4,.aac,.wav,.ogg,.webm"
                        onChange={(e) => { if (e.target.files?.length) ajouter(e.target.files); e.target.value = ''; }}
                    />

                    {attente.length > 0 && (
                        <ul className="space-y-2">
                            {attente.map((p) => (
                                <li key={p.cle} className="rounded-xl border border-border p-3">
                                    <label className="block text-xs font-medium text-muted-foreground" htmlFor={`${id}-${p.cle}`}>
                                        {t('Nom de la piste')} <span className="font-normal">({p.fichier.name} · {taille(p.fichier.size)})</span>
                                    </label>
                                    <div className="mt-1 flex gap-2">
                                        <input
                                            id={`${id}-${p.cle}`}
                                            className={champ}
                                            value={p.titre}
                                            placeholder={t('ex. TCF CO piste 12')}
                                            maxLength={160}
                                            disabled={p.etat === 'envoi'}
                                            onChange={(e) => majAttente(p.cle, { titre: e.target.value })}
                                        />
                                        <button
                                            type="button"
                                            disabled={p.etat === 'envoi'}
                                            onClick={() => setAttente((a) => a.filter((x) => x.cle !== p.cle))}
                                            className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            aria-label={t('Retirer {nom} de l’envoi', { nom: p.fichier.name })}
                                        >
                                            <X className="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </div>
                                    {p.etat === 'envoi' && (
                                        <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-muted" role="progressbar" aria-valuenow={p.progression} aria-valuemin={0} aria-valuemax={100} aria-label={t('Envoi de {nom}', { nom: p.titre })}>
                                            <div className="h-full bg-primary transition-all" style={{ width: `${p.progression}%` }} />
                                        </div>
                                    )}
                                    {p.erreur && <p role="alert" className="mt-1 text-xs text-rose-600 dark:text-rose-400">{p.erreur}</p>}
                                </li>
                            ))}
                        </ul>
                    )}

                    <div>
                        <label htmlFor={`${id}-source`} className="text-sm font-medium">{t('Source et droits')} <span className="text-rose-600">*</span></label>
                        <input
                            id={`${id}-source`}
                            list={`${id}-sources`}
                            className={`${champ} mt-1`}
                            value={source}
                            maxLength={255}
                            required
                            placeholder={t('ex. enregistrement du centre, sujet sous licence')}
                            onChange={(e) => setSource(e.target.value)}
                        />
                        <datalist id={`${id}-sources`}>
                            {SOURCES_PROPOSEES.map((s) => <option key={s} value={t(s)} />)}
                        </datalist>
                    </div>
                    <label className="flex items-start gap-2 text-sm">
                        <input type="checkbox" className="mt-0.5 h-5 w-5 shrink-0" checked={certifie} onChange={(e) => setCertifie(e.target.checked)} />
                        <span>{t('Je certifie avoir les droits de diffuser ce fichier à mes élèves.')}</span>
                    </label>
                    {erreur && <p role="alert" className="text-sm text-rose-600 dark:text-rose-400">{erreur}</p>}
                    <Button type="button" onClick={envoyerTout} disabled={envoiEnCours || aEnvoyer === 0}>
                        <Upload className="h-4 w-4" aria-hidden="true" />
                        {envoiEnCours ? t('Envoi en cours…') : aEnvoyer > 1 ? t('Envoyer les {n} fichiers', { n: aEnvoyer }) : t('Envoyer le fichier')}
                    </Button>
                </section>

                <section aria-labelledby={`${id}-liste`} className="space-y-2">
                    <h2 id={`${id}-liste`} className="font-semibold">{liste.length > 1 ? t('{n} audios', { n: liste.length }) : t('{n} audio', { n: liste.length })}</h2>
                    {liste.length === 0 ? (
                        <p className="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">{t('Aucun audio pour l’instant.')}</p>
                    ) : (
                        <ul className="space-y-2">
                            {liste.map((m) => (
                                <LigneAudio key={m.id} media={m} onChange={(f) => setListe((l) => l.map((x) => (x.id === f.id ? f : x)))} onDelete={() => supprimer(m)} />
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}

function LigneAudio({ media, onChange, onDelete }: { media: FicheMedia; onChange: (f: FicheMedia) => void; onDelete: () => void }) {
    const { t } = useT();
    const id = useId();
    const [edition, setEdition] = useState(false);
    const [titre, setTitre] = useState(media.title);
    const [source, setSource] = useState(media.source_rights ?? '');
    const [erreur, setErreur] = useState<string | null>(null);

    async function enregistrer() {
        setErreur(null);
        try {
            const res = await axios.patch(route('center.media.update', media.id), { title: titre, source_rights: source });
            onChange(res.data as FicheMedia);
            setEdition(false);
        } catch (e) {
            setErreur(messageErreur(e) ?? t('Enregistrement impossible.'));
        }
    }

    return (
        <li className="rounded-xl border border-border bg-card p-3">
            {edition ? (
                <div className="space-y-2">
                    <label className="block text-xs font-medium" htmlFor={`${id}-t`}>{t('Nom de la piste')}</label>
                    <input id={`${id}-t`} className={champ} value={titre} maxLength={160} onChange={(e) => setTitre(e.target.value)} />
                    <label className="block text-xs font-medium" htmlFor={`${id}-s`}>{t('Source et droits')}</label>
                    <input id={`${id}-s`} className={champ} value={source} maxLength={255} onChange={(e) => setSource(e.target.value)} />
                    {erreur && <p role="alert" className="text-xs text-rose-600 dark:text-rose-400">{erreur}</p>}
                    <div className="flex gap-2">
                        <Button type="button" size="sm" onClick={enregistrer}><Check className="h-4 w-4" aria-hidden="true" /> {t('Enregistrer')}</Button>
                        <Button type="button" size="sm" variant="ghost" onClick={() => setEdition(false)}>{t('Annuler')}</Button>
                    </div>
                </div>
            ) : (
                <div className="flex flex-wrap items-start gap-2">
                    <div className="min-w-0 flex-1">
                        <p className="truncate font-semibold">{media.title}</p>
                        <p className="truncate text-xs text-muted-foreground">
                            {media.source_rights ? media.source_rights : t('Source non renseignée')}
                            {media.size ? ` · ${taille(media.size)}` : ''}
                        </p>
                    </div>
                    <button type="button" onClick={() => setEdition(true)} className="inline-flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label={t('Modifier « {nom} »', { nom: media.title })}>
                        <Pencil className="h-4 w-4" aria-hidden="true" />
                    </button>
                    <button type="button" onClick={onDelete} className="inline-flex h-9 w-9 items-center justify-center rounded-lg text-rose-500 hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:hover:bg-rose-950/40" aria-label={t('Supprimer « {nom} »', { nom: media.title })}>
                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                    </button>
                    <audio src={media.url} controls preload="none" className="mt-1 w-full" />
                </div>
            )}
        </li>
    );
}
