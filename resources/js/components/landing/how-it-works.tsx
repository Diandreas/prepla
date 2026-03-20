import { useEffect, useRef, useState } from 'react';

const INK = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';
const CREAM = '#F4F7F6';
const DIM = 'rgba(244,247,246,0.55)';

const steps = [
    {
        number: '01',
        icon: 'user',
        title: 'Créez votre profil',
        description: 'Inscrivez-vous en 30 secondes, sélectionnez votre examen cible et votre langue. Indiquez votre score visé et la date de votre test.',
        accent: SKY,
    },
    {
        number: '02',
        icon: 'puzzle',
        title: 'Pratiquez avec l\'IA',
        description: 'Enchaînez les exercices générés sur mesure — compréhension écrite, grammaire, expression, écoute. L\'IA corrige et explique chaque réponse.',
        accent: GOLD,
    },
    {
        number: '03',
        icon: 'trending-up',
        title: 'Suivez votre progression',
        description: 'Consultez vos analyses détaillées, identifiez vos points faibles et regardez votre score estimé grimper semaine après semaine.',
        accent: '#22c55e',
    },
];

function useInView(ref: React.RefObject<HTMLElement | null>, threshold = 0.15) {
    const [visible, setVisible] = useState(false);
    useEffect(() => {
        if (!ref.current) return;
        const obs = new IntersectionObserver(([e]) => { if (e.isIntersecting) setVisible(true); }, { threshold });
        obs.observe(ref.current);
        return () => obs.disconnect();
    }, [ref, threshold]);
    return visible;
}

function cssColorFilter(hex: string): string {
    const map: Record<string, string> = {
        [SKY]:    'invert(53%) sepia(55%) saturate(729%) hue-rotate(193deg) brightness(103%) contrast(91%)',
        [GOLD]:   'invert(71%) sepia(55%) saturate(855%) hue-rotate(1deg) brightness(100%) contrast(97%)',
        '#22c55e':'invert(61%) sepia(69%) saturate(400%) hue-rotate(92deg) brightness(95%) contrast(92%)',
    };
    return map[hex] ?? 'invert(100%)';
}

export function HowItWorks() {
    const headerRef = useRef<HTMLDivElement>(null);
    const headerVisible = useInView(headerRef as React.RefObject<HTMLElement>);
    const stepsRef = useRef<HTMLDivElement>(null);
    const stepsVisible = useInView(stepsRef as React.RefObject<HTMLElement>, 0.1);

    return (
        <section
            style={{
                position: 'relative',
                background: INK,
                overflow: 'hidden',
                padding: 'clamp(5rem,10vw,8rem) 0',
            }}
        >
            {/* Subtle grid */}
            <svg aria-hidden className="pointer-events-none" style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', opacity: 0.05 }}>
                <defs>
                    <pattern id="hiw-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke={SKY} strokeWidth="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#hiw-grid)" />
            </svg>

            {/* Ambient */}
            <div style={{ position: 'absolute', inset: 0, pointerEvents: 'none' }}>
                <div style={{ position: 'absolute', top: '20%', right: '5%', width: 360, height: 360, borderRadius: '50%', background: 'rgba(245,166,35,0.06)', filter: 'blur(80px)' }} />
                <div style={{ position: 'absolute', bottom: '10%', left: '5%', width: 320, height: 320, borderRadius: '50%', background: 'rgba(74,144,226,0.07)', filter: 'blur(80px)' }} />
            </div>

            <div style={{ position: 'relative', maxWidth: '72rem', margin: '0 auto', padding: '0 1.5rem' }}>
                {/* Header */}
                <div
                    ref={headerRef}
                    style={{
                        textAlign: 'center',
                        marginBottom: 'clamp(3rem,6vw,5rem)',
                        opacity: headerVisible ? 1 : 0,
                        transform: headerVisible ? 'translateY(0)' : 'translateY(24px)',
                        transition: 'all 0.7s cubic-bezier(0.4,0,0.2,1)',
                    }}
                >
                    <div
                        style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '0.5rem',
                            marginBottom: '1.25rem',
                            padding: '0.35rem 1rem',
                            borderRadius: '100px',
                            border: `1px solid ${SKY}33`,
                            background: `${SKY}0c`,
                        }}
                    >
                        <img src="/icons/rocket.png" alt="" width={16} height={16}
                            style={{ filter: 'brightness(0) saturate(100%) invert(53%) sepia(55%) saturate(729%) hue-rotate(193deg) brightness(103%) contrast(91%)' }} />
                        <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.65rem', fontWeight: 700, letterSpacing: '0.14em', textTransform: 'uppercase', color: SKY }}>
                            Comment ça marche
                        </span>
                    </div>

                    <h2
                        style={{
                            fontFamily: '"Cormorant Garamond", Georgia, serif',
                            fontSize: 'clamp(2rem, 4vw, 3.25rem)',
                            fontWeight: 700,
                            lineHeight: 1.1,
                            color: CREAM,
                            marginBottom: '1rem',
                        }}
                    >
                        Trois étapes vers votre{' '}
                        <span style={{ fontStyle: 'italic', color: GOLD }}>meilleur score</span>
                    </h2>
                    <p
                        style={{
                            fontFamily: '"Plus Jakarta Sans", sans-serif',
                            fontSize: '1rem',
                            color: DIM,
                            maxWidth: '34rem',
                            margin: '0 auto',
                            lineHeight: 1.7,
                        }}
                    >
                        Commencez à pratiquer en moins de 2 minutes. Aucune installation, aucune carte de crédit.
                    </p>
                </div>

                {/* Steps */}
                <div
                    ref={stepsRef}
                    style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 260px), 1fr))',
                        gap: '1.5rem',
                        position: 'relative',
                    }}
                >
                    {/* Connector line — desktop only */}
                    <div
                        style={{
                            position: 'absolute',
                            top: '2.75rem',
                            left: '16.5%',
                            right: '16.5%',
                            height: '1px',
                            background: `linear-gradient(90deg, ${SKY}44, ${GOLD}44, #22c55e44)`,
                            display: 'none',
                            // shown via CSS in media query equivalent below
                        }}
                        className="hiw-connector"
                    />

                    {steps.map((step, i) => (
                        <div
                            key={step.number}
                            style={{
                                opacity: stepsVisible ? 1 : 0,
                                transform: stepsVisible ? 'translateY(0)' : 'translateY(36px)',
                                transition: `all 0.7s cubic-bezier(0.4,0,0.2,1) ${i * 0.15}s`,
                            }}
                        >
                            <StepCard step={step} />
                        </div>
                    ))}
                </div>

                {/* Bottom CTA hint */}
                <div
                    style={{
                        marginTop: 'clamp(3rem,5vw,4rem)',
                        textAlign: 'center',
                        opacity: stepsVisible ? 1 : 0,
                        transition: 'opacity 0.7s ease 0.5s',
                    }}
                >
                    <p style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.875rem', color: DIM }}>
                        Rejoignez des milliers d'apprenants qui préparent leur examen plus intelligemment.
                    </p>
                </div>
            </div>

            <style>{`
                @media (min-width: 768px) {
                    .hiw-connector { display: block !important; }
                }
            `}</style>
        </section>
    );
}

