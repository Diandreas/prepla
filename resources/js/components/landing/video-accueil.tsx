import { Link } from '@inertiajs/react';
import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import type { VideoAccueil } from '@/data/languages';
import { REPLIQUES } from './video-repliques';

/**
 * La video du haut de page : « PrePla, par ou commencer ? », 34 s, en francais.
 *
 * Rien ne part avec le son, et le film n'est jamais precharge : avant le clic, on
 * ne montre qu'une image (et, si rien ne s'y oppose, un apercu muet de 4 s qui
 * pese 60 Ko). Au clic, la source, le son et la lecture sont poses dans le meme
 * geste — sans quoi Safari sur iPhone refuse le son. Sur ordinateur, le film se
 * lit dans le cadre ; sur un telephone tenu droit, en plein ecran vertical, la
 * version 16:9 y ayant des sous-titres de 9 px.
 *
 * Le film finit sur une IMAGE de bouton : de vrais boutons la recouvrent, dont
 * l'essai sans compte, qui prolonge sa derniere phrase.
 */

type Langue = 'fr' | 'en' | 'de';

interface Commande {
    (depuis?: number): void;
}

const ContexteVideo = createContext<{ jouer: Commande; enregistrer: (c: Commande | null) => void }>({
    jouer: () => {},
    enregistrer: () => {},
});

/** Permet aux autres sections (etapes, dernier appel) de lancer le film. */
export function VideoAccueilProvider({ children }: { children: React.ReactNode }) {
    const commande = useRef<Commande | null>(null);
    const valeur = useMemo(
        () => ({
            jouer: (depuis?: number) => commande.current?.(depuis),
            enregistrer: (c: Commande | null) => {
                commande.current = c;
            },
        }),
        [],
    );

    return <ContexteVideo.Provider value={valeur}>{children}</ContexteVideo.Provider>;
}

export function useVideoAccueil() {
    return useContext(ContexteVideo);
}

interface ConnexionReseau {
    saveData?: boolean;
    effectiveType?: string;
}

/** Economie de donnees demandee, ou reseau classe 2g : on sert le plus leger. */
function economieDeDonnees(): boolean {
    const connexion = (navigator as Navigator & { connection?: ConnexionReseau }).connection;

    return !!connexion?.saveData || ['slow-2g', '2g'].includes(connexion?.effectiveType ?? '');
}

