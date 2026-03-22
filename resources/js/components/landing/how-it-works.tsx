import { useEffect, useRef, useState } from 'react';
import { useTokens } from './landing-theme';

const steps = [
    {
        number: '01',
        icon: '◉',
        title: 'Créez votre profil',
        description: 'Inscrivez-vous en 30 secondes, sélectionnez votre examen cible et votre langue. Indiquez votre score visé et la date de votre test.',
        accentKey: 'sky' as const,
    },
    {
        number: '02',
        icon: '⬡',
        title: 'Pratiquez avec l\'IA',
        description: 'Enchaînez les exercices générés sur mesure — compréhension écrite, grammaire, expression, écoute. L\'IA corrige et explique chaque réponse.',
        accentKey: 'gold' as const,
    },
    {
        number: '03',
        icon: '↗',
        title: 'Suivez votre progression',
        description: 'Consultez vos analyses détaillées, identifiez vos points faibles et regardez votre score estimé grimper semaine après semaine.',
        accentKey: 'green' as const,
    },
];

const ACCENT_COLORS = { sky: '#4A90E2', gold: '#F5A623', green: '#22c55e' };

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

function StepCard({ step }: { step: typeof steps[0] }) {
    const T = useTokens();
    const [hovered, setHovered] = useState(false);
    const accent = ACCENT_COLORS[step.accentKey];

    return (
        <div onMouseEnter={() => setHovered(true)} onMouseLeave={() => setHovered(false)}
            style={{
                position: 'relative', borderRadius: '1.25rem',
                border: `1px solid ${hovered ? accent + '44' : T.border}`,
                background: hovered ? T.bgCardHov : T.bgCard,
                padding: '2rem 1.75rem',
                transition: 'all 0.4s cubic-bezier(0.4,0,0.2,1)',
                transform: hovered ? 'translateY(-4px)' : 'translateY(0)',
                boxShadow: hovered ? `0 16px 48px ${accent}1a` : T.theme === 'dark' ? 'none' : '0 4px 16px rgba(26,22,18,0.06)',
                textAlign: 'center',
            }}>
            {/* Big number bg */}
            <div style={{
                position: 'absolute', top: '1rem', right: '1.25rem',
                fontFamily: '"Cormorant Garamond", Georgia, serif',
                fontSize: '5rem', fontWeight: 900, lineHeight: 1,
                color: accent, opacity: 0.07, userSelect: 'none', pointerEvents: 'none',
            }}>
                {step.number}
            </div>

            {/* Icon */}
            <div style={{
                width: 64, height: 64, borderRadius: '50%',
                background: `${accent}14`, border: `1.5px solid ${accent}28`,
                display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.5rem',
                transition: 'all 0.3s ease',
                ...(hovered ? { background: `${accent}22`, border: `1.5px solid ${accent}44` } : {}),
            }}>
                <span style={{ fontSize: '1.6rem', color: accent, lineHeight: 1 }}>{step.icon}</span>
            </div>

            <div style={{
                fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.65rem', fontWeight: 700,
                letterSpacing: '0.14em', textTransform: 'uppercase', color: accent, marginBottom: '0.75rem',
            }}>
                Étape {step.number}
            </div>

            <h3 style={{
                fontFamily: '"Cormorant Garamond", Georgia, serif',
                fontSize: 'clamp(1.25rem, 2vw, 1.5rem)', fontWeight: 700, lineHeight: 1.2,
                color: T.text, marginBottom: '0.875rem',
            }}>
                {step.title}
            </h3>

            <p style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.875rem', lineHeight: 1.7, color: T.textMid }}>
                {step.description}
            </p>
        </div>
    );
}

export function HowItWorks() {
    const T = useTokens();
    const headerRef = useRef<HTMLDivElement>(null);
    const headerVisible = useInView(headerRef as React.RefObject<HTMLElement>);
    const stepsRef = useRef<HTMLDivElement>(null);
    const stepsVisible = useInView(stepsRef as React.RefObject<HTMLElement>, 0.1);

    return (
        <section style={{ position: 'relative', background: T.bgAlt, overflow: 'hidden', padding: 'clamp(5rem,10vw,8rem) 0' }}>
            <svg aria-hidden className="pointer-events-none" style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', opacity: T.gridOpacity }}>
                <defs>
                    <pattern id="hiw-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke={T.gridColor} strokeWidth="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#hiw-grid)" />
            </svg>

            <div style={{ position: 'relative', maxWidth: '72rem', margin: '0 auto', padding: '0 1.5rem' }}>
                <div ref={headerRef} style={{
                    textAlign: 'center', marginBottom: 'clamp(3rem,6vw,5rem)',
                    opacity: headerVisible ? 1 : 0, transform: headerVisible ? 'translateY(0)' : 'translateY(24px)',
                    transition: 'all 0.7s cubic-bezier(0.4,0,0.2,1)',
                }}>
                    <div style={{
                        display: 'inline-flex', alignItems: 'center', gap: '0.5rem', marginBottom: '1.25rem',
                        padding: '0.35rem 1rem', borderRadius: '100px',
                        border: `1px solid ${T.sky}30`, background: `${T.sky}0a`,
                    }}>
                        <span style={{ color: T.sky, fontSize: '0.8rem' }}>→</span>
                        <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.65rem', fontWeight: 700, letterSpacing: '0.14em', textTransform: 'uppercase', color: T.sky }}>
                            Comment ça marche
                        </span>
                    </div>

                    <h2 style={{
                        fontFamily: '"Cormorant Garamond", Georgia, serif',
                        fontSize: 'clamp(2rem, 4vw, 3.25rem)', fontWeight: 700, lineHeight: 1.1,
                        color: T.text, marginBottom: '1rem',
                    }}>
                        Trois étapes vers votre{' '}
                        <span style={{ fontStyle: 'italic', color: T.gold }}>meilleur score</span>
                    </h2>
                    <p style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '1rem', color: T.textMid, maxWidth: '34rem', margin: '0 auto', lineHeight: 1.7 }}>
                        Commencez à pratiquer en moins de 2 minutes. Aucune installation, aucune carte de crédit.
                    </p>
                </div>

                <div ref={stepsRef} style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 260px), 1fr))',
                    gap: '1.5rem', position: 'relative',
                }}>
                    {steps.map((step, i) => (
                        <div key={step.number} style={{
                            opacity: stepsVisible ? 1 : 0,
                            transform: stepsVisible ? 'translateY(0)' : 'translateY(36px)',
                            transition: `all 0.7s cubic-bezier(0.4,0,0.2,1) ${i * 0.15}s`,
                        }}>
                            <StepCard step={step} />
                        </div>
                    ))}
                </div>

                <div style={{ marginTop: 'clamp(3rem,5vw,4rem)', textAlign: 'center', opacity: stepsVisible ? 1 : 0, transition: 'opacity 0.7s ease 0.5s' }}>
                    <p style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.875rem', color: T.textMid }}>
                        Rejoignez des milliers d'apprenants qui préparent leur examen plus intelligemment.
                    </p>
                </div>
            </div>
        </section>
    );
}
