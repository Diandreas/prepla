import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const SKY = '#4A90E2';
const GOLD = '#F5A623';
const CREAM = '#F4F7F6';
const DIM = 'rgba(244,247,246,0.6)';

function useInView(ref: React.RefObject<HTMLElement | null>, threshold = 0.2) {
    const [visible, setVisible] = useState(false);
    useEffect(() => {
        if (!ref.current) return;
        const obs = new IntersectionObserver(([e]) => { if (e.isIntersecting) setVisible(true); }, { threshold });
        obs.observe(ref.current);
        return () => obs.disconnect();
    }, [ref, threshold]);
    return visible;
}

export function CtaSection() {
    const ref = useRef<HTMLDivElement>(null);
    const visible = useInView(ref as React.RefObject<HTMLElement>);

    return (
        <section
            style={{
                position: 'relative',
                background: '#0d1221',
                overflow: 'hidden',
                padding: 'clamp(5rem,10vw,8rem) 1.5rem',
            }}
        >
            {/* Grid overlay */}
            <svg aria-hidden className="pointer-events-none" style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', opacity: 0.04 }}>
                <defs>
                    <pattern id="cta-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke={SKY} strokeWidth="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#cta-grid)" />
            </svg>

            {/* Central glow blob */}
            <div style={{
                position: 'absolute',
                top: '50%', left: '50%',
                transform: 'translate(-50%, -50%)',
                width: 600, height: 400,
                background: `radial-gradient(ellipse at center, rgba(74,144,226,0.12) 0%, transparent 70%)`,
                pointerEvents: 'none',
            }} />
            <div style={{
                position: 'absolute',
                top: '50%', left: '50%',
                transform: 'translate(-50%, -50%)',
                width: 400, height: 300,
                background: `radial-gradient(ellipse at center, rgba(245,166,35,0.07) 0%, transparent 70%)`,
                pointerEvents: 'none',
            }} />

            <div
                ref={ref}
                style={{
                    position: 'relative',
                    maxWidth: '52rem',
                    margin: '0 auto',
                    textAlign: 'center',
                    opacity: visible ? 1 : 0,
                    transform: visible ? 'translateY(0)' : 'translateY(32px)',
                    transition: 'all 0.8s cubic-bezier(0.4,0,0.2,1)',
                }}
            >
                {/* Icon */}
                <div
                    style={{
                        width: 72, height: 72,
                        borderRadius: '50%',
                        background: `rgba(74,144,226,0.12)`,
                        border: `1px solid ${SKY}33`,
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        margin: '0 auto 2rem',
                        boxShadow: `0 0 40px rgba(74,144,226,0.2)`,
                    }}
                >
                    <img
                        src="/icons/rocket.png"
                        alt=""
                        width={32} height={32}
                        style={{ filter: 'brightness(0) saturate(100%) invert(53%) sepia(55%) saturate(729%) hue-rotate(193deg) brightness(103%) contrast(91%)' }}
                    />
                </div>

                {/* Eyebrow */}
                <div
                    style={{
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: '0.5rem',
                        marginBottom: '1.5rem',
                        padding: '0.35rem 1rem',
                        borderRadius: '100px',
                        border: `1px solid ${GOLD}33`,
                        background: `${GOLD}0c`,
                    }}
                >
                    <span style={{
                        width: 6, height: 6, borderRadius: '50%',
                        background: GOLD,
                        display: 'inline-block',
                        animation: 'ctaPulse 2s ease-in-out infinite',
                    }} />
                    <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.65rem', fontWeight: 700, letterSpacing: '0.14em', textTransform: 'uppercase', color: GOLD }}>
                        Commencez dès aujourd'hui
                    </span>
                </div>

                {/* Heading */}
                <h2
                    style={{
                        fontFamily: '"Cormorant Garamond", Georgia, serif',
                        fontSize: 'clamp(2.2rem, 5vw, 4rem)',
                        fontWeight: 700,
                        lineHeight: 1.08,
                        color: CREAM,
                        marginBottom: '1.25rem',
                    }}
                >
                    Prêt à décrocher votre{' '}
                    <span style={{ fontStyle: 'italic', color: GOLD }}>meilleur score ?</span>
                </h2>

                {/* Subtext */}
                <p
                    style={{
                        fontFamily: '"Plus Jakarta Sans", sans-serif',
                        fontSize: '1rem',
                        color: DIM,
                        maxWidth: '32rem',
                        margin: '0 auto 2.5rem',
                        lineHeight: 1.7,
                    }}
                >
                    Rejoignez des milliers d'apprenants qui se préparent plus intelligemment. Gratuit pour commencer, sans carte de crédit.
                </p>

                {/* CTA buttons */}
                <div style={{ display: 'flex', flexWrap: 'wrap', justifyContent: 'center', gap: '1rem' }}>
                    <Link
                        href="/register"
                        style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '0.625rem',
                            padding: '1rem 2rem',
                            borderRadius: '100px',
                            background: `linear-gradient(135deg, ${SKY} 0%, #3478c8 100%)`,
                            color: '#fff',
                            fontFamily: '"Plus Jakarta Sans", sans-serif',
                            fontSize: '0.9rem',
                            fontWeight: 600,
                            textDecoration: 'none',
                            boxShadow: '0 4px 24px rgba(74,144,226,0.4)',
                            transition: 'all 0.3s ease',
                        }}
                        onMouseEnter={(e) => {
                            (e.currentTarget as HTMLElement).style.transform = 'translateY(-2px)';
                            (e.currentTarget as HTMLElement).style.boxShadow = '0 8px 40px rgba(74,144,226,0.55)';
                        }}
                        onMouseLeave={(e) => {
                            (e.currentTarget as HTMLElement).style.transform = 'translateY(0)';
                            (e.currentTarget as HTMLElement).style.boxShadow = '0 4px 24px rgba(74,144,226,0.4)';
                        }}
                    >
                        Commencer gratuitement
                        <img
                            src="/icons/arrow-right.png"
                            alt=""
                            width={16} height={16}
                            style={{ filter: 'brightness(0) saturate(100%) invert(100%)' }}
                            className="cta-arrow"
                        />
                    </Link>

                    <Link
                        href="/login"
                        style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '0.5rem',
                            padding: '1rem 2rem',
                            borderRadius: '100px',
                            border: '1px solid rgba(244,247,246,0.15)',
                            background: 'rgba(255,255,255,0.04)',
                            color: DIM,
                            fontFamily: '"Plus Jakarta Sans", sans-serif',
                            fontSize: '0.9rem',
                            fontWeight: 500,
                            textDecoration: 'none',
                            transition: 'all 0.3s ease',
                        }}
                        onMouseEnter={(e) => {
                            (e.currentTarget as HTMLElement).style.borderColor = 'rgba(244,247,246,0.3)';
                            (e.currentTarget as HTMLElement).style.color = CREAM;
                        }}
                        onMouseLeave={(e) => {
                            (e.currentTarget as HTMLElement).style.borderColor = 'rgba(244,247,246,0.15)';
                            (e.currentTarget as HTMLElement).style.color = DIM;
                        }}
                    >
                        Se connecter
                    </Link>
                </div>

                {/* Social proof micro line */}
                <div
                    style={{
                        marginTop: '2.5rem',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '1.5rem',
                        flexWrap: 'wrap',
                    }}
                >
                    {[
                        { icon: 'shield', text: 'Gratuit pour commencer' },
                        { icon: 'check-circle', text: 'Sans engagement' },
                        { icon: 'zap', text: 'Accès immédiat' },
                    ].map((item) => (
                        <div key={item.text} style={{ display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
                            <img
                                src={`/icons/${item.icon}.png`}
                                alt=""
                                width={16} height={16}
                                style={{ filter: 'brightness(0) saturate(100%) invert(61%) sepia(69%) saturate(400%) hue-rotate(92deg) brightness(95%) contrast(92%)', opacity: 0.8 }}
                            />
                            <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.75rem', color: 'rgba(244,247,246,0.4)' }}>
                                {item.text}
                            </span>
                        </div>
                    ))}
                </div>
            </div>

            <style>{`
                @keyframes ctaPulse {
                    0%, 100% { opacity: 1; transform: scale(1); }
                    50% { opacity: 0.5; transform: scale(0.85); }
                }
                .cta-arrow { transition: transform 0.25s ease; }
                a:hover .cta-arrow { transform: translateX(4px); }
            `}</style>
        </section>
    );
}
