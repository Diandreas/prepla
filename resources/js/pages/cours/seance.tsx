import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { appel } from '@/lib/cours-api';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CheckCircle2, Circle, Download, ExternalLink, Eye, EyeOff, Lock, Mic, Square, Video } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

/* eslint-disable @typescript-eslint/no-explicit-any */
interface Media { id: number; type: string; titre: string; mime: string | null; drive: boolean; url: string }
interface Etat {
    fait: boolean;
    score: number | null;
    ecoutes: number;
    pourcentage_vu: number;
    reponse: any;
    correction: { statut: string | null; ia: any; commentaire: string | null; note: number | null } | null;
}
export interface BlocEleve { id: number; type: string; titre: string | null; obligatoire: boolean; contenu: any; etat: Etat | null }
interface Props {
    apercu: boolean;
    parcours: { id: number; titre: string };
    seance: { id: number; titre: string; description: string | null; duree_minutes: number | null; blocs_ordre_impose: boolean };
    blocs: BlocEleve[];
    retourUrl: string;
}

const ETAT_VIDE: Etat = { fait: false, score: null, ecoutes: 0, pourcentage_vu: 0, reponse: null, correction: null };

/** Le lecteur de seance : bloc par bloc, completion enregistree, reprise au premier bloc non fait. */
export default function Seance({ apercu, parcours, seance, blocs, retourUrl }: Props) {
    const { t } = useTranslation();
    const [etats, setEtats] = useState<Record<number, Etat>>(() => Object.fromEntries(blocs.map((b) => [b.id, b.etat ?? ETAT_VIDE])));
    const premierNonFait = blocs.findIndex((b) => !(b.etat?.fait ?? false));
    const [index, setIndex] = useState(premierNonFait === -1 ? 0 : premierNonFait);
    const [erreur, setErreur] = useState<string | null>(null);

    const bloc = blocs[index];
    const majEtat = (id: number, partiel: Partial<Etat>) => setEtats((e) => ({ ...e, [id]: { ...(e[id] ?? ETAT_VIDE), ...partiel } }));

    /** Envoie une action ; en apercu, rien ne part au serveur. */
    const envoyer = async (b: BlocEleve, corps: Record<string, unknown> | FormData): Promise<any> => {
        setErreur(null);
        if (apercu) {
            const action = corps instanceof FormData ? corps.get('action') : corps.action;
            const r = { fait: action !== 'ecoute', ecoutes: (etats[b.id]?.ecoutes ?? 0) + (action === 'ecoute' ? 1 : 0) };
            majEtat(b.id, r);
            return r;
        }
        try {
            const r = await appel<any>(`/mes-cours/blocs/${b.id}`, 'POST', corps);
            majEtat(b.id, { fait: r.fait, score: r.score, ecoutes: r.ecoutes, pourcentage_vu: r.pourcentage_vu });
            return r;
        } catch (e) {
            setErreur((e as Error).message);
            return null;
        }
    };

    const accessible = (i: number) =>
        !seance.blocs_ordre_impose || blocs.slice(0, i).every((b) => !b.obligatoire || etats[b.id]?.fait);
    const faits = blocs.filter((b) => etats[b.id]?.fait).length;
    const finie = blocs.filter((b) => b.obligatoire).every((b) => etats[b.id]?.fait);

    return (
        <AppLayout>
            <Head title={seance.titre} />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                {apercu && (
                    <div className="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                        {t('courses.preview_banner', 'Aperçu : la séance telle que l’élève la voit. Rien n’est enregistré.')}
                    </div>
                )}
                <div>
                    <Link href={retourUrl} className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                        <ArrowLeft className="size-4" /> {parcours.titre}
                    </Link>
                    <h1 className="mt-2 text-2xl font-bold tracking-tight">{seance.titre}</h1>
                    {seance.description && <p className="mt-1 text-sm text-muted-foreground">{seance.description}</p>}
                    <p className="mt-1 text-xs text-muted-foreground">
                        {t('courses.progress_blocks', '{{done}} / {{total}} étapes', { done: faits, total: blocs.length })}
                        {seance.duree_minutes ? ` · ${t('courses.minutes', '{{n}} min', { n: seance.duree_minutes })}` : ''}
                    </p>
                </div>

                {blocs.length === 0 ? (
                    <p className="text-sm text-muted-foreground">{t('courses.empty_session', 'Cette séance est vide pour le moment.')}</p>
                ) : (
                    <>
                        <ol className="flex flex-wrap gap-2" aria-label={t('courses.steps', 'Étapes de la séance')}>
                            {blocs.map((b, i) => {
                                const ouvert = accessible(i);
                                return (
                                    <li key={b.id}>
                                        <button
                                            type="button"
                                            disabled={!ouvert}
                                            onClick={() => setIndex(i)}
                                            aria-current={i === index ? 'step' : undefined}
                                            className={`flex size-8 items-center justify-center rounded-full border text-xs font-semibold transition ${i === index ? 'border-primary bg-primary text-primary-foreground' : etats[b.id]?.fait ? 'border-emerald-400 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30' : 'bg-background'} disabled:opacity-50`}
                                            title={b.titre ?? undefined}
                                        >
                                            {!ouvert ? <Lock className="size-3.5" /> : etats[b.id]?.fait ? <CheckCircle2 className="size-4" /> : i + 1}
                                        </button>
                                    </li>
                                );
                            })}
                        </ol>

                        <section className="rounded-xl border bg-card p-4 md:p-6" key={bloc.id}>
                            <div className="mb-3 flex items-center justify-between gap-2">
                                <h2 className="text-lg font-semibold">{bloc.titre || t(`courses.type.${bloc.type}`, LIBELLES[bloc.type] ?? bloc.type)}</h2>
                                {etats[bloc.id]?.fait ? (
                                    <span className="inline-flex items-center gap-1 text-xs font-medium text-emerald-600"><CheckCircle2 className="size-4" /> {t('courses.done', 'Fait')}</span>
                                ) : !bloc.obligatoire ? (
                                    <span className="text-xs text-muted-foreground">{t('courses.optional', 'Facultatif')}</span>
                                ) : null}
                            </div>
                            <LecteurBloc bloc={bloc} etat={etats[bloc.id] ?? ETAT_VIDE} envoyer={envoyer} apercu={apercu} />
                            {erreur && <p className="mt-3 text-sm text-destructive" role="alert">{erreur}</p>}
                        </section>

                        <div className="flex items-center justify-between">
                            <Button variant="outline" disabled={index === 0} onClick={() => setIndex(index - 1)}>
                                <ArrowLeft className="size-4" /> {t('courses.previous', 'Précédent')}
                            </Button>
                            {index < blocs.length - 1 ? (
                                <Button disabled={!accessible(index + 1)} onClick={() => setIndex(index + 1)}>
                                    {t('courses.next', 'Suivant')} <ArrowRight className="size-4" />
                                </Button>
                            ) : (
                                <Button asChild variant={finie ? 'default' : 'outline'}>
                                    <Link href={retourUrl}>{finie ? t('courses.session_done', 'Séance terminée') : t('courses.back_to_path', 'Retour au parcours')}</Link>
                                </Button>
                            )}
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}

export const LIBELLES: Record<string, string> = {
    texte: 'Texte', video: 'Vidéo', audio: 'Audio', document: 'Document', exercice: 'Exercice', quiz: 'Quiz',
    ecrit: 'Production écrite', oral: 'Production orale', vocabulaire: 'Vocabulaire', lien: 'Lien', consigne: 'Consigne', live: 'Séance live',
};

type Envoyer = (b: BlocEleve, corps: Record<string, unknown> | FormData) => Promise<any>;

function BoutonFait({ bloc, etat, envoyer, libelle }: { bloc: BlocEleve; etat: Etat; envoyer: Envoyer; libelle?: string }) {
    const { t } = useTranslation();
    if (etat.fait) return null;
    return (
        <Button className="mt-4" onClick={() => envoyer(bloc, { action: 'terminer' })}>
            <CheckCircle2 className="size-4" /> {libelle ?? t('courses.mark_done', 'J’ai terminé')}
        </Button>
    );
}

function Html({ html }: { html: string }) {
    // Contenu assaini par le serveur (App\Services\Cours\HtmlAssaini).
    return <div className="prose prose-sm max-w-none dark:prose-invert [&_a]:text-primary [&_a]:underline [&_img]:max-w-full [&_img]:rounded" dangerouslySetInnerHTML={{ __html: html }} />;
}

function LecteurBloc({ bloc, etat, envoyer, apercu }: { bloc: BlocEleve; etat: Etat; envoyer: Envoyer; apercu: boolean }) {
    const { t } = useTranslation();
    const c = bloc.contenu ?? {};

    switch (bloc.type) {
        case 'texte':
            return (
                <>
                    <Html html={c.html ?? ''} />
                    <BoutonFait bloc={bloc} etat={etat} envoyer={envoyer} libelle={t('courses.read_done', 'J’ai lu')} />
                </>
            );
        case 'video':
            return <LecteurVideo bloc={bloc} etat={etat} envoyer={envoyer} />;
        case 'audio':
            return <LecteurAudio bloc={bloc} etat={etat} envoyer={envoyer} />;
        case 'document':
            return (
                <>
                    {!c.media ? <p className="text-sm text-muted-foreground">{t('courses.no_file', 'Aucun fichier.')}</p> : c.media.drive || c.media.type !== 'image' ? (
                        <iframe
                            src={c.media.drive ? c.media.url : `${c.media.url}${c.telechargeable ? '' : '#toolbar=0'}`}
                            title={c.media.titre}
                            className="h-[70vh] w-full rounded border"
                        />
                    ) : (
                        <img src={c.media.url} alt={c.media.titre} className="max-h-[70vh] w-full rounded object-contain" onContextMenu={c.telechargeable ? undefined : (e) => e.preventDefault()} />
                    )}
                    {c.media && c.telechargeable && !c.media.drive && (
                        <a href={c.media.url} download className="mt-3 inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
                            <Download className="size-4" /> {t('courses.download', 'Télécharger')}
                        </a>
                    )}
                    <div><BoutonFait bloc={bloc} etat={etat} envoyer={envoyer} /></div>
                </>
            );
        case 'exercice':
            return (
                <div className="space-y-3">
                    {c.seuil && <p className="text-sm text-muted-foreground">{t('courses.exercise_threshold', 'À réussir avec au moins {{n}} %.', { n: c.seuil })}</p>}
                    {etat.score !== null && <p className="text-sm">{t('courses.best_score', 'Meilleur score : {{n}} %', { n: etat.score })}</p>}
                    {c.exercise_id ? (
                        apercu ? (
                            <p className="text-sm text-muted-foreground">{t('courses.exercise_preview', 'L’élève ouvre ici l’exercice n° {{id}}.', { id: c.exercise_id })}</p>
                        ) : (
                            <Button asChild><Link href={route('exercise.show', c.exercise_id)}>{etat.fait ? t('courses.redo_exercise', 'Refaire l’exercice') : t('courses.do_exercise', 'Faire l’exercice')} <ArrowRight className="size-4" /></Link></Button>
                        )
                    ) : (
                        <p className="text-sm text-muted-foreground">{t('courses.no_exercise', 'Aucun exercice choisi.')}</p>
                    )}
                </div>
            );
        case 'quiz':
            return <LecteurQuiz bloc={bloc} etat={etat} envoyer={envoyer} apercu={apercu} />;
        case 'ecrit':
            return <ProductionEcrite bloc={bloc} etat={etat} envoyer={envoyer} />;
        case 'oral':
            return <ProductionOrale bloc={bloc} etat={etat} envoyer={envoyer} />;
        case 'vocabulaire':
            return (
                <>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {(c.mots ?? []).map((m: any, i: number) => <Flashcard key={i} mot={m} />)}
                    </div>
                    <BoutonFait bloc={bloc} etat={etat} envoyer={envoyer} libelle={t('courses.revised', 'J’ai révisé ces mots')} />
                </>
            );
        case 'lien':
            return c.url ? (
                <a href={c.url} target="_blank" rel="noopener noreferrer" onClick={() => envoyer(bloc, { action: 'terminer' })} className="inline-flex items-center gap-2 font-medium text-primary hover:underline">
                    <ExternalLink className="size-4" /> {c.libelle || c.url}
                </a>
            ) : null;
        case 'consigne':
            return (
                <>
                    <p className="whitespace-pre-line rounded-lg bg-muted/60 p-4 text-sm">{c.texte}</p>
                    <BoutonFait bloc={bloc} etat={etat} envoyer={envoyer} libelle={t('courses.understood', 'Compris')} />
                </>
            );
        case 'live': {
            const date = c.date ? new Date(c.date) : null;
            return (
                <div className="space-y-3">
                    {date && <p className="text-sm">{t('courses.live_at', 'Rendez-vous le {{date}}', { date: date.toLocaleString() })}</p>}
                    {c.url ? (
                        <Button asChild>
                            <a href={c.url} target="_blank" rel="noopener noreferrer" onClick={() => envoyer(bloc, { action: 'terminer' })}>
                                <Video className="size-4" /> {t('courses.join_live', 'Rejoindre la séance live')}
                            </a>
                        </Button>
                    ) : (
                        <p className="text-sm text-muted-foreground">{t('courses.live_link_soon', 'Le lien sera communiqué par votre enseignant.')}</p>
                    )}
                </div>
            );
        }
        default:
            return null;
    }
}

function LecteurVideo({ bloc, etat, envoyer }: { bloc: BlocEleve; etat: Etat; envoyer: Envoyer }) {
    const { t } = useTranslation();
    const c = bloc.contenu;
    const seuil: number = c.seuil_visionnage ?? 0;
    const dernierEnvoi = useRef(etat.pourcentage_vu ?? 0);
    const vuMax = useRef(0);
    // Video integree (YouTube, Vimeo, Drive) : on ne mesure pas le visionnage ;
    // le bouton s'ouvre apres la part de la duree demandee.
    const attente = c.source !== 'media' || c.media?.drive ? Math.round(((c.duree_secondes ?? 0) * seuil) / 100) : 0;
    const [reste, setReste] = useState(attente);
    useEffect(() => {
        if (reste <= 0) return;
        const id = window.setTimeout(() => setReste((r) => r - 1), 1000);
        return () => window.clearTimeout(id);
    }, [reste]);

    const fichier = c.source === 'media' && c.media && !c.media.drive;
    const src = c.source === 'youtube' && c.video_id ? `https://www.youtube-nocookie.com/embed/${c.video_id}` : c.source === 'vimeo' && c.video_id ? `https://player.vimeo.com/video/${c.video_id}` : c.media?.drive ? c.media.url : null;

    return (
        <div className="space-y-3">
            {fichier ? (
                <video
                    controls
                    controlsList="nodownload"
                    src={c.media.url}
                    className="w-full rounded-lg bg-black"
                    onTimeUpdate={(e) => {
                        const v = e.currentTarget;
                        if (!v.duration) return;
                        // Seul ce qui a ete regarde compte : sauter a la fin ne valide rien.
                        if (v.currentTime - vuMax.current < 3) vuMax.current = Math.max(vuMax.current, v.currentTime);
                        const pct = Math.floor((vuMax.current / v.duration) * 100);
                        if (pct >= dernierEnvoi.current + 10 || (pct >= seuil && dernierEnvoi.current < seuil) || pct >= 99) {
                            if (pct > dernierEnvoi.current) {
                                dernierEnvoi.current = pct;
                                envoyer(bloc, { action: 'video', pourcentage: pct });
                            }
                        }
                    }}
                />
            ) : src ? (
                <div className="aspect-video w-full overflow-hidden rounded-lg bg-black">
                    <iframe src={src} title={bloc.titre ?? 'video'} className="size-full" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowFullScreen />
                </div>
            ) : (
                <p className="text-sm text-muted-foreground">{t('courses.no_video', 'Aucune vidéo.')}</p>
            )}
            {seuil > 0 && fichier && !etat.fait && (
                <p className="text-xs text-muted-foreground">{t('courses.watch_threshold', 'Regardez au moins {{n}} % de la vidéo pour la valider.', { n: seuil })}</p>
            )}
            {!fichier && !etat.fait && (
                <Button disabled={reste > 0} onClick={() => envoyer(bloc, { action: 'terminer' })}>
                    <CheckCircle2 className="size-4" /> {reste > 0 ? t('courses.wait_seconds', 'Encore {{n}} s', { n: reste }) : t('courses.watched', 'J’ai regardé la vidéo')}
                </Button>
            )}
        </div>
    );
}

function LecteurAudio({ bloc, etat, envoyer }: { bloc: BlocEleve; etat: Etat; envoyer: Envoyer }) {
    const { t } = useTranslation();
    const c = bloc.contenu;
    const max: number | null = c.ecoutes_max ?? null;
    const [texteVisible, setTexteVisible] = useState(c.mode_texte === 'lecture' || !c.texte_masque);
    const audio = useRef<HTMLAudioElement>(null);
    const epuise = max !== null && etat.ecoutes >= max;
    const enCours = useRef(false);

    return (
        <div className="space-y-3">
            {!c.media ? (
                <p className="text-sm text-muted-foreground">{t('courses.no_audio', 'Aucun audio.')}</p>
            ) : c.media.drive ? (
                <iframe src={c.media.url} title={c.media.titre} className="h-24 w-full rounded border" />
            ) : (
                <audio
                    ref={audio}
                    controls={!epuise || enCours.current}
                    controlsList="nodownload noplaybackrate"
                    src={c.media.url}
                    className="w-full"
                    onPlay={async (e) => {
                        const a = e.currentTarget;
                        if (max === null || enCours.current) return;
                        // Une ecoute commence au debut ; au-dela du maximum, on refuse.
                        if (a.currentTime > 1) return;
                        a.pause();
                        const r = await envoyer(bloc, { action: 'ecoute' });
                        if (r) {
                            enCours.current = true;
                            a.play();
                        }
                    }}
                    onEnded={() => {
                        enCours.current = false;
                    }}
                />
            )}
            {max !== null && (
                <p className="text-xs text-muted-foreground">{t('courses.listens', 'Écoutes : {{n}} / {{max}}', { n: etat.ecoutes, max })}</p>
            )}
            {c.mode_texte !== 'aucun' && c.texte_html && (
                <div className="rounded-lg border p-3">
                    {c.mode_texte === 'transcription' && (
                        <Button variant="ghost" size="sm" onClick={() => setTexteVisible((v) => !v)} aria-expanded={texteVisible}>
                            {texteVisible ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                            {texteVisible ? t('courses.hide_transcript', 'Masquer la transcription') : t('courses.show_transcript', 'Afficher la transcription')}
                        </Button>
                    )}
                    {texteVisible && <div className="mt-2"><Html html={c.texte_html} /></div>}
                </div>
            )}
            <BoutonFait bloc={bloc} etat={etat} envoyer={envoyer} />
        </div>
    );
}

function LecteurQuiz({ bloc, etat, envoyer, apercu }: { bloc: BlocEleve; etat: Etat; envoyer: Envoyer; apercu: boolean }) {
    const { t } = useTranslation();
    const questions: any[] = bloc.contenu.questions ?? [];
    const [reponses, setReponses] = useState<any[]>(() => questions.map((q) => (q.type === 'trous' ? q.segments.filter((s: any) => s === null).map(() => '') : null)));
    const [resultat, setResultat] = useState<{ score: number; reussi: boolean; details: boolean[] } | null>(null);
    const fixer = (i: number, v: any) => setReponses((r) => r.map((x, j) => (j === i ? v : x)));

    return (
        <form
            className="space-y-5"
            onSubmit={async (e) => {
                e.preventDefault();
                const r = await envoyer(bloc, { action: 'quiz', reponses });
                if (r && !apercu) setResultat(r);
            }}
        >
            <p className="text-sm text-muted-foreground">{t('courses.quiz_threshold', 'Réussite à partir de {{n}} %.', { n: bloc.contenu.seuil })}</p>
            {questions.map((q, i) => (
                <fieldset key={i} className="space-y-2">
                    <legend className="text-sm font-medium">{i + 1}. {q.enonce}</legend>
                    {q.type === 'qcm' && q.choix.map((ch: string, j: number) => (
                        <label key={j} className="flex items-center gap-2 text-sm">
                            <input type="radio" name={`q${bloc.id}-${i}`} checked={reponses[i] === j} onChange={() => fixer(i, j)} /> {ch}
                        </label>
                    ))}
                    {q.type === 'vrai_faux' && [true, false].map((v) => (
                        <label key={String(v)} className="mr-4 inline-flex items-center gap-2 text-sm">
                            <input type="radio" name={`q${bloc.id}-${i}`} checked={reponses[i] === v} onChange={() => fixer(i, v)} /> {v ? t('courses.true', 'Vrai') : t('courses.false', 'Faux')}
                        </label>
                    ))}
                    {q.type === 'trous' && (
                        <p className="text-sm leading-9">
                            {(() => {
                                let k = -1;
                                return q.segments.map((s: string | null, j: number) => {
                                    if (s !== null) return <span key={j}>{s}</span>;
                                    k += 1;
                                    const n = k;
                                    return (
                                        <input
                                            key={j}
                                            aria-label={t('courses.blank', 'Trou {{n}}', { n: n + 1 })}
                                            className="mx-1 w-28 rounded border px-2 py-0.5"
                                            value={reponses[i]?.[n] ?? ''}
                                            onChange={(e) => fixer(i, (reponses[i] as string[]).map((x, m) => (m === n ? e.target.value : x)))}
                                        />
                                    );
                                });
                            })()}
                        </p>
                    )}
                </fieldset>
            ))}
            <Button type="submit">{t('courses.check', 'Vérifier')}</Button>
            {apercu && <p className="text-xs text-muted-foreground">{t('courses.preview_quiz', 'En aperçu, le quiz n’est pas corrigé.')}</p>}
            {(resultat || etat.score !== null) && (
                <p className={`text-sm font-medium ${resultat?.reussi || etat.fait ? 'text-emerald-600' : 'text-amber-600'}`} role="status">
                    {resultat
                        ? resultat.reussi
                            ? t('courses.quiz_passed', 'Réussi : {{n}} %', { n: resultat.score })
                            : t('courses.quiz_failed', '{{n}} % : pas encore le seuil, réessayez.', { n: resultat.score })
                        : t('courses.best_score', 'Meilleur score : {{n}} %', { n: etat.score })}
                </p>
            )}
        </form>
    );
}

function Correction({ etat }: { etat: Etat }) {
    const { t } = useTranslation();
    const c = etat.correction;
    if (!c) return null;
    return (
        <div className="mt-4 space-y-2 rounded-lg border bg-muted/40 p-3 text-sm">
            {c.statut === 'corrige' ? (
                <>
                    <p className="font-medium">{t('courses.teacher_feedback', 'Correction de votre enseignant')}{c.note !== null ? ` · ${c.note}/20` : ''}</p>
                    {c.commentaire && <p className="whitespace-pre-line">{c.commentaire}</p>}
                </>
            ) : (
                <p>{t('courses.waiting_teacher', 'Envoyé : votre enseignant va corriger.')}</p>
            )}
            {c.ia?.feedback && <p className="text-muted-foreground"><span className="font-medium">{t('courses.ai_feedback', 'Premier avis automatique')} :</span> {String(c.ia.feedback)}</p>}
        </div>
    );
}

function ProductionEcrite({ bloc, etat, envoyer }: { bloc: BlocEleve; etat: Etat; envoyer: Envoyer }) {
    const { t } = useTranslation();
    const c = bloc.contenu;
    const [texte, setTexte] = useState<string>(etat.reponse?.texte ?? '');
    const [envoi, setEnvoi] = useState(false);
    const mots = useMemo(() => texte.trim().split(/\s+/).filter(Boolean).length, [texte]);
    return (
        <form
            className="space-y-3"
            onSubmit={async (e) => {
                e.preventDefault();
                setEnvoi(true);
                await envoyer(bloc, { action: 'ecrit', texte });
                setEnvoi(false);
            }}
        >
            <p className="whitespace-pre-line text-sm">{c.consigne}</p>
            <textarea value={texte} onChange={(e) => setTexte(e.target.value)} rows={10} className="w-full rounded-lg border bg-background p-3 text-sm" aria-label={t('courses.your_text', 'Votre texte')} />
            <p className="text-xs text-muted-foreground">
                {t('courses.word_count', '{{n}} mots', { n: mots })}
                {c.mots_min ? ` · min ${c.mots_min}` : ''}
                {c.mots_max ? ` · max ${c.mots_max}` : ''}
            </p>
            <Button type="submit" disabled={envoi || mots === 0}>{etat.fait ? t('courses.resend', 'Renvoyer') : t('courses.send', 'Envoyer')}</Button>
            <Correction etat={etat} />
        </form>
    );
}

function ProductionOrale({ bloc, etat, envoyer }: { bloc: BlocEleve; etat: Etat; envoyer: Envoyer }) {
    const { t } = useTranslation();
    const c = bloc.contenu;
    const [enregistre, setEnregistre] = useState(false);
    const [blob, setBlob] = useState<Blob | null>(null);
    const [secondes, setSecondes] = useState(0);
    const [erreurMicro, setErreurMicro] = useState<string | null>(null);
    const recorder = useRef<MediaRecorder | null>(null);
    const minuterie = useRef<number | null>(null);
    const apercuUrl = useMemo(() => (blob ? URL.createObjectURL(blob) : null), [blob]);
    const max: number = c.duree_max_secondes ?? 120;

    const arreter = () => {
        recorder.current?.stop();
        if (minuterie.current) window.clearInterval(minuterie.current);
        setEnregistre(false);
    };
    const demarrer = async () => {
        setErreurMicro(null);
        try {
            const flux = await navigator.mediaDevices.getUserMedia({ audio: true });
            const morceaux: Blob[] = [];
            const r = new MediaRecorder(flux);
            r.ondataavailable = (e) => morceaux.push(e.data);
            r.onstop = () => {
                setBlob(new Blob(morceaux, { type: r.mimeType || 'audio/webm' }));
                flux.getTracks().forEach((p) => p.stop());
            };
            recorder.current = r;
            r.start();
            setSecondes(0);
            setEnregistre(true);
            minuterie.current = window.setInterval(() => setSecondes((s) => {
                if (s + 1 >= max) arreter();
                return s + 1;
            }), 1000);
        } catch {
            setErreurMicro(t('courses.mic_error', 'Micro indisponible : autorisez l’accès au micro.'));
        }
    };

    return (
        <div className="space-y-3">
            <p className="whitespace-pre-line text-sm">{c.consigne}</p>
            <p className="text-xs text-muted-foreground">{t('courses.max_duration', 'Durée maximale : {{n}} s', { n: max })}</p>
            {enregistre ? (
                <Button variant="destructive" onClick={arreter}><Square className="size-4" /> {t('courses.stop', 'Arrêter')} ({secondes} s)</Button>
            ) : (
                <Button variant="outline" onClick={demarrer}><Mic className="size-4" /> {blob ? t('courses.record_again', 'Recommencer') : t('courses.record', 'Enregistrer')}</Button>
            )}
            {erreurMicro && <p className="text-sm text-destructive">{erreurMicro}</p>}
            {apercuUrl && <audio controls src={apercuUrl} className="w-full" />}
            {blob && (
                <Button
                    onClick={async () => {
                        const f = new FormData();
                        f.append('action', 'oral');
                        f.append('audio', new File([blob], 'enregistrement.webm', { type: blob.type }));
                        if (await envoyer(bloc, f)) setBlob(null);
                    }}
                >
                    {t('courses.send', 'Envoyer')}
                </Button>
            )}
            {etat.reponse?.audio_url && !blob && <audio controls src={etat.reponse.audio_url} className="w-full" />}
            <Correction etat={etat} />
        </div>
    );
}

function Flashcard({ mot }: { mot: { mot: string; traduction: string; exemple?: string } }) {
    const [retournee, setRetournee] = useState(false);
    return (
        <button type="button" onClick={() => setRetournee((r) => !r)} aria-pressed={retournee} className="min-h-24 rounded-lg border bg-background p-4 text-left transition hover:border-primary/50">
            {retournee ? (
                <>
                    <span className="block font-semibold">{mot.traduction}</span>
                    {mot.exemple && <span className="mt-1 block text-xs text-muted-foreground">{mot.exemple}</span>}
                </>
            ) : (
                <span className="flex items-center gap-2 text-lg font-semibold"><Circle className="size-3 text-primary" />{mot.mot}</span>
            )}
        </button>
    );
}