function mouvementReduit(): boolean {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/** Un telephone tenu droit : le film s'y lit en plein ecran, en version verticale. */
function telephoneDroit(): boolean {
    return window.matchMedia('(max-width: 767px)').matches;
}

/**
 * La langue dans laquelle la page s'affiche VRAIMENT : un navigateur en espagnol
 * recoit les textes anglais (langue de repli), et doit donc etre prevenu, comme
 * un visiteur anglais, que la video est en francais.
 */
function langueDe(code: string | undefined): Langue {
    const l = (code ?? 'en').slice(0, 2);

    return l === 'fr' || l === 'de' ? l : 'en';
}

function enMegaOctets(octets: number, langue: Langue): string {
    const nombre = new Intl.NumberFormat(langue, { maximumFractionDigits: 1, minimumFractionDigits: 1 }).format(octets / 1_000_000);

    return `${nombre} ${langue === 'fr' ? 'Mo' : 'MB'}`;
}

export function LecteurAccueil({ video }: { video: VideoAccueil }) {
    const { t, i18n } = useTranslation();
    const langue = langueDe(i18n.resolvedLanguage ?? i18n.language);
    const { enregistrer } = useVideoAccueil();

    const cadre = useRef<HTMLDivElement>(null);
    const declencheur = useRef<HTMLButtonElement>(null);
    const boucle = useRef<HTMLVideoElement>(null);
    const film = useRef<HTMLVideoElement>(null);
    const dialogue = useRef<HTMLDialogElement>(null);
    const filmVertical = useRef<HTMLVideoElement>(null);

    // attente : l'affiche (et l'apercu) ; dansLeCadre : le film se lit dans le
    // cadre ; pleinEcran : la fenetre du telephone est ouverte.
    const [etat, setEtat] = useState<'attente' | 'dansLeCadre' | 'pleinEcran'>('attente');
    const [filmVisible, setFilmVisible] = useState(false);
    const [fin, setFin] = useState(false);
    const [instant, setInstant] = useState(0);
    const [transcription, setTranscription] = useState(false);

    // L'apercu : permis ou non, en cours ou en pause, visible ou non.
    const [apercuPermis, setApercuPermis] = useState(false);
    const [apercuEnPause, setApercuEnPause] = useState(false);
    const [apercuVisible, setApercuVisible] = useState(false);
    const tours = useRef(0);

    // Le poids de ce qui sera vraiment servi a CET appareil.
    const [poids, setPoids] = useState<number>(video.poids.paysage);
    useEffect(() => {
        const leger = economieDeDonnees();
        if (telephoneDroit()) {
            setPoids(leger ? video.poids.portrait_leger : video.poids.portrait);
        } else {
            setPoids(leger ? video.poids.paysage_leger : video.poids.paysage);
        }
    }, [video]);

    // L'apercu ne demarre qu'une fois la page chargee et le navigateur au repos,
    // et jamais si l'on a demande moins d'animations ou d'economiser les donnees.
    useEffect(() => {
        if (mouvementReduit() || economieDeDonnees()) return;

        let annule = false;
        const permettre = () => {
            if (annule) return;
            const auRepos = (window as Window & { requestIdleCallback?: (f: () => void) => number }).requestIdleCallback;
            if (auRepos) auRepos(() => !annule && setApercuPermis(true));
            else window.setTimeout(() => !annule && setApercuPermis(true), 300);
        };

        if (document.readyState === 'complete') permettre();
        else window.addEventListener('load', permettre, { once: true });

        return () => {
            annule = true;
            window.removeEventListener('load', permettre);
        };
    }, []);

    // Visible a 25 % au moins, onglet au premier plan : sinon l'apercu s'arrete.
    useEffect(() => {
        const element = cadre.current;
        if (!apercuPermis || !element) return;

        let dansLEcran = false;
        const appliquer = () => {
            const v = boucle.current;
            if (!v) return;
            const doitJouer = dansLEcran && document.visibilityState === 'visible' && !apercuEnPause && etat === 'attente' && tours.current < 3;
            if (doitJouer && v.paused) {
                void v.play().then(() => setApercuVisible(true)).catch(() => {
                    // Lecture automatique refusee (economie d'energie, navigateur) :
                    // l'affiche reste, la page est complete sans l'apercu.
                });
            } else if (!doitJouer && !v.paused) {
                v.pause();
            }
        };

        const observateur = new IntersectionObserver(([entree]) => {
            dansLEcran = entree.isIntersecting;
            appliquer();
        }, { threshold: 0.25 });
        observateur.observe(element);
        document.addEventListener('visibilitychange', appliquer);

        return () => {
            observateur.disconnect();
            document.removeEventListener('visibilitychange', appliquer);
        };
    }, [apercuPermis, apercuEnPause, etat]);

    // Trois tours au plus, puis l'apercu s'arrete sur sa premiere image — la meme
    // que l'affiche.
    const finDUnTour = () => {
        const v = boucle.current;
        if (!v) return;
        tours.current += 1;
        v.currentTime = 0;
        if (tours.current < 3 && !apercuEnPause) {
            void v.play().catch(() => {});
        } else {
            setApercuVisible(false);
        }
    };

    const arreterApercu = () => {
        boucle.current?.pause();
        setApercuVisible(false);
    };

    const lancer = useCallback(
        (depuis = 0) => {
            const leger = economieDeDonnees();
            arreterApercu();
            setFin(depuis >= video.fin);

            // Tout dans le geste du clic : la source, le son, la lecture.
            const demarrer = (v: HTMLVideoElement, source: string) => {
                if (v.getAttribute('src') !== source) {
                    v.src = source;
                }
                v.muted = false;
                if (depuis > 0) {
                    if (v.readyState >= 1) v.currentTime = depuis;
                    else v.addEventListener('loadedmetadata', () => { v.currentTime = depuis; }, { once: true });
                } else if (v.readyState >= 1) {
                    v.currentTime = 0;
                }
                void v.play().catch(() => {});
            };

            if (telephoneDroit()) {
                const fenetre = dialogue.current;
                const v = filmVertical.current;
                if (!fenetre || !v) return;
                if (!fenetre.open) fenetre.showModal();
                demarrer(v, leger ? video.sources.portrait_leger : video.sources.portrait);
                setEtat('pleinEcran');

                return;
            }

            const v = film.current;
            if (!v) return;
            demarrer(v, leger ? video.sources.paysage_leger : video.sources.paysage);
            setEtat('dansLeCadre');
        },
        [video],
    );

    // Les etapes et le dernier appel lancent le film a un passage precis.
    useEffect(() => {
        enregistrer((depuis?: number) => {
            cadre.current?.scrollIntoView({ behavior: mouvementReduit() ? 'auto' : 'smooth', block: 'center' });
            lancer(depuis ?? 0);
        });

        return () => enregistrer(null);
    }, [enregistrer, lancer]);

    const suivre = (v: HTMLVideoElement) => {
        setInstant(v.currentTime);
        // La carte de fin du film montre une image de bouton : on la recouvre.
        setFin(v.currentTime >= video.fin || v.ended);
    };

    const fermerPleinEcran = () => {
        filmVertical.current?.pause();
        setEtat('attente');
        setFin(false);
        declencheur.current?.focus();
    };

    const revoir = () => lancer(0);

    const repliques = REPLIQUES[langue];
    const replique = repliques.find((r) => instant >= r.debut && instant < r.fin);
    const libelleJouer = langue === 'fr'
        ? t('landing.video_jouer_aria')
        : `${t('landing.video_jouer_aria')} ${t('landing.video_en_francais_aria')}`;

    const ecranDeFin = (
        <div className="lecteur-fin" role="group" aria-labelledby="lecteur-fin-titre">
            <h3 id="lecteur-fin-titre">{t('landing.video_fin_titre')}</h3>
            <p>{t('landing.video_fin_texte')}</p>
            <div className="lecteur-fin-boutons">
                <Link href="/essayer?depuis=video" className="bouton-essai">
                    {t('landing.video_fin_essayer')}
                </Link>
                <Link href="/register" className="bouton-compte">
                    {t('landing.video_fin_compte')}
                </Link>
            </div>
            <button type="button" className="lecteur-revoir" onClick={revoir}>
                ↺ {t('landing.video_revoir')}
            </button>
        </div>
    );

    return (
        <div>
            <div ref={cadre} className="lecteur">
                <picture>
                    <source type="image/webp" srcSet={`${video.sources.affiche_640} 640w, ${video.sources.affiche_1280} 1280w`} sizes="(min-width: 1024px) 640px, 100vw" />
                    <img
                        src={video.sources.affiche_640_jpg}
                        srcSet={`${video.sources.affiche_640_jpg} 640w, ${video.sources.affiche_1280_jpg} 1280w`}
                        sizes="(min-width: 1024px) 640px, 100vw"
                        width={1280}
                        height={720}
                        alt={t('landing.video_affiche_alt')}
                        fetchPriority="high"
                    />
                </picture>

                {apercuPermis && etat === 'attente' && (
                    <video
                        ref={boucle}
                        className="lecteur-boucle"
                        data-visible={apercuVisible}
                        src={window.devicePixelRatio > 1.5 && !telephoneDroit() ? video.sources.boucle_960 : video.sources.boucle_640}
                        muted
                        playsInline
                        preload="auto"
                        aria-hidden="true"
                        tabIndex={-1}
                        onEnded={finDUnTour}
                    />
                )}

                {/* Toujours monte, sans source : le clic n'a plus qu'a la poser. */}
                <video
                    ref={film}
                    className="lecteur-film"
                    data-visible={etat === 'dansLeCadre' && filmVisible}
                    preload="none"
                    playsInline
                    controls={etat === 'dansLeCadre'}
                    controlsList="nodownload noremoteplayback"
                    disablePictureInPicture
                    tabIndex={etat === 'dansLeCadre' ? 0 : -1}
                    aria-hidden={etat !== 'dansLeCadre'}
                    onPlaying={() => setFilmVisible(true)}
                    onTimeUpdate={(e) => suivre(e.currentTarget)}
                    onEnded={(e) => suivre(e.currentTarget)}
                />

                {etat !== 'dansLeCadre' && (
                    <>
                        <button
                            ref={declencheur}
                            type="button"
                            className="lecteur-declencheur"
                            aria-label={libelleJouer}
                            onClick={() => lancer(0)}
                        />
                        <span className="lecteur-jouer" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="#0b1322"><path d="M8 5.5v13l11-6.5z" /></svg>
                        </span>
                    </>
                )}

                {apercuPermis && etat === 'attente' && apercuVisible && (
                    <button
                        type="button"
                        className="lecteur-pause"
                        aria-label={apercuEnPause ? t('landing.video_apercu_relancer') : t('landing.video_apercu_pause')}
                        onClick={() => setApercuEnPause((p) => !p)}
                    >
                        {apercuEnPause ? (
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.5v13l11-6.5z" /></svg>
                        ) : (
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M7 5h4v14H7zM13 5h4v14h-4z" /></svg>
                        )}
                    </button>
                )}

                {etat === 'dansLeCadre' && fin && ecranDeFin}
            </div>

            {/* Le film est en francais : ailleurs, une bande traduite HORS de l'image. */}
            {etat === 'dansLeCadre' && langue !== 'fr' && (
                <p className="lecteur-bande" aria-live="off">{replique?.texte ?? ' '}</p>
            )}

            <div className="lecteur-legende">
                <button type="button" onClick={() => lancer(0)}>
                    <strong>▶ {t('landing.video_regarder')}</strong>
                    {' · '}
                    {t('landing.video_duree', { secondes: video.duree })}
                    {' · '}
                    {langue === 'fr' ? t('landing.video_avec_son') : t('landing.video_en_francais')}
                    {' · '}
                    <span className="whitespace-nowrap">{enMegaOctets(poids, langue)}</span>
                </button>
                <button type="button" className="lecteur-lire" aria-expanded={transcription} aria-controls="lecteur-transcription" onClick={() => setTranscription((o) => !o)}>
                    {t('landing.video_lire_texte')}
                </button>
            </div>

            {transcription && (
                <div id="lecteur-transcription" className="lecteur-transcription">
                    {repliques.map((r) => (
                        <p key={r.debut}>{r.texte}</p>
                    ))}
                    {langue !== 'fr' && (
                        <details>
                            <summary>{t('landing.video_texte_original')}</summary>
                            <div lang="fr" className="mt-2">
                                {REPLIQUES.fr.map((r) => (
                                    <p key={r.debut}>{r.texte}</p>
                                ))}
                            </div>
                        </details>
                    )}
                </div>
            )}

            <dialog
                ref={dialogue}
                className="lecteur-dialogue"
                aria-label={t('landing.video_titre')}
                onClose={fermerPleinEcran}
            >
                <div className="lecteur-dialogue-haut">
                    <span>{t('landing.video_titre')} · {t('landing.video_duree', { secondes: video.duree })}</span>
                    <button type="button" aria-label={t('landing.video_fermer')} onClick={() => dialogue.current?.close()}>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"><path d="M6 6l12 12M18 6L6 18" /></svg>
                    </button>
                </div>
                {langue !== 'fr' && etat === 'pleinEcran' && (
                    <p className="lecteur-bande" aria-live="off">{replique?.texte ?? ' '}</p>
                )}
                <div className="lecteur-dialogue-scene">
                    <video
                        ref={filmVertical}
                        preload="none"
                        playsInline
                        controls
                        controlsList="nodownload noremoteplayback"
                        disablePictureInPicture
                        // Chargee a l'ouverture seulement : 21 Ko de moins pour qui ne lance pas le film.
                        poster={etat === 'pleinEcran' ? video.sources.affiche_portrait : undefined}
                        onTimeUpdate={(e) => suivre(e.currentTarget)}
                        onEnded={(e) => suivre(e.currentTarget)}
                    />
                    {etat === 'pleinEcran' && fin && ecranDeFin}
                </div>
            </dialog>
        </div>
    );
}
