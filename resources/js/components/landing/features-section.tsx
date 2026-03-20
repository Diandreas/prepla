import { useEffect, useRef, useState } from 'react';

const INK = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';
const CREAM = '#F4F7F6';
const DIM = 'rgba(244,247,246,0.55)';

const features = [
    {
        icon: 'sparkles',
        label: 'IA Générative',
        title: 'Exercices illimités,\ncréés par l\'IA',
        description: 'Chaque exercice est généré en temps réel par Mistral IA, adapté à votre niveau CECR et calqué sur le format exact de votre examen cible.',
        accent: SKY,
        glow: 'rgba(74,144,226,0.18)',
    },
    {
        icon: 'zap',
        label: 'Instantané',
        title: 'Corrections &\nexplications en direct',
        description: 'Soumettez votre réponse et obtenez une analyse détaillée en quelques secondes — grammaire, vocabulaire, registre de langue, tout est commenté.',
        accent: GOLD,
        glow: 'rgba(245,166,35,0.18)',
    },
    {
        icon: 'target',
        label: 'Adaptatif',
        title: 'Parcours sur\nmesure, semaine après semaine',
        description: 'L\'algorithme identifie vos lacunes et réoriente votre programme de révision là où vous en avez vraiment besoin — sans effort de votre part.',
        accent: '#22c55e',
        glow: 'rgba(34,197,94,0.15)',
    },
    {
        icon: 'trending-up',
        label: 'Analytiques',
        title: 'Progressez,\net le voyez vraiment',
        description: 'Tableaux de bord de compétences, historique de scores, streaks de pratique : votre évolution est visible, mesurable et motivante.',
        accent: '#a78bfa',
        glow: 'rgba(167,139,250,0.15)',
    },
];

function useInView(ref: React.RefObject<HTMLElement | null>, threshold = 0.15) {
    const [visible, setVisible] = useState(false);
    useEffect(() => {
        if (!ref.current) return;
        const obs = new IntersectionObserver(([e]) => { if (e.isIntersecting) setVisible(true); }, { threshold });
        obs.observe(ref.current);
        return () => obs.disconnect();
    }, [ref, threshold]);
    return visible;
}

function FeatureCard({ feature, index }: { feature: typeof features[0]; index: number }) {
    const ref = useRef<HTMLDivElement>(null);
    const visible = useInView(ref as React.RefObject<HTMLElement>);
    const [hovered, setHovered] = useState(false);

    return (
        <div
            ref={ref}
            onMouseEnter={() => setHovered(true)}
            onMouseLeave={() => setHovered(false)}
            style={{
                position: 'relative',
                borderRadius: '1.25rem',
                border: `1px solid ${hovered ? feature.accent + '55' : 'rgba(255,255,255,0.07)'}`,
                background: hovered
                    ? `linear-gradient(145deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0.02) 100%)`
                    : `linear-gradient(145deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%)`,
                backdropFilter: 'blur(12px)',
                padding: '2rem 1.75rem 1.75rem',
                transition: 'all 0.4s cubic-bezier(0.4,0,0.2,1)',
                transform: visible
                    ? hovered ? 'translateY(-6px)' : 'translateY(0)'
                    : 'translateY(32px)',
                opacity: visible ? 1 : 0,
                transitionDelay: `${index * 0.12}s`,
                boxShadow: hovered ? `0 20px 60px ${feature.glow}` : '0 4px 24px rgba(0,0,0,0.2)',
                cursor: 'default',
            }}
        >
            {/* Ambient corner glow */}
            <div
                style={{
                    position: 'absolute', top: 0, right: 0,
                    width: '120px', height: '120px',
                    background: feature.glow,
                    borderRadius: '50%',
                    filter: 'blur(40px)',
                    opacity: hovered ? 1 : 0,
                    transition: 'opacity 0.4s ease',
                    pointerEvents: 'none',
                }}
            />

            {/* Icon badge */}
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '1.5rem' }}>
                <div
                    style={{
                        width: 52, height: 52,
                        borderRadius: '0.875rem',
                        background: feature.accent + '18',
                        border: `1px solid ${feature.accent}33`,
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        flexShrink: 0,
                        transition: 'all 0.3s ease',
                        ...(hovered ? { background: feature.accent + '28', border: `1px solid ${feature.accent}55` } : {}),
                    }}
                >
                    <img
                        src={`/icons/${feature.icon}.png`}
                        alt=""
                        width={24} height={24}
                        style={{
                            filter: `brightness(0) saturate(100%) ${cssColorFilter(feature.accent)}`,
                            objectFit: 'contain',
                        }}
                    />
                </div>
                <span
                    style={{
                        fontFamily: '"Plus Jakarta Sans", sans-serif',
                        fontSize: '0.65rem',
                        fontWeight: 700,
                        letterSpacing: '0.12em',
                        textTransform: 'uppercase',
                        color: feature.accent,
                    }}
                >
                    {feature.label}
                </span>
            </div>

            {/* Title */}
            <h3
                style={{
                    fontFamily: '"Cormorant Garamond", Georgia, serif',
                    fontSize: 'clamp(1.3rem, 2vw, 1.55rem)',
                    fontWeight: 700,
                    lineHeight: 1.15,
                    color: CREAM,
                    whiteSpace: 'pre-line',
                    marginBottom: '1rem',
                }}
            >
                {feature.title}
            </h3>

            {/* Description */}
            <p
                style={{
                    fontFamily: '"Plus Jakarta Sans", sans-serif',
                    fontSize: '0.875rem',
                    lineHeight: 1.7,
                    color: DIM,
                }}
            >
                {feature.description}
            </p>

            {/* Bottom accent line */}
            <div
                style={{
                    position: 'absolute',
                    bottom: 0, left: '1.75rem', right: '1.75rem',
                    height: '1px',
                    background: `linear-gradient(90deg, transparent, ${feature.accent}44, transparent)`,
                    opacity: hovered ? 1 : 0,
                    transition: 'opacity 0.4s ease',
                }}
            />
        </div>
    );
}