function StepCard({ step }: { step: typeof steps[0] }) {
    const [hovered, setHovered] = useState(false);

    return (
        <div
            onMouseEnter={() => setHovered(true)}
            onMouseLeave={() => setHovered(false)}
            style={{
                position: 'relative',
                borderRadius: '1.25rem',
                border: `1px solid ${hovered ? step.accent + '44' : 'rgba(255,255,255,0.07)'}`,
                background: hovered
                    ? 'rgba(255,255,255,0.05)'
                    : 'rgba(255,255,255,0.025)',
                padding: '2rem 1.75rem',
                transition: 'all 0.4s cubic-bezier(0.4,0,0.2,1)',
                transform: hovered ? 'translateY(-4px)' : 'translateY(0)',
                boxShadow: hovered ? `0 16px 48px ${step.accent}22` : 'none',
                textAlign: 'center',
            }}
        >
            {/* Large step number — background */}
            <div
                style={{
                    position: 'absolute',
                    top: '1rem',
                    right: '1.25rem',
                    fontFamily: '"Cormorant Garamond", Georgia, serif',
                    fontSize: '5rem',
                    fontWeight: 900,
                    lineHeight: 1,
                    color: step.accent,
                    opacity: 0.06,
                    userSelect: 'none',
                    pointerEvents: 'none',
                }}
            >
                {step.number}
            </div>

            {/* Icon circle */}
            <div
                style={{
                    width: 64, height: 64,
                    borderRadius: '50%',
                    background: step.accent + '14',
                    border: `1.5px solid ${step.accent}33`,
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    margin: '0 auto 1.5rem',
                    transition: 'all 0.3s ease',
                    ...(hovered ? { background: step.accent + '24', border: `1.5px solid ${step.accent}55` } : {}),
                }}
            >
                <img
                    src={`/icons/${step.icon}.png`}
                    alt=""
                    width={28} height={28}
                    style={{
                        filter: `brightness(0) saturate(100%) ${cssColorFilter(step.accent)}`,
                        objectFit: 'contain',
                    }}
                />
            </div>

            {/* Step label */}
            <div
                style={{
                    fontFamily: '"Plus Jakarta Sans", sans-serif',
                    fontSize: '0.65rem',
                    fontWeight: 700,
                    letterSpacing: '0.14em',
                    textTransform: 'uppercase',
                    color: step.accent,
                    marginBottom: '0.75rem',
                }}
            >
                Étape {step.number}
            </div>

            {/* Title */}
            <h3
                style={{
                    fontFamily: '"Cormorant Garamond", Georgia, serif',
                    fontSize: 'clamp(1.25rem, 2vw, 1.5rem)',
                    fontWeight: 700,
                    lineHeight: 1.2,
                    color: CREAM,
                    marginBottom: '0.875rem',
                }}
            >
                {step.title}
            </h3>

            {/* Description */}
            <p
                style={{
                    fontFamily: '"Plus Jakarta Sans", sans-serif',
                    fontSize: '0.875rem',
                    lineHeight: 1.7,
                    color: DIM,
                }}
            >
                {step.description}
            </p>
        </div>
    );
}
