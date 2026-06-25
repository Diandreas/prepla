import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const SKY = '#4A90E2';
const GOLD = '#F5A623';

interface AuthLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

function useDarkMode() {
    const [isDark, setIsDark] = useState(() =>
        typeof document !== 'undefined' && document.documentElement.classList.contains('dark')
    );

    useEffect(() => {
        // Watch for class changes on <html> (triggered by useAppearance)
        const observer = new MutationObserver(() => {
            setIsDark(document.documentElement.classList.contains('dark'));
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        return () => observer.disconnect();
    }, []);

    return isDark;
}

export default function AuthSimpleLayout({ children, title, description }: AuthLayoutProps) {
    const isDark = useDarkMode();

    const bg           = isDark ? '#0d1221'                      : '#f0f3f8';
    const cardBg       = isDark ? 'rgba(255,255,255,0.04)'       : 'rgba(255,255,255,0.88)';
    const cardBorder   = isDark ? 'rgba(255,255,255,0.08)'       : 'rgba(26,43,72,0.1)';
    const cardShadow   = isDark ? '0 8px 40px rgba(0,0,0,0.3)'  : '0 8px 40px rgba(26,43,72,0.1)';
    const titleColor   = isDark ? '#f4f7f6'                      : '#1A2B48';
    const descColor    = isDark ? 'rgba(244,247,246,0.55)'       : 'rgba(26,43,72,0.5)';
    const logoText     = isDark ? '#f4f7f6'                      : '#1A2B48';
    const glowSky      = isDark ? 'rgba(74,144,226,0.08)'        : 'rgba(74,144,226,0.14)';
    const glowGold     = isDark ? 'rgba(245,166,35,0.06)'        : 'rgba(245,166,35,0.1)';
    const gridOpacity  = isDark ? 0.04                           : 0.07;
    const gridColor    = isDark ? SKY                            : '#1A2B48';

    return (
        <div style={{
            minHeight: '100svh',
            background: bg,
            display: 'flex', flexDirection: 'column',
            alignItems: 'center', justifyContent: 'center',
            padding: '1.5rem',
            position: 'relative', overflow: 'hidden',
            transition: 'background 0.3s ease',
        }}>
            {/* Grid */}
            <svg aria-hidden style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', opacity: gridOpacity, pointerEvents: 'none', transition: 'opacity 0.3s ease' }}>
                <defs>
                    <pattern id="auth-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke={gridColor} strokeWidth="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#auth-grid)" />
            </svg>

            {/* Glows */}
            <div style={{ position: 'absolute', top: '-10%', left: '20%', width: 400, height: 400, borderRadius: '50%', background: glowSky, filter: 'blur(80px)', pointerEvents: 'none', transition: 'background 0.3s ease' }} />
            <div style={{ position: 'absolute', bottom: '-10%', right: '15%', width: 360, height: 360, borderRadius: '50%', background: glowGold, filter: 'blur(80px)', pointerEvents: 'none', transition: 'background 0.3s ease' }} />

            <div style={{ position: 'relative', width: '100%', maxWidth: '26rem', display: 'flex', flexDirection: 'column', gap: '2rem' }}>

                {/* Logo */}
                <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '1rem' }}>
                    <Link href={route('home')} style={{ display: 'inline-flex', alignItems: 'center', gap: '0.75rem', textDecoration: 'none' }}>
                        <div style={{
                            width: 48, height: 48, borderRadius: '0.875rem',
                            background: 'linear-gradient(135deg, #1A2B48 0%, #2a3f6a 100%)',
                            border: '1px solid rgba(74,144,226,0.2)',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            boxShadow: '0 4px 20px rgba(26,43,72,0.4)',
                        }}>
                            <img src="/icons/logo.png?v=4" alt="PrePla" width={30} height={30} style={{ objectFit: 'contain', filter: 'brightness(0) saturate(100%) invert(100%)' }} />
                        </div>

                        <div style={{ display: 'flex', alignItems: 'baseline', gap: 0 }}>
                            <span style={{ fontFamily: '"Cormorant Garamond", Georgia, serif', fontSize: '1.85rem', fontWeight: 700, fontStyle: 'italic', color: logoText, lineHeight: 1, letterSpacing: '-0.02em', transition: 'color 0.3s ease' }}>Pre</span>
                            <span style={{ fontFamily: '"Cormorant Garamond", Georgia, serif', fontSize: '1.85rem', fontWeight: 700, fontStyle: 'italic', color: GOLD, lineHeight: 1, letterSpacing: '-0.02em' }}>Pla</span>
                            <span style={{ marginLeft: '0.4rem', padding: '0.2rem 0.45rem', borderRadius: '0.3rem', fontSize: '0.55rem', fontWeight: 700, letterSpacing: '0.1em', textTransform: 'uppercase', background: `${GOLD}15`, border: `1px solid ${GOLD}22`, color: GOLD, fontFamily: '"Plus Jakarta Sans", sans-serif' }}>IA</span>
                        </div>
                    </Link>

                    <div style={{ textAlign: 'center' }}>
                        <h1 style={{ fontFamily: '"Cormorant Garamond", Georgia, serif', fontSize: '1.5rem', fontWeight: 700, color: titleColor, margin: 0, lineHeight: 1.2, transition: 'color 0.3s ease' }}>{title}</h1>
                        {description && (
                            <p style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.85rem', color: descColor, margin: '0.5rem 0 0', lineHeight: 1.5, transition: 'color 0.3s ease' }}>{description}</p>
                        )}
                    </div>
                </div>

                {/* Form card — uses Tailwind CSS vars so inputs/labels/buttons adapt automatically */}
                <div style={{
                    borderRadius: '1.25rem',
                    border: `1px solid ${cardBorder}`,
                    background: cardBg,
                    backdropFilter: 'blur(20px)',
                    WebkitBackdropFilter: 'blur(20px)',
                    padding: '2rem',
                    boxShadow: cardShadow,
                    transition: 'background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease',
                }}>
                    {children}
                </div>
            </div>
        </div>
    );
}