// Approximate CSS filter string for common brand colors
function cssColorFilter(hex: string): string {
    const map: Record<string, string> = {
        [SKY]:    'invert(53%) sepia(55%) saturate(729%) hue-rotate(193deg) brightness(103%) contrast(91%)',
        [GOLD]:   'invert(71%) sepia(55%) saturate(855%) hue-rotate(1deg) brightness(100%) contrast(97%)',
        '#22c55e':'invert(61%) sepia(69%) saturate(400%) hue-rotate(92deg) brightness(95%) contrast(92%)',
        '#a78bfa':'invert(67%) sepia(40%) saturate(800%) hue-rotate(218deg) brightness(105%) contrast(95%)',
    };
    return map[hex] ?? 'invert(100%)';
}

export function FeaturesSection() {
    const headerRef = useRef<HTMLDivElement>(null);
    const headerVisible = useInView(headerRef as React.RefObject<HTMLElement>);

    return (
        <section
            id="features"
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
                    <pattern id="feat-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke={SKY} strokeWidth="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#feat-grid)" />
            </svg>

            {/* Ambient glows */}
            <div style={{ position: 'absolute', inset: 0, pointerEvents: 'none' }}>
                <div style={{ position: 'absolute', top: '-10%', left: '15%', width: 500, height: 500, borderRadius: '50%', background: 'rgba(74,144,226,0.06)', filter: 'blur(80px)' }} />
                <div style={{ position: 'absolute', bottom: '-10%', right: '10%', width: 400, height: 400, borderRadius: '50%', background: 'rgba(245,166,35,0.05)', filter: 'blur(80px)' }} />
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
                        <img src="/icons/sparkles.png" alt="" width={16} height={16}
                            style={{ filter: 'brightness(0) saturate(100%) invert(71%) sepia(55%) saturate(855%) hue-rotate(1deg)' }} />
                        <span style={{ fontFamily: '"Plus Jakarta Sans", sans-serif', fontSize: '0.65rem', fontWeight: 700, letterSpacing: '0.14em', textTransform: 'uppercase', color: GOLD }}>
                            Fonctionnalités
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
                        Tout ce qu'il vous faut pour{' '}
                        <span style={{ fontStyle: 'italic', color: GOLD }}>réussir</span>
                    </h2>
                    <p
                        style={{
                            fontFamily: '"Plus Jakarta Sans", sans-serif',
                            fontSize: '1rem',
                            color: DIM,
                            maxWidth: '36rem',
                            margin: '0 auto',
                            lineHeight: 1.7,
                        }}
                    >
                        Une plateforme conçue autour d'un seul objectif : vous amener au score que vous visez, le plus efficacement possible.
                    </p>
                </div>

                {/* Cards grid */}
                <div
                    style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 280px), 1fr))',
                        gap: '1.25rem',
                    }}
                >
                    {features.map((f, i) => <FeatureCard key={f.label} feature={f} index={i} />)}
                </div>
            </div>
        </section>
    );
}
