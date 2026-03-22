import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { PricingPlan } from '@/data/languages';
import { useTokens } from './landing-theme';

function useInView(ref: React.RefObject<HTMLElement | null>, threshold = 0.1) {
    const [visible, setVisible] = useState(false);
    useEffect(() => {
        if (!ref.current) return;
        const obs = new IntersectionObserver(([e]) => { if (e.isIntersecting) setVisible(true); }, { threshold });
        obs.observe(ref.current);
        return () => obs.disconnect();
    }, [ref, threshold]);
    return visible;
}

function PlanCard({ plan, index }: { plan: PricingPlan; index: number }) {
    const T = useTokens();
    const ref = useRef<HTMLDivElement>(null);
    const visible = useInView(ref as React.RefObject<HTMLElement>);
    const [hovered, setHovered] = useState(false);
    const isHighlighted = plan.highlighted;
    const accent = isHighlighted ? T.sky : T.gold;

    return (
        <div ref={ref} onMouseEnter={() => setHovered(true)} onMouseLeave={() => setHovered(false)}
            style={{
                position: 'relative', borderRadius: '1.5rem',
                border: `1px solid ${isHighlighted ? T.sky + '55' : hovered ? T.borderHov : T.border}`,
                background: isHighlighted
                    ? T.theme === 'dark'
                        ? `linear-gradient(145deg, rgba(74,144,226,0.14) 0%, rgba(74,144,226,0.05) 100%)`
                        : `linear-gradient(145deg, rgba(74,144,226,0.08) 0%, rgba(74,144,226,0.02) 100%)`
                    : hovered ? T.bgCardHov : T.bgCard,
                padding: '2rem 1.75rem',
                transition: 'all 0.4s cubic-bezier(0.4,0,0.2,1)',
                transform: visible
                    ? isHighlighted ? 'scale(1.03)' : hovered ? 'translateY(-4px)' : 'translateY(0)'
                    : 'translateY(32px)',
                opacity: visible ? 1 : 0,
                transitionDelay: `${index * 0.12}s`,
                boxShadow: isHighlighted
                    ? `0 20px 60px ${T.sky}22`
                    : hovered
                        ? `0 12px 40px ${T.theme === 'dark' ? 'rgba(0,0,0,0.3)' : 'rgba(26,22,18,0.1)'}`
                        : T.theme === 'light' ? '0 4px 16px rgba(26,22,18,0.06)' : 'none',
            }}>
            {isHighlighted && (
                <div style={{
                    position: 'absolute', top: '-14px', left: '50%', transform: 'translateX(-50%)',
                    background: `linear-gradient(135deg, ${T.sky} 0%, #3478c8 100%)`,
                    color: '#fff', fontSize: '0.65rem', fontWeight: 700,
                    letterSpacing: '0.1em', textTransform: 'uppercase',
                    padding: '0.3rem 1rem', borderRadius: '100px',
                    fontFamily: '"Plus Jakarta Sans", sans-serif',
                    whiteSpace: 'nowrap', boxShadow: `0 4px 16px ${T.sky}44`,
                }}>
                    Le plus populaire
                </div>
            )}

            <div style={{ marginBottom: '1.75rem' }}>
                <h3 style={{
                    fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.75rem', fontWeight: 700,
                    letterSpacing: '0.12em', textTransform: 'uppercase', color: accent, marginBottom: '1rem',
                }}>
                    {plan.name}
                </h3>
                <div style={{ display: 'flex', alignItems: 'baseline', gap: '0.25rem' }}>
                    <span style={{
                        fontFamily: '"Cormorant Garamond", Georgia, serif',
                        fontSize: 'clamp(2.5rem, 4vw, 3.5rem)', fontWeight: 700, color: T.text, lineHeight: 1,
                    }}>
                        {plan.price === 0 ? 'Gratuit' : `${plan.price}€`}
                    </span>
                    {plan.period && (
                        <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.8rem', color: T.textMid, marginLeft: '0.25rem' }}>
                            /{plan.period}
                        </span>
                    )}
                </div>
            </div>

            <div style={{ height: '1px', background: `linear-gradient(90deg, transparent, ${accent}33, transparent)`, marginBottom: '1.5rem' }} />

            <ul style={{ listStyle: 'none', padding: 0, margin: '0 0 2rem', display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                {plan.features.map((feature) => (
                    <li key={feature} style={{ display: 'flex', alignItems: 'flex-start', gap: '0.75rem' }}>
                        <div style={{
                            flexShrink: 0, width: 20, height: 20, borderRadius: '50%',
                            background: isHighlighted ? `${T.sky}18` : `${T.gold}12`,
                            border: `1px solid ${isHighlighted ? T.sky + '33' : T.gold + '28'}`,
                            display: 'flex', alignItems: 'center', justifyContent: 'center', marginTop: '0.05rem',
                        }}>
                            <svg width="10" height="10" viewBox="0 0 12 12" fill="none">
                                <path d="M2 6l3 3 5-5" stroke={isHighlighted ? T.sky : T.gold} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                        </div>
                        <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.875rem', color: T.textMid, lineHeight: 1.5 }}>
                            {feature}
                        </span>
                    </li>
                ))}
            </ul>

            <Link href="/register" style={{
                display: 'block', textAlign: 'center', padding: '0.875rem 1.5rem', borderRadius: '100px',
                fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.875rem', fontWeight: 600,
                textDecoration: 'none', transition: 'all 0.3s ease',
                ...(isHighlighted
                    ? { background: `linear-gradient(135deg, ${T.sky} 0%, #3478c8 100%)`, color: '#fff', boxShadow: `0 4px 20px ${T.sky}44` }
                    : { background: T.bgCard, border: `1px solid ${T.border}`, color: T.text }),
            }}
                onMouseEnter={(e) => { if (!isHighlighted) { (e.currentTarget as HTMLElement).style.borderColor = T.borderHov; } }}
                onMouseLeave={(e) => { if (!isHighlighted) { (e.currentTarget as HTMLElement).style.borderColor = T.border; } }}>
                {plan.cta}
            </Link>
        </div>
    );
}

export function PricingSection({ pricing }: { pricing: PricingPlan[] }) {
    const T = useTokens();
    const headerRef = useRef<HTMLDivElement>(null);
    const headerVisible = useInView(headerRef as React.RefObject<HTMLElement>);

    return (
        <section id="pricing" style={{ position: 'relative', background: T.bg, overflow: 'hidden', padding: 'clamp(5rem,10vw,8rem) 0' }}>
            <svg aria-hidden className="pointer-events-none" style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', opacity: T.gridOpacity }}>
                <defs>
                    <pattern id="price-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke={T.gridColor} strokeWidth="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#price-grid)" />
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
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke={T.sky} strokeWidth="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.65rem', fontWeight: 700, letterSpacing: '0.14em', textTransform: 'uppercase', color: T.sky }}>
                            Tarifs
                        </span>
                    </div>
                    <h2 style={{
                        fontFamily: '"Cormorant Garamond", Georgia, serif',
                        fontSize: 'clamp(2rem, 4vw, 3.25rem)', fontWeight: 700, lineHeight: 1.1,
                        color: T.text, marginBottom: '1rem',
                    }}>
                        Tarification{' '}
                        <span style={{ fontStyle: 'italic', color: T.gold }}>simple et transparente</span>
                    </h2>
                    <p style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '1rem', color: T.textMid, maxWidth: '32rem', margin: '0 auto', lineHeight: 1.7 }}>
                        Commencez gratuitement, évoluez quand vous êtes prêt. Aucun engagement, aucune surprise.
                    </p>
                </div>

                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 280px), 1fr))', gap: '1.5rem', alignItems: 'center' }}>
                    {pricing.map((plan, i) => <PlanCard key={plan.slug} plan={plan} index={i} />)}
                </div>

                <p style={{ textAlign: 'center', marginTop: '2.5rem', fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.8rem', color: T.textDim }}>
                    Paiement sécurisé · Résiliation à tout moment · Support inclus
                </p>
            </div>
        </section>
    );
}
