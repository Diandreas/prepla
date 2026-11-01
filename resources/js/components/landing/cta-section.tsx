import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { SharedData } from '@/types';
import { useVideoAccueil } from './video-accueil';

/**
 * Le dernier appel : un geste simple pour qui arrive en bas de la page. Plus de
 * « Rejoignez des milliers d'apprenants » : rien ne l'etablissait.
 */
export function CtaSection({ avecVideo }: { avecVideo: boolean }) {
    const { t } = useTranslation();
    const { auth } = usePage<SharedData>().props;
    const { jouer } = useVideoAccueil();

    return (
        <section className="accueil-section" aria-labelledby="dernier-appel-titre" style={{ background: 'var(--fond-alt)' }}>
            <div className="accueil-section-interieur text-center">
                <h2 id="dernier-appel-titre">
                    {t('landing.dernier_titre')} <em>{t('landing.dernier_titre_accent')}</em>
                </h2>
                <p className="accueil-section-chapo">{t('landing.dernier_chapo')}</p>

                <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    {auth?.user ? (
                        <Link href="/dashboard" className="bouton-essai">{t('landing.reprendre')} →</Link>
                    ) : (
                        <>
                            <Link href="/essayer" className="bouton-essai">{t('landing.cta_essayer')} →</Link>
                            <Link href="/register" className="bouton-compte">{t('landing.cta_compte')}</Link>
                        </>
                    )}
                </div>

                {avecVideo && (
                    <button type="button" className="accueil-lien mt-5 text-sm" onClick={() => jouer(0)}>
                        ↺ {t('landing.dernier_revoir')}
                    </button>
                )}
            </div>
        </section>
    );
}
