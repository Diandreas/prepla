import { useTranslation } from 'react-i18next';
import type { VideoAccueil } from '@/data/languages';
import { useVideoAccueil } from './video-accueil';

/**
 * Ce que raconte la video, en clair, pour qui ne la lance pas — et un bouton
 * pour en voir le passage. Les etapes disent ce que l'application fait
 * vraiment : plus de « inscrivez-vous en 30 secondes » ni de « score qui grimpe ».
 */
const ETAPES = [
    { cle: 'etape_1', depuis: 5, image: 'etape_1' },
    { cle: 'etape_2', depuis: 7.5, image: 'etape_2' },
    { cle: 'etape_3', depuis: 11, image: 'etape_3' },
] as const;

export function HowItWorks({ video }: { video: VideoAccueil | null }) {
    const { t } = useTranslation();
    const { jouer } = useVideoAccueil();

    return (
        <section id="etapes" className="accueil-section" aria-labelledby="etapes-titre" style={{ background: 'var(--fond-alt)' }}>
            <div className="accueil-section-interieur">
                <h2 id="etapes-titre">
                    {t('landing.etapes_titre')} <em>{t('landing.etapes_titre_accent')}</em>
                </h2>
                <p className="accueil-section-chapo">{t('landing.etapes_chapo')}</p>

                <ol className="accueil-grille trois">
                    {ETAPES.map((etape, i) => (
                        <li key={etape.cle} className="accueil-carte overflow-hidden">
                            {video && (
                                <img
                                    src={video.sources[etape.image]}
                                    alt=""
                                    width={400}
                                    height={225}
                                    loading="lazy"
                                    decoding="async"
                                    className="aspect-video w-full object-cover"
                                />
                            )}
                            <div className="p-5">
                                <p className="text-xs font-bold uppercase tracking-widest" style={{ color: 'var(--accent)' }}>
                                    {t('landing.etape_numero', { numero: i + 1 })}
                                </p>
                                <h3 className="mt-1.5 text-lg font-bold" style={{ color: 'var(--texte)' }}>
                                    {t(`landing.${etape.cle}_titre`)}
                                </h3>
                                <p className="mt-2 text-sm leading-relaxed" style={{ color: 'var(--texte-moyen)' }}>
                                    {t(`landing.${etape.cle}_texte`)}
                                </p>
                                {video && (
                                    <button
                                        type="button"
                                        className="accueil-lien mt-3 text-sm font-semibold"
                                        aria-label={t('landing.etape_voir_aria', { secondes: etape.depuis })}
                                        onClick={() => jouer(etape.depuis)}
                                    >
                                        ▶ {t('landing.etape_voir')}
                                    </button>
                                )}
                            </div>
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}
