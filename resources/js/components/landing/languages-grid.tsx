import { useEffect, useRef, useState } from 'react';
import type { Language } from '@/data/languages';

const SKY = '#4A90E2';
const GOLD = '#F5A623';
const CREAM = '#F4F7F6';
const DIM = 'rgba(244,247,246,0.55)';

interface LanguagesGridProps {
    languages: Language[];
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
    const ref = useRef<HTMLDivElement>(null);
    const visible = useInView(ref as React.RefObject<HTMLElement>, 0.05);
    const [hovered, setHovered] = useState(false);

    return (
        <div
            ref={ref}
            onMouseEnter={() => setHovered(true)}
            onMouseLeave={() => setHovered(false)}
            style={{
                position: 'relative',
                borderRadius: '1rem',
                border: `1px solid ${hovered ? SKY + '55' : 'rgba(255,255,255,0.07)'}`,
                background: hovered
                    ? 'rgba(74,144,226,0.08)'
                    : 'rgba(255,255,255,0.025)',
                padding: '1.5rem 1.25rem',
                textAlign: 'center',
                transition: 'all 0.35s cubic-bezier(0.4,0,0.2,1)',
                transform: visible
                    ? hovered ? 'translateY(-4px) scale(1.02)' : 'translateY(0) scale(1)'
                    : 'translateY(24px) scale(0.97)',
                opacity: visible ? 1 : 0,
                transitionDelay: `${(index % 5) * 0.06}s`,
                boxShadow: hovered ? `0 12px 40px rgba(74,144,226,0.2)` : 'none',
                cursor: 'default',
            }}
        >
            {/* Corner glow on hover */}
            <div style={{
                position: 'absolute', top: 0, right: 0,
                width: 80, height: 80,
                background: 'rgba(74,144,226,0.12)',
                borderRadius: '50%',
                filter: 'blur(24px)',
                opacity: hovered ? 1 : 0,
                transition: 'opacity 0.35s ease',
                pointerEvents: 'none',
            }} />

            {/* Flag */}
            <div style={{ fontSize: '2.5rem', lineHeight: 1, marginBottom: '0.75rem' }}>
                {lang.flag}
            </div>

            {/* Name */}
            <h3
                style={{
                    fontFamily: '"Cormorant Garamond", Georgia, serif',
                    fontSize: '1.05rem',
                    fontWeight: 700,
                    color: CREAM,
                    lineHeight: 1.2,
                    marginBottom: '0.2rem',
                }}
            >
                {lang.name}
            </h3>

            {/* Native name */}
            <p style={{
                fontFamily: '"Plus Jakarta Sans", sans-serif',
                fontSize: '0.7rem',
                color: 'rgba(244,247,246,0.35)',
                marginBottom: '1rem',
            }}>
                {lang.native_name}
            </p>

            {/* Exam badges */}
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.3rem', justifyContent: 'center' }}>
                {lang.exams.slice(0, 3).map((exam) => (
                    <span
                        key={exam.slug}
                        style={{
                            fontFamily: '"Plus Jakarta Sans", sans-serif',
                            fontSize: '0.6rem',
                            fontWeight: 600,
                            padding: '0.2rem 0.5rem',
                            borderRadius: '100px',
                            background: hovered ? `${SKY}22` : 'rgba(255,255,255,0.06)',
                            border: `1px solid ${hovered ? SKY + '33' : 'rgba(255,255,255,0.1)'}`,
                            color: hovered ? SKY : DIM,
                            transition: 'all 0.3s ease',
                            letterSpacing: '0.02em',
                        }}
                    >
                        {exam.name}
                    </span>
                ))}
                {lang.exams.length > 3 && (
                    <span
                        style={{
                            fontFamily: '"Plus Jakarta Sans", sans-serif',
                            fontSize: '0.6rem',
                            fontWeight: 600,
                            padding: '0.2rem 0.5rem',
                            borderRadius: '100px',
                            background: `${GOLD}12`,
                            border: `1px solid ${GOLD}22`,
                            color: GOLD,
                        }}
                    >
                        +{lang.exams.length - 3}
                    </span>
                )}
            </div>
        </div>
    );
}

