import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { EssaiLangue, Exam, Language, VideoAccueil } from '@/data/languages';
import type { SharedData } from '@/types';
import { LecteurAccueil } from './video-accueil';

/**
 * Le haut de page. Le titre pose la question de la video — « Examen de langue :
 * par ou commencer ? » —, la video y repond en 34 secondes, avec des visages, et
 * les dix examens sont des boutons des le premier ecran : on touche le sien, et
 * un vrai exercice s'ouvre, sans compte.
 *
 * Ont disparu : la carte « EXERCICE EN DIRECT » qui se repondait toute seule, le
 * bloc « Votre niveau estime » aux pourcentages inventes, et les chiffres
 * « 3 · 10 · ∞ ». La page ne montre plus que ce qu'un visiteur peut verifier.
 */

/** Le nom qui tient dans un bouton : « Goethe », « ÖSD », « telc ». */
function nomCourt(nom: string): string {
    return nom.replace(/[-\s]Zertifikat$/i, '').replace(/\sDeutsch$/i, '');
}

/** Ou mene un examen : son premier exercice d'essai (l'officiel d'abord), sinon sa page d'essai. */
function versLEssai(examen: Exam, essai: EssaiLangue[]): string {
    for (const langue of essai) {
        const exercices = langue.examens.find((e) => e.slug === examen.slug)?.exercices ?? [];
        const premier = exercices.find((e) => e.officiel) ?? exercices[0];
        if (premier) return route('essai.show', premier.id);
    }

    return `/essayer?examen=${examen.slug}`;
}

function ChoixExamen({ langues, essai }: { langues: Language[]; essai: EssaiLangue[] }) {
    const { t } = useTranslation();

    return (
        <div className="accueil-choix">
            <p className="accueil-choix-titre">{t('landing.choix_titre')}</p>
            <p className="accueil-choix-aide">{t('landing.choix_aide')}</p>
            <div className="accueil-choix-langues">
                {langues.map((langue) => (
                    <div key={langue.slug} className="accueil-choix-langue">
                        <span className="accueil-choix-nom">{t(`landing.langue_${langue.slug}`, { defaultValue: langue.name })}</span>
                        <div className="accueil-choix-puces">
                            {langue.exams.map((examen) => (
                                <Link
                                    key={examen.slug}
                                    href={versLEssai(examen, essai)}
                                    className="puce-examen"
                                    aria-label={t('landing.choix_aria', { examen: examen.name })}
                                >
                                    {nomCourt(examen.name)}
                                </Link>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
            <p className="accueil-choix-pied">
                {t('landing.choix_note')}{' '}
                <Link href="/essayer" className="accueil-lien whitespace-nowrap">{t('landing.choix_tous')} →</Link>
            </p>
        </div>
    );
}

export function HeroSection({ langues, essai, video }: { langues: Language[]; essai: EssaiLangue[]; video: VideoAccueil | null }) {
    const { t } = useTranslation();
    const { auth } = usePage<SharedData>().props;
    const connecte = !!auth?.user;
    const nombre = langues.reduce((total, langue) => total + (langue.exams?.length ?? 0), 0);

    return (
        <section className="accueil-haut" aria-labelledby="accueil-titre">
            <div className="accueil-haut-grille">
                <div className="accueil-haut-texte">
                    <h1 id="accueil-titre">
                        {t('landing.titre_question')}{' '}
                        <span className="accueil-haut-par-ici">{t('landing.titre_reponse')}</span>
                    </h1>
                    <p className="accueil-haut-sous-titre">{t('landing.sous_titre', { nombre })}</p>
                </div>

                {/* L'essai sans compte, des le premier ecran : un examen, un toucher,
                    un vrai exercice. */}
                {!connecte && (
                    <div className="accueil-haut-choix">
                        <ChoixExamen langues={langues} essai={essai} />
                    </div>
                )}

                {video && (
                    <div className="accueil-haut-video">
                        <LecteurAccueil video={video} />
                    </div>
                )}

                <div className="accueil-haut-actions">
                    {connecte ? (
                        <div className="accueil-haut-boutons">
                            <Link href="/dashboard" className="bouton-essai">
                                {t('landing.reprendre')} →
                            </Link>
                        </div>
                    ) : (
                        <>
                            <div className="accueil-haut-boutons">
                                <Link href="/register" className="bouton-compte">
                                    {t('landing.cta_compte')}
                                </Link>
                            </div>
                            <p className="accueil-haut-reassurance">{t('landing.reassurance')}</p>
                        </>
                    )}
                </div>
            </div>
        </section>
    );
}
