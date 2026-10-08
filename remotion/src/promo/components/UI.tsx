import React from 'react';
import { interpolate, useCurrentFrame } from 'remotion';
import { C, EASE, FONT, tw } from '../theme';
import { Icon, type IconName } from './Icons';

/** Carte claire, comme les écrans de l'application, posée sur le décor sombre. */
export const Card: React.FC<{ children: React.ReactNode; style?: React.CSSProperties; radius?: number; padding?: number | string }> = ({
    children,
    style,
    radius = 44,
    padding = 48,
}) => (
    <div
        style={{
            position: 'relative',
            background: `linear-gradient(180deg, ${C.paper} 0%, ${C.paperSoft} 100%)`,
            borderRadius: radius,
            padding,
            boxShadow:
                '0 70px 120px -40px rgba(0,0,0,0.75), 0 30px 60px -30px rgba(0,0,0,0.55), inset 0 2px 0 rgba(255,255,255,0.9), 0 0 0 1.5px rgba(255,255,255,0.35)',
            color: C.ink,
            fontFamily: FONT.sans,
            ...style,
        }}
    >
        {children}
    </div>
);

/** Carte sombre translucide (style landing). */
export const GlassCard: React.FC<{ children: React.ReactNode; style?: React.CSSProperties; radius?: number; padding?: number | string }> = ({
    children,
    style,
    radius = 40,
    padding = 40,
}) => (
    <div
        style={{
            position: 'relative',
            background: 'linear-gradient(160deg, rgba(36,56,94,0.92) 0%, rgba(17,29,52,0.94) 100%)',
            border: '1.5px solid rgba(255,255,255,0.12)',
            borderRadius: radius,
            padding,
            boxShadow: '0 50px 100px -40px rgba(0,0,0,0.8), inset 0 1.5px 0 rgba(255,255,255,0.10)',
            color: C.text,
            fontFamily: FONT.sans,
            ...style,
        }}
    >
        {children}
    </div>
);

export type Tone = 'sky' | 'gold' | 'green' | 'red' | 'neutral' | 'dark';

const TONES: Record<Tone, { bg: string; fg: string; border: string }> = {
    sky: { bg: C.skyPale, fg: '#1d63c4', border: 'rgba(59,130,224,0.35)' },
    gold: { bg: C.goldPale, fg: '#b86e00', border: 'rgba(245,166,35,0.45)' },
    green: { bg: C.greenPale, fg: '#138a3e', border: 'rgba(34,197,94,0.4)' },
    red: { bg: C.redPale, fg: '#c42b2b', border: 'rgba(239,68,68,0.4)' },
    neutral: { bg: '#eef2f7', fg: C.inkSoft, border: C.paperLine },
    dark: { bg: 'rgba(255,255,255,0.08)', fg: C.text, border: 'rgba(255,255,255,0.16)' },
};

export const Pill: React.FC<{ tone?: Tone; icon?: IconName; size?: number; children: React.ReactNode; style?: React.CSSProperties }> = ({
    tone = 'sky',
    icon,
    size = 34,
    children,
    style,
}) => {
    const t = TONES[tone];
    return (
        <div
            style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: size * 0.35,
                padding: `${size * 0.42}px ${size * 0.7}px`,
                borderRadius: 999,
                background: t.bg,
                border: `2px solid ${t.border}`,
                color: t.fg,
                fontFamily: FONT.sans,
                fontWeight: 700,
                fontSize: size,
                lineHeight: 1,
                whiteSpace: 'nowrap',
                ...style,
            }}
        >
            {icon ? <Icon name={icon} size={size * 1.05} color={t.fg} stroke={2.4} /> : null}
            {children}
        </div>
    );
};

export type PointerKey = { f: number; x: number; y: number; tap?: boolean };

/** Doigt virtuel : se déplace entre les clés, s'enfonce et émet une onde à chaque tap. */
export const Pointer: React.FC<{ keys: PointerKey[]; hideAt: number; size?: number }> = ({ keys, hideAt, size = 74 }) => {
    const frame = useCurrentFrame();
    if (!keys.length) return null;
    const first = keys[0];
    const enterStart = first.f - 14;
    if (frame < enterStart || frame > hideAt + 12) return null;

    // Position : entrée depuis le bas à droite, puis interpolation entre les clés.
    let x = first.x;
    let y = first.y;
    if (frame < first.f) {
        const t = tw(frame, enterStart, 12, 0, 1, EASE.out);
        x = first.x + (1 - t) * 160;
        y = first.y + (1 - t) * 260;
    } else {
        for (let i = 0; i < keys.length - 1; i++) {
            const a = keys[i];
            const b = keys[i + 1];
            if (frame >= a.f) {
                const t = interpolate(frame, [a.f + 4, b.f - 3], [0, 1], {
                    extrapolateLeft: 'clamp',
                    extrapolateRight: 'clamp',
                    easing: EASE.inOutSoft,
                });
                x = a.x + (b.x - a.x) * t;
                y = a.y + (b.y - a.y) * t;
            }
        }
    }

    const appear = tw(frame, enterStart, 10) * (1 - tw(frame, hideAt, 10, 0, 1, EASE.in));
    let press = 0;
    const ripples: Array<{ t: number }> = [];
    for (const k of keys) {
        if (!k.tap) continue;
        press = Math.max(press, interpolate(frame, [k.f - 3, k.f, k.f + 7], [0, 1, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' }));
        if (frame >= k.f && frame <= k.f + 22) ripples.push({ t: (frame - k.f) / 22 });
    }

    return (
        <div style={{ position: 'absolute', left: x - size / 2, top: y - size / 2, width: size, height: size, pointerEvents: 'none', zIndex: 50 }}>
            {ripples.map((r, i) => (
                <div
                    key={i}
                    style={{
                        position: 'absolute',
                        inset: 0,
                        borderRadius: '50%',
                        border: '4px solid rgba(255,255,255,0.95)',
                        transform: `scale(${1 + EASE.out(r.t) * 1.9})`,
                        opacity: (1 - r.t) * 0.85,
                    }}
                />
            ))}
            <div
                style={{
                    position: 'absolute',
                    inset: 0,
                    borderRadius: '50%',
                    background: 'radial-gradient(circle at 40% 35%, rgba(255,255,255,0.98), rgba(225,235,250,0.92))',
                    boxShadow: '0 14px 30px rgba(0,0,0,0.45), 0 0 0 5px rgba(255,255,255,0.28)',
                    transform: `scale(${appear * (1 - press * 0.22)})`,
                    opacity: appear,
                }}
            />
        </div>
    );
};

/** Barre de progression fine. */
export const ProgressBar: React.FC<{ value: number; width: number; height?: number; color?: string; track?: string }> = ({
    value,
    width,
    height = 14,
    color = C.sky,
    track = '#e6edf6',
}) => (
    <div style={{ width, height, borderRadius: height, background: track, overflow: 'hidden' }}>
        <div
            style={{
                width: `${Math.max(0, Math.min(1, value)) * 100}%`,
                height: '100%',
                borderRadius: height,
                background: `linear-gradient(90deg, ${color} 0%, ${color}dd 100%)`,
            }}
        />
    </div>
);
