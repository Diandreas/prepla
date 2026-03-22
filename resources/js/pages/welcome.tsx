import { Head } from '@inertiajs/react';
import { LandingThemeProvider, useTokens } from '@/components/landing/landing-theme';
import { LandingNavbar } from '@/components/landing/landing-navbar';
import { HeroSection } from '@/components/landing/hero-section';
import { LanguagesGrid } from '@/components/landing/languages-grid';
import { FeaturesSection } from '@/components/landing/features-section';
import { HowItWorks } from '@/components/landing/how-it-works';
import { PricingSection } from '@/components/landing/pricing-section';
import { CtaSection } from '@/components/landing/cta-section';
import { LandingFooter } from '@/components/landing/landing-footer';
import type { Language, PricingPlan } from '@/data/languages';

interface WelcomeProps {
    languages: Language[];
    pricing: PricingPlan[];
}

function WelcomeInner({ languages, pricing }: WelcomeProps) {
    const T = useTokens();

    return (
        <div style={{ minHeight: '100vh', background: T.bg, color: T.text, transition: 'background 0.3s ease, color 0.3s ease' }}>
            <LandingNavbar />
            <HeroSection />
            <LanguagesGrid languages={languages} />
            <FeaturesSection />
            <HowItWorks />
            <PricingSection pricing={pricing} />
            <CtaSection />
            <LandingFooter />
        </div>
    );
}

export default function Welcome({ languages, pricing }: WelcomeProps) {
    return (
        <>
            <Head title="PrePla — Préparez vos examens de langue avec l'IA" />
            <LandingThemeProvider>
                <WelcomeInner languages={languages} pricing={pricing} />
            </LandingThemeProvider>
        </>
    );
}
