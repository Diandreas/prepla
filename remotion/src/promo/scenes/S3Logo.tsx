import React from 'react';
import { AbsoluteFill, random, useCurrentFrame } from 'remotion';
import { KineticText } from '../components/KineticText';
import { LOGO_RATIO, LogoMark, Wordmark } from '../components/Logo';
import { MINI } from '../components/MiniLogo';
import { textWidth } from '../components/textWidth';
import { usePromoContent } from '../content';
import { C, EASE, env, tw } from '../theme';

// Révélation (8–12 s) : impact lumineux, les facettes du « P » s'assemblent, le cube doré
// tombe à sa place, puis « Ton examen. Ton niveau. Ton parcours. » sur trois temps.
// En sortie, le logo file se ranger en haut à gauche (il y reste, discret, jusqu'à la fin).

const MARK_SIZE = 400;
const MARK_CENTER = { x: 540, y: 680 };
const WORD_SIZE = 150;
const WORD_CENTER_Y = 1010;
const CUBE = { x: 542, y: 638 };
const LAND = 20;

export const S3Logo: React.FC = () => {
    const frame = useCurrentFrame();
    const { tagline } = usePromoContent().logo;
    const prePlaWidth = textWidth('PrePla', 'jakarta800', WORD_SIZE, -0.03);

    const flash = 1 - tw(frame, 0, 16, 0, 1, EASE.outSoft);
    const wave = tw(frame, 0, 34, 0, 1, EASE.out);
    const rays = env(frame, 4, 30, 96, 20);

    // Sortie : vol vers la position du mini-logo.
    const fly = tw(frame, 104, 16, 0, 1, EASE.inOut);
    const markScale = 1 + (MINI.markSize / MARK_SIZE - 1) * fly;
    const markDx = (MINI.markCenter.x - MARK_CENTER.x) * fly;
    const markDy = (MINI.markCenter.y - MARK_CENTER.y) * fly;

    const badgeW = WORD_SIZE * 0.3 * 1.25 + WORD_SIZE * 0.28 + 6;
    const wordTotal = prePlaWidth + WORD_SIZE * 0.16 + badgeW;
    const wordLeft = 540 - wordTotal / 2;
    const wordScale = 1 + (MINI.wordSize / WORD_SIZE - 1) * fly;
    const wordDx = (MINI.wordLeft - wordLeft) * fly;
    const wordDy = (MINI.markCenter.y - WORD_CENTER_Y) * fly;

    return (
        <AbsoluteFill>
            {/* Rayons lumineux */}
            <div
                style={{
                    position: 'absolute',
                    left: MARK_CENTER.x - 900,
                    top: MARK_CENTER.y - 900,
                    width: 1800,
                    height: 1800,
                    borderRadius: '50%',
                    background: 'repeating-conic-gradient(from 0deg, rgba(106,170,246,0.13) 0deg 5deg, rgba(106,170,246,0) 5deg 15deg)',
                    WebkitMaskImage: 'radial-gradient(circle, black 0%, rgba(0,0,0,0.6) 30%, transparent 62%)',
                    maskImage: 'radial-gradient(circle, black 0%, rgba(0,0,0,0.6) 30%, transparent 62%)',
                    transform: `rotate(${frame * 0.22}deg) scale(${0.8 + rays * 0.2})`,
                    opacity: rays * (1 - fly),
                }}
            />

            {/* Onde de choc */}
            <svg width={1080} height={1920} style={{ position: 'absolute', inset: 0 }}>
                <circle
                    cx={MARK_CENTER.x}
                    cy={MARK_CENTER.y}
                    r={60 + wave * 1050}
                    fill="none"
                    stroke={C.skyLight}
                    strokeWidth={Math.max(0.5, 46 * (1 - wave))}
                    opacity={(1 - wave) * 0.9}
                />
                <circle
                    cx={MARK_CENTER.x}
                    cy={MARK_CENTER.y}
                    r={40 + tw(frame, 4, 40) * 700}
                    fill="none"
                    stroke={C.gold}
                    strokeWidth={Math.max(0.5, 18 * (1 - tw(frame, 4, 40)))}
                    opacity={(1 - tw(frame, 4, 40)) * 0.7}
                />
            </svg>

            {/* Éclats dorés quand le cube se pose */}
            {Array.from({ length: 18 }).map((_, i) => {
                const p = tw(frame, LAND, 26, 0, 1, EASE.out);
                if (frame < LAND || p >= 1) return null;
                const a = (i / 18) * Math.PI * 2 + random(`pa${i}`) * 0.5;
                const d = 140 + random(`pd${i}`) * 260;
                const s = 10 + random(`ps${i}`) * 14;
                return (
                    <div
                        key={i}
                        style={{
                            position: 'absolute',
                            left: CUBE.x + Math.cos(a) * d * p - s / 2,
                            top: CUBE.y + Math.sin(a) * d * p - s / 2,
                            width: s,
                            height: s,
                            borderRadius: 3,
                            background: i % 3 === 0 ? C.skyLight : C.gold,
                            transform: `rotate(${p * 240 + i * 20}deg) scale(${1 - p})`,
                            opacity: 1 - p * 0.6,
                        }}
                    />
                );
            })}

            {/* Le « P » */}
            <div
                style={{
                    position: 'absolute',
                    left: MARK_CENTER.x - (MARK_SIZE * LOGO_RATIO) / 2,
                    top: MARK_CENTER.y - MARK_SIZE / 2,
                    transform: `translate(${markDx}px, ${markDy}px) scale(${markScale})`,
                    transformOrigin: 'center center',
                    filter: `drop-shadow(0 30px 60px rgba(0,0,0,${0.5 * (1 - fly)})) drop-shadow(0 0 ${50 * (1 - fly)}px rgba(59,130,224,0.45))`,
                }}
            >
                <LogoMark size={MARK_SIZE} assembleAt={0} glossAt={44} />
            </div>

            {/* PrePla IA */}
            <div
                style={{
                    position: 'absolute',
                    left: wordLeft,
                    top: WORD_CENTER_Y - WORD_SIZE / 2,
                    height: WORD_SIZE,
                    display: 'flex',
                    alignItems: 'center',
                    transform: `translate(${wordDx}px, ${wordDy}px) scale(${wordScale})`,
                    transformOrigin: '0% 50%',
                }}
            >
                <Wordmark size={WORD_SIZE} revealAt={18} badgeAt={34} />
            </div>

            {/* Ton examen. Ton niveau. Ton parcours. */}
            <div style={{ position: 'absolute', left: 0, right: 0, top: 1150, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 14 }}>
                {tagline.map((line, i) => (
                    <KineticText key={line} lines={[line]} start={60 + i * 15} size={86} stagger={3} exit={98 + i * 2} accentScale={1.24} />
                ))}
            </div>

            {/* Flash d'impact */}
            <AbsoluteFill
                style={{
                    background: `radial-gradient(circle at ${MARK_CENTER.x}px ${MARK_CENTER.y}px, rgba(255,255,255,1) 0%, rgba(190,220,255,0.9) 25%, rgba(59,130,224,0.5) 60%, rgba(11,19,34,0) 100%)`,
                    opacity: flash * 0.95,
                }}
            />
        </AbsoluteFill>
    );
};
