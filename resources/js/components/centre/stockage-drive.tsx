import axios from 'axios';
import { Cloud, FileText, FolderOpen, Trash2, Upload, Video } from 'lucide-react';
import { useId, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { choisirDansDrive, enregistrerDepuisDrive, envoyerVersDrive, type EtatDrive } from '@/lib/drive';
import { useT } from '@/lib/i18n/traduire';
import { messageErreur, SOURCES_PROPOSEES, type FicheMedia } from '@/lib/mediatheque';

const champ = 'min-h-11 w-full rounded-lg border border-border bg-background px-3 text-base sm:text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

/** Encart affiche quand le Drive n'est pas connecte : l'envoi local reste possible. */
export function EncartConnecterDrive({ drive }: { drive: EtatDrive }) {
    const { t } = useT();
    return (
        <section className="flex flex-wrap items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-950 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-100">
            <Cloud className="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
            <div className="min-w-0 flex-1 space-y-1">
                <p className="font-semibold">{t('Rangez vos vidéos, audios et PDF dans votre Google Drive')}</p>
                <p>{t('Vos fichiers restent chez vous, sans limite de taille côté PrePla, et vos contenus y sont sauvegardés chaque semaine.')}</p>
            </div>
            {drive.peut_connecter ? (
                <a href={route('center.google.connect')} className="inline-flex min-h-11 items-center rounded-lg bg-primary px-4 text-sm font-semibold text-primary-foreground hover:bg-primary/90">
                    {t('Connecter Google Drive')}
                </a>
            ) : (
                <p className="text-xs">{t('Le responsable du centre peut le connecter dans les paramètres.')}</p>
            )}
        </section>
    );
}

interface Envoi {
    nom: string;
    progression: number;
    erreur?: string;
}

/**
 * Le Drive connecte : envoyer des videos, audios ou PDF directement dans le
 * Drive du centre, ou choisir des fichiers qui y sont deja.
 */
export function PanneauDrive({ drive, fichiers, onAjout, onRetrait }: {
    drive: EtatDrive;
    fichiers: FicheMedia[];
    onAjout: (f: FicheMedia) => void;
    onRetrait: (f: FicheMedia) => void;
}) {
    const { t } = useT();
    const id = useId();
    const entree = useRef<HTMLInputElement>(null);
    const [source, setSource] = useState('');
    const [certifie, setCertifie] = useState(false);
    const [envois, setEnvois] = useState<Envoi[]>([]);
    const [erreur, setErreur] = useState<string | null>(null);
    const [occupe, setOccupe] = useState(false);

    function droitsOk(): boolean {
        if (source.trim() === '') {
            setErreur(t('Indiquez la source et les droits de ces fichiers.'));
            return false;
        }
        if (!certifie) {
            setErreur(t('Cochez la case : vous certifiez avoir les droits de diffuser ces fichiers à vos élèves.'));
            return false;
        }
        return true;
    }

    async function envoyer(liste: FileList) {
        setErreur(null);
        if (!droitsOk()) return;
        setOccupe(true);
        for (const f of Array.from(liste)) {
            setEnvois((e) => [...e, { nom: f.name, progression: 0 }]);
            const maj = (m: Partial<Envoi>) => setEnvois((e) => e.map((x) => (x.nom === f.name ? { ...x, ...m } : x)));
            try {
                const fiche = await envoyerVersDrive(f, { title: f.name.replace(/\.[^.]+$/, ''), source_rights: source.trim(), rights_certified: true }, (p) => maj({ progression: p }));
                onAjout(fiche);
                setEnvois((e) => e.filter((x) => x.nom !== f.name));
            } catch (e) {
                maj({ erreur: messageErreur(e) ?? (e instanceof Error ? e.message : t('L’envoi a échoué.')) });
            }
        }
        setOccupe(false);
    }

    async function choisir() {
        setErreur(null);
        if (!droitsOk()) return;
        setOccupe(true);
        try {
            for (const doc of await choisirDansDrive()) {
                onAjout(await enregistrerDepuisDrive(doc.id, { title: doc.name.replace(/\.[^.]+$/, ''), source_rights: source.trim(), rights_certified: true }));
            }
        } catch (e) {
            setErreur(messageErreur(e) ?? t('Google Drive ne répond pas.'));
        }
        setOccupe(false);
    }

    async function retirer(m: FicheMedia) {
        if (!confirm(t('Retirer « {nom} » de PrePla ? Le fichier reste dans votre Google Drive.', { nom: m.title }))) return;
        try {
            await axios.delete(route('center.media.destroy', m.id));
            onRetrait(m);
        } catch (e) {
            setErreur(messageErreur(e) ?? t('Suppression impossible.'));
        }
    }

    return (
        <section aria-labelledby={`${id}-titre`} className="space-y-4 rounded-2xl border border-border bg-card p-4">
            <div className="flex items-start gap-2">
                <Cloud className="mt-0.5 h-5 w-5 shrink-0 text-primary" aria-hidden="true" />
                <div className="min-w-0">
                    <h2 id={`${id}-titre`} className="font-semibold">{t('Votre Google Drive')}</h2>
                    <p className="text-xs text-muted-foreground">{t('Connecté à {email}. Les fichiers vont directement dans votre Drive, sans passer par PrePla.', { email: drive.email ?? '' })}</p>
                </div>
            </div>

            <p className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
                {t('Attention : pour que vos élèves puissent les lire, chaque fichier ajouté ici devient lisible par toute personne qui a son lien. N’ajoutez pas de document confidentiel.')}
            </p>

            <div>
                <label htmlFor={`${id}-source`} className="text-sm font-medium">{t('Source et droits')} <span className="text-rose-600">*</span></label>
                <input id={`${id}-source`} list={`${id}-sources`} className={`${champ} mt-1`} value={source} maxLength={255} placeholder={t('ex. enregistrement du centre, sujet sous licence')} onChange={(e) => setSource(e.target.value)} />
                <datalist id={`${id}-sources`}>
                    {SOURCES_PROPOSEES.map((s) => <option key={s} value={t(s)} />)}
                </datalist>
            </div>
            <label className="flex items-start gap-2 text-sm">
                <input type="checkbox" className="mt-0.5 h-5 w-5 shrink-0" checked={certifie} onChange={(e) => setCertifie(e.target.checked)} />
                <span>{t('Je certifie avoir les droits de diffuser ce fichier à mes élèves.')}</span>
            </label>

            <div className="flex flex-wrap gap-2">
                <Button type="button" onClick={() => entree.current?.click()} disabled={occupe}>
                    <Upload className="h-4 w-4" aria-hidden="true" /> {t('Envoyer vidéos, audios ou PDF')}
                </Button>
                {drive.picker && (
                    <Button type="button" variant="outline" onClick={choisir} disabled={occupe}>
                        <FolderOpen className="h-4 w-4" aria-hidden="true" /> {t('Choisir dans mon Drive')}
                    </Button>
                )}
                <input
                    ref={entree}
                    type="file"
                    multiple
                    className="sr-only"
                    tabIndex={-1}
                    aria-hidden="true"
                    accept="video/*,audio/*,application/pdf,image/*"
                    onChange={(e) => { if (e.target.files?.length) envoyer(e.target.files); e.target.value = ''; }}
                />
            </div>

            {envois.length > 0 && (
                <ul className="space-y-2">
                    {envois.map((e) => (
                        <li key={e.nom} className="rounded-lg border border-border p-2 text-sm">
                            <p className="truncate">{e.nom}</p>
                            {!e.erreur && (
                                <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-muted" role="progressbar" aria-valuenow={e.progression} aria-valuemin={0} aria-valuemax={100} aria-label={t('Envoi de {nom}', { nom: e.nom })}>
                                    <div className="h-full bg-primary transition-all" style={{ width: `${e.progression}%` }} />
                                </div>
                            )}
                            {e.erreur && <p role="alert" className="text-xs text-rose-600 dark:text-rose-400">{e.erreur}</p>}
                        </li>
                    ))}
                </ul>
            )}
            {erreur && <p role="alert" className="text-sm text-rose-600 dark:text-rose-400">{erreur}</p>}

            {fichiers.length > 0 && (
                <ul className="space-y-2">
                    {fichiers.map((m) => (
                        <li key={m.id} className="flex items-center gap-2 rounded-lg border border-border p-2 text-sm">
                            {m.type === 'video' ? <Video className="h-4 w-4 shrink-0" aria-hidden="true" /> : <FileText className="h-4 w-4 shrink-0" aria-hidden="true" />}
                            <a href={m.url} target="_blank" rel="noopener noreferrer" className="min-w-0 flex-1 truncate font-medium underline-offset-2 hover:underline">{m.title}</a>
                            <button type="button" onClick={() => retirer(m)} className="inline-flex h-9 w-9 items-center justify-center rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40" aria-label={t('Retirer « {nom} »', { nom: m.title })}>
                                <Trash2 className="h-4 w-4" aria-hidden="true" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
