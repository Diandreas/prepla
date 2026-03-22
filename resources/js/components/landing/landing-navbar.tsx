import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import type { SharedData } from '@/types';
import { useLandingTheme, useTokens } from './landing-theme';

export function LandingNavbar() {
    const { auth } = usePage<SharedData>().props;
    const { toggle, theme } = useLandingTheme();
    const T = useTokens();
    const [scrolled, setScrolled] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 20);
        window.addEventListener('scroll', onScroll);
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    const navLinks = [
        { label: 'Langues', href: '#languages' },
        { label: 'Fonctionnalités', href: '#features' },
        { label: 'Tarifs', href: '#pricing' },
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
                <Link href="/" className="flex items-baseline gap-0.5 select-none" style={{ textDecoration: 'none' }}>
                    <span style={{
                        fontFamily: '"Cormorant Garamond", Georgia, serif',
                        fontSize: '1.65rem', fontWeight: 700, fontStyle: 'italic',
                        color: T.text, lineHeight: 1, letterSpacing: '-0.02em',
                    }}>Pre</span>
                    <span style={{
                        fontFamily: '"Cormorant Garamond", Georgia, serif',
                        fontSize: '1.65rem', fontWeight: 700, fontStyle: 'italic',
                        color: T.gold, lineHeight: 1, letterSpacing: '-0.02em',
                    }}>Pla</span>
                    <span className="ml-1.5 rounded-sm px-1 py-0.5 text-[9px] font-bold uppercase tracking-widest"
                        style={{ background: `${T.gold}18`, color: T.gold, letterSpacing: '0.1em' }}>
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
                    {/* Theme toggle */}
                    <button
                        onClick={toggle}
                        title={isDark ? 'Mode clair' : 'Mode sombre'}
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
                            // Sun icon
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
                            // Moon icon
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke={T.ink} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            </svg>
                        )}
                    </button>

                    {auth.user ? (
                        <Link href="/dashboard"
                            className="rounded-full px-5 py-2 text-sm font-semibold transition-all duration-200 hover:-translate-y-0.5"
                            style={{ background: `linear-gradient(135deg, ${T.sky} 0%, #3478c8 100%)`, color: '#fff', textDecoration: 'none' }}>
                            Mon tableau de bord
                        </Link>
                    ) : (
                        <>
                            <Link href="/login"
                                className="text-sm font-medium transition-colors duration-200"
                                style={{ color: T.textMid, textDecoration: 'none' }}
                                onMouseEnter={(e) => ((e.target as HTMLElement).style.color = T.text)}
                                onMouseLeave={(e) => ((e.target as HTMLElement).style.color = T.textMid)}>
                                Connexion
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
                                Commencer
                            </Link>
                        </>
                    )}
                </div>

                {/* Mobile hamburger */}
                <div className="flex items-center gap-2 md:hidden">
                    <button onClick={toggle} title="Thème"
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
                    <div className="flex flex-col gap-3 pt-2">
                        {auth.user ? (
                            <Link href="/dashboard" className="rounded-full py-3 text-center text-sm font-semibold"
                                style={{ background: `linear-gradient(135deg, ${T.sky} 0%, #3478c8 100%)`, color: '#fff', textDecoration: 'none' }}>
                                Mon tableau de bord
                            </Link>
                        ) : (
                            <>
                                <Link href="/login" className="rounded-full border py-3 text-center text-sm font-medium"
                                    style={{ borderColor: T.border, color: T.text, textDecoration: 'none' }}>
                                    Connexion
                                </Link>
                                <Link href="/register" className="rounded-full py-3 text-center text-sm font-semibold"
                                    style={{ background: `linear-gradient(135deg, ${T.sky} 0%, #3478c8 100%)`, color: '#fff', textDecoration: 'none' }}>
                                    Commencer gratuitement
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </nav>
    );
}
