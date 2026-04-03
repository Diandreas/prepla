import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useTokens } from './landing-theme';

const featureKeys = [
    { icon: '✦', accentKey: 'sky' as const, labelKey: 'landing.feature_ai_label', titleKey: 'landing.feature_ai_title', descKey: 'landing.feature_ai_desc' },
    { icon: '⚡', accentKey: 'gold' as const, labelKey: 'landing.feature_instant_label', titleKey: 'landing.feature_instant_title', descKey: 'landing.feature_instant_desc' },
    { icon: '◎', accentKey: 'green' as const, labelKey: 'landing.feature_adaptive_label', titleKey: 'landing.feature_adaptive_title', descKey: 'landing.feature_adaptive_desc' },
    { icon: '↗', accentKey: 'violet' as const, labelKey: 'landing.feature_analytics_label', titleKey: 'landing.feature_analytics_title', descKey: 'landing.feature_analytics_desc' },
];

const ACCENT_COLORS = {
    sky: '#4A90E2',
    gold: '#F5A623',
    green: '#22c55e',
    violet: '#a78bfa',
};

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

function FeatureCard({ feature, index }: { feature: typeof featureKeys[0]; index: number }) {
    const T = useTokens();
    const { t } = useTranslation();
    const ref = useRef<HTMLDivElement>(null);
    const visible = useInView(ref as React.RefObject<HTMLElement>);
    const [hovered, setHovered] = useState(false);
    const accent = ACCENT_COLORS[feature.accentKey];

    return (
        <div ref={ref} onMouseEnter={() => setHovered(true)} onMouseLeave={() => setHovered(false)}
            style={{
                position: 'relative', borderRadius: '1.25rem',
                border: `1px solid ${hovered ? accent + '44' : T.border}`,
                background: hovered ? T.bgCardHov : T.bgCard,
                padding: '2rem 1.75rem 1.75rem',
                transition: 'all 0.4s cubic-bezier(0.4,0,0.2,1)',
                transform: visible
                    ? hovered ? 'translateY(-6px)' : 'translateY(0)'
                    : 'translateY(32px)',
                opacity: visible ? 1 : 0,
                transitionDelay: `${index * 0.12}s`,
                boxShadow: hovered
                    ? `0 20px 60px ${accent}20`
                    : T.theme === 'dark' ? '0 4px 24px rgba(0,0,0,0.2)' : '0 4px 16px rgba(26,22,18,0.06)',
                cursor: 'default',
            }}>
            <div style={{
                position: 'absolute', top: 0, right: 0,
                width: 120, height: 120, background: `${accent}18`,
                borderRadius: '50%', filter: 'blur(40px)',
                opacity: hovered ? 1 : 0, transition: 'opacity 0.4s ease', pointerEvents: 'none',
            }} />

            <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '1.5rem' }}>
                <div style={{
                    width: 52, height: 52, borderRadius: '0.875rem',
                    background: `${accent}14`, border: `1px solid ${accent}28`,
                    display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0,
                    transition: 'all 0.3s ease',
                    ...(hovered ? { background: `${accent}22`, border: `1px solid ${accent}44` } : {}),
                }}>
                    <span style={{ fontSize: '1.4rem', color: accent, lineHeight: 1 }}>{feature.icon}</span>
                </div>
                <span style={{
                    fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.65rem',
                    fontWeight: 700, letterSpacing: '0.12em', textTransform: 'uppercase', color: accent,
                }}>
                    {t(feature.labelKey)}
                </span>
            </div>

            <h3 style={{
                fontFamily: '"Cormorant Garamond", Georgia, serif',
                fontSize: 'clamp(1.3rem, 2vw, 1.55rem)', fontWeight: 700, lineHeight: 1.15,
                color: T.text, whiteSpace: 'pre-line', marginBottom: '1rem',
            }}>
                {t(feature.titleKey)}
            </h3>

            <p style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.875rem', lineHeight: 1.7, color: T.textMid }}>
                {t(feature.descKey)}
            </p>

            <div style={{
                position: 'absolute', bottom: 0, left: '1.75rem', right: '1.75rem',
                height: '1px', background: `linear-gradient(90deg, transparent, ${accent}44, transparent)`,
                opacity: hovered ? 1 : 0, transition: 'opacity 0.4s ease',
            }} />
        </div>
    );
}

export function FeaturesSection() {
    const T = useTokens();
    const { t } = useTranslation();
    const headerRef = useRef<HTMLDivElement>(null);
    const headerVisible = useInView(headerRef as React.RefObject<HTMLElement>);

    return (
        <section id="features" style={{ position: 'relative', background: T.bg, overflow: 'hidden', padding: 'clamp(5rem,10vw,8rem) 0' }}>
            <svg aria-hidden className="pointer-events-none" style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', opacity: T.gridOpacity }}>
                <defs>
                    <pattern id="feat-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke={T.gridColor} strokeWidth="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#feat-grid)" />
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
                        border: `1px solid ${T.gold}30`, background: `${T.gold}0a`,
                    }}>
                        <span style={{ color: T.gold }}>✦</span>
                        <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.65rem', fontWeight: 700, letterSpacing: '0.14em', textTransform: 'uppercase', color: T.gold }}>
                            {t('landing.features_badge')}
                        </span>
                    </div>

                    <h2 style={{
                        fontFamily: '"Cormorant Garamond", Georgia, serif',
                        fontSize: 'clamp(2rem, 4vw, 3.25rem)', fontWeight: 700, lineHeight: 1.1,
                        color: T.text, marginBottom: '1rem',
                    }}>
                        {t('landing.features_headline')}{' '}
                        <span style={{ fontStyle: 'italic', color: T.gold }}>{t('landing.features_headline_accent')}</span>
                    </h2>
                    <p style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '1rem', color: T.textMid, maxWidth: '36rem', margin: '0 auto', lineHeight: 1.7 }}>
                        {t('landing.features_body')}
                    </p>
                </div>

                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 280px), 1fr))', gap: '1.25rem' }}>
                    {featureKeys.map((f, i) => <FeatureCard key={f.labelKey} feature={f} index={i} />)}
                </div>
            </div>
        </section>
    );
}
