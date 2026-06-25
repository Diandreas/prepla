import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import type { SharedData } from '@/types';
import { useLandingTheme, useTokens } from './landing-theme';

const LANG_OPTIONS = [
    { code: 'fr', label: 'FR', flag: '🇫🇷' },
    { code: 'en', label: 'EN', flag: '🇬🇧' },
    { code: 'de', label: 'DE', flag: '🇩🇪' },
];

function LangSwitcher() {
    const { i18n } = useTranslation();
    const T = useTokens();
    const [open, setOpen] = useState(false);
    const current = LANG_OPTIONS.find(l => l.code === i18n.language) ?? LANG_OPTIONS[0];

    const switchLang = (code: string) => {
        i18n.changeLanguage(code);
        setOpen(false);
    };

    return (
        <div style={{ position: 'relative' }}>
            <button
                onClick={() => setOpen(p => !p)}
                style={{
                    display: 'flex', alignItems: 'center', gap: '0.35rem',
                    height: 36, padding: '0 0.75rem',
                    borderRadius: '0.625rem',
                    border: `1px solid ${T.border}`,
                    background: T.bgCard,
                    cursor: 'pointer',
                    fontSize: '0.75rem', fontWeight: 700, letterSpacing: '0.04em',
                    color: T.textMid,
                    transition: 'all 0.2s ease',
                    flexShrink: 0,
                }}
                onMouseEnter={(e) => {
                    (e.currentTarget as HTMLElement).style.borderColor = T.borderHov;
                    (e.currentTarget as HTMLElement).style.color = T.text;
                }}
                onMouseLeave={(e) => {
                    (e.currentTarget as HTMLElement).style.borderColor = T.border;
                    (e.currentTarget as HTMLElement).style.color = T.textMid;
                }}
            >
                <span style={{ fontSize: '0.9rem', lineHeight: 1 }}>{current.flag}</span>
                {current.label}
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"
                    style={{ transition: 'transform 0.2s ease', transform: open ? 'rotate(180deg)' : 'none' }}>
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </button>

            {open && (
                <div style={{
                    position: 'absolute', top: 'calc(100% + 8px)', right: 0,
                    background: T.bgCard, border: `1px solid ${T.border}`,
                    borderRadius: '0.75rem',
                    boxShadow: T.theme === 'dark' ? '0 8px 32px rgba(0,0,0,0.4)' : '0 8px 24px rgba(26,22,18,0.1)',
                    overflow: 'hidden', zIndex: 100, minWidth: 110,
                }}>
                    {LANG_OPTIONS.map((lang) => (
                        <button
                            key={lang.code}
                            onClick={() => switchLang(lang.code)}
                            style={{
                                display: 'flex', alignItems: 'center', gap: '0.5rem',
                                width: '100%', padding: '0.6rem 0.875rem',
                                background: lang.code === i18n.language ? `${T.sky}12` : 'transparent',
                                border: 'none', cursor: 'pointer',
                                fontSize: '0.8rem', fontWeight: lang.code === i18n.language ? 700 : 500,
                                color: lang.code === i18n.language ? T.sky : T.textMid,
                                textAlign: 'left',
                                transition: 'background 0.15s ease',
                            }}
                            onMouseEnter={(e) => {
                                if (lang.code !== i18n.language) (e.currentTarget as HTMLElement).style.background = `${T.sky}08`;
                            }}
                            onMouseLeave={(e) => {
                                if (lang.code !== i18n.language) (e.currentTarget as HTMLElement).style.background = 'transparent';
                            }}
                        >
                            <span style={{ fontSize: '1rem' }}>{lang.flag}</span>
                            {lang.label}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

export function LandingNavbar() {
    const { auth } = usePage<SharedData>().props;
    const { toggle, theme } = useLandingTheme();
    const T = useTokens();
    const { t } = useTranslation();
    const [scrolled, setScrolled] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 20);
        window.addEventListener('scroll', onScroll);
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    const navLinks = [
        { label: t('landing.nav_languages'), href: '#languages' },
        { label: t('landing.nav_features'), href: '#features' },
        { label: t('landing.nav_pricing'), href: '#pricing' },
    ];

    const isDark = theme === 'dark';

    return (
        <nav
            style={{
                position: 'sticky',
                top: 0,
                zIndex: 50,
                background: scrolled ? T.navBgScroll : T.navBg,
                borderBottom: `1px solid ${T.navBorder}`,
                backdropFilter: 'blur(20px)',
                transition: 'all 0.3s ease',
            }}
        >
            <div
                style={{ maxWidth: '80rem', margin: '0 auto', padding: '0 1.5rem' }}
                className="flex h-16 items-center justify-between"
            >
                {/* Logo */}
                <Link href="/" className="flex items-center gap-2 select-none" style={{ textDecoration: 'none' }}>
                    <img src="/icons/logo.png?v=4" alt="PrePla" width={32} height={32} style={{ objectFit: 'contain' }} />
                    <span className="flex items-baseline gap-0.5">
                        <span style={{
                            fontFamily: '"Plus Jakarta Sans", system-ui, sans-serif',
                            fontSize: '1.35rem', fontWeight: 800,
                            color: T.text, lineHeight: 1, letterSpacing: '-0.02em',
                        }}>Pre</span>
                        <span style={{
                            fontFamily: '"Plus Jakarta Sans", system-ui, sans-serif',
                            fontSize: '1.35rem', fontWeight: 800,
                            color: T.sky, lineHeight: 1, letterSpacing: '-0.02em',
                        }}>Pla</span>
                    </span>
                    <span className="rounded-md px-1 py-0.5 text-[9px] font-bold uppercase tracking-widest"
                        style={{ background: `${T.gold}1a`, color: T.gold, letterSpacing: '0.1em' }}>
                        IA
                    </span>
                </Link>

                {/* Desktop nav links */}
                <div className="hidden items-center gap-8 md:flex">
                    {navLinks.map((link) => (
                        <a key={link.href} href={link.href}
                            className="text-sm font-medium transition-colors duration-200"
                            style={{ color: T.textMid, textDecoration: 'none' }}
                            onMouseEnter={(e) => ((e.target as HTMLElement).style.color = T.text)}
                            onMouseLeave={(e) => ((e.target as HTMLElement).style.color = T.textMid)}
                        >
                            {link.label}
                        </a>
                    ))}
                </div>

                {/* Desktop CTAs + Theme toggle */}
                <div className="hidden items-center gap-3 md:flex">
                    <LangSwitcher />
                    <button
                        onClick={toggle}
                        title={isDark ? t('landing.theme_light') : t('landing.theme_dark')}
                        style={{
                            width: 36, height: 36,
                            borderRadius: '0.625rem',
                            border: `1px solid ${T.border}`,
                            background: T.bgCard,
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            cursor: 'pointer',
                            transition: 'all 0.25s ease',
                            flexShrink: 0,
                        }}
                        onMouseEnter={(e) => {
                            (e.currentTarget as HTMLElement).style.borderColor = T.borderHov;
                            (e.currentTarget as HTMLElement).style.background = T.bgCardHov;
                        }}
                        onMouseLeave={(e) => {
                            (e.currentTarget as HTMLElement).style.borderColor = T.border;
                            (e.currentTarget as HTMLElement).style.background = T.bgCard;
                        }}
                    >
                        {isDark ? (
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke={T.gold} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <circle cx="12" cy="12" r="4"/>
                                <line x1="12" y1="2" x2="12" y2="5"/>
                                <line x1="12" y1="19" x2="12" y2="22"/>
                                <line x1="4.22" y1="4.22" x2="6.34" y2="6.34"/>
                                <line x1="17.66" y1="17.66" x2="19.78" y2="19.78"/>
                                <line x1="2" y1="12" x2="5" y2="12"/>
                                <line x1="19" y1="12" x2="22" y2="12"/>
                                <line x1="4.22" y1="19.78" x2="6.34" y2="17.66"/>
                                <line x1="17.66" y1="6.34" x2="19.78" y2="4.22"/>
                            </svg>
                        ) : (
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke={T.ink} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            </svg>
                        )}
                    </button>

                    {auth.user ? (
                        <Link href="/dashboard"
                            className="rounded-full px-5 py-2 text-sm font-semibold transition-all duration-200 hover:-translate-y-0.5"
                            style={{ background: `linear-gradient(135deg, ${T.sky} 0%, #3478c8 100%)`, color: '#fff', textDecoration: 'none' }}>
                            {t('landing.nav_dashboard')}
                        </Link>
                    ) : (
                        <>
                            <Link href="/login"
                                className="text-sm font-medium transition-colors duration-200"
                                style={{ color: T.textMid, textDecoration: 'none' }}
                                onMouseEnter={(e) => ((e.target as HTMLElement).style.color = T.text)}
                                onMouseLeave={(e) => ((e.target as HTMLElement).style.color = T.textMid)}>
                                {t('landing.nav_login')}
                            </Link>
                            <Link href="/register"
                                className="rounded-full border px-5 py-2 text-sm font-semibold transition-all duration-200 hover:-translate-y-0.5"
                                style={{
                                    borderColor: `${T.sky}44`, color: T.sky,
                                    textDecoration: 'none', background: `${T.sky}0c`,
                                }}
                                onMouseEnter={(e) => {
                                    (e.currentTarget as HTMLElement).style.background = `${T.sky}18`;
                                    (e.currentTarget as HTMLElement).style.borderColor = `${T.sky}66`;
                                }}
                                onMouseLeave={(e) => {
                                    (e.currentTarget as HTMLElement).style.background = `${T.sky}0c`;
                                    (e.currentTarget as HTMLElement).style.borderColor = `${T.sky}44`;
                                }}>
                                {t('landing.nav_start')}
                            </Link>
                        </>
                    )}
                </div>

                {/* Mobile hamburger */}
                <div className="flex items-center gap-2 md:hidden">
                    <button onClick={toggle} title={t('landing.theme_dark')}
                        style={{
                            width: 32, height: 32, borderRadius: '0.5rem',
                            border: `1px solid ${T.border}`, background: T.bgCard,
                            display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer',
                        }}>
                        {isDark
                            ? <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke={T.gold} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/></svg>
                            : <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke={T.ink} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                        }
                    </button>
                    <button className="flex flex-col items-center justify-center gap-1.5 p-2"
                        onClick={() => setMobileOpen(p => !p)} aria-label="Menu">
                        {[0, 1, 2].map(i => (
                            <span key={i} className="block h-0.5 w-5 rounded-full transition-all duration-300" style={{
                                background: T.text,
                                transform: i === 0 && mobileOpen ? 'rotate(45deg) translateY(6px)'
                                    : i === 2 && mobileOpen ? 'rotate(-45deg) translateY(-6px)' : 'none',
                                opacity: i === 1 && mobileOpen ? 0 : 1,
                            }} />
                        ))}
                    </button>
                </div>
            </div>

            {/* Mobile dropdown */}
            <div className="overflow-hidden md:hidden"
                style={{ maxHeight: mobileOpen ? '320px' : '0', transition: 'max-height 0.35s cubic-bezier(0.4,0,0.2,1)', borderTop: `1px solid ${T.navBorder}` }}>
                <div className="flex flex-col gap-4 px-6 py-5">
                    {navLinks.map((link) => (
                        <a key={link.href} href={link.href} onClick={() => setMobileOpen(false)}
                            className="text-base font-medium" style={{ color: T.text, textDecoration: 'none' }}>
                            {link.label}
                        </a>
                    ))}
                    <div style={{ paddingTop: '0.25rem' }}>
                        <LangSwitcher />
                    </div>
                    <div className="flex flex-col gap-3 pt-2">
                        {auth.user ? (
                            <Link href="/dashboard" className="rounded-full py-3 text-center text-sm font-semibold"
                                style={{ background: `linear-gradient(135deg, ${T.sky} 0%, #3478c8 100%)`, color: '#fff', textDecoration: 'none' }}>
                                {t('landing.nav_dashboard')}
                            </Link>
                        ) : (
                            <>
                                <Link href="/login" className="rounded-full border py-3 text-center text-sm font-medium"
                                    style={{ borderColor: T.border, color: T.text, textDecoration: 'none' }}>
                                    {t('landing.nav_login')}
                                </Link>
                                <Link href="/register" className="rounded-full py-3 text-center text-sm font-semibold"
                                    style={{ background: `linear-gradient(135deg, ${T.sky} 0%, #3478c8 100%)`, color: '#fff', textDecoration: 'none' }}>
                                    {t('landing.hero_cta_primary')}
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </nav>
    );
}
