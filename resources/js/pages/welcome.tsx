import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { CentresSection } from '@/components/landing/centres-section';
import { CtaSection } from '@/components/landing/cta-section';
import { EssaiSection } from '@/components/landing/essai-section';
import { FeaturesSection } from '@/components/landing/features-section';
import { HeroSection } from '@/components/landing/hero-section';
import { HowItWorks } from '@/components/landing/how-it-works';
import { LandingFooter } from '@/components/landing/landing-footer';
import { LandingNavbar } from '@/components/landing/landing-navbar';
import { LandingThemeProvider, useTokens } from '@/components/landing/landing-theme';
import { PricingSection } from '@/components/landing/pricing-section';
import { VideoAccueilProvider } from '@/components/landing/video-accueil';
import type { EssaiLangue, Language, VideoAccueil } from '@/data/languages';

interface WelcomeProps {
    languages: Language[];
    video: VideoAccueil | null;
    essai: EssaiLangue[];
}

/**
 * La page d'accueil : la question de la video, l'essai sans compte, les etapes,
 * les epreuves, les vrais prix et l'offre aux centres. Chaque chiffre est
 * calcule — le nombre d'examens vient de la configuration, les prix de Stripe.
 */
function WelcomeInner({ languages, video, essai }: WelcomeProps) {
    const T = useTokens();
    const { t } = useTranslation();
    const examens = languages.flatMap((langue) => langue.exams ?? []);

    // Les couleurs du theme en variables : les sections les lisent en CSS, ce qui
    // permet leur mise en page selon la largeur d'ecran.
    const variables = {
        '--fond': T.bg,
        '--fond-alt': T.bgAlt,
        '--texte': T.text,
        '--texte-moyen': T.textMid,
        '--texte-doux': T.textDim,
        '--bordure': T.border,
        '--carte': T.bgCard,
        '--carte-forte': T.dark ? 'rgba(255,255,255,0.08)' : 'rgba(19,35,63,0.07)',
        '--ciel': T.sky,
        '--essai': T.essai,
        '--sur-essai': T.surEssai,
        '--accent': T.accent,
        '--lien': T.lien,
        '--officiel': T.dark ? '#4ade80' : '#15803d',
    } as React.CSSProperties;

    return (
        <div
            className="accueil overflow-x-hidden"
            style={{ ...variables, minHeight: '100vh', background: T.bg, color: T.text, fontFamily: '"Plus Jakarta Sans", system-ui, sans-serif', transition: 'background 0.3s ease, color 0.3s ease' }}
        >
            <a href="#contenu" className="accueil-aller-au-contenu">{t('landing.aller_au_contenu')}</a>
            <LandingNavbar />
            <main id="contenu">
                <HeroSection langues={languages} essai={essai} video={video} />
                <HowItWorks video={video} />
                <EssaiSection langues={languages} essai={essai} />
                <FeaturesSection />
                <PricingSection />
                <CentresSection />
                <CtaSection avecVideo={!!video} />
            </main>
            <LandingFooter nombreDExamens={examens.length} />
        </div>
    );
}

export default function Welcome(props: WelcomeProps) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('landing.titre_onglet')} />
            <LandingThemeProvider>
                <VideoAccueilProvider>
                    <WelcomeInner {...props} />
                </VideoAccueilProvider>
            </LandingThemeProvider>
        </>
    );
}
