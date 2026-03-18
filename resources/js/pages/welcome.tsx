import { Head } from '@inertiajs/react';
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

export default function Welcome({ languages, pricing }: WelcomeProps) {
    return (
        <>
            <Head title="PrePla - AI-Powered Language Test Preparation" />
            <div className="min-h-screen bg-background text-foreground">
                <LandingNavbar />
                <HeroSection />
                <LanguagesGrid languages={languages} />
                <FeaturesSection />
                <HowItWorks />
                <PricingSection pricing={pricing} />
                <CtaSection />
                <LandingFooter />
            </div>
        </>
    );
}
