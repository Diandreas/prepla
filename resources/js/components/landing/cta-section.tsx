import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useTokens } from './landing-theme';

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
    const T = useTokens();
    const ref = useRef<HTMLDivElement>(null);
    const visible = useInView(ref as React.RefObject<HTMLElement>);

    return (
        <section style={{ position: 'relative', background: T.bgAlt, overflow: 'hidden', padding: 'clamp(5rem,10vw,8rem) 1.5rem' }}>
            <svg aria-hidden className="pointer-events-none" style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', opacity: T.gridOpacity }}>
                <defs>
                    <pattern id="cta-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke={T.gridColor} strokeWidth="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#cta-grid)" />
            </svg>

            {/* Glows */}
            <div style={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%, -50%)', width: 600, height: 400, background: `radial-gradient(ellipse at center, ${T.sky}14 0%, transparent 70%)`, pointerEvents: 'none' }} />
            <div style={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%, -50%)', width: 400, height: 300, background: `radial-gradient(ellipse at center, ${T.gold}0d 0%, transparent 70%)`, pointerEvents: 'none' }} />

            <div ref={ref} style={{
                position: 'relative', maxWidth: '52rem', margin: '0 auto', textAlign: 'center',
                opacity: visible ? 1 : 0, transform: visible ? 'translateY(0)' : 'translateY(32px)',
                transition: 'all 0.8s cubic-bezier(0.4,0,0.2,1)',
            }}>
                {/* Icon */}
                <div style={{
                    width: 72, height: 72, borderRadius: '50%',
                    background: `${T.sky}14`, border: `1px solid ${T.sky}30`,
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    margin: '0 auto 2rem', boxShadow: `0 0 40px ${T.sky}22`,
                }}>
                    <span style={{ fontSize: '1.75rem', color: T.sky, lineHeight: 1 }}>→</span>
                </div>

                {/* Eyebrow */}
                <div style={{
                    display: 'inline-flex', alignItems: 'center', gap: '0.5rem', marginBottom: '1.5rem',
                    padding: '0.35rem 1rem', borderRadius: '100px',
                    border: `1px solid ${T.gold}30`, background: `${T.gold}0a`,
                }}>
                    <span style={{ width: 6, height: 6, borderRadius: '50%', background: T.gold, display: 'inline-block', animation: 'ctaPulse 2s ease-in-out infinite' }} />
                    <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.65rem', fontWeight: 700, letterSpacing: '0.14em', textTransform: 'uppercase', color: T.gold }}>
                        Commencez dès aujourd'hui
                    </span>
                </div>

                <h2 style={{
                    fontFamily: '"Cormorant Garamond", Georgia, serif',
                    fontSize: 'clamp(2.2rem, 5vw, 4rem)', fontWeight: 700, lineHeight: 1.08,
                    color: T.text, marginBottom: '1.25rem',
                }}>
                    Prêt à décrocher votre{' '}
                    <span style={{ fontStyle: 'italic', color: T.gold }}>meilleur score ?</span>
                </h2>

                <p style={{
                    fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '1rem', color: T.textMid,
                    maxWidth: '32rem', margin: '0 auto 2.5rem', lineHeight: 1.7,
                }}>
                    Rejoignez des milliers d'apprenants qui se préparent plus intelligemment. Gratuit pour commencer, sans carte de crédit.
                </p>

                <div style={{ display: 'flex', flexWrap: 'wrap', justifyContent: 'center', gap: '1rem' }}>
                    <Link href="/register" style={{
                        display: 'inline-flex', alignItems: 'center', gap: '0.625rem',
                        padding: '1rem 2rem', borderRadius: '100px',
                        background: `linear-gradient(135deg, ${T.sky} 0%, #3478c8 100%)`,
                        color: '#fff', fontFamily: '"Plus Jakarta Sans", sans-serif',
                        fontSize: '0.9rem', fontWeight: 600, textDecoration: 'none',
                        boxShadow: `0 4px 24px ${T.sky}44`, transition: 'all 0.3s ease',
                    }}
                        onMouseEnter={(e) => { (e.currentTarget as HTMLElement).style.transform = 'translateY(-2px)'; (e.currentTarget as HTMLElement).style.boxShadow = `0 8px 40px ${T.sky}55`; }}
                        onMouseLeave={(e) => { (e.currentTarget as HTMLElement).style.transform = 'translateY(0)'; (e.currentTarget as HTMLElement).style.boxShadow = `0 4px 24px ${T.sky}44`; }}>
                        Commencer gratuitement
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M9 5l7 7-7 7"/>
                        </svg>
                    </Link>

                    <Link href="/login" style={{
                        display: 'inline-flex', alignItems: 'center', gap: '0.5rem',
                        padding: '1rem 2rem', borderRadius: '100px',
                        border: `1px solid ${T.border}`, background: T.bgCard,
                        color: T.textMid, fontFamily: '"Plus Jakarta Sans", sans-serif',
                        fontSize: '0.9rem', fontWeight: 500, textDecoration: 'none', transition: 'all 0.3s ease',
                    }}
                        onMouseEnter={(e) => { (e.currentTarget as HTMLElement).style.borderColor = T.borderHov; (e.currentTarget as HTMLElement).style.color = T.text; }}
                        onMouseLeave={(e) => { (e.currentTarget as HTMLElement).style.borderColor = T.border; (e.currentTarget as HTMLElement).style.color = T.textMid; }}>
                        Se connecter
                    </Link>
                </div>

                <div style={{ marginTop: '2.5rem', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '1.5rem', flexWrap: 'wrap' }}>
                    {[
                        { icon: '🛡', text: 'Gratuit pour commencer' },
                        { icon: '✓', text: 'Sans engagement' },
                        { icon: '⚡', text: 'Accès immédiat' },
                    ].map((item) => (
                        <div key={item.text} style={{ display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
                            <span style={{ fontSize: '0.875rem', color: '#22c55e', opacity: 0.8 }}>{item.icon}</span>
                            <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.75rem', color: T.textDim }}>
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
            `}</style>
        </section>
    );
}
