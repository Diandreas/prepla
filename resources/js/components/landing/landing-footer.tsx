import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useTokens } from './landing-theme';

const socialLinks = [
    { icon: '✉', href: '#', label: 'Email' },
    { icon: '◈', href: '#', label: 'Discord' },
    { icon: '✕', href: '#', label: 'Twitter / X' },
];

export function LandingFooter() {
    const T = useTokens();
    const { t } = useTranslation();

    const footerLinks = {
        [t('landing.footer_col_product')]: [
            { label: t('landing.footer_link_features'), href: '#features' },
            { label: t('landing.footer_link_pricing'), href: '#pricing' },
            { label: t('landing.footer_link_languages'), href: '#languages' },
            { label: t('landing.footer_link_hiw'), href: '#hiw' },
        ],
        [t('landing.footer_col_company')]: [
            { label: t('landing.footer_link_about'), href: '#' },
            { label: t('landing.footer_link_blog'), href: '#' },
            { label: t('landing.footer_link_contact'), href: '#' },
        ],
        [t('landing.footer_col_legal')]: [
            { label: t('landing.footer_link_privacy'), href: '#' },
            { label: t('landing.footer_link_terms'), href: '#' },
            { label: t('landing.footer_link_cookies'), href: '#' },
        ],
    };

    return (
        <footer style={{
            position: 'relative',
            background: T.theme === 'dark' ? '#070b14' : '#e8e4dc',
            borderTop: `1px solid ${T.border}`,
            overflow: 'hidden',
        }}>
            <div style={{ position: 'absolute', top: 0, left: 0, right: 0, height: 1, background: `linear-gradient(90deg, transparent, ${T.sky}33, ${T.gold}33, transparent)` }} />

            <div style={{ maxWidth: '72rem', margin: '0 auto', padding: '4rem 1.5rem 2rem' }}>
                <div style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 180px), 1fr))',
                    gap: '2.5rem', marginBottom: '3.5rem',
                }}>
                    {/* Brand column */}
                    <div>
                        <Link href="/" style={{ display: 'inline-flex', alignItems: 'baseline', gap: '0.1rem', textDecoration: 'none', marginBottom: '1rem' }}>
                            <span style={{ fontFamily: '"Cormorant Garamond", Georgia, serif', fontSize: '1.5rem', fontWeight: 700, fontStyle: 'italic', color: T.text, lineHeight: 1, letterSpacing: '-0.02em' }}>Pre</span>
                            <span style={{ fontFamily: '"Cormorant Garamond", Georgia, serif', fontSize: '1.5rem', fontWeight: 700, fontStyle: 'italic', color: T.gold, lineHeight: 1, letterSpacing: '-0.02em' }}>Pla</span>
                            <span style={{ marginLeft: '0.35rem', padding: '0.15rem 0.4rem', borderRadius: '0.25rem', fontSize: '0.55rem', fontWeight: 700, letterSpacing: '0.1em', textTransform: 'uppercase', background: `${T.gold}15`, color: T.gold, fontFamily: '"Plus Jakarta Sans", sans-serif' }}>
                                IA
                            </span>
                        </Link>

                        <p style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.8rem', color: T.textMid, lineHeight: 1.7, maxWidth: '18rem', margin: '0 0 1.5rem' }}>
                            {t('landing.footer_tagline')}
                        </p>

                        <div style={{ display: 'flex', gap: '0.6rem' }}>
                            {socialLinks.map((s) => (
                                <a key={s.label} href={s.href} aria-label={s.label}
                                    style={{
                                        width: 34, height: 34, borderRadius: '0.5rem',
                                        border: `1px solid ${T.border}`, background: T.bgCard,
                                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                                        transition: 'all 0.25s ease', fontSize: '0.9rem', color: T.textMid, textDecoration: 'none',
                                    }}
                                    onMouseEnter={(e) => {
                                        (e.currentTarget as HTMLElement).style.borderColor = `${T.sky}44`;
                                        (e.currentTarget as HTMLElement).style.color = T.sky;
                                    }}
                                    onMouseLeave={(e) => {
                                        (e.currentTarget as HTMLElement).style.borderColor = T.border;
                                        (e.currentTarget as HTMLElement).style.color = T.textMid;
                                    }}>
                                    {s.icon}
                                </a>
                            ))}
                        </div>
                    </div>

                    {Object.entries(footerLinks).map(([category, links]) => (
                        <div key={category}>
                            <h4 style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.7rem', fontWeight: 700, letterSpacing: '0.12em', textTransform: 'uppercase', color: T.textDim, marginBottom: '1.25rem' }}>
                                {category}
                            </h4>
                            <ul style={{ listStyle: 'none', padding: 0, margin: 0, display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                                {links.map((link) => (
                                    <li key={link.label}>
                                        <a href={link.href} style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.85rem', color: T.textMid, textDecoration: 'none', transition: 'color 0.2s ease' }}
                                            onMouseEnter={(e) => ((e.target as HTMLElement).style.color = T.text)}
                                            onMouseLeave={(e) => ((e.target as HTMLElement).style.color = T.textMid)}>
                                            {link.label}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>

                <div style={{ height: '1px', background: T.border, marginBottom: '1.75rem' }} />

                <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', justifyContent: 'space-between', gap: '1rem' }}>
                    <p style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.75rem', color: T.textDim }}>
                        {t('landing.footer_copyright', { year: new Date().getFullYear() })}
                    </p>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
                        <span style={{ fontSize: '0.8rem', color: '#22c55e', opacity: 0.6 }}>🛡</span>
                        <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.7rem', color: T.textDim }}>
                            {t('landing.footer_gdpr')}
                        </span>
                    </div>
                </div>
            </div>
        </footer>
    );
}
