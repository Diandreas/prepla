import React from 'react';
import { useCurrentFrame } from 'remotion';
import { C, EASE, FONT, SPRING, sp, tw } from '../theme';
import { Icon, type IconName } from './Icons';

const TONES = {
    red: { bg: 'linear-gradient(135deg, #ff6b6b 0%, #e5533a 100%)', glow: 'rgba(239,68,68,0.5)', ring: C.red },
    gold: { bg: `linear-gradient(135deg, ${C.goldLight} 0%, ${C.gold} 100%)`, glow: 'rgba(245,166,35,0.5)', ring: C.gold },
};

/**
 * Tampon qui s'abat sur le temps fort : grand mot dans une pastille inclinée, onde de choc,
 * lisible sans le son (verdict « Incorrect », etc.).
 */
export const Stamp: React.FC<{ text: string; icon?: IconName; tone?: keyof typeof TONES; at: number; exitAt?: number; size?: number }> = ({
    text,
    icon,
    tone = 'red',
    at,
    exitAt,
    size = 88,
}) => {
    const frame = useCurrentFrame();
    if (frame < at) return null;
    const t = TONES[tone];
    const s = sp(frame, at, SPRING.bouncy);
    const out = exitAt === undefined ? 0 : tw(frame, exitAt, 8, 0, 1, EASE.in);
    const ring = tw(frame, at, 16, 0, 1, EASE.out);
    const fg = tone === 'gold' ? '#1b1204' : '#ffffff';
    return (
        <div style={{ position: 'relative', display: 'inline-flex', opacity: Math.min(1, s * 2.5) * (1 - out) }}>
            <div
                style={{
                    position: 'absolute',
                    inset: -ring * 120,
                    borderRadius: 999,
                    border: `${6 * (1 - ring)}px solid ${t.ring}`,
                    opacity: 1 - ring,
                }}
            />
            <div
                style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: size * 0.2,
                    padding: `${size * 0.14}px ${size * 0.45}px ${size * 0.14}px ${size * 0.32}px`,
                    borderRadius: size * 0.34,
                    background: t.bg,
                    color: fg,
                    fontFamily: FONT.sans,
                    fontWeight: 800,
                    fontSize: size,
                    lineHeight: 1.1,
                    letterSpacing: '-0.02em',
                    whiteSpace: 'nowrap',
                    boxShadow: `0 0 50px ${t.glow}, 0 18px 34px rgba(0,0,0,0.45)`,
                    transform: `scale(${(1.9 - 0.9 * s) * (1 - out * 0.2)}) rotate(-3deg)`,
                }}
            >
                {icon ? <Icon name={icon} size={size * 0.78} color={fg} stroke={3.6} /> : null}
                {text}
            </div>
        </div>
    );
};
