import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import type { SharedData } from '@/types';
import { useLandingTheme, useTokens } from './landing-theme';

// En texte : les drapeaux emoji s'affichent « FR FR » sous Windows.
const LANG_OPTIONS = [
    { code: 'fr', label: 'FR', nom: 'Français' },
    { code: 'en', label: 'EN', nom: 'English' },
    { code: 'de', label: 'DE', nom: 'Deutsch' },
];

function LangSwitcher({ id }: { id: string }) {
    const { i18n, t } = useTranslation();
    const T = useTokens();
    const [open, setOpen] = useState(false);
    // La langue reellement affichee : un navigateur en espagnol voit l'anglais, et
    // le choix doit dire « EN », pas « FR ».
    const affichee = (i18n.resolvedLanguage ?? i18n.language ?? 'en').slice(0, 2);
    const current = LANG_OPTIONS.find((l) => l.code === affichee) ?? LANG_OPTIONS[1];

    return (
        <div style={{ position: 'relative' }}>
            <button
                type="button"
                onClick={() => setOpen((p) => !p)}
                aria-expanded={open}
                aria-controls={id}
                aria-label={t('landing.nav_langue')}
                style={{
                    display: 'flex', alignItems: 'center', gap: '0.35rem',
                    height: 40, padding: '0 0.75rem', borderRadius: '0.625rem',
                    border: `1px solid ${T.border}`, background: T.bgCard,
                    fontSize: '0.78rem', fontWeight: 700, letterSpacing: '0.04em', color: T.textMid,
                }}
            >
                {current.label}
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"
                    style={{ transition: 'transform 0.2s ease', transform: open ? 'rotate(180deg)' : 'none' }} aria-hidden="true">
                    <path d="M6 9l6 6 6-6" />
                </svg>
            </button>

            {open && (
                <div id={id} style={{
                    position: 'absolute', top: 'calc(100% + 8px)', right: 0, zIndex: 100, minWidth: 140,
                    background: T.dark ? '#111b2e' : '#ffffff', border: `1px solid ${T.border}`, borderRadius: '0.75rem',
                    boxShadow: T.dark ? '0 8px 32px rgba(0,0,0,0.4)' : '0 8px 24px rgba(26,22,18,0.1)', overflow: 'hidden',
                }}>
                    {LANG_OPTIONS.map((lang) => {
                        const actif = lang.code === current.code;

                        return (
                            <button
                                key={lang.code}
                                type="button"
                                aria-pressed={actif}
                                lang={lang.code}
                                onClick={() => { void i18n.changeLanguage(lang.code); setOpen(false); }}
                                style={{
                                    display: 'flex', alignItems: 'center', gap: '0.6rem', width: '100%', minHeight: 44,
                                    padding: '0 0.875rem', textAlign: 'left', fontSize: '0.85rem',
                                    fontWeight: actif ? 700 : 500, color: actif ? T.sky : T.textMid,
                                    background: actif ? `${T.sky}14` : 'transparent',
                                }}
                            >
                                <span style={{ fontWeight: 800, width: '1.6rem' }}>{lang.label}</span>
                                {lang.nom}
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

function BoutonTheme() {
    const { toggle, theme } = useLandingTheme();
    const T = useTokens();
    const { t } = useTranslation();
    const sombre = theme === 'dark';

    return (
        <button
            type="button"
            onClick={toggle}
            aria-label={sombre ? t('landing.theme_light') : t('landing.theme_dark')}
            title={sombre ? t('landing.theme_light') : t('landing.theme_dark')}
            style={{
                width: 40, height: 40, borderRadius: '0.625rem', border: `1px solid ${T.border}`,
                background: T.bgCard, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0,
            }}
        >
            {sombre ? (
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke={T.gold} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="4" /><path d="M12 2v3M12 19v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M2 12h3M19 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12" />
                </svg>
            ) : (
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke={T.ink} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                </svg>
            )}
        </button>
    );
}

export function LandingNavbar() {
    const { auth } = usePage<SharedData>().props;
    const T = useTokens();
    const { t } = useTranslation();
    const [scrolled, setScrolled] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 20);
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    const navLinks = [
        { label: t('landing.nav_etapes'), href: '#etapes' },
        { label: t('landing.nav_examens'), href: '#essayer' },
        { label: t('landing.nav_tarifs'), href: '#tarifs' },
        { label: t('landing.nav_centres'), href: '#centres' },
    ];

    return (
        <nav
            aria-label={t('landing.nav_aria')}
            style={{
                position: 'sticky', top: 0, zIndex: 50,
                background: scrolled ? T.navBgScroll : T.navBg,
                borderBottom: `1px solid ${T.navBorder}`,
                backdropFilter: 'blur(20px)',
                transition: 'background 0.3s ease',
            }}
        >
            <div style={{ maxWidth: '80rem', margin: '0 auto' }} className="landing-navbar-inner flex h-16 items-center justify-between gap-3">
                <Link href="/" className="flex shrink-0 items-center gap-2 select-none" aria-label="PrePla">
                    <img src="/icons/logo.png?v=4" alt="" width={32} height={32} style={{ objectFit: 'contain' }} />
                    <span className="flex items-baseline gap-0.5" aria-hidden="true">
                        <span style={{ fontFamily: '"Plus Jakarta Sans", system-ui, sans-serif', fontSize: '1.35rem', fontWeight: 800, color: T.text, lineHeight: 1, letterSpacing: '-0.02em' }}>Pre</span>
                        <span style={{ fontFamily: '"Plus Jakarta Sans", system-ui, sans-serif', fontSize: '1.35rem', fontWeight: 800, color: T.sky, lineHeight: 1, letterSpacing: '-0.02em' }}>Pla</span>
                    </span>
                </Link>

                {/* Liens : a partir de 1280 px, sinon dans le menu. */}
                <div className="hidden items-center gap-7 xl:flex">
                    {navLinks.map((link) => (
                        <a key={link.href} href={link.href} className="text-sm font-medium" style={{ color: T.textMid }}>
                            {link.label}
                        </a>
                    ))}
                </div>

                <div className="flex items-center gap-2 sm:gap-3">
                    <div className="hidden items-center gap-3 md:flex">
                        <LangSwitcher id="langue-bureau" />
                        <BoutonTheme />
                        {!auth.user && (
                            <Link href="/login" className="text-sm font-medium" style={{ color: T.textMid }}>
                                {t('landing.nav_login')}
                            </Link>
                        )}
                    </div>

                    {auth.user ? (
                        <Link href="/dashboard" className="hidden rounded-full px-5 py-2 text-sm font-semibold md:inline-flex"
                            style={{ background: `linear-gradient(135deg, ${T.sky} 0%, #3478c8 100%)`, color: '#fff' }}>
                            {t('landing.nav_dashboard')}
                        </Link>
                    ) : (
                        // L'essai, hors du menu, a toute hauteur de page.
                        <Link href="/essayer" className="inline-flex min-h-[40px] items-center rounded-full px-4 text-sm font-extrabold sm:px-5"
                            style={{ background: T.essai, color: T.surEssai, boxShadow: '0 4px 18px rgba(245,166,35,0.35)' }}>
                            <span className="sm:hidden">{t('landing.nav_essayer_court')}</span>
                            <span className="hidden sm:inline">{t('landing.nav_essayer')}</span>
                        </Link>
                    )}

                    <button
                        type="button"
                        className="flex h-11 w-11 flex-col items-center justify-center gap-1.5 xl:hidden"
                        onClick={() => setMobileOpen((p) => !p)}
                        aria-expanded={mobileOpen}
                        aria-controls="menu-accueil"
                        aria-label={t('landing.nav_menu')}
                    >
                        {[0, 1, 2].map((i) => (
                            <span key={i} className="block h-0.5 w-5 rounded-full transition-all duration-300" style={{
                                background: T.text,
                                transform: i === 0 && mobileOpen ? 'rotate(45deg) translateY(6px)' : i === 2 && mobileOpen ? 'rotate(-45deg) translateY(-6px)' : 'none',
                                opacity: i === 1 && mobileOpen ? 0 : 1,
                            }} />
                        ))}
                    </button>
                </div>
            </div>

            <div id="menu-accueil" className="xl:hidden" hidden={!mobileOpen} style={{ borderTop: `1px solid ${T.navBorder}` }}>
                <div className="flex flex-col gap-1 px-4 py-4 sm:px-6">
                    {navLinks.map((link) => (
                        <a key={link.href} href={link.href} onClick={() => setMobileOpen(false)}
                            className="flex min-h-[44px] items-center text-base font-medium" style={{ color: T.text }}>
                            {link.label}
                        </a>
                    ))}
                    <div className="flex items-center gap-3 pt-2 md:hidden">
                        <LangSwitcher id="langue-menu" />
                        <BoutonTheme />
                    </div>
                    <div className="flex flex-col gap-3 pt-3 md:hidden">
                        {auth.user ? (
                            <Link href="/dashboard" className="rounded-full py-3 text-center text-sm font-semibold"
                                style={{ background: `linear-gradient(135deg, ${T.sky} 0%, #3478c8 100%)`, color: '#fff' }}>
                                {t('landing.nav_dashboard')}
                            </Link>
                        ) : (
                            <>
                                <Link href="/login" className="rounded-full border py-3 text-center text-sm font-medium" style={{ borderColor: T.border, color: T.text }}>
                                    {t('landing.nav_login')}
                                </Link>
                                <Link href="/register" className="rounded-full py-3 text-center text-sm font-semibold"
                                    style={{ background: `linear-gradient(135deg, ${T.sky} 0%, #3478c8 100%)`, color: '#fff' }}>
                                    {t('landing.cta_compte')}
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </nav>
    );
}