export function LanguagesGrid({ languages }: LanguagesGridProps) {
    const headerRef = useRef<HTMLDivElement>(null);
    const headerVisible = useInView(headerRef as React.RefObject<HTMLElement>);

    return (
        <section
            id="languages"
            style={{
                position: 'relative',
                background: '#0d1221',
                overflow: 'hidden',
                padding: 'clamp(5rem,10vw,8rem) 0',
            }}
        >
            {/* Grid overlay */}
            <svg aria-hidden className="pointer-events-none" style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', opacity: 0.04 }}>
                <defs>
                    <pattern id="lang-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke={SKY} strokeWidth="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#lang-grid)" />
            </svg>

            {/* Ambient glows */}
            <div style={{ position: 'absolute', inset: 0, pointerEvents: 'none' }}>
                <div style={{ position: 'absolute', top: '-5%', right: '20%', width: 420, height: 420, borderRadius: '50%', background: 'rgba(245,166,35,0.05)', filter: 'blur(80px)' }} />
                <div style={{ position: 'absolute', bottom: '-5%', left: '10%', width: 380, height: 380, borderRadius: '50%', background: 'rgba(74,144,226,0.06)', filter: 'blur(80px)' }} />
            </div>

            <div style={{ position: 'relative', maxWidth: '72rem', margin: '0 auto', padding: '0 1.5rem' }}>
                {/* Header */}
                <div
                    ref={headerRef}
                    style={{
                        textAlign: 'center',
                        marginBottom: 'clamp(3rem,6vw,5rem)',
                        opacity: headerVisible ? 1 : 0,
                        transform: headerVisible ? 'translateY(0)' : 'translateY(24px)',
                        transition: 'all 0.7s cubic-bezier(0.4,0,0.2,1)',
                    }}
                >
                    <div
                        style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '0.5rem',
                            marginBottom: '1.25rem',
                            padding: '0.35rem 1rem',
                            borderRadius: '100px',
                            border: `1px solid ${GOLD}33`,
                            background: `${GOLD}0c`,
                        }}
                    >
                        <img src="/icons/languages.png" alt="" width={16} height={16}
                            style={{ filter: 'brightness(0) saturate(100%) invert(71%) sepia(55%) saturate(855%) hue-rotate(1deg)' }} />
                        <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.65rem', fontWeight: 700, letterSpacing: '0.14em', textTransform: 'uppercase', color: GOLD }}>
                            Langues disponibles
                        </span>
                    </div>

                    <h2
                        style={{
                            fontFamily: '"Cormorant Garamond", Georgia, serif',
                            fontSize: 'clamp(2rem, 4vw, 3.25rem)',
                            fontWeight: 700,
                            lineHeight: 1.1,
                            color: CREAM,
                            marginBottom: '1rem',
                        }}
                    >
                        <span style={{ fontStyle: 'italic', color: GOLD }}>10 langues</span>,{' '}
                        plus de 20 examens officiels
                    </h2>
                    <p
                        style={{
                            fontFamily: '"Plus Jakarta Sans", sans-serif',
                            fontSize: '1rem',
                            color: DIM,
                            maxWidth: '34rem',
                            margin: '0 auto',
                            lineHeight: 1.7,
                        }}
                    >
                        Choisissez votre langue cible et commencez à vous préparer avec des exercices calqués sur les vrais formats d'examen.
                    </p>
                </div>

                {/* Language cards grid */}
                <div
                    style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fill, minmax(min(100%, 160px), 1fr))',
                        gap: '1rem',
                    }}
                >
                    {languages.map((lang, i) => (
                        <LangCard key={lang.slug} lang={lang} index={i} />
                    ))}
                </div>

                {/* Stats strip */}
                <div
                    style={{
                        marginTop: '3.5rem',
                        display: 'flex',
                        justifyContent: 'center',
                        gap: 'clamp(2rem, 6vw, 5rem)',
                        flexWrap: 'wrap',
                        paddingTop: '2.5rem',
                        borderTop: '1px solid rgba(255,255,255,0.06)',
                    }}
                >
                    {[
                        { value: '10', label: 'Langues' },
                        { value: '20+', label: 'Examens officiels' },
                        { value: 'A1–C2', label: 'Tous niveaux CECR' },
                    ].map((s) => (
                        <div key={s.label} style={{ textAlign: 'center' }}>
                            <div
                                style={{
                                    fontFamily: '"Cormorant Garamond", Georgia, serif',
                                    fontSize: 'clamp(1.75rem, 3vw, 2.5rem)',
                                    fontWeight: 700,
                                    color: GOLD,
                                    lineHeight: 1,
                                    marginBottom: '0.25rem',
                                }}
                            >
                                {s.value}
                            </div>
                            <div style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.8rem', color: DIM }}>
                                {s.label}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
