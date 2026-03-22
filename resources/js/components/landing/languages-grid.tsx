import { useEffect, useRef, useState } from 'react';
import type { Language } from '@/data/languages';
import * as Flags from 'country-flag-icons/react/3x2';
import { useTokens } from './landing-theme';

function flagEmojiToCode(flag: string): string {
    const points = [...flag].map(c => c.codePointAt(0)! - 0x1F1E6);
    if (points.length === 2 && points[0] >= 0 && points[0] <= 25) {
        return String.fromCharCode(65 + points[0], 65 + points[1]);
    }
    return '';
}

function useInView(ref: React.RefObject<HTMLElement | null>, threshold = 0.1) {
    const [visible, setVisible] = useState(false);
    useEffect(() => {
        if (!ref.current) return;
        const obs = new IntersectionObserver(([e]) => { if (e.isIntersecting) setVisible(true); }, { threshold });
        obs.observe(ref.current);
        return () => obs.disconnect();
    }, [ref, threshold]);
    return visible;
}

function LangCard({ lang, index }: { lang: Language; index: number }) {
    const T = useTokens();
    const ref = useRef<HTMLDivElement>(null);
    const visible = useInView(ref as React.RefObject<HTMLElement>, 0.05);
    const [hovered, setHovered] = useState(false);

    return (
        <div ref={ref} onMouseEnter={() => setHovered(true)} onMouseLeave={() => setHovered(false)}
            style={{
                position: 'relative', borderRadius: '1.25rem',
                border: `1px solid ${hovered ? T.sky + '55' : T.border}`,
                background: hovered ? T.bgCardHov : T.bgCard,
                padding: '2rem 1.5rem', textAlign: 'center',
                transition: 'all 0.35s cubic-bezier(0.4,0,0.2,1)',
                transform: visible
                    ? hovered ? 'translateY(-6px) scale(1.02)' : 'translateY(0) scale(1)'
                    : 'translateY(24px) scale(0.97)',
                opacity: visible ? 1 : 0,
                transitionDelay: `${index * 0.1}s`,
                boxShadow: hovered ? `0 20px 50px ${T.sky}22` : T.theme === 'dark' ? 'none' : '0 4px 20px rgba(26,22,18,0.06)',
                cursor: 'default',
            }}>
            {/* Corner glow */}
            <div style={{
                position: 'absolute', top: 0, right: 0, width: 80, height: 80,
                background: `${T.sky}14`, borderRadius: '50%', filter: 'blur(24px)',
                opacity: hovered ? 1 : 0, transition: 'opacity 0.35s ease', pointerEvents: 'none',
            }} />

            {/* Flag */}
            <div style={{ marginBottom: '1rem', display: 'flex', justifyContent: 'center' }}>
                {(() => {
                    const code = flagEmojiToCode(lang.flag);
                    const FlagComponent = code ? (Flags as Record<string, React.ComponentType<{ style?: React.CSSProperties }>>)[code] : null;
                    return FlagComponent
                        ? <FlagComponent style={{ width: 56, borderRadius: 6, boxShadow: '0 2px 8px rgba(0,0,0,0.15)' }} />
                        : <span style={{ fontSize: '2.5rem', lineHeight: 1 }}>{lang.flag}</span>;
                })()}
            </div>

            {/* Name */}
            <h3 style={{
                fontFamily: '"Cormorant Garamond", Georgia, serif',
                fontSize: '1.15rem', fontWeight: 700, color: T.text, lineHeight: 1.2, marginBottom: '0.2rem',
            }}>
                {lang.name}
            </h3>
            <p style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.7rem', color: T.textDim, marginBottom: '1.25rem' }}>
                {lang.native_name}
            </p>

            {/* Exam badges */}
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.35rem', justifyContent: 'center' }}>
                {lang.exams.map((exam) => (
                    <span key={exam.slug} style={{
                        fontFamily: '"Plus Jakarta Sans", sans-serif',
                        fontSize: '0.6rem', fontWeight: 600,
                        padding: '0.25rem 0.6rem', borderRadius: '100px',
                        background: hovered ? `${T.sky}18` : T.theme === 'dark' ? 'rgba(255,255,255,0.06)' : 'rgba(26,22,18,0.06)',
                        border: `1px solid ${hovered ? T.sky + '33' : T.border}`,
                        color: hovered ? T.sky : T.textMid,
                        transition: 'all 0.3s ease', letterSpacing: '0.02em',
                    }}>
                        {exam.name}
                    </span>
                ))}
            </div>
        </div>
    );
}

