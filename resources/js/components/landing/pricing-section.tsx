import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { PricingPlan } from '@/data/languages';

const INK = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';
const CREAM = '#F4F7F6';
const DIM = 'rgba(244,247,246,0.55)';

interface PricingSectionProps {
    pricing: PricingPlan[];
}

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
    const ref = useRef<HTMLDivElement>(null);
    const visible = useInView(ref as React.RefObject<HTMLElement>);
    const [hovered, setHovered] = useState(false);

    const isHighlighted = plan.highlighted;
    const accent = isHighlighted ? SKY : GOLD;

    return (
        <div
            ref={ref}
            onMouseEnter={() => setHovered(true)}
            onMouseLeave={() => setHovered(false)}
            style={{
                position: 'relative',
                borderRadius: '1.5rem',
                border: `1px solid ${isHighlighted
                    ? SKY + '55'
                    : hovered ? 'rgba(255,255,255,0.12)' : 'rgba(255,255,255,0.07)'}`,
                background: isHighlighted
                    ? `linear-gradient(145deg, rgba(74,144,226,0.12) 0%, rgba(74,144,226,0.04) 100%)`
                    : hovered
                        ? 'rgba(255,255,255,0.05)'
                        : 'rgba(255,255,255,0.025)',
                padding: '2rem 1.75rem',
                transition: 'all 0.4s cubic-bezier(0.4,0,0.2,1)',
                transform: visible
                    ? (isHighlighted ? 'scale(1.03)' : hovered ? 'translateY(-4px)' : 'translateY(0)')
                    : 'translateY(32px)',
                opacity: visible ? 1 : 0,
                transitionDelay: `${index * 0.12}s`,
                boxShadow: isHighlighted
                    ? `0 20px 60px rgba(74,144,226,0.2)`
                    : hovered ? '0 12px 40px rgba(0,0,0,0.3)' : '0 4px 20px rgba(0,0,0,0.2)',
            }}
        >
            {/* "Populaire" badge */}
            {isHighlighted && (
                <div
                    style={{
                        position: 'absolute',
                        top: '-14px',
                        left: '50%',
                        transform: 'translateX(-50%)',
                        background: `linear-gradient(135deg, ${SKY} 0%, #3478c8 100%)`,
                        color: '#fff',
                        fontSize: '0.65rem',
                        fontWeight: 700,
                        letterSpacing: '0.1em',
                        textTransform: 'uppercase',
                        padding: '0.3rem 1rem',
                        borderRadius: '100px',
                        fontFamily: '"Plus Jakarta Sans", sans-serif',
                        whiteSpace: 'nowrap',
                        boxShadow: '0 4px 16px rgba(74,144,226,0.4)',
                    }}
                >
                    Le plus populaire
                </div>
            )}

            {/* Plan name + price */}
            <div style={{ marginBottom: '1.75rem' }}>
                <h3
                    style={{
                        fontFamily: '"Plus Jakarta Sans", sans-serif',
                        fontSize: '0.75rem',
                        fontWeight: 700,
                        letterSpacing: '0.12em',
                        textTransform: 'uppercase',
                        color: accent,
                        marginBottom: '1rem',
                    }}
                >
                    {plan.name}
                </h3>

                <div style={{ display: 'flex', alignItems: 'baseline', gap: '0.25rem' }}>
                    <span
                        style={{
                            fontFamily: '"Cormorant Garamond", Georgia, serif',
                            fontSize: 'clamp(2.5rem, 4vw, 3.5rem)',
                            fontWeight: 700,
                            color: CREAM,
                            lineHeight: 1,
                        }}
                    >
                        {plan.price === 0 ? 'Gratuit' : `${plan.price}€`}
                    </span>
                    {plan.period && (
                        <span
                            style={{
                                fontFamily: '"Plus Jakarta Sans", sans-serif',
                                fontSize: '0.8rem',
                                color: DIM,
                                marginLeft: '0.25rem',
                            }}
                        >
                            /{plan.period}
                        </span>
                    )}
                </div>
            </div>

            {/* Divider */}
            <div style={{
                height: '1px',
                background: `linear-gradient(90deg, transparent, ${accent}33, transparent)`,
                marginBottom: '1.5rem',
            }} />

            {/* Features */}
            <ul style={{ listStyle: 'none', padding: 0, margin: '0 0 2rem', display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                {plan.features.map((feature) => (
                    <li
                        key={feature}
                        style={{ display: 'flex', alignItems: 'flex-start', gap: '0.75rem' }}
                    >
                        <div
                            style={{
                                flexShrink: 0,
                                width: 20, height: 20,
                                borderRadius: '50%',
                                background: isHighlighted ? `${SKY}20` : `${GOLD}14`,
                                border: `1px solid ${isHighlighted ? SKY + '44' : GOLD + '33'}`,
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                marginTop: '0.05rem',
                            }}
                        >
                            <img
                                src="/icons/check.png"
                                alt=""
                                width={14} height={14}
                                style={{
                                    filter: isHighlighted
                                        ? 'brightness(0) saturate(100%) invert(53%) sepia(55%) saturate(729%) hue-rotate(193deg) brightness(103%) contrast(91%)'
                                        : 'brightness(0) saturate(100%) invert(71%) sepia(55%) saturate(855%) hue-rotate(1deg)',
                                }}
                            />
                        </div>
                        <span
                            style={{
                                fontFamily: '"Plus Jakarta Sans", sans-serif',
                                fontSize: '0.875rem',
                                color: DIM,
                                lineHeight: 1.5,
                            }}
                        >
                            {feature}
                        </span>
                    </li>
                ))}
            </ul>

            {/* CTA Button */}
            <Link
                href="/register"
                style={{
                    display: 'block',
                    textAlign: 'center',
                    padding: '0.875rem 1.5rem',
                    borderRadius: '100px',
                    fontFamily: '"Plus Jakarta Sans", sans-serif',
                    fontSize: '0.875rem',
                    fontWeight: 600,
                    textDecoration: 'none',
                    transition: 'all 0.3s ease',
                    ...(isHighlighted
                        ? {
                            background: `linear-gradient(135deg, ${SKY} 0%, #3478c8 100%)`,
                            color: '#fff',
                            boxShadow: '0 4px 20px rgba(74,144,226,0.4)',
                        }
                        : {
                            background: 'rgba(255,255,255,0.06)',
                            border: '1px solid rgba(255,255,255,0.12)',
                            color: CREAM,
                        }),
                }}
                onMouseEnter={(e) => {
                    if (!isHighlighted) {
                        (e.currentTarget as HTMLElement).style.background = 'rgba(255,255,255,0.1)';
                        (e.currentTarget as HTMLElement).style.borderColor = 'rgba(255,255,255,0.2)';
                    }
                }}
                onMouseLeave={(e) => {
                    if (!isHighlighted) {
                        (e.currentTarget as HTMLElement).style.background = 'rgba(255,255,255,0.06)';
                        (e.currentTarget as HTMLElement).style.borderColor = 'rgba(255,255,255,0.12)';
                    }
                }}
            >
                {plan.cta}
            </Link>
        </div>
    );
}

export function PricingSection({ pricing }: PricingSectionProps) {
    const headerRef = useRef<HTMLDivElement>(null);
    const headerVisible = useInView(headerRef as React.RefObject<HTMLElement>);

    return (
        <section
            id="pricing"
            style={{
                position: 'relative',
                background: INK,
                overflow: 'hidden',
                padding: 'clamp(5rem,10vw,8rem) 0',
            }}
        >
            {/* Grid overlay */}
            <svg aria-hidden className="pointer-events-none" style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', opacity: 0.04 }}>
                <defs>
                    <pattern id="price-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke={SKY} strokeWidth="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#price-grid)" />
            </svg>

            {/* Ambient glows */}
            <div style={{ position: 'absolute', inset: 0, pointerEvents: 'none' }}>
                <div style={{ position: 'absolute', top: 0, left: '30%', width: 500, height: 500, borderRadius: '50%', background: 'rgba(74,144,226,0.07)', filter: 'blur(100px)' }} />
                <div style={{ position: 'absolute', bottom: 0, right: '20%', width: 380, height: 380, borderRadius: '50%', background: 'rgba(245,166,35,0.05)', filter: 'blur(80px)' }} />
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
                        <img src="/icons/credit-card.png" alt="" width={16} height={16}
                            style={{ filter: 'brightness(0) saturate(100%) invert(53%) sepia(55%) saturate(729%) hue-rotate(193deg) brightness(103%) contrast(91%)' }} />
                        <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.65rem', fontWeight: 700, letterSpacing: '0.14em', textTransform: 'uppercase', color: SKY }}>
                            Tarifs
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
                        Tarification{' '}
                        <span style={{ fontStyle: 'italic', color: GOLD }}>simple et transparente</span>
                    </h2>
                    <p
                        style={{
                            fontFamily: '"Plus Jakarta Sans", sans-serif',
                            fontSize: '1rem',
                            color: DIM,
                            maxWidth: '32rem',
                            margin: '0 auto',
                            lineHeight: 1.7,
                        }}
                    >
                        Commencez gratuitement, évoluez quand vous êtes prêt. Aucun engagement, aucune surprise.
                    </p>
                </div>

                {/* Plans grid */}
                <div
                    style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 280px), 1fr))',
                        gap: '1.5rem',
                        alignItems: 'center',
                    }}
                >
                    {pricing.map((plan, i) => (
                        <PlanCard key={plan.slug} plan={plan} index={i} />
                    ))}
                </div>

                {/* Trust note */}
                <p
                    style={{
                        textAlign: 'center',
                        marginTop: '2.5rem',
                        fontFamily: '"Plus Jakarta Sans", sans-serif',
                        fontSize: '0.8rem',
                        color: 'rgba(244,247,246,0.3)',
                    }}
                >
                    Paiement sécurisé · Résiliation à tout moment · Support inclus
                </p>
            </div>
        </section>
    );
}
