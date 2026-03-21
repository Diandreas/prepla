import { Link } from '@inertiajs/react';

const INK = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';
const CREAM = '#F4F7F6';
const DIM = 'rgba(244,247,246,0.55)';

interface AuthLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div
            style={{
                minHeight: '100svh',
                background: '#0d1221',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                padding: '1.5rem',
                position: 'relative',
                overflow: 'hidden',
            }}
        >
            {/* Grid overlay */}
            <svg aria-hidden style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', opacity: 0.04, pointerEvents: 'none' }}>
                <defs>
                    <pattern id="auth-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke={SKY} strokeWidth="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#auth-grid)" />
            </svg>

            {/* Ambient glows */}
            <div style={{ position: 'absolute', top: '-10%', left: '20%', width: 400, height: 400, borderRadius: '50%', background: 'rgba(74,144,226,0.08)', filter: 'blur(80px)', pointerEvents: 'none' }} />
            <div style={{ position: 'absolute', bottom: '-10%', right: '15%', width: 360, height: 360, borderRadius: '50%', background: 'rgba(245,166,35,0.06)', filter: 'blur(80px)', pointerEvents: 'none' }} />

            <div style={{ position: 'relative', width: '100%', maxWidth: '26rem', display: 'flex', flexDirection: 'column', gap: '2rem' }}>
                {/* Logo */}
                <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '1rem' }}>
                    <Link
                        href={route('home')}
                        style={{ display: 'inline-flex', alignItems: 'center', gap: '0.75rem', textDecoration: 'none' }}
                    >
                        {/* Logo image */}
                        <div style={{
                            width: 48, height: 48,
                            borderRadius: '0.875rem',
                            background: `linear-gradient(135deg, ${INK} 0%, #2a3f6a 100%)`,
                            border: `1px solid rgba(74,144,226,0.2)`,
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            boxShadow: '0 4px 20px rgba(26,43,72,0.5)',
                        }}>
                            <img src="/icons/logo.png" alt="PrePla" width={30} height={30} style={{ objectFit: 'contain', filter: 'brightness(0) saturate(100%) invert(100%)' }} />
                        </div>

                        {/* Logo text */}
                        <div style={{ display: 'flex', alignItems: 'baseline', gap: 0 }}>
                            <span style={{
                                fontFamily: '"Cormorant Garamond", Georgia, serif',
                                fontSize: '1.85rem',
                                fontWeight: 700,
                                fontStyle: 'italic',
                                color: CREAM,
                                lineHeight: 1,
                                letterSpacing: '-0.02em',
                            }}>Pre</span>
                            <span style={{
                                fontFamily: '"Cormorant Garamond", Georgia, serif',
                                fontSize: '1.85rem',
                                fontWeight: 700,
                                fontStyle: 'italic',
                                color: GOLD,
                                lineHeight: 1,
                                letterSpacing: '-0.02em',
                            }}>Pla</span>
                            <span style={{
                                marginLeft: '0.4rem',
                                padding: '0.2rem 0.45rem',
                                borderRadius: '0.3rem',
                                fontSize: '0.55rem',
                                fontWeight: 700,
                                letterSpacing: '0.1em',
                                textTransform: 'uppercase',
                                background: `${GOLD}15`,
                                border: `1px solid ${GOLD}22`,
                                color: GOLD,
                                fontFamily: '"Plus Jakarta Sans", sans-serif',
                            }}>IA</span>
                        </div>
                    </Link>

                    {/* Title + description */}
                    <div style={{ textAlign: 'center' }}>
                        <h1 style={{
                            fontFamily: '"Cormorant Garamond", Georgia, serif',
                            fontSize: '1.5rem',
                            fontWeight: 700,
                            color: CREAM,
                            margin: 0,
                            lineHeight: 1.2,
                        }}>{title}</h1>
                        {description && (
                            <p style={{
                                fontFamily: '"Plus Jakarta Sans", sans-serif',
                                fontSize: '0.85rem',
                                color: DIM,
                                margin: '0.5rem 0 0',
                                lineHeight: 1.5,
                            }}>{description}</p>
                        )}
                    </div>
                </div>

                {/* Form card */}
                <div style={{
                    borderRadius: '1.25rem',
                    border: '1px solid rgba(255,255,255,0.08)',
                    background: 'rgba(255,255,255,0.04)',
                    backdropFilter: 'blur(20px)',
                    padding: '2rem',
                    boxShadow: '0 8px 40px rgba(0,0,0,0.3)',
                }}>
                    {children}
                </div>
            </div>
        </div>
    );
}
