import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import type { SharedData } from '@/types';

export function LandingNavbar() {
    const { auth } = usePage<SharedData>().props;
    const [scrolled, setScrolled] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 20);
        window.addEventListener('scroll', onScroll);
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    const ink = '#1A2B48';        // Oxford Blue
    const sky = '#4A90E2';         // Sky Blue
    const gold = '#F5A623';        // Gold (badges only)
    const cream = '#F4F7F6';       // Pearl Gray
    const dimCream = 'rgba(244,247,246,0.6)';

    const navLinks = [
        { label: 'Langues', href: '#languages' },
        { label: 'Fonctionnalités', href: '#features' },
        { label: 'Tarifs', href: '#pricing' },
    ];

    return (
        <nav
            style={{
                position: 'sticky',
                top: 0,
                zIndex: 50,
                background: scrolled
                    ? `rgba(7,11,20,0.92)`
                    : ink,
                borderBottom: `1px solid rgba(212,168,67,${scrolled ? '0.15' : '0.08'})`,
                backdropFilter: 'blur(16px)',
                transition: 'all 0.3s ease',
            }}
        >
            <div
                style={{ maxWidth: '80rem', margin: '0 auto', padding: '0 1.5rem' }}
                className="flex h-16 items-center justify-between"
            >
                {/* Logo */}
                <Link
                    href="/"
                    className="flex items-baseline gap-0.5 select-none"
                    style={{ textDecoration: 'none' }}
                >
                    <span
                        style={{
                            fontFamily: '"Cormorant Garamond", Georgia, serif',
                            fontSize: '1.65rem',
                            fontWeight: 700,
                            fontStyle: 'italic',
                            color: cream,
                            lineHeight: 1,
                            letterSpacing: '-0.02em',
                        }}
                    >
                        Pre
                    </span>
                    <span
                        style={{
                            fontFamily: '"Cormorant Garamond", Georgia, serif',
                            fontSize: '1.65rem',
                            fontWeight: 700,
                            fontStyle: 'italic',
                            color: gold,
                            lineHeight: 1,
                            letterSpacing: '-0.02em',
                        }}
                    >
                        Pla
                    </span>
                    <span
                        className="ml-1.5 rounded-sm px-1 py-0.5 text-[9px] font-bold uppercase tracking-widest"
                        style={{ background: 'rgba(212,168,67,0.12)', color: gold, letterSpacing: '0.1em' }}
                    >
                        IA
                    </span>
                </Link>

                {/* Desktop nav links */}
                <div className="hidden items-center gap-8 md:flex">
                    {navLinks.map((link) => (
                        <a
                            key={link.href}
                            href={link.href}
                            className="text-sm font-medium transition-colors duration-200"
                            style={{ color: dimCream, textDecoration: 'none' }}
                            onMouseEnter={(e) => ((e.target as HTMLElement).style.color = cream)}
                            onMouseLeave={(e) => ((e.target as HTMLElement).style.color = dimCream)}
                        >
                            {link.label}
                        </a>
                    ))}
                </div>

                {/* Desktop CTAs */}
                <div className="hidden items-center gap-3 md:flex">
                    {auth.user ? (
                        <Link
                            href="/dashboard"
                            className="rounded-full px-5 py-2 text-sm font-semibold transition-all duration-200 hover:-translate-y-0.5"
                            style={{
                                background: `linear-gradient(135deg, ${sky} 0%, #3478c8 100%)`,
                                color: '#ffffff',
                                textDecoration: 'none',
                            }}
                        >
                            Mon tableau de bord
                        </Link>
                    ) : (
                        <>
                            <Link
                                href="/login"
                                className="text-sm font-medium transition-colors duration-200"
                                style={{ color: dimCream, textDecoration: 'none' }}
                                onMouseEnter={(e) => ((e.target as HTMLElement).style.color = cream)}
                                onMouseLeave={(e) => ((e.target as HTMLElement).style.color = dimCream)}
                            >
                                Connexion
                            </Link>
                            <Link
                                href="/register"
                                className="rounded-full border px-5 py-2 text-sm font-semibold transition-all duration-200 hover:-translate-y-0.5"
                                style={{
                                    borderColor: 'rgba(74,144,226,0.35)',
                                    color: sky,
                                    textDecoration: 'none',
                                    background: 'rgba(74,144,226,0.06)',
                                }}
                                onMouseEnter={(e) => {
                                    (e.currentTarget as HTMLElement).style.background = 'rgba(74,144,226,0.14)';
                                    (e.currentTarget as HTMLElement).style.borderColor = 'rgba(74,144,226,0.6)';
                                }}
                                onMouseLeave={(e) => {
                                    (e.currentTarget as HTMLElement).style.background = 'rgba(74,144,226,0.06)';
                                    (e.currentTarget as HTMLElement).style.borderColor = 'rgba(74,144,226,0.35)';
                                }}
                            >
                                Commencer
                            </Link>
                        </>
                    )}
                </div>

                {/* Mobile hamburger */}
                <button
                    className="flex flex-col items-center justify-center gap-1.5 p-2 md:hidden"
                    onClick={() => setMobileOpen(p => !p)}
                    aria-label="Menu"
                >
                    <span
                        className="block h-0.5 w-5 rounded-full transition-all duration-300"
                        style={{
                            background: cream,
                            transform: mobileOpen ? 'rotate(45deg) translateY(6px)' : 'none',
                        }}
                    />
                    <span
                        className="block h-0.5 w-5 rounded-full transition-all duration-300"
                        style={{
                            background: cream,
                            opacity: mobileOpen ? 0 : 1,
                        }}
                    />
                    <span
                        className="block h-0.5 w-5 rounded-full transition-all duration-300"
                        style={{
                            background: cream,
                            transform: mobileOpen ? 'rotate(-45deg) translateY(-6px)' : 'none',
                        }}
                    />
                </button>
            </div>

            {/* Mobile dropdown */}
            <div
                className="overflow-hidden md:hidden"
                style={{
                    maxHeight: mobileOpen ? '320px' : '0',
                    transition: 'max-height 0.35s cubic-bezier(0.4,0,0.2,1)',
                    borderTop: `1px solid rgba(212,168,67,0.1)`,
                }}
            >
                <div className="flex flex-col gap-4 px-6 py-5">
                    {navLinks.map((link) => (
                        <a
                            key={link.href}
                            href={link.href}
                            onClick={() => setMobileOpen(false)}
                            className="text-base font-medium"
                            style={{ color: cream, textDecoration: 'none' }}
                        >
                            {link.label}
                        </a>
                    ))}
                    <div className="flex flex-col gap-3 pt-2">
                        {auth.user ? (
                            <Link
                                href="/dashboard"
                                className="rounded-full py-3 text-center text-sm font-semibold"
                                style={{
                                    background: `linear-gradient(135deg, ${sky} 0%, #3478c8 100%)`,
                                    color: '#ffffff',
                                    textDecoration: 'none',
                                }}
                            >
                                Mon tableau de bord
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="rounded-full border py-3 text-center text-sm font-medium"
                                    style={{ borderColor: 'rgba(240,234,216,0.2)', color: cream, textDecoration: 'none' }}
                                >
                                    Connexion
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-full py-3 text-center text-sm font-semibold"
                                    style={{
                                        background: `linear-gradient(135deg, ${sky} 0%, #3478c8 100%)`,
                                        color: '#ffffff',
                                        textDecoration: 'none',
                                    }}
                                >
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
