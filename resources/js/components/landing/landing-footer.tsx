import { Link } from '@inertiajs/react';

const SKY = '#4A90E2';
const GOLD = '#F5A623';
const CREAM = '#F4F7F6';
const DIM = 'rgba(244,247,246,0.4)';
const DIMMER = 'rgba(244,247,246,0.2)';

const footerLinks = {
    Produit: [
        { label: 'Fonctionnalités', href: '#features' },
        { label: 'Tarifs', href: '#pricing' },
        { label: 'Langues', href: '#languages' },
        { label: 'Comment ça marche', href: '#hiw' },
    ],
    Entreprise: [
        { label: 'À propos', href: '#' },
        { label: 'Blog', href: '#' },
        { label: 'Contact', href: '#' },
    ],
    Légal: [
        { label: 'Confidentialité', href: '#' },
        { label: 'CGU', href: '#' },
        { label: 'Cookies', href: '#' },
    ],
};

const socialLinks = [
    { icon: 'mail', href: '#', label: 'Email' },
    { icon: 'message-square', href: '#', label: 'Discord' },
    { icon: 'share', href: '#', label: 'Twitter / X' },
];

export function LandingFooter() {
    return (
        <footer
            style={{
                position: 'relative',
                background: '#070b14',
                borderTop: '1px solid rgba(255,255,255,0.05)',
                overflow: 'hidden',
            }}
        >
            {/* Top gradient fade */}
            <div style={{
                position: 'absolute',
                top: 0, left: 0, right: 0,
                height: 1,
                background: `linear-gradient(90deg, transparent, ${SKY}33, ${GOLD}33, transparent)`,
            }} />

            {/* Ambient glow */}
            <div style={{
                position: 'absolute',
                bottom: '-20%', left: '30%',
                width: 600, height: 300,
                background: 'rgba(74,144,226,0.04)',
                borderRadius: '50%',
                filter: 'blur(80px)',
                pointerEvents: 'none',
            }} />

            <div style={{ maxWidth: '72rem', margin: '0 auto', padding: '4rem 1.5rem 2rem' }}>
                {/* Top row: Brand + links */}
                <div
                    style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 180px), 1fr))',
                        gap: '2.5rem',
                        marginBottom: '3.5rem',
                    }}
                >
                    {/* Brand column */}
                    <div style={{ gridColumn: 'span 1' }}>
                        {/* Logo */}
                        <Link
                            href="/"
                            style={{ display: 'inline-flex', alignItems: 'baseline', gap: '0.1rem', textDecoration: 'none', marginBottom: '1rem' }}
                        >
                            <span
                                style={{
                                    fontFamily: '"Cormorant Garamond", Georgia, serif',
                                    fontSize: '1.5rem',
                                    fontWeight: 700,
                                    fontStyle: 'italic',
                                    color: CREAM,
                                    lineHeight: 1,
                                    letterSpacing: '-0.02em',
                                }}
                            >
                                Pre
                            </span>
                            <span
                                style={{
                                    fontFamily: '"Cormorant Garamond", Georgia, serif',
                                    fontSize: '1.5rem',
                                    fontWeight: 700,
                                    fontStyle: 'italic',
                                    color: GOLD,
                                    lineHeight: 1,
                                    letterSpacing: '-0.02em',
                                }}
                            >
                                Pla
                            </span>
                            <span
                                style={{
                                    marginLeft: '0.35rem',
                                    padding: '0.15rem 0.4rem',
                                    borderRadius: '0.25rem',
                                    fontSize: '0.55rem',
                                    fontWeight: 700,
                                    letterSpacing: '0.1em',
                                    textTransform: 'uppercase',
                                    background: `${GOLD}15`,
                                    color: GOLD,
                                    fontFamily: '"Plus Jakarta Sans", sans-serif',
                                }}
                            >
                                IA
                            </span>
                        </Link>

                        <p
                            style={{
                                fontFamily: '"Plus Jakarta Sans", sans-serif',
                                fontSize: '0.8rem',
                                color: DIM,
                                lineHeight: 1.7,
                                maxWidth: '18rem',
                                margin: '0 0 1.5rem',
                            }}
                        >
                            Préparation aux examens de langue alimentée par l'IA. 10 langues, 20+ certifications internationales.
                        </p>

                        {/* Social links */}
                        <div style={{ display: 'flex', gap: '0.6rem' }}>
                            {socialLinks.map((s) => (
                                <a
                                    key={s.label}
                                    href={s.href}
                                    aria-label={s.label}
                                    style={{
                                        width: 34, height: 34,
                                        borderRadius: '0.5rem',
                                        border: '1px solid rgba(255,255,255,0.08)',
                                        background: 'rgba(255,255,255,0.03)',
                                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                                        transition: 'all 0.25s ease',
                                    }}
                                    onMouseEnter={(e) => {
                                        (e.currentTarget as HTMLElement).style.borderColor = `${SKY}44`;
                                        (e.currentTarget as HTMLElement).style.background = `${SKY}0f`;
                                    }}
                                    onMouseLeave={(e) => {
                                        (e.currentTarget as HTMLElement).style.borderColor = 'rgba(255,255,255,0.08)';
                                        (e.currentTarget as HTMLElement).style.background = 'rgba(255,255,255,0.03)';
                                    }}
                                >
                                    <img
                                        src={`/icons/${s.icon}.png`}
                                        alt=""
                                        width={18} height={18}
                                        style={{ filter: `brightness(0) saturate(100%) invert(84%) sepia(5%) saturate(392%) hue-rotate(177deg) brightness(94%) contrast(86%)`, opacity: 0.7 }}
                                    />
                                </a>
                            ))}
                        </div>
                    </div>

                    {/* Link columns */}
                    {Object.entries(footerLinks).map(([category, links]) => (
                        <div key={category}>
                            <h4
                                style={{
                                    fontFamily: '"Plus Jakarta Sans", sans-serif',
                                    fontSize: '0.7rem',
                                    fontWeight: 700,
                                    letterSpacing: '0.12em',
                                    textTransform: 'uppercase',
                                    color: 'rgba(244,247,246,0.5)',
                                    marginBottom: '1.25rem',
                                }}
                            >
                                {category}
                            </h4>
                            <ul style={{ listStyle: 'none', padding: 0, margin: 0, display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                                {links.map((link) => (
                                    <li key={link.label}>
                                        <a
                                            href={link.href}
                                            style={{
                                                fontFamily: '"Plus Jakarta Sans", sans-serif',
                                                fontSize: '0.85rem',
                                                color: DIM,
                                                textDecoration: 'none',
                                                transition: 'color 0.2s ease',
                                            }}
                                            onMouseEnter={(e) => ((e.target as HTMLElement).style.color = CREAM)}
                                            onMouseLeave={(e) => ((e.target as HTMLElement).style.color = DIM as string)}
                                        >
                                            {link.label}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>

                {/* Divider */}
                <div style={{
                    height: '1px',
                    background: 'rgba(255,255,255,0.05)',
                    marginBottom: '1.75rem',
                }} />

                {/* Bottom row */}
                <div
                    style={{
                        display: 'flex',
                        flexWrap: 'wrap',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: '1rem',
                    }}
                >
                    <p style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.75rem', color: DIMMER }}>
                        © {new Date().getFullYear()} PrePla. Tous droits réservés.
                    </p>

                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
                        <img src="/icons/shield.png" alt="" width={14} height={14}
                            style={{ filter: `brightness(0) saturate(100%) invert(61%) sepia(69%) saturate(400%) hue-rotate(92deg)`, opacity: 0.5 }} />
                        <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.7rem', color: DIMMER }}>
                            Données sécurisées · RGPD conforme
                        </span>
                    </div>
                </div>
            </div>
        </footer>
    );
}