export function LanguagesGrid({ languages }: { languages: Language[] }) {
    const T = useTokens();
    const headerRef = useRef<HTMLDivElement>(null);
    const headerVisible = useInView(headerRef as React.RefObject<HTMLElement>);

    return (
        <section id="languages" style={{ position: 'relative', background: T.bgAlt, overflow: 'hidden', padding: 'clamp(5rem,10vw,8rem) 0' }}>
            {/* Grid */}
            <svg aria-hidden className="pointer-events-none" style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', opacity: T.gridOpacity }}>
                <defs>
                    <pattern id="lang-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke={T.gridColor} strokeWidth="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#lang-grid)" />
            </svg>

            <div style={{ position: 'relative', maxWidth: '72rem', margin: '0 auto', padding: '0 1.5rem' }}>
                {/* Header */}
                <div ref={headerRef} style={{
                    textAlign: 'center', marginBottom: 'clamp(3rem,6vw,5rem)',
                    opacity: headerVisible ? 1 : 0,
                    transform: headerVisible ? 'translateY(0)' : 'translateY(24px)',
                    transition: 'all 0.7s cubic-bezier(0.4,0,0.2,1)',
                }}>
                    <div style={{
                        display: 'inline-flex', alignItems: 'center', gap: '0.5rem',
                        marginBottom: '1.25rem', padding: '0.35rem 1rem', borderRadius: '100px',
                        border: `1px solid ${T.gold}30`, background: `${T.gold}0a`,
                    }}>
                        <span style={{ width: 8, height: 8, borderRadius: '50%', background: T.gold, display: 'inline-block' }} />
                        <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.65rem', fontWeight: 700, letterSpacing: '0.14em', textTransform: 'uppercase', color: T.gold }}>
                            Langues disponibles
                        </span>
                    </div>

                    <h2 style={{
                        fontFamily: '"Cormorant Garamond", Georgia, serif',
                        fontSize: 'clamp(2rem, 4vw, 3.25rem)', fontWeight: 700, lineHeight: 1.1,
                        color: T.text, marginBottom: '1rem',
                    }}>
                        <span style={{ fontStyle: 'italic', color: T.gold }}>3 langues</span>,{' '}
                        8 examens officiels
                    </h2>
                    <p style={{
                        fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '1rem', color: T.textMid,
                        maxWidth: '34rem', margin: '0 auto', lineHeight: 1.7,
                    }}>
                        Choisissez votre langue cible et commencez à vous préparer avec des exercices calqués sur les vrais formats d'examen.
                    </p>
                </div>

                {/* Cards — 3 columns for 3 languages */}
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 240px), 1fr))', gap: '1.5rem' }}>
                    {languages.map((lang, i) => (
                        <LangCard key={lang.slug} lang={lang} index={i} />
                    ))}
                </div>

                {/* Stats strip */}
                <div style={{
                    marginTop: '3.5rem', display: 'flex', justifyContent: 'center',
                    gap: 'clamp(2rem, 6vw, 5rem)', flexWrap: 'wrap',
                    paddingTop: '2.5rem', borderTop: `1px solid ${T.border}`,
                }}>
                    {[
                        { value: '3', label: 'Langues' },
                        { value: '8', label: 'Examens officiels' },
                        { value: 'A1–C2', label: 'Tous niveaux CECR' },
                    ].map((s) => (
                        <div key={s.label} style={{ textAlign: 'center' }}>
                            <div style={{
                                fontFamily: '"Cormorant Garamond", Georgia, serif',
                                fontSize: 'clamp(1.75rem, 3vw, 2.5rem)', fontWeight: 700,
                                color: T.gold, lineHeight: 1, marginBottom: '0.25rem',
                            }}>
                                {s.value}
                            </div>
                            <div style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.8rem', color: T.textMid }}>
                                {s.label}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
